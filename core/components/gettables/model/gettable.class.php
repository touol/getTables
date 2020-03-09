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
	
    public function handleRequest($action, $data = array())
    {
		$class = get_class($this);
		
		if($action == "fetch" and !$this->config['isAjax'])
				return $this->fetch($data);
		
		$this->getTables->addDebug($table_name,'handleRequest  $table_name');
		if(!$table = $this->getTables->getClassCache('getTable',$data['table_name'])){
			return $this->error("$table_name не найдено");
		}
		if($this->config['isAjax'] and $selects = $this->getTables->getClassCache('getSelect','all')){
			$this->config['selects'] = $selects;
		}  
		
		switch($action){
			case 'create': case 'update': case 'toggle': case 'remove': case 'set': case 'autosave':
				require_once('gettableprocessor.class.php');
				$getTableProcessor = new getTableProcessor($this, $this->config);
				return $getTableProcessor->run($action, $table, $data);
				break;
				
			case 'refresh':
				return $this->refresh($action, $table, $data);
				break;
			case 'filter':
				return $this->refresh($action, $table, $data);
				break;
			case 'subtable':
				return $this->subtable($action, $table,$table_name, $data);
				break;
		}
		return $this->error("Метод $action в классе $class не найден!");
		/*if(method_exists($this,$action)){
			return $this->$action($data);
		}else{
			return $this->error("Метод $action в классе $class не найден!");
		}*/
    }
	/*public function filter($action, $table, $data)
    {
		$table = $this->generateData($table);
		$html = $table['tbody']['inner'];
		return $this->success('',array('html'=>$html));
	}*/
	public function subtable($action, $table,$table_name, $data)
    {
		$current_action = $table['actions'][$action];
		//$this->getTables->addDebug($table,'subtable  $table');
		/*
		'sub_show' =>[
							'cls' => $a['cls'][0],
							'html' => $html[0],
							'field'=> $a['field'],
							'data' =>[
								'name'=>'subtable',
								'action'=>$a['action'],
								'subtable_name'=> $table['subtable']['name'],
								'js_action'=>'sub_show'
							],
							*/
		if(empty($data['button_data']['subtable_name'])) return $this->error('subtable_name не найдено',array('button_data'=>$data['button_data']));
		
		if(!$subtable = $this->getTables->getClassCache('getTable',$data['button_data']['subtable_name'])){
			return $this->error('subtable не найдено',array('button_data'=>$data['button_data']));
		}
		//$this->getTables->addDebug($table,'subtable $table');
		//$this->getTables->addDebug($subtable,'subtable subtable');
		$subtable['parent_table_name'] = $table_name;
		
		$pdoConfig = $subtable['pdoClear'];
		$pdoConfig['return'] = 'data';
		/*foreach($edit['search_fields'] as $k=>&$v){
					if($v == 'id') $v = (int)$data['id'];
				}*/
		$where = $pdoConfig['where'] ? $pdoConfig['where'] : [];
		$this->getTables->addDebug($current_action,'subtable current_action');
		foreach($current_action['where'] as $where_field=>$where_value){
			foreach($data['tr_data'] as $tr_field =>$tr_value){
				if($tr_field == $where_value)
					$where[$where_field] = $tr_value;
			}
		}
		$this->getTables->addDebug($where,'subtable where');
		$pdoConfig['where'] = $where;
		$subtable['pdoTools'] = $pdoConfig;
		$this->getTables->setClassConfig('getTable',$subtable['name'], $subtable);
		//получаем таблицу дочернию
		$subtable = $this->generateData($subtable);
		$sub_content = $this->pdoTools->getChunk($this->config['getTableOuterTpl'], $subtable);
		
		return $this->success('',array('sub_content'=>$sub_content));
	}
	
	public function generateData($table,$pdoConfig =[])
    {
		$table = $this->addFilterTable($table);
		if(empty($table['paginator']) or ($table['paginator'] !== false and $pdoConfig['limit'] != 1)){
			$paginator = true;
			$table['pdoTools']['setTotal'] = true;//offset
			if(!empty((int)$_REQUEST['limit'])) $table['pdoTools']['limit'] = (int)$_REQUEST['limit'];
			if(!empty((int)$_REQUEST['page'])) $table['pdoTools']['offset'] = ((int)$_REQUEST['page'] - 1)*$table['pdoTools']['limit'];
		}
		//echo "getTable generateData table ".print_r($table,1);
		//$this->pdoTools->addTime("getTable generateData table ".print_r($table,1));
		$this->getTables->addDebug($table['pdoTools'],'generateData $table[pdoTools]');
		//$this->getTables->addDebug($table['query'],'generateData $table[query]');
		$table['pdoTools']['return'] = 'data';
		
		$this->pdoTools->config=array_merge($this->config['pdoClear'],$table['pdoTools'],$pdoConfig,$table['query']);
		//$this->pdoTools->addTime("getTable generateData this->pdoTools->config ".print_r($this->pdoTools->config,1));
		$this->getTables->addDebug($this->pdoTools->config,'generateData this->pdoTools->config');
		$rows = $this->pdoTools->run();
		
		if($paginator){
			$limit = $this->pdoTools->config['limit'];
			$total = $this->modx->getPlaceholder($this->pdoTools->config['totalVar']);
			$table['page']['max'] = ceil($total/$limit);
			if(!empty((int)$_REQUEST['page'])){
				$table['page']['current'] = (int)$_REQUEST['page'];
			}else{
				$table['page']['current'] = 1;
			}
			$table['page']['total'] = $total;
			$table['page']['content'] = $this->pdoTools->getChunk($this->config['getTableNavTpl'],['page' => $table['page']]);
		}
		 //$this->pdoTools->addTime("getTable generateData table['page'] ".print_r($table['page'],1));
		//echo "getTable generateData rows <pre>".print_r($rows,1)."</pre>";
		$output = array();
		$tr = $table['tbody']['tr'];
		//echo "getTable generateData tr <pre>".print_r($tr,1)."</pre>";
		foreach($rows as $k => $row){
			//echo "getTable generateData row <pre>".print_r($row,1)."</pre>";
			
			$r = $tr;
			$data = [];
			foreach($tr['data'] as $dv){
				
				$data[$dv] = $row[$dv];
			}
			$r['data'] = $data;
			//echo "getTable generateData r <pre>".print_r($r,1)."</pre>";
			foreach($r['tds'] as &$td){
				$td['value'] = $row[$td['as']];
				//$this->pdoTools->addTime("getTable generateData td ".print_r($td,1));
				if(isset($td['content'])){
					$td['content'] = $this->pdoTools->getChunk('@INLINE '.$td['content'], $row);
				}else{
					$td['content'] = $td['value'];
				}
				$td['cls'] = $this->pdoTools->getChunk('@INLINE '.$td['cls'], $row);
				if(!empty($table['autosave']) and !empty($td['edit'])){
					$edit = $td['edit'];
					$edit['value'] = $td['value'];
					$td['content'] = $this->pdoTools->getChunk($this->config['getTableEditRowTpl'],['edit'=>$edit]);
				}else{
					if($td['edit']['type'] == "checkbox"){
						if($td['value']){
							$td['content'] = "Да";
						}else{
							$td['content'] = "Нет";
						}
						
					}
				} 
			}
			$sub = ['cls'=>'hidden'];
			//echo "getTable generateData r <pre>".print_r($r,1)."</pre>";
			$output[] = $this->pdoTools->getChunk($this->config['getTableRowTpl'],['tr'=>$r,'sub'=>$sub]);
		}
		
		
		$table['tbody']['inner'] = implode("\r\n",$output);
		$this->getTables->addDebug($table['tbody']['inner'],'generateData $table[tbody][inner]');
		//echo "getTable generateData inner <pre>".print_r($table['tbody']['inner'],1)."</pre>";
		return $table;
	}
	public function refresh($action, $table, $data)
    {
		$table2 = $this->generateData($table);
		$this->getTables->addDebug($table,'refresh  table 2');
		$html = $table2['tbody']['inner'];
		return $this->success('',array('html'=>$html,'nav'=>$table2['page']['content']));
	}
	
	public function fetch($table = array())
    {
        
		if(!$this->config['isAjax']){
			$this->getStyleChunks();
		}
		//$this->getTables->addDebug($table,'fetch  $table');
		//$this->getTables->addDebug($this->config,'fetch  $this->config');
		//$this->pdoTools->addTime("getTable fetch table ".print_r($this->config,1));
		//echo "<pre>{ignore}".print_r($this->config,1)."{/ignore}</pre>";
		if(empty($table)){
			if(!empty($this->config['table'])) $table = $this->config['table'];
		}
		//$table['pdoTools']['return'] = 'data';
		//if(!empty($table['pdoTools'])) $this->pdoTools->setConfig($table['pdoTools']);
		
		if(is_string($table) and strpos(ltrim($table), '{') === 0) $table = json_decode($table, true);
		//$this->pdoTools->addTime("getTable fetch table ".print_r($table,1));
		if($table['row']){
			//$this->pdoTools->addTime("getTable fetch selects  {ignore}".print_r($this->config['selects'],1)."{/ignore}");
			if(isset($this->config['selects'])){
				if(empty($this->config['compile']) and $selects = $this->getTables->getClassCache('getSelect','all')){
					
				}else{
					$request = $this->getTables->handleRequest('getSelect/compile',$this->config['selects']);
					$selects = $request['data']['selects'];
				}
				$this->getTables->setClassConfig('getSelect','all', $selects);
				$this->config['selects'] = $selects;
				//$this->pdoTools->addTime("getTable fetch selects  {ignore}".print_r($this->config['selects'],1)."{/ignore}");
			}
			//if(empty($table['compile'])){
				if(empty($this->config['compile']) and $table_compile = $this->getTables->getClassCache('getTable',$table['name'])){
					
				}else{
					 
					if($table['class'] == 'TV') $table['class'] = 'modTemplateVarResource';
					$table['class'] = $table['class'] ? $table['class'] : 'modResource';
					$name = $table['name'] ? $table['name'] : $table['class'];
					
					$table['name'] = $this->getTables->getRegistryAppName('getTable',$name);
					
					//$this->pdoTools->addTime("getTable fetch table  name $name !".$table['name']);
					//$this->pdoTools->addTime("getTable fetch subtable  {ignore}".print_r($table['subtable'],1)."{/ignore}");
					if(!empty($table['subtable'])){
						if($table['subtable']['class'] == 'TV') $table['subtable']['class'] = 'modTemplateVarResource';
						$table['subtable']['class'] = $table['subtable']['class'] ? $table['subtable']['class'] : 'modResource';
						$name = $table['subtable']['name'] ? $table['subtable']['name'] : $table['subtable']['class'];
						
						$table['subtable']['name'] = $this->getTables->getRegistryAppName('getTable',$name);
						//$this->pdoTools->addTime("getTable fetch table subtable name $name !".$table['subtable']['name']);
					}
					$table_compile = $this->compile($table);
					//$this->pdoTools->addTime("getTable fetch table_compile   {ignore}".print_r($table_compile,1)."{/ignore}");
					$table_compile['pdoTools'] = $table['pdoTools'];
					//$table['compile']['pdoTools'] = $this->pdoTools->config;
					
					$this->getTables->setClassConfig('getTable',$table_compile['name'], $table_compile);
					
					if(!empty($table['subtable'])){
						$subtable_compile = $this->compile($table['subtable']);
						$subtable_compile['pdoClear'] = $table['subtable']['pdoTools'];
						$this->getTables->setClassConfig('getTable',$subtable_compile['name'], $subtable_compile);
					}
				//}
			}
			//echo "getTable table_compile table ".print_r($table_compile,1);
			$html = $this->pdoTools->getChunk($this->config['getTableOuterTpl'], $this->generateData($table_compile));
			
			//$this->pdoTools->addTime("getTable fetch table registryAppName  {ignore}".print_r($this->getTables->registryAppName,1)."{/ignore}");
			//if(!$this->config['isAjax']) $this->registerActionJS($table);
			
			return $this->success('',array('html'=>$html));
		}else{
			return $this->error("Нет конфига row!");
		}
    }
	public function addFilterTable($table)
    {
		$query = [
			//'return'=>'data',
		];
		/*
		//Это чтобы не искать
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
				$edit['where_field'] = '`'.$value['class'].'`.`'.$value['field'].'`';
				$edit['class'] = $value['class'];
				$edit['search_fields'] = [];
			}
		$tf = [
			'section' => 'topBar/topline/filters',
			'cls' => '',
			'edit'=>$edit
			//'content' => '<input type="text" name="'.$value['field'].'" value="{\''.$value['field'].'\' | placeholder}" class="form-control" placeholder="'.$th['name'].'">'
		];
		*/
		
		foreach($table['filters'] as $k=>&$filter){
			if(!empty($_REQUEST[$filter['edit']['field']]) or $_REQUEST[$filter['edit']['field']]==='0'){
				$filter['value'] = $_REQUEST[$filter['edit']['field']];
				if(isset($filter['where'])){
					if (strpos($filter['where'], 'LIKE') === false) {
						$query[$filter['where']] = $filter['value'];
					}else{
						$query[$filter['where']] = '%'.$filter['value'].'%';
					}
				}else{
					$query[$filter['edit']['where_field']] = $filter['value'];
				}
				
			}else{
				$filter['value'] = '';
			}
			$filter['content'] = '<input type="text" name="'.$filter['edit']['field'].'" value="'.$filter['value'].'" class="form-control" placeholder="'.$filter['edit']['field'].'">';
		}
		$query = array_merge($table['pdoTools']['where'],$query);
		//$this->getTables->addDebug($query,'addFilterTable  $query');
		$table['query'] = ['where'=>$query];
		//$this->getTables->addDebug($table['topBar'],'addFilterTable  $table[topBar] 1');
		foreach($table['filters'] as $f){
			$table['topBar'][$f['section']][] = $f;
		}
		//$this->getTables->addDebug($table['filters'],'addFilterTable  $table[filters]');
		//$this->getTables->addDebug($table['topBar'],'addFilterTable  $table[topBar] 2');
		if(isset($table['topBar']['topBar/topline/filters'])) $table['topBar']['topBar/topline/filters/search'] = array_pop($table['topBar']['topBar/topline/filters']);
		//$this->getTables->addDebug($table,'addFilterTable  $table');
		return $table;
	}
	
	
	public function compileActions($actions){
		$default_actions = [
			'create' =>[
				'action'=>'getTable/create',
				'title'=>'Создать',
				'cls' => '',
				//'icon' => 'icon icon-edit',
				'topBar' => [],
				//'multiple' => $this->modx->lexicon('modextra_items_update'),
				//'row' => [],
				'modal' => [
					'action' => 'getModal/fetchTableModal',
					'tpl'=>'getTableModalCreateUpdateTpl',
				],
				//'processors'=>['modResource'=>'resource/create'],
			],
			'update' =>[
				'action'=>'getTable/update',
				'title'=>'Изменить',
				'cls' => '',
				'icon' => 'glyphicon glyphicon-edit',
				//'topBar' => [],
				//'multiple' => $this->modx->lexicon('modextra_items_update'),
				'row' => [],
				'modal' => [
					'action' => 'getModal/fetchTableModal',
					'tpl'=>'getTableModalCreateUpdateTpl',
				],
				//'processors'=>['modResource'=>'resource/update'],
			],
			'remove' =>[
				'action'=>'getTable/remove',
				'title'=>'Удалить',
				'cls' => 'btn-danger',
				'icon' => 'glyphicon glyphicon-trash',
				//'topBar' => [],
				'multiple' => ['title'=>'Удалить выбранное'],
				'row' => [],
			],
			'toggle' =>[
				'action'=>"getTable/toggle",
				'title'=>['Включить','Выключить'],
				'multiple'=>['Включить','Выключить'],
				'cls' => ['btn-danger','btn-success'],
				'icon' => 'glyphicon glyphicon-off',
				/*[
					'enable' =>
					[
						'action'=>'getTable/enable',
						'title'=>'Включить',
						'cls' => 'btn-danger',
						'icon' => 'glyphicon glyphicon-off',
						//'topBar' => [],
						'multiple' => ['title'=>'Включить выбранное'],
					],
					'disable' =>[
						'action'=>'getTable/disable',
						'title'=>'Выключить',
						'cls' => 'btn-success',
						'icon' => 'glyphicon glyphicon-off',
						//'topBar' => [],
						'multiple' => ['title'=>'Выключить выбранное'],
					],
				],*/
				'field' => 'published',
				'row' => [],
			],
			'subtable' =>[
				'action'=>"getTable/subtable",
				'title'=>['Открыть','Закрыть'],
				//'multiple'=>['Включить','Выключить'],
				'cls' => ['get-sub-show ','get-sub-hide hidden'],//['btn-danger','btn-success'],
				'icon' => ['glyphicon glyphicon-eye-open','glyphicon glyphicon-eye-close'],
				//'field' => 'published',
				'row' => [],
				'where'=>['parent'=>'id'],
			],
			
		];
		//<button class="btn btn-danger"><i class="glyphicon glyphicon-download-alt"></i> Скачать</button>
		$compile_actions = [];
		if(empty($actions)){
			$compile_actions = $default_actions;
		}else{
			foreach($actions as $k=>$a){
				if(!isset($a['action'])){
					if(isset($default_actions[$k])){
						//Прописываем дефолтовое действие, например для  $actions['create'=>[]]
						$compile_actions[$k] = array_merge($default_actions[$k],$actions[$k]);
					}else{
						$this->pdoTools->addTime("Не определено действие $k =>".print_r($actions[$k],1));
					}
				}else{
					if(is_string($a['action'])){
						//Прописываем дефолтовое действие, например для  $actions['create'=>['action'=>'getTable/create',]]. Чтобы можно было задать много getTable/create 
						$ta = explode("/",$a['action']);
						if($ta[0] == "getTable"){
							if(isset($default_actions[$ta[1]])){
								$compile_actions[$k] = array_merge($default_actions[$ta[1]],$actions[$k]);
							}else{
								$this->pdoTools->addTime("В getTable нет действия {$ta[1]}. Действие  $k =>".print_r($actions[$k]));
							}
						}
					}
				}
			}
			
			//$compile_actions = $actions;
		}
		//$this->pdoTools->addTime("table compile_actions {ignore}".print_r($compile_actions,1)."{/ignore}");
		return $compile_actions;
	}
	public function compileTopBar($actions)
    {
		$topBar = [];
		foreach($actions as $a){
			if($a['topBar']){
				$buttons = [];
				foreach($a['topBar']['buttons'] as $arbk=>$arb){
					$str_data = "";
					foreach($arb['data'] as $arbdk=>$arbdv){
						$str_data .= ' data-'.$arbdk.'="'.$arbdv.'"';
					} 
					$buttons[] ='<button type = "button" class="btn get-table-'.$a['topBar']['bcls'].' '.$arb['cls'].'" '.$str_data.' title="'.$arb['title'].'"> '.$arb['html'].'</button>';
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
		foreach($table['defaultFieldSet'] as $df=>$dfv){
			if(is_array($dfv)){
				if(!isset($dfv['class'])) $dfv['class'] = $class;
				if($dfv['class'] = 'TV') $dfv['class'] = 'modTemplateVarResource';
				$defaultFieldSet[$df] = $dfv;
			}else{
				$defaultFieldSet[$df] = ['class'=>$class,'value'=>$dfv];
			}
		}
		//$this->pdoTools->addTime("table compile table[actions] {ignore}111".print_r($table,1)."{/ignore}");
		$actions = $table['actions'] ? $table['actions'] : [];
		
		$actions = $this->compileActions($actions);
		
		$actions_row = [];
		
		foreach($actions as $k=>&$a){
			if(!empty($a['permission'])){
				if (!$this->modx->hasPermission($a['permission'])){ unset($actions[$k]); continue;}
			}
			if(isset($a['modal'])) $modal[$a['action']] = $a['modal'];
			if($a['action'] == "getTable/subtable" and !empty($table['subtable']['name'])){
				if(empty($a['buttons'])){
					$html = [];
					$html[0] = $a['icon'][0] ? '<i class="'.$a['icon'][0].'"></i>' : $a['title'][0];
					$html[1] = $a['icon'][1] ? '<i class="'.$a['icon'][1].'"></i>' : $a['title'][1];
					$a['buttons'] = [
						'sub_show' =>[
							'cls' => $a['cls'][0],
							'html' => $html[0],
							'field'=> $a['field'],
							'data' =>[
								'name'=>'subtable',
								'action'=>$a['action'],
								'subtable_name'=> $table['subtable']['name'],
								'js_action'=>'sub_show'
							],
						],
						'sub_hide' =>[
							'cls' => $a['cls'][1],
							'field'=> $a['field'],
							'html' => $html[1],
							'data' =>[
								'name'=>'subtable',
								'action'=>$a['action'],
								'subtable_name'=> $table['subtable']['name'],
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
					$a['buttons'] = [
						'enable' =>[
							'cls' => $a['cls'][0],
							'html' => $html[0],
							'field'=> $a['field'],
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
				if(!isset($a['buttons'])){
					$html = '';
					$html = $a['icon'] ? '<i class="'.$a['icon'].'"></i>' : $a['title'];
					$html .= $a['text'] ? $a['text'] : '';
					$a['buttons'] = [
						$k =>[
							'cls' => $a['cls'],
							'html' => $html,
							'data' =>[
								'name'=>$k,
								'action'=>$a['action'],
							],
						],
					];
					if(isset($a['modal'])){
						$a['buttons'][$k]['data']['modal'] = $a['modal']['action'];
					}
					if(isset($a['row'])) $a['row']['buttons'] = $a['buttons'];
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
			if(isset($a['row'])) $actions_row[$k] = [
				'buttons'=> $a['row']['buttons'],
			];
		}
		if(isset($table['checkbox'])){
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
		$data = [];
		$edits = [];
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
			//$td['value'] = '{$tr.'.$value['field'].'}';
			//$td['content'] = $value['content'] ? $value['content'] : '{$'.$value['field'].'}';
			if($value['content']) $td['content'] = $value['content'];
			if(empty($value['as'])){
				$td['as'] = $td['field'];
			}else{
				$td['as'] = $value['as'];
			}
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
				$edit['where_field'] = '`'.$value['class'].'`.`'.$value['field'].'`';
				$edit['class'] = $value['class'];
				$edit['search_fields'] = [];
			}
			
			
			if($value['field'] == 'id') $edit['type'] = 'hidden';
			if(is_array($value['edit'])) $edit = array_merge($edit,$value['edit']);
			
			//$this->pdoTools->addTime("getTable fetch table_compile selects  {ignore}".print_r($this->config['selects'][$edit['select']],1)."{/ignore}");
			if(isset($edit['select']) and $this->config['selects'][$edit['select']]){
				$edit['type'] = 'select';
				$edit['select'] = $this->config['selects'][$edit['select']];
			}
			if(isset($value['edit']) and $value['edit'] == false ){
				
			}else{
				$td['edit'] = $edit;
				$edits[] = $edit;
			}
			
			if(isset($value['filter'])){
				
				$tf = [
					'section' => 'topBar/topline/filters',
					'cls' => '',
					'edit'=>$edit
					//'content' => '<input type="text" name="'.$value['field'].'" value="{\''.$value['field'].'\' | placeholder}" class="form-control" placeholder="'.$th['name'].'">'
				];
				if(is_string($value['filter'])){
					$tf['where'] = $value['filter'];
				}
				if(is_array($value['filter'])){
					$tf = array_merge($tf,$value['filter']);
				}
				$filters[] = $tf;
			}
			if(isset($value['data'])) $data[] = $value['field'];
			
			$ths[] = $th;
			$tds[] = $td;
		}
		
		if(is_string($body_tr['data']))
			$body_tr['data'] = explode(",",$body_tr['data']);
		if(empty($body_tr['data'])) $body_tr['data'] = [];
		$body_tr['data'] = array_merge($body_tr['data'],$data);
		if(empty($body_tr['data'])) $body_tr['data'] = ['id'];
		
		if(!empty($actions_row)){
			//собираем кнопки
			$buttons = [];
			foreach($actions_row as $ak=>$ar){
				
				if($ak == 'subtable'){
					$buttons_toggle = [];
					$field = '';
					foreach($ar['buttons'] as $arbk=>$arb){
						$str_data = "";
						foreach($arb['data'] as $arbdk=>$arbdv){
							$str_data .= ' data-'.$arbdk.'="'.$arbdv.'"';
						}
						$field = $arb['field'];
						$buttons_toggle[$arbk] ='<button type = "button" class="btn get-table-row '.$arb['cls'].'" '.$str_data.' title="'.$arb['title'].'"> '.$arb['html'].'</button>';
					}
					$buttons[] = $buttons_toggle['sub_show'].' '.$buttons_toggle['sub_hide'];
				}else if($ak == 'toggle'){
					$buttons_toggle = [];
					$field = '';
					foreach($ar['buttons'] as $arbk=>$arb){
						$str_data = "";
						foreach($arb['data'] as $arbdk=>$arbdv){
							$str_data .= ' data-'.$arbdk.'="'.$arbdv.'"';
						}
						$field = $arb['field'];
						$buttons_toggle[$arbk] ='<button type = "button" class="btn get-table-row '.$arb['cls'].'" '.$str_data.' title="'.$arb['title'].'"> '.$arb['html'].'</button>';
					}
					$buttons[] = '{if !$'.$field.'}'.$buttons_toggle['enable'].'{else}'.$buttons_toggle['disable'].'{/if}';
				}else{
					foreach($ar['buttons'] as $arbk=>$arb){
						$str_data = "";
						foreach($arb['data'] as $arbdk=>$arbdv){
							$str_data .= ' data-'.$arbdk.'="'.$arbdv.'"';
						} 
						$buttons[] ='<button type = "button" class="btn get-table-row '.$arb['cls'].'" '.$str_data.' title="'.$arb['title'].'"> '.$arb['html'].'</button>';
					}
				}
			}
			 //text-right
			$ths[] = ['cls'=>'text-right','name'=>'actions','content'=> "Действия"];
			$tds[] = ['cls'=>'text-right','name'=>'actions','content'=> '<div class="row">'.implode('&nbsp;',$buttons)."</div>"];
		}
		//$this->pdoTools->addTime("getTable compile filters {ignore}".print_r($filters,1)."{/ignore}");
		
		$topBar = $this->compileTopBar($actions);
		//$this->pdoTools->addTime("getTable compileTopBar topBar {ignore}".print_r($topBar,1)."{/ignore}");
		if(!isset($table['checkbox'])) unset($topBar['topBar/topline/multiple']);
		$topBar['hash'] = $this->config['hash'];
		$topBar['table_name'] = $name;
		
		$thead_tr['ths'] = $ths;
		$body_tr['tds'] = $tds;
		
		$table_compile = [
			'name'=>$name,
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
		];
		if(!empty($table['autosave'])) $table_compile['autosave'] = $table['autosave'];
		return $table_compile;
    }
	
	public function getStyleChunks()
    {
		if($this->config['frontend_framework_style'] != 'bootstrap_v3'){
			if($propSet = $this->modx->getObject('modPropertySet',array('name'=>'getTables_'.$this->config['frontend_framework_style']))){
				if($this->config['getTableOuterTpl'] == 'getTable.outer.tpl'){
					if($chunk = $this->modx->getObject('modChunk', array('name' => $propSet->getTableOuterTpl))){
						$this->config['getTableOuterTpl'] = $propSet->getTableOuterTpl;
					}
				}
				if($this->config['getTableRowTpl'] == 'getTable.outer.tpl'){
					if($chunk = $this->modx->getObject('modChunk', array('name' => $propSet->getTableRowTpl))){
						$this->config['getTableRowTpl'] = $propSet->getTableRowTpl;
					}
				}
				if($this->config['getTableModalCreateUpdateTpl'] == 'getTable.Modal.CreateUpdate.tpl'){
					if($chunk = $this->modx->getObject('modChunk', array('name' => $propSet->getTableModalCreateUpdateTpl))){
						$this->config['getTableModalCreateUpdateTpl'] = $propSet->getTableModalCreateUpdateTpl;
					}
				}
			}
		}
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