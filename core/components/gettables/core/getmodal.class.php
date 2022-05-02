<?php

class getModal
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
        if($data['sub_where_current']){
            $table['sub_where_current'] = $data['sub_where_current'];
            $this->getTables->REQUEST['sub_where_current'] = $data['sub_where_current'] = json_decode($data['sub_where_current'],1);
        }else if($data['table_data']['sub_where_current']){
            $table['sub_where_current'] = json_encode($data['table_data']['sub_where_current']);
            $this->getTables->REQUEST['sub_where_current'] = $data['sub_where_current'] = $data['table_data']['sub_where_current'];
        } 
        if($data['parent_current']){
            $data['parent_current'] = json_decode($data['parent_current'],1);
        }else if($data['table_data']['parent_current']){
            $data['parent_current'] = $data['table_data']['parent_current'];
        }
        $this->getTables->REQUEST = $this->getTables->sanitize($this->getTables->REQUEST); //Санация запросов

        switch($action){
            case 'fetchTableModal':
                $data = $this->getTables->sanitize($data); //Санация $data
                return $this->fetchTableModal($data);
                break;
            case 'fetchModalRemove':
                $data = $this->getTables->sanitize($data); //Санация $data
                return $this->fetchModalRemove($data);
                break;
            case 'fetchModalProgress':
                $data = $this->getTables->sanitize($data); //Санация $data
                return $this->fetchModalProgress($data);
                break;
            case 'fetchModal2Step':
                $data = $this->getTables->sanitize($data); //Санация $data
                return $this->fetchModal2Step($data);
                break;
            default:
                return $this->error("Метод $action в классе $class не найден!");
        }
        /*
        if(method_exists($this,$action)){
            return $this->$action($data);
        }else{
            return $this->error("Метод $action в классе $class не найден!");
        }*/
    }
    public function fetchModal2Step($data)
    {
        $table_action = !empty($data['button_data']['action'])
            ? (string)$data['button_data']['action']
            : false;
        $table_name = !empty($data['table_data']['name'])
            ? (string)$data['table_data']['name']
            : false;
        $action_name = !empty($data['button_data']['name'])
            ? (string)$data['button_data']['name']
            : false;
        $tr_data = !empty($data['tr_data']) ? $data['tr_data'] : [];

        if(!$table = $this->getTables->getClassCache('getTable',$table_name)){
            return $this->error("Таблица $table_name не найдено",$this->config);
        }
        $modal = $table['actions'][$action_name]['modal'];
        $modal['hash'] = $this->config['hash'];
        $modal['table_name'] = $table_name;
        $modal['table_action'] = $table_action;
        $modal['tr_data'] = json_encode($tr_data);

        if(isset($modal['tpl'])){
            $tpl = $modal['tpl'];
        }else{
            return $this->error("Шаблон модального окна не найден!",$this->config);
        }
        $html = $this->pdoTools->getChunk($tpl, ['modal'=>$modal]);
        
        return $this->success('',array('html'=>$html));
    }
    public function fetchModalRemove($data)
    {
        $table_action = !empty($data['button_data']['action'])
            ? (string)$data['button_data']['action']
            : false;
        $table_name = !empty($data['table_data']['name'])
            ? (string)$data['table_data']['name']
            : false;
        $action_name = !empty($data['button_data']['name'])
            ? (string)$data['button_data']['name']
            : false;
        if(!$table = $this->getTables->getClassCache('getTable',$table_name)){
            return $this->error("Таблица $table_name не найдено",$this->config);
        }
        $modal = $table['actions'][$action_name]['modal'];
        $html = $this->pdoTools->getChunk($this->config['getTableModalRemoveTpl'],['modal'=>$modal]);
        
        return $this->success('',array('html'=>$html));
    }
    public function fetchModalProgress($data)
    {
        
        $html = $this->pdoTools->getChunk('getTable.Modal.Progress.tpl');
        
        return $this->success('',array('html'=>$html));
    }
    public function fetchTableModal($data)
    {
        //echo json_encode($data).'! '.$data['data']['button_data']['action'].'!';
        //$data = $data['data'];
        //$this->getTables->addDebug($data,'fetchTableModal  $data');
        $table_action = !empty($data['button_data']['action'])
            ? (string)$data['button_data']['action']
            : false;
        $table_name = !empty($data['table_data']['name'])
            ? (string)$data['table_data']['name']
            : false;
        $action_name = !empty($data['button_data']['name'])
            ? (string)$data['button_data']['name']
            : false;
        $tr_data = !empty($data['tr_data']) ? $data['tr_data'] : [];
           
        if(!$table_action) return $this->error("Нет table_action!");
        
        if(!$table_name) return $this->error("Нет table_name!");
        //$this->getTables->addDebug($_SESSION['getTables']);
        //$this->getTables->clearCache();
        //if(empty($this->config['getTable'][$table_name])) return $this->error("Таблица $table_name не найдена! ",$this->config);
        if(!$table = $this->getTables->getClassCache('getTable',$table_name)){
            return $this->error("Таблица $table_name не найдено",$this->config);
        }
        //$table = $this->config['getTable'][$table_name];
        //echo json_encode($table);
        //$this->getTables->addDebug($table,'fetchTableModal $table ');
        $modal = $table['actions'][$action_name]['modal'];
        $edits = $table['edits'];
        $modal['hash'] = $this->config['hash'];
        $modal['table_name'] = $table_name;
        $modal['table_action'] = $table_action;
        
        if($data['sub_where_current']) $modal['sub_where_current'] = json_encode($data['sub_where_current']);
        if($data['parent_current']) $modal['parent_current'] = json_encode($data['parent_current']);
        
        if($tr_data){
            $edits = $this->generateEditsData($edits,$tr_data,$table);
        }
        //$this->getTables->addDebug($data['sub_where_current'],'fetchTableModal $data[sub_where_current] ');
        if($data['sub_where_current']){
            //$table['default'] = array_merge($table['default'],$data['table_data']['sub_where_current']);
            foreach($edits as &$edit){
                if(isset($data['sub_where_current'][$edit['field']])){
                    $edit['force'] = $data['sub_where_current'][$edit['field']];
                }
            }
        }
        $edits = $this->defaultFieldSet($edits);
        $method = 'update';
        foreach($edits as &$edit){
            if($edit['field'] == 'id' and empty($edit['value'])){
                $method = 'create';
            }
        }
        $resp = $this->run_triggers($table, $method, $edits);

        //if(!empty($table['force'])) $edits = $this->defaultFieldSet($edits,$table['force']);
        //return $this->error("getModal fetchTableModal modal! ",$tr_data);
        if(isset($this->config[$modal['EditFormtpl']])){
            $EditFormtpl = $this->config[$modal['EditFormtpl']];
        }else{
            $EditFormtpl = $modal['EditFormtpl'];
        }
        foreach($edits as $k=>&$edit){
            if($edit['skip_modal']){
                unset($edits[$k]);
            }else{
                $edit['modal_content'] = $this->pdoTools->getChunk($EditFormtpl, ['edit'=>$edit]);
            }
            
        }
        $modal['edits'] = $edits;
        //$this->getTables->addDebug($modal,'fetchTableModal $modal ');
        if(isset($this->config[$modal['tpl']])){
            $tpl = $this->config[$modal['tpl']];
        }else{
            $tpl = $modal['tpl'];
        }
        $html = $this->pdoTools->getChunk($tpl, ['modal'=>$modal]);
        
        return $this->success('',array('html'=>$html));
    }
    public function run_triggers($table, $method, &$edits)
    {
        $class = $table['class'];
        $getTablesRunModalTriggers = $this->modx->invokeEvent('getTablesRunModalTriggers', array(
            'class'=>$class,
            'table'=>$table,
            'method'=>$method,
            'edits'=>&$edits,
        ));
        if (is_array($getTablesRunModalTriggers)) {
            $canSave = false;
            foreach ($getTablesRunModalTriggers as $msg) {
                if (!empty($msg)) {
                    $canSave .= $msg."\n";
                }
            }
        } else {
            $canSave = $getTablesRunModalTriggers;
        }
        if(!empty($canSave)) return $this->error($canSave);

        $triggers = $this->config['modaltriggers'];
        if(isset($triggers[$class]['function']) and isset($triggers[$class]['model'])){
            $response = $this->getTables->loadService($triggers[$class]['model']);
            if(is_array($response) and $response['success']){
                $service = $this->getTables->models[$triggers[$class]['model']]['service'];
                if(method_exists($service,$triggers[$class]['function'])){ 
                    return  $service->{$triggers[$class]['function']}($class, $table, $method, $edits);
                }
            }
        }
    }
    public function defaultFieldSet($edits)
    {
        foreach($edits as &$edit){
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
        return $edits;
    }
    public function generateEditsData($edits,$tr_data,$table)
    {
        $pdoConfig = $table['pdoTools'];
        //$this->getTables->addDebug($table['pdoTools'],'$table[pdoTools] ');
        $pdoConfig['limit'] = 1;
        $pdoConfig['return'] = 'data';
        //$pdoConfig['where'] = [];
        foreach($tr_data as $k=>$v){
            foreach($edits as $edit){
                if($edit['field'] == $k and $k == 'id')
                    $pdoConfig['where'][$edit['where_field']] = $v;
            }
        }

        $this->pdoTools->config = array_merge($this->config['pdoClear'],$pdoConfig);

        $rows = $this->pdoTools->run();
        
        
        //$this->getTables->addDebug($rows,'$rows ');
        if(is_array($rows) and count($rows) == 1){
            foreach($rows as $row){
                foreach($edits as &$edit){
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
                    if(isset($edit['field_content'])){
                        $edit['content'] = $row[$edit['field_content']];
                    }
                    if($edit['type'] == "date"){
                        $edit['value'] = date($this->config['date_format'],strtotime($edit['value']));
                    }
                    if($edit['type'] == "datetime"){
                        $edit['value'] = date($this->config['datetime_format'],strtotime($edit['value']));
                    }
                    //$this->getTables->addDebug($edit,'$edit generateEditsData');
                }
            }
        }
        return $edits;
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