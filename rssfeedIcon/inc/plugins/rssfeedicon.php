<?php
/*
RSS Feed Icon
by: vbgamer45
http://www.mybbhacks.com
Copyright 2014  MyBBHacks.com

############################################
License Information:

Links to http://www.mybbhacks.com must remain unless
branding free option is purchased.
#############################################
*/

function rssfeedicon_info()
{

	return array(
		"name"		=> "RSS Feed Icons",
		"description"		=> "Adds RSS Feed Icon after forum name.",
		"website"		=> "http://www.mybbhacks.com",
		"author"		=> "vbgamer45",
		"authorsite"		=> "http://www.mybbhacks.com",
		"version"		=> "1.0.1",
		"guid" 			=> "8035c12ac7803ab87e51bc0f0b9c8f69",
		"compatibility"	=> "18*"
		);
}


function rssfeedicon_install()
{
	// Create Tables/Settings

	// Not needed for this plugin

}

function rssfeedicon_is_activated()
{
	// Not needed for this plugin

}

function rssfeedicon_uninstall()
{
	// Not needed for this plugin
}


function rssfeedicon_activate()
{
  require_once MYBB_ROOT."/inc/adminfunctions_templates.php";

  $returnStatus1 = find_replace_templatesets("forumbit_depth2_forum", "#".preg_quote('{$forum_viewers_text}') . "#i", '&nbsp;<a href="{$mybb->settings[\'bburl\']}/syndication.php?fid={$forum[\'fid\']}&limit=15"><img src="{$mybb->settings[\'bburl\']}/rss.png" alt="RSS" /></a>{$forum_viewers_text}');

  $returnStatus2 = find_replace_templatesets("forumbit_depth2_cat", "#".preg_quote('{$forum_viewers_text}') . "#i", '&nbsp;<a href="{$mybb->settings[\'bburl\']}/syndication.php?fid={$forum[\'fid\']}&limit=15"><img src="{$mybb->settings[\'bburl\']}/rss.png" alt="RSS" /></a>{$forum_viewers_text}');

}

function rssfeedicon_deactivate()
{
  require_once MYBB_ROOT."/inc/adminfunctions_templates.php";

  $returnStatus1 = find_replace_templatesets(
  "forumbit_depth2_forum", "#".preg_quote('&nbsp;<a href="{$mybb->settings[\'bburl\']}/syndication.php?fid={$forum[\'fid\']}&limit=15"><img src="{$mybb->settings[\'bburl\']}/rss.png" alt="RSS" /></a>{$forum_viewers_text}') . "#i",
  '{$forum_viewers_text}',0);

  $returnStatus2 = find_replace_templatesets(
  "forumbit_depth2_cat", "#".preg_quote('&nbsp;<a href="{$mybb->settings[\'bburl\']}/syndication.php?fid={$forum[\'fid\']}&limit=15"><img src="{$mybb->settings[\'bburl\']}/rss.png" alt="RSS" /></a>{$forum_viewers_text}') . "#i",
  '{$forum_viewers_text}',0);


}



?>