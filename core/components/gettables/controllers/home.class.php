<?php

/**
 * The home manager controller for getTables.
 *
 */
class getTablesHomeManagerController extends modExtraManagerController
{
    /** @var getTables $getTables */
    public $getTables;
    public $error = "";

    /**
     *
     */
    public function initialize()
    {
        //$this->getTables = $this->modx->getService('getTables', 'getTables', MODX_CORE_PATH . 'components/gettables/model/');
        $gettables_core_path = $this->modx->getOption('gettables_core_path',null, MODX_CORE_PATH . 'components/gettables/core/');
        $gettables_core_path = str_replace('[[+core_path]]', MODX_CORE_PATH, $gettables_core_path);
        if (!$this->modx->loadClass('gettables', $gettables_core_path, false, true)) {
            $this->error .= 'getTablesHomeManagerController Could not load getTables class! '.$gettables_core_path ."<br/>\r\n";
        }
        if(empty($_REQUEST['config'])){
            $this->error .= 'getTablesHomeManagerController Could not config!' ."<br/>\r\n";
        }
        if(is_dir(MODX_CORE_PATH . 'components/gettablespro/model/')){
            if($this->gtsPro = $this->modx->getService('getTablesPro', 'getTablesPro', MODX_CORE_PATH . 'components/gettablespro/model/')){
                if($gtsPConfig = $this->modx->getObject('gtsPConfig',['name'=>$_REQUEST['config']])){
                    $config = json_decode($gtsPConfig->config,1);
                }
            }
        }
        if(!is_array($config)){
            $config = json_decode($this->modx->getOption($_REQUEST['config']),1);
        } 
        
        if(!is_array($config)){
            $this->error .= 'getTablesHomeManagerController Could not load config! Check config json!' ."<br/>\r\n";
        } 
        $config['ctx'] = 'mgr';

        $this->getTables = new getTables($this->modx, $config);
        if ($this->getTables) {
            $this->getTables->pdoTools->addTime('getTables loaded.');
            $this->getTables->initialize();
            $this->getTables->pdoTools->addTime('getTables init from cache.');
        }else{
            $this->error .= 'getTablesHomeManagerController Could not create getTables!' ."<br/>\r\n";
        }
        if($this->error) $this->modx->log(1, $this->error);

        parent::initialize();
    }


    /**
     * @return array
     */
    public function getLanguageTopics()
    {
        
        $LanguageTopics = ['gettables:manager', 'gettables:default'];
        if(empty($this->error)){
            if(!empty($this->getTables->config['loadModels'])){
                $models = explode(",",$this->getTables->config['loadModels']);
                foreach($models as $model){
                    $LanguageTopics[] = trim($model).':manager';
                    $LanguageTopics[] = trim($model).':default';
                }
            }
        }
        return $LanguageTopics;
    }


    /**
     * @return bool
     */
    public function checkPermissions()
    {
        return true;
    }


    /**
     * @return null|string
     */
    public function getPageTitle()
    {
        if(empty($this->error)){
            if(!empty($this->getTables->config['loadModels'])){
                $models = explode(",",$this->getTables->config['loadModels']);
                return $this->modx->lexicon($models[0]);
            }
        }
        return $this->modx->lexicon('gettables');
    }


    /**
     * @return void
     */
    public function loadCustomCssJs()
    {
        /*$this->addCss($this->getTables->config['cssUrl'] . 'mgr/main.css');
        $this->addJavascript($this->getTables->config['jsUrl'] . 'mgr/gettables.js');
        $this->addJavascript($this->getTables->config['jsUrl'] . 'mgr/misc/utils.js');
        $this->addJavascript($this->getTables->config['jsUrl'] . 'mgr/misc/combo.js');
        $this->addJavascript($this->getTables->config['jsUrl'] . 'mgr/misc/default.grid.js');
        $this->addJavascript($this->getTables->config['jsUrl'] . 'mgr/misc/default.window.js');
        $this->addJavascript($this->getTables->config['jsUrl'] . 'mgr/widgets/items/grid.js');
        $this->addJavascript($this->getTables->config['jsUrl'] . 'mgr/widgets/items/windows.js');
        $this->addJavascript($this->getTables->config['jsUrl'] . 'mgr/widgets/home.panel.js');
        $this->addJavascript($this->getTables->config['jsUrl'] . 'mgr/sections/home.js');

        $this->addJavascript(MODX_MANAGER_URL . 'assets/modext/util/datetime.js');

        $this->getTables->config['date_format'] = $this->modx->getOption('gettables_date_format', null, '%d.%m.%y <span class="gray">%H:%M</span>');
        $this->getTables->config['help_buttons'] = ($buttons = $this->getButtons()) ? $buttons : '';

        $this->addHtml('<script type="text/javascript">
        getTables.config = ' . json_encode($this->getTables->config) . ';
        getTables.config.connector_url = "' . $this->getTables->config['connectorUrl'] . '";
        Ext.onReady(function() {MODx.load({ xtype: "gettables-page-home"});});
        </script>');*/
        if(!empty($this->error)) return;

        $this->getTables->pdoTools->addTime('registerCSS_JS');
        $CSS_JS = $this->getTables->prepareCSS_JS();
        $this->addHtml(
            '<script type="text/javascript">getTablesConfig = ' . $CSS_JS['data'] . ';</script>', true
        );
        //$this->modx->log(1, 'getTablesHomeManagerController CSS_JS! '.print_r($CSS_JS,1));
        foreach($CSS_JS['css'] as $css){
            if (!empty($css) && preg_match('/\.css/i', $css)) {
                if (preg_match('/\.css$/i', $css)) {
                    $css .= '?v=' . substr(md5($this->version.$config['frontend_framework_style']), 0, 10);
                }
                $this->addCss(str_replace($CSS_JS['placeholders']['pl'], $CSS_JS['placeholders']['vl'], $css));
            }
        }
        foreach($CSS_JS['js'] as $js){
            if (!empty($js) && preg_match('/\.js/i', $js)) {
                if (preg_match('/\.js$/i', $js)) {
                    $js .= '?v=' . substr(md5($this->version.$config['frontend_framework_style']), 0, 10);
                }
                $this->addLastJavascript(str_replace($CSS_JS['placeholders']['pl'], $CSS_JS['placeholders']['vl'], $js));
            }
        }
        
        

        
    }


    /**
     * @return string
     */
    public function getTemplateFile()
    {
        if(!empty($this->error)){
            $this->content .=  '<div id="gettables-panel-home-div">Error '.$this->error.'</div>';
            return '';
        }
        if($this->getTables->config['tabs'])
            $response = $this->getTables->handleRequest('getTabs/fetch');
        if($this->getTables->config['table'])
            $response = $this->getTables->handleRequest('getTable/fetch');

        if(!$response['success']){
            $output = $response['message'];
        }else{
            $output = $response['data']['html'];
        }

        $log = '';
        if ($this->modx->user->hasSessionContext('mgr') && !empty($this->getTables->config['showLog'])) {
            $log .= "<div style='width:500px;'>".$response['log']."</div>";
        }

        $this->content .=  '
        <style>
        * {
            -webkit-box-sizing: initial !important;
            -moz-box-sizing: initial !important;
            box-sizing: initial !important;
        }
        .modal * {
            -webkit-box-sizing: border-box !important;
            -moz-box-sizing: border-box !important;
            box-sizing: border-box !important;
        }
        #gettables-panel-home-div * {
            -webkit-box-sizing: border-box !important;
            -moz-box-sizing: border-box !important;
            box-sizing: border-box !important;
        }
        </style>
        <div id="gettables-panel-home-div" style="height: 90vh;overflow-y: scroll;">'.$output.$log.'</div>';
        return '';
    }

    /**
     * @return string
     */
    /*public function getButtons()
    {
        $buttons = null;
        $name = 'getTables';
        $path = "Extras/{$name}/_build/build.php";
        if (file_exists(MODX_BASE_PATH . $path)) {
            $site_url = $this->modx->getOption('site_url').$path;
            $buttons[] = [
                'url' => $site_url,
                'text' => $this->modx->lexicon('gettables_button_install'),
            ];
            $buttons[] = [
                'url' => $site_url.'?download=1&encryption_disabled=1',
                'text' => $this->modx->lexicon('gettables_button_download'),
            ];
            $buttons[] = [
                'url' => $site_url.'?download=1',
                'text' => $this->modx->lexicon('gettables_button_download_encryption'),
            ];
        }
        return $buttons;
    }*/
}