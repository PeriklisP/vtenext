<?php
/*
 * $sdk_value = $value � il valore del dato
 */
global $sdk_mode;
$imgdir = 'modules/SDK/examples/uitypeSocial/img/';
switch($sdk_mode) {
	case 'detail':
		$label_fld[] = getTranslatedString($fieldlabel,$module);
		$label_fld[] = $col_fields[$fieldname];
		break;
	case 'edit':
		$editview_label[] = getTranslatedString($fieldlabel, $module_name);
		$fieldvalue[] = $value; // posso modificare il parametro prima che venga inserito nella textbox per la modifica
		break;
	case 'relatedlist':
//		$value = '<span style="color: green; font-weight: bold;">'.$sdk_value.'</span>';
//		break;
	case 'list':
		if (!empty($sdk_value))
		  $value = '<a target="_blank" href="http://twitter.com/'.$sdk_value.'"><img src='.$imgdir.'twico.png" align="left" alt="Twitter" title="Twitter" />&nbsp;'.$sdk_value.'</a>';
		else
			$value = '';
		break;
}
?>