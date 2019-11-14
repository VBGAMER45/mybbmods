<?php
/*
Recent Topics
by: vbgamer45
http://www.mybbhacks.com
Copyright 2011  MyBBHacks.com

############################################
License Information:

Links to http://www.mybbhacks.com must remain unless
branding free option is purchased.
#############################################
*/
if(!defined('IN_MYBB'))
	die('This file cannot be accessed directly.');

$plugins->add_hook("index_end", "recenttopicsindex_show");

function recenttopicsindex_info()
{

	return array(
		"name"		=> "Recent Topics Index Page",
		"description"		=> "Adds Recent Topics to the index page",
		"website"		=> "http://www.mybbhacks.com",
		"author"		=> "vbgamer45",
		"authorsite"		=> "http://www.mybbhacks.com",
		"version"		=> "1.0.2",
		"guid" 			=> "3244972b3e44b82b52b12594a6af9261",
		"compatibility"	=> "1*"
		);
}


function recenttopicsindex_install()
{
	global $mybb, $db;
	// Create Tables/Settings
	$db->query("INSERT  INTO ".TABLE_PREFIX."settings (sid, name, title, description, optionscode, value, disporder, gid) VALUES (NULL, 'recenttopicslimit', 'Recent Topics To Show', 'The number of recent topics you wish to display on the main index page', 'text', '10', 1, 6);");

	rebuild_settings();


}

function recenttopicsindex_is_installed()
{
	global $db;
	$query = $db->write_query("SELECT * FROM " . TABLE_PREFIX . "settings WHERE `name` = 'recenttopicslimit'");

	if($db->num_rows($query) > 0)
		return true;
	else
		return false;
}

function recenttopicsindex_uninstall()
{
	global $mybb, $db;

	$db->query("DELETE FROM ".TABLE_PREFIX."settings WHERE name = 'recenttopicslimit'");

	rebuild_settings();
}


function recenttopicsindex_activate()
{
  require_once MYBB_ROOT."/inc/adminfunctions_templates.php";

  $returnStatus1 = find_replace_templatesets("index", "#".preg_quote('{$forums}') . "#i", '{$forums}' . "\n" . '{$recenttopics}');


}

function recenttopicsindex_deactivate()
{
  require_once MYBB_ROOT."/inc/adminfunctions_templates.php";

  $returnStatus1 = find_replace_templatesets(
  "index", "#".preg_quote('{$forums}' . "\n" . '{$recenttopics}') . "#i",
  '{$forums}',0);


}

function recenttopicsindex_show()
{
	global $db, $mybb, $page, $recenttopics, $theme, $lang, $permissioncache;

	$lang->load('recenttopicsindex');

	require_once MYBB_ROOT."inc/functions_search.php";

	if (empty($mybb->settings['recenttopicslimit']))
		$mybb->settings['recenttopicslimit'] = 10;

	$recenttopics .= '<table border="0" cellspacing="' . $theme['borderwidth'] . '" cellpadding="' . $theme['tablespace'] . '" class="tborder">
<thead>
<tr>
<td class="thead" colspan="2">

<div><strong>' . $lang->recenttopics . '</strong></div>
</td>
</tr>
</thead>';

	// Run the Query
	
    // !!! FIX private forum exposure!!!
   if ( !is_array($permissioncache) ||(is_array($permissioncache) && ((count($permissioncache)==1) && (isset($permissioncache['-1']) && ($permissioncache['-1'] = "1"))))) 
       $permissioncache = forum_permissions();

	$unsearchforums = get_unsearchable_forums();
	if($unsearchforums)
		$where_sql .= " AND t.fid NOT IN ($unsearchforums)";

	$inactiveforums = get_inactive_forums();
	if ($inactiveforums)
		$where_sql .= " AND t.fid NOT IN ($inactiveforums)";


	$query = $db->query("
	SELECT
		t.tid, t.fid, t.subject, t.lastposteruid, t.lastposter, t.lastpost, f.name
	FROM ".TABLE_PREFIX."threads as t,  ".TABLE_PREFIX."forums as f
	WHERE f.fid = t.fid AND t.visible = 1 $where_sql
	ORDER BY t.lastpost DESC LIMIT " . $mybb->settings['recenttopicslimit']);
	while($threadRow = $db->fetch_array($query))
	{
		$recenttopics .= '<tr>';
		$subject = my_substr($threadRow['subject'], 0, 50);
		$subject = htmlspecialchars_uni($subject);
		$postdate = my_date($mybb->settings['dateformat'], $threadRow['lastpost']);
		$posttime = my_date($mybb->settings['timeformat'], $threadRow['lastpost']);

		$recenttopics .= '<td class="trow1">
		<a href="showthread.php?tid=' . $threadRow['tid'] . '&action=lastpost">' . $subject .'</a> '  . $lang->recenttopics_by . (!empty($threadRow['lastposteruid']) ? ' <a href="member.php?action=profile&uid=' . $threadRow['lastposteruid'] . '">' . $threadRow['lastposter'] . '</a>' : $threadRow['lastposter']) . ' (<a href="forumdisplay.php?fid=' . $threadRow['fid'] . '">' . $threadRow['name'] . '</a>)
		</td>
		<td class="trow1">' .
		$postdate . ' ' . $posttime . '
		</td>
		</tr>';
	}


	$recenttopics .= "</table><br />";



}


?>