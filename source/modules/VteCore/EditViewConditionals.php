<?php
/*+*************************************************************************************
 * The contents of this file are subject to the VTECRM License Agreement
 * ("licenza.txt"); You may not use this file except in compliance with the License
 * The Original Code is: VTECRM
 * The Initial Developer of the Original Code is VTECRM LTD.
 * Portions created by VTECRM LTD are Copyright (C) VTECRM LTD.
 * All Rights Reserved.
 ***************************************************************************************/
/* crmv@112297 crmv@113771 crmv@115268 */

global $currentModule, $app_strings, $mod_strings, $theme;
$crmvUtils = CRMVUtils::getInstance();

$tabid = getTabid($currentModule);
$focus = CRMEntity::getInstance($currentModule);

$_REQUEST = Zend_Json::decode($_REQUEST['form']);
setObjectValuesFromRequest($focus);

require_once('modules/SDK/src/29/29Utils.php');
$uitypeFileUtils = UitypeFileUtils::getInstance();
$uitypeFileUtils->uploadTempFiles();

$mode = $_REQUEST['mode'];
$record=$_REQUEST['record'];
if($mode) $focus->mode = $mode;
if($record) {
	$focus->id = $record;
	$focus->column_fields['record_id'] = $record;
    $focus->column_fields['record_module'] = $currentModule;
}

// crmv@64542
if (isInventoryModule($currentModule)) {
	$focus->column_fields['currency_id'] = $_REQUEST['inventory_currency'];
	$cur_sym_rate = getCurrencySymbolandCRate($_REQUEST['inventory_currency']);
	$focus->column_fields['conversion_rate'] = $cur_sym_rate['rate'];
}
// crmv@64542e

if($_REQUEST['assigntype'] == 'U') {
	$focus->column_fields['assigned_user_id'] = $_REQUEST['assigned_user_id'];
} elseif($_REQUEST['assigntype'] == 'T') {
	$focus->column_fields['assigned_user_id'] = $_REQUEST['assigned_group_id'];
}

// get fields informations
$fieldRows = array();
//$fieldWS = array();
$result = $adb->pquery("select * from {$table_prefix}_field where tabid = ?", array($tabid));
if ($result && $adb->num_rows($result) > 0) {
	while($row=$adb->fetchByAssoc($result)) {
		$fieldRows[$row['fieldname']] = $row;
		//$fieldWS[$row['fieldname']] = WebserviceField::fromArray($adb,$row);
	}
}
// format values
foreach($focus->column_fields as $fieldname => &$value) {
	$value = $crmvUtils->formatValue($value, $fieldRows[$fieldname], $_REQUEST);
}

require_once('Smarty_setup.php');
$smarty = new vtigerCRM_Smarty();
$smarty->assign('MODE',$focus->mode);
$smarty->assign('MODULE',$currentModule);
$smarty->assign('APP',$app_strings);
$smarty->assign('MOD',$mod_strings);
$smarty->assign("THEME", $theme);
$smarty->assign('IMAGE_PATH', "themes/$theme/images/");
$smarty->assign('ID', $focus->id);
// Gather the help information associated with fields
$smarty->assign('FIELDHELPINFO', vtlib_getFieldHelpInfo($currentModule));
if (!empty($record) && $currentModule == 'HelpDesk') {
	//Added to display the ticket comments information
	$smarty->assign("COMMENT_BLOCK",$focus->getCommentInformation($record));
}

$disp_view = getView($focus->mode);
$blocks_info = getBlocks($currentModule, $disp_view, $focus->mode, $focus->column_fields, '', $blockVisibility);
$blocks = array();
if (!empty($blocks_info)) {
	foreach($blocks_info as $header => $data) {
		$smarty->assign('data',$data['fields']);
		$smarty->assign('header',$data['label']);
		$blocks[$data['blockid']] = $smarty->fetch('DisplayFields.tpl');
	}
}

// Field Validation Information
$validationUitypes = array();
$validationData = getDBValidationData($focus->tab_name,$tabid,$otherInfo,$focus);
$validationArray = getValidationdataArray($validationData, $otherInfo);

$return = array(
	'BLOCKS' => $blocks,
	'BLOCKVISIBILITY' => $blockVisibility,
	'VALIDATION_DATA_FIELDNAME' => $validationArray['fieldname'],
	'VALIDATION_DATA_FIELDDATATYPE' => $validationArray['datatype'],
	'VALIDATION_DATA_FIELDLABEL' => $validationArray['fieldlabel'],
	'VALIDATION_DATA_FIELDUITYPE' => $validationArray['fielduitype'],
	'VALIDATION_DATA_FIELDWSTYPE' => $validationArray['fieldwstype'],
);

echo Zend_Json::encode($return);
exit;