<?php
/** @var xPDOTransport $transport */
/** @var array $options */
/** @var modX $modx */
if ($transport->xpdo) {
    $modx =& $transport->xpdo;

    switch ($options[xPDOTransport::PACKAGE_ACTION]) {
        case xPDOTransport::ACTION_INSTALL:
        case xPDOTransport::ACTION_UPGRADE:
            if(!$Event = $modx->getObject('modEvent',array('name'=>'getTablesAfterUpdateCreate'))){
				$Event = $modx->newObject('modEvent');
				$Event->set('name', 'getTablesAfterUpdateCreate');
				$Event->set('service',1); 
				$Event->set('groupname', 'getTables');
				$Event->save();
			}
			if(!$Event = $modx->getObject('modEvent',array('name'=>'getTablesBeforeUpdateCreate'))){
				$Event = $modx->newObject('modEvent');
				$Event->set('name', 'getTablesBeforeUpdateCreate');
				$Event->set('service',1); 
				$Event->set('groupname', 'getTables');
				$Event->save();
			}
			if(!$Event = $modx->getObject('modEvent',array('name'=>'getTablesBeforeRemove'))){
				$Event = $modx->newObject('modEvent');
				$Event->set('name', 'getTablesBeforeRemove');
				$Event->set('service',1); 
				$Event->set('groupname', 'getTables');
				$Event->save();
			}
			if(!$Event = $modx->getObject('modEvent',array('name'=>'getTablesAfterRemove'))){
				$Event = $modx->newObject('modEvent');
				$Event->set('name', 'getTablesAfterRemove');
				$Event->set('service',1); 
				$Event->set('groupname', 'getTables');
				$Event->save();
			}
			break;
        case xPDOTransport::ACTION_UNINSTALL:
            $events = $modx->getIterator('modEvent', array('groupname'=>'getTables'));
			foreach($events as $e){
				$e->remove();
			}
			break;
    }
}
return true;
