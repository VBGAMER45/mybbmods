<?php


function LoadTraderSettings()
{
	global $traderSettings, $db, $mybb;
	
	if(!$db->table_exists("trader_settings"))
		return;
	
	$dbresult = $db->query("
		SELECT 
			`variable`, `value`
		FROM ".TABLE_PREFIX."trader_settings");
		
	$traderSettings = array();
	while ($row = $db->fetch_array($dbresult))
		$traderSettings[$row['variable']] = $row['value'];
		
	if (empty($traderSettings['trader_feedbackperpage']))
		$traderSettings['trader_feedbackperpage'] = 10;

}

function UpdateTraderSettings($changeArray)
{
	global $db, $traderSettings;

	if (empty($changeArray) || !is_array($changeArray))
		return;

	$replaceArray = array();
	foreach ($changeArray as $variable => $value)
	{
		if (isset($traderSettings[$variable]) && $traderSettings[$variable] == stripslashes($value))
			continue;

		elseif (!isset($traderSettings[$variable]) && empty($value))
			continue;

		$replaceArray[] = "(SUBSTRING('$variable', 1, 255), SUBSTRING('$value', 1, 65534))";
		$traderSettings[$variable] = stripslashes($value);
	}

	if (empty($replaceArray))
		return;

	$db->query("
		REPLACE INTO ".TABLE_PREFIX."trader_settings
			(variable, value)
		VALUES " . implode(',
			', $replaceArray));

}

function ezTrader_isAllowedTo($permission = '')
{
	global $mybb, $lang;
	
	$feedPermTitle = 'cannot_eztrader_' . $permission;
	if (ezTrader_allowedTo($permission) == false)
		ezTrader_fatal_error2($lang->$feedPermTitle);
}

function ezTrader_fatal_error2($errorMsg)
{
	global $page, $lang, $context, $errors;
	
	$context['page_title'] = $errorMsg;
	$errors = inline_error($errorMsg);
	eztrader_header();
	
	echo $errors;
	
	eztrader_footer();
	exit;
}

function ezTrader_allowedTo($permission = '')
{
	global $mybb, $traderPermissions;
	
	$permRow = $traderPermissions[$mybb->user['usergroup']];
	
	if ($permission == 'feedback')
		return $permRow['feedback'];
		
	if ($permission == 'deletefeed')
		return $permRow['deletefeed'];
		
	if ($permission == 'autorating')
		return $permRow['autorating'];

}

function TraderLoadPermissions()
{
	global $db, $traderPermissions;
	$dbresult = $db->query("
		SELECT 
		p.ID_GROUP,
		  p.feedback, p.deletefeed, p.autorating
		FROM ".TABLE_PREFIX."trader_permissions as p");
	$traderPermissions = array();
	while ($row = $db->fetch_array($dbresult))
		$traderPermissions[$row['ID_GROUP']] = $row;


}

?>