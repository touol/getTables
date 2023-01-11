<?php

class getSelect
{
    public $modx;
    /** @var pdoFetch $pdoTools */
    public $pdoTools;
    
    public $getTables;
    public $debug = [];
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
        if($action != 'expand' and $action != 'autocomplect' and $this->config['isAjax'])
            return $this->error("Доступ запрешен $class $action");
        
        switch($action){
            case 'compile':
                return $this->compile($data);
                break;
            case 'autocomplect':
                $data = $this->getTables->sanitize($data); //Санация $data
                return $this->autocomplect($data);
                break;
            case 'expand':
                $data = $this->getTables->sanitize($data); //Санация $data
                return $this->expand($data);
                break;
            /*case 'fetch':
                return $this->fetch($data);
                break;
            case 'ajax':
                return $this->ajax($data);
                break;*/
            default:
                return $this->error("Метод $action в классе $class не найден!");
        }
    }
    public function expand($data)
    {
        $hash = $data['hash'];
        $select_name = $data['select_name'];
        if(!$select = $this->getTables->getClassCache('getSelect',$select_name)){
            return $this->error("select $select_name не найден!");
        }
        $select['pdoTools']['limit'] = 0;
        // if(empty($select['where'])){
        //     $select['where'] = [];
        // }
        if(empty($select['parentIdField'])) $select['parentIdField'] = 'parent';
        if(empty($select['idField'])) $select['idField'] = 'id';
        if(empty($select['pdoTools']['where'])) $select['pdoTools']['where'] = [];

        $pdoTools = $select['pdoTools'];
        $pdoTools['parents'] = $data['id']; //depth
        $pdoTools['depth'] = 0;
        $this->pdoTools->setConfig(array_merge($this->config['pdoClear'],$pdoTools));
        $rows1 = $this->pdoTools->run();
        
        if(is_array($select['where_active'])){
            $pdoTools1 = $pdoTools;
            $pdoTools1['where'] = array_merge($pdoTools1['where'],$select['where_active']);
            $this->pdoTools->setConfig(array_merge($this->config['pdoClear'],$pdoTools1));
            $rows_active = $this->pdoTools->run();
            if(is_array($rows_active) and count($rows_active)>0){
                foreach($rows_active as $row_active){
                    foreach($rows1 as &$row1){
                        if($row_active['id'] = $row1['id']) $row1['active'] = 1;
                    }
                }
            }
        }
        if(is_array($select['where_parent'])){
            $pdoTools1 = $pdoTools;
            $pdoTools1['where'] = array_merge($pdoTools1['where'],$select['where_parent']);
            $this->pdoTools->setConfig(array_merge($this->config['pdoClear'],$pdoTools1));
            $rows_parent = $this->pdoTools->run();
            if(is_array($rows_parent) and count($rows_parent)>0){
                foreach($rows_parent as $row_parent){
                    foreach($rows1 as &$row1){
                        if($row_parent['id'] = $row1['id']) $row1['tree_parent'] = 1;
                    }
                }
            }
        }

        $pdoTools = $select['pdoTools'];
        $pdoTools['resources'] = $data['id']; //depth
        $pdoTools['depth'] = 0;
        $this->pdoTools->setConfig(array_merge($this->config['pdoClear'],$pdoTools));
        $roots = $this->pdoTools->run();

        foreach($roots as &$root){
            $root['expanded'] = 1;
        }
        $rows = array_merge($rows1,$roots);
        $this->getTables->addTime('autocomplect_tree '.print_r($rows,1));
        if(count($rows)>1){
            $tree = $this->pdoTools->buildTree($rows,$select['idField'],$select['parentIdField'],explode(",",$data['id']));
        }else{
            $tree = $rows;
        }
        $this->getTables->addTime('autocomplect_tree tree '.print_r($tree,1));
        $menus = $this->show($tree);

        return $this->success('',array('html'=>$menus));
    }
    public function autocomplect($data)
    {
        $hash = $data['hash'];
        $select_name = $data['select_name'];
        if(!$select = $this->getTables->getClassCache('getSelect',$select_name)){
            return $this->error("select $select_name не найден!");
        }
        if(empty($select['pdoTools']['limit'])) $select['pdoTools']['limit'] = 0;
        if(empty($select['where'])){
            $select['where'] = [];
        }
        $where = [];
        if((int)$data['id']){
            $where['id'] = (int)$data['id'];
            $select['pdoTools']['limit'] = 1;
            if(empty($select['pdoTools']['where'])) $select['pdoTools']['where'] = [];
            $select['pdoTools']['where'] = array_merge($select['pdoTools']['where'],$where);
            $this->pdoTools->setConfig(array_merge($this->config['pdoClear'],$select['pdoTools']));
            $rows = $this->pdoTools->run();
            if(isset($rows[0])){
                $content = $this->pdoTools->getChunk('@INLINE '.$select['content'],$rows[0]);
                return $this->success('',array('content'=>$content));
            }else{
                return $this->error("Объект {$select['class']} id {$data['id']} не найден!");
            }
                
        }else{
            if($select['treeOn']){
                if($data['treeon']){
                    return $this->autocomplect_tree($data,$select);
                }else{
                    return $this->autocomplect_tree_false($data,$select);
                }
            }
            $query = $data['query'];
            if($query){
                foreach($select['where'] as $field=>$value){
                    $value = str_replace('query',$query,$value);
                    $where[$field] = $value;
                }
            }
            $search = $data['search'];
            if(!empty($search)){
                foreach($select['where'] as $field=>$value){
                    foreach($search as $s){
                        if($s['field'] == $value and $s['type'] == 'parent'){
                            $where[$field] = $s['value'];
                        }
                        if($s['type'] == 'query'){
                            $where[$field] = str_replace('query',$query,$s['value']);
                        }
                    }
                }
            }
            //$this->getTables->addTime('autocomplect  where'.print_r($where,1));
            if(!empty($where)){
                if(empty($select['pdoTools']['where'])) $select['pdoTools']['where'] = [];
                $select['pdoTools']['where'] = array_merge($select['pdoTools']['where'],$where);
            }
        }
        
        $this->pdoTools->setConfig(array_merge($this->config['pdoClear'],$select['pdoTools']));
        $rows = $this->pdoTools->run();
        //$this->getTables->addTime('autocomplect  getTime'.print_r($this->pdoTools->getTime(),1));
        $output = [];
        foreach($rows as $row){
            $content = $this->pdoTools->getChunk('@INLINE '.$select['content'],$row);
            $output[] = '<li><a href="#" data-id="'.$row['id'].'">'.$content.'</a></li>';
        }
        return $this->success('',array('html'=>implode("\r\n",$output)));
    }
    public function autocomplect_tree_false($data,$select)
    {
        //$this->getTables->addTime('autocomplect_tree '.print_r($select,1));
        $query = $data['query']; $where = [];
        if(empty($select['parentIdField'])) $select['parentIdField'] = 'parent';
        if(empty($select['idField'])) $select['idField'] = 'id';
        if(empty($select['pdoTools']['where'])) $select['pdoTools']['where'] = [];

        if(empty($query)){
            $pdoTools = $select['pdoTools'];
            $pdoTools['parents'] = $select['rootIds']; //depth
            $pdoTools['depth'] = 0;
            //$this->pdoTools->setConfig(array_merge($this->config['pdoClear'],$pdoTools));
            //$rows1 = $this->pdoTools->run();
            
            if(is_array($select['where_active'])){
                $pdoTools1 = $pdoTools;
                $pdoTools1['where'] = array_merge($pdoTools1['where'],$select['where_active']);
            }
            $this->pdoTools->setConfig(array_merge($this->config['pdoClear'],$pdoTools1));
            $rows = $this->pdoTools->run();
            $output = [];
            foreach($rows as $row){
                $content = $this->pdoTools->getChunk('@INLINE '.$select['content'],$row);
                $output[] = '<li><a href="#" data-id="'.$row['id'].'">'.$content.'</a></li>';
            }
            return $this->success('',array('html'=>implode("\r\n",$output)));
        }else{
            foreach($select['where'] as $field=>$value){
                $value = str_replace('query',$query,$value);
                $where[$field] = $value;
            }
            $pdoTools = $select['pdoTools'];
            $pdoTools['parents'] = $select['rootIds']; //depth
            $pdoTools['where'] = array_merge($pdoTools['where'],$where);
            //$pdoTools['depth'] = 0;
            if(is_array($select['where_active'])){
                $pdoTools['where'] = array_merge($pdoTools['where'],$select['where_active']);
            }
            $this->pdoTools->setConfig(array_merge($this->config['pdoClear'],$pdoTools));
            $rows = $this->pdoTools->run();
            $output = [];
            foreach($rows as $row){
                $content = $this->pdoTools->getChunk('@INLINE '.$select['content'],$row);
                $output[] = '<li><a href="#" data-id="'.$row['id'].'">'.$content.'</a></li>';
            }
            return $this->success('',array('html'=>implode("\r\n",$output)));
            
        }
    }
    public function autocomplect_tree($data,$select)
    {
        //$this->getTables->addTime('autocomplect_tree '.print_r($select,1));
        $query = $data['query']; $where = [];
        if(empty($select['parentIdField'])) $select['parentIdField'] = 'parent';
        if(empty($select['idField'])) $select['idField'] = 'id';
        if(empty($select['pdoTools']['where'])) $select['pdoTools']['where'] = [];

        if(empty($query)){
            $pdoTools = $select['pdoTools'];
            $pdoTools['parents'] = $select['rootIds']; //depth
            $pdoTools['depth'] = 0;
            $this->pdoTools->setConfig(array_merge($this->config['pdoClear'],$pdoTools));
            $rows1 = $this->pdoTools->run();
            
            if(is_array($select['where_active'])){
                $pdoTools1 = $pdoTools;
                $pdoTools1['where'] = array_merge($pdoTools1['where'],$select['where_active']);
                $this->pdoTools->setConfig(array_merge($this->config['pdoClear'],$pdoTools1));
                $rows_active = $this->pdoTools->run();
                if(is_array($rows_active) and count($rows_active)>0){
                    foreach($rows_active as $row_active){
                        foreach($rows1 as &$row1){
                            if($row_active['id'] = $row1['id']) $row1['active'] = 1;
                        }
                    }
                }
            }
            if(is_array($select['where_parent'])){
                $pdoTools1 = $pdoTools;
                $pdoTools1['where'] = array_merge($pdoTools1['where'],$select['where_parent']);
                $this->pdoTools->setConfig(array_merge($this->config['pdoClear'],$pdoTools1));
                $rows_parent = $this->pdoTools->run();
                if(is_array($rows_parent) and count($rows_parent)>0){
                    foreach($rows_parent as $row_parent){
                        foreach($rows1 as &$row1){
                            if($row_parent['id'] = $row1['id']) $row1['tree_parent'] = 1;
                        }
                    }
                }
            }

            $pdoTools = $select['pdoTools'];
            $pdoTools['resources'] = $select['rootIds']; //depth
            $pdoTools['depth'] = 0;
            $this->pdoTools->setConfig(array_merge($this->config['pdoClear'],$pdoTools));
            $roots = $this->pdoTools->run();

            foreach($roots as &$root){
                $root['expanded'] = 1;
            }
            $rows = array_merge($rows1,$roots);
            //$this->getTables->addTime('autocomplect_tree '.print_r($rows,1));
            $tree = $this->pdoTools->buildTree($rows,$select['idField'],$select['parentIdField'],explode(",",$select['rootIds']));
            $this->getTables->addTime('autocomplect_tree tree'.print_r($tree,1));
            $menus = $this->show($tree);

            return $this->success('',array('html'=>$menus));
        }else{
            foreach($select['where'] as $field=>$value){
                $value = str_replace('query',$query,$value);
                $where[$field] = $value;
            }
            $pdoTools = $select['pdoTools'];
            $pdoTools['parents'] = $select['rootIds']; //depth
            $pdoTools['where'] = array_merge($pdoTools['where'],$where);
            //$pdoTools['depth'] = 0;
            if(is_array($select['where_active'])){
                $pdoTools['where'] = array_merge($pdoTools['where'],$select['where_active']);
            }
            $this->pdoTools->setConfig(array_merge($this->config['pdoClear'],$pdoTools));
            $rows_search = $this->pdoTools->run();
            if(is_array($rows_search) and count($rows_search)>0){
                foreach($rows_search as &$row_active){
                    $row_active['active'] = 1;
                }
            }
            $rows = [];
            //$this->getTables->addTime('autocomplect_tree rows_search'.print_r($rows_search,1));
            if(is_array($select['where_parent'])){
                $pdoTools1 = $select['pdoTools'];
                $pdoTools1['parents'] = $select['rootIds'];
                $pdoTools1['where'] = array_merge($pdoTools1['where'],$select['where_parent']);
                $this->pdoTools->setConfig(array_merge($this->config['pdoClear'],$pdoTools1));
                $rows_parent = $this->pdoTools->run();
                if(is_array($rows_parent) and count($rows_parent)>0){
                    //$this->getTables->addTime('autocomplect_tree rows_parent'.print_r($rows_parent,1));
                    foreach($rows_parent as &$row){
                        $row['expanded'] = 1;
                    }
                    $rows0 = array_merge($rows_parent,$rows_search);
                }
            }
            $pdoTools = $select['pdoTools'];
            $pdoTools['resources'] = $select['rootIds']; //depth
            $pdoTools['depth'] = 0;
            $this->pdoTools->setConfig(array_merge($this->config['pdoClear'],$pdoTools));
            $roots = $this->pdoTools->run();

            foreach($roots as &$root){
                $root['expanded'] = 1;
            }
            $rows0 = array_merge($rows0,$roots);
            //$this->getTables->addTime('autocomplect_tree rows0'.print_r($rows0,1));
            $rows2 = [];
            foreach($rows0 as $row1){
                //$this->getTables->addTime('autocomplect_tree row1 '.print_r($row1,1));
                $rows2[$row1[$select['idField']]] = $row1;
            }
            //$this->getTables->addTime('autocomplect_tree rows2 '.print_r($rows2,1));
            $Map = $this->buildMap($rows2,$select['parentIdField'],$select['idField']);
            $parents = [];
            foreach($rows_search as $row_search){
                $parents = array_merge($this->getParentIds($Map,$row_search[$select['idField']]),$parents);
            }
            //$this->getTables->addTime('autocomplect_tree parents'.print_r($parents,1));
            //$this->getTables->addTime('autocomplect_tree rows2 '.print_r($rows2,1));
            foreach($parents as $p){
                if($p) $rows[] = $rows2[$p];
            }
            //$this->getTables->addTime('autocomplect_tree rows '.print_r($rows,1));
            $rows = array_merge($rows,$rows_search);
            //$this->getTables->addTime('autocomplect_tree rows '.print_r($rows,1));
            if(count($rows)>1){
                $tree = $this->pdoTools->buildTree($rows,$select['idField'],$select['parentIdField'],explode(",",$select['rootIds']));
            }else{
                $tree = $rows;
            }
            //$this->getTables->addTime('autocomplect_tree tree'.print_r($tree,1));
            $menus = $this->show($tree);

            return $this->success('',array('html'=>$menus));
        }
    }
    public function getParentIds($Map,$id= null) {
        $parentId= 0;
        $parents= array ();
        if ($id) {
            foreach ($Map as $parentId => $mapNode) {
                if (array_search($id, $mapNode) !== false) {
                    $parents[]= $parentId;
                    break;
                }
            }
            if ($parentId && !empty($parents)) {
                $parents= array_merge($this->getParentIds($Map,$parentId),$parents);
            }
        }
        return $parents;
    }
    public function buildMap(array $flatList,$parentId,$id)
    {
        $Map = [];
        foreach ($flatList as $node){
            //$grouped[$node['parentID']][] = $node;
            if (!isset($Map[(integer) $node[$parentId]])) {
                $Map[(integer) $node[$parentId]] = array();
            }
            $Map[(integer) $node[$parentId]][] = (integer) $node[$id];
        }
        return $Map;
    }
    
    public function tplMenu($category = []){
        if(isset($category['children'])){
            // $category['wraper'] = show($category['children'],$pdo);
            $category['wraper'] = $this->pdoTools->getChunk($this->config['ACTreeULTpl'], [
                'wrap' => $this->show($category['children']),
                'expanded'=>$category['expanded'],
            ]);
        }
        $menu = $this->pdoTools->getChunk($this->config['ACTreeLITpl'], $category);
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
    public function compile($selects)
    {
        if(empty($selects)) return $this->error("Пустой selects! getSelect compile");
        $compile = [];
        foreach($selects as $name=>&$select){
            if(empty($select['class'])) $select['class'] = $name;
            if(empty($select['name'])) $select['name'] = $name;
            switch($select['class']){
                case 'users':
                    $pdoUser = [];
                    if(!empty($select['groups'])) $pdoUser['groups'] = $select['groups'];
                    if(!empty($select['roles'])) $pdoUser['roles'] = $select['roles'];
                    if(!empty($select['users'])) $pdoUser['users'] = $select['users'];
                    if(!empty($select['showInactive'])) $pdoUser['showInactive'] = $select['showInactive'];
                    if(!empty($select['showBlocked'])) $pdoUser['showBlocked'] = $select['showBlocked'];
                    if(empty($select['fields'])) $pdoUser['select'] = 'id,fullname';
                    $pdoUser = $this->pdoUsersConfig($pdoUser);
                    

                    $select['pdoTools'] = $pdoUser;
                    if(empty($select['type'])) $select['type'] = 'select';
                    if(empty($select['content'])) $select['content'] = '{$fullname}';
                    break;
                case 'template':
                    $pdoTemplate = [
                        'class' => 'modTemplate',
                        'select'=>['modTemplate' => '*',]
                    ];
                    if(empty($select['pdoTools'])) $select['pdoTools'] = [];
                    $select['pdoTools'] = array_merge($pdoTemplate,$select['pdoTools']);
                    if(empty($select['content'])) $select['content'] = '{$templatename}';
                    if(empty($select['type'])) $select['type'] = 'select';
                    break;
                case 'resource':
                    if(empty($select['pdoTools'])) $select['pdoTools'] = [];
                    if(empty($select['content'])) $select['content'] = '{$pagetitle}';
                    if(empty($select['type'])) $select['type'] = 'select';
                    break;
            }
            switch($select['type']){
                case 'select':
                    $select['pdoTools']['limit'] = 0;
                    
                    $this->pdoTools->setConfig(array_merge($this->config['pdoClear'],$select['pdoTools']));
                    $rows = $this->pdoTools->run();
                    $data = [];
                    foreach($rows as $row){
                        $d = [
                            'id' =>$row['id'],
                            'content' =>$this->pdoTools->getChunk('@INLINE '.$select['content'],$row),
                        ];
                        $data[] = $d;
                    }
                    $select['data'] = $data;
                    $compile[$name] = $select;
                    
                    //$this->getTables->addTime('select '.print_r($select,1));
                    break;
                case 'data':
                    $data = [];
                    foreach($select['rows'] as $row){
                        $d = [
                            'id' =>$row[0],
                            'content' =>$row[1],
                        ];
                        $data[] = $d;
                    }
                    $select['data'] = $data;
                    $compile[$name] = $select;
                    
                    //$this->getTables->addTime('select '.print_r($select,1));
                    break;
                case 'autocomplect':
                    /*$select['pdoTools']['limit'] = 0;
                    
                    $this->pdoTools->setConfig(array_merge($this->config['pdoClear'],$select['pdoTools']));
                    $rows = $this->pdoTools->run();
                    $data = [];
                    foreach($rows as $row){
                        $d = [
                            'id' =>$row['id'],
                            'content' =>$this->pdoTools->getChunk('@INLINE '.$select['content'],$row),
                        ];
                        $data[] = $d;
                    }
                    $select['data'] = $data;*/
                    $compile[$name] = $select;
                    
                    break;
            }
            
        }
        return $this->success('',array('selects'=>$compile));
    }
    
    public function pdoUsersConfig($sp = array())
    {
        $class = 'modUser';
        $profile = 'modUserProfile';
        $member = 'modUserGroupMember';
        //$this->getTables->addTime('select sp'.print_r($sp,1));
        // Start building "Where" expression
        $where = array();
        if (empty($showInactive)) {
            $where[$class . '.active'] = 1;
        }
        if (empty($showBlocked)) {
            $where[$profile . '.blocked'] = 0;
        }

        // Add users profiles and groups
        $innerJoin = array(
            $profile => array('alias' => $profile, 'on' => "$class.id = $profile.internalKey"),
        );

        // Filter by users, groups and roles
        $tmp = array(
            'users' => array(
                'class' => $class,
                'name' => 'username',
                'join' => $class . '.id',
            ),
            'groups' => array(
                'class' => 'modUserGroup',
                'name' => 'name',
                'join' => $member . '.user_group',
            ),
            'roles' => array(
                'class' => 'modUserGroupRole',
                'name' => 'name',
                'join' => $member . '.role',
            ),
        );
        foreach ($tmp as $k => $p) {
            if (!empty($sp[$k])) {
                $$k = $sp[$k];
                $$k = array_map('trim', explode(',', $$k));
                ${$k . '_in'} = ${$k . '_out'} = $fetch_in = $fetch_out = array();
                foreach ($$k as $v) {
                    if (is_numeric($v)) {
                        if ($v[0] == '-') {
                            ${$k . '_out'}[] = abs($v);
                        } else {
                            ${$k . '_in'}[] = abs($v);
                        }
                    } else {
                        if ($v[0] == '-') {
                            $fetch_out[] = $v;
                        } else {
                            $fetch_in[] = $v;
                        }
                    }
                }

                if (!empty($fetch_in) || !empty($fetch_out)) {
                    $q = $this->modx->newQuery($p['class'], array($p['name'] . ':IN' => array_merge($fetch_in, $fetch_out)));
                    $q->select('id,' . $p['name']);
                    $tstart = microtime(true);
                    if ($q->prepare() && $q->stmt->execute()) {
                        $this->modx->queryTime += microtime(true) - $tstart;
                        $this->modx->executedQueries++;
                        while ($row = $q->stmt->fetch(PDO::FETCH_ASSOC)) {
                            if (in_array($row[$p['name']], $fetch_in)) {
                                ${$k . '_in'}[] = $row['id'];
                            } else {
                                ${$k . '_out'}[] = $row['id'];
                            }
                        }
                    }
                }

                if (!empty(${$k . '_in'})) {
                    $where[$p['join'] . ':IN'] = ${$k . '_in'};
                }
                if (!empty(${$k . '_out'})) {
                    $where[$p['join'] . ':NOT IN'] = ${$k . '_out'};
                }
            }
        }

        if (!empty($groups_in) || !empty($groups_out) || !empty($roles_in) || !empty($roles_out)) {
            $innerJoin[$member] = array('alias' => $member, 'on' => "$class.id = $member.member");
        }

        // Fields to select
        $select = array(
            $profile => implode(',', array_keys($this->modx->getFieldMeta($profile))),
            $class => implode(',', array_keys($this->modx->getFieldMeta($class))),
        );

        // Add custom parameters
        foreach (array('where', 'innerJoin', 'select') as $v) {
            if (!empty($sp[$v])) {
                $tmp = $sp[$v];
                if (!is_array($tmp)) {
                    $tmp = json_decode($tmp, true);
                }
                if (is_array($tmp)) {
                    $$v = array_merge($$v, $tmp);
                }
            }
            unset($sp[$v]);
        }
        //$this->getTables->addTime('Conditions prepared');

        $default = array(
            'class' => $class,
            'innerJoin' => json_encode($innerJoin),
            'where' => json_encode($where),
            'select' => json_encode($select),
            'groupby' => $class . '.id',
            'sortby' => $class . '.id',
            'sortdir' => 'ASC',
            'fastMode' => false,
            'return' => 'data',
            'disableConditions' => true,
        );

        if (!empty($users_in) && (empty($sp['sortby']) || $sp['sortby'] == $class . '.id')) {
            $sp['sortby'] = "find_in_set(`$class`.`id`,'" . implode(',', $users_in) . "')";
            $sp['sortdir'] = '';
        }

        // Merge all properties and run!
        //$this->getTables->addTime('Query parameters ready');
        return array_merge($default, $sp);
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