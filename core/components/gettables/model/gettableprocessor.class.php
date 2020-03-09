<?php

class getTableProcessor
{
    public $modx;
	/** @var pdoFetch $pdoTools */
    public $pdoTools;
	
	public $getTables;
	public $getTable;
	public $debug = [];
	/**
     * @param modX $modx
     * @param array $config
     */
    function __construct(getTable & $getTable, array $config = [])
    {
        $this->getTable =& $getTable;
		$this->getTables =& $this->getTable->getTables;
		$this->modx =& $this->getTables->modx;
		$this->pdoTools =& $this->getTables->pdoTools;
		
		$this->config = array_merge([
			
		], $config);
		
    }
	
    public function run($action, $table, $data = array())
    {
		
		if(!isset($table['actions'][$action]) and $action !="autosave") return $this->error("Action $action не найдено! ",$table);
		$this->current_action = $table['actions'][$action];
		
		$edit_tables = [];
		foreach($table['edits'] as $edit){
			$edit_tables[$edit['class']][] = $edit;
		}
		
		//beforeSave callback
		/*foreach($edit_tables as $k=>$edits){
			if(isset($current_action['callbacks'][$k])){
				$datas = [
					'action'=>$current_action,
					'data'=>$data,
					'edits'=>$edits,
				];
				$response =  $service->handleRequest($current_action['callbacks'][$k], $datas);
				if($response['data']['break']) return $response;
			}
		}*/
		
		switch($action){
			case 'update':
				$response = $this->update($table, $edit_tables, $data);
				break;
			case 'create':
				$response = $this->update($table, $edit_tables, $data, true);
				break;
			case 'toggle':
				$response = $this->sets($table, $edit_tables, $data);
				break;
			case 'set':
				$response = $this->sets($table, $edit_tables, $data);
				break;
			case 'remove':
				$response = $this->remove($table, $edit_tables, $data);
				break;
			case 'autosave':
				$response = $this->autosave($table, $edit_tables, $data);
				break;
			default:
				$response = $this->error("Action $action не найдено! ",$table);
				break;
		}
		if($action != 'remove' and count($data['trs_data']) == 1){
			$pdoConfig = $table['pdoTools'];
			$pdoConfig['limit'] = 1;
			$pdoConfig['setTotal'] = false;
			foreach($data['trs_data'][0] as $field=>$value){
				$pdoConfig['where'][$field] = $value;
			}
			$response['row'] = $this->getTable->generateData($table,$pdoConfig);
		}
		if(!empty($table['parent_table_name']) and !empty($data['parent_tr_data'])){
			if(!$parent = $getTables->getClassCache('getTable',$table['parent_table_name'])){
				$pdoConfig = $parent['pdoTools'];
				$pdoConfig['limit'] = 1;
				$pdoConfig['setTotal'] = false;
				foreach($data['parent_tr_data'] as $field=>$value){
					$pdoConfig['where'][$field] = $value;
				}
				$response['parent_row'] = $this->getTable->generateData($parent,$pdoConfig);
			}
		}
		//afterSave callback
		
		return $response;
    }
	public function autosave($table, $edit_tables, $data = array())
    {
		if(empty($data['tr_data'])) return $this->error('tr_data пусто');

		if(!(int)$data['tr_data']['id']){
			return $this->error('$tr_data[id] пусто'); continue;
		}
		$set_data['id'] = (int)$data['tr_data']['id'];
		$set_data[$data['td']['field']] = $data['td']['value'];
		return $this->update($table, $edit_tables, $set_data);
	}
	public function sets($table, $edit_tables, $data = array())
    {
		
		$saved = [];
		if(empty($data['trs_data'])) return $this->error('trs_data пусто');
		foreach($data['trs_data'] as $tr_data){
			if(!(int)$tr_data['id']){
				$saved[] = $this->error('$tr_data[id] пусто'); continue;
			}
			$set_data['id'] = (int)$tr_data['id'];
			$value = 0;
			if(isset($data['button_data']['toggle'])){
				if($data['button_data']['toggle'] == 'enable') $value = 1;
			}
			$set_data[$this->current_action['field']] = $value;
			$saved[] = $this->update($table, $edit_tables, $set_data);
		}
		
		$error = '';
		foreach($saved as $s){
			if(!$s['success']) $error = "Object {$s['class']} {$s['field']} не сохранен \r\n";
		}
		if(!$error){
			return $this->success('Сохранено успешно',$saved);
		}else{
			return $this->error($error,$saved);
		}
	}
	
	public function remove($table, $edit_tables, $data = array())
    {
		$saved = [];
		if(empty($data['trs_data'])) return $this->error('trs_data пусто');
		foreach($data['trs_data'] as $tr_data){
			if(!(int)$tr_data['id']){
				$saved[] = $this->error('$tr_data[id] пусто'); continue;
			}
			if(!$this->checkUpdateAccess((int)$data['id'],$table)){
				$saved[] = $this->error('Объект не найден или редактирование запрещено');
			}else{
				if(!$obj = $this->modx->getObject($table['class'],(int)$tr_data['id'])){
					$saved[] = $this->error('Объект не найден');
				}
				if($obj->remove()) $saved[] = $this->success('Удалено успешно',$saved);
			}
		}
		
		$error = '';
		foreach($saved as $s){
			if(!$s['success']) $error = "Удаление запрещено или возникла ошибка \r\n";
		}
		if(!$error){
			return $this->success('Удалено успешно',$saved);
		}else{
			return $this->error($error,$saved);
		}
	}
	public function checkUpdateAccess($id,$table)
    {
		//$this->getTables->addDebug($table['pdoTools'],'checkUpdateAccess $table[pdoTools] ');
		$pdoConfig = $table['pdoTools'];
		//$this->getTables->addDebug($table['pdoTools'],'$table[pdoTools] ');
		$pdoConfig['limit'] = 1;
		$pdoConfig['return'] = 'ids';
		$pdoConfig['where']['id'] = $id;
		
		$this->pdoTools->config = array_merge($this->config['pdoClear'],$pdoConfig);
		
		$ids = $this->pdoTools->run();
		//$this->getTables->addDebug($this->pdoTools->config,$ids.' checkUpdateAccess $this->pdoTools->config ');
		if($ids == $id) return true;
		return false;		
	}
	
	public function update($table, $edit_tables, $data = array(), $create = false)
    {
		$saved = [];
		if($create){
			
		}else{
			$this->getTables->addDebug($data,'update data');
			$this->getTables->addDebug($table,'update $table');
			if(!$this->checkUpdateAccess((int)$data['id'],$table)) return $this->error('Объект не найден или редактирование запрещено');	
		}
		
		
		$class = $table['class'];
		if($edit_tables[$class]){
			$set_data = [];
			foreach($edit_tables[$class] as $edit){
				if($data[$edit['field']] !==null)
					$set_data[$edit['field']] = $data[$edit['field']];
			}
			foreach($table['defaultFieldSet'] as $df=>$dfv){
				if($dfv['class'] == $class)
					$set_data[$df] = $dfv['value'];
			}
			if(isset($this->current_action['processors'][$class])){
				if(empty($set_data['context_key'])) $set_data['context_key'] = 'web';
				//$saved[] = $this->error('runProcessor ',$set_data);
				$modx_response = $this->modx->runProcessor($this->current_action['processors'][$class], $set_data);
				if ($modx_response->isError()) {
					$saved[] = $this->error('runProcessor ',$this->modx->error->failure($modx_response->getMessage()));
					$data['id'] = false;
				}else{
					$saved[] = $this->success('runProcessor ',$modx_response->response);
					$data['id'] = $modx_response->response['object']['id'];
				}
			}else{
				$saveobj = ['success'=>false,'class'=>$class];
				//$saved[] = $data;
				if($create){
					$obj = $this->modx->newObject($class);
					$data['id'] = false;
				}else{
					$obj = $this->modx->getObject($class,(int)$data['id']);
				}
				if($obj){
					//$saved[] = $obj->toArray();
					$obj->fromArray($set_data);
					$saved[] = $this->success('Сохранено успешно',$set_data);
					if($obj->save()){
						$saveobj['success'] = true;
						$data['id'] = $obj->id;
					}
				}
				$saved[] = $saveobj;
			}
			unset($edit_tables[$class]);
		}
		if($create and !$data['id']) return $this->error("Не удалось создать объект $class",$saved);
		
		foreach($edit_tables as $class=>$edits){
			foreach($edits as $edit){
				$saveobj = ['success'=>false,'class'=>$class,'field'=>$edit['field']];
				if(!empty($edit['search_fields'])){
					
					foreach($edit['search_fields'] as $k=>&$v){
						if($v == 'id') $v = (int)$data['id'];
					}
					
					if(!$obj2 = $this->modx->getObject($class,$edit['search_fields'])){
						$obj2 = $this->modx->newObject($class,$edit['search_fields']);
					}
					if($obj2){
						if(isset($table['defaultFieldSet'][$edit['field']])){
							$data[$edit['field']] == $table['defaultFieldSet'][$edit['field']];
						}
						$obj2->{$edit['value_field']} = $data[$edit['field']];
						
						/*foreach($table['defaultFieldSet'] as $df=>$dfv){
							if($dfv['class'] == $class and $df == )
								$obj2->{$df} = $dfv['value'];;
						}*/
						if($obj2->save()) $saveobj['success'] = true;
					}
				}
				$saved[] = $saveobj;
			}
		}
		$error = '';
		foreach($saved as $s){
			if(!$s['success']) $error = "Object {$s['class']} {$s['field']} не сохранен \r\n";
		}
		if(!$error){
			return $this->success('Сохранено успешно',$saved);
		}else{
			return $this->error($error,$saved);
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