<?php
/** @var xPDOTransport $transport */
/** @var array $options */
/** @var modX $modx */
if ($transport->xpdo) {
	$modx =& $transport->xpdo;

	$dev = MODX_BASE_PATH . 'Extras/getTables/';
	/** @var xPDOCacheManager $cache */
	$cache = $modx->getCacheManager();
	if (file_exists($dev) && $cache) {
		if (!is_link($dev . 'assets/components/gettables')) {
			$cache->deleteTree(
				$dev . 'assets/components/gettables/',
				['deleteTop' => true, 'skipDirs' => false, 'extensions' => []]
			);
			symlink(MODX_ASSETS_PATH . 'components/gettables/', $dev . 'assets/components/gettables');
		}
		if (!is_link($dev . 'core/components/gettables')) {
			$cache->deleteTree(
				$dev . 'core/components/gettables/',
				['deleteTop' => true, 'skipDirs' => false, 'extensions' => []]
			);
			symlink(MODX_CORE_PATH . 'components/gettables/', $dev . 'core/components/gettables');
		}
	}
}

return true;