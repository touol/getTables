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
    
    public function getCSS_JS()
    {
        
        return [
            'js'=>[
                'frontend_gettabs_js' => '',//$this->modx->getOption('gettables_frontend_gettabs_js',null,'[[+jsUrl]]gettables.gettabs.js'),
            ],
            'css'=>[
                'frontend_gettabs_css' => '',//$this->modx->getOption('gettables_frontend_message_css',null,'[[+cssUrl]]gettables.gettabs.css'),
            ],
            'load'=>[
            ],
        ];
    }

    public function handleRequest($action, $data = array(),$skype_check_ajax = false)
    {
        if( $action == "fetch"){
            if($this->config['isAjax'] and !$skype_check_ajax) $data = [];
            return $this->fetch($data);
        }else{
            return $this->error("Метод $action в классе $class не найден!");
        }
    }

    public function fetch($tabs = [])
    {
        //$this->pdoTools->addTime("getTable fetch table ".print_r($this->config,1));
        if(empty($tabs)){
            if(!empty($this->config['tabs'])){
                if (is_string($this->config['tabs']) and strpos(ltrim($this->config['tabs']), '{') === 0) {
                    $this->config['tabs'] = json_decode($this->config['tabs'], true);
                }
                $tabs = $this->config['tabs'];
            }else{
                return $this->error("Нет конфига tabs!");
            }
        }
        
        $html = $this->pdoTools->getChunk($this->config['getTabsTpl'], $this->generateData($tabs));
        return $this->success('',array('html'=>$html));
        
    }
    
    public function generateData($tabs = [])
    {
        $name = $this->config['name'] ? $this->config['name'] : 'getTablesTabs';
        $cls = $this->config['cls'] ? $this->config['cls'] : '';
        $tabs1 = [];
       
        $idx = 1;
        // $this->pdoTools->addTime("getTabs generateData".print_r($tabs,1));
        foreach($tabs as $n => $tab){
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
        //echo "<pre>generateData ".print_r(['name'=>$name,'class'=>$class,'tabs'=>$tabs],1)."</pre>";
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