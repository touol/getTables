<?php
/** @var modX $modx */
/** @var array $scriptProperties */
/** @var getTables $getTables */
//$getTables = $modx->getService('getTables', 'getTables', MODX_CORE_PATH . 'components/gettables/model/', $scriptProperties);

if (!$modx->loadClass('gettables', MODX_CORE_PATH . 'components/gettables/model/', false, true)) {
	return 'Could not load getTables class!';
}
//echo "<pre>".print_r($scriptProperties,1)."<pre>";

$getTables = new getTables($modx, $scriptProperties);
if (!$getTables) {
	return 'Could not load getTables class!';
}

$getTables->pdoTools->addTime('getTables loaded.');
$getTables->initFromCache();
$getTables->pdoTools->addTime('getTables init from cache.');

$response = $getTables->handleRequest('getTable/fetch');

if(!$response['success']){
	$output = $response['message'];
}else{
	$output = $response['data']['html'];
}

$log = '';
if ($modx->user->hasSessionContext('mgr') && !empty($showLog)) {
	$log .= $response['log'];
}
return $output.$log;