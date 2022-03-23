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

    public function handleRequest($action, $data = array())
    {
        $class = get_class($this);
        if($action != 'autocomplect' and $this->config['isAjax'])
            return $this->error("Доступ запрешен $class $action");
        
        switch($action){
            case 'compile':
                return $this->compile($data);
                break;
            case 'autocomplect':
                $data = $this->getTables->sanitize($data); //Санация $data
                return $this->autocomplect($data);
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
    public function autocomplect($data)
    {
        $hash = $data['hash'];
        $select_name = $data['select_name'];
        if(!$select = $this->getTables->getClassCache('getSelect',$select_name)){
            return $this->error("select $select_name не найден!");
        }
        $select['pdoTools']['limit'] = 0;
        if(empty($select['where'])){
            $select['where'] = [];
        }
        $where = [];
        if((int)$data['id']){
            $where['id'] = (int)$data['id'];
            $select['pdoTools']['limit'] = 1;
            if(empty($select['pdoTools']['where'])) $select['pdoTools']['where'] = [];
            $select['pdoTools']['where'] = array_merge($select['pdoTools']['where'],$where);
            $this->pdoTools->config = array_merge($this->config['pdoClear'],$select['pdoTools']);
            $rows = $this->pdoTools->run();
            if(isset($rows[0])){
                $content = $this->pdoTools->getChunk('@INLINE '.$select['content'],$rows[0]);
                return $this->success('',array('content'=>$content));
            }else{
                return $this->error("Объект {$select['class']} id {$data['id']} не найден!");
            }
                
        }else{
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
                        if($s['field'] == $value){
                            $where[$field] = $s['value'];
                        }
                    }
                }
            }
            //$this->getTables->addDebug($where,'autocomplect  $where');
            if(!empty($where)){
                if(empty($select['pdoTools']['where'])) $select['pdoTools']['where'] = [];
                $select['pdoTools']['where'] = array_merge($select['pdoTools']['where'],$where);
            }
        }
        
        $this->pdoTools->config = array_merge($this->config['pdoClear'],$select['pdoTools']);
        $rows = $this->pdoTools->run();
        $output = [];
        foreach($rows as $row){
            $content = $this->pdoTools->getChunk('@INLINE '.$select['content'],$row);
            $output[] = '<li><a href="#" data-id="'.$row['id'].'">'.$content.'</a></li>';
        }
        return $this->success('',array('html'=>implode("\r\n",$output)));
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
                    
                    $this->pdoTools->config = array_merge($this->config['pdoClear'],$select['pdoTools']);
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
                    
                    //$this->pdoTools->addTime('select '.print_r($select,1));
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
                    
                    //$this->pdoTools->addTime('select '.print_r($select,1));
                    break;
                case 'autocomplect':
                    /*$select['pdoTools']['limit'] = 0;
                    
                    $this->pdoTools->config = array_merge($this->config['pdoClear'],$select['pdoTools']);
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
        //$this->pdoTools->addTime('select sp'.print_r($sp,1));
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
        //$this->pdoTools->addTime('Conditions prepared');

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
        //$this->pdoTools->addTime('Query parameters ready');
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