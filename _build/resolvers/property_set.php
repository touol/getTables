<?php
/** @var xPDOTransport $transport */
/** @var array $options */
/** @var modX $modx */
if ($transport->xpdo) {
    $modx =& $transport->xpdo;

    switch ($options[xPDOTransport::PACKAGE_ACTION]) {
        case xPDOTransport::ACTION_INSTALL:
        case xPDOTransport::ACTION_UPGRADE:
            $modx->addPackage('gettables', MODX_CORE_PATH . 'components/gettables/model/');
            if (!$prop_bootstrap_v3 = $modx->getObject('modPropertySet', array('name' => 'getTables_bootstrap_v3'))) {
                $prop_bootstrap_v3 = $modx->newObject('modPropertySet');
            }
            $prop_bootstrap_v3->fromArray(array(
                'name' => 'getTables_bootstrap_v3',
                'description' => 'Setting for getTables on bootstrap_v3',
                'properties' => array(
                    'getTableOuterTpl' => array(
                        'name' => 'getTableOuterTpl',
                        'desc' => 'getTableOuterTpl',
                        'type' => 'textfield',
                        'options' => array(),
                        'lexicon' => '',
                        'area' => '',
                        'value' => 'getTable.outer.tpl',
                    ),
                    'getTableFilterTpl' => array(
                        'name' => 'getTableFilterTpl',
                        'desc' => 'getTableFilterTpl',
                        'type' => 'textfield',
                        'options' => array(),
                        'lexicon' => '',
                        'area' => '',
                        'value' => 'getTable.Filter.tpl',
                    ),
                    'getTableNavTpl' => array(
                        'name' => 'getTableNavTpl',
                        'desc' => 'getTableNavTpl',
                        'type' => 'textfield',
                        'options' => array(),
                        'lexicon' => '',
                        'area' => '',
                        'value' => 'getTable.nav.tpl',
                    ),
                    'getTableRowTpl' => array(
                        'name' => 'getTableRowTpl',
                        'desc' => 'getTableRowTpl',
                        'type' => 'textfield',
                        'options' => array(),
                        'lexicon' => '',
                        'area' => '',
                        'value' => 'getTable.row.tpl',
                    ),
                    'getTableEditRowTpl' => array(
                        'name' => 'getTableEditRowTpl',
                        'desc' => 'getTableEditRowTpl',
                        'type' => 'textfield',
                        'options' => array(),
                        'lexicon' => '',
                        'area' => '',
                        'value' => 'getTable.EditRow.tpl',
                    ),
                    'getTableModalCreateUpdateTpl' => array(
                        'name' => 'getTableModalCreateUpdateTpl',
                        'desc' => 'getTableModalCreateUpdateTpl',
                        'type' => 'textfield',
                        'options' => array(),
                        'lexicon' => '',
                        'area' => '',
                        'value' => 'getTable.Modal.CreateUpdate.tpl',
                    ),
                    'getTabsTpl' => array(
                        'name' => 'getTabsTpl',
                        'desc' => 'getTabsTpl',
                        'type' => 'textfield',
                        'options' => array(),
                        'lexicon' => '',
                        'area' => '',
                        'value' => 'getTabs.tpl',
                    ),
                    'getTableActionTpl' => array(
                        'name' => 'getTableActionTpl',
                        'desc' => 'getTableActionTpl',
                        'type' => 'textfield',
                        'options' => array(),
                        'lexicon' => '',
                        'area' => '',
                        'value' => 'getTable.action.tpl',
                    ),
                    'getTableEditFormTpl' => array(
                        'name' => 'getTableEditFormTpl',
                        'desc' => 'getTableEditFormTpl',
                        'type' => 'textfield',
                        'options' => array(),
                        'lexicon' => '',
                        'area' => '',
                        'value' => 'getTable.EditForm.tpl',
                    ),
                    'getTableFilterCheckboxTpl' => array(
                        'name' => 'getTableFilterCheckboxTpl',
                        'desc' => 'getTableFilterCheckboxTpl',
                        'type' => 'textfield',
                        'options' => array(),
                        'lexicon' => '',
                        'area' => '',
                        'value' => 'getTable.FilterCheckbox.tpl',
                    ),
                    'getTableModalRemoveTpl' => array(
                        'name' => 'getTableModalRemoveTpl',
                        'desc' => 'getTableModalRemoveTpl',
                        'type' => 'textfield',
                        'options' => array(),
                        'lexicon' => '',
                        'area' => '',
                        'value' => 'getTable.Modal.Remove.tpl',
                    ),
                    'getTableFormTpl' => array(
                        'name' => 'getTableFormTpl',
                        'desc' => 'getTableFormTpl',
                        'type' => 'textfield',
                        'options' => array(),
                        'lexicon' => '',
                        'area' => '',
                        'value' => 'getTable.Form.tpl',
                    ),
                    'getTreeMainTpl' => array(
                        'name' => 'getTreeMainTpl',
                        'desc' => 'getTreeMainTpl',
                        'type' => 'textfield',
                        'options' => array(),
                        'lexicon' => '',
                        'area' => '',
                        'value' => 'getTree.Main.tpl',
                    ),
                    'getTreeULTpl' => array(
                        'name' => 'getTreeULTpl',
                        'desc' => 'getTreeULTpl',
                        'type' => 'textfield',
                        'options' => array(),
                        'lexicon' => '',
                        'area' => '',
                        'value' => 'getTree.UL.tpl',
                    ),
                    'getTreeLITpl' => array(
                        'name' => 'getTreeLITpl',
                        'desc' => 'getTreeLITpl',
                        'type' => 'textfield',
                        'options' => array(),
                        'lexicon' => '',
                        'area' => '',
                        'value' => 'getTree.LI.tpl',
                    ),
                    'getTreeModalTpl' => array(
                        'name' => 'getTreeModalTpl',
                        'desc' => 'getTreeModalTpl',
                        'type' => 'textfield',
                        'options' => array(),
                        'lexicon' => '',
                        'area' => '',
                        'value' => 'getTree.Modal.tpl',
                    ),
                    'getTreeModalRemoveTpl' => array(
                        'name' => 'getTreeModalRemoveTpl',
                        'desc' => 'getTreeModalRemoveTpl',
                        'type' => 'textfield',
                        'options' => array(),
                        'lexicon' => '',
                        'area' => '',
                        'value' => 'getTree.Modal.Remove.tpl',
                    ),
                    'getTreePanelTpl' => array(
                        'name' => 'getTreePanelTpl',
                        'desc' => 'getTreePanelTpl',
                        'type' => 'textfield',
                        'options' => array(),
                        'lexicon' => '',
                        'area' => '',
                        'value' => 'getTree.Panel.tpl',
                    ),
                    'ACTreeULTpl' => array(
                        'name' => 'ACTreeULTpl',
                        'desc' => 'ACTreeULTpl',
                        'type' => 'textfield',
                        'options' => array(),
                        'lexicon' => '',
                        'area' => '',
                        'value' => 'ACTree.UL.tpl',
                    ),
                    'ACTreeLITpl' => array(
                        'name' => 'ACTreeLITpl',
                        'desc' => 'ACTreeLITpl',
                        'type' => 'textfield',
                        'options' => array(),
                        'lexicon' => '',
                        'area' => '',
                        'value' => 'ACTree.LI.tpl',
                    ),
                ),
            ));
            if ($prop_bootstrap_v3->save()) {
                $modx->log(xPDO::LOG_LEVEL_INFO,
                    '[getTables] Property set "getTables_bootstrap_v3" was created');
            } else {
                $modx->log(xPDO::LOG_LEVEL_ERROR,
                    '[getTables] Could not create property set "getTables_bootstrap_v3"');
            }

            if (!$prop_bootstrap_v4 = $modx->getObject('modPropertySet', array('name' => 'getTables_bootstrap_v4'))) {
                $prop_bootstrap_v4 = $modx->newObject('modPropertySet');
            }
            $prop_bootstrap_v4->fromArray(array(
                'name' => 'getTables_bootstrap_v4',
                'description' => 'Setting for getTables on bootstrap_v3',
                'properties' => array(
                    'getTableOuterTpl' => array(
                        'name' => 'getTableOuterTpl',
                        'desc' => 'getTableOuterTpl',
                        'type' => 'textfield',
                        'options' => array(),
                        'lexicon' => '',
                        'area' => '',
                        'value' => 'b4.getTable.outer.tpl',
                    ),
                    'getTableFilterTpl' => array(
                        'name' => 'getTableFilterTpl',
                        'desc' => 'getTableFilterTpl',
                        'type' => 'textfield',
                        'options' => array(),
                        'lexicon' => '',
                        'area' => '',
                        'value' => 'b4.getTable.Filter.tpl',
                    ),
                    'getTableNavTpl' => array(
                        'name' => 'getTableNavTpl',
                        'desc' => 'getTableNavTpl',
                        'type' => 'textfield',
                        'options' => array(),
                        'lexicon' => '',
                        'area' => '',
                        'value' => 'b4.getTable.nav.tpl',
                    ),
                    'getTableRowTpl' => array(
                        'name' => 'getTableRowTpl',
                        'desc' => 'getTableRowTpl',
                        'type' => 'textfield',
                        'options' => array(),
                        'lexicon' => '',
                        'area' => '',
                        'value' => 'getTable.row.tpl',
                    ),
                    'getTableEditRowTpl' => array(
                        'name' => 'getTableEditRowTpl',
                        'desc' => 'getTableEditRowTpl',
                        'type' => 'textfield',
                        'options' => array(),
                        'lexicon' => '',
                        'area' => '',
                        'value' => 'b4.getTable.EditRow.tpl',
                    ),
                    'getTableModalCreateUpdateTpl' => array(
                        'name' => 'getTableModalCreateUpdateTpl',
                        'desc' => 'getTableModalCreateUpdateTpl',
                        'type' => 'textfield',
                        'options' => array(),
                        'lexicon' => '',
                        'area' => '',
                        'value' => 'b4.getTable.Modal.CreateUpdate.tpl',
                    ),
                    'getTabsTpl' => array(
                        'name' => 'getTabsTpl',
                        'desc' => 'getTabsTpl',
                        'type' => 'textfield',
                        'options' => array(),
                        'lexicon' => '',
                        'area' => '',
                        'value' => 'b4.getTabs.tpl',
                    ),
                    'getTableActionTpl' => array(
                        'name' => 'getTableActionTpl',
                        'desc' => 'getTableActionTpl',
                        'type' => 'textfield',
                        'options' => array(),
                        'lexicon' => '',
                        'area' => '',
                        'value' => 'getTable.action.tpl',
                    ),
                    'getTableEditFormTpl' => array(
                        'name' => 'getTableEditFormTpl',
                        'desc' => 'getTableEditFormTpl',
                        'type' => 'textfield',
                        'options' => array(),
                        'lexicon' => '',
                        'area' => '',
                        'value' => 'b4.getTable.EditForm.tpl',
                    ),
                    'getTableFilterCheckboxTpl' => array(
                        'name' => 'getTableFilterCheckboxTpl',
                        'desc' => 'getTableFilterCheckboxTpl',
                        'type' => 'textfield',
                        'options' => array(),
                        'lexicon' => '',
                        'area' => '',
                        'value' => 'getTable.FilterCheckbox.tpl',
                    ),
                    'getTableModalRemoveTpl' => array(
                        'name' => 'getTableModalRemoveTpl',
                        'desc' => 'getTableModalRemoveTpl',
                        'type' => 'textfield',
                        'options' => array(),
                        'lexicon' => '',
                        'area' => '',
                        'value' => 'getTable.Modal.Remove.tpl',
                    ),
                    'getTableFormTpl' => array(
                        'name' => 'getTableFormTpl',
                        'desc' => 'getTableFormTpl',
                        'type' => 'textfield',
                        'options' => array(),
                        'lexicon' => '',
                        'area' => '',
                        'value' => 'b4.getTable.Form.tpl',
                    ),
                    'getTreeMainTpl' => array(
                        'name' => 'getTreeMainTpl',
                        'desc' => 'getTreeMainTpl',
                        'type' => 'textfield',
                        'options' => array(),
                        'lexicon' => '',
                        'area' => '',
                        'value' => 'getTree.Main.tpl',
                    ),
                    'getTreeULTpl' => array(
                        'name' => 'getTreeULTpl',
                        'desc' => 'getTreeULTpl',
                        'type' => 'textfield',
                        'options' => array(),
                        'lexicon' => '',
                        'area' => '',
                        'value' => 'getTree.UL.tpl',
                    ),
                    'getTreeLITpl' => array(
                        'name' => 'getTreeLITpl',
                        'desc' => 'getTreeLITpl',
                        'type' => 'textfield',
                        'options' => array(),
                        'lexicon' => '',
                        'area' => '',
                        'value' => 'getTree.LI.tpl',
                    ),
                    'getTreeModalTpl' => array(
                        'name' => 'getTreeModalTpl',
                        'desc' => 'getTreeModalTpl',
                        'type' => 'textfield',
                        'options' => array(),
                        'lexicon' => '',
                        'area' => '',
                        'value' => 'getTree.Modal.tpl',
                    ),
                    'getTreeModalRemoveTpl' => array(
                        'name' => 'getTreeModalRemoveTpl',
                        'desc' => 'getTreeModalRemoveTpl',
                        'type' => 'textfield',
                        'options' => array(),
                        'lexicon' => '',
                        'area' => '',
                        'value' => 'getTree.Modal.Remove.tpl',
                    ),
                    'getTreePanelTpl' => array(
                        'name' => 'getTreePanelTpl',
                        'desc' => 'getTreePanelTpl',
                        'type' => 'textfield',
                        'options' => array(),
                        'lexicon' => '',
                        'area' => '',
                        'value' => 'getTree.Panel.tpl',
                    ),
                    'ACTreeULTpl' => array(
                        'name' => 'ACTreeULTpl',
                        'desc' => 'ACTreeULTpl',
                        'type' => 'textfield',
                        'options' => array(),
                        'lexicon' => '',
                        'area' => '',
                        'value' => 'ACTree.UL.tpl',
                    ),
                    'ACTreeLITpl' => array(
                        'name' => 'ACTreeLITpl',
                        'desc' => 'ACTreeLITpl',
                        'type' => 'textfield',
                        'options' => array(),
                        'lexicon' => '',
                        'area' => '',
                        'value' => 'ACTree.LI.tpl',
                    ),
                ),
            ));
            if ($prop_bootstrap_v4->save()) {
                $modx->log(xPDO::LOG_LEVEL_INFO,
                    '[getTables] Property set "getTables_bootstrap_v4" was created');
            } else {
                $modx->log(xPDO::LOG_LEVEL_ERROR,
                    '[getTables] Could not create property set "getTables_bootstrap_v4"');
            }

            break;

        case xPDOTransport::ACTION_UNINSTALL:
            if ($prop_bootstrap_v3 = $modx->getObject('modPropertySet', array('name' => 'getTables_bootstrap_v3'))) {
                $prop_bootstrap_v3->remove();
            }
            if ($prop_bootstrap_v4 = $modx->getObject('modPropertySet', array('name' => 'getTables_bootstrap_v4'))) {
                $prop_bootstrap_v4->remove();
            }
            break;
    }
}

return true;