<?php
/*
Who Viewed Topic
by: vbgamer45
http://www.mybbhacks.com
Copyright 2010  MyBBHacks.com

############################################
License Information:

Links to http://www.mybbhacks.com must remain unless
branding free option is purchased.
#############################################
*/
ini_set("display_errors",1);
if(!defined('IN_MYBB'))
	die('This file cannot be accessed directly.');

$plugins->add_hook("misc_start", "whoviewedtopic_show");
$plugins->add_hook("showthread_start","whoviewedtopic_createlink");


function whoviewedtopic_info()
{

	return array(
		"name"		=> "Who Viewed Topic",
		"description"		=> "Adds a link on topic display for forum moderators to view who viewed that topic",
		"website"		=> "http://www.mybbhacks.com",
		"author"		=> "vbgamer45",
		"authorsite"		=> "http://www.mybbhacks.com",
		"version"		=> "1.0.1",
		"guid" 			=> "38819d15b01927e01bfdadaa8df2089a",
		"compatibility"	=> "1*"
		);
}


function whoviewedtopic_install()
{
	global $db;

	$data = '
	<html>
	<head>
	<title>{$mybb->settings[bbname]} - {$pagetitle}</title>
	{$headerinclude}
	</head>
	<body>
	{$header}
	<table border="0" cellspacing="1" cellpadding="4" class="tborder">
		<thead>
		<tr>
			<td class="thead" colspan="2"  align="center">
			{$pagetitle}
			</td>
		</tr>
	</thead>
	<tbody>
	<tr>
		<td class="tcat"><span class="smalltext"><strong>{$lang->whoviewedtopic_username}</strong></span></td>
		<td class="tcat"><span class="smalltext"><strong>{$lang->whoviewedtopic_date}</strong></span></td>
	</tr>


	{$viewerlist}

	{$pagingList}
	</tbody>
	</table>


	{$footer}
	</body>
	</html>';


	$db->insert_query("templates", array(
		"title" => 'whoviewedtopic',
		"template" => $data,
		"sid" => "-1",
		"version" => "1.0",
		"dateline" => TIME_NOW
	));

}

function whoviewedtopic_is_installed()
{
	global $db;
	$query = $db->write_query("SELECT * FROM " . TABLE_PREFIX . "templates WHERE `title` = 'whoviewedtopic'");

	if ($db->num_rows($query) > 0)
		return true;
	else
		return false;
}

function whoviewedtopic_uninstall()
{
	global $db;

	$db->delete_query("templates", "`title` = 'whoviewedtopic'");

}

function whoviewedtopic_activate()
{
  require_once MYBB_ROOT."/inc/adminfunctions_templates.php";

  find_replace_templatesets("showthread", "#".preg_quote('{$add_remove_subscription_text}</a></li>') . "#i", '{$add_remove_subscription_text}</a></li>{$pluginwhoviewedtopic}');

}

function whoviewedtopic_deactivate()
{
  require_once MYBB_ROOT."/inc/adminfunctions_templates.php";

  find_replace_templatesets(
  "showthread", "#".preg_quote('{$add_remove_subscription_text}</a></li>{$pluginwhoviewedtopic}') . "#i",
  '{$add_remove_subscription_text}</a></li>',0);


}



function whoviewedtopic_loadlanguage()
{
	global $lang;

	$lang->load('whoviewedtopic');

}

function whoviewedtopic_show()
{
	global $db, $mybb, $lang, $pluginwhoviewedtopic, $headerinclude, $page, $errors, $templates, $templatelist, $footer, $header;


	whoviewedtopic_loadlanguage();

	if ($mybb->input['action'] == 'viewers')
	{

		$tid = (int) $_REQUEST['tid'];

		if (empty($tid))
		{
			error($lang->whoviewedtopic_notopic);

		}
		else
		{


			$query = $db->query("
			SELECT
				fid, subject
			FROM ".TABLE_PREFIX."threads
			WHERE tid = $tid");
			$topicRow = $db->fetch_array($query);

			// Check moderator permissions
			if (is_moderator($topicRow['fid']) && !empty($topicRow['fid']))
			{
				$viewerlist = '';
				add_breadcrumb($lang->whoviewedtopic_title);
				$pagetitle = $lang->whoviewedtopic_title . " - " . $topicRow['subject'];


				$query = $db->simple_select("threadsread", "COUNT(*) AS total", "tid = $tid");
				$viewedCount = $db->fetch_array($query);

				$perpage = 25;
				$page = intval($mybb->input['page']);

				if(intval($mybb->input['page']) > 0)
				{
					$start = ($page-1) *$perpage;
				}
				else
				{
					$start = 0;
					$page = 1;
				}


				$pagingList = multipage($viewedCount['total'], $perpage, $page, "misc.php?action=viewers&tid=$tid");


				$query = $db->query("
				SELECT
					r.dateline, u.username, g.namestyle, u.uid
				FROM (".TABLE_PREFIX."threadsread as r, ".TABLE_PREFIX."users as u)
				LEFT JOIN ".TABLE_PREFIX."usergroups as g ON (g.gid = u.usergroup)
				WHERE r.uid = u.uid AND r.tid = $tid ORDER BY r.dateline DESC LIMIT $start,$perpage");
				while($viewerRow = $db->fetch_array($query))
				{
					$vdate = my_date($mybb->settings['dateformat'], $viewerRow['dateline']);
					$vtime = my_date($mybb->settings['timeformat'], $viewerRow['dateline']);

					$viewerlist .='<tr>
					<td class="trow1">
					<a href="member.php?action=profile&uid=' . $viewerRow['uid'] . '">' . str_replace("{username}",$viewerRow['username'],$viewerRow['namestyle']) . '</a>
					</td>
					<td class="trow1">
					' . $vdate . ' ' . $vtime . '
					</td>
					</tr>';
				}


				eval("\$whoviewedtopic = \"".$templates->get("whoviewedtopic")."\";");
				output_page($whoviewedtopic);



			}
			else
			{

				error($lang->whoviewedtopic_nopermissions);

			}
		}
	}

}

function whoviewedtopic_createlink()
{
	global $ismod, $lang, $pluginwhoviewedtopic, $tid;

	whoviewedtopic_loadlanguage();


	$pluginwhoviewedtopic = '';

	if ($ismod == true)
		$pluginwhoviewedtopic = '<li style="background: url(\'images/old_pm.gif\') no-repeat 0px 0px;"><a href="misc.php?action=viewers&tid=' . $tid . '">' . $lang->whoviewedtopic_title . '</a></li>';



}


?>