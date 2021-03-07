<?php
/*
eztrader
by: vbgamer45
http://www.mybbhacks.com
Copyright 2011-2020  MyBBHacks.com

############################################
License Information:

Links to http://www.mybbhacks.com must remain unless
branding free option is purchased.
#############################################
*/
if(!defined('IN_MYBB'))
	die('This file cannot be accessed directly.');

$plugins->add_hook('admin_config_action_handler','eztrader_admin_action');
$plugins->add_hook('admin_config_menu','eztrader_admin_config_menu');

$plugins->add_hook('admin_load','eztrader_admin');
$plugins->add_hook("build_friendly_wol_location_end", "eztrader_whosonline");
$plugins->add_hook("global_start", "eztrader_loadmainlanguage");
$plugins->add_hook('postbit','ezTrader_ShowThreadDisplay');
$plugins->add_hook('member_profile_end','ezTrader_ProfileDisplay');


function eztrader_info()
{

	return array(
		"name"		=> "ezTrader",
		"description"		=> "Trader Rating System for MyBB",
		"website"		=> "https://www.mybbhacks.com",
		"author"		=> "vbgamer45",
		"authorsite"		=> "https://www.mybbhacks.com",
		"version"		=> "1.5",
		"compatibility"	=> "18*",
		"guid" => "76ae0d95bfd978369edf8cf65c6afa96",
		);
}


function eztrader_install()
{
	global $db, $charset;

	$db->query("CREATE TABLE IF NOT EXISTS ".TABLE_PREFIX."trader_feedback
(feedbackid int(11) NOT NULL auto_increment,
ID_MEMBER mediumint(8) unsigned NOT NULL default '0',
comment_short tinytext NOT NULL,
comment_long text, topicurl tinytext,
saletype tinyint(4) NOT NULL default '0',
salevalue tinyint(4) NOT NULL default '0',
saledate int(11) NOT NULL default '0',
FeedBackMEMBER_ID mediumint(8) unsigned NOT NULL default '0',
approved tinyint(4) NOT NULL default '1',
ID_LISTING int(11) NOT NULL default '0',
KEY ID_LISTING (ID_LISTING),
KEY ID_MEMBER (ID_MEMBER),
PRIMARY KEY  (feedbackid))");


	$db->query("CREATE TABLE IF NOT EXISTS ".TABLE_PREFIX."trader_permissions
	(
	ID_GROUP int(11),
	`feedback` tinyint(1) default 0,
	`deletefeed` tinyint(1) default 0,
	`autorating` tinyint(1) default 0,

	PRIMARY KEY  (ID_GROUP))");

	// Guests
	$db->query("INSERT IGNORE INTO ".TABLE_PREFIX."trader_permissions VALUES (1,0,0,0)");
	// Reg Members
	$db->query("INSERT IGNORE INTO ".TABLE_PREFIX."trader_permissions VALUES (2,1,0,0)");
	// Super Mod
	$db->query("INSERT IGNORE INTO ".TABLE_PREFIX."trader_permissions VALUES (3,1,0,1)");
	// Administrator
	$db->query("INSERT IGNORE INTO ".TABLE_PREFIX."trader_permissions VALUES (4,1,1,1)");
	// Awaiting Activation
	$db->query("INSERT IGNORE INTO ".TABLE_PREFIX."trader_permissions VALUES (5,0,0,0)");
	// Moderators
	$db->query("INSERT IGNORE INTO ".TABLE_PREFIX."trader_permissions VALUES (6,1,0,0)");
	// Banned
	$db->query("INSERT IGNORE INTO ".TABLE_PREFIX."trader_permissions VALUES (7,0,0,0)");



	$db->query("CREATE TABLE IF NOT EXISTS ".TABLE_PREFIX."trader_settings (
	  variable tinytext NOT NULL,
	  value text NOT NULL,
	  PRIMARY KEY (variable(30))
	) Engine=MyISAM");


	// Insert the settings
	$db->query("INSERT IGNORE INTO ".TABLE_PREFIX."trader_settings VALUES ('trader_approval', '0')");
	$db->query("INSERT IGNORE INTO ".TABLE_PREFIX."trader_settings VALUES ('trader_feedbackperpage', '10')");
	$db->query("INSERT IGNORE INTO ".TABLE_PREFIX."trader_settings VALUES ('trader_use_pos_neg', '0')");


	$db->query("INSERT IGNORE INTO ".TABLE_PREFIX."trader_settings VALUES ('eztrader_version', '1.0')");



}

function eztrader_is_installed()
{
	// Not needed for this plugin
	global $db;


	if($db->table_exists("trader_settings"))
	{
		$query = $db->query("
				SELECT
					value
				FROM ".TABLE_PREFIX."trader_settings

				WHERE variable = 'eztrader_version'

			");
		$row = $db->fetch_array($query);


		if (!empty($row['value']))
			return true;
		else
			return false;


	}
	return false;
}


function eztrader_uninstall()
{
	global $db;


	if($db->table_exists("trader_settings"))
	{
		// Delete the version
		$db->query("DELETE FROM ".TABLE_PREFIX."trader_settings WHERE variable = 'eztrader_version'");

	}


}


function eztrader_activate()
{

}

function eztrader_deactivate()
{


}

function eztrader_admin_action(&$action)
{
	$action['eztrader'] = array('active'=>'eztrader');
}

function eztrader_admin_config_menu(&$admim_menu)
{
	global $lang;

	// Load Language file
	eztrader_loadlanguage();

	end($admim_menu);

	$key = (key($admim_menu)) + 10;

	$admim_menu[$key] = array
	(
		'id' => 'eztrader',
		'title' => $lang->eztrader_title,
		'link' => 'index.php?module=config/eztrader'
	);

}

function eztrader_loadlanguage()
{
	global $lang;

	$lang->load('eztrader');

}

function eztrader_admin()
{
	global $lang, $mybb, $db, $page, $tabs, $plugins;

	if ($page->active_action != 'eztrader')
		return false;


	// Load Language file
	eztrader_loadlanguage();

	require_once MYBB_ROOT.'inc/eztrader.lib.php';

	LoadTraderSettings();


	$page->add_breadcrumb_item($lang->eztrader_title);

	// Create Admin Tabs
	$tabs['eztrader_settings'] = array
		(
			'title' => $lang->eztrader_admin,
			'link' => 'index.php?module=config/eztrader&action=adminset',
			'description' => $lang->eetrader_admin_description
		);

	$tabs['eztrader_approvelist'] = array
		(
			'title' => $lang->eztrader_trader_approval,
			'link' => 'index.php?module=config/eztrader&action=approvelist',
			'description' => $lang->eztrader_trader_approval_description,
		);

	$tabs['eztrader_permissions'] = array
		(
			'title' => $lang->eztrader_text_permissions,
			'link' => 'index.php?module=config/eztrader&action=permissions',
			'description' => $lang->eztrader_permissions_description
		);


	// ezTrader Actions
	$subActions = array(

		'adminset'=> 'ezTrader_AdminSettings',
		'adminset2'=> 'ezTrader_AdminSettings2',
		'approvelist' => 'ezTrader_ApproveList',
		'approve' => 'ezTrader_ApproveRating',
		'bulkactions' => 'ezTrader_BulkActions',

		'permissions' => 'ezTrader_Permissions',
		'permissions2' => 'ezTrader_Permissions2',
	);

	$plugins->run_hooks("gallery_admin_subactions");


	// Follow the sa or just go to main function
	@$sa = $mybb->input['action'];
	if (!empty($subActions[$sa]))
		$subActions[$sa]();
	else
		ezTrader_AdminSettings();


}

function ezTrader_AdminSettings()
{
	global $lang, $page, $traderSettings;

	$page->output_header($lang->eztrader_text_title . ' - ' . $lang->eztrader_text_settings);

	DoTraderAdminTabs();
echo '
			<form method="post" action="index.php?module=config/eztrader&action=adminset2">';
				$table = new Table;


				$table->construct_cell($lang->eztrader_trader_approval);
				$table->construct_cell('<input type="checkbox" name="trader_approval" ' . ($traderSettings['trader_approval'] ? ' checked="checked" ' : '') . ' />');
				$table->construct_row();

				$table->construct_cell($lang->trader_feedbackperpage);
				$table->construct_cell('<input type="text" name="trader_feedbackperpage" value="' .  $traderSettings['trader_feedbackperpage'] . '" />');
				$table->construct_row();

				$table->construct_cell($lang->trader_use_pos_neg);
				$table->construct_cell('<input type="text" name="trader_use_pos_neg" value="' .  $traderSettings['trader_use_pos_neg'] . '" />');
				$table->construct_row();

				$table->construct_cell('<input type="submit" name="savesettings" value="' . $lang->eztrader_save_settings .'" />', array('colspan' => 2));

				$table->construct_row();

				$table->output($lang->eztrader_text_settings);

				echo '
			</form>


			<br />
			<b>' . $lang->eztrader_text_permissions . '</b><br/><span class="smalltext">' . $lang->eztrader_set_permissionnotice . '</span>
			<br /><a href="index.php?module=config/eztrader&action=permissions">' . $lang->eztrader_set_editpermissions  . '</a>

<b>Has ezTrader helped you?</b> Then support the developers:<br />
    <form action="https://www.paypal.com/cgi-bin/webscr" method="post">
	<input type="hidden" name="cmd" value="_xclick">
	<input type="hidden" name="business" value="sales@visualbasiczone.com">
	<input type="hidden" name="item_name" value="ezTrader">
	<input type="hidden" name="no_shipping" value="1">
	<input type="hidden" name="no_note" value="1">
	<input type="hidden" name="currency_code" value="USD">
	<input type="hidden" name="tax" value="0">
	<input type="hidden" name="bn" value="PP-DonationsBF">
	<input type="image" src="https://www.paypal.com/en_US/i/btn/x-click-butcc-donate.gif" border="0" name="submit" alt="Make payments with PayPal - it is fast, free and secure!" />
	<img alt="" border="0" src="https://www.paypal.com/en_US/i/scr/pixel.gif" width="1" height="1">
</form>
';

	$page->output_footer();

}


function ezTrader_AdminSettings2()
{


	// Get the settings
	$trader_approval= isset($_REQUEST['trader_approval']) ? 1 : 0;
	$trader_feedbackperpage =  (int) $_REQUEST['trader_feedbackperpage'];
	$trader_use_pos_neg =  (int) $_REQUEST['trader_use_pos_neg'];


	UpdateTraderSettings(
	array(
	'trader_approval' => $trader_approval,
	'trader_feedbackperpage' => $trader_feedbackperpage,
	'trader_use_pos_neg' => $trader_use_pos_neg,

	));

	admin_redirect('index.php?module=config/eztrader&action=admin');

}


function ezTrader_ApproveList()
{
	global $context, $lang, $db, $page, $mybb, $traderSettings;

	$page->output_header($lang->eztrader_text_title . ' - ' . $lang->eztrader_trader_approval);

	DoTraderAdminTabs('eztrader_approvelist');

	$dbresult = $db->query("
		  	SELECT
	f.saletype, f.feedbackid, f.FeedBackMEMBER_ID,  f.topicurl, f.comment_short,
	f.salevalue, f.saledate, m.username, f.ID_MEMBER, u.username mainName
	FROM (".TABLE_PREFIX."trader_feedback AS f)
	LEFT JOIN ".TABLE_PREFIX."users AS m ON (f.FeedBackMEMBER_ID = m.uid)
	LEFT JOIN ".TABLE_PREFIX."users AS u ON (f.ID_MEMBER= u.uid)
	WHERE f.approved = 0 ");
	$context['eztrader_approve_list'] = array();
	while($row = $db->fetch_array($dbresult))
	{
		$context['eztrader_approve_list'][] = $row;
	}

	echo '<form method="post" action="index.php?module=config/eztrader&action=bulkactions">';
	$table = new Table;
	$table->construct_header("");
	$table->construct_header($lang->eztrader_rating);
	$table->construct_header($lang->eztrader_comment);
	$table->construct_header($lang->eztrader_to);
	$table->construct_header($lang->eztrader_from);
	$table->construct_header($lang->eztrader_detail);
	$table->construct_header($lang->eztrader_date);
	$table->construct_header($lang->eztrader_options);

	require_once MYBB_ROOT."inc/class_parser.php";
		$parser = new postParser();
		$parser_options = array(
			"allow_html" => 1,
			"allow_mycode" => 1,
			"allow_smilies" => 1,
			"allow_imgcode" => 1,
			"filter_badwords" => 1
		);

			// List all the unapproved pictures

			foreach($context['eztrader_approve_list'] as $row)
			{


				$table->construct_cell('<input type="checkbox" name="ratings[]" value="' . $row['feedbackid'] .'" />');

				switch($row['salevalue'])
				{

					case 0:
						$table->construct_cell('<img src="' . $mybb->settings['bburl'] . '/images/smilies/smile.gif" alt="positive" />');

					break;
					case 1:
						 $table->construct_cell('<img src="' . $mybb->settings['bburl'] . '/images/smilies/undecided.gif" alt="netural" />');
					break;
					case 2:
						$table->construct_cell('<img src="' . $mybb->settings['bburl'] . '/images/smilies/angry.gif" alt="negative" />');
					break;
					default:
						$table->construct_cell($row['salevalue']);
					break;
				}

				if($row['topicurl'] == '')
					$table->construct_cell($parser->parse_message($row['comment_short'],$parser_options));
				else
					$table->construct_cell('<a href="' . $row['topicurl'] . '">' . $parser->parse_message($row['comment_short'],$parser_options) . '</a>');

				$mtype = ' ';
				switch($row['saletype'])
				{
					case 0:
						$mtype = $lang->eztrader_buyer;
					break;
					case 1:
						$mtype = $lang->eztrader_seller;
					break;
					case 2:
						$mtype = $lang->eztrader_trade;
					break;
					default:
					$mtype = '';
					break;
				}

				$table->construct_cell('<a href="' . $mybb->settings['bburl'] . '/member.php?action=profile&uid=' . $row['ID_MEMBER'] . '">' . $row['mainName'] . '</a>');
				$table->construct_cell($mtype . '&nbsp;<a href="' . $mybb->settings['bburl'] . '/member.php?action=profile&uid=' . $row['FeedBackMEMBER_ID'] . '">' . $row['username'] . '</a>');
				$table->construct_cell('<a href="' . $mybb->settings['bburl'] . '/eztrader.php?sa=detail&feedid=' . $row['feedbackid'] . '">' . $lang->eztrader_viewdetail .'</a>');
				$table->construct_cell(my_date($mybb->settings['dateformat'], $row['saledate']));
				$table->construct_cell('<a href="index.php?module=config/eztrader&action=approve&id=' . $row['feedbackid'] .  '">' . $lang->eztrader_approve . '</a>
				<br /><br /><a href="' . $mybb->settings['bburl'] . '/eztrader.php?sa=delete&feedid=' . $row['feedbackid'] .  '">' . $lang->eztrader_delete . '</a>');


				$table->construct_row();


			}

			$table->construct_cell($lang->eztrader_text_withselected .'</b>

					<select name="doaction">
					<option value="approve">' .$lang->eztrader_bulk_approveratings .'</option>
					<option value="delete">' .$lang->eztrader_bulk_deleteratings .'</option>
					</select>
					<input type="submit" value="' . $lang->eztrader_text_performaction .'" />',array('colspan'=>8));
			$table->construct_row();


	$table->output($lang->eztrader_trader_approval);
	echo '</form>';

	$page->output_footer();

}



function DoTraderAdminTabs($selectedTab = 'eztrader_settings')
{
	global $page, $tabs;

	$page->output_nav_tabs($tabs, $selectedTab);
}

function ezTrader_Permissions()
{
	global $page, $db, $lang;


	$page->output_header($lang->eztrader_text_title . ' - ' . $lang->eztrader_text_permissions);


	DoTraderAdminTabs('eztrader_permissions');

	echo '
<form method="post" name="frmpermissions" action="index.php?module=config/eztrader&action=permissions2">
';

	$table = new Table;

	$table->construct_header($lang->eztrader_membergroup);
	$table->construct_header($lang->permissionname_eztrader_feedback);
	$table->construct_header($lang->permissionname_eztrader_deletefeed);
	$table->construct_header($lang->permissionname_eztrader_autorating);



	$dbresult = $db->query("
		  	SELECT
		  		u.title, p.ID_GROUP, u.gid,
		  		p.feedback, p.deletefeed, p.autorating
		  	FROM ". TABLE_PREFIX."usergroups AS u
		  	LEFT JOIN ". TABLE_PREFIX."trader_permissions as p ON (p.ID_GROUP = u.gid)
		  	");

	while($row = $db->fetch_array($dbresult))
	{
		$table->construct_cell($row['title']);
		$table->construct_cell('<input type="checkbox" name="feedback_' . $row['gid'] . '" ' . ($row['feedback'] ? ' checked="checked"' : '') . ' />');
		$table->construct_cell('<input type="checkbox" name="deletefeed_' . $row['gid'] . '" ' . ($row['deletefeed'] ? ' checked="checked"' : '') . ' />');
		$table->construct_cell('<input type="checkbox" name="autorating_' . $row['gid'] . '" ' . ($row['autorating'] ? ' checked="checked"' : '') . ' />');
		$table->construct_row();

	}

	$table->construct_cell('<input type="submit" value="' .$lang->eztrader_update_permissions . '" />',array("colspan"=>4));
	$table->construct_row();

	$table->output($lang->eztrader_text_permissions);

	echo '</form>';


	$page->output_footer();


}

function ezTrader_Permissions2()
{
	global  $db;

	$dbresult = $db->query("
		  	SELECT
		  		u.gid

		  	FROM ". TABLE_PREFIX."usergroups AS u


		  	");

	while($row = $db->fetch_array($dbresult))
	{
		$feedback = isset($_REQUEST['feedback_' . $row['gid']]) ? 1 : 0;
		$deletefeed = isset($_REQUEST['deletefeed_' . $row['gid'] ]) ? 1 : 0;
		$autorating = isset($_REQUEST['autorating_' . $row['gid']]) ? 1 : 0;


		$dbresult2 = $db->query("
		  	SELECT
		  		COUNT(*) as total
		  	FROM ".TABLE_PREFIX."trader_permissions as p
		WHERE p.ID_GROUP = ".  $row['gid']);
		$row2 = $db->fetch_array($dbresult2);

		if ($row2['total'] != 0)
		{
			// Update Member Group Permissions
			$db->query("
		  	UPDATE " . TABLE_PREFIX."trader_permissions as p
		    SET
		  		p.feedback = $feedback, p.deletefeed = $deletefeed, p.autorating = $autorating
		  	WHERE p.ID_GROUP = ".  $row['gid']);
		}
		else
		{
			$db->query("
		  	INSERT IGNORE INTO " . TABLE_PREFIX."trader_permissions
		  	(`ID_GROUP`,`feedback`,`deletefeed`,`autorating`)
		  	VALUES (" . $row['gid'] . ",$feedback,$deletefeed,$autorating)
		  	"
		  );


		}


	}




	admin_redirect('index.php?module=config/eztrader&action=permissions');
}

function eztrader_eztrader_fatal_error($errorMsg)
{
	global $page, $lang;

	$page->output_header($errorMsg);
	$page->output_inline_error($errorMsg);

	// Go Back link
	echo '<br /><a href="javascript:history.go(-1)">' . $lang->eztrader_txt_goback . '</a>';

	$page->output_footer();
	exit;
}

function eztrader_whosonline(&$plugin_array)
{
	global $lang;

	eztrader_loadlanguage();

	if (preg_match('/eztrader\.php/',$plugin_array['user_activity']['location']))
	{
		$plugin_array['location_name'] = "Viewing <a href=\"eztrader.php\">" . $lang->eztrader_whoonline . "</a>";
	}

	return $plugin_array;
}

function eztrader_loadmainlanguage()
{
	global $lang, $traderSettings;

	require_once MYBB_ROOT.'inc/eztrader.lib.php';

	LoadTraderSettings();

	$lang->load("eztrader");

}

function ezTrader_ProfileDisplay()
{
	global $lang, $traderSettings, $mybb, $profilefields, $memprofile;

	$tradecount = GetTraderCount($memprofile['uid']);


	$context['trader_mem_data'] = GetTraderInformation($memprofile['uid']);

	$context['neturalcount'] = 0;
	$context['pcount'] = 0;
	$context['ncount'] = 0;
	foreach($context['trader_mem_data'] as $row)
	{
		if ($row['salevalue'] == 0)
		{
			$context['pcount'] = $row['total'];
		}
		else if ($row['salevalue'] == 2)
		{
			$context['ncount'] = $row['total'];
		}
		else if ($row['salevalue'] == 1)
		{
			$context['neturalcount'] = $row['total'];
		}

	}


	$context['tradecount_nonetural'] = ($context['pcount'] +  $context['ncount']);

	if ($traderSettings['trader_use_pos_neg'])
		$tradecount = ($context['pcount'] -$context['ncount']);

	$profilefields = '<table border="0" cellspacing="0" cellpadding="4" class="tborder">
<tr>
<td colspan="2" class="thead"><strong>' . $lang->eztrader_title . '</strong></td></tr>
	<tr><td class="trow1"><b>' . $lang->eztrader_profile . '</b></td><td  class="trow1"><b>(<a href="eztrader.php?id=' . $memprofile['uid'] . '">' . ($traderSettings['trader_use_pos_neg'] ? ($tradecount > 0 ? '+' . $tradecount : $tradecount)  : $tradecount) . '</a>)</b></td></tr>
	<tr><td  class="trow1"><b>' . $lang->eztrader_positivefeedbackpercent  . '</b></td><td class="trow1"><b>' . ($context['tradecount_nonetural'] != 0 ? round(($context['pcount'] / $context['tradecount_nonetural']) * 100, 2) : 0) . '%</b></td></tr>
	<tr><td class="trow1">' . $lang->eztrader_positivefeedback. '</td><td class="trow1">' . $context['pcount'] . '&nbsp;<img src="' . $mybb->settings['bburl'] . '/images/smilies/smile.gif" alt="positive" /></td></tr>
	<tr><td class="trow1">' . $lang->eztrader_neutralfeedback  . '</td><td class="trow1">' .  $context['neturalcount'] . '&nbsp;<img src="' . $mybb->settings['bburl'] . '/images/smilies/undecided.gif" alt="neutral" /></td></tr>
	<tr><td class="trow1">' . $lang->eztrader_negativefeedback  . '</td><td class="trow1">' .  $context['ncount'] . '&nbsp;<img src="' . $mybb->settings['bburl'] . '/images/smilies/angry.gif" alt="negative" /></td></tr>
	<tr><td class="trow1">' . $lang->eztrader_totalfeedback  . '</td><td  class="trow1">' .  ($context['pcount'] - $context['ncount']) . '</td></tr>
	<tr><td colspan="2"  class="trow1"><br /><strong><a href="eztrader.php?sa=submit&id=' . $memprofile['uid'] . '">' . $lang->eztrader_submitfeedback . $memprofile['username'] . '</a></strong></td>
	</tr></table>' . $profilefields;

}

function ezTrader_ShowThreadDisplay(&$post)
{
	global $lang, $traderSettings, $mybb, $traderPostBit;



		$context['trader_mem_data'] = GetTraderInformation($post['uid']);

		$context['neturalcount'] = 0;
		$context['pcount'] = 0;
		$context['ncount'] = 0;
		foreach($context['trader_mem_data'] as $row)
		{
			if ($row['salevalue'] == 0)
			{
				$context['pcount'] = $row['total'];
			}
			else if ($row['salevalue'] == 2)
			{
				$context['ncount'] = $row['total'];
			}
			else if ($row['salevalue'] == 1)
			{
				$context['neturalcount'] = $row['total'];
			}

		}

		if ($traderSettings['trader_use_pos_neg'])
			$tradecount = ($context['pcount'] - $context['ncount']);
		else
			$tradecount = $context['pcount'] + $context['ncount'] + $context['neturalcount'];

		// Show the trader info
		$post['user_details'] = '
					<b>' . $lang->eztrader_profile . ' </b>
					(<a href="' . $mybb->settings['bburl'] . '/eztrader.php?id=' . $post['uid'] . '">' . ($traderSettings['trader_use_pos_neg'] ? ($tradecount > 0 ? '+' . $tradecount : $tradecount)  : $tradecount)   . '</a>)<br />' . $post['user_details'];

}


function GetTraderInformation($memberID)
{
	global $db, $context;

		$result = $db->query("
		SELECT
			COUNT(*) AS total,salevalue
		FROM " . TABLE_PREFIX."trader_feedback
		WHERE approved = 1 AND ID_MEMBER = " . $memberID . " GROUP BY salevalue");
		$context['trader_mem_data'] = array();
		while($row = $db->fetch_array($result))
		{
			$context['trader_mem_data'][] = $row;
		}

	return $context['trader_mem_data'];

}

function GetTraderCount($memberID)
{
	global $db, $context;

	$dbresult2 = $db->query("
	SELECT
		COUNT(*) as total
	FROM " . TABLE_PREFIX."trader_feedback
	WHERE approved = 1 AND ID_MEMBER =" . $memberID);
	$row = $db->fetch_array($dbresult2);
	$tradecount = $row['total'];

	$context['trader_trade_count'] = $tradecount;

	return $tradecount;
}

function ezTrader_ApproveByID($id)
{
	global $db;

	$db->query("UPDATE " . TABLE_PREFIX."trader_feedback
	SET approved = 1
	WHERE feedbackid = $id LIMIT 1");

	ezTrader_SendTraderPMByID($id);

	ezTrader_SendCommenterPMByID($id);
}

function ezTrader_ApproveRating()
{

	$id = (int) $_REQUEST['id'];

	ezTrader_ApproveByID($id);

	admin_redirect('index.php?module=config/eztrader&action=approvelist');
}

function ezTrader_SendTraderPMByID($id)
{
	global $db, $lang, $mybb;

	$request = $db->query("
		SELECT
			 m.username, f.comment_short, f.ID_MEMBER, f.FeedBackMEMBER_ID
		FROM
			(" . TABLE_PREFIX."trader_feedback AS f, " . TABLE_PREFIX."users as m)
		WHERE f.FeedBackMEMBER_ID = m.uid AND f.feedbackid  = $id LIMIT 1");


		$row = $db->fetch_array($request);

	require_once MYBB_ROOT."inc/datahandlers/pm.php";
	$pmhandler = new PMDataHandler();

	$pm = array(
		'subject' => $lang->eztrader_newrating,
		'message' => $row['comment_short'] .  "\n\n" . $lang->eztrader_commentmadeby . $row['username'] . "\n" . $mybb->settings['bburl'] . "/member.php?action=profile&uid=" . $row['ID_MEMBER'],
		'icon' => '',
		'toid' => array($row['ID_MEMBER']),
		'fromid' => $row['FeedBackMEMBER_ID'],
		"do" => '',
		"pmid" => '',

	);

	$pm['options'] = array(
	'signature' => '0',
	'savecopy' => '0',
	'disablesmilies' => '0',
	'readreceipt' => '0',
	);


	$pmhandler->set_data($pm);
	$valid_pm = $pmhandler->validate_pm();

	if( $valid_pm)
	{
		$pmhandler->insert_pm();
	}




}

function ezTrader_BulkActions()
{

	if (isset($_REQUEST['ratings']))
	{

		$baction = $_REQUEST['doaction'];

		foreach ($_REQUEST['ratings'] as $value)
		{

			if ($baction == 'approve')
				 ezTrader_ApproveByID($value);
			if ($baction == 'delete')
				 ezTrader_DeleteByID($value);

		}
	}

	// Redirect to approval list
	admin_redirect('index.php?module=config/eztrader&action=approvelist');
}

function ezTrader_DeleteByID($id)
{
	global $db;

	// Delete the comment
	$db->query("
	DELETE FROM " . TABLE_PREFIX."trader_feedback
	WHERE feedbackid = $id");
}


function ezTrader_SendCommenterPMByID($id)
{
	global $db, $lang, $mybb;

	$request = $db->query("
		SELECT
			 m.username, f.comment_short, f.ID_MEMBER,f.FeedBackMEMBER_ID,
			 u.username MainName
		FROM
			(" . TABLE_PREFIX."trader_feedback AS f)

		LEFT JOIN " . TABLE_PREFIX."users as m ON (f.FeedBackMEMBER_ID = m.uid)
		LEFT JOIN " . TABLE_PREFIX."users as u ON (f.ID_MEMBER = u.uid)

		WHERE  f.feedbackid  = $id LIMIT 1");
		$row = $db->fetch_array($request);


	require_once MYBB_ROOT."inc/datahandlers/pm.php";

	$finaltime = my_date($mybb->settings['dateformat'], time());
	$pmhandler = new PMDataHandler();

	$pm = array(
		'subject' => sprintf($lang->eztrader_commenter_subject,$row['MainName']),
		'message' => sprintf($lang->eztrader_commenter_body,$row['MainName'],$finaltime),
		'icon' => '',
		'toid' => array($row['FeedBackMEMBER_ID']),
		'fromid' => $row['ID_MEMBER'],
		"do" => '',
		"pmid" => '',

	);

	$pm['options'] = array(
	'signature' => '0',
	'savecopy' => '0',
	'disablesmilies' => '0',
	'readreceipt' => '0',
	);


	$pmhandler->set_data($pm);
	$valid_pm = $pmhandler->validate_pm();

	if( $valid_pm)
	{
		$pmhandler->insert_pm();
	}



}


?>