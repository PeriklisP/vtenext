<?php
/*********************************************************************************
** The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
*
 ********************************************************************************/
require_once('Smarty_setup.php');
require_once('include/database/PearDatabase.php');
require_once('include/CustomFieldUtil.php');

global $mod_strings,$app_strings,$theme,$table_prefix;
$smarty=new vtigerCRM_Smarty;
$smarty->assign("MOD",$mod_strings);
$smarty->assign("APP",$app_strings);
$theme_path="themes/".$theme."/";
$image_path=$theme_path."images/";
$smarty->assign("IMAGE_PATH", $image_path);
$module_array=getCustomFieldSupportedModules();

$cfimagecombo = Array($image_path."text.gif",
$image_path."number.gif",
$image_path."percent.gif",
$image_path."currency.gif",
$image_path."date.gif",
$image_path."email.gif",
$image_path."phone.gif",
$image_path."picklist.gif",
$image_path."url.gif",
$image_path."checkbox.gif",
$image_path."text.gif",
$image_path."picklist.gif");

$cftextcombo = Array($mod_strings['Text'],
$mod_strings['Number'],
$mod_strings['Percent'],
$mod_strings['Currency'],
$mod_strings['Date'],
$mod_strings['Email'],
$mod_strings['Phone'],
$mod_strings['PickList'],
$mod_strings['LBL_URL'],
$mod_strings['LBL_CHECK_BOX'],
$mod_strings['LBL_TEXT_AREA'],
$mod_strings['LBL_MULTISELECT_COMBO']
);

$smarty->assign("THEME", $theme);
$smarty->assign("MODULES",$module_array);
$smarty->assign("CFTEXTCOMBO",$cftextcombo);
$smarty->assign("CFIMAGECOMBO",$cfimagecombo);
if($_REQUEST['fld_module'] !='')
	$fld_module = $_REQUEST['fld_module'];
//crmv@62929
elseif($_REQUEST['formodule'] !='')
	$fld_module = $_REQUEST['formodule'];
//crmv@62929e
else{
	reset($module_array); // make sure array pointer is at first element
	$fld_module = key($module_array);
}
$smarty->assign("MODULE",$fld_module);
$smarty->assign("CFENTRIES",getCFListEntries($fld_module));
if(isset($_REQUEST["duplicate"]) && $_REQUEST["duplicate"] == "yes")
{
	$error=$mod_strings['ERR_CUSTOM_FIELD_WITH_NAME']. $_REQUEST["fldlabel"] .$mod_strings['ERR_ALREADY_EXISTS'] . ' ' .$mod_strings['ERR_SPECIFY_DIFFERENT_LABEL'];
	$smarty->assign("DUPLICATE_ERROR", $error);
}

if($_REQUEST['mode'] !='')
	$mode = $_REQUEST['mode'];
$smarty->assign("MODE", $mode);

if($_REQUEST['ajax'] != 'true')
	$smarty->display('CustomFieldList.tpl');	
else
	$smarty->display('CustomFieldEntries.tpl');

	/**
	* Function to get customfield entries
	* @param string $module - Module name
	* return array  $cflist - customfield entries
	*/
function getCFListEntries($module)
{
	global $adb,$app_strings,$theme,$smarty,$log,$table_prefix;
	$tabid = getTabid($module);
	if ($module == 'Calendar') {
		$tabid = array(9, 16);
	}
	$theme_path="themes/".$theme."/";
	$image_path="themes/images/";
	$dbQuery = "select fieldid,columnname,fieldlabel,uitype,displaytype,block,".$table_prefix."_convertleadmapping.cfmid,tabid from ".$table_prefix."_field left join ".$table_prefix."_convertleadmapping on  ".$table_prefix."_convertleadmapping.leadfid = ".$table_prefix."_field.fieldid where tabid in (". generateQuestionMarks($tabid) .") and ".$table_prefix."_field.presence in (0,2) and generatedtype = 2 order by sequence";
	$result = $adb->pquery($dbQuery, array($tabid));
	$row = $adb->fetch_array($result);
	$count=1;
	$cflist=Array();
	if($row!='')
	{
		do
		{
			$cf_element=Array();
			$cf_element['no']=$count;
			$cf_element['label']=getTranslatedString($row["fieldlabel"],$module);
			$fld_type_name = getCustomFieldTypeName($row["uitype"]);
			$cf_element['type']=$fld_type_name;
			$cf_tab_id = $row["tabid"];
			if($module == 'Leads')
			{
				$mapping_details = getListLeadMapping($row["cfmid"]);
				$cf_element[]= $mapping_details['accountlabel'];
				$cf_element[]= $mapping_details['contactlabel'];
				$cf_element[]= $mapping_details['potentiallabel'];
			}
			if($module == 'Calendar')
			{
				if ($cf_tab_id == '9')
					$cf_element['activitytype'] = getTranslatedString('Task',$module);
				else
					$cf_element['activitytype'] = getTranslatedString('Event',$module);
			}
			$cf_element['tool']='&nbsp;<img style="cursor:pointer;" onClick="deleteCustomField('.$row["fieldid"].',\''.$module.'\', \''.$row["columnname"].'\', \''.$row["uitype"].'\')" src="'. vtiger_imageurl('delete.gif', $theme) .'" border="0"  alt="'.$app_strings['LBL_DELETE_BUTTON_LABEL'].'" title="'.$app_strings['LBL_DELETE_BUTTON_LABEL'].'"/></a>';
			$cflist[] = $cf_element;
			$count++;
		}while($row = $adb->fetch_array($result));
	}
	return $cflist;
}

/**
* Function to Lead customfield Mapping entries
* @param integer  $cfid   - Lead customfield id
* return array    $label  - customfield mapping
*/
function getListLeadMapping($cfid)
{
	global $adb,$table_prefix;
	$sql="select * from ".$table_prefix."_convertleadmapping where cfmid =?";
	$result = $adb->pquery($sql, array($cfid));
	$noofrows = $adb->num_rows($result);
	for($i =0;$i <$noofrows;$i++)
	{
		$leadid = $adb->query_result($result,$i,'leadfid');
		$accountid = $adb->query_result($result,$i,'accountfid');
		$contactid = $adb->query_result($result,$i,'contactfid');
		$potentialid = $adb->query_result($result,$i,'potentialfid');
		$cfmid = $adb->query_result($result,$i,'cfmid');

		$sql2="select fieldlabel from ".$table_prefix."_field where fieldid =?";
		$result2 = $adb->pquery($sql2, array($accountid));
		$accountfield = $adb->query_result($result2,0,'fieldlabel');
		$label['accountlabel'] = $accountfield;
		
		$sql3="select fieldlabel from ".$table_prefix."_field where fieldid =?";
		$result3 = $adb->pquery($sql3, array($contactid));
		$contactfield = $adb->query_result($result3,0,'fieldlabel');
		$label['contactlabel'] = $contactfield;
		$sql4="select fieldlabel from ".$table_prefix."_field where fieldid =?";
		$result4 = $adb->pquery($sql4, array($potentialid));
		$potentialfield = $adb->query_result($result4,0,'fieldlabel');
		$label['potentiallabel'] = $potentialfield;
	}
	return $label;
}

/* function to get the modules supports Custom Fields
*/

function getCustomFieldSupportedModules()
{
	global $adb,$table_prefix;
	// vtlib customization: Do not list disabled modules.
	//$sql="select distinct vtiger_field.tabid,name from vtiger_field inner join vtiger_tab on vtiger_field.tabid=vtiger_tab.tabid where vtiger_field.tabid not in(9,10,16,15,8,29)";
	$sql="select distinct ".$table_prefix."_field.tabid,name from ".$table_prefix."_field inner join ".$table_prefix."_tab on ".$table_prefix."_field.tabid=".$table_prefix."_tab.tabid where ".$table_prefix."_field.tabid not in(10,16,15,8,29) and ".$table_prefix."_tab.presence != 1"; // 16 is still here as we do not want duplicates for Calendar - Both 9 and 16 point to Calendar itself
	// END
	$result = $adb->pquery($sql, array());
	while($moduleinfo=$adb->fetch_array($result))
	{
		$modulelist[$moduleinfo['name']] = $moduleinfo['name'];
	}
	return $modulelist;
}
?>
