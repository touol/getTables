<?php

class getTree
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
    
    public function handleRequest($action, $data = array())
    {
        $class = get_class($this);
        
        $this->getTables->REQUEST = $_REQUEST;
        $this->getTables->REQUEST = $this->getTables->sanitize($this->getTables->REQUEST); //Санация запросов

        switch($action){
            case 'fetch':
                if($this->config['isAjax']) $data = [];
                //$data = $this->getTables->sanitize($data); //Санация $data
                return $this->fetch($data);
                break;
            default:
                return $this->error("Метод $action в классе $class не найден!");
        }
    }
    public function tplMenu($category){
        if(isset($category['children'])){
            // $category['wraper'] = show($category['children'],$pdo);
            $category['wraper'] = $this->pdoTools->getChunk($this->config['getTreeULTpl'], ['wrap' => $this->show($category['children'])]);
        }
        $menu = $this->pdoTools->getChunk($this->config['getTreeLITpl'], $category);
        return $menu;
    }

    public function show($data){
        $string = '';
        foreach($data as $item){
            $string .= $this->tplMenu($item);
            // $string .= $pdo->getChunk('getTreeOuter', ["wrap" => tplMenu($item, $pdo), "isFolder" => $isFolder]);
        }
        return $string;
    }
    public function fetch($data = []){
        
        if(empty($data)){
            if(!$this->config['tree']){
                $data = $this->config['tree'];
            }else{
                return $this->error("Нет конфига tree!");
            }
        }
        if(empty($data['idField'])) $data['idField'] = 'id';
        if(empty($data['parentIdField'])) $data['parentIdField'] = 'parent';
        if(empty($data['treeShowField'])) $data['treeShowField'] = 'pagetitle';
        if(empty($data['class'])) $data['class'] = 'modResource';

        if(empty($data['rootIds'])) $data['rootIds'] = '0';

        $this->pdoTools->setConfig([
            'class' => $data['class'],
            'parents' => $data['rootIds'],
            'return' => 'data',
            'limit'=> 0,
          ]);
        $rows = $this->pdoTools->run();
        
        $tree = $this->pdoTools->buildTree($rows,$data['idField'],$data['parentIdField'],explode(",",$data['rootIds']));

        $menus = $this->show($tree);

        $html = $this->pdoTools->getChunk($this->config['getTreeMainTpl'], ["wrap" => $menus]);

        return $this->success('',array('html'=>$html));
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