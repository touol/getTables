<?php

return [
    'getTable' => [
        'file' => 'gettable',
        'description' => 'getTable snippet to list items',
        'properties' => [
            'getTableOuterTpl' => [
                'type' => 'textfield',
                'value' => 'getTable.outer.tpl',
            ],
			'getTableNavTpl' => [
                'type' => 'textfield',
                'value' => 'getTable.nav.tpl',
            ],
			'getTableRowTpl' => [
                'type' => 'textfield',
                'value' => 'getTable.row.tpl',
            ],
			'getTableEditRowTpl' => [
                'type' => 'textfield',
                'value' => 'getTable.EditRow.tpl',
            ],
			'getTableModalCreateUpdateTpl' => [
                'type' => 'textfield',
                'value' => 'getTable.Modal.CreateUpdate.tpl',
            ],
			/*
				'getTableNavTpl' => 'getTable.nav.tpl',
			'getTableEditRowTpl' => 'getTable.EditRow.tpl',
			'getTableFilterTpl' => 'getTable.Filter.tpl',
				*/
			'getTableFilterTpl' => [
                'type' => 'textfield',
                'value' => 'getTable.Filter.tpl',
            ],
			'getTabsTpl' => [
                'type' => 'textfield',
                'value' => 'getTabs.tpl',
            ],
            'sortby' => [
                'type' => 'textfield',
                'value' => 'id',
            ],
            'sortdir' => [
                'type' => 'list',
                'options' => [
                    ['text' => 'ASC', 'value' => 'ASC'],
                    ['text' => 'DESC', 'value' => 'DESC'],
                ],
                'value' => 'ASC',
            ],
            'limit' => [
                'type' => 'numberfield',
                'value' => 10,
            ],
            'outputSeparator' => [
                'type' => 'textfield',
                'value' => "\n",
            ],
        ],
    ],
	'getTabs' => [
        'file' => 'gettabs',
        'description' => 'getTabs snippet to list items',
        'properties' => [
            'getTableOuterTpl' => [
                'type' => 'textfield',
                'value' => 'getTable.outer.tpl',
            ],
			'getTableNavTpl' => [
                'type' => 'textfield',
                'value' => 'getTable.nav.tpl',
            ],
			'getTableRowTpl' => [
                'type' => 'textfield',
                'value' => 'getTable.row.tpl',
            ],
			'getTableEditRowTpl' => [
                'type' => 'textfield',
                'value' => 'getTable.EditRow.tpl',
            ],
			'getTableModalCreateUpdateTpl' => [
                'type' => 'textfield',
                'value' => 'getTable.Modal.CreateUpdate.tpl',
            ],
			/*
				'getTableNavTpl' => 'getTable.nav.tpl',
			'getTableEditRowTpl' => 'getTable.EditRow.tpl',
			'getTableFilterTpl' => 'getTable.Filter.tpl',
				*/
			'getTableFilterTpl' => [
                'type' => 'textfield',
                'value' => 'getTable.Filter.tpl',
            ],
			'getTabsTpl' => [
                'type' => 'textfield',
                'value' => 'getTabs.tpl',
            ],
            'outputSeparator' => [
                'type' => 'textfield',
                'value' => "\n",
            ],
        ],
    ],
];