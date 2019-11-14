<?php
/*
PM On Reputation
by: vbgamer45
http://www.mybbhacks.com
Copyright 2010  MyBBHacks.com

############################################
License Information:

Links to http://www.mybbhacks.com must remain unless
branding free option is purchased.
#############################################
*/
if(!defined('IN_MYBB'))
	die('This file cannot be accessed directly.');

$plugins->add_hook('reputation_do_add_end','pmonreputation_sendpm');


function pmonreputation_info()
{

	return array(
		"name"		=> "PM On Reputation",
		"description"		=> "This plugin sends the user a pm when they receive new reputation",
		"website"		=> "http://www.mybbhacks.com",
		"author"		=> "vbgamer45",
		"authorsite"		=> "http://www.mybbhacks.com",
		"version"		=> "1.0",
		"guid" 			=> "465430b96e45ab9af6e5ff2c97e3ec21",
		"compatibility"	=> "1*"
		);
}



function pmonreputation_activate()
{

}

function pmonreputation_deactivate()
{


}

function pmonreputation_sendpm()
{
	global $db, $user_info, $lang, $reputation, $existing_reputation;

	// Change exisitng
	if ($existing_reputation['uid'])
	{
		return;
	}

	$lang->load('pmonreputation');


	// Get username of reputation adder
	$query = $db->write_query("SELECT username FROM ".TABLE_PREFIX."users
		 WHERE uid = " . $reputation['adduid']);
	$userRow = $db->fetch_array($query);


	$subject = '';
	$body =  '';

	// Netural
	if ($reputation['reputation'] == 0)
	{
		$subject = $lang->pmonreputation_newreputation . " " . $lang->pmonreputation_netural;
		$body =   $lang->pmonreputation_body1 . $lang->pmonreputation_netural . $lang->pmonreputation_body2 . $userRow['username'];
	}

	// Negative
	if ($reputation['reputation'] < 0)
	{
		$subject = $lang->pmonreputation_newreputation . " " . $lang->pmonreputation_negative;
		$body =   $lang->pmonreputation_body1 . $lang->pmonreputation_negative . $lang->pmonreputation_body2 . $userRow['username'];
	}

	// Positive
	if ($reputation['reputation'] > 0)
	{
		$subject = $lang->pmonreputation_newreputation . " " . $lang->pmonreputation_positive;
		$body =   $lang->pmonreputation_body1 . $lang->pmonreputation_positive . $lang->pmonreputation_body2 . $userRow['username'];
	}




	// Send PM
	require_once MYBB_ROOT."inc/datahandlers/pm.php";
	$pmhandler = new PMDataHandler();

	$pm = array(
		'subject' => $subject,
		'message' => $body,
		'icon' => '',
		'toid' => array($reputation['uid']),
		'fromid' => $reputation['adduid'],
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