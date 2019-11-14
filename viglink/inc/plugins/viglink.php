<?php
ini_set("display_errors",1);
/*
Viglink
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



$plugins->add_hook('pre_output_page','viglink_display');


function viglink_info()
{

	return array(
		"name"		=> "Viglink",
		"description"		=> "Adds Viglink to your site. Earn money from links posted on your forum",
		"website"		=> "http://www.mybbhacks.com",
		"author"		=> "vbgamer45",
		"authorsite"		=> "http://www.mybbhacks.com",
		"version"		=> "1.0",
		"guid" 			=> "18d2e99be1d2b72f1dacb74072728e12",
		"compatibility"	=> "18*"
		);
}


function viglink_install()
{
	
	global $db;
	
	$viglink_group = array(
		"name"			=> "viglink",
		"title"			=> "Viglink",
		"description"	=> "Earn money from links posted on your forum",
		"disporder"		=> "25",
		"isdefault"		=> "no",
	);
	
	$db->insert_query("settinggroups", $viglink_group);
	$gid = $db->insert_id();
	

	$new_setting = array(
		'name'			=> 'viglink_apikey',
		'title'			=> 'API Key',
		'description'	=> 'Step 1: Signup For 
			<a href="http://www.viglink.com/?vgref=11246" target="_blank">VigLink</a><br />
		
		Step 2: Enter the apikey or grab the full javascript code
		',
		'optionscode'	=> 'text',
		'value'			=> '',
		'disporder'		=> '1',
		'gid'			=> intval($gid)
	);

	
	$db->insert_query('settings', $new_setting);
	
	
	$new_setting = array(
		'name'			=> 'viglink_reaffiliate',
		'title'			=> 'Reaffiliate Links:',
		'description'	=> '',
		'optionscode'	=> 'yesno',
		'value'			=> '',
		'disporder'		=> '2',
		'gid'			=> intval($gid)
	);

	$db->insert_query('settings', $new_setting);
	
		$new_setting = array(
		'name'			=> 'viglink_fulljs',
		'title'			=> 'Or Enter your Viglink full javascript code',
		'description'	=> 'If you do not want to use the apikey you can enter your viglink javascript code here',
		'optionscode'	=> 'textarea',
		'value'			=> '',
		'disporder'		=> '3',
		'gid'			=> intval($gid)
	);

	$db->insert_query('settings', $new_setting);
	
	
	
	rebuild_settings();

}


function viglink_is_installed()
{
	global $db;
	 
	$dbresult = $db->query("SELECT name FROM ".TABLE_PREFIX."settings WHERE name='viglink_apikey'");
	$row = $db->fetch_array($dbresult);
	
	if (empty($row['name']))
		return false;
	else 
		return true;

}

function viglink_uninstall()
{
	global $db;

	$db->query("DELETE FROM ".TABLE_PREFIX."settings WHERE name='viglink_apikey'");
	$db->query("DELETE FROM ".TABLE_PREFIX."settings WHERE name='viglink_reaffiliate'");
	$db->query("DELETE FROM ".TABLE_PREFIX."settings WHERE name='viglink_fulljs'");
	$db->delete_query("settinggroups","name='viglink'");

	rebuild_settings();
}


function viglink_activate()
{

}

function viglink_deactivate()
{


}

function viglink_loadlanguage()
{
	global $lang;

	$lang->load('viglink');

}

function viglink_display($page)
{
	global $mybb;
	
	if (!empty($mybb->settings['viglink_fulljs']))
	{
		$page = str_replace("</body>", $mybb->settings['viglink_fulljs'] . '</body>', $page);

	}
	else 
	{
		if (!empty($mybb->settings['viglink_apikey']))
		{
			$jscode = "<script type=\"text/javascript\">
  var vglnk = { api_url: '//api.viglink.com/api',
                key: '" . $mybb->settings['viglink_apikey'] . "' };
                
                ";
               
          if (!empty($mybb->settings['viglink_reaffiliate']))
               $jscode .= "vglnk.reaffiliate = true;";
                 
   $jscode .= "   

  (function(d, t) {
    var s = d.createElement(t); s.type = 'text/javascript'; s.async = true;
    s.src = ('https:' == document.location.protocol ? vglnk.api_url :
             '//cdn.viglink.com/api') + '/vglnk.js';
    var r = d.getElementsByTagName(t)[0]; r.parentNode.insertBefore(s, r);
  }(document, 'script'));
</script>";
			

			$page = str_replace("</body>", $jscode . '</body>', $page);
		}
		
		
	}
	
	return $page;
}



?>