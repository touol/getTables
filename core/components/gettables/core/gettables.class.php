<?php

class getTables
{
    public $version = '1.7.1';
    /** @var modX $modx */
    public $modx;
    /** @var pdoFetch $pdoTools */
    public $pdoTools;
    
    public $gtsPro;

    public $models;
    
    public $debugs = [];
    
    public $config = array();
    
    public $registryAppName = [];
    public $REQUEST = [];

    public $selects_compile = false;

    public $timings = [];
    protected $start = 0;
    public $PHPExcelSheet = null;
    /**
     * @param modX $modx
     * @param array $config
     */
    function __construct(modX &$modx, array $config = [])
    {
        $this->modx =& $modx;
        $corePath = MODX_CORE_PATH . 'components/gettables/';
        $assetsUrl = MODX_ASSETS_URL . 'components/gettables/';
        
        
        $this->config = array_merge([
            'corePath' => $corePath,
            'modelPath' => $corePath . 'model/',
            'processorsPath' => $corePath . 'processors/',
            'connectorUrl' => $assetsUrl . 'connector.php',
            'actionUrl' => $assetsUrl . 'action.php',
            'assetsUrl' => $assetsUrl,
            'cssUrl' => $assetsUrl . 'css/',
            'jsUrl' => $assetsUrl . 'js/',
            'ctx' => $this->modx->context->key,
            'frontend_framework_style' => $this->modx->getOption('gettables_frontend_framework_style',null,'bootstrap_v3'),
            'date_format' => $this->modx->getOption('gettables_date_format','','Y-m-d'),
            'datetime_format' => $this->modx->getOption('gettables_datetime_format','','Y-m-d H:i'),

        ], $config);
        if($this->config['ctx'] == 'mgr') $this->config['frontend_framework_style'] = $this->modx->getOption('gettables_mgr_framework_style',null,'bootstrap_v3');
        $this->models['getTabs']['class'] = 'gettabs.class.php';
        $this->models['getTable']['class'] = 'gettable.class.php';
        $this->models['getModal']['class'] = 'getmodal.class.php';
        $this->models['getSelect']['class'] = 'getselect.class.php';
        $this->models['getForm']['class'] = 'getform.class.php';
        $this->models['getTree']['class'] = 'gettree.class.php';

        //$this->modx->addPackage('gettables', $this->config['modelPath']);
        $this->modx->lexicon->load('gettables:default');
        
        $this->timings = [];
        $this->time = $this->start = microtime(true);
        //загрузка конфига
        if(is_dir(MODX_CORE_PATH . 'components/gettablespro/model/')){
            if($this->gtsPro = $this->modx->getService('getTablesPro', 'getTablesPro', MODX_CORE_PATH . 'components/gettablespro/model/')){
                if(!empty($this->config['config'])){
                    if($gtsPConfig = $this->modx->getObject('gtsPConfig',['name'=>$this->config['config']])){
                        $config_set = json_decode($gtsPConfig->config,1);
                    }
                }
            }
        }
        //$this->addTime("getTables {$this->config['config']}".print_r($config,1));
        if(!empty($this->config['config'])){
            if(!is_array($config_set)){
                $config_set = json_decode($this->modx->getOption($this->config['config']),1);
            } 
            
            if(is_array($config_set)){
                //$this->addTime("getTables {$this->config['config']} ".print_r($config_set,1));
                unset($this->config['config']);
                $this->config = array_merge($config_set, $this->config);
                $config = array_merge($config_set, $config);
            }else{
                $this->addTime("getTables. Не удалось распознать конфиг {$this->config['config']} ");
            }
        }
        
        if (isset($this->modx->event->returnedValues)) {
            $this->modx->event->returnedValues = null;
        }
        $getTablesLoadGTSConfig = $this->modx->invokeEvent('getTablesLoadGTSConfig', [
            'config'=>$this->config,
            'getTables'=>$this,
        ]);
        if (isset($this->modx->event->returnedValues) && is_array($this->modx->event->returnedValues)) {
            if(isset($this->modx->event->returnedValues['config']))
                $this->config = $this->modx->event->returnedValues['config'];
        }
        //$this->addTime("getTables ".print_r($this->config,1));

        if ($this->pdoTools = $this->modx->getService('pdoFetch')) {
            if(isset($this->config['pdoTools'])){
                $this->pdoTools->setConfig($this->config['pdoTools']);
            }else{
                $pdoConfig = ['return'=>'data','limit'=>60];
                if (!empty($this->config['loadModels'])) {
                    $pdoConfig['loadModels'] = $this->config['loadModels'];
                    $pdoConfig['decodeJSON'] = false;
                }
                $this->pdoTools->setConfig($pdoConfig);
                
            }
            $this->config['pdoClear'] = $pdoConfig;
        }
        $this->addTime('__construct pdoTools');
        $this->getModels();
        $this->addTime('__construct getModels');

        $this->config['hash'] = sha1(json_encode($this->config));
        if(!empty($config['toJSON'])){
            unset($config['toJSON']);
            $this->addTime('toJSON '.json_encode($config,JSON_PRETTY_PRINT));
        }
        if(!empty($config['toFenom'])){
            unset($config['toFenom']);
            $this->addTime('toFenom '.$this->varexport($config,1));
        }
        $this->addTime('__construct');
    }
    
    public function get_PHPExcelSheet(){
        if(!$this->PHPExcelSheet){
            if (!class_exists('PHPExcel')) {
                $PHPExcelPath = MODX_CORE_PATH.'components/gettables/vendor/PHPOffice/';
                require_once $PHPExcelPath . 'PHPExcel.php';
                
            }
            $xls = new PHPExcel();
            //$locale = 'ru';
            //$validLocale = PHPExcel_Settings::setLocale($locale);
            $xls->setActiveSheetIndex(0);
            $this->PHPExcelSheet = $xls->getActiveSheet();
            $this->PHPExcelSheet->setTitle('Лист1');
        }
    }

    public function calc_excel_formula($formula){
        if(substr($formula, 0, 2) == '==') $formula = substr($formula, 1);
        if(!$this->PHPExcelSheet) $this->get_PHPExcelSheet();
        $sum = PHPExcel_Calculation::getInstance(
            $this->PHPExcelSheet->getParent()
        )->calculateFormula($formula, 'A1', $this->PHPExcelSheet->getCell('A1'));
        return $sum;
    }
    /**
     * Add new record to time log
     *
     * @param $message
     * @param null $delta
     */
    public function addTime($message, $delta = null)
    {
        $time = microtime(true);
        if (!$delta) {
            $delta = $time - $this->time;
        }

        $this->timings[] = array(
            'time' => number_format(round(($delta), 7), 7),
            'message' => $message,
        );
        $this->time = $time;
    }
    /**
     * Return timings log
     *
     * @param bool $string Return array or formatted string
     *
     * @return array|string
     */
    public function getTime($string = true)
    {
        $this->timings[] = array(
            'time' => number_format(round(microtime(true) - $this->start, 7), 7),
            'message' => '<b>Total time</b>',
        );
        $this->timings[] = array(
            'time' => number_format(round((memory_get_usage(true)), 2), 0, ',', ' '),
            'message' => '<b>Memory usage</b>',
        );

        if (!$string) {
            return $this->timings;
        } else {
            $res = '';
            foreach ($this->timings as $v) {
                $res .= $v['time'] . ': ' . $v['message'] . "\n";
            }

            return $res;
        }
    }
    public function varexport($expression, $return=FALSE) {
        $export = var_export($expression, TRUE);
        $export = preg_replace("/^([ ]*)(.*)/m", '$1$1$2', $export);
        $array = preg_split("/\r\n|\n|\r/", $export);
        $array = preg_replace(["/\s*array\s\($/", "/\)(,)?$/", "/\s=>\s$/"], [NULL, ']$1', ' => ['], $array);
        $export = join(PHP_EOL, array_filter(["["] + $array));
        if ((bool)$return) return $export; else echo $export;
    }

    public function addDebug($debug = [],$mes = '')
    {
        if($this->config['debug']) $this->debugs[] = ['mes'=>$mes,'debug'=>$debug];
    }
    public function getModels()
    {
        
        
        if (empty($this->config['loadModels'])) {
            return;
        }
        
        
        $time = microtime(true);
        $models = array();
        if (strpos(ltrim($this->config['loadModels']), '{') === 0) {
            $tmp = json_decode($this->config['loadModels'], true);
            foreach ($tmp as $k => $v) {
                if (!is_array($v)) {
                    $v = array(
                        'path' => trim($v),
                    );
                }
                $v = array_merge(array(
                    'path' => MODX_CORE_PATH . 'components/' . strtolower($k) . '/model/',
                    'prefix' => null,
                ), $v);
                if (strpos($v['path'], MODX_CORE_PATH) === false) {
                    $v['path'] = MODX_CORE_PATH . ltrim($v['path'], '/');
                }
                $models[$k] = $v;
            }
        } else {
            $tmp = array_map('trim', explode(',', $this->config['loadModels']));
            foreach ($tmp as $v) {
                $parts = explode(':', $v, 2);
                $models[$parts[0]] = array(
                    'path' => MODX_CORE_PATH . 'components/' . strtolower($parts[0]) . '/model/',
                    'prefix' => count($parts) > 1 ? $parts[1] : null,
                );
            }
        }
        $this->models = array_merge($this->models,$models);

        //Загрузка тригеров
        if(!$this->config['isAjax']){
            $this->config['triggers'] = [];
            foreach($models as $name =>$v){
                $response = $this->loadService($name);
                
                if(is_array($response) and $response['success']){
                    $service = $this->models[$name]['service'];
                    $this->addTime("getModels triggers $name");
                    if(method_exists($service,'regTriggers')){ 
                        $triggers =  $service->regTriggers();
                        foreach($triggers as &$trigger){
                            $trigger['model'] = $name;
                        }
                        $this->config['triggers'] = array_merge($this->config['triggers'],$triggers);
                    }
                    if(method_exists($service,'regModalTriggers')){ 
                        $triggers =  $service->regModalTriggers();
                        foreach($triggers as &$trigger){
                            $trigger['model'] = $name;
                        }
                        $this->config['modaltriggers'] = array_merge($this->config['triggers'],$triggers);
                    }
                }else{
                    $this->addTime("getModels triggers. Not load $name.");
                }
            }
        }
        
    }
    
    public function cacheConfig()
    {
        if (empty($this->config['cacheKey'])) $this->config['cacheKey'] = 'getTables';
        if (empty($this->config['cacheHandler'])) $this->config['cacheHandler'] = $this->modx->getOption('cache_resource_handler', null, $this->modx->getOption(xPDO::OPT_CACHE_HANDLER, null, 'xPDOFileCache'));
        if (!isset($this->config['cacheExpires'])) $this->config['cacheExpires'] = (integer) $this->modx->getOption('cache_resource_expires', null, $this->modx->getOption(xPDO::OPT_CACHE_EXPIRES, null, 0));
        
        if (empty($this->config['cacheElementKey'])) $this->config['cacheElementKey'] = 'user_id_'.$this->modx->user->id. "_" . $this->config['hash'];

        $this->config['cacheOptions'] = array(
            xPDO::OPT_CACHE_KEY => $this->config['cacheKey'],
            xPDO::OPT_CACHE_HANDLER => $this->config['cacheHandler'],
            xPDO::OPT_CACHE_EXPIRES => $this->config['cacheExpires'],
        );
        //$this->addDebug($this->config,'cacheConfig');
        $this->addTime('cacheConfig');
    }
    public function getClassCache($gts_class,$gts_name)
    {
        /*if(isset($_SESSION['getTables'][$this->config['hash']][$gts_class][$gts_name]))
            return $_SESSION['getTables'][$this->config['hash']][$gts_class][$gts_name];*/
        /*$this->cacheConfig();
        $this->config['cacheElementKey'] = 'user_id_'.$this->modx->user->id. "_" . $this->config['hash'];
        
        $this->addDebug($this->config,'cacheConfig');
        if($cashed = $this->modx->cacheManager->get($this->config['cacheElementKey'], $this->config['cacheOptions'])){
            //$this->config = $cashed;
            return $cashed[$gts_class][$gts_name];
        }*/
        if($gts_name == 'all' and isset($this->config[$gts_class])){
            return $this->config[$gts_class];
        }
        if(isset($this->config[$gts_class][$gts_name])) return $this->config[$gts_class][$gts_name];
        return false;
    }
    /*public function clearCache()
    {
        unset($_SESSION['getTables']);
    }*/
    /*public function setClassCache($gts_class,$gts_name, $gts_config)
    {
        $this->config[$gts_class][$gts_name] = $gts_config;
        $this->saveCache();
        return true;
    }*/
    public function setClassConfig($gts_class, $gts_name, $gts_config)
    {
        if(!$this->config[$gts_class][$gts_name]) $this->config[$gts_class][$gts_name] = [];
        if($gts_name == 'all'){
            $this->config[$gts_class] = array_merge($this->config[$gts_class], $gts_config);
        }else{
            $this->config[$gts_class][$gts_name] = array_merge($this->config[$gts_class][$gts_name], $gts_config);
        }
        
        $this->saveCache();
        //$this->setClassCache($gts_class,$gts_name, $this->config[$gts_class][$gts_name]);
        //$this->registryAppName[$gts_class][] = $gts_name;
    }
    public function loadFromCache($hash)
    {
        /*if(!empty($_SESSION['getTables'][$hash]))
            $this->config = $_SESSION['getTables'][$hash];*/
        $this->cacheConfig();
        $this->config['cacheElementKey'] = 'user_id_'.$this->modx->user->id. "_" . $hash;
        //file_put_contents(__DIR__ ."/". $this->config['cacheElementKey'].".txt",$this->config['cacheElementKey']." ".json_encode($this->config['cacheOptions']));
        if($cashed = $this->modx->cacheManager->get($this->config['cacheElementKey'], $this->config['cacheOptions'])){
            $this->config = $cashed;
            //file_put_contents(__DIR__ ."/". $this->config['cacheElementKey'].".txt",json_encode($cashed,JSON_PRETTY_PRINT));
            return true;
        }
        return false;
    }
    public function saveCache()
    {
        /*if(!empty($this->config['hash']))
            $_SESSION['getTables'][$this->config['hash']] = $this->config;*/
        $this->cacheConfig();
        $this->modx->cacheManager->set($this->config['cacheElementKey'], $this->config, $this->config['cacheExpires'], $this->config['cacheOptions']);
    }
    public function initFromCache()
    {
        if(!$this->config['compile']){
            $this->cacheConfig();
            //$this->config['cacheElementKey'] = 'user_id_'.$this->modx->user->id. "_" . $hash;
            if($cashed = $this->modx->cacheManager->get($this->config['cacheElementKey'], $this->config['cacheOptions'])){
                $this->config = $cashed;
                $this->addTime('getTables init from cache.');
            }
        }
    }
    public function initialize()
    {
        //$this->addTime("initialize ".print_r($this->config,1));
        if(!$this->config['isAjax'] and !$this->config['registerCSS_JS']){
            $this->initFromCache();
            $this->getStyleChunks();
            
            $this->saveCache();
            $this->registerCSS_JS();
            //$this->addTime("initialize getTabsTpl".print_r($this->config['getTabsTpl'],1));
            //file_put_contents(__DIR__ ."/". $this->config['cacheElementKey']."_initialize.txt",json_encode($this->config,JSON_PRETTY_PRINT));
        }
    }
    
    public function getStyleChunks()
    {
        if($propSet = $this->modx->getObject('modPropertySet',array('name'=>'getTables_'.$this->config['frontend_framework_style']))){
            foreach($propSet->getProperties() as $name=>$prop){
                if(!isset($this->config[$name])) $this->config[$name] = $prop;
            }
            $this->addTime("load propertySet ".'getTables_'.$this->config['frontend_framework_style']);    
        }else if($propSet = $this->modx->getObject('modPropertySet',array('name'=>'getTables_bootstrap_v3'))){
            foreach($propSet->getProperties() as $name=>$prop){
                if(!isset($this->config[$name])) $this->config[$name] = $prop;
            }
            $this->addTime("load propertySet ".'getTables_getTables_bootstrap_v3');
        }
    }
    
    public function getRegistryAppName($gts_class, $gts_name)
    {
        $i = 1; $gts_name_temp = $gts_name;
        if(empty($this->registryAppName[$gts_class])){
            //$this->addTime("getRegistryAppName1 gts_name=$gts_name gts_name_temp=$gts_name_temp");
            $this->registryAppName[$gts_class][] = $gts_name_temp;
            return $gts_name_temp;
        }
        do {
            //$this->addTime("getRegistryAppName2 gts_name=$gts_name gts_name_temp=$gts_name_temp ".print_r($this->registryAppName[$gts_class],1));
            if(in_array($gts_name_temp,$this->registryAppName[$gts_class])){
                //$this->addTime("getRegistryAppName3 gts_name=$gts_name gts_name_temp=$gts_name_temp ".print_r($this->registryAppName[$gts_class],1));
                $gts_name_temp = $gts_name.'_'.$i;
            }else{
                //$this->addTime("getRegistryAppName4 gts_name=$gts_name gts_name_temp=$gts_name_temp ".print_r($this->registryAppName[$gts_class],1));
                $this->registryAppName[$gts_class][] = $gts_name_temp;
                return $gts_name_temp;
            }
            $i++;
        } while ($i < 10000);
    }
    
    
    
    
    public function getCSS_JS()
    {
        
        return [
            'js'=>[
                'frontend_jquery_js' => $this->modx->getOption('gettables_frontend_jquery_js',null,'[[+assetsUrl]]vendor/bootstrap_v3_3_6/js/jquery.min.js'),
                'frontend_framework_js' => $this->modx->getOption('gettables_frontend_framework_js',null,'[[+jsUrl]]gettables.js'),
                'frontend_framework_style_js' => $this->modx->getOption('gettables_frontend_framework_style_js',null,'[[+assetsUrl]]vendor/bootstrap_v3_3_6/js/bootstrap.min.js'),
                'frontend_message_js' => $this->modx->getOption('gettables_frontend_message_js',null,'[[+jsUrl]]gettables.message.js'),
                'add_lib_datepicker' => '[[+assetsUrl]]vendor/air-datepicker/dist/air-datepicker.js',
                'add_lib_multiselect' => '[[+assetsUrl]]vendor/bootstrap-multiselect/js/bootstrap-multiselect.js',
                'ace' => '[[+assetsUrl]]vendor/ace/ace.min.js',
                'ckeditor' => '[[+assetsUrl]]vendor/ckeditor/ckeditor.js',
                'cellsselection' => '[[+jsUrl]]gettables.cellsselection.js',
                'sortable' => '[[+jsUrl]]gettables.sortable.js',
            ],
            'css'=>[
                'frontend_framework_style_css' => $this->modx->getOption('gettables_frontend_framework_style_css',null,'[[+assetsUrl]]vendor/bootstrap_v3_3_6/css/bootstrap.min.css'),
                'frontend_excel_style' => $this->modx->getOption('gettables_frontend_excel_style',null,'[[+cssUrl]]gettables.excel-style.css'),
                'frontend_message_css' => $this->modx->getOption('gettables_frontend_message_css',null,'[[+cssUrl]]gettables.message.css'),
                'add_lib_datepicker' => '[[+assetsUrl]]vendor/air-datepicker/dist/air-datepicker.css',
                'add_lib_multiselect' => '[[+assetsUrl]]vendor/bootstrap-multiselect/css/bootstrap-multiselect.css',
                'cellsselection' => '[[+cssUrl]]gettables.cellsselection.css',
                'sortable' => '[[+cssUrl]]gettables.sortable.css',
            ],
            'load'=>[
                'load_frontend_jquery' => $this->modx->getOption('gettables_load_frontend_jquery','',0),
                'load_frontend_framework_style' => $this->modx->getOption('gettables_load_frontend_framework_style',null,0),
                'load_add_lib' => $this->modx->getOption('gettables_load_frontend_add_lib','',0),
                'load_ace' => $this->modx->getOption('gettables_load_frontend_ace','',0),
                'load_ckeditor' => $this->modx->getOption('gettables_load_frontend_ckeditor','',0),
                'load_cellsselection' => $this->modx->getOption('gettables_load_frontend_cellsselection','',0),
                'load_sortable' => $this->modx->getOption('gettables_load_frontend_sortable','',0),
            ],
            'mgr'=>[
                'js'=>[
                    'frontend_jquery_js' => $this->modx->getOption('gettables_mgr_jquery_js',null,'[[+assetsUrl]]vendor/bootstrap_v3_3_6/js/jquery.min.js'),
                    'frontend_framework_js' => $this->modx->getOption('gettables_mgr_framework_js',null,'[[+jsUrl]]gettables.js'),
                    'frontend_framework_style_js' => $this->modx->getOption('gettables_mgr_framework_style_js',null,'[[+assetsUrl]]vendor/bootstrap_v3_3_6/js/bootstrap.min.js'),
                    'frontend_message_js' => $this->modx->getOption('gettables_mgr_message_js',null,'[[+jsUrl]]gettables.message.js'),
                    'add_lib_datepicker' => '[[+assetsUrl]]vendor/air-datepicker/dist/air-datepicker.js',
                    'add_lib_multiselect' => '[[+assetsUrl]]vendor/bootstrap-multiselect/js/bootstrap-multiselect.js',
                    'ace' => '[[+assetsUrl]]vendor/ace/ace.min.js',
                    'ckeditor' => '[[+assetsUrl]]vendor/ckeditor/ckeditor.js',
                    'cellsselection' => '[[+jsUrl]]gettables.cellsselection.js',
                    'sortable' => '[[+jsUrl]]gettables.sortable.js',
                ],
                'css'=>[
                    'frontend_framework_style_css' => $this->modx->getOption('gettables_mgr_framework_style_css',null,'[[+assetsUrl]]vendor/bootstrap_v3_3_6/css/bootstrap.min.css'),
                    'frontend_excel_style' => $this->modx->getOption('gettables_mgr_excel_style',null,'[[+cssUrl]]gettables.excel-style-admin.css'),
                    'frontend_message_css' => $this->modx->getOption('gettables_mgr_message_css',null,'[[+cssUrl]]gettables.message.css'),
                    'add_lib_datepicker' => '[[+assetsUrl]]vendor/air-datepicker/dist/air-datepicker.css',
                    'add_lib_multiselect' => '[[+assetsUrl]]vendor/bootstrap-multiselect/css/bootstrap-multiselect.css',
                    'cellsselection' => '[[+cssUrl]]gettables.cellsselection.css',
                    'sortable' => '[[+cssUrl]]gettables.sortable.css',
                ],
                'load'=>[
                    'load_frontend_jquery' => $this->modx->getOption('gettables_load_mgr_jquery','',0),
                    'load_frontend_framework_style' => $this->modx->getOption('gettables_load_mgr_framework_style',null,0),
                    'load_add_lib' => $this->modx->getOption('gettables_load_mgr_add_lib','',0),
                    'load_ace' => $this->modx->getOption('gettables_load_mgr_ace','',0),
                    'load_ckeditor' => $this->modx->getOption('gettables_load_mgr_ckeditor','',0),
                    'load_cellsselection' => $this->modx->getOption('gettables_load_mgr_cellsselection','',0),
                    'load_sortable' => $this->modx->getOption('gettables_load_mgr_sortable','',0),
                ],
            ],
        ];
    }
    
    public function makePlaceholders($config)
    {
        $placeholders = [];
        foreach($config as $k=>$v){
            if(is_string($v)){
                $placeholders['pl'][] = "[[+$k]]";
                $placeholders['vl'][] = $v;
            }
        }
        return $placeholders;
    }
    public function registerCSS_JS()
    {
        $this->addTime('registerCSS_JS');
        $CSS_JS = $this->prepareCSS_JS();
        
        foreach($CSS_JS['js'] as $js){
            if (!empty($js) && preg_match('/\.js/i', $js)) {
                if (preg_match('/\.js$/i', $js)) {
                    $js .= '?v=' . substr(md5($this->version.$this->config['frontend_framework_style']), 0, 10);
                }
                $this->modx->regClientScript(str_replace($CSS_JS['placeholders']['pl'], $CSS_JS['placeholders']['vl'], $js));
            }
        }
        
        foreach($CSS_JS['css'] as $css){
            if (!empty($css) && preg_match('/\.css/i', $css)) {
                if (preg_match('/\.css$/i', $css)) {
                    $css .= '?v=' . substr(md5($this->version.$this->config['frontend_framework_style']), 0, 10);
                }
                $this->modx->regClientCSS(str_replace($CSS_JS['placeholders']['pl'], $CSS_JS['placeholders']['vl'], $css));
            }
        }

        $this->modx->regClientStartupScript(
            '<script type="text/javascript">getTablesConfig = ' . $CSS_JS['data'] . ';</script>', true
        );
        $this->config['registerCSS_JS'] = true;
    }

    public function prepareCSS_JS()
    {
        
        $config = $this->config;
        $placeholders = $this->makePlaceholders($config);
        //$this->modx->log(1,"<pre>".print_r($placeholders,1)."</pre>");
        
        $CSS_JS = $this->getCSS_JS();
        if($this->modx->context->key == 'mgr'){
            $CSS_JS = $CSS_JS['mgr'];
        }
        // if(isset($config['tabs'])){
        //     $getTabs = $this->getService('getTabs');
        //     $tabCss = $getTabs->getCSS_JS();
        //     foreach($CSS_JS as $k=>$v){
        //         $CSS_JS[$k] = array_merge($CSS_JS[$k], $tabCss[$k]);
        //     }
            
        // }
        foreach($CSS_JS as $k=>$v){
            foreach($v as $k1=>$v1){
                if(isset($config[$k][$k1])) $CSS_JS[$k][$k1] = $config[$k][$k1];
            }
        }
        
        
        // Register CSS
        $csss = array();
        if($CSS_JS['load']['load_frontend_framework_style']) $csss[] = $CSS_JS['css']['frontend_framework_style_css'];
        $csss[] = $CSS_JS['css']['frontend_message_css']; 
        if(!empty($CSS_JS['css']['frontend_gettabs_css'])) $csss[] = $CSS_JS['css']['frontend_gettabs_css'];
        
        if(!empty($CSS_JS['css']['frontend_excel_style'])) $csss[] = $CSS_JS['css']['frontend_excel_style'];
        
        if($CSS_JS['load']['load_add_lib']){
            $csss[] = $CSS_JS['css']['add_lib_datepicker'];
            $csss[] = $CSS_JS['css']['add_lib_multiselect'];
        } 
        if($CSS_JS['load']['load_cellsselection']){
            $csss[] = $CSS_JS['css']['cellsselection'];
        } 
        if($CSS_JS['load']['load_sortable']){
            $csss[] = $CSS_JS['css']['sortable'];
        }
        if($config['add_css']){
            foreach(explode(",",$config['add_css']) as $acss){
                $csss[] = $acss;
            }
        }
        
        // Register JS
        $jss = array();
        if($CSS_JS['load']['load_frontend_jquery']) $jss[] = $CSS_JS['js']['frontend_jquery_js'];
        if($CSS_JS['load']['load_frontend_framework_style']) $jss[] = $CSS_JS['js']['frontend_framework_style_js'];
        $jss[] = $CSS_JS['js']['frontend_framework_js'];
        $jss[] = $CSS_JS['js']['frontend_message_js'];
        if(!empty($CSS_JS['js']['frontend_gettabs_js'])) $jss[] = $CSS_JS['js']['frontend_gettabs_js'];
        
        $js_datepicker = false;
        if($CSS_JS['load']['load_add_lib']){
            $jss[] = $CSS_JS['js']['add_lib_datepicker'];
            $jss[] = $CSS_JS['js']['add_lib_multiselect'];
        }
        if($CSS_JS['load']['load_cellsselection']){
            $jss[] = $CSS_JS['js']['cellsselection'];
        } 

        if($CSS_JS['load']['load_sortable']){
            $jss[] = $CSS_JS['js']['sortable'];
        } 

        if($config['add_js']){
            foreach(explode(",",$config['add_js']) as $ajs){
                $jss[] = $ajs;
            }
        }
        
        
        $data = array(
            'ctx' => $this->config['ctx'],
            'cssUrl' => $this->config['cssUrl'],
            'jsUrl' => $this->config['jsUrl'],
            'actionUrl' => $this->config['actionUrl'],
            'close_all_message' => $this->modx->lexicon('gettables_message_close_all'),
            'showLog' => (boolean)$this->config['showLog'],
        );
        $data['hash'] = $this->config['hash'];
        
        if($CSS_JS['load']['load_ace']){
            $jss[] = $CSS_JS['js']['ace'];
            $data['load_ace'] = 1;
        } 
        if($CSS_JS['load']['load_ckeditor']){
            $jss[] = $CSS_JS['js']['ckeditor'];
            $data['load_ckeditor'] = 1;
        }

        $data = json_encode($data, true);
        
        return [
            'js' => $jss,
            'css' => $csss,
            'data' => $data,
            'placeholders' => $placeholders,
        ];
    }
    /**
     * Handle frontend requests with actions
     *
     * @param $action
     * @param array $data
     *
     * @return array|bool|string
     */
    public function handleRequestInt($action, &$data = array())
    {
        //$this->addTime("getTables handleRequest $action");
        $this->addTime("handleRequestInt $action");
        

        $actions = explode("/",$action);
        $class = $actions[0];
        if($actions[0] == "processor"){
                /*unset($actions[0]);
                $action = implode("/",$actions);
                $otherProps = array(
                    'processors_path' => $this->config['corePath'].'processors/'
                );
                $response =  $this->modx->runProcessor($action, $data, $otherProps);*/
                $response = $this->error("Доступ запрешен processor $action");
        }else if(count($actions) == 1){
            $response = $this->error("Доступ запрешен method_exists $action");
            /*$class = get_class($this);
            if(method_exists($this,$action)){
                $response = $this->$action($data);
            }else{
                $response = $this->error("Метод $action в классе $class не найден!");
            }*/
        }else if(isset($this->models[$actions[0]])){
            $response = $this->loadService($class);
            if(!is_array($response) or !$response['success']){
                $response = $response;
            }else{
                $service = $this->models[$class]['service'];
                
                //unset($actions[0]); 
                //$class_action = implode("/",$actions);
                if(method_exists($service,$actions[1])){ 
                    $response =  $service->{$actions[1]}($data,true);
                }else{
                    $class = get_class($service);
                    $response = $this->error("Не найден $class/{$actions[0]}");
                }
            }
        }else{
            
        }
        //$this->addDebug($response,"handleRequest");
        if(!$response) {
            $class = get_class($this);
            $response = $this->error("Ошибка {$class} handleRequestInt!");
        }
        
        return $response;
    }
    /**
     * Handle frontend requests with actions
     *
     * @param $action
     * @param array $data
     *
     * @return array|bool|string
     */
    public function handleRequest($action, $data = array())
    {
        //$this->addTime("getTables handleRequest $action");
        $this->addTime("handleRequest $action");
        
        if(isset($this->config['permission'][$action]))
            if(!$this->modx->hasPermission($this->config['permission'][$action])) return $this->error(['lexicon'=>'access_denied']);
        
        $this->config['isAjax'] = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest';
        if(($this->config['isAjax'] or $action == 'getTable/export_excel' or $_GET["load_model"] == 1) and $this->config['loadModels']){
            $this->pdoTools->setConfig(['loadModels' => $this->config['loadModels']]);
            $this->pdoTools->loadModels();
            $this->getModels();
        }
        

        $actions = explode("/",$action);
        $class = $actions[0];
        if($actions[0] == "processor"){
                /*unset($actions[0]);
                $action = implode("/",$actions);
                $otherProps = array(
                    'processors_path' => $this->config['corePath'].'processors/'
                );
                $response =  $this->modx->runProcessor($action, $data, $otherProps);*/
                $response = $this->error("Доступ запрешен processor $action");
        }else if(count($actions) == 1){
            $response = $this->error("Доступ запрешен method_exists $action");
            /*$class = get_class($this);
            if(method_exists($this,$action)){
                $response = $this->$action($data);
            }else{
                $response = $this->error("Метод $action в классе $class не найден!");
            }*/
        }else if(isset($this->models[$actions[0]])){
            $response = $this->loadService($class);
            if(!is_array($response) or !$response['success']){
                $response = $response;
            }else{
                $service = $this->models[$class]['service'];
                
                unset($actions[0]); 
                $class_action = implode("/",$actions);
                if(method_exists($service,'handleRequest')){ 
                    $response =  $service->handleRequest($class_action, $data);
                }else{
                    $class = get_class($service);
                    $response = $this->error("Не найден $class/$class_action");
                }
            }
        }else{
            
        }
        //$this->addDebug($response,"handleRequest");
        if(!$response) {
            $class = get_class($this);
            $response = $this->error("Ошибка {$class} handleRequest!");
        }
        if ($this->modx->user->hasSessionContext('mgr') && !empty($this->config['showLog'])) {
            $response['log'] = '<pre class="getTablesLog" style="width:900px;">' . print_r($this->getTime(), 1) . '</pre>';
        }
        if ($this->modx->user->hasSessionContext('mgr') && !empty($this->config['debug'])) {
            $response['debugs'] = $this->debugs;
        }
        
        return $response;
    }
    
    public function getService($class)
    {
        $response = $this->loadService($class);
        if(is_array($response) and $response['success'])
            return $this->models[$class]['service'];
        return false;
    }
    public function loadService($class)
    {
        if(!$this->models[$class]['service']){
            if($this->models[$class]['class']){
                require_once($this->models[$class]['class']);
                $this->models[$class]['service'] = new $class($this, $this->config);
            }else{
                if(file_exists($this->models[$class]['path'].strtolower($class).".class.php")){
                    if(!$this->models[$class]['service'] = $this->modx->getService($class,$class,$this->models[$class]['path'],[])) {
                        return $this->error("Компонент $class не найден!");
                    }
                }else{
                    if(!$this->models[$class]['service'] = $this->modx->getService($class,$class,$this->models[$class]['path']."$class/",[])) {
                        return $this->error("Компонент $class не найден!");
                    }
                }
            }
        }
        if(!$this->models[$class]['service']){
            return $this->error("Компонент или класс $class не найден!");
        }
        return array('success'=> true);
    }
    /**
     * Sanitize values of an array using regular expression patterns.
     *
     * @static
     * @param array $target The target array to sanitize.
     * @param array|string $patterns A regular expression pattern, or array of
     * regular expression patterns to apply to all values of the target.
     * @param integer $depth The maximum recursive depth to sanitize if the
     * target contains values that are arrays.
     * @param integer $nesting The maximum nesting level in which to dive
     * @return array The sanitized array.
     */
    public function modx_sanitize(array &$target, array $patterns= array(), $depth= 99, $nesting= 10) {
        foreach ($target as $key => &$value) {
            if (is_array($value) && $depth > 0) {
                $this->modx_sanitize($value, $patterns, $depth-1);
            } elseif (is_string($value)) {
                if (!empty($patterns)) {
                    $iteration = 1;
                    $nesting = ((integer) $nesting ? (integer) $nesting : 10);
                    while ($iteration <= $nesting) {
                        $matched = false;
                        foreach ($patterns as $pattern) {
                            $patternIterator = 1;
                            $patternMatches = preg_match($pattern, $value);
                            if ($patternMatches > 0) {
                                $matched = true;
                                while ($patternMatches > 0 && $patternIterator <= $nesting) {
                                    $value= preg_replace($pattern, '', $value);
                                    $patternMatches = preg_match($pattern, $value);
                                }
                            }
                        }
                        if (!$matched) {
                            break;
                        }
                        $iteration++;
                    }
                }
                /*if (get_magic_quotes_gpc()) {
                    $target[$key]= stripslashes($value);
                } else {
                    $target[$key]= $value;
                }*/
                $target[$key]= $value;
            }
        }
        return $target;
    }
    
    public function modx_sanitize_string($value, array $patterns= array(), $depth= 99, $nesting= 10) {
        if (is_string($value)) {
            if (!empty($patterns)) {
                $iteration = 1;
                $nesting = ((integer) $nesting ? (integer) $nesting : 10);
                while ($iteration <= $nesting) {
                    $matched = false;
                    foreach ($patterns as $pattern) {
                        $patternIterator = 1;
                        $patternMatches = preg_match($pattern, $value);
                        if ($patternMatches > 0) {
                            $matched = true;
                            while ($patternMatches > 0 && $patternIterator <= $nesting) {
                                $value= preg_replace($pattern, '', $value);
                                $patternMatches = preg_match($pattern, $value);
                            }
                        }
                    }
                    if (!$matched) {
                        break;
                    }
                    $iteration++;
                }
            }
        }
        return $value;
    }
    
    public function sanitize($data)
    {
        $sanitizePatterns = $this->modx->sanitizePatterns;
        $sanitizePatterns['fenom_syntax'] = '@\{(.*?)\}@si';
        if(is_array($data)){
            return $this->modx_sanitize($data, $sanitizePatterns);
            //return preg_replace($sanitizePatterns,'',$data);
        }else{
            return $this->modx_sanitize_string($data, $sanitizePatterns);
        }
    }

    /*public function isJson($string) {
        json_decode($string);
        return (json_last_error() == JSON_ERROR_NONE);
    }*/

    public function error($message = '', $data = array())
    {
        if(is_array($message)){
            if(isset($message['data'])){
                $message = $this->modx->lexicon($message['lexicon'], $message['data']);
            }else{
                $message = $this->modx->lexicon($message['lexicon']);
            }
        }
        $response = array(
            'success' => false,
            'message' => $message,
            'data' => $data,
        );

        return $response;
    }
    public function insertToArray($array=array(), $new=array(), $after='') {
        $res = array();
        $res1 = array();
        $res2 = array();
        $c = 0;
        $n = 0;
        foreach ($array as $k => $v) {
          if ($k == $after) { 
            $n = $c;
          } 
          $c++;
        }
        $c = 0;
        foreach ($array as $i => $a) {
          if ($c > $n) { 
            $res1[$i] = $a;
          } else {
            $res2[$i] = $a;
          }
          $c++;
        }
        $res = $res2 + $new + $res1;
        return $res;
    }
    public function success($message = '', $data = array())
    {
        if(is_array($message)){
            if(isset($message['data'])){
                $message = $this->modx->lexicon($message['lexicon'], $message['data']);
            }else{
                $message = $this->modx->lexicon($message['lexicon']);
            }
        }
        $response = array(
            'success' => true,
            'message' => $message,
            'data' => $data,
        );

        return $response;
    }
}