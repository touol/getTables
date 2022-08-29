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
            case 'get_form_create':
                $data = $this->getTables->sanitize($data); //Санация $data
                return $this->get_form_create($tree,$data);
                break;
            case 'create':
                $data = $this->getTables->sanitize($data); //Санация $data
                return $this->create($tree,$data);
                break;
            case 'get_modal_remove':
                $data = $this->getTables->sanitize($data); //Санация $data
                return $this->get_modal_remove($tree,$data);
                break;
            case 'remove':
                $data = $this->getTables->sanitize($data); //Санация $data
                return $this->remove($tree,$data);
                break;
            
            case 'expand':
                $data = $this->getTables->sanitize($data); //Санация $data
                return $this->expand($tree,$data);
                break;
            default:
                return $this->error("Метод $action в классе $class не найден!");
        }
    }
    public function expand($tree,$data = []){
        //$id = (int)$data['id'];
        $_SESSION['getTree'][$data['hash']]['expanded_ids'] = $data['expanded_ids'];
        return $this->success('',[]);
    }
    public function remove($tree,$data = []){
        $id = (int)$data['id'];
        $action_key = explode("/",$data['action'])[1];
        
        if(!isset($tree['compile_actions'][$action_key])) return $this->error("Не найдено действие $action_key!");
        
        if($res = $this->modx->getObject($tree['class'],$id)){
            if(!$res->remove()){
                return $this->error("Ошибка удаления!");
            }
        }else{
            return $this->error("Не найден!");
        }
        return $this->success('Удалено!',['reload_without_id'=>1]);
    }
    public function get_modal_remove($tree,$data = []){
        $id = (int)$data['id'];
        $action_key = $data['action_key'];
        
        if(!isset($tree['compile_actions'][$action_key])) return $this->error("Не найдено действие $action_key!");

        $html = $this->pdoTools->getChunk($this->config['getTreeModalRemoveTpl'], $data);
        return $this->success('',['modal'=>$html]);
    }
    public function create($tree,$data = []){
        $id = (int)$data['id'];
        $action_key = $data['action_key'];
        if(!$form = $this->getTables->getClassCache('getForm',$data['form_name'])){
            return $this->error("Форма {$data['form_name']} не найдена!");
        }
        if(!isset($tree['compile_actions'][$action_key])) return $this->error("Не найдено действие $action_key!");
        $action = $tree['compile_actions'][$action_key];
        $data['class_key'] = $action['class'];
        $form['edits']['class_key'] = [
            'field'=>'class_key',
            'type' => 'text',
            'class'=>$form['class'],
        ];
         
        $form['actions'] = ['create'=>$action];
        unset($data['action_key']);
        unset($data['tree_name']);
        //ошибка пропадает edits parent
        if(!isset($form['edits']['parent'])){
            $form['edits']['parent'] = [
                'field' => 'parent',
                'as' => 'parent',
                'type' => 'hidden',
                'class' => $form['class'],
            ];
        }
        require_once('gettableprocessor.class.php');
        $getTableProcessor = new getTableProcessor($this->getTables, $this->config);
        //$this->getTables->addTime("getTree create".print_r($form,1));
        $resp = $getTableProcessor->run('create', $form, $data);
        if($resp['success']){
            //$resp['data']['close_modal'] = 1;
            if($res = $this->modx->getObject('modResource',$resp['data']['id'])){
                if($res->parent == 0){
                    $this->modx->log(1,'getTree create form'.print_r($form['edits'],1));
                    $this->modx->log(1,'getTree create data'.print_r($data,1));
                }
            }
            $resp['data']['reload_with_id'] = 1;
        }
        return $resp;
    }
    public function get_form_create($tree,$data = []){
        $id = (int)$data['id'];
        $action_key = $data['action_key'];
        
        if(!isset($tree['compile_actions'][$action_key])) return $this->error("Не найдено действие $action_key!");
        $action = $tree['compile_actions'][$action_key];
        $action['form'] = str_replace("tree_parent",$id,$action['form']);
        $modal = [
            'title'=>$action['label'],
            'form'=>$action['form'],
        ];
        $html = $this->pdoTools->getChunk($this->config['getTreeModalTpl'], ['modal'=>$modal]);
        return $this->success('',['modal'=>$html]);
    }
    public function tplMenu($category = []){
        if(isset($category['children'])){
            // $category['wraper'] = show($category['children'],$pdo);
            $category['wraper'] = $this->pdoTools->getChunk($this->config['getTreeULTpl'], [
                'wrap' => $this->show($category['children']),
                'expanded'=>$category['expanded'],
            ]);
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
    public function get_panel($tree,$id){
        $this->pdoTools->setConfig([
            'class' => $tree['class'],
            'parents' => $tree['rootIds'],
            'showUnpublished' => $tree['showUnpublished'],
            'where'=>[
                'id'=>$id,
            ],
            'return' => 'data',
            'limit'=> 1,
          ]);
        $rows = $this->pdoTools->run();
        if(!is_array($rows) or count($rows) != 1){
            $this->pdoTools->setConfig([
                'class' => $tree['class'],
                'resources' => $tree['rootIds'],
                'showUnpublished' => $tree['showUnpublished'],
                'where'=>[
                    'id'=>$id,
                ],
                'return' => 'data',
                'limit'=> 1,
              ]);
            $rows = $this->pdoTools->run();
            if(!is_array($rows) or count($rows) != 1) return $this->error("Пункт меню не найден!");
        }
        if(isset($tree['onclick']['switch'])){
            $resp = $this->get_panel_switch($rows[0],$tree,$id);
        }
        if(isset($tree['onclick']['action'])){
            $resp = $this->getTables->handleRequestInt($tree['onclick']['action'],$rows[0]);
            if(isset($resp['data']['table'])) $response = $this->getTables->handleRequestInt('getTable/fetch',$resp['data']['table']);
            if(isset($resp['data']['form'])) $response = $this->getTables->handleRequestInt('getForm/fetch',$resp['data']['form']);
            if(isset($resp['data']['tabs'])) $response = $this->getTables->handleRequestInt('getTabs/fetch',$resp['data']);
            if($response){
                $resp = $response;
            }else{
                return $this->error("Ошибка gts_config!");
            }
            //return $resp;
        }
        if(!empty($resp)){
            if($resp['success']){
                $html = $this->pdoTools->getChunk($this->config['getTreePanelTpl'], [
                    "row" => $rows[0],
                    'html'=>$resp['data']['html'],
                ]);
                return $this->success('',array('html'=>$html));
            }
            return $resp;
        }
        return $this->error("Действие не найдено!");
    }
    public function get_panel_switch($row,$tree,$id){
        $value = $row[$tree['onclick']['field']];
        foreach($tree['onclick']['switch'] as $key=>$case){
            if($value == $key){
                switch($case['type']){
                    case "table":
                        $table = $tree['panels'][$case['name']];
                        $response = $this->getTables->handleRequestInt('getTable/fetch',$table);
                        return $response;
                    break;
                    case "form":
                        $form = $tree['panels'][$case['name']];
                        $response = $this->getTables->handleRequestInt('getForm/fetch',$form);
                        return $response;
                    break;
                    case "tabs":
                        $tabs = $tree['panels'][$case['name']];
                        $response = $this->getTables->handleRequestInt('getTabs/fetch',$tabs);
                        return $response;
                    break;
                }
                break;
            }
        }
        return $this->error("Действие не найдено!");
    }
    public function load_panel($tree,$data = []){
        $id = (int)$data['id'];
        if(!$tree['onclick']) return $this->error("onclick не задан!");
        $this->getTables->REQUEST['pageID'] = $id;
        return $this->get_panel($tree,$id);
    }
    public function fetch($data = []){
        //$this->getTables->addTime("getTree fetch".print_r($this->config['tree'],1));
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
            if(empty($data['showUnpublished'])) $data['showUnpublished'] = 0;
            
            if(empty($data['rootIds'])) $data['rootIds'] = '0';
            if(empty($data['name'])) $data['name'] = $data['class'];
            $data['name'] = $this->getTables->getRegistryAppName('getTree',$data['name']);
            //$this->getTables->addTime("getTree compile_actions ".print_r($data['actions'],1));
            $data['compile_actions'] = $this->compileActions($data['actions'],$data['name']);
            $this->getTables->setClassConfig('getTree',$data['name'], $data);
        }else{
            $data = $tree_cache;
        }
        $id = null;
        if($this->getTables->REQUEST['id']){
            $id = $this->getTables->REQUEST['id'];
            $this->getTables->REQUEST['pageID'] = $id;
            //$this->getTables->addTime("getTree compile_actions pageID={$this->getTables->REQUEST['pageID']}");
            $resp = $this->get_panel($data,$id);
            //$this->getTables->addTime("getTree compile_actions pageID={$this->getTables->REQUEST['pageID']}");
            if($resp['success']) $panel = $resp['data']['html'];
            //$this->getTables->addTime("getTree compile_actions ".print_r($resp,1));
        }
        $menus = $this->generateTree($data,$id);
        $html = $this->pdoTools->getChunk($this->config['getTreeMainTpl'], [
            "menus" => $menus,
            "panel" => $panel,
            'hash'=>$this->config['hash'],
            'name'=>$data['name'],
        ]);

        return $this->success('',array('html'=>$html));
    }

    public function generateTree($tree,$id = null){
        
        $this->pdoTools->setConfig([
            'class' => $tree['class'],
            'parents' => $tree['rootIds'],
            'showUnpublished' => $tree['showUnpublished'],
            'return' => 'data',
            'limit'=> 0,
          ]);
        $rows1 = $this->pdoTools->run();
        $this->getTables->addTime("getTree generateTree time".print_r($this->pdoTools->getTime(),1));
        $this->pdoTools->setConfig([
            'class' => $tree['class'],
            'resources' => $tree['rootIds'],
            'showUnpublished' => $tree['showUnpublished'],
            'return' => 'data',
            'limit'=> 0,
          ]);
        $roots = $this->pdoTools->run();
        
        if(!is_array($rows1)) return $this->error("Нет данных rows!");
        if(!is_array($roots)) return $this->error("Нет данных roots!");
        $rows = array_merge($rows1,$roots);
        //$this->getTables->addTime("getTree generateTree".print_r($rows,1));
        if(!empty($tree['compile_actions'])){
            foreach($rows as &$row){
                $this->generateActions($row,$tree['compile_actions']);
            }
        }
        if(isset($_SESSION['getTree'][$this->config['hash']]['expanded_ids'])){
            $expanded_ids = $_SESSION['getTree'][$this->config['hash']]['expanded_ids'];
        }else{
            $expanded_ids = [];
        }
        
        if($id){
            $parentIds = $this->modx->getParentIds($id);
            $expanded_ids = array_merge($expanded_ids,$parentIds);
        }
        if(!empty($expanded_ids)){
            foreach($rows as &$row){
                if(in_array($row['id'],$expanded_ids)){
                    $row['expanded'] = true;
                }else{
                    $row['expanded'] = false;
                }
                if($id and $row['id'] == $id){
                    $row['classes'][] = 'active';
                }
                if(!$row['published']) $row['classes'][] = 'unpublished'; 
            }
        }
        //$this->getTables->addTime("getTree fetch".print_r($rows,1));
        if(count($rows1)>0){
            $tree = $this->pdoTools->buildTree($rows,$tree['idField'],$tree['parentIdField'],explode(",",$tree['rootIds']));

            $menus = $this->show($tree);
        }else{
            $menus = $this->show($rows);
        }
        return $menus;
    }
    public function compileActions($actions,$tree_name){
        $compile_actions = [];
        foreach($actions as $key=>$action){
            if($key == 'create'){
                if(isset($action['classes'])){
                    foreach($action['classes'] as $class=>$data){
                        if(!empty($data['class'])) $class = $data['class'];
                        $data['class'] = $class;
                        $form = [
                            'class'=>$class,
                            'pdoTools'=>[
                                'class'=>$class,
                            ],
                            'buttons'=>[
                                'create'=>[
                                    'action'=>"getTree/create",
                                    'lexicon'=>'gettables_create'
                                ],
                            ],
                            'row'=>[
                                'id'=>[],
                                'parent'=>[
                                    'edit'=>['type'=>'hidden'],
                                    'default'=>'tree_parent',
                                ],
                                'action_key'=>[
                                    'edit'=>['type'=>'hidden'],
                                    'default'=>$key."_".$class,
                                ],
                                'tree_name'=>[
                                    'edit'=>['type'=>'hidden'],
                                    'default'=>$tree_name,
                                ],
                                'pagetitle'=>[
                                    'label'=>'Наименование',
                                ],
                                'alias'=>[
                                    'label'=>'Алиас',
                                ],
                            ],
                        ];
                        if(isset($this->config['selects']['template'])){
                            $form['row']['template'] = [
                                'label'=>'Шаблон',
                                'edit'=>['type'=>'select','select'=>'template']
                            ];
                            if(isset($data['default_template'])){
                                if($tempate = $this->modx->getObject('modTemplate',['templatename'=>$data['default_template']])){
                                    $form['row']['template']['default'] = $tempate->id; 
                                }
                            }
                        }
                        if(!empty($data['form'])){
                            if(!empty($data['form']['row'])){
                                $data['form']['row'] = array_merge($form['row'],$data['form']['row']); 
                            }
                            $form = array_merge($form,$data['form']);
                        }
                        $form['only_create'] = 1;
                        //$this->getTables->addTime("getTree fetch".print_r($form,1)); 
                        $response = $this->getTables->handleRequestInt('getForm/fetch',$form);
                        $data['form'] = $response['data']['html'];
                        if(!isset($form['row']['parent'])){
                            $this->modx->log(1," compile_actions".print_r($form['row'],1));
                        }
                        $data['action'] = "getTree/get_form_create";
                        $compile_actions[$key."_".$class] = $data;  
                    }
                }
            }else{
                switch($key){
                    case 'remove':
                        $remove = [
                            'label'=>'Удалить',
                            'action'=>"getTree/get_modal_remove"
                        ];
                        $action = array_merge($remove,$action);
                    break;
                }
                $compile_actions[$key] = $action; 
            }
        }
        return $compile_actions;
    }
    public function generateActions(&$row, $actions){
        $row_actions = [];
        foreach($actions as $key=>$action){
            if(isset($action['parent_class'])){
                $parent_class = explode(",",$action['parent_class']);
                if(in_array($row['class_key'],$parent_class))  $row_actions[$key] = $action; 
            }else{
                $row_actions[$key] = $action;
            }
        }

        if(!empty($row_actions)) $row['actions'] = $row_actions;
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