<?php

class getForm
{
    public $modx;
    /** @var pdoFetch $pdoTools */
    public $pdoTools;
    
    public $getTables;

    /**
     * @param modX $modx
     * @param array $config
     */
    function __construct(getTables & $getTables, array $config = [])
    {
        $this->getTables =& $getTables;
        $this->modx =& $this->getTables->modx;
        $this->pdoTools =& $this->getTables->pdoTools;
        
        //$tab_config => $this->modx->getOption('gettables_default_tab_config',null,'bootstrap_v3'),
        
        $this->config = array_merge([
            
        ], $config);
        
    }
    
    public function handleRequest($action, $data = array(),$skype_check_ajax = false)
    {
        $class = get_class($this);
        
        $this->getTables->REQUEST = $_REQUEST;
        $this->getTables->REQUEST = $this->getTables->sanitize($this->getTables->REQUEST); //Санация запросов

        switch($action){
            case 'fetch':
                if($this->config['isAjax'] and !$skype_check_ajax) $data = [];
                //$data = $this->getTables->sanitize($data); //Санация $data
                return $this->fetch($data);
                break;
            case 'save':
                //$data = $this->getTables->sanitize($data); //Санация $data
                return $this->save($data);
                break;
            case 'test':
                //$data = $this->getTables->sanitize($data); //Санация $data
                return $this->test($data);
                break;
            default:
                return $this->error("Метод $action в классе $class не найден!");
        }
    }
    public function save($data = []){
        $saved = [];
        if(!$form = $this->getTables->getClassCache('getForm',$data['form_name'])){
            return $this->error("Форма {$data['form_name']} не найдена!");
        }
        $create = false;
        if(empty($data['id'])) $create = true;
        if($form['event']){
            $getTablesBeforeUpdateCreate = $this->modx->invokeEvent('getTablesBeforeUpdateCreate', array(
                'data'=>$data,
                'create'=>$create,
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
        foreach($form['edits'] as $edit){
            if(isset($data[$edit['field']])){
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
        $edit_tables = [];
        ////$this->getTables->addDebug($table['edits'],'run $table[edits] ');
        foreach($form['edits'] as $edit){
            if($edit['type'] == 'view') continue;
            $edit_tables[$edit['class']][] = $edit;
        }

        $class = $form['class'];
        if($edit_tables[$class]){
            $set_data = [];
            foreach($edit_tables[$class] as $edit){
                if($data[$edit['field']] !== null)
                    $set_data[$edit['field']] = $data[$edit['field']];

                if($edit['type'] == 'date'){
                    if(isset($data[$edit['field']]) and $data[$edit['field']] === ''){
                        $set_data[$edit['field']] = null;
                    }else{
                        $set_data[$edit['field']] = date('Y-m-d',strtotime($data[$edit['field']]));
                    }
                }
                if($edit['type'] == 'datetime'){
                    if(isset($data[$edit['field']]) and $data[$edit['field']] === ''){
                        $set_data[$edit['field']] = null;
                    }else{
                        $set_data[$edit['field']] = date('Y-m-d H:i',strtotime($data[$edit['field']]));
                    }
                }
            }

            if($create){
                $obj = $this->modx->newObject($class);
                $data['id'] = false;
                $type = 'create';
            }else{
                $obj = $this->modx->getObject($class,(int)$data['id']);
                $type = 'update';
            }
            if($obj){
                //$saved[] = $obj->toArray();
                $object_old = $obj->toArray();
                //$this->getTables->addDebug($set_data,'$set_data update ');
                $obj->fromArray($set_data);
                $object_new = $obj->toArray();
                
                $resp = $this->run_triggers($class, 'before', $type, $set_data, $object_old,$object_new);
                if(!$resp['success']) return $resp;
                
                //$saved[] = $this->success('Сохранено успешно',$set_data);
                if($obj->save()){
                    
                    $object_new = $obj->toArray();
                    $resp = $this->run_triggers($class, 'after', $type, $set_data, $object_old,$object_new);
                    if(!$resp['success']) return $resp;
                    
                    $saveobj['success'] = true;
                    $data['id'] = $obj->id;
                }
            }
            $saved[] = $saveobj;
        }
        unset($edit_tables[$class]);
        if($create and !$data['id']) return $this->error("Failed to create object $class",$saved);
        foreach($edit_tables as $class=>$edits){
            foreach($edits as $edit){
                //$this->getTables->addDebug($edit,'$edit update '.$edit['field']);
                
                
                if(!empty($edit['search_fields'])){
                    $saveobj = ['success'=>false,'class'=>$class,'field'=>$edit['field']];
                    $search_fields = [];
                    foreach($edit['search_fields'] as $k=>$v){
                        $search_fields[$k] = $v;
                        
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
                    'set_data'=>[],
                    'create'=>$create,
                ));
            }
            return $this->success($this->modx->lexicon('gettables_saved_successfully'),['id'=>$data['id'],'saved'=>$saved]);
        }else{
            return $this->error($error,$saved);
        }
    }
    public function run_triggers($class, $type, $method, $fields, $object_old, $object_new =[])
    {
        $getTablesRunTriggers = $this->modx->invokeEvent('getTablesRunTriggers', array(
            'class'=>$class,
            'type'=>$type,
            'method'=>$method,
            'fields'=>$fields,
            'object_old'=>$object_old,
            'object_new'=>$object_new,
        ));
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
        if(isset($triggers[$class]['function']) and isset($triggers[$class]['model'])){
            $response = $this->getTables->loadService($triggers[$class]['model']);
            if(is_array($response) and $response['success']){
                $service = $this->getTables->models[$triggers[$class]['model']]['service'];
                if(method_exists($service,$triggers[$class]['function'])){ 
                    return  $service->{$triggers[$class]['function']}($class, $type, $method, $fields, $object_old, $object_new);
                }
            }
        }
        return $this->success('Выполнено успешно');
    }
    public function fetch($form = []){
        
        if(empty($form)){
            if(!empty($this->config['form'])){
                $form = $this->config['form'];
            }else{
                return $this->error("Нет конфига form!");
            }
        }
        if(empty($form['row'])){
            return $this->error("Нет конфига row!");
        }
        if(isset($this->config['selects'])){
            if(!$selects = $this->getTables->getClassCache('getSelect','all')){
                if(!$this->getTables->selects_compile){
                    $request = $this->getTables->handleRequest('getSelect/compile',$this->config['selects']);
                    $selects = $request['data']['selects'];
                
                    $this->getTables->setClassConfig('getSelect','all', $selects);
                    $this->getTables->selects_compile = true;
                }
            }
            $this->config['selects'] = $selects;
        }
        if(!empty($this->config['compile']) or !$form_compile = $this->getTables->getClassCache('getForm',$form['name'])){
            $form['class'] = $form['class'] ? $form['class'] : 'modResource';
            $name = $form['name'] ? $form['name'] : $form['class'];
            
            $form['name'] = $this->getTables->getRegistryAppName('getForm',$name);
            $form_compile = $this->compile($form);
            
            $this->getTables->setClassConfig('getForm',$form['name'], $form_compile);
        }
        
        $tpl = $form_compile['tpl'] ? $form_compile['tpl'] : $this->config['getTableFormTpl'];

        $this->pdoTools->addTime("generateData start");
        $this->generateData($form_compile);
        $this->defaultFieldSet($form_compile);
        $this->pdoTools->addTime("generateData end");

        $EditFormtpl = $this->config['getTableEditFormTpl'];
        
        $this->pdoTools->addTime("getChunk edit start");
        foreach($form_compile['edits'] as $k=>&$edit){
            if(!isset($edit['form_content'])) $edit['form_content'] = $this->pdoTools->getChunk($EditFormtpl, ['edit'=>$edit]);            
        }
        $this->pdoTools->addTime("getChunk edit end");

        $html = $this->pdoTools->getChunk($tpl, ['form'=>$form_compile]);
        $this->pdoTools->addTime("getChunk outer end");

        return $this->success('',array('html'=>$html));
    }

    public function defaultFieldSet(&$form)
    {
        foreach($form['edits'] as &$edit){
            if($edit['default'] and empty($edit['value'])){
                $edit['force'] = $edit['default'];
            }
            if($edit['force']){
                switch($edit['type']){
                    case 'date':
                        $edit['force'] = date($this->config['date_format'],strtotime($edit['force']));
                        break;
                    case 'datetime':
                        $edit['force'] = date($this->config['datetime_format'],strtotime($edit['force']));
                        break;
                }
                switch($edit['force']){
                    case 'user_id':
                        $edit['force'] = $this->modx->user->id;
                        break;
                }
                $edit['value'] = $edit['force'];
            }
        }
    }

    public function test($form){
        if(!isset($form['pdoTools'])) $form['pdoTools'] = [];
        if(!isset($form['pdoTools']['class'])) $form['pdoTools']['class'] = $form['class'];
        $form['pdoTools']['limit'] = 1;
        if($this->getTables->REQUEST['id']){
            if(!isset($form['pdoTools']['where'])) $form['pdoTools']['where'] = [];
            $form['pdoTools']['where'][$form['pdoTools']['class'].'.id'] = (int)$this->getTables->REQUEST['id'];
            //$form['pdoTools']['setTotal'] = 1;
            $this->pdoTools->config = array_merge($this->config['pdoClear'],$form['pdoTools']);
            //$this->pdoTools->addTime("form generateData {ignore}".print_r($this->pdoTools->config,1)."{/ignore}");
            $rows = $this->pdoTools->run();

            if(is_array($rows) and count($rows) == 1){
                return $this->success('');
            }
        }
        return $this->error("");
    }

    public function generateData(&$form){
        if(!isset($form['pdoTools'])) $form['pdoTools'] = [];
        if(!isset($form['pdoTools']['class'])) $form['pdoTools']['class'] = $form['class'];
        $form['pdoTools']['limit'] = 1;
        
        if($this->getTables->REQUEST['id']){
            if(!isset($form['pdoTools']['where'])) $form['pdoTools']['where'] = [];
            $form['pdoTools']['where'][$form['pdoTools']['class'].'.id'] = (int)$this->getTables->REQUEST['id'];
            //$form['pdoTools']['setTotal'] = 1;
            $this->pdoTools->config = array_merge($this->config['pdoClear'],$form['pdoTools']);
            //$this->pdoTools->addTime("form generateData {ignore}".print_r($this->pdoTools->config,1)."{/ignore}");
            $rows = $this->pdoTools->run();

            if(is_array($rows) and count($rows) == 1){
                foreach($rows as $row){
                    foreach($form['edits'] as &$edit){
                        if(!empty($edit['multiple']) and isset($edit['pdoTools']) and !empty($edit['search_fields'])){
                            if(empty($edit['pdoTools']['class'])) $edit['pdoTools']['class'] = $edit['class'];
                            $where = [];
                            foreach($edit['search_fields'] as $field=>$row_field){
                                $where[$field] = $row[$row_field];
                            }
                            $edit['pdoTools']['where'] = $where;
                            $edit['pdoTools']['limit'] = 0;
                            
                            $this->pdoTools->config = array_merge($this->config['pdoClear'],$edit['pdoTools']);
                            $edit['value'] = $this->pdoTools->run();
                            $value = [];
                            foreach($edit['value'] as $v){
                                $value[$v[$edit['field']]] = $v[$edit['field']];
                            }
                            $edit['value'] = $value;
                            $edit['json'] = json_encode($value);
                            
                        }else{
                            $edit['value'] = $row[$edit['as']];
                        }
                        if($edit['type'] == "date"){
                            $edit['value'] = date($this->config['date_format'],strtotime($edit['value']));
                        }
                        if($edit['type'] == "datetime"){
                            $edit['value'] = date($this->config['datetime_format'],strtotime($edit['value']));
                        }
                        if(isset($edit['content'])){
                            $edit['form_content'] = $this->pdoTools->getChunk('@INLINE '.$edit['content'], $row);
                            //$this->pdoTools->addTime("form generateData {ignore}".print_r($row,1)."{$edit['content']}{/ignore}");
                        }
                        if(isset($edit['field_content'])){
                            $edit['content'] = $row[$edit['field_content']];
                        }
                        
                        //$this->getTables->addDebug($edit,'$edit generateEditsData');
                    }
                }
            }
        }
    }
    
    public function compile($form){
        $class = $form['class'] ? $form['class'] : 'modResource';
        $edits = [];
        //if(empty($form['tag'])) $form['tag'] = 'form';
        $form['hash']= $this->config['hash'];

        foreach($form['row'] as $field => $value){
            if(!empty($value['permission'])){
                if (!$this->modx->hasPermission($value['permission'])) continue;
            }
            $value['field'] = $value['field'] ? $value['field'] : $field;
            //конфигурация редактирования поля
            if(empty($value['label'])) $value['label'] = $field;
            $edit = [
                'field' => $value['field'],
                'type' => 'text',
                'label' => $value['label'],
                'placeholder' => $value['placeholder'] ? $value['placeholder'] : $value['label'],
            ];
            if(!isset($value['class'])) $value['class'] = $class;
            if($value['class'] == 'TV'){
                $edit['where_field'] = '`TV'.$value['field'].'`.`value`';
                $edit['class'] = 'modTemplateVarResource';
                if($tv = $this->modx->getObject('modTemplateVar',array('name'=>$value['field']))){
                    $edit['search_fields'] = ['contentid'=>'id', 'tmplvarid'=>$tv->id];
                }
                $edit['value_field'] = 'value';
            }else{
                $edit['class'] = $value['class'];
                $edit['search_fields'] = [];
                $edit['value_field'] = $value['field'];
            }
            if($value['field'] == 'id') $edit['type'] = 'row_view';
            if(!empty($value['default'])) $edit['default'] = $value['default'];
            if(!empty($value['force'])) $edit['force'] = $value['force'];
            if(!empty($value['edit'])) $edit = array_merge($edit,$value['edit']);
            if(!empty($value['content'])) $edit['content'] = $value['content'];

            if(empty($value['as'])){
                $edit['as'] = $value['field'];
            }else{
                $edit['as'] = $value['as'];
            }

            if(empty($edit['where_field'])){
                if($edit['type'] == "text"){
                    $edit['where_field'] = '`'.$value['class'].'`.`'.$value['field'].'`:LIKE';
                }else{
                    $edit['where_field'] = '`'.$value['class'].'`.`'.$value['field'].'`';
                }
            }
            
            if(isset($edit['select']) and $this->config['selects'][$edit['select']]){
                $edit['type'] = 'select';
                $edit['select'] = $this->config['selects'][$edit['select']];
            }
            $edits[$field] = $edit;
            //$this->modx->log(1,"form {ignore}{$edit['content']}{/ignore}");
        }
        if($form['buttons']){
            foreach($form['buttons'] as $name=>&$button){
                switch($name){
                    case 'save':
                        $button['action'] = 'getForm/save';
                        $button['lexicon'] = 'gettables_save';
                        break;
                }
            }
        }

        $idx = 1;
        if($form['tabs']){
            foreach($form['tabs'] as $n => &$tab){
                if(!empty($tab['permission'])){
                    if (!$this->modx->hasPermission($tab['permission'])){
                        unset($form['tabs'][$n]);
                        continue;
                    } 
                }
                
                $tab['name'] = $tab['name'] ? $tab['name'] : $n;
                $tab['label'] = $tab['label'] ? $tab['label'] : 'Панель '.$idx;
                $tab['idx'] = $idx;
                if($tab['fields']) $tab['fields'] = explode(",",$tab['fields']);
                if($idx == 1) $tab['active'] = 'active';
                $idx++;
            }
        }
        $form['edits'] = $edits;
        return $form;
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