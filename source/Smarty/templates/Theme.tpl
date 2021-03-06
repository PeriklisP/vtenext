{* crmv@82419 *}
{* Template which includes all the theme related things *}
{* 
   Theme configuration is in theme.json file. This file is loaded and parsed to get the list of
   files to load.
*}

{if $THEME_PATH}
	{assign var="THEME_JSON" value="`$THEME_PATH`theme.json"}
	{assign var="THEME_ABS_ROOT" value="`$THEME_PATH`/"|realpath}
{elseif $THEME}
	{assign var="THEME_PATH" value="themes/`$THEME`/"}
	{assign var="THEME_JSON" value="themes/`$THEME`/theme.json"}
	{assign var="THEME_ABS_ROOT" value="themes/`$THEME`/"|realpath}
{else}
	<p>No theme specified!</p>
{/if}

{* This is the relative path for the resources *}
{if $PATH}
	{assign var="RELPATH" value=$PATH}
{else}
	{assign var="RELPATH" value=""}
{/if}


{if file_exists($THEME_JSON) }
	{* use the json file to extract files to be included, don't recalculate if already read *}
	{if !$THEME_JSON_INFO}
		{assign var="THEME_JSON_INFO" value=$THEME_JSON|file_get_contents|json_decode:true}
	{/if}
	
	{if $THEME_MODE == "head" && count($THEME_JSON_INFO.includes) > 0}
		
		{* CSS STYLES *}
		{if count($THEME_JSON_INFO.includes.css) > 0}
			{foreach item=css from=$THEME_JSON_INFO.includes.css}
				{if is_array($css) }
					{* array, there are options *}
					{if $css.active}
						{if $css.media}
							{assign var="CSSMEDIA" value=$css.media}
						{else}
							{assign var="CSSMEDIA" value="all"}
						{/if}
						<link rel="stylesheet" type="text/css" href="{$RELPATH}{$THEME_PATH}/{$css.file}" media="{$CSSMEDIA}">
					{/if}
				{else}
					{* simple string, just output it *}
					<link rel="stylesheet" type="text/css" href="{$RELPATH}{$THEME_PATH}/{$css}">
				{/if}
			
			{/foreach}
		{/if}
		
		{* JAVASCRIPT *}
		{if count($THEME_JSON_INFO.includes.js) > 0}
			{foreach item=js from=$THEME_JSON_INFO.includes.js}
				{if is_array($js) }
					{* array, there are options, none supported yet *}
					{if $js.active}
						<script language="JavaScript" type="text/javascript" src="{$RELPATH}{$THEME_PATH}/{$js.file}"></script>
					{/if}
				{else}
					<script language="JavaScript" type="text/javascript" src="{$RELPATH}{$THEME_PATH}/{$js}"></script>
				{/if}
			{/foreach}
		{/if}
		
	{/if}
	
	{if $THEME_MODE == "body" && count($THEME_JSON_INFO.body) > 0}

		{if count($THEME_JSON_INFO.body.insert) > 0}
			{foreach item=bodytpl from=$THEME_JSON_INFO.body.insert}
				{include file="file:`$THEME_ABS_ROOT`/`$bodytpl`"}
			{/foreach}
		{/if}
	{/if}
	
{else}
	{* fallback for old themes: try to load standard files *}
	{if $THEME_MODE == "head"}
		<link rel="stylesheet" href="{$RELPATH}{$THEME_PATH}/style.css">
		<link rel="stylesheet" href="{$RELPATH}{$THEME_PATH}/style_print.css" media="print">	{* crmv@27236 *}
	{/if}
{/if}

<script type="text/javascript">
	if (!window.current_theme) {ldelim}
		window.current_theme = '{$THEME}';
	{rdelim}
</script>
