<?php

class getTabs
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
    
    // public function getCSS_JS()
    // {
        
    //     return [
    //         'js'=>[
    //             'frontend_gettabs_js' => '',//$this->modx->getOption('gettables_frontend_gettabs_js',null,'[[+jsUrl]]gettables.gettabs.js'),
    //         ],
    //         'css'=>[
    //             'frontend_gettabs_css' => '',//$this->modx->getOption('gettables_frontend_message_css',null,'[[+cssUrl]]gettables.gettabs.css'),
    //         ],
    //         'load'=>[
    //         ],
    //     ];
    // }

    public function handleRequest($action, $data = array(),$skype_check_ajax = false)
    {
        $class = get_class($this);
        $this->getTables->REQUEST = $_REQUEST;
        $this->getTables->REQUEST = $this->getTables->sanitize($this->getTables->REQUEST); //Санация запросов
        
        if( $action == "fetch"){
            if($this->config['isAjax'] and !$skype_check_ajax) $data = [];
            return $this->fetch($data);
        }else{
            return $this->error("Метод $action в классе $class не найден!");
        }
    }

    public function fetch($tabs = [])
    {
        //$this->getTables->addTime("getTable fetch table ".print_r($tabs,1));
        if(empty($tabs)){
            if(!empty($this->config['tabs'])){
                $tabs = $this->config;
            }else{
                return $this->error("Нет конфига tabs!");
            }
        }
        
        $html = $this->pdoTools->getChunk($this->config['getTabsTpl'], $this->generateData($tabs));
        return $this->success('',array('html'=>$html));
        
    }
    
    public function generateData($tabs = [])
    {
        $name = $tabs['name'] ? $tabs['name'] : 'getTablesTabs';
        $cls = $tabs['cls'] ? $tabs['cls'] : '';
        $tabs1 = [];
        $tabs2 = $tabs['tabs'];
        $idx = 1;
        //$this->getTables->addTime("getTabs generateData".print_r($tabs,1));
        foreach($tabs2 as $n => $tab){
            if(!empty($tab['permission'])){
                if (!$this->modx->hasPermission($tab['permission'])) continue;
            }
            
            $tab['name'] = $tab['name'] ? $tab['name'] : $n;
            $tab['label'] = $tab['label'] ? $tab['label'] : 'Панель '.$idx;
            $tab['idx'] = $idx;
            if($idx == 1) $tab['active'] = 'active';
            $idx++;
            
            if(isset($tab['table'])){
                $response = $this->getTables->handleRequestInt('getTable/fetch',$tab['table']);

                if(!$response['success']){
                    $tab['content'] = $response['message'];
                }else{
                    $tab['content'] = $response['data']['html'];
                }
            }
            if(isset($tab['tables']) and is_array($tab['tables'])){
                $tab['content'] = '';
                foreach($tab['tables'] as $table){
                    $response = $this->getTables->handleRequestInt('getTable/fetch',$table);
                    if(!$response['success']){
                        $tab['content'] .= $response['message'];
                    }else{
                        $tab['content'] .= $response['data']['html'];
                    }
                }
            }
            if(isset($tab['form'])){
                $response = $this->getTables->handleRequestInt('getForm/fetch',$tab['form']);

                if(!$response['success']){
                    $tab['content'] = $response['message'];
                }else{
                    $tab['content'] = $response['data']['html'];
                }
            }

            if(isset($tab['chunk'])) $tab['content'] = $this->pdoTools->getChunk($tab['chunk']);
            
            $tabs1[$n] = $tab;
        }
        //$this->getTables->addTime("getTabs generateData tabs1".print_r($tabs1,1));
        return ['name'=>$name,'cls'=>$cls,'tabs'=>$tabs1];
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