<?php
/*
Twitter New Thread
by: vbgamer45
Thanks to  mark-in-dallas
http://www.mybbhacks.com
Copyright 2014  MyBBHacks.com

############################################
License Information:

Links to http://www.mybbhacks.com must remain unless
branding free option is purchased.
#############################################
*/

if(!defined("IN_MYBB"))
{
    die("Direct initialization of this file is not allowed.<br /><br />Please make sure IN_MYBB is defined.");
}

$plugins->add_hook("newthread_do_newthread_end", "twitternewthread_addthread");

function twitternewthread_info()
{
    return array(
        "name"            => "Twitter New Thread",
        "description"    => "Tweet new threads to Twitter!",
        "website"        => "http://www.mybbhacks.com",
        "author"        => "vbgamer45",
        "authorsite"    => "http://www.mybbhacks.com",
        "version"        => "1.3",
        "guid"            => "88c0d3eca537465301ceb775884c26e6",
        "compatibility" => "1*",
    );
    
    /*
    Register an App
http://dev.twitter.com/apps
*/

}

function twitternewthread_activate()
{
	global $db, $mybb;
	
	$twitternewthread_group = array(
		"name"			=> "twitternewthread",
		"title"			=> "Twitter New Thread",
		"description"	=> "Tweets new threads on creation",
		"disporder"		=> "25",
		"isdefault"		=> "no",
	);
	
	$db->insert_query("settinggroups", $twitternewthread_group);
	$gid = $db->insert_id();
	
	/*
	$new_setting = array(
		'name'			=> 'twitterusername',
		'title'			=> 'Twitter Username',
		'description'	=> 'Your Twitter Username',
		'optionscode'	=> 'text',
		'value'			=> '',
		'disporder'		=> '1',
		'gid'			=> intval($gid)
	);

	$db->insert_query('settings', $new_setting);
	
	
	$new_setting = array(
		'name'			=> 'twitterpassword',
		'title'			=> 'Twitter Password',
		'description'	=> 'Your Twitter Password',
		'optionscode'	=> 'passwordbox',
		'value'			=> '',
		'disporder'		=> '1',
		'gid'			=> intval($gid)
	);

	$db->insert_query('settings', $new_setting);
	
	
	*/
	$data = base64_encode("Step 1: Sign with your Twitter Account 
	<a href=\'" . $mybb->settings['bburl'] . "/twittercallback.php?sa=twittersignin\'><img src=\'" . $mybb->settings['bburl'] . "/images/lighter.png\' alt=\'Sign in with Twitter\'/></a>");

$new_setting = array(
		'name'			=> 'oauth_sigin',
		'title'			=> 'OAuth Twitter Signin',
		'description'	=> 'Step 1: Register an App
			<a href="http://dev.twitter.com/apps" target="_blank">http://dev.twitter.com/apps</a> and fill out the settings below.<br />
		
		Step 2: Sign with your Twitter Account 
	<a href="' . $mybb->settings['bburl'] . '/twittercallback.php?sa=twittersignin"><img src="'. $mybb->settings['bburl'] . '/images/lighter.png" alt="Sign in with Twitter"/></a>',
		'optionscode'	=> 'php\n" . base64_decode($data) . "',
		'value'			=> '',
		'disporder'		=> '1',
		'gid'			=> intval($gid)
	);

	
	$db->insert_query('settings', $new_setting);
	
	
	$new_setting = array(
		'name'			=> 'con_key',
		'title'			=> 'Consumer key',
		'description'	=> '',
		'optionscode'	=> 'text',
		'value'			=> '',
		'disporder'		=> '2',
		'gid'			=> intval($gid)
	);

	$db->insert_query('settings', $new_setting);
	
		$new_setting = array(
		'name'			=> 'con_secret',
		'title'			=> 'Consumer Secret',
		'description'	=> '',
		'optionscode'	=> 'text',
		'value'			=> '',
		'disporder'		=> '3',
		'gid'			=> intval($gid)
	);

	$db->insert_query('settings', $new_setting);
	
	
	
	$new_setting = array(
		'name'			=> 'oauth_token',
		'title'			=> 'OAuth Token',
		'description'	=> 'Token do not fill in',
		'optionscode'	=> 'text',
		'value'			=> '',
		'disporder'		=> '4',
		'gid'			=> intval($gid)
	);

	$db->insert_query('settings', $new_setting);
	
		$new_setting = array(
		'name'			=> 'oauth_token_secret',
		'title'			=> 'OAuth Secret Token',
		'description'	=> 'Secret Token do not fill in',
		'optionscode'	=> 'text',
		'value'			=> '',
		'disporder'		=> '5',
		'gid'			=> intval($gid)
	);

	$db->insert_query('settings', $new_setting);
	
	
	$new_setting = array(
		'name'			=> 'bitlyusername',
		'title'			=> 'Bitly Username',
		'description'	=> 'Optional: Your Bitly Username used for link shorting',
		'optionscode'	=> 'text',
		'value'			=> '',
		'disporder'		=> '6',
		'gid'			=> intval($gid)
	);

	$db->insert_query('settings', $new_setting);
	
	
	$new_setting = array(
		'name'			=> 'bitlyapikey',
		'title'			=> 'Bitly ApiKey',
		'description'	=> 'Optional: Used for link shorting signup at Bitly.com',
		'optionscode'	=> 'text',
		'value'			=> '',
		'disporder'		=> '7',
		'gid'			=> intval($gid)
	);

	$db->insert_query('settings', $new_setting);
	
	
	rebuild_settings();
}

function twitternewthread_deactivate()
{
	global $db;
	
	$db->query("DELETE FROM ".TABLE_PREFIX."settings WHERE name='con_key'");
	$db->query("DELETE FROM ".TABLE_PREFIX."settings WHERE name='con_secret'");
	$db->query("DELETE FROM ".TABLE_PREFIX."settings WHERE name='oauth_token'");
	$db->query("DELETE FROM ".TABLE_PREFIX."settings WHERE name='oauth_token_secret'");
	$db->query("DELETE FROM ".TABLE_PREFIX."settings WHERE name='twitterusername'");
	$db->query("DELETE FROM ".TABLE_PREFIX."settings WHERE name='twitterpassword'");
	$db->query("DELETE FROM ".TABLE_PREFIX."settings WHERE name='bitlyusername'");
	$db->query("DELETE FROM ".TABLE_PREFIX."settings WHERE name='bitlyapikey'");
	$db->delete_query("settinggroups","name='twitternewthread'");

	
	rebuild_settings();
}

function twitternewthread_addthread()
{
        global $new_thread, $mybb, $thread_info;

        
        
        if (empty($new_thread['subject']))
			return;
			
		if (!empty($mybb->settings['oauth_token']) && !empty($mybb->settings['oauth_token_secret']))
		{
		
		}
		else 
		{		
					
				if (empty($mybb->settings['twitterusername']))
					return;
					
				if (empty($mybb->settings['twitterpassword']))
					return;
					
		}
        	
		if (empty($thread_info['tid']))
			return;
			
		require_once('inc/bitly.php');
			
		
		
		
        // get URL
        $url = $mybb->settings['bburl'] ."/" . get_thread_link($thread_info['tid']);
        
        if (!empty($mybb->settings['bitlyusername']) && !empty($mybb->settings['bitlyapikey']))
        {
        	$bitly = new bitly($mybb->settings['bitlyusername'], $mybb->settings['bitlyapikey']);
			$url = $bitly->shorten($url);
        }
        

        
if (!empty($mybb->settings['oauth_token']) && !empty($mybb->settings['oauth_token_secret']))
{
	 require_once('inc/twitteroauth.php');
	 
	$connection = new TwitterOAuth($mybb->settings['con_key'], $mybb->settings['con_secret'], $mybb->settings['oauth_token'], $mybb->settings['oauth_token_secret']);

	
	$content = $connection->get('account/verify_credentials');


	$connection->post('statuses/update', array('status' => $new_thread['subject'] . " " . $new_thread['username']  . " " . $url));
}
else 
{
        // include twitter class
        require_once('inc/class.twitter.php');
        // send twitter status
        $tw = new twitter($mybb->settings['twitterusername'],$mybb->settings['twitterpassword']);
        
		try
		{
    		$tw->updateStatus($new_thread['subject'] . " " . $new_thread['username']  . " " . $url);

		} 
		catch(TwitterException $exception)
		{
    		return;
		}
}	

}

?>