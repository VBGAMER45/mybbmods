<?php
/*
Registered Links
by: vbgamer45
http://www.mybbhacks.com
Copyright 2014  MyBBHacks.com

############################################
License Information:

Links to http://www.mybbhacks.com must remain unless
branding free option is purchased.
#############################################
*/
if(!defined('IN_MYBB'))
	die('This file cannot be accessed directly.');
	
$plugins->add_hook("parse_message", "registeredlinks_process");

function registeredlinks_info()
{

	return array(
		"name"		=> "Registered Links",
		"description"		=> "Hides all links from guests requires them to register in order to view",
		"website"		=> "http://www.mybbhacks.com",
		"author"		=> "vbgamer45",
		"authorsite"		=> "http://www.mybbhacks.com",
		"version"		=> "1.0",
		"guid" 			=> "7725ba33bb01a4223b01fe0ee022d650",
		"compatibility"	=> "18*"
		);
}


function registeredlinks_install()
{

	

}



function registeredlinks_uninstall()
{

}


function registeredlinks_process(&$message)
{
	global $lang, $mybb;
	

	if ($mybb->user['uid'] == 0)
	{
		$lang->load('registeredlinks');
		
		$lang->reglinks_text = str_replace("{bburl}",$mybb->settings['bburl'],$lang->reglinks_text);
		
		$message = preg_replace('#<a href="(.*?)</a>#i', $lang->reglinks_text, $message);
		

	}
	
	
	return $message;

}


?>