<?php

class getTableProcessor
{
    public $modx;
    /** @var pdoFetch $pdoTools */
    public $pdoTools;
    
    public $getTables;
    public $debug = [];
    public $old_rows = [];

    public $parent_old_row = [];
    public $parent_old_row_id;

    /**
     * @param modX $modx
     * @param array $config
     */
    function __construct(getTables & $getTables, array $config = [])
    {
       // $this->getTable =& $getTable;
        $this->getTables =& $getTables;
        $this->modx =& $this->getTables->modx;
        $this->pdoTools =& $this->getTables->pdoTools;
        
        $this->config = array_merge([
            
        ], $config);
        
    }
    public function run_triggers($class, $type, $method, $fields, $object_old, $object_new =[], $object = null)
    {
        $getTablesRunTriggers = $this->modx->invokeEvent('getTablesRunTriggers', [
            'class'=>$class,
            'type'=>$type,
            'method'=>$method,
            'fields'=>$fields,
            'object_old'=>$object_old,
            'object_new'=>$object_new,
            'getTables'=>$this->getTables,
            'object'=>&$object,
        ]);
        if (is_array($getTablesRunTriggers)) {
            $canSave = false;
            foreach ($getTablesRunTriggers as $msg) {
                if (!empty($msg)) {
                    $canSave .= $msg."\n";
                }
            }
        } else {
            $canSave = $getTablesRunTriggers;
        }
        if(!empty($canSave)) return $this->error($canSave);
        
        $triggers = $this->config['triggers'];
        //$this->getTables->addTime("run_triggers ".print_r($triggers,1));
        if(isset($triggers[$class]['function']) and isset($triggers[$class]['model'])){
            $response = $this->getTables->loadService($triggers[$class]['model']);
            if(is_array($response) and $response['success']){
                $service = $this->getTables->models[$triggers[$class]['model']]['service'];
                if(method_exists($service,$triggers[$class]['function'])){ 
                    //$this->getTables->addTime("run_triggers function");
                    return  $service->{$triggers[$class]['function']}($class, $type, $method, $fields, $object_old, $object_new);
                }
            }
        }
        if(isset($triggers[$class]['gtsfunction']) and isset($triggers[$class]['model'])){
            $response = $this->getTables->loadService($triggers[$class]['model']);
            if(is_array($response) and $response['success']){
                $service = $this->getTables->models[$triggers[$class]['model']]['service'];
                if(method_exists($service,$triggers[$class]['gtsfunction'])){ 
                    //$this->getTables->addTime("run_triggers gtsfunction");
                    return  $service->{$triggers[$class]['gtsfunction']}($this->getTables,$class, $type, $method, $fields, $object_old, $object_new);
                }
            }
        }
        if(isset($triggers[$class]['gtsfunction2']) and isset($triggers[$class]['model'])){
            $response = $this->getTables->loadService($triggers[$class]['model']);
            if(is_array($response) and $response['success']){
                $service = $this->getTables->models[$triggers[$class]['model']]['service'];
                if(method_exists($service,$triggers[$class]['gtsfunction2'])){ 
                    //$this->getTables->addTime("run_triggers gtsfunction");
                    $params = [
                        'class'=>$class,
                        'type'=>$type,
                        'method'=>$method,
                        'fields'=>$fields,
                        'object_old'=>$object_old,
                        'object_new'=>$object_new,
                        'getTables'=>$this->getTables,
                        'object'=>&$object,
                    ];
                    return  $service->{$triggers[$class]['gtsfunction2']}($params);
                }
            }
        }
        return $this->success('Выполнено успешно');
    }
    
    public function walkFunc(&$item, $key, $sub_default){
        $item = $this->pdoTools->getChunk("@INLINE ".$item, ['sub_default'=>$sub_default]);
    }
    
    public function walkFuncInsertMenuId(&$item, $key, $id){
        $item = str_replace("insert_menu_id",$id,$item);
    }
    public function run($action, $table, $data = array())
    {
        
        if(!(isset($table['actions'][$action]) or ($action == "autosave" and !empty($table['autosave'])
        or ($action == "sort" and !empty($table['sortable']))))){ 
            if(!isset($table['actions'][$data['button_data']['name']])){
                return $this->error("Action $action not found! ",$table);
            }
        }
        if($this->getTables->REQUEST['pageID']){
            array_walk_recursive($table,array(&$this, 'walkFuncInsertMenuId'),$this->getTables->REQUEST['pageID']);
        }
        $this->current_action = $table['actions'][$action];
        if(isset($table['actions'][$data['button_data']['name']])){
            $this->current_action = $table['actions'][$data['button_data']['name']];
        }
        $this->action = $action;
        $edit_tables = [];
        ////$this->getTables->addDebug($table['edits'],'run $table[edits] ');
        foreach($table['edits'] as $edit){
            if($edit['type'] == 'view') continue;
            $edit_tables[$edit['class']][] = $edit;
        }
        
        if($action != "sort" and $action != "insert"){
            $resp = $this->check_rows($table, $data);
            if(!$resp['success']) return $resp;
        }
        
        switch($action){
            case 'update':
                $response = $this->update($table, $edit_tables, $data);
                break;
            case 'create':
                $response = $this->update($table, $edit_tables, $data, true);
                break;
            case 'insert':
                $response = $this->insert($table, $edit_tables);
                break;
            case 'insert_child':
                $response = $this->insert_child($table, $edit_tables, $data);
                break;
            case 'toggle':
                $response = $this->sets($table, $edit_tables, $data);
                break;
            case 'set':
                $response = $this->sets($table, $edit_tables, $data);
                break;
            case 'remove':
                $response = $this->remove($table, $edit_tables, $data);
                break;
            case 'autosave':
                $response = $this->autosave($table, $edit_tables, $data);
                break;
            case 'copy':
                $response = $this->copy($table, $edit_tables, $data);
                break;
            case 'sort':
                $response = $this->sort($table, $edit_tables, $data);
                break;
            default:
                $response = $this->error("Action $action not found! ",$table);
                break;
        }
        
        return $response;
    }
    public function insert($table, $edit_tables)
    {
        $create = true;
        $class = $table['class'];
        if($edit_tables[$class]){
            $set_data = [];
            foreach($edit_tables[$class] as $edit){
                if($edit['default']) $edit['force'] = $edit['default'];
                if($edit['force']){
                    switch($edit['type']){
                        case 'date':
                            $edit['force'] = date('Y-m-d',strtotime($edit['force']));
                            break;
                        case 'datetime':
                            $edit['force'] = date('Y-m-d H:i',strtotime($edit['force']));
                            break;
                    }
                    switch($edit['force']){
                        case 'user_id':
                            $edit['force'] = $this->modx->user->id;
                            break;
                    }
                    $data[$edit['field']] = $edit['force'];
                }
                if($data[$edit['field']] !== null)
                    $set_data[$edit['field']] = $data[$edit['field']];

                if($edit['type'] == 'date'){
                    if(isset($data[$edit['field']])){
                        if($data[$edit['field']] == ''){
                            $set_data[$edit['field']] = null;
                        }else{
                            $set_data[$edit['field']] = date('Y-m-d',strtotime($data[$edit['field']]));
                        }
                    }
                }
                
                if($edit['type'] == 'datetime'){
                    if(isset($data[$edit['field']])){
                        if($data[$edit['field']] == ''){
                            $set_data[$edit['field']] = null;
                        }else{
                            $set_data[$edit['field']] = date('Y-m-d H:i',strtotime($data[$edit['field']]));
                        }
                    }
                }
            }
            if(isset($table['defaultFieldSet'])){
                foreach($table['defaultFieldSet'] as $df=>$dfv){
                    if($dfv['class'] == $class)
                        $set_data[$df] = $dfv['value'];
                }
            }
            if(isset($table['role']['where'])){
                foreach($table['role']['where'] as $k=>$v){
                    $k = str_replace("`","",$k);
                    $arr = explode(".",$k);
                    if($arr[0] == $class){
                        if($v == 'id'){
                            $set_data[$arr[1]] = (int)$table['role']['id'];
                        }else{
                            $set_data[$arr[1]] = $v;
                        }
                    }
                    
                }
            }
            $set_data_event = $set_data;
            
            $saveobj = ['success'=>false,'class'=>$class];
            //$saved[] = $data;
            //$this->getTables->addDebug($set_data,'$set_data update');
            if($create){
                $obj = $this->modx->newObject($class);
                $data['id'] = false;
                $type = 'insert';
                //sortable
                if(isset($table['sortable']) and is_array($table['sortable'])){
                    if($table['sortable']['field']){
                        $field = $table['sortable']['field'];
                        if(is_array($table['sortable']['where'])){
                            $where = $table['sortable']['where'];
                        }else{
                            $where = [];
                        }
                        $c = $this->modx->newQuery($class);
                        $c->select("MAX($field) as max");
                        $c->where($where);
                        $max = 0;
                        if ($c->prepare() && $c->stmt->execute()) {
                            $max = $c->stmt->fetchColumn();
                        }
                        $set_data[$table['sortable']['field']] = $max + 1;
                    }
                }
                // $this->getTables->addDebug($table['sortable'],'sortable update');
                // $this->getTables->addDebug($set_data,'$set_data update');
            }
            if($obj){
                //$saved[] = $obj->toArray();
                $object_old = $obj->toArray();
                
                $obj->fromArray($set_data);
                $object_new = $obj->toArray();
                
                $resp = $this->run_triggers($class, 'before', $type, $set_data, $object_old,$object_new,$obj);
                if(!$resp['success']) return $resp;
                
                //$saved[] = $this->success('Сохранено успешно',$set_data);
                if($obj->save()){
                    
                    $object_new = $obj->toArray();
                    $resp = $this->run_triggers($class, 'after', $type, $set_data, $object_old,$object_new,$obj);
                    if(!$resp['success']) return $resp;
                    $data['id'] = $obj->id;
                    return $this->success($this->modx->lexicon('gettables_insert_successfully'),$data);
                }
            }
        }
        return $this->error($this->modx->lexicon('gettables_insert_error'));
    }
    public function insert_child($table, $edit_tables, $data = array())
    {
        if(empty($data['trs_data'])) return $this->error('trs_data empty');
        if(empty($table['tree']) or empty($table['tree']['idField']) or empty($table['tree']['parentIdField'])) return $this->error('tree table empty');

        $id = $data['trs_data'][0][$table['tree']['idField']];

        $create = true;
        $class = $table['class'];
        if($edit_tables[$class]){
            $set_data = [];
            foreach($edit_tables[$class] as $edit){
                if($edit['default']) $edit['force'] = $edit['default'];
                if($edit['force']){
                    switch($edit['type']){
                        case 'date':
                            $edit['force'] = date('Y-m-d',strtotime($edit['force']));
                            break;
                        case 'datetime':
                            $edit['force'] = date('Y-m-d H:i',strtotime($edit['force']));
                            break;
                    }
                    switch($edit['force']){
                        case 'user_id':
                            $edit['force'] = $this->modx->user->id;
                            break;
                    }
                    $data[$edit['field']] = $edit['force'];
                }
                if($data[$edit['field']] !== null)
                    $set_data[$edit['field']] = $data[$edit['field']];

                if($edit['type'] == 'date'){
                    if(isset($data[$edit['field']])){
                        if($data[$edit['field']] == ''){
                            $set_data[$edit['field']] = null;
                        }else{
                            $set_data[$edit['field']] = date('Y-m-d',strtotime($data[$edit['field']]));
                        }
                    }
                }
                
                if($edit['type'] == 'datetime'){
                    if(isset($data[$edit['field']])){
                        if($data[$edit['field']] == ''){
                            $set_data[$edit['field']] = null;
                        }else{
                            $set_data[$edit['field']] = date('Y-m-d H:i',strtotime($data[$edit['field']]));
                        }
                    }
                }
            }
            if(isset($table['defaultFieldSet'])){
                foreach($table['defaultFieldSet'] as $df=>$dfv){
                    if($dfv['class'] == $class)
                        $set_data[$df] = $dfv['value'];
                }
            }
            if(isset($table['role']['where'])){
                foreach($table['role']['where'] as $k=>$v){
                    $k = str_replace("`","",$k);
                    $arr = explode(".",$k);
                    if($arr[0] == $class){
                        if($v == 'id'){
                            $set_data[$arr[1]] = (int)$table['role']['id'];
                        }else{
                            $set_data[$arr[1]] = $v;
                        }
                    }
                    
                }
            }
            $set_data_event = $set_data;
            
            $saveobj = ['success'=>false,'class'=>$class];
            //$saved[] = $data;
            //$this->getTables->addDebug($set_data,'$set_data update');
            if($create){
                $obj = $this->modx->newObject($class);
                $data['id'] = false;
                $type = 'insert_child';
                $set_data[$table['tree']['parentIdField']] = $id;
                $set_data['fixed'] = 1;
                //sortable
                if(isset($table['sortable']) and is_array($table['sortable'])){
                    if($table['sortable']['field']){
                        $field = $table['sortable']['field'];
                        if(is_array($table['sortable']['where'])){
                            $where = $table['sortable']['where'];
                        }else{
                            $where = [];
                        }
                        $c = $this->modx->newQuery($class);
                        $c->select("MAX($field) as max");
                        $c->where($where);
                        $max = 0;
                        if ($c->prepare() && $c->stmt->execute()) {
                            $max = $c->stmt->fetchColumn();
                        }
                        $set_data[$table['sortable']['field']] = $max + 1;
                    }
                }
                // $this->getTables->addDebug($table['sortable'],'sortable update');
                // $this->getTables->addDebug($set_data,'$set_data update');
            }
            if($obj){
                //$saved[] = $obj->toArray();
                $object_old = $obj->toArray();
                
                $obj->fromArray($set_data);
                $object_new = $obj->toArray();
                
                $resp = $this->run_triggers($class, 'before', $type, $set_data, $object_old,$object_new,$obj);
                if(!$resp['success']) return $resp;
                
                //$saved[] = $this->success('Сохранено успешно',$set_data);
                if($obj->save()){
                    
                    $object_new = $obj->toArray();
                    $resp = $this->run_triggers($class, 'after', $type, $set_data, $object_old,$object_new,$obj);
                    if(!$resp['success']) return $resp;
                    $data['id'] = $obj->id;
                    return $this->success($this->modx->lexicon('gettables_insert_successfully'),$data);
                }
            }
        }

        return $this->error($this->modx->lexicon('gettables_insert_error'));
    }
    public function sort($table, $edit_tables, $data = array())
    {
        $class = $table["class"];
        $source = $this->modx->getObject($class, $data['source_id']);
        $target = $this->modx->getObject($class, $data['target_id']);
        
        if (empty($source) || empty($target)) {
            return $this->error('gettables_empty_indexes');
        }
        if(isset($table['sortable']) and is_array($table['sortable'])){
            if($table['sortable']['field']){
                $field = $table['sortable']['field'];
                if(is_array($table['sortable']['where'])){
                    $where = $table['sortable']['where'];
                }else{
                    $where = [];
                }
                $where_str = "";
                if(!empty($where)){
                    foreach($where as $k=>$v)
                    $where_str .= " AND $k = $v";
                }

                if ($source->get($field) < $target->get($field)) {
                    $this->modx->exec("UPDATE {$this->modx->getTableName($class)}
                        SET $field = $field - 1 WHERE
                            $field <= {$target->get($field)}
                            AND $field > {$source->get($field)}
                            AND $field > 0
                    ".$where_str);
        
                } else {
                    $this->modx->exec("UPDATE {$this->modx->getTableName($class)}
                        SET $field = $field + 1 WHERE
                        $field >= {$target->get($field)}
                            AND $field < {$source->get($field)}
                    ".$where_str);
                }
                $newRank = $target->get($field);
                $source->set($field,$newRank);
                $source->save();
                return $this->success($this->modx->lexicon('gettables_saved_successfully'));
            }
        }

        return $this->error("Error");
    }
    public function copy($table, $edit_tables, $data = array())
    {
        
        $saved = [];
        if(empty($this->old_rows)) return $this->error('old_rows empty');
        
        foreach($this->old_rows as $row){
            $old_row = $row;
            unset($row['id']);
            $resp = $this->update($table, $edit_tables, $row, true);
            $saved[] = $resp;

            if($resp['success']){
                $row['id'] = $resp['data']['id'];
                if(!empty($this->current_action['child']['subtables'])){
                    foreach($this->current_action['child']['subtables'] as $subtable_name){
                        $resp1 = $this->copy_subtables($subtable_name, $old_row, $row);
                        $resp1['subtables'] = 1;
                        $resp1['subtable_name'] = $subtable_name;
                        $saved[] = $resp1;
                    }
                }
                if(!empty($this->current_action['child']['many'])){
                    foreach($this->current_action['child']['many'] as $child_class=>$child_alias){
                        $resp1 = $this->copy_many($table['class'], $child_class, $child_alias, $old_row, $row);
                        $resp1['subtables'] = 1;
                        //$resp1['subtable_name'] = $field;
                        $saved[] = $resp1;
                    }
                }
                if(!empty($this->current_action['child']['one'])){
                    foreach($this->current_action['child']['one'] as $child_class=>$child_alias){
                        $resp1 = $this->copy_one($table['class'], $child_class, $child_alias, $old_row, $row);
                        $resp1['subtables'] = 1;
                        //$resp1['subtable_name'] = $field;
                        $saved[] = $resp1;
                    }
                }
                $resp = $this->run_triggers($table['class'], 'after', 'copy', [], $old_row,$row);
            }
            
        }
        
        $error = '';
        foreach($saved as $s){
            if(!$s['success']){
                if($s['subtables']){
                    $error = $s['subtable_name'].' '.$s['message'];
                }else{
                    $error = "Object {$s['class']} {$s['field']} не сохранен copy \r\n";
                }
            }
        }
        if(!$error){
            
            return $this->success($this->modx->lexicon('gettables_saved_successfully'),['id'=>$row['id'],'saved'=>$saved]);
        }else{
            return $this->error($error,$saved);
        }
    }
    public function copy_one($class, $child_class, $child_alias, $old_row, $new_row)
    {
        $saved = [];
        if(!$source = $this->modx->getObject($class,(int)$old_row['id']) or !$dest = $this->modx->getObject($class,(int)$new_row['id'])){
            return $this->error('class not found',array('class'=>$class));
        }
        if(!$ch = $source->getOne($child_alias)){
            return $this->success('child_alias not found',array('child_alias'=>$child_alias));
        }

            if($newchild = $this->modx->newObject($child_class,$ch->toArray())){
                $newchild->addOne($dest);
                $newchild->save();
            }
        return $this->success($this->modx->lexicon('gettables_saved_successfully'),$saved);
    }
    public function copy_many($class, $child_class, $child_alias, $old_row, $new_row)
    {
        $saved = [];
        if(!$source = $this->modx->getObject($class,(int)$old_row['id']) or !$dest = $this->modx->getObject($class,(int)$new_row['id'])){
            return $this->error('class not found',array('class'=>$class));
        }
        if(!$childs = $source->getMany($child_alias)){
            return $this->success('child_alias not found',array('child_alias'=>$child_alias));
        }
        if($dest_childs = $dest->getMany($child_alias)){
            foreach($dest_childs as $dch){
                $dch->remove();
            }
        }
        foreach($childs as $ch){
            if($newchild = $this->modx->newObject($child_class,$ch->toArray())){
                $newchild->addOne($dest);
                $newchild->save();
            }
        }
        return $this->success($this->modx->lexicon('gettables_saved_successfully'),$saved);
    }


    public function copy_subtables($subtable_name, $old_row, $new_row)
    {
        
        $saved = [];
        if(!$subtable = $this->getTables->getClassCache('getTable',$subtable_name)){
            return $this->error('subtable not found',array('subtable_name'=>$subtable_name));
        }
        $pdoConfig = $subtable['pdoTools'];
        $pdoConfig['return'] = 'data';
        $where = $pdoConfig['where'] ? $pdoConfig['where'] : [];
        //$this->getTables->addDebug($current_action,'subtable current_action');
        foreach($subtable['sub_where'] as $where_field=>$where_value){
            foreach($old_row as $tr_field =>$tr_value){
                if($tr_field == $where_value)
                    $where[$where_field] = $tr_value;
            }
        }
        
        $pdoConfig['where'] = $where;
        
        if(isset($subtable['sub_default'])){
            $sub_default = [];
            foreach($subtable['sub_default'] as $where_field=>$where_value){
                foreach($old_row as $tr_field =>$tr_value){
                    if($tr_field == $where_value)
                        $sub_default[$where_field] = $tr_value;
                }
            }
            array_walk_recursive($pdoConfig,array(&$this, 'walkFunc'),$sub_default);
            $where = array_merge($where,$sub_default);
        }
        $pdoConfig['limit'] = 0;

        $this->pdoTools->setConfig(array_merge($this->config['pdoClear'],$pdoConfig));
        $rows = $this->pdoTools->run();
        if(!is_array($rows) or count($rows) == 0){
            return $this->success($this->modx->lexicon('gettables_row_not_found'));
        }
        
        $edit_tables = [];
        ////$this->getTables->addDebug($table['edits'],'run $table[edits] ');
        foreach($subtable['edits'] as $edit){
            if($edit['type'] == 'view') continue;
            $edit_tables[$edit['class']][] = $edit;
        }

        foreach($rows as $row){
            unset($row['id']);
            foreach($subtable['sub_where'] as $where_field=>$where_value){
                foreach($new_row as $tr_field =>$tr_value){
                    if($tr_field == $where_value)
                        $row[$where_field] = $tr_value;
                }
            }
            $resp = $this->update($subtable, $edit_tables, $row, true);
            $saved[] = $resp;
        }
        
        $error = '';
        foreach($saved as $s){
            if(!$s['success']) $error = "Object {$s['class']} {$s['field']} не сохранен copy_subtables \r\n";
        }
        if(!$error){
            return $this->success($this->modx->lexicon('gettables_saved_successfully'),$saved);
        }else{
            return $this->error($error,$saved);
        }
    }

    public function gen_pdoConfig($pdoConfig, $tsub_default = [], $tsub_where =[], $data = array(), $add_gen = [])
    {
        //$pdoConfig = $table['pdoTools'];
        if(!empty($tsub_default)){
            $sub_default = $add_gen;
            foreach($data['sub_where_current'] as $where_field=>$where_value){
                if($tsub_default[$where_field]){
                    $sub_default[$where_field] = $where_value;
                }    
            }
            array_walk_recursive($pdoConfig,array(&$this, 'walkFunc'),$sub_default);
        }else if(!empty($tsub_where)){
            $sub_where = [];
            foreach($data['sub_where_current'] as $where_field=>$where_value){
                if($tsub_where[$where_field]){
                    $sub_where[$where_field] = $where_value;
                }    
            }
            $pdoConfig['where'] = $sub_where;
        }
        return $pdoConfig;
    }
    public function check_rows($table, $data = array())
    {
        $trs_data = [];
        if($data['trs_data']){
            $trs_data = $data['trs_data'];
        }elseif($data['tr_data']){
            $trs_data[] = $data['tr_data'];
        }
        if($data['id']){
            $trs_data[] = ['id'=>$data['id']];
        }
        if($this->action != "create"){
            $pdoConfig = $this->gen_pdoConfig($table['pdoTools'],$table['sub_default'],$table['sub_where'], $data);
            ////$this->getTables->addDebug($sub_default,'run $sub_default ');
            
            $ids = [];
            foreach($trs_data as $tr_data){
                $ids[] = $tr_data['id'];
            }
            if(empty($ids)) return $this->error('trs_data empty');
            $pdoConfig['where'][$table['class'].".id:IN"] = $ids;
            $pdoConfig['limit'] = 1;
            $pdoConfig['sortby'] = [$table['class'].'.id'=>'ASC'];

            //$this->getTables->addDebug($pdoConfig,'run $pdoConfig ');
            $this->pdoTools->setConfig(array_merge($this->config['pdoClear'],$pdoConfig));
            $rows = $this->pdoTools->run();
            if(!is_array($rows) or count($rows) == 0){
                return $this->error('gettables_row_not_found');
            }else{
                $this->old_rows = $rows;
            }
        }
        
        return $this->success('');
    }
    public function autosave($table, $edit_tables, $data = array())
    {
        if(empty($data['tr_data'])) return $this->error('tr_data empty');

        if(!(int)$data['tr_data']['id']){
            return $this->error('$tr_data[id] empty');
        }
        $set_data['id'] = (int)$data['tr_data']['id'];
        $set_data[$data['td']['field']] = $data['td']['value'];
        $set_data[$data['td']['field']] = $data['td']['value'];
		if(isset($data['td2'])){
			$set_data[$data['td2']['name']] = $data['td2']['value'];
		}
		//$this->getTables->addTime("getTable autosave set_data ".print_r($data,1));
        $refresh_table = false;
        foreach($edit_tables as &$class_edits){
            foreach($class_edits as $edit){
                if($edit['field'] == $data['td']['field']){
                    if($edit['refresh_table']) $refresh_table = true;
                }
                if(isset($data['td2'])){
                        $edit2 = $edit;
                        $edit2['field'] = $data['td2']['name'];
                        $class_edits[] = $edit2;
                }
                if($edit['force']){
                    switch($edit['type']){
                        case 'date':
                            $edit['force'] = date('Y-m-d',strtotime($edit['force']));
                            break;
                        case 'datetime':
                            $edit['force'] = date('Y-m-d H:i',strtotime($edit['force']));
                            break;
                    }
                    switch($edit['force']){
                        case 'user_id':
                            $edit['force'] = $this->modx->user->id;
                            break;
                    }
                    $set_data[$edit['field']] = $edit['force'];
                }
            }
        }
        $set_data['table_name'] = $data['table_name'];
        $resp = $this->update($table, $edit_tables, $set_data, false, $data['tr_data']);
        if($refresh_table) $resp['data']['refresh_table'] = 1;
        if(is_array($table['autosave'])){
            if(isset($table['autosave']['refresh']['row'])){
                $table['pdoTools']['where'][$table['pdoTools']['class'].'.id'] = (int)$data['tr_data']['id'];
                $getTable = $this->getTables->models['getTable']['service'];
                $table2 = $getTable->generateData($table);
                if(isset($table2['tbody']['trs'][0])) $resp['data']['update_row'] = $table2['tbody']['trs'][0]['html'];
            }
            if(isset($table['autosave']['refresh']['form'])){
                if($table['role']['type'] == 'document' and $table['top']['type'] == 'form'){
                    $this->getTables->REQUEST['id'] = (int)$table['role']['id'];
                    $resp2 = $this->getTables->handleRequestInt('getForm/fetch',$table['top']['form']);
                    if($resp2['success']) $resp['data']['update_form'] = $resp2['data']['html'];
                }
            }
        }
        return $resp;
    }

    public function sets($table, $edit_tables, $data = array())
    {
        
        $saved = [];
        if(empty($data['trs_data'])) return $this->error('trs_data empty');
        foreach($data['trs_data'] as $tr_data){
            if(!(int)$tr_data['id']){
                $saved[] = $this->error('$tr_data[id] empty'); continue;
            }
            $set_data['id'] = (int)$tr_data['id'];
            $value = 0;
            if(isset($data['button_data']['toggle'])){
                if($data['button_data']['toggle'] == 'enable') $value = 1;
            }
            if(isset($this->current_action['value'])){
                $value = $this->current_action['value'];
            }
            $set_data[$this->current_action['field']] = $value;
            $set_data['table_name'] = $data['table_name'];
            // $saved[] = $this->current_action['field'];
            // $saved[] = $this->current_action['value'];
            $saved[] = $this->update($table, $edit_tables, $set_data);
        }
        
        $error = '';
        foreach($saved as $s){
            if(!$s['success']) $error = "Object {$s['class']} {$s['field']} не сохранен sets \r\n";
        }
        if(!$error){
            return $this->success($this->modx->lexicon('gettables_saved_successfully'),$saved);
        }else{
            return $this->error($error,$saved);
        }
    }
    
    public function remove($table, $edit_tables, $data = array())
    {
        $saved = [];
        if(empty($data['trs_data'])) return $this->error('trs_data empty');
        if($table['event']){
            $getTablesBeforeRemove = $this->modx->invokeEvent('getTablesBeforeRemove', array(
                'data'=>$data,
                'getTables'=>$this->getTables,
            ));
            if (is_array($getTablesBeforeRemove)) {
                $canSave = false;
                foreach ($getTablesBeforeRemove as $msg) {
                    if (!empty($msg)) {
                        $canSave .= $msg."\n";
                    }
                }
            } else {
                $canSave = $getTablesBeforeRemove;
            }
            if(!empty($canSave)) return $this->error($canSave);
        }
        foreach($data['trs_data'] as $tr_data){
            if(!(int)$tr_data['id']){
                $saved[] = $this->error('$tr_data[id] empty'); continue;
            }
            if(!$obj = $this->modx->getObject($table['class'],(int)$tr_data['id'])){
                $saved[] = $this->error('Объект не найден');
            }
            
            $object_old = $obj->toArray();
            $resp = $this->run_triggers($table['class'], 'before', 'remove', [], $object_old,[],$obj);
            if(!$resp['success']) return $resp;
            
            $id = $obj->id;
            if($obj->remove()){
                $resp = $this->run_triggers($table['class'], 'after', 'remove', [], $object_old);
                if(!$resp['success']) return $resp;
                
                $saved[] = $this->success($this->modx->lexicon('gettables_removed_successfully'),$saved);
            } 
        }
        
        $error = '';
        foreach($saved as $s){
            if(!$s['success']) $error = $this->modx->lexicon('gettables_removed_error');
        }
        if(!$error){
            if($table['event']){
                $response = $this->modx->invokeEvent('getTablesAfterRemove', array(
                    'data'=>$data,
                    'getTables'=>$this->getTables,
                ));
            }
            return $this->success($this->modx->lexicon('gettables_removed_successfully'),$saved);
        }else{
            return $this->error($error,$saved);
        }
    }

    public function validate($type,$label,$value)
    {
        $type2 = explode(":",$type);
        switch($type[0]){
            case 1: default:
                if(empty($value)) return $this->error($this->modx->lexicon('gettables_validate_field_required',['label'=>$label]));
            break;
        }
        return $this->success();
    }
    public function update($table, $edit_tables, $data = array(), $create = false, $tr_data = [])
    {
        //$this->getTables->addTime("update data".print_r($data,1));
        //$this->getTables->addTime("update edit_tables".print_r($edit_tables,1));
        $saved = [];
        if($table['event']){
            $getTablesBeforeUpdateCreate = $this->modx->invokeEvent('getTablesBeforeUpdateCreate', array(
                'data'=>$data,
                'create'=>$create,
                'getTables'=>$this->getTables,
            ));
            if (is_array($getTablesBeforeUpdateCreate)) {
                $canSave = false;
                foreach ($getTablesBeforeUpdateCreate as $msg) {
                    if (!empty($msg)) {
                        $canSave .= $msg."\n";
                    }
                }
            } else {
                $canSave = $getTablesBeforeUpdateCreate;
            }
            if(!empty($canSave)) return $this->error($canSave);
        }
        ////$this->getTables->addDebug($edit_tables,'update $edit_tables ');
        $validates_messages = [];
        $validates_error_fields = [];
        foreach($table['edits'] as $edit){
            if(isset($data[$edit['field']])){
                if($edit['validate']){
                    $resp = $this->validate($edit['validate'],$edit['label'],$data[$edit['field']]);
                    if(!$resp['success']){
                        $validates_messages[] = $resp['message'];
                        $validates_error_fields[$edit['field']] = $resp['message'];
                    }
                }
                if($edit['check'] and $edit['check'] == 'user_id'){
                    if($data[$edit['field']] != $this->modx->user->id) return $this->error(['lexicon'=>'access_denied']);
                }
                if($edit['type'] == "textarea"){
                    if(!$edit['skip_sanitize']){
                        $temp = json_decode($data[$edit['field']],1);
                        if(json_last_error() == JSON_ERROR_NONE){
                            $temp = $this->getTables->sanitize($temp); //Санация записей в базу
                            $data[$edit['field']] = json_encode($temp, JSON_PRETTY_PRINT);
                        }else{
                            $data[$edit['field']] = $this->getTables->sanitize($data[$edit['field']]); //Санация записей в базу
                        }
                    }
                }else{
                    $data[$edit['field']] = $this->getTables->sanitize($data[$edit['field']]); //Санация записей в базу
                }
            }
        }
        if(!empty($validates_messages)) return $this->error(implode("<br>",$validates_messages),
            ['validates_error_fields'=>$validates_error_fields]);

        foreach($edit_tables as &$class_edits){
            foreach($class_edits as $edit){
                if(isset($edit['content_name'])){
                    $edit2 = $edit;
                    $edit2['field'] = $edit['content_name'];
                    $class_edits[] = $edit2;
                }
            }
        }
        $class = $table['class'];
        if($edit_tables[$class]){
            $set_data = [];
            foreach($edit_tables[$class] as $edit){
                if($edit['force']){
                    switch($edit['type']){
                        case 'date':
                            $edit['force'] = date('Y-m-d',strtotime($edit['force']));
                            break;
                        case 'datetime':
                            $edit['force'] = date('Y-m-d H:i',strtotime($edit['force']));
                            break;
                    }
                    switch($edit['force']){
                        case 'user_id':
                            $edit['force'] = $this->modx->user->id;
                            break;
                    }
                    $data[$edit['field']] = $edit['force'];
                }
                if($data[$edit['field']] !== null)
                    $set_data[$edit['field']] = $data[$edit['field']];

                if($edit['type'] == 'date'){
                    if(isset($data[$edit['field']])){
                        if($data[$edit['field']] == ''){
                            $set_data[$edit['field']] = null;
                        }else{
                            $set_data[$edit['field']] = date('Y-m-d',strtotime($data[$edit['field']]));
                        }
                    }
                }
                
                if($edit['type'] == 'datetime'){
                    if(isset($data[$edit['field']])){
                        if($data[$edit['field']] == ''){
                            $set_data[$edit['field']] = null;
                        }else if(strpos($edit['options'],'onlyTimepicker') !== false){
                            $set_data[$edit['field']] = $data[$edit['field']];
                        }else{
                            $set_data[$edit['field']] = date('Y-m-d H:i',strtotime($data[$edit['field']]));
                        }
                    }
                }
            }
            if(isset($table['defaultFieldSet'])){
                foreach($table['defaultFieldSet'] as $df=>$dfv){
                    if($dfv['class'] == $class)
                        $set_data[$df] = $dfv['value'];
                }
            }
            if(isset($table['role']['where'])){
                foreach($table['role']['where'] as $k=>$v){
                    $k = str_replace("`","",$k);
                    $arr = explode(".",$k);
                    if($arr[0] == $class){
                        if($v == 'id'){
                            $set_data[$arr[1]] = (int)$table['role']['id'];
                        }else{
                            $set_data[$arr[1]] = $v;
                        }
                    }
                    
                }
            }
            $set_data_event = $set_data;
            //$this->getTables->addTime("update set_data".print_r($set_data,1));
            if(isset($this->current_action['processors'][$class])){
                if(empty($set_data['context_key'])) $set_data['context_key'] = 'web';
                //добавить триггер before
                //$saved[] = $this->success('runProcessor ',$set_data);
                $modx_response = $this->modx->runProcessor($this->current_action['processors'][$class], $set_data);
                if ($modx_response->isError()) {
                    $saved[] = $this->error('runProcessor ',$this->modx->error->failure($modx_response->getMessage()));
                    $data['id'] = false;
                }else{
                    $saved[] = $this->success('runProcessor ',$modx_response->response);
                    $data['id'] = $modx_response->response['object']['id'];
                    $object_new = $modx_response->response['object'];
                    $this->modx->cacheManager->refresh();
                    $type = 'update';
                    $resp = $this->run_triggers($class, 'after', $type, $set_data, $object_new,$object_new);
                    if(!$resp['success']) return $resp;
                }
            }else{
                $saveobj = ['success'=>false,'class'=>$class];
                //$saved[] = $data;
                //$this->getTables->addDebug($set_data,'$set_data update');
                if($create){
                    $obj = $this->modx->newObject($class);
                    $data['id'] = false;
                    $type = 'create';
                    //sortable
                    if(isset($table['sortable']) and is_array($table['sortable'])){
                        if($table['sortable']['field']){
                            $field = $table['sortable']['field'];
                            if(is_array($table['sortable']['where'])){
                                $where = $table['sortable']['where'];
                            }else{
                                $where = [];
                            }
                            $c = $this->modx->newQuery($class);
                            $c->select("MAX($field) as max");
                            $c->where($where);
                            $max = 0;
                            if ($c->prepare() && $c->stmt->execute()) {
                                $max = $c->stmt->fetchColumn();
                            }
                            $set_data[$table['sortable']['field']] = $max + 1;
                        }
                    }
                    // $this->getTables->addDebug($table['sortable'],'sortable update');
                    // $this->getTables->addDebug($set_data,'$set_data update');
                }else{
                    $obj = $this->modx->getObject($class,(int)$data['id']);
                    $type = 'update';
                }
                if($obj){
                    //$saved[] = $obj->toArray();
                    $object_old = $obj->toArray();
                    //$this->getTables->addTime("update set_data".print_r($set_data,1));
                    
                    foreach($set_data as $field=>$v){
                        if($v === ''){
                            if (isset ($this->modx->map[$class])) {
                                if($this->modx->map[$class]['fieldMeta'][$field]['phptype'] == 'float'){
                                    unset($set_data[$field]);
                                }
                            }
                        }
                        if(empty($field)){
                            unset($set_data[$field]);
                        }
                    }
                    $obj->fromArray($set_data);
                    $object_new = $obj->toArray();
                    
                    $resp = $this->run_triggers($class, 'before', $type, $set_data, $object_old,$object_new,$obj);
                    if(!$resp['success']) return $resp;
                    
                    //$saved[] = $this->success('Сохранено успешно',$set_data);
                    if($obj->save()){
                        
                        $object_new = $obj->toArray();
                        $resp = $this->run_triggers($class, 'after', $type, $set_data, $object_old,$object_new,$obj);
                        if(!$resp['success']) return $resp;
                        
                        $saveobj['success'] = true;
                        $data['id'] = $obj->id;
                    }
                }
                $saved[] = $saveobj;
            }
            unset($edit_tables[$class]);
        }
        if($create and !$data['id']) return $this->error("Failed to create object $class",$saved);
        ////$this->getTables->addDebug($edit_tables,'update 2 $edit_tables ');
        
        foreach($edit_tables as $class=>$edits){
            foreach($edits as $edit){
                //$this->getTables->addDebug($edit,'$edit update '.$edit['field']);
                
                
                if(!empty($edit['search_fields'])){
                    $saveobj = ['success'=>false,'class'=>$class,'field'=>$edit['field']];
                    //$this->getTables->addDebug($edit,'$edit update search_fields '.$edit['field']);
                    //$this->getTables->addDebug($tr_data,'$tr_data');
                    //$this->getTables->addDebug($edit['search_fields'],'111 update $edit[search_fields]');
                    $search_fields = [];
                    foreach($edit['search_fields'] as $k=>$v){
                        $search_fields[$k] = $v;
                        //$this->getTables->addDebug($search_fields[$k],$v." ".$k.' 1 k update $$search_fields');
                        if(isset($this->getTables->REQUEST['sub_where_current'])){
                            foreach($this->getTables->REQUEST['sub_where_current'] as $f=>$f_v){
                                if($f == mb_strtolower($v)){
                                    $search_fields[$k] = $f_v;
                                    //$this->getTables->addDebug($search_fields[$k],$v." ".$k.' 1 k update $$search_fields');
                                }
                            }
                        }
                        foreach($tr_data as $tr_field=>$tr_value){
                            //$this->getTables->addDebug($search_fields[$k],$v." $tr_field ".$k.' 1 k update $$search_fields');
                            if($tr_field == mb_strtolower($v)){
                                
                                $search_fields[$k] = $tr_value;
                                //$this->getTables->addDebug($search_fields[$k],$v." ".$k.' 1 k update $$search_fields');
                            }
                        }
                        ////$this->getTables->addDebug($search_fields[$k],$v." ".$k.' 2 k update $$search_fields');
                        if($v === 'id'){
                            $search_fields[$k] = (int)$data['id'];
                        }
                        ////$this->getTables->addDebug($search_fields[$k],$v." ".$k.' 3 k update $$search_fields');
                    }
                    //$this->getTables->addDebug($search_fields,'222 update $$search_fields');
                    ////$this->getTables->addDebug($search_fields,'$search_fields');
                    if($edit['multiple']){
                        $cols = $this->modx->getIterator($class,$search_fields);
                        foreach($cols as $obj1){
                            $object_old = $obj1->toArray();
                            $resp = $this->run_triggers($class, 'before', 'remove', [], $object_old);
                            if(!$resp['success']) return $resp;
                            
                            $id = $obj1->id;
                            if($obj1->remove()){
                                $resp = $this->run_triggers($class, 'after', 'remove', [], $object_old);
                                if(!$resp['success']) return $resp;
                                
                            }
                            
                        }
                        if(isset($data[$edit['field']])){
                            foreach($data[$edit['field']] as $v){
                                $search_fields2 = $search_fields;
                                $search_fields2[$edit['field']] = $v;
                                ////$this->getTables->addDebug($search_fields2,'multiple update $search_fields2');
                                if($obj2 = $this->modx->newObject($class,$search_fields2)){
                                    if($obj2->save()){
                                        $object_old = $obj2->toArray();
                                        $resp = $this->run_triggers($class, 'after', 'create', [$edit['field']=>1], $object_old, $object_old);
                                        if(!$resp['success']) return $resp;
                                        
                                        $saveobj['success'] = true;
                                    }
                                }
                            }
                        }else{
                            /*if($obj2->save()){ //кажется не нужно
                                        
                                $saveobj['success'] = true;
                            }*/
                        }
                    }else{
                        //$this->getTables->addDebug($search_fields,"search object $class {$edit['field']} {$edit['value_field']} {$data[$edit['field']]} search_fields");
                        if(!$obj2 = $this->modx->getObject($class,$search_fields)){
                            $obj2 = $this->modx->newObject($class,$search_fields);
                            $type = 'create';
                        }else{
                            $type = 'update';
                        }
                        if($obj2){
                            
                            if($edit['default'] and empty($data[$edit['field']]) and empty($obj2->{$edit['value_field']})){
                                $edit['force'] = $edit['default'];
                            }
                            if($edit['force']){
                                switch($edit['type']){
                                    case 'date':
                                        $edit['force'] = date('Y-m-d',strtotime($edit['force']));
                                        break;
                                    case 'datetime':
                                        $edit['force'] = date('Y-m-d H:i',strtotime($edit['force']));
                                        break;
                                }
                                switch($edit['force']){
                                    case 'user_id':
                                        $edit['force'] = $this->modx->user->id;
                                        break;
                                }
                                $data[$edit['field']] = $edit['force'];
                            }
                            //$this->getTables->addDebug($edit,"$class  edit");
                            //$this->getTables->addDebug($search_fields,"$class {$edit['field']} {$edit['value_field']} {$data[$edit['field']]} search_fields");
                            //продумать для удаления пустых записей в БВ. Наверно тригером.
                            if(!isset($data[$edit['field']])){
                                $saveobj['success'] = true;
                                continue;
                            }
                            if($edit['type'] == 'date'){
                                if(isset($data[$edit['field']])){
                                    if($data[$edit['field']] == ''){
                                        $data[$edit['field']] = null;
                                    }else{
                                        $data[$edit['field']] = date('Y-m-d',strtotime($data[$edit['field']]));
                                    }
                                }
                            }
                            
                            if($edit['type'] == 'datetime'){
                                if(isset($data[$edit['field']])){
                                    if($data[$edit['field']] == ''){
                                        $data[$edit['field']] = null;
                                    }else{
                                        $data[$edit['field']] = date('Y-m-d H:i',strtotime($data[$edit['field']]));
                                    }
                                }
                            }
                            
                            
                            /*if(isset($table['defaultFieldSet'][$edit['field']])){
                                $data[$edit['field']] == $table['defaultFieldSet'][$edit['field']];
                            }*/
                            
                            $object_old = $obj2->toArray();
                            $obj2->{$edit['value_field']} = $data[$edit['field']];
                            $object_new = $obj2->toArray();
                            $resp = $this->run_triggers($class, 'before', $type, [$edit['field']=>1], $object_old,$object_new);
                            if(!$resp['success']) return $resp;
                            
                            if($obj2->save()){
                                $object_new = $obj2->toArray();
                                $resp = $this->run_triggers($class, 'after', $type, [$edit['field']=>1], $object_old,$object_new);
                                if(!$resp['success']) return $resp;
                                
                                $saveobj['success'] = true;
                            }
                        }
                    }
                    $saved[] = $saveobj;
                }
                
            }
        }
        $error = '';
        foreach($saved as $s){
            if(!$s['success']) $error = "Object {$s['class']} {$s['field']} не сохранен update \r\n";
        }
        if(!$error){
            if($table['event']){
                $response = $this->modx->invokeEvent('getTablesAfterUpdateCreate', array(
                    'data'=>$data,
                    'set_data'=>$set_data_event,
                    'create'=>$create,
                    'getTables'=>$this->getTables,
                ));
            }
            return $this->success($this->modx->lexicon('gettables_saved_successfully'),['id'=>$data['id'],'saved'=>$saved]);
        }else{
            return $this->error($error,$saved);
        }
    }
    public function error($message = '', $data = array())
    {
        if(is_array($message)) $message = $this->modx->lexicon($message['lexicon'], $message['data']);
        $response = array(
            'success' => false,
            'message' => $message,
            'data' => $data,
        );

        return $response;
    }
    
    public function success($message = '', $data = array())
    {
        if(is_array($message)) $message = $this->modx->lexicon($message['lexicon'], $message['data']);
        $response = array(
            'success' => true,
            'message' => $message,
            'data' => $data,
        );

        return $response;
    }
}