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
    
    public function handleRequest($action, $data = array(),$skype_check_ajax = false)
    {
        $class = get_class($this);
        
        $this->getTables->REQUEST = $_REQUEST;
        $this->getTables->REQUEST = $this->getTables->sanitize($this->getTables->REQUEST); //Санация запросов

        if($action == "fetch"){
            if($this->config['isAjax']) $data = [];
            return $this->fetch($data);
        } 
        if(!$tree = $this->getTables->getClassCache('getTree',$data['tree_name'])){
            return $this->error("Дерево {$data['tree_name']} не найдено");
        }
        switch($action){
            case 'load_panel':
                $data = $this->getTables->sanitize($data); //Санация $data
                return $this->load_panel($tree,$data);
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
    public function load_panel($tree,$data = []){
        $id = (int)$data['id'];
        if(!$tree['onclick']) return $this->error("onclick не задан!");
        $this->pdoTools->setConfig([
            'class' => $tree['class'],
            'parents' => $tree['rootIds'],
            'where'=>[
                'id'=>$id,
            ],
            'return' => 'data',
            'limit'=> 0,
          ]);
        $rows = $this->pdoTools->run();
        //$this->pdoTools->addTime("getTree fetch".print_r($rows,1));
        if(!is_array($rows) or count($rows) != 1) return $this->error("пункт меню не найден!");
        $value = $rows[0][$tree['onclick']['field']];
        foreach($tree['onclick']['switch'] as $key=>$case){
            if($value == $key){
                switch($case['type']){
                    case "table":
                        $table = $tree['panels'][$case['name']];
                        //$table['pdoTools']['parents'] = $id;
                        array_walk_recursive($table,array(&$this, 'walkFunc'),$id);
                        //$this->pdoTools->addTime("getTree fetch".print_r($table,1));
                        $response = $this->getTables->handleRequestInt('getTable/fetch',$table);
                        return $response;
                    break;
                    case "form":
                        $form = $tree['panels'][$case['name']];
                        //$table['pdoTools']['parents'] = $id;
                        array_walk_recursive($form,array(&$this, 'walkFunc'),$id);
                        //$this->pdoTools->addTime("getTree fetch".print_r($form,1));
                        $response = $this->getTables->handleRequestInt('getForm/fetch',$form);
                        return $response;
                    break;
                }
                break;
            }
        }
        return $this->error("Действие не найдено!");
    }
    public function walkFunc(&$item, $key, $id){
        $item = str_replace("insert_menu_id",$id,$item);
    }
    public function fetch($data = []){
        //$this->pdoTools->addTime("getTree fetch".print_r($this->config['tree'],1));
        if(empty($data)){
            if(isset($this->config['tree'])){
                $data = $this->config['tree'];
            }else{
                return $this->error("Нет конфига tree!");
            }
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
        if(!$tree_cache = $this->getTables->getClassCache('getTree',$data['name'])){
            if(empty($data['idField'])) $data['idField'] = 'id';
            if(empty($data['parentIdField'])) $data['parentIdField'] = 'parent';
            if(empty($data['treeShowField'])) $data['treeShowField'] = 'pagetitle';
            if(empty($data['class'])) $data['class'] = 'modResource';

            if(empty($data['rootIds'])) $data['rootIds'] = '0';
            if(empty($data['name'])) $data['name'] = $data['class'];
            $data['name'] = $this->getTables->getRegistryAppName('getTree',$data['name']);
            $this->getTables->setClassConfig('getTree',$data['name'], $data);
        }else{
            $data = $tree_cache;
        }
        

        $this->pdoTools->setConfig([
            'class' => $data['class'],
            'parents' => $data['rootIds'],
            'return' => 'data',
            'limit'=> 0,
          ]);
        $rows = $this->pdoTools->run();
        
        $tree = $this->pdoTools->buildTree($rows,$data['idField'],$data['parentIdField'],explode(",",$data['rootIds']));

        $menus = $this->show($tree);

        $html = $this->pdoTools->getChunk($this->config['getTreeMainTpl'], [
            "wrap" => $menus,
            'hash'=>$this->config['hash'],
            'name'=>$data['name'],
        ]);

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