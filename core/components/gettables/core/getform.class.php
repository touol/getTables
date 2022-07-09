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
                //return $this->save($data);
                if(!$form = $this->getTables->getClassCache('getForm',$data['form_name'])){
                    return $this->error("Форма {$data['form_name']} не найдена!");
                }
                if(empty($data['id'])){
                    $action = 'create';
                }else{
                    $action = 'update';
                }
                $form['actions'] = ['create'=>[]];
                require_once('gettableprocessor.class.php');
                $getTableProcessor = new getTableProcessor($this->getTables, $this->config);
                return $getTableProcessor->run($action, $form, $data);
                break;
            case 'test':
                //$data = $this->getTables->sanitize($data); //Санация $data
                return $this->test($data);
                break;
            default:
                return $this->error("Метод $action в классе $class не найден!");
        }
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

        
        if(!$form_compile['only_create']){
            $this->getTables->addTime("getForm generateData start id={$this->getTables->REQUEST['id']}");
            $this->generateData($form_compile);
            $this->getTables->addTime("getForm generateData end");
        }
        $this->defaultFieldSet($form_compile);
        

        $EditFormtpl = $this->config['getTableEditFormTpl'];
        
        $this->getTables->addTime("getChunk edit start");
        foreach($form_compile['edits'] as $k=>&$edit){
            if(!isset($edit['form_content'])) $edit['form_content'] = $this->pdoTools->getChunk($EditFormtpl, ['edit'=>$edit]);            
        }
        $this->getTables->addTime("getChunk edit end");

        $html = $this->pdoTools->getChunk($tpl, ['form'=>$form_compile]);
        $this->getTables->addTime("getChunk outer end");

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
                if($edit['type'] == "select" and isset($edit['force'])){
                    $tmp = [
                        'select_name'=>$edit['select']['name'],
                        'id'=>$edit['force'],
                    ];
                    $resp = $this->getTables->handleRequestInt('getSelect/autocomplect',$tmp);
                    if($resp['success']) $edit['content'] = $resp['data']['content'];
                }
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
            //$this->getTables->addTime("form generateData {ignore}".print_r($this->pdoTools->config,1)."{/ignore}");
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
            //$this->getTables->addTime("form generateData {ignore}".print_r($this->pdoTools->config,1)."{/ignore}");
            $rows = $this->pdoTools->run();
            $this->getTables->addTime("form generateData {ignore}".print_r($this->pdoTools->getTime(),1)."{/ignore}");
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
                            //$this->getTables->addTime("form generateData {ignore}".print_r($row,1)."{$edit['content']}{/ignore}");
                        }
                        if(isset($edit['field_content'])){
                            $edit['content'] = $row[$edit['field_content']];
                        }else if($edit['type'] == "select" and isset($edit['default'])){
                            $tmp = [
                                'select_name'=>$edit['select']['name'],
                                'id'=>$row[$edit['field']],
                            ];
                            $resp = $this->getTables->handleRequestInt('getSelect/autocomplect',$tmp);
                            if($resp['success']) $edit['content'] = $resp['data']['content'];
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