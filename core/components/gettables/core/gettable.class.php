<?php

class getTable
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
        
        $this->config = array_merge([
            
        ], $config);
        
    }
    
    public function setConfig($config)
    {
        $this->config = array_merge($this->config, $config);
    }
    
    public function getCSS_JS()
    {
        return [
            'frontend_gettable_css' => '',//$this->modx->getOption('gettables_frontend_message_css',null,'[[+cssUrl]]gettables.gettabs.css')
            'frontend_gettable_js' => '',//$this->modx->getOption('gettables_frontend_gettabs_js',null,'[[+jsUrl]]gettables.gettabs.js'),
        ];
    }
    public function checkAccsess($action)
    {
        switch($action){
            case 'fetch':
                if($this->config['isAjax']) return false;
                return true;
                break;
            default:
                return true;
        }
    }
    
    public function handleRequest($action, $data = array(),$skype_check_ajax = false)
    {
        $class = get_class($this);
        
        
        
        
        
        $this->getTables->REQUEST = $_REQUEST;
        if($data['sub_where_current'] and is_string($data['sub_where_current'])){
            $table['sub_where_current'] = $data['sub_where_current'];
            $this->getTables->REQUEST['sub_where_current'] = $data['sub_where_current'] = json_decode($data['sub_where_current'],1);
        }else if($data['table_data']['sub_where_current']){
            $table['sub_where_current'] = json_encode($data['table_data']['sub_where_current']);
            $this->getTables->REQUEST['sub_where_current'] = $data['sub_where_current'] = $data['table_data']['sub_where_current'];
        }else if(is_array($data['sub_where_current'])){
            $table['sub_where_current'] = json_encode($data['sub_where_current']);
            $this->getTables->REQUEST['sub_where_current'] = $data['sub_where_current'] = $data['sub_where_current'];
        }  
        if($data['parent_current'] and is_string($data['parent_current'])){
            $data['parent_current'] = json_decode($data['parent_current'],1);
        }else if($data['table_data']['parent_current']){
            $data['parent_current'] = $data['table_data']['parent_current'];
        }else if(is_array($data['parent_current'])){
            $data['parent_current'] = $data['parent_current'];
        }
        //$this->pdoTools->addTime('REQUEST1'.print_r($this->getTables->REQUEST,1));
        $this->getTables->REQUEST = $this->getTables->sanitize($this->getTables->REQUEST); //Санация запросов
        //$this->pdoTools->addTime('REQUEST2'.print_r($this->getTables->REQUEST,1));

        if($action == "fetch"){
            if($this->config['isAjax'] and !$skype_check_ajax) $data = [];
            return $this->fetch($data);
        } // and !$this->config['isAjax'])
            

        //$this->getTables->addDebug($data['table_name'],'handleRequest  $table_name');
        if(!$table = $this->getTables->getClassCache('getTable',$data['table_name'])){
            return $this->error("Таблица {$data['table_name']} не найдено");
        }
        
        if($this->config['isAjax'] and $selects = $this->getTables->getClassCache('getSelect','all')){
            $this->config['selects'] = $selects;
        }  

        switch($action){
            case 'create': case 'update': case 'toggle': case 'remove': case 'set': case 'autosave': case 'copy': case 'sort':
                require_once('gettableprocessor.class.php');
                $getTableProcessor = new getTableProcessor($this, $this->config);
                return $getTableProcessor->run($action, $table, $data);
                break;
                
            case 'refresh':
                $data = $this->getTables->sanitize($data); //Санация $data
                return $this->refresh($action, $table, $data);
                break;
            case 'filter':
                $data = $this->getTables->sanitize($data); //Санация $data
                return $this->refresh($action, $table, $data);
                break;
            case 'subtable':
                $data = $this->getTables->sanitize($data); //Санация $data
                return $this->subtable($action, $table, $data);
                break;
            case 'export_excel':
                $data = $this->getTables->sanitize($data); //Санация $data
                return $this->export_excel($action, $table, $data);
                break;
            case 'filter_checkbox_load':
                $data = $this->getTables->sanitize($data); //Санация $data
                return $this->filter_checkbox_load($action, $table, $data);
                break;
            case 'get_tree_child':
                $data = $this->getTables->sanitize($data); //Санация $data
                return $this->get_tree_child($action, $table, $data);
                break;
        }
        return $this->error("Метод $action в классе $class не найден!");
    }
    public function get_tree_child($action, $table, $data)
    {
        $table2 = $this->generateData($table);
        //$this->getTables->addDebug($table2,'refresh  table 2');
        $html = '';
        foreach($table2['tbody']['trs'] as $tr){
            $html .= $tr['html'];
        }
        
        return $this->success('',array('html'=>$html));
    }
    public function filter_checkbox_load($action, $table, $data)
    {
        
        //$table['pdoTools']['limit'] = 0;
        //$table2 = $this->generateData($table);
        $table['pdoTools2'] = $table['pdoTools'];
        $table = $this->addFilterTable($table);
        
        $table['pdoTools2']['limit'] = 0;
        unset($table['pdoTools2']['offset']);
        $table['pdoTools2']['return'] = 'data';
        
        $table['pdoTools2']['where'] = array_merge($table['pdoTools2']['where'],$table['query']['where']);
        $select = "DISTINCT ";
        $checkbox_edit = [];
        foreach($table['edits'] as $edit){
            if($edit['field'] == $data['field']){
                $select .= $edit['class'].".".$data['field'];
                $checkbox_edit = $edit;
            }
        }
        $table['pdoTools2']['select'] = $select;
        $table['pdoTools2']['sortby'] = [
            $data['field']=>'DESC',
        ];
        $this->pdoTools->config=array_merge($this->config['pdoClear'],$table['pdoTools2']);
        $rows = $this->pdoTools->run();
        $checkboxs = [];
        switch($checkbox_edit['type']){
            case 'date': case 'datetime': case 'text': case 'decimal': case 'row_view': 
                foreach($rows as $row){
                    $checkboxs[] = [
                        'value'=>$row[$data['field']],
                        'content'=>$row[$data['field']],
                    ];
                }
                break;
            case 'checkbox':
                foreach($rows as $row){
                    $checkboxs[] = [
                        'value'=>$row[$data['field']],
                        'content'=>$row[$data['field']] ? $this->modx->lexicon('gettables_yes') : $this->modx->lexicon('gettables_no'),
                    ];
                }
                break;
            case 'textarea':
                foreach($rows as $row){
                    $checkboxs[] = [
                        'value'=>$row[$data['field']],
                        'content'=> substr(strip_tags($row[$data['field']]),0,40),
                    ];
                }
                break;
            case 'select':
                switch($checkbox_edit['select']['type']){
                    case 'select':
                        foreach($rows as $row){
                            foreach($checkbox_edit['select']['data'] as $d){
                                if($d['id']==$row[$data['field']]) $content=$d['content'];
                            }
                            $checkboxs[] = [
                                'value'=>$row[$data['field']],
                                'content'=> $content,
                            ];
                        }
                        break;
                    case 'autocomplect':
                        foreach($rows as $row){
                            $content = "";
                            if($row[$data['field']] != 0){
                                $pdoTools = $checkbox_edit['select']['pdoTools'];
                                $pdoTools['where'] = [
                                    $pdoTools['class'].".id"=>$row[$data['field']],
                                ];
                                $pdoTools['limit'] = 1;
                                $pdoTools['return'] = 'data';
                                $this->pdoTools->setConfig($pdoTools);
                                $select = $this->pdoTools->run();

                                $content=$this->pdoTools->getChunk('@INLINE '.$checkbox_edit['select']['content'],$select[0]);
                            }
                            $checkboxs[] = [
                                'value'=>$row[$data['field']],
                                'content'=> $content,
                            ];
                        }
                        break;
                }
                    
        }
        $html=$this->pdoTools->getChunk($this->config['getTableFilterCheckboxTpl'],['checkboxs' => $checkboxs]);
        return $this->success('',['html'=>$html]);
    }
    public function export_excel($action, $table, $data)
    {
        
        //$table['pdoTools']['limit'] = 0;
        $table2 = $this->generateData($table);
        
        $PHPExcelPath = MODX_CORE_PATH.'components/gettables/vendor/PHPOffice/';
        require_once $PHPExcelPath . 'PHPExcel.php';
        require_once $PHPExcelPath . 'PHPExcel/Writer/Excel2007.php';
        
        $xls = new PHPExcel();
        $xls->setActiveSheetIndex(0);
        $sheet = $xls->getActiveSheet();
        $sheet->setTitle('Лист1');

        $i = 1;$k = 0;
        foreach($table2['edits'] as $edit){
            if(!$edit['modal_only']){
                $sheet->setCellValueByColumnAndRow($k, $i, $edit['label']);
                $k++;
            }
        }
        $i++;
        foreach($table2['tbody']['trs'] as $row){
            $k = 0;
            foreach($row['tr']['tds'] as $v){
                if($v['field']){
                    switch($v['edit']['type']){
                        case 'select':
                            switch($v['edit']['select']['type']){
                                case 'select':
                                    $content = [];
                                    if($v['edit']['multiple']){
                                        foreach($v['edit']['select']['data'] as $d){
                                            if($v['value'][$d['id']]){
                                                $content[] = $d['content'];
                                            }
                                        }
                                    }else{
                                        foreach($v['edit']['select']['data'] as $d){
                                            if($v['value'] == $d['id']){
                                                $content[] = $d['content'];
                                            }
                                        }
                                    }
                                    $sheet->setCellValueByColumnAndRow($k, $i, implode(" ",$content));
                                break;
                                case 'autocomplect':
                                    $sheet->setCellValueByColumnAndRow($k, $i, $v['edit']['content']);
                                break;
                                default:
                                    $sheet->setCellValueByColumnAndRow($k, $i, $v['value']);
                            }
                        break;
                        case 'checkbox':
                            if($v['value']){
                                $sheet->setCellValueByColumnAndRow($k, $i, "Да");
                            }else{
                                $sheet->setCellValueByColumnAndRow($k, $i, "Нет");
                            }
                        break;    
                        default:
                            $sheet->setCellValueByColumnAndRow($k, $i, $v['value']);
                    }
                    
                    $k++;
                }
                
            }
            $i++;
        }

        header("Expires: Mon, 1 Apr 1974 05:00:00 GMT");
        header("Last-Modified: " . gmdate("D,d M YH:i:s") . " GMT");
        header("Cache-Control: no-cache, must-revalidate");
        header("Pragma: no-cache");
        header("Content-type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet");
        header("Content-Disposition: attachment; filename={$table2['name']}.xlsx");
        
        $objWriter = new PHPExcel_Writer_Excel2007($xls);
        $objWriter->save('php://output'); 
        exit();
    }
    public function subtable($action, $table, $data)
    {
        $current_action = $table['actions'][$action];
        //$this->getTables->addDebug($table,'subtable  $table');
        if(empty($data['button_data']['subtable_name'])) return $this->error('subtable_name не найдено',array('button_data'=>$data['button_data']));
        
        if(!$subtable = $this->getTables->getClassCache('getTable',$data['button_data']['subtable_name'])){
            return $this->error('subtable не найдено',array('button_data'=>$data['button_data']));
        }
        //$this->getTables->addDebug($table,'subtable $table');
        //$this->getTables->addDebug($subtable,'subtable subtable');
        //$subtable['parent_table_name'] = $table_name;
        
        $pdoConfig = $subtable['pdoTools'];
        $pdoConfig['return'] = 'data';
        $where = $pdoConfig['where'] ? $pdoConfig['where'] : [];
        //$this->getTables->addDebug($current_action,'subtable current_action');
        foreach($subtable['sub_where'] as $where_field=>$where_value){
            if(strpos($where_field, ':IN') !== false){
                foreach($data['tr_data'] as $tr_field =>$tr_value){
                    if($tr_field == $where_value)
                        $where[$where_field] = explode(",",$tr_value);
                }
            }else{
                if(is_numeric($where_value)) $where[$where_field] = (int)$where_value;
                foreach($data['tr_data'] as $tr_field =>$tr_value){
                    if($tr_field == $where_value)
                        $where[$where_field] = $tr_value;
                }
            }
        }
        
        //$this->getTables->addDebug($subtable['sub_where'],'subtable sub_where');
        //$this->getTables->addDebug($where,'subtable where');
        $pdoConfig['where'] = $where;
        //$this->getTables->addDebug($subtable['sub_default'],'subtable sub_default');
        if(isset($subtable['sub_default'])){
            $sub_default = [];
            foreach($subtable['sub_default'] as $where_field=>$where_value){
                foreach($data['tr_data'] as $tr_field =>$tr_value){
                    if($tr_field == $where_value)
                        $sub_default[$where_field] = $tr_value;
                }
            }
            //$this->getTables->addDebug($sub_default,'subtable sub_default2');
            //$this->getTables->addDebug($pdoConfig,'subtable pdoConfig1');
            array_walk_recursive($pdoConfig,array(&$this, 'walkFunc'),$sub_default);
            $where = array_merge($where,$sub_default);
            //$this->getTables->addDebug($pdoConfig,'subtable pdoConfig');
        }
        //$subtable['pdoTools'] = $pdoConfig;
        $subtable['sub_where_current'] = json_encode($where);
        $subtable['parent_current'] = json_encode(['name'=>$data['table_name'],'tr_data'=>$data['tr_data']]);
        $this->getTables->setClassConfig('getTable',$subtable['name'], $subtable);
        //получаем таблицу дочернию
        $subtable = $this->generateData($subtable,$pdoConfig);
        $sub_content = $this->pdoTools->getChunk($this->config['getTableOuterTpl'], $subtable);
        
        return $this->success('',array('sub_content'=>$sub_content));
    }
    public function walkFunc(&$item, $key, $sub_default){
        //$item = $this->pdoTools->getChunk("@INLINE ".$item, ['sub_default'=>$sub_default]);
        //$this->getTables->addDebug($sub_default,'subtable sub_default2');
        if(strpos($item, '{$sub_default') !== false){
            
            foreach($sub_default as $k=>$v){
                $item = str_replace('{$sub_default.'.$k.'}',$v,$item);
            }
        }
    }
    public function varexport($expression, $return=FALSE) {
        $export = var_export($expression, TRUE);
        $export = preg_replace("/^([ ]*)(.*)/m", '$1$1$2', $export);
        $array = preg_split("/\r\n|\n|\r/", $export);
        $array = preg_replace(["/\s*array\s\($/", "/\)(,)?$/", "/\s=>\s$/"], [NULL, ']$1', ' => ['], $array);
        $export = join(PHP_EOL, array_filter(["["] + $array));
        if ((bool)$return) return $export; else echo $export;
    }
    public function addFilterTable($table)
    {
        $query = [];
        
        if($table['sub_where'] and $this->getTables->REQUEST['sub_where_current']){
            $sub_where_current = $this->getTables->REQUEST['sub_where_current']; 
            foreach($sub_where_current as $field =>$v){
                if($table['sub_where'][$field]){
                    $query[$field] = (int)$v;
                }
            }
            if(isset($table['sub_default'])){
                $sub_default = [];
                $pdoConfig = $table['pdoTools2'];
                foreach($sub_where_current as $where_field=>$where_value){
                    if($table['sub_default'][$where_field]){
                        $sub_default[$where_field] = $where_value;
                    }    
                }
                array_walk_recursive($pdoConfig,array(&$this, 'walkFunc'),$sub_default);
                $table['pdoTools2'] = $pdoConfig;
            }
        }
        
        
        foreach($table['filters'] as $k=>&$filter){
            $date=[];
            $datetime=[];

            if(!empty($filter['where'])) $filter['edit']['where_field'] = $filter['where'];
            
            if($filter['default'] and empty($this->getTables->REQUEST[$filter['edit']['field']]) and $this->getTables->REQUEST[$filter['edit']['field']] !== "0"){
                if($filter['default']){
                    if(!is_array($filter['default'])){
                        switch($filter['edit']['type']){
                            case 'date':
                                if($filter['default']) $filter['default'] = ['from'=>date('Y-m-d',strtotime($filter['default']))];
                                break;
                            case 'datetime':
                                if($filter['default']) $filter['default'] = ['from'=>date('Y-m-d H:i',strtotime($filter['default']))];
                                break;
                        }
                        switch($filter['default']){
                            case 'user_id':
                                $filter['default'] = ['user_id'=>[$this->modx->user->get('id')]];
                                break;
                            default:
                                $filter['default'] = ['default'=>$filter['default']];
                                break;
                        }
                    }
                }
                
                if(empty($filter['force'])){
                    $filter['force'] = $filter['default'];
                }else if(is_array($filter['default'])){
                    $filter['force'] = array_merge($filter['default'],$filter['force']);
                }
                
            }
            if($filter['force']){
                if(!is_array($filter['force'])){
                    switch($filter['edit']['type']){
                        case 'date':
                            if($filter['force']) $filter['force'] = ['from'=>date('Y-m-d',strtotime($filter['force']))];
                            break;
                        case 'datetime':
                            if($filter['force']) $filter['force'] = ['from'=>date('Y-m-d H:i',strtotime($filter['force']))];
                            break;
                    }
                    switch($filter['force']){
                        case 'user_id':
                            $filter['force'] = ['user_id'=>[$this->modx->user->get('id')]];
                            break;
                        default:
                            $filter['force'] = ['default'=>$filter['force']];
                            break;
                    }
                }
                if(!empty($filter['force']['from'])){
                    switch($filter['edit']['type']){
                        case 'date':
                            $date['from'] = date('Y-m-d',strtotime($filter['force']['from']));
                            break;
                        case 'datetime':
                            $datetime['from'] = date('Y-m-d H:i',strtotime($filter['force']['from']));
                            break;
                    }
                }
                if(!empty($filter['force']['default'])){
                    $query[$filter['edit']['where_field']] = $filter['force']['default'];
                    $filter['value'] = $filter['force']['default'];
                }
                if(is_array($filter['force']['in'])){
                    $query[$filter['edit']['where_field']] = $filter['force']['in'];
                    $filter['value'] = $filter['force']['in'];
                }
            
                if($filter['force']['user_id'] ){
                    if(!$this->modx->user->isMember('Administrator')){
                        //$this->pdoTools->addTime("getTable filter default ".print_r($filter['default'],1));
                        if(is_array($filter['force']['modx_user_id'])) $filter['force']['user_id'] = array_merge($filter['force']['user_id'],$filter['force']['modx_user_id']);
                        if(is_array($filter['force']['user_id']) and in_array($this->modx->user->id,$filter['force']['user_id'])){
                            $query[$filter['edit']['where_field'].':IN'] = $filter['force']['user_id'];
                            //$filter['value'] = $filter['edit']['force']['user_id'];
                        }
                        $filter['section'] = ""; continue;
                    }else if(!empty($this->getTables->REQUEST[$filter['edit']['field']]) or $this->getTables->REQUEST[$filter['edit']['field']]==='0'){
                        $filter['value'] = $this->getTables->REQUEST[$filter['edit']['field']];
                        if(!empty($filter['edit']['multiple']) and strpos($filter['edit']['where_field'], ':IN') === false){
                            $filter['edit']['where_field'] = $filter['edit']['where_field'].':IN';
                        }
                        $query[$filter['edit']['where_field']] = $filter['value'];
                    }
                }
                
            }else if(!empty($this->getTables->REQUEST[$filter['edit']['field']]) or $this->getTables->REQUEST[$filter['edit']['field']] ==='0'){
                
                switch($filter['edit']['type']){
                    case 'date':
                        if($this->getTables->REQUEST[$filter['edit']['field']]['from'])
                            $date['from'] = date('Y-m-d',strtotime($this->getTables->REQUEST[$filter['edit']['field']]['from']));
                        if($this->getTables->REQUEST[$filter['edit']['field']]['to'])
                            $date['to'] = date('Y-m-d',strtotime($this->getTables->REQUEST[$filter['edit']['field']]['to']));
                        break;
                    case 'datetime':
                        if($this->getTables->REQUEST[$filter['edit']['field']]['from'])
                            $datetime['from'] = date('Y-m-d H:i',strtotime($this->getTables->REQUEST[$filter['edit']['field']]['from']));
                        if($this->getTables->REQUEST[$filter['edit']['field']]['to'])
                            $datetime['to'] = date('Y-m-d H:i',strtotime($this->getTables->REQUEST[$filter['edit']['field']]['to']));
                        break;
                    default:
                        $filter['value'] = $this->getTables->REQUEST[$filter['edit']['field']];
                        $pattern = '/^\<\=|\>\=|\=\>|\=\<|\!\=|\=|\<|\>/';
                        
                        if(preg_match($pattern,$filter['value'],$matches)){
                            $filter['value'] = (int)str_replace($matches[0],"",$filter['value']);
                            //$this->pdoTools->addTime("getTable filter  {$filter['edit']['where_field']}");
                            $where_field = explode(":",$filter['edit']['where_field'])[0];
                            switch($matches[0]){
                                case '<=': case '=<': 
                                    if($filter['value'] >=0){
                                        $query[] = "($where_field <= {$filter['value']} OR $where_field IS NULL)";
                                    }else{
                                        $query[$where_field.":<="] = $filter['value'];
                                    }
                                    break;
                                case '=':
                                    if($filter['value']==0){
                                        $query[] = "($where_field = {$filter['value']} OR $where_field IS NULL)";
                                    }else{
                                        $query[$where_field.":".$matches[0]] = $filter['value'];
                                    }
                                    break;
                                case '>=': case '=>':
                                    if($filter['value'] <=0){
                                        $query[] = "($where_field >= {$filter['value']} OR $where_field IS NULL)";
                                    }else{
                                        $query[$where_field.":>="] = $filter['value'];
                                    }
                                    break;
                                default:
                                    $query[$where_field.":".$matches[0]] = $filter['value'];
                            }
                            
                            // <!-- $query->where(array('width:IS' => null, 'width:<='=> 0,)); -->
                        }else{
                            if(strpos($filter['edit']['where_field'], ':LIKE') === false) {
                                if(!empty($filter['edit']['multiple']) and strpos($filter['edit']['where_field'], ':IN') === false){
                                    $filter['edit']['where_field'] = $filter['edit']['where_field'].':IN';
                                }
                                if(strpos($filter['edit']['where_field'], ':IN') !== false){
                                    if(!is_array($filter['value'])){
                                        $query[$filter['edit']['where_field']] = explode(',',$filter['value']);
                                    }else{
                                        $query[$filter['edit']['where_field']] = $filter['value'];
                                    }
                                }else{
                                    $query[$filter['edit']['where_field']] = $filter['value'];
                                }
                                
                            }else{
                                $query[$filter['edit']['where_field']] = '%'.$filter['value'].'%';
                            }
                        }
                        //if(isset($filter['edit']['where_field'])) $query[$filter['edit']['where_field']] = $filter['value'];
                }
            }else{
                $filter['value'] = '';
            }
            
            if(!empty($date)){
                if(!empty($date['from'])){
                    $query[$filter['edit']['where_field'].':>='] = $date['from'];
                    $filter['value']['from'] = date($this->config['date_format'],strtotime($date['from']));
                }
                if(!empty($date['to'])){
                    $query[$filter['edit']['where_field'].':<='] = $date['to'];
                    $filter['value']['to'] = date($this->config['date_format'],strtotime($date['to']));
                }
            }
            if(!empty($datetime)){
                if(!empty($datetime['from'])){
                    $query[$filter['edit']['where_field'].':>='] = $datetime['from'];
                    $filter['value']['from'] = date($this->config['datetime_format'],strtotime($datetime['from']));
                }
                if(!empty($datetime['to'])){
                    $query[$filter['edit']['where_field'].':<='] = $datetime['to'];
                    $filter['value']['to'] = date($this->config['datetime_format'],strtotime($datetime['to']));
                }
            }
            if(!empty($filter['edit']['multiple'])){
                $value = [];
                foreach($filter['value'] as $v){
                    $value[$v] = $v;
                }
                $filter['value'] = $value;
            }
              
            //checkbox filter
            if(isset($this->getTables->REQUEST['filter_checkboxs'][$filter['edit']['field']])){
                $query[$filter['edit']['class'].".".$filter['edit']['field'].':IN'] = 
                $this->getTables->REQUEST['filter_checkboxs'][$filter['edit']['field']];
            }
            $filter['content'] = $this->pdoTools->getChunk($this->config['getTableFilterTpl'],['filter'=>$filter]);
        }
        
        if(!isset($table['pdoTools2']['where'])) $table['pdoTools2']['where'] = [];
        //$query = array_merge($table['pdoTools2']['where'],$query);

        $table['query'] = ['where'=>$query];

        foreach($table['filters'] as $f){
            if($f['section'] == 'topBar/topline/filters') $table['topBar'][$f['section']]['filters'][] = $f;
            if($f['section'] == 'th'){
                foreach($table['thead']['tr']['ths'] as &$th){
                    if($th['field']==$f['edit']['field']){
                        $th['filter'] = 1;
                        $th['filters'][] = $f;
                        if(!empty($f['value']) or $f['value'] === '0' or $f['value'] === 0){
                            $th['filter_class'] = 'filter-active';
                        }else{
                            $th['filter_class'] = 'filter';
                        }
                    }
                }
            }
        }
        
        if(isset($table['topBar']['topBar/topline/filters'])){
            $offset = 0;
            foreach($table['topBar']['topBar/topline/filters']['filters'] as $f){
                $offset += $f['cols'];
            }
            $table['topBar']['topBar/topline/filters']['offset'] = 10-$offset;
            $table['topBar']['topBar/topline/filters/search'] = array_pop($table['topBar']['topBar/topline/filters']['filters']);
        }
        
        return $table;
    }
    public function prepareRow($action,&$rows,$table){
        $action = explode("/",$action);
        if($action[0] == "snippet"){
            if(!empty($action[1]) and $element = $this->modx->getObject('modSnippet', array('name' => $action[1]))){
                $params = [
                    'rows'=>$rows,
                    'getTables'=>$this->getTables,
                    'table'=>$table,
                    'getTable'=>$this,
                ];
                if ($tmp = $element->process($params)) {
                    $rows = $tmp;
                }
            }
        }
    }
    public function generateData($table,$pdoConfig =[])
    {
        $table['pdoTools2'] = array_merge($table['pdoTools'],$pdoConfig);
        $table = $this->addFilterTable($table);
        if(empty($table['paginator']) or ($table['paginator'] !== false and $pdoConfig['limit'] != 1)){
            $paginator = true;
            $table['pdoTools2']['setTotal'] = true;//offset
            if(!empty($this->getTables->REQUEST['limit'])) $table['pdoTools2']['limit'] = (int)$this->getTables->REQUEST['limit'];
            if(!empty($this->getTables->REQUEST['page'])) $table['pdoTools2']['offset'] = ((int)$this->getTables->REQUEST['page'] - 1)*$table['pdoTools2']['limit'];
            
        }
        if($this->getTables->REQUEST['gts_action'] == 'getTable/export_excel'){
            $table['pdoTools2']['limit'] = 0;
            unset($table['pdoTools2']['offset']);
        }
        //echo "getTable generateData table ".print_r($table,1);
        //$this->pdoTools->addTime("getTable generateData table ".print_r($table,1));
        //$this->getTables->addDebug($table['pdoTools2'],'generateData $table[pdoTools]');
        //$this->getTables->addDebug($table['query'],'generateData $table[query]');
        $table['pdoTools2']['return'] = 'data';
        
        $table['pdoTools2']['where'] = array_merge($table['pdoTools2']['where'],$table['query']['where']);
        //сортировка
        if(isset($this->getTables->REQUEST['filter_sort'])){
            $filter_sort = $this->getTables->REQUEST['filter_sort'];
            uasort($filter_sort, function ($a, $b) {
                if($a['rank'] == $b['rank'])
                    return 0;
                return $a['rank'] < $b['rank'] ? -1 : 1;
            });
            $sort = [];
            foreach($filter_sort as $field=>$value){
                if($value['sortdir'] == "ASC"){
                    $sortdir = "ASC";
                }else{
                    $sortdir = "DESC";
                }
                foreach($table['filters'] as $k=>$filter){
                    if($filter['edit']['field'] == $field) $sort[$field] = $sortdir;
                }
            }
            $table['pdoTools2']['sortby'] = $sort;
            //$this->pdoTools->addTime("getTable generateData filter_sort ".print_r($filter_sort,1));
        }
        //tree
        if($table['tree']){
            $tree_where = $table['pdoTools2']['where'];
            if(isset($this->getTables->REQUEST['gts_tree']['parent'])){
                $table['pdoTools2']['where'][$table['class'].".".$table['tree']['parentIdField']] = (int)$this->getTables->REQUEST['gts_tree']['parent'];
            }else{
                $table['pdoTools2']['where'][$table['class'].".".$table['tree']['parentIdField']] = (int)$table['tree']['rootParentId'];
            }
            
        }

        $this->pdoTools->config=array_merge($this->config['pdoClear'],$table['pdoTools2']);
        //file_put_contents(__DIR__ ."/". "222_initialize.txt",json_encode($this->pdoTools->config,JSON_PRETTY_PRINT));
        //$this->pdoTools->addTime("getTable generateData this->pdoTools->config ".print_r($this->config['pdoTools'],1));
        //$this->getTables->addDebug($this->pdoTools->config,'generateData this->pdoTools->config');
        $rows = $this->pdoTools->run();
        
        if($paginator){
            $limit = $this->pdoTools->config['limit'];
            $total = $this->modx->getPlaceholder($this->pdoTools->config['totalVar']);
            if($limit){
                $table['page']['max'] = ceil($total/$limit);
                $table['page']['limit'] = $limit;
            }else{
                $table['page']['max'] = 1;
            }
            if(!empty($this->getTables->REQUEST['page'])){
                $table['page']['current'] = (int)$this->getTables->REQUEST['page'];
            }else{
                $table['page']['current'] = 1;
            }
            $table['page']['total'] = $total;
            $table['page']['content'] = $this->pdoTools->getChunk($this->config['getTableNavTpl'],['page' => $table['page']]);
        }
         //$this->pdoTools->addTime("getTable generateData table['page'] ".print_r($table['page'],1));
        //echo "getTable generateData rows <pre>".print_r($rows,1)."</pre>";
        //$output = [];
        $trs = [];
        $tr = $table['tbody']['tr'];
        //$this->getTables->addDebug($rows,'gen1  rows');
        if($table['export'] == 1){
            $this->pdoTools->addTime("getTable export ".$this->varexport($rows,1));   
        }
        if(!empty($table['prepareRow'])){
            $this->prepareRow($table['prepareRow'],$rows,$table);
        }
        foreach($rows as $k => $row){
            //echo "getTable generateData row <pre>".print_r($row,1)."</pre>";
            
            $r = $tr;
            $data = [];
            //echo "getTable generateData r <pre>".print_r($r,1)."</pre>";
            foreach($r['tds'] as $ktd=>&$td){
                
                //if(!empty($td['edit']['multiple'])) $this->pdoTools->addTime("getTable generateData td ".print_r($td,1));
                if(!empty($td['edit']['multiple']) and isset($td['edit']['pdoTools']) and !empty($td['edit']['search_fields'])){
                    if(empty($td['edit']['pdoTools']['class'])) $td['edit']['pdoTools']['class'] = $td['edit']['class'];
                    $where = [];
                    foreach($td['edit']['search_fields'] as $field=>$row_field){
                        $where[$field] = $row[$row_field];
                    }
                    $td['edit']['pdoTools']['where'] = $where;
                    $td['edit']['pdoTools']['limit'] = 0;
                    
                    $this->pdoTools->config = array_merge($this->config['pdoClear'],$td['edit']['pdoTools']);
                    $td['value'] = $this->pdoTools->run();
                    $value = [];
                    foreach($td['value'] as $v){
                        $value[$v[$td['edit']['field']]] = $v[$td['edit']['field']];
                    }
                    $td['value'] = $value;
                    $td['edit']['json'] = json_encode($value);
                    //$this->pdoTools->addTime("getTable generateData td ".print_r($td,1));
                }else{
                    $td['value'] = $row[$td['edit']['as']];
                }
                //$this->pdoTools->addTime("getTable generateData row ".print_r($row,1));
                //$this->pdoTools->addTime("getTable generateData td ".print_r($td,1));
                if(isset($td['number'])){
                    if(!is_array($td['number'])) $td['number'] = [];
                    if(!isset($td['number'][0])){
                        $td['value'] = number_format($td['value']);
                    }else if(!isset($td['number'][2]) and !isset($td['number'][2])){
                        $td['value'] = number_format($td['value'],$td['number'][0]);
                    }else{
                        $td['value'] = number_format($td['value'],$td['number'][0],$td['number'][1],$td['number'][2]);
                    }
                }
                if($td['edit']['type'] == "date"){
                    if($td['value'])
                        $td['value'] = date($this->config['date_format'],strtotime($td['value']));
                }
                if($td['edit']['type'] == "datetime"){
                    if($td['value'])
                        $td['value'] = date($this->config['datetime_format'],strtotime($td['value']));
                }
                // if($td['edit']['type'] == 'textarea' and $this->getTables->REQUEST['gts_action'] != 'getTable/export_excel'){
                //     $td['value'] = '{ignore}'.$td['value'].'{/ignore}';
                // }
                if(isset($table['sub_where_current'])){
                    if(isset($this->getTables->REQUEST['sub_where_current'])){
                        $sub_where_current = $this->getTables->REQUEST['sub_where_current'];
                    }else{
                        $sub_where_current = json_decode($table['sub_where_current'],1);
                    }    
                    //$this->getTables->addDebug($sub_where_current,'sub_where_current  td');
                    if(isset($sub_where_current[$td['field']]) and empty($td['value'])){
                        $td['value'] = $sub_where_current[$td['field']];
                    }
                }
                //$this->getTables->addDebug($this->getTables->REQUEST['sub_where_current'],'$this->getTables->REQUEST[sub_where_current]');
                
                
                if(isset($td['content'])){
                    $filters = [];
                    foreach($table['filters'] as $f){
                        $filters[$f['edit']['field']] = $f;
                    }
                    $row['filters'] = $filters;
                    $td['content'] = $this->pdoTools->getChunk('@INLINE '.$td['content'], $row);
                    $autosave = false;
                }else{
                    $td['content'] = $td['value'];
                    $autosave = true;
                }
                //tree
                if($table['tree']){
                    if($table['tree']['treeShowField'] == $td['edit']['field']){
                        $level = (int)$this->getTables->REQUEST['gts_tree']['level'];
                        $expand = "";
                        if($level){
                            for($i=0;$i<=$level;$i++){
                                $expand .= '<span class="gtstree-indent"></span>';
                            }
                        }
                        $level++;
                        $tree_where[$table['class'].".".$table['tree']['parentIdField']] = $row[$table['tree']['idField']];
                        $table['pdoTools2']['where'] = $tree_where;
                        $table['pdoTools2']['select'] = $table['class'].".".'id';

                        $this->pdoTools->config=array_merge($this->config['pdoClear'],$table['pdoTools2']);
                        $treerows = $this->pdoTools->run();
                        $child_count = count($treerows);
                        if($child_count){
                            $expand .= '<span data-level="'.$level.'" data-parent="'.$row[$table['tree']['idField']].'" class="gtstree-expander gtstree-expander-collapsed"></span>';
                        }else{
                            $expand .= '<class="gtstree-expander"></span>';
                        }
                        $td['content'] = $expand.$td['content'];
                    }
                }
                if($td['cls']) $td['cls'] = $this->pdoTools->getChunk('@INLINE '.$td['cls'], $row);

                if(!empty($table['autosave']) and !empty($td['edit']) and $autosave){
                    
                    //autocomplect
                    if(isset($td['edit']['field_content'])){
                        $td['edit']['content'] = $row[$td['edit']['field_content']];
                    }
                    $td['edit']['value'] = $td['value'];
                    //$this->getTables->addDebug($td,'gen1  td');
                    $td['content'] = $this->pdoTools->getChunk($this->config['getTableEditRowTpl'],['edit'=>&$td['edit']]);
                    //$this->getTables->addDebug($td,'gen2  td');
                }else{
                    if($td['edit']['type'] == "checkbox"){
                        if($td['value']){
                            $td['content'] = "Да";
                        }else{
                            $td['content'] = "Нет";
                        }
                        
                    }
                }
                
                /*if(isset($td['buttons'])){
                    $td['content'] .= '<div style=""width:'.count($td['buttons'])*40 .'px;">'.$this->pdoTools->getChunk('@INLINE '.$td['buttons'], $row)."</div>";
                }*/
                //$this->pdoTools->addTime("getTable generateData td field ".print_r($td['edit']['field'],1));
                //$this->pdoTools->addTime("getTable generateData tr field ".$tr['data'][$td['edit']['field']].print_r($tr['data'],1));
                foreach($tr['data'] as $dv){
                    if($dv == $td['edit']['field']){
                        $data[$dv] = $td['value'];
                    }
                }
            }
            foreach($tr['data'] as $dv){
                if($row[$dv]){
                    $data[$dv] = $row[$dv];
                }
            }
            $r['data'] = $data;
            //tree
            if($table['tree']){
                $r['data']['gts_tree_child'] = $row[$table['tree']['idField']];
                $r['data']['gts_tree_parent'] = $row[$table['tree']['parentIdField']];
            }
            //$this->getTables->addDebug($r,'genData  $r');
            //$this->pdoTools->addTime("getTable generateData r cls {ignore}{$r['cls']} {/ignore}");
            if($r['cls']) $r['cls'] = $this->pdoTools->getChunk('@INLINE '.$r['cls'], $row);
            
            $sub = ['cls'=>'hidden'];
            $html = $this->pdoTools->getChunk($this->config['getTableRowTpl'],['tr'=>$r,'sub'=>$sub],true);
            $trs[] = [
                'tr'=>$r,
                'sub'=>$sub,
                'html'=> $html,
                ];
            //$output[] = $html;
        }
        
        
        $table['tbody']['trs'] = $trs;
        $this->pdoTools->addTime('generateData end');
        //$this->getTables->addDebug($table,'generateData $table');
        //echo "getTable generateData inner <pre>".print_r($table['tbody']['inner'],1)."</pre>";
        return $table;
    }
    public function refresh($action, $table, $data)
    {
        $table2 = $this->generateData($table);
        //$this->getTables->addDebug($table2,'refresh  table 2');
        $html = '';
        foreach($table2['tbody']['trs'] as $tr){
            $html .= $tr['html'];
        }

        $top = '';
        if($table['role']['type'] == 'document' and $table['top']['type'] == 'form'){
            $this->getTables->REQUEST['id'] = (int)$table['role']['id'];
            $resp = $this->getTables->handleRequestInt('getForm/fetch',$table['top']['form']);
            if($resp['success']) $top = $resp['data']['html'];
        }

        return $this->success('',[
            'html'=>$html,
            'nav'=>$table2['page']['content'],
            'nav_total'=>$table2['page']['total'],
            'top'=>$top,
        ]);
    }
    
    public function fetch($table = array())
    {
        
        //$this->getTables->addDebug($table,'fetch  $table');
        //$this->getTables->addDebug($this->config,'fetch  $this->config');
        //$this->pdoTools->addTime("getTable fetch table ".print_r($this->config,1));
        //echo "<pre>{ignore}".print_r($this->config,1)."{/ignore}</pre>";
        if(empty($table)){
            if(!empty($this->config['table'])){
                $table = $this->config['table'];
            }else{
                return $this->error("Нет конфига table!");
            }
        }
        //$table['pdoTools']['return'] = 'data';
        $top = '';
        if($table['role']['type'] == 'document' and $table['top']['type'] == 'form'){
            $this->getTables->REQUEST['id'] = (int)$table['role']['id'];
            $this->pdoTools->addTime("document id = {$this->getTables->REQUEST['id']}");
            $resp =$this->getTables->handleRequestInt('getForm/test',$table['top']['form']);
            if(!$resp['success']){
                return $this->error("Документ не найден!");
            }
            foreach($table['role']['where'] as $k=>$v){
                if($v == 'id'){
                    $table['pdoTools']['where'][$k] = (int)$table['role']['id'];
                }else{
                    $table['pdoTools']['where'][$k] = $v;
                }
            }
            $resp = $this->getTables->handleRequestInt('getForm/fetch',$table['top']['form']);
            if($resp['success']) $top = $resp['data']['html'];
        }
        
        if(is_string($table) and strpos(ltrim($table), '{') === 0) $table = json_decode($table, true);
        //$this->pdoTools->addTime("getTable fetch table ".print_r($table,1));
        if($table['row']){
            //$this->pdoTools->addTime("getTable fetch selects  {ignore}".print_r($this->config['selects'],1)."{/ignore}");
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
            //if(empty($table['compile'])){
                if(empty($this->config['compile']) and $table_compile = $this->getTables->getClassCache('getTable',$table['name'])){
                    
                }else{
                     
                    if($table['class'] == 'TV') $table['class'] = 'modTemplateVarResource';
                    $table['class'] = $table['class'] ? $table['class'] : 'modResource';
                    $name = $table['name'] ? $table['name'] : $table['class'];
                    
                    $table['name'] = $this->getTables->getRegistryAppName('getTable',$name);
                    $table_compile = $this->compile($table);
                    $table_compile['width'] = 100;
                    $table_compile['style'] = 1;
                    if(!empty($this->getTables->REQUEST['width'])){
                        $table_compile['width'] = $this->getTables->REQUEST['width'];
                    }
                    if(!empty($this->getTables->REQUEST['subtable_in_all_page'])){
                        $table_compile['subtable_in_all_page'] = true;
                    }else{
                        $table_compile['subtable_in_all_page'] = false;
                    }
                    $this->getTables->setClassConfig('getTable',$table_compile['name'], $table_compile);
                    
                    //$this->pdoTools->addTime("getTable fetch table  name $name !".$table['name']);
                    //$this->pdoTools->addTime("getTable fetch subtable  {ignore}".print_r($table['subtable'],1)."{/ignore}");
                    if(!empty($table['subtables'])){
                        foreach($table['subtables'] as $sub_name=>$subtable){
                            if($subtable['class'] == 'TV') $subtable['class'] = 'modTemplateVarResource';
                            $subtable['class'] = $subtable['class'] ? $subtable['class'] : 'modResource';
                            //$name = $table['subtable']['name'] ? $table['subtable']['name'] : $table['subtable']['class'];
                            $subtable['pdoTools']['class'] = $subtable['class'];
                            
                            $subtable_compile = $this->compile($subtable);
                            if($table_compile['subtable_in_all_page']) $subtable_compile['in_all_page'] = true;
                            $this->getTables->setClassConfig('getTable',$sub_name, $subtable_compile);
                        }
                    }
                //}
            }
            //echo "getTable table_compile table ".print_r($table_compile,1);
            
            $this->pdoTools->addTime('generateData start');
            $generateData = $this->generateData($table_compile);
            $this->pdoTools->addTime('generateData end');
            $generateData['top'] = $top;

            $html = $this->pdoTools->getChunk($this->config['getTableOuterTpl'], $generateData,true);
            $this->pdoTools->addTime('getChunk outer');

            //$this->pdoTools->addTime("getTable fetch table registryAppName  {ignore}".print_r($this->getTables->registryAppName,1)."{/ignore}");
            //if(!$this->config['isAjax']) $this->registerActionJS($table);
            
            return $this->success('',array('html'=>$html));
        }else{
            return $this->error("Нет конфига row!");
        }
    }
    
    
    
    public function compileActions($actions){
        if($this->config['frontend_framework_style'] == 'bootstrap_v3'){
            $icon_prefix = 'glyphicon glyphicon';
        }else{
            $icon_prefix = 'fa fa';
        }
        $default_actions = [
            'create' =>[
                'action'=>'getTable/create',
                'title'=>$this->modx->lexicon('gettables_create'),
                'cls' => 'btn',
                'icon' => "$icon_prefix-plus",
                'topBar' => [],
                'modal' => [
                    'action' => 'getModal/fetchTableModal',
                    'tpl'=>'getTableModalCreateUpdateTpl',
                    'EditFormtpl'=>'getTableEditFormTpl',
                    'title'=>'Создать',
                ],
                'tag' =>'button',
                'attr' => '',
                'style' => '',
                //'processors'=>['modResource'=>'resource/create'],
            ],
            'update' =>[
                'action'=>'getTable/update',
                'title'=>$this->modx->lexicon('gettables_edit'),
                'cls' => 'btn',
                'icon' => "$icon_prefix-edit",//'glyphicon glyphicon-edit',
                'row' => [],
                'modal' => [
                    'action' => 'getModal/fetchTableModal',
                    'tpl'=>'getTableModalCreateUpdateTpl',
                    'EditFormtpl'=>'getTableEditFormTpl',
                    'title'=>'Редактировать',
                ],
                'tag' =>'button',
                'attr' => '',
                'style' => '',
                //'processors'=>['modResource'=>'resource/update'],
            ],
            'remove' =>[
                'action'=>'getTable/remove',
                'title'=>$this->modx->lexicon('gettables_delete'),
                'cls' => 'btn btn-danger',
                'icon' => "$icon_prefix-trash", //'glyphicon glyphicon-trash',
                //'topBar' => [],
                'multiple' => ['title'=>'Удалить выбранное'],
                'row' => [],
                'tag' =>'button',
                'attr' => '',
                'style' => '',
            ],
            'toggle' =>[
                'action'=>"getTable/toggle",
                'title'=>[$this->modx->lexicon('gettables_enable'),$this->modx->lexicon('gettables_disable')],
                'multiple'=>[$this->modx->lexicon('gettables_enable'),$this->modx->lexicon('gettables_disable')],
                'cls' => ['btn btn-danger','btn btn-success'],
                'icon' => $icon_prefix == 'fa fa' ? "$icon_prefix-power-off" : 'glyphicon glyphicon-off',
                'field' => 'published',
                'row' => [],
                'tag' =>'button',
                'attr' => '',
                'style' => '',
            ],
            'subtable' =>[
                'action'=>"getTable/subtable",
                'title'=>[$this->modx->lexicon('gettables_open'),$this->modx->lexicon('gettables_close')],//['Открыть','Закрыть'],
                'cls' => ['btn get-sub-show ','btn get-sub-hide'],
                'icon' => [$icon_prefix == 'fa fa' ? "$icon_prefix-eye" : 'glyphicon glyphicon-eye-open'
                    ,$icon_prefix == 'fa fa' ? "$icon_prefix-eye-slash" : 'glyphicon glyphicon-eye-close'],
                'row' => [],
                'tag' =>'button',
                'attr' => '',
                'style' => '',
            ],
            'a' =>[
                'action'=>"getTable/a",
                'cls'=>'btn',
                'row' => [],
                'icon' => '',
                'tag' =>'a',
                'attr' => '',
                'href' => '',
                'res_id' => '',
                'style' => '',
            ],
            'custom' =>[
                'action'=>"getTable/custom",
                'cls'=>'btn',
                'row' => [],
                'icon' => '',
                'tag' =>'a',
                'attr' => '',
                'style' => '',
            ],
            'copy' =>[
                'action'=>"getTable/copy",
                'title'=>$this->modx->lexicon('gettables_copy'), //'Копировать',
                'cls'=>'btn',
                'row' => [],
                'multiple' => ['title'=>'Скопировать выбранное'],
                'icon' => $icon_prefix == 'fa fa' ? "$icon_prefix-clone" : 'glyphicon glyphicon-duplicate', //'glyphicon-glyphicon-duplicate',
                'tag' =>'button',
                'attr' => '',
                'style' => '',
            ],
            'export_excel' =>[
                'action'=>"getTable/export_excel",
                'title'=>$this->modx->lexicon('gettables_export_to_excel'), //'Экспорт в excel',
                'cls'=>'btn',
                'multiple' => ['title'=>$this->modx->lexicon('gettables_export_to_excel')],
                'icon' => $icon_prefix == 'fa fa' ? "fas fa-file-excel" : 'glyphicon glyphicon-export', //'glyphicon-glyphicon-duplicate',
                'tag' =>'button',
                'attr' => '',
                'style' => '',
            ],
            
        ];
        $compile_actions = [];
        if(empty($actions)){
            $compile_actions = [];//$default_actions;
        }else{
            foreach($actions as $k=>$a){
                if(!isset($a['action'])){
                    if(isset($default_actions[$k])){
                        //Прописываем дефолтовое действие, например для  $actions['create'=>[]]
                        if(is_array($actions[$k]['modal']) and is_array($default_actions[$k]['modal'])){
                            $actions[$k]['modal'] = array_merge($default_actions[$k]['modal'],$actions[$k]['modal']);
                        }
                        $compile_actions[$k] = array_merge($default_actions[$k],$actions[$k]);
                    }else{
                        $this->pdoTools->addTime("Не определено действие $k =>".print_r($actions[$k],1));
                    }
                    //$this->pdoTools->addTime(" действия {$k}. Действие  $k ".print_r($compile_actions[$k],1));
                }else{
                    if(is_string($a['action'])){
                        //Прописываем дефолтовое действие, например для  $actions['create'=>['action'=>'getTable/create',]]. Чтобы можно было задать много getTable/create 
                        $ta = explode("/",$a['action']);
                        if($ta[0] == "getTable"){
                            if(isset($default_actions[$ta[1]])){
                                if(is_array($actions[$k]['modal']) and is_array($default_actions[$ta[1]]['modal'])){
                                    $actions[$k]['modal'] = array_merge($default_actions[$ta[1]]['modal'],$actions[$k]['modal']);
                                }
                                $compile_actions[$k] = array_merge($default_actions[$ta[1]],$actions[$k]);
                                //$this->pdoTools->addTime(" действия {$ta[1]}. Действие  $k ".print_r($compile_actions[$k],1));
                            }else{
                                $this->pdoTools->addTime("В getTable нет действия {$ta[1]}. Действие  $k ".print_r($actions[$k],1));
                            }
                        }else{
                            $compile_actions[$k] = $actions[$k];
                        }
                    }
                }
            }
            
            //$compile_actions = $actions;
        }
        
        //$this->pdoTools->addTime("table compile_actions {ignore}".print_r($compile_actions,1)."{/ignore}");
        foreach($compile_actions as $k=>&$a){
            if(!empty($a['permission'])){
                if (!$this->modx->hasPermission($a['permission'])){ unset($actions[$k]); continue;}
            }
            
            if(empty($a['tag'])) $a['tag'] = 'button';

            if($a['action'] == "getTable/subtable" and !empty($a['subtable_name'])){
                if(empty($a['buttons'])){
                    $html = [];
                    $html[0] = $a['icon'][0] ? '<i class="'.$a['icon'][0].'"></i>' : $a['title'][0];
                    $html[1] = $a['icon'][1] ? '<i class="'.$a['icon'][1].'"></i>' : $a['title'][1];
                    $html[0] .= $a['text'] ? $a['text'] : '';
                    $html[1] .= $a['text'] ? $a['text'] : '';
                    $a['buttons'] = [
                        'sub_show' =>[
                            'cls' => $a['cls'][0],
                            'html' => $html[0],
                            'field'=> $a['field'],
                            'title'=> $a['title'][0],
                            'data' =>[
                                'name'=>'subtable',
                                'action'=>$a['action'],
                                'subtable_name'=> $a['subtable_name'],
                                'js_action'=>'sub_show'
                            ],
                        ],
                        'sub_hide' =>[
                            'cls' => $a['cls'][1],
                            'field'=> $a['field'],
                            'html' => $html[1],
                            'title'=> $a['title'][1],
                            'style'=>'display:none;',
                            'data' =>[
                                'name'=>'subtable',
                                'action'=>$a['action'],
                                'subtable_name'=> $a['subtable_name'],
                                'js_action'=>'sub_hide'
                            ],
                        ],
                    ];
                    if(isset($a['modal'])){
                        $a['buttons']['enable']['data']['modal'] = $a['modal']['action'];
                        $a['buttons']['disable']['data']['modal'] = $a['modal']['action'];
                    } 
                }
                if($a['multiple']){
                    $ttopBar = [
                        'section' => 'topBar/topline/multiple',
                        'cls' => '',
                        'bcls' => 'multiple',
                        'buttons' => $a['buttons']
                    ];
                    $a['topBar'] = $ttopBar;
                }
                if(isset($a['row'])){
                    $a['row']['buttons'] = $a['buttons'];
                } 
            }else if($a['action'] == "getTable/toggle"){
                if(empty($a['buttons'])){
                    $html = [];
                    $html[0] = $a['icon'] ? '<i class="'.$a['icon'].'"></i>' : $a['title'][0];
                    $html[1] = $a['icon'] ? '<i class="'.$a['icon'].'"></i>' : $a['title'][1];
                    $html[0] .= $a['text'] ? $a['text'] : '';
                    $html[1] .= $a['text'] ? $a['text'] : '';
                    $a['buttons'] = [
                        'enable' =>[
                            'cls' => $a['cls'][0],
                            'html' => $html[0],
                            'field'=> $a['field'],
                            'title'=> $a['title'][0],
                            'data' =>[
                                'name'=>'toggle',
                                'action'=>$a['action'],
                                'field'=> $a['field'],
                                'toggle'=>'enable'
                            ],
                        ],
                        'disable' =>[
                            'cls' => $a['cls'][1],
                            'field'=> $a['field'],
                            'html' => $html[1],
                            'title'=> $a['title'][1],
                            'data' =>[
                                'name'=>'toggle',
                                'action'=>$a['action'],
                                'field'=> $a['field'],
                                'toggle'=>'disable'
                            ],
                        ],
                    ];
                    if(isset($a['modal'])){
                        $a['buttons']['enable']['data']['modal'] = $a['modal']['action'];
                        $a['buttons']['disable']['data']['modal'] = $a['modal']['action'];
                    } 
                }
                if($a['multiple']){
                    $ttopBar = [
                        'section' => 'topBar/topline/multiple',
                        'cls' => '',
                        'bcls' => 'multiple',
                        'buttons' => $a['buttons']
                    ];
                    $a['topBar'] = $ttopBar;
                }
                if(isset($a['row'])){
                    $a['row']['buttons'] = $a['buttons'];
                } 
            }else{
                if(!isset($a['buttons']) and $a['action'] != "getTable/a" and $a['action'] != "getTable/custom"){
                    $html = '';
                    $html = $a['icon'] ? '<i class="'.$a['icon'].'"></i>' : $a['title'];
                    $html .= $a['text'] ? $a['text'] : '';

                    $data = $a['data'] ? $a['data'] : [];
                    $data['name'] = $k;
                    $data['action'] = $a['action'];
                    if($a['long_process']) $data['long_process'] = 1;

                    $a['buttons'] = [
                        $k =>[
                            'cls' => $a['cls'],
                            'html' => $html,
                            'title'=> $a['title'],
                            'data' =>$data,
                        ],
                    ];
                    if(isset($a['modal'])){
                        $a['buttons'][$k]['data']['modal'] = $a['modal']['action'];
                    }
                    if(isset($a['row'])) $a['row']['buttons'] = $a['buttons'];
                }else{
                    $a['buttons'] = [];
                    if($a['icon']) $a['html'] = $a['icon'] ? '<i class="'.$a['icon'].'"></i>' : $a['title'];
                    $a['html'] .= $a['text'] ? $a['text'] : '';
                    if($a['href']) $a['attr'] .= ' href="'.$a['href'].'"';
                    
                    if(isset($a['row'])) $a['row']['buttons'] = [];
                }
                if(isset($a['topBar'])){
                    $ttopBar = [
                        'section' => 'topBar/topline/first',
                        'cls' => '',
                        'bcls' => 'first',
                        'buttons' => $a['buttons']
                    ];
                    $a['topBar'] = $ttopBar;
                }
                if($a['multiple']){
                    $ttopBar = [
                        'section' => 'topBar/topline/multiple',
                        'cls' => '',
                        'bcls' => 'multiple',
                        'buttons' => $a['buttons']
                    ];
                    $a['topBar'] = $ttopBar;
                }
            }
            
        }
        return $compile_actions;
    }
    public function compileTopBar($actions)
    {
        $topBar = [];
        foreach($actions as $a){
            if($a['topBar']){
                $buttons = [];
                if(empty($a['topBar']['buttons'])){
                    $buttons[] = $this->pdoTools->getChunk($this->config['getTableActionTpl'], $a);
                    //'<'.$a['tag'].' class="'.$a['cls'].' '.$a['attr'].' title="'.$a['title'].'"> '.$a['html'].'</'.$a['tag'].'>';
                }else{
                    foreach($a['topBar']['buttons'] as $arbk=>$arb){
                        $str_data = "";
                        foreach($arb['data'] as $arbdk=>$arbdv){
                            $str_data .= ' data-'.$arbdk.'="'.$arbdv.'"';
                        } 
                        $a['cls'] = ' get-table-'.$a['topBar']['bcls'].' '.$arb['cls'];
                        $a['attr'] .= ' '.$str_data;
                        $a['title'] = $arb['title'];
                        $a['html'] = $arb['html'];
                        $buttons[] = $this->pdoTools->getChunk($this->config['getTableActionTpl'], $a);
                        //'<button type = "button" class="btn get-table-'.$a['topBar']['bcls'].' '.$arb['cls'].'" '.
                        //$str_data.' title="'.$arb['title'].'"> '.$arb['html'].'</button>';
                    }
                }
                $a['topBar']['content'] = implode(' ',$buttons);
                $topBar[$a['topBar']['section']][] = $a['topBar'];
            }
                
        }
        /*foreach($filters as $f){
            $topBar[$f['section']][] = $f;
        }*/
        return $topBar;
    }
    public function compile($table)
    {
        if($table['class'] == 'TV') $table['class'] = 'modTemplateVarResource';
        $class = $table['class'] ? $table['class'] : 'modResource';
        $name = $table['name'] ? $table['name'] : $class;
        $cls = $table['cls'] ? $table['cls'] : $class;
        $thead_tr = [];
        $ths = [];
        $body_tr = [];
        $tds = [];
        $filters = [];
        $topBar = [];
        $button = [];
        $modal = [];
        $defaultFieldSet = [];
        
        //$this->pdoTools->addTime("table compile {ignore}".print_r($table,1)."{/ignore}");
        if(is_array($table['defaultFieldSet'])){
            foreach($table['defaultFieldSet'] as $df=>$dfv){
                if(is_array($dfv)){
                    if(!isset($dfv['class'])) $dfv['class'] = $class;
                    if($dfv['class'] = 'TV') $dfv['class'] = 'modTemplateVarResource';
                    $defaultFieldSet[$df] = $dfv;
                }else{
                    $defaultFieldSet[$df] = ['class'=>$class,'value'=>$dfv];
                }
            }
        }
        //$this->pdoTools->addTime("table compile table[actions] {ignore}111".print_r($table,1)."{/ignore}");
        $actions = $table['actions'] ? $table['actions'] : [];
        
        $actions = $this->compileActions($actions);
        $actions_row = [];
        $row_menus = [];
        foreach($actions as $k=>$a){
            if($a['row']){
                //$actions_row[$k] = ['buttons'=> $a['row']['buttons'],];
                if(isset($a['row']['menu'])){
                    if(!empty($a['content'])){
                        $row_menus[$a['row']['menu']][$k] = $a['content'];
                    }else if(empty($a['buttons'])){
                            $a['html'] = $a['html']."<span>".$a['title']."</span>";
                            // $a['attr'] .= ' type="button"';
                            // $a['cls'] .= ' dropdown-item';
                        $row_menus[$a['row']['menu']][$k] = $this->pdoTools->getChunk($this->config['getTableActionTpl'], $a);
                        //'<'.$a['tag'].' class="'.$a['cls'].' '.$a['attr'].' title="'.$a['title'].'"> '.$a['html'].'</'.$a['tag'].'>';
                    }else{
                        $row_menus[$a['row']['menu']][$k] = $this->compileActionButtons($a);
                    }
                }else{
                    if(!empty($a['content'])){
                        $actions_row[$k] = $a['content'];
                    }else if(empty($a['buttons'])){
                        $actions_row[$k] = $this->pdoTools->getChunk($this->config['getTableActionTpl'], $a);
                        //'<'.$a['tag'].' class="'.$a['cls'].' '.$a['attr'].' title="'.$a['title'].'"> '.$a['html'].'</'.$a['tag'].'>';
                    }else{
                        $actions_row[$k] = $this->compileActionButtons($a);
                    }
                }
            } 
            if(isset($a['modal'])) $modal[$a['action']] = $a['modal'];
        }
        if(!empty($row_menus) and !empty($table['menu'])){
            foreach($table['menu'] as $mname=>$mval){
                $mval['buttons'] = $row_menus[$mname];
                $actions_row[$mname] = $this->pdoTools->getChunk('getTable.menu.tpl', $mval);
            }
        }
        if(isset($table['checkbox']) and $table['checkbox']){
            $checkbox = [
                'th' => '<input type="checkbox" class="get-table-check-all">',
                'td' => '<input type="checkbox" class="get-table-check-row">',
            ];
            if(is_array($table['checkbox'])){
                $checkbox = array_merge($checkbox,$table['checkbox']);
            }
            $ths[] = ['name'=>'checkbox','content'=>$checkbox['th']];
            $tds[] = ['name'=>'checkbox','content'=>$checkbox['td']];
        }
        $temp_tds = [];
        if(isset($table['row']['cols'])){
            $temp_tds = $table['row']['cols'];
            unset($table['row']['cols']);
            $thead_tr = $table['row'];
            $body_tr = $table['row'];
        }else{
            $temp_tds = $table['row'];
        }
        $temp_tds2 = [];
        foreach($temp_tds as $field => $value){ 
            if(is_string($value)){
                $temp_tds2[$value] = [];
            }else{
                $temp_tds2[$field] = $value;
            }
        }
        $data = ['id'];
        $edits = [];
        $filter_position = 0;
        //$this->pdoTools->addTime("getTable compile temp_tds2 $name ".print_r($temp_tds2,1));
        foreach($temp_tds2 as $field => $value){
            if(!empty($value['permission'])){
                if (!$this->modx->hasPermission($value['permission'])) continue;
            }
            $value['field'] = $value['field'] ? $value['field'] : $field;
            $th = [];
            $th['cls'] = $value['cls'];
            $th['field'] = $value['field'];
            $th['name'] = $value['name'] ? $value['name'] : $value['field'];
            $th['content'] = $value['label'] ? $value['label'] : $th['name'];
            
            
            $td = [];
            $td['cls'] = $value['cls'];
            $td['name'] = $th['name'];
            $td['field'] = $th['field'];
            if(isset($value['number'])) $td['number'] = $value['number'];
            //$td['value'] = '{$tr.'.$value['field'].'}';
            //$td['content'] = $value['content'] ? $value['content'] : '{$'.$value['field'].'}';
            if($value['content']) $td['content'] = $value['content'];
            
            
            
            //конфигурация редактирования поля
            $edit = [
                'field' => $value['field'],
                'type' => 'text',
                'label' => $th['content'],
                'placeholder' => $th['content'],
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
            
            if(empty($value['as'])){
                $edit['as'] = $td['field'];
            }else{
                $edit['as'] = $value['as'];
            }
            if($value['skip_modal']){
                $edit['skip_modal'] = $value['skip_modal'];
            }
            if($value['field'] == 'id') $edit['type'] = 'row_view';
            if(!empty($value['default'])) $edit['default'] = $value['default'];
            if(!empty($value['force'])) $edit['force'] = $value['force'];

            if(!empty($value['edit'])) $edit = array_merge($edit,$value['edit']);
            //$this->pdoTools->addTime("getTable fetch table_compile {$table['class']} value edit {$edit['class']}  {ignore}".print_r($value['edit'],1)."{/ignore}");
            //$this->pdoTools->addTime("getTable fetch table_compile {$table['class']} edit {$edit['class']} {ignore}".print_r($edit,1)."{/ignore}");
            if(empty($edit['where_field'])){
                if($edit['type'] == "text"){
                    $edit['where_field'] = '`'.$value['class'].'`.`'.$value['field'].'`:LIKE';
                }else{
                    $edit['where_field'] = '`'.$value['class'].'`.`'.$value['field'].'`';
                }
            }
            //$this->pdoTools->addTime("getTable fetch edit {ignore}".print_r($edit,1)."{/ignore}");
            if(isset($edit['select']) and $this->config['selects'][$edit['select']]){
                $edit['type'] = 'select';
                $edit['select'] = $this->config['selects'][$edit['select']];
            }
            if(isset($value['pdoTools'])) $edit['pdoTools'] = $value['pdoTools'];

            //фильтры
            if(isset($value['filter'])){
                
                $tf = [
                    'section' => 'th',
                    'position' => $filter_position,
                    'cls' => '',
                    'cols'=>2,
                    'edit'=>$edit,
                    'default'=>[]
                    //'content' => '<input type="text" name="'.$value['field'].'" value="{\''.$value['field'].'\' | placeholder}" class="form-control" placeholder="'.$th['name'].'">'
                ];
                $filter_position++;
                if(is_string($value['filter'])){
                    $tf['where'] = $value['filter'];
                }
                if(is_array($value['filter'])){
                    //$this->pdoTools->addTime("getTable filter  ".print_r($value['filter'],1));
                    if(is_array($value['filter']['edit'])){
                        $value['filter']['edit'] = array_merge($edit,$value['filter']['edit']);
                    }
                    if(is_array($value['filter']['default'])){
                        $tf['default'] = array_merge($tf['default'],$value['filter']['default']);
                    }
                    $tf = array_merge($tf,$value['filter']);
                }
                $filters[] = $tf;
            }
            //end фильтры
            if(isset($value['edit']) and $value['edit'] == false ){
                
            }else{
                if(isset($value['modal_only'])){
                    $edit['modal_only'] = 1;
                    $edits[] = $edit;
                    continue;
                }
                $td['edit'] = $edit;
                $edits[] = $edit;
            }
            
            if(isset($value['data'])) $data[] = $value['field'];
            if(isset($value['actions'])){
                $td['actions'] = $this->compileActions($value['actions']);
                $buttons = [];
                foreach($td['actions'] as $k=>$a){
                    //$buttons[$k]['buttons'] = $a['buttons'];
                    if(empty($a['buttons'])){
                        $buttons[$k] = $this->pdoTools->getChunk($this->config['getTableActionTpl'], $a);
                        //'<'.$a['tag'].' class="'.$a['cls'].' '.$a['attr'].' title="'.$a['title'].'"> '.$a['html'].'</'.$a['tag'].'>';
                    }else{
                        $buttons[$k] = $this->compileActionButtons($a);
                    }
                }
                //$this->pdoTools->addTime("getTable compileActions td actions ".print_r($td['actions'],1));
                //$buttons = $this->compileActionButtons($buttons);
                $td['edit']['buttons'] = implode('&nbsp;',$buttons);
            }
            if($td['edit']['type'] == 'hidden'){
                $td['style'] = 'display:none;';
                $th['style'] = 'display:none;';
            }
            if($value['style']){
                $td['style'] = $td['style'].$value['style'];
                $th['style'] = $th['style'].$value['style'];
            }
            $ths[] = $th;
            $tds[] = $td;
        }
        
        if(is_string($table['data']))
            $table['data'] = explode(",",$table['data']);
        
        //$this->pdoTools->addTime("getTable compile table data".print_r($table['data'],1));
        if(empty($table['data'])) $table['data'] = [];
        if(isset($table['sortable']) and is_array($table['sortable'])){
            if($table['sortable']['field']){
                $body_tr['sortable'] = true;
            }
        }
        $body_tr['data'] = array_merge($table['data'],$data);
        //if(empty($body_tr['data'])) $body_tr['data'] = ['id'];
        //$this->pdoTools->addTime("getTable compile table actions_row".print_r($actions_row,1));
        if(!empty($actions_row)){
            //собираем кнопки
            //$buttons = $this->compileActionButtons($actions_row);
             //text-right
            $ths[] = ['cls'=>'text-right','name'=>'actions','content'=> $this->modx->lexicon('gettables_actions')];
            $tds[] = ['cls'=>'text-right','name'=>'actions','content'=> implode('&nbsp;',$actions_row),'style'=>'white-space: nowrap;'];
        }
        //$this->pdoTools->addTime("getTable compile buttons {ignore}".print_r($actions_row,1)."{/ignore}");
        
        $topBar = $this->compileTopBar($actions);
        //$this->pdoTools->addTime("getTable compileTopBar topBar {ignore}".print_r($topBar,1)."{/ignore}");
        //пока убрал
        //if(!isset($table['checkbox'])) unset($topBar['topBar/topline/multiple']);
        $topBar['hash'] = $this->config['hash'];
        $topBar['table_name'] = $name;
        
        $thead_tr['ths'] = $ths;
        $body_tr['tds'] = $tds;
        $addFilter = [];
        if(!empty($table['filters'])) $addFilter = $table['filters'];
            
        $filters = $this->addAndSortFilter($filters,$addFilter); 
        
        
        
        $table_compile = [
            'name'=>$name,
            'label'=>$table['label']?:'',
            'pdoTools'=>$table['pdoTools'],
            'hash'=> $this->config['hash'],
            'class'=>$class,
            'cls'=>$cls,
            'filters'=>$filters,
            'topBar' =>$topBar,
            'thead'=>['tr'=>$thead_tr],
            'tbody'=>['tr'=>$body_tr],
            'tfoot'=>[],
            'actions'=>$actions,
            'modal'=>$modal,
            'edits'=>$edits,
            'defaultFieldSet'=>$defaultFieldSet,
            //'commands'=>$table['commands'],
            'loadModels'=>$this->config['loadModels'],
        ];
        if(isset($table['export'])) $table_compile['export'] = $table['export'];
        if(isset($table['autosave'])) $table_compile['autosave'] = $table['autosave'];
        if(!empty($table['sub_where'])) $table_compile['sub_where'] = $table['sub_where'];
        if(!empty($table['sub_default'])) $table_compile['sub_default'] = $table['sub_default'];
        if(!empty($table['event'])) $table_compile['event'] = $table['event'];
        if(isset($table['sortable']) and is_array($table['sortable'])){
            $table_compile['sortable'] = $table['sortable'];
        }
        if(isset($table['tree']) and is_array($table['tree'])){
            $table_compile['tree'] = $table['tree'];
        }
        if(isset($table['role']) and is_array($table['role'])){
            $table_compile['role'] = $table['role'];
        }
        if(isset($table['top']) and is_array($table['top'])){
            $table_compile['top'] = $table['top'];
        }
        if(!empty($table['prepareRow'])) $table_compile['prepareRow'] = $table['prepareRow'];
        //$table['role']['type'] == 'document' and $table['top']['type'] == 'form'
        //if(!empty($table['commands'])) $table_compile['commands'] = $table['commands'];
        return $table_compile;
    }
    
    public function compileActionButtons($a)
    {
        //$this->pdoTools->addTime("getTable compileActionButtons $a ".print_r($a,1));
        $buttons = []; 
            if($a['action'] == "getTable/subtable"){
                $buttons_toggle = [];
                $field = '';
                foreach($a['row']['buttons'] as $arbk=>$arb){
                    $str_data = "";
                    foreach($arb['data'] as $arbdk=>$arbdv){
                        $str_data .= ' data-'.$arbdk.'="'.$arbdv.'"';
                    }
                    $field = $arb['field'];
                    $ta = [];
                    $ta['cls'] = ' get-table-row '.$arb['cls'];
                    $ta['attr'] = $a['attr'] . ' '.$str_data;
                    $ta['title'] = $arb['title'];
                    $ta['html'] = $arb['html'];
                    $ta['style'] = $arb['style'];
                    $ta['tag'] = $a['tag'];
                    if(isset($a['row']['menu'])){
                        $ta['html'] = $ta['html']."<span>".$ta['title']."</span>";
                        // $ta['attr'] .= ' type="button"';
                        // $ta['cls'] .= ' dropdown-item';
                    }
                    $buttons_toggle[$arbk] = $this->pdoTools->getChunk($this->config['getTableActionTpl'], $ta);

                    //$buttons_toggle[$arbk] ='<button type = "button" class="btn get-table-row '.$arb['cls'].'" '.$str_data.
                    //' title="'.$arb['title'].'" style="'.$arb['style'].'"> '.$arb['html'].'</button>';
                }
                $buttons[] = $buttons_toggle['sub_show'];
                $buttons[] = $buttons_toggle['sub_hide'];
            }else if($a['action'] == "getTable/toggle"){
                $buttons_toggle = [];
                $field = '';
                foreach($a['row']['buttons'] as $arbk=>$arb){
                    $str_data = "";
                    foreach($arb['data'] as $arbdk=>$arbdv){
                        $str_data .= ' data-'.$arbdk.'="'.$arbdv.'"';
                    }
                    $field = $arb['field'];
                    $t = '';
                    if($arbk != 'enable') $t = '!';
                    $ta = [];
                    $ta['cls'] = ' get-table-row '.$arb['cls'];
                    $ta['attr'] = $a['attr'] . ' '.$str_data;
                    $ta['title'] = $arb['title'];
                    $ta['html'] = $arb['html'];
                    $ta['style'] = $arb['style'].' {if '.$t.'$'.$field.'}display:none;{/if}';
                    $ta['tag'] = $a['tag'];
                    if(isset($a['row']['menu'])){
                        $ta['html'] = $ta['html']."<span>".$ta['title']."</span>";
                        // $ta['attr'] .= ' type="button"';
                        // $ta['cls'] .= ' dropdown-item';
                    }
                    $buttons_toggle[$arbk] = $this->pdoTools->getChunk($this->config['getTableActionTpl'], $ta);
                    //$buttons_toggle[$arbk] ='<button type = "button" class="btn get-table-row '.$arb['cls'].'" '.
                    //$str_data.' style="{if '.$t.'$'.$field.'}display:none;{/if}" title="'.$arb['title'].'"> '.$arb['html'].'</button>';
                    
                }
                $buttons[] = $buttons_toggle['enable'];
                $buttons[] = $buttons_toggle['disable'];
            }else{
                foreach($a['row']['buttons'] as $arbk=>$arb){
                    $str_data = "";
                    foreach($arb['data'] as $arbdk=>$arbdv){
                        $str_data .= ' data-'.$arbdk.'="'.$arbdv.'"';
                    } 
                    $a['cls'] = ' get-table-row '.$arb['cls'];
                    $a['attr'] .= ' '.$str_data;
                    $a['title'] = $arb['title'];
                    $a['html'] .= $arb['html'];

                    if(isset($a['row']['menu'])){
                        $a['html'] = $a['html']."<span>".$a['title']."</span>";
                        // $a['attr'] .= ' type="button"';
                        // $a['cls'] .= ' dropdown-item';
                    }

                    $buttons[] = $this->pdoTools->getChunk($this->config['getTableActionTpl'], $a);

                    //$buttons[] ='<button type = "button" class="btn get-table-row '.$arb['cls'].'" '.
                    //$str_data.' title="'.$arb['title'].'"> '.$arb['html'].'</button>';
                }
            }
        
        //$this->pdoTools->addTime("getTable compileActionButtons buttons ".print_r($buttons,1));
        return implode('&nbsp;',$buttons);
    }
    public function addAndSortFilter($filters,$addFilter)
    {
        $filter_position = count($filters);
        foreach($addFilter as $f){
            if(empty($f['where'])) continue;
            if(empty($f['edit']['field'])) continue;

            //$f['edit']['where_field']
            
            if(empty($f['section'])) $f['section'] = 'topBar/topline/filters';
            if(empty($f['position'])){ $f['position'] = $filter_position; $filter_position++;}
            $filters[] = $f;
        }
        usort
        ( 
            $filters,
            create_function
            (   
                '$a,$b', 
                'return ($a["position"] - $b["position"]);' 
            )
        );
        
        foreach($filters as &$f){
            if(isset($f['edit']['select']) and $this->config['selects'][$f['edit']['select']]){
                $f['edit']['type'] = 'select';
                $f['edit']['select'] = $this->config['selects'][$f['edit']['select']];
                
            }

            if(empty($f['edit']['type'])) $f['edit']['type'] = 'text';
        }
        //$this->pdoTools->addTime("getTable addAndSortFilter ".print_r($filters,1));
        return $filters;
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