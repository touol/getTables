<?php

return [
	'core_path' => [
        'xtype' => 'textfield',
        'value' => '[[+core_path]]components/gettables/core/',
        'area' => 'gettables_main',
    ],
	'frontend_jquery_js' => [
        'xtype' => 'textfield',
        'value' => '[[+assetsUrl]]vendor/bootstrap_v3_3_6/js/jquery.min.js',
        'area' => 'gettables_main',
    ],
    'load_frontend_jquery' => [
        'xtype' => 'combo-boolean',
        'value' => false,
        'area' => 'gettables_main',
    ],
    'frontend_framework_style' => [
        'xtype' => 'textfield',
        'value' => 'bootstrap_v3',
        'area' => 'gettables_main',
    ],
    'load_frontend_framework_style' => [
        'xtype' => 'combo-boolean',
        'value' => false,
        'area' => 'gettables_main',
    ],
    'frontend_framework_style_css' => [
        'xtype' => 'textfield',
        'value' => '[[+assetsUrl]]vendor/bootstrap_v3_3_6/css/bootstrap.min.css',
        'area' => 'gettables_main',
    ],
    'frontend_framework_style_js' => [
        'xtype' => 'textfield',
        'value' => '[[+assetsUrl]]vendor/bootstrap_v3_3_6/js/bootstrap.min.js',
        'area' => 'gettables_main',
    ],
    
    'frontend_framework_js' => [
        'xtype' => 'textfield',
        'value' => '[[+jsUrl]]gettables.js',
        'area' => 'gettables_main',
    ],
    'frontend_message_css' => [
        'xtype' => 'textfield',
        'value' => '[[+cssUrl]]gettables.message.css',
        'area' => 'gettables_main',
    ],
    'frontend_message_js' => [
        'xtype' => 'textfield',
        'value' => '[[+jsUrl]]gettables.message.js',
        'area' => 'gettables_main',
    ],
    'frontend_excel_style' => [
        'xtype' => 'textfield',
        'value' => '[[+cssUrl]]gettables.excel-style.css',
        'area' => 'gettables_main',
    ],

    'mgr_jquery_js' => [
        'xtype' => 'textfield',
        'value' => '[[+assetsUrl]]vendor/bootstrap_v3_3_6/js/jquery.min.js',
        'area' => 'gettables_main',
    ],
    'load_mgr_jquery' => [
        'xtype' => 'combo-boolean',
        'value' => true,
        'area' => 'gettables_main',
    ],
    'mgr_framework_style' => [
        'xtype' => 'textfield',
        'value' => 'bootstrap_v3',
        'area' => 'gettables_main',
    ],
    'load_mgr_framework_style' => [
        'xtype' => 'combo-boolean',
        'value' => true,
        'area' => 'gettables_main',
    ],
    'mgr_framework_style_css' => [
        'xtype' => 'textfield',
        'value' => '[[+assetsUrl]]vendor/bootstrap_v3_3_6/css/bootstrap.min.css',
        'area' => 'gettables_main',
    ],
    'mgr_framework_style_js' => [
        'xtype' => 'textfield',
        'value' => '[[+assetsUrl]]vendor/bootstrap_v3_3_6/js/bootstrap.min.js',
        'area' => 'gettables_main',
    ],
    
    'mgr_framework_js' => [
        'xtype' => 'textfield',
        'value' => '[[+jsUrl]]gettables.js',
        'area' => 'gettables_main',
    ],
    'mgr_message_css' => [
        'xtype' => 'textfield',
        'value' => '[[+cssUrl]]gettables.message.css',
        'area' => 'gettables_main',
    ],
    'mgr_message_js' => [
        'xtype' => 'textfield',
        'value' => '[[+jsUrl]]gettables.message.js',
        'area' => 'gettables_main',
    ],
    'mgr_excel_style' => [
        'xtype' => 'textfield',
        'value' => '[[+cssUrl]]gettables.excel-style.css',
        'area' => 'gettables_main',
    ],

    'load_frontend_add_lib' => [
        'xtype' => 'combo-boolean',
        'value' => false,
        'area' => 'gettables_main',
    ],
    'load_mgr_add_lib' => [
        'xtype' => 'combo-boolean',
        'value' => true,
        'area' => 'gettables_main',
    ],

];