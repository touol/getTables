<?php
if (empty($_REQUEST['action']) and empty($_REQUEST['gts_action'])) {
    $message = 'Access denied action.php';
	echo json_encode(
			['success' => false,
            'message' => $message,]
			);
	return;
}



define('MODX_API_MODE', true);
require dirname(dirname(dirname(dirname(__FILE__)))) . '/index.php';

$_REQUEST['action'] = $_REQUEST['action'] ? $_REQUEST['action'] : $_REQUEST['gts_action'];

$getTables = $modx->getService('getTables', 'getTables', MODX_CORE_PATH . 'components/gettables/model/');
$modx->lexicon->load('gettables:default');

if(!empty($_REQUEST['hash'])) $getTables->loadFromCache($_REQUEST['hash']);

$response = $getTables->handleRequest($_REQUEST['action'],$_REQUEST);

echo $response;