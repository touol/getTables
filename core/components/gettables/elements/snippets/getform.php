<?php
/** @var modX $modx */
/** @var array $scriptProperties */
/** @var getTables $getTables */
//$getTables = $modx->getService('getTables', 'getTables', MODX_CORE_PATH . 'components/gettables/model/', $scriptProperties);

$gettables_core_path = $modx->getOption('gettables_core_path',null, MODX_CORE_PATH . 'components/gettables/core/');
$gettables_core_path = str_replace('[[+core_path]]', MODX_CORE_PATH, $gettables_core_path);
if (!$modx->loadClass('gettables', $gettables_core_path, false, true)) {
    return 'Could not load getTables class!';
}
//echo "<pre>".print_r($scriptProperties,1)."<pre>";

$getTables = new getTables($modx, $scriptProperties);
if (!$getTables) {
    return 'Could not load getTables class!';
}

$getTables->pdoTools->addTime('getTables loaded.');
$getTables->initialize();


$response = $getTables->handleRequest('getForm/fetch');

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