<?php

class getModal
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
	public function checkAccsess($action)
    {
		switch($action){
			case 'fetchTableModal':
				return true;
				break;
			default:
				return false;
		}
	}
    public function handleRequest($action, $data = array())
    {
		$class = get_class($this);
		switch($action){
			case 'fetchTableModal':
				return $this->fetchTableModal($data);
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

	public function fetchTableModal($data)
    {
		//echo json_encode($data).'! '.$data['data']['button_data']['action'].'!';
		//$data = $data['data'];
		//$this->getTables->addDebug($data,'fetchTableModal  $data');
		$table_action = !empty($data['button_data']['action'])
            ? (string)$data['button_data']['action']
            : false;
		$table_name = !empty($data['table_name'])
            ? (string)$data['table_name']
            : false;
		$tr_data = !empty($data['tr_data']) ? $data['tr_data'] : [];
           
		if(!$table_action) return $this->error("Нет table_action!");
		
		if(!$table_name) return $this->error("Нет table_name!");
		//$this->getTables->addDebug($_SESSION['getTables']);
		//$this->getTables->clearCache();
		if(empty($this->config['getTable'][$table_name])) return $this->error("Таблица $table_name не найдена! ",$this->config);
		
		$table = $this->config['getTable'][$table_name];
		//echo json_encode($table);
		$modal = $table['modal'][$table_action];
		$edits = $table['edits'];
		$modal['hash'] = $this->config['hash'];
		$modal['table_name'] = $table_name;
		$modal['table_action'] = $table_action;
		
		if($tr_data){
			$edits = $this->generateEditsData($edits,$tr_data,$table);
		}
		if(!empty($table['defaultFieldSet'])) $edits = $this->defaultFieldSet($edits,$table['defaultFieldSet']);
		//return $this->error("getModal fetchTableModal modal! ",$tr_data);
		$modal['edits'] = $edits;
		$this->getTables->addDebug($modal,'fetchTableModal $modal ');
		$html = $this->pdoTools->getChunk($this->config[$modal['tpl']], ['modal'=>$modal]);
		
		return $this->success('',array('html'=>$html));
    }
	public function defaultFieldSet($edits,$defaultFieldSet)
    {
		foreach($edits as &$edit){
			if($defaultFieldSet[$edit['field']])
				$edit['value'] = $defaultFieldSet[$edit['field']]['value'];
		}
		return $edits;
    }
	public function generateEditsData($edits,$tr_data,$table)
    {
		$pdoConfig = $table['pdoTools'];
		//$this->getTables->addDebug($table['pdoTools'],'$table[pdoTools] ');
		$pdoConfig['limit'] = 1;
		$pdoConfig['return'] = 'data';
		foreach($tr_data as $k=>$v){
			foreach($edits as $edit){
				if($edit['field'] == $k)
					$pdoConfig['where'][$edit['where_field']] = $v;
			}
		}

		$this->pdoTools->config = array_merge($this->config['pdoClear'],$pdoConfig);

		$rows = $this->pdoTools->run();
		
		
		//$this->getTables->addDebug($rows,'$rows ');
		
		foreach($rows as $row){
			foreach($edits as &$edit){
				$edit['value'] = $row[$edit['field']];
			}
		}
		return $edits;
    }
	
	public function getStyleChunks()
    {
		/*if($this->config['frontend_framework_style'] != 'bootstrap_v3' and $this->config['getTabsTpl'] == 'getTabs.tpl'){
			if($propSet = $this->modx->getObject('modPropertySet',array('name'=>'getTables_'.$this->config['frontend_framework_style']))){
				if($chunk = $this->modx->getObject('modChunk', array('name' => $propSet->getTabsTpl))){
					$this->config['getTabsTpl'] = $propSet->getTabsTpl;
				}
			}
		}*/
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