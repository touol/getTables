<?php
if (empty($_REQUEST['action']) and empty($_REQUEST['gts_action'])) {
    $message = 'Access denied action.php';
    echo json_encode(
            ['success' => false,
            'message' => $message,]
            );
    return;
}


//решение проблеммы с modx->cacheManager на beget.com
/*define('MODX_API_MODE', true);
require dirname(dirname(dirname(dirname(__FILE__)))) . '/index.php';*/
require_once dirname(dirname(dirname(dirname(__FILE__)))).'/config.core.php';
require_once MODX_CORE_PATH.'config/'.MODX_CONFIG_KEY.'.inc.php';
require_once MODX_CONNECTORS_PATH.'index.php';

$_REQUEST['action'] = $_REQUEST['action'] ? $_REQUEST['action'] : $_REQUEST['gts_action'];

$gettables_core_path = $modx->getOption('gettables_core_path',null, MODX_CORE_PATH . 'components/gettables/core/');
$gettables_core_path = str_replace('[[+core_path]]', MODX_CORE_PATH, $gettables_core_path);
if (!$modx->loadClass('gettables', $gettables_core_path, false, true)) {
	$message =  'Could not load getTables class!';
	echo json_encode(
		['success' => false,
		'message' => $message,]
		);
	return;
}

$getTables = new getTables($modx, []);
if (!$getTables) {
    $message =  'Could not create getTables!';
	echo json_encode(
		['success' => false,
		'message' => $message,]
		);
	return;
}

$modx->lexicon->load('gettables:default');

if(!empty($_REQUEST['hash'])){
	if(!$getTables->loadFromCache($_REQUEST['hash'])){
		$message =  'Not loadFromCache!';
		echo json_encode(
			['success' => false,
			'message' => $message,]
			);
		return;
	}
}else{
	$message =  'Not hash!';
	echo json_encode(
		['success' => false,
		'message' => $message,]
		);
	return;
}
$response = $getTables->handleRequest($_REQUEST['action'],$_REQUEST);

echo $response;