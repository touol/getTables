<?php
if (empty($_REQUEST['action']) and empty($_REQUEST['gts_action'])) {
    $message = 'Access denied action.php';
    echo json_encode(
            ['success' => false,
            'message' => $message,]
            );
    return;
}


//решение проблеммы с modx->cacheManager на beget.com. Но как оказалось запрещает доступ не админам.
define('MODX_API_MODE', true);
//require dirname(dirname(dirname(dirname(__FILE__)))) . '/index.php';
if (file_exists(dirname(dirname(dirname(dirname(__FILE__)))) . '/config.core.php')) {
    /** @noinspection PhpIncludeInspection */
    require_once dirname(dirname(dirname(dirname(__FILE__)))) . '/config.core.php';
} else {
    require_once dirname(dirname(dirname(dirname(dirname(__FILE__))))) . '/config.core.php';
}
$tstart= microtime(true);

/* include the modX class */
if (!@include_once (MODX_CORE_PATH . "model/modx/modx.class.php")) {
    $errorMessage = 'Site temporarily unavailable';
    @include(MODX_CORE_PATH . 'error/unavailable.include.php');
    header($_SERVER['SERVER_PROTOCOL'] . ' 503 Service Unavailable');
    echo "<html><title>Error 503: Site temporarily unavailable</title><body><h1>Error 503</h1><p>{$errorMessage}</p></body></html>";
    exit();
}

/* start output buffering */
ob_start();

/* Create an instance of the modX class */
$modx= new modX();
if (!is_object($modx) || !($modx instanceof modX)) {
    ob_get_level() && @ob_end_flush();
    $errorMessage = '<a href="setup/">MODX not installed. Install now?</a>';
    @include(MODX_CORE_PATH . 'error/unavailable.include.php');
    header($_SERVER['SERVER_PROTOCOL'] . ' 503 Service Unavailable');
    echo "<html><title>Error 503: Site temporarily unavailable</title><body><h1>Error 503</h1><p>{$errorMessage}</p></body></html>";
    exit();
}

/* Set the actual start time */
$modx->startTime= $tstart;

$ctx = isset($_REQUEST['ctx']) && !empty($_REQUEST['ctx']) && is_string($_REQUEST['ctx']) ? $_REQUEST['ctx'] : 'mgr';
$modx->initialize($ctx);

/*require_once dirname(dirname(dirname(dirname(__FILE__)))).'/config.core.php';
require_once MODX_CORE_PATH.'config/'.MODX_CONFIG_KEY.'.inc.php';
require_once MODX_CONNECTORS_PATH.'index.php';*/

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

echo json_encode($response);
exit;