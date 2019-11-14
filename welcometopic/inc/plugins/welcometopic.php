<?php
/*
Welcome Topic
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


$plugins->add_hook('admin_config_action_handler','welcometopic_admin_action');
$plugins->add_hook('admin_config_menu','welcometopic_admin_config_menu');

$plugins->add_hook('admin_load','welcometopic_admin');
$plugins->add_hook('member_do_register_end','welcometopic_createtopic');


function welcometopic_info()
{

	return array(
		"name"		=> "Welcome Topic",
		"description"		=> "Auto creates a welcome topic for a user when they join the forum",
		"website"		=> "http://www.mybbhacks.com",
		"author"		=> "vbgamer45",
		"authorsite"		=> "http://www.mybbhacks.com",
		"version"		=> "1.0",
		"guid" 			=> "792a1ae17c6a50ccc086c12b36f76ad0",
		"compatibility"	=> "1*"
		);
}


function welcometopic_install()
{
	global $db, $charset;

	// Create Tables/Settings
	$db->write_query("CREATE TABLE IF NOT EXISTS ".TABLE_PREFIX."welcome
(ID smallint(5) unsigned NOT NULL auto_increment,
welcomesubject tinytext,
welcomebody text,
PRIMARY KEY (ID))");

	$db->write_query("CREATE TABLE IF NOT EXISTS ".TABLE_PREFIX."welcomesettings
(
welcome_boardid int(5) default 0,
welcome_memberid int(5) default 0,
welcome_membername varchar(255)
)");

	$db->write_query("INSERT INTO ".TABLE_PREFIX."welcomesettings
(welcome_boardid,welcome_memberid,welcome_membername) VALUES(0,0,'')
");



}


function welcometopic_is_installed()
{

	global $db;
	if($db->table_exists("welcome"))
	{
		return true;
	}
	return false;
}


function welcometopic_uninstall()
{
	global $db;

	// Drop the Table
	$db->drop_table("welcome");

	// Drop the Table
	$db->drop_table("welcomesettings");
}


function welcometopic_activate()
{

}

function welcometopic_deactivate()
{


}



function welcometopic_admin_action(&$action)
{
	$action['welcometopic'] = array('active'=>'welcometopic');
}

function welcometopic_admin_config_menu(&$admim_menu)
{
	global $lang;

	// Load Language file
	welcometopic_loadlanguage();

	end($admim_menu);

	$key = (key($admim_menu)) + 10;

	$admim_menu[$key] = array
	(
		'id' => 'welcometopic',
		'title' => $lang->welcometopic_admin,
		'link' => 'index.php?module=config/welcometopic'
	);

}

function welcometopic_loadlanguage()
{
	global $lang;

	$lang->load('welcometopic');

}

function welcometopic_admin()
{
	global $lang, $mybb, $db, $page;

	if ($page->active_action != 'welcometopic')
		return false;


	// Load Language file
	welcometopic_loadlanguage();

	// Create Admin Tabs
	$tabs['welcometopic'] = array
		(
			'title' => $lang->welcometopic_settings,
			'link' =>'index.php?module=config/welcometopic',
			'description'=> $lang->welcometopic_description
		);
	$tabs['welcometopic_addtopic'] = array
		(
			'title' => $lang->welcometopic_addtopic,
			'link' => 'index.php?module=config/welcometopic&action=add',
			'description' => $lang->welcometopic_addtopic_description
		);

	// No action
	if(!$mybb->input['action'])
	{


		$page->output_header($lang->welcometopic_title);
		$page->add_breadcrumb_item($lang->welcometopic_title);
		$page->output_nav_tabs($tabs,'welcometopic');

		$table = new Table;
		$form = new Form("index.php?module=config/welcometopic&amp;action=update", "post");

		$forumList = array();
		$query = $db->query("
			SELECT
				fid, name
			FROM ".TABLE_PREFIX."forums
			WHERE type = 'f'

		");
		$forumList[0] = '';
		while($forumRow = $db->fetch_array($query))
		{
			$forumList[$forumRow['fid']] = $forumRow['name'];
		}

		$welcomeSettings = welcometopic_loadsettings();
		$forumBox = $form->generate_select_box("welcome_boardid",$forumList,$welcomeSettings['welcome_boardid']);


		require_once MYBB_ADMIN_DIR."inc/class_form.php";
		$table->construct_cell($lang->welcometopic_forum);
		$table->construct_cell($forumBox);
		$table->construct_row();


		$table->construct_cell($lang->welcometopic_postername);
		$table->construct_cell('<input type="text" size="50" name="welcome_membername" value="' . $welcomeSettings['welcome_membername'] . '" />');
		$table->construct_row();

		$table->construct_cell($lang->welcometopic_memberid);
		$table->construct_cell('<input type="text" size="50" name="welcome_memberid" value="' . $welcomeSettings['welcome_memberid'] . '" />');
		$table->construct_row();



		$table->construct_cell('<input type="submit" value="' .$lang->welcometopic_savesettings . '" />', array('colspan' => 2));
		$table->construct_row();


		$form->end;


		$table->output($lang->welcometopic_settings);

		$table = new Table;
		$table->construct_header($lang->welcometopic_table_title);
		$table->construct_header($lang->welcometopic_table_options);
		$query = $db->query("
			SELECT
				id, welcomesubject, welcomebody
			FROM ".TABLE_PREFIX."welcome

		");
		while($welcomeRow = $db->fetch_array($query))
		{


			$table->construct_cell($welcomeRow['welcomesubject']);
			$table->construct_cell('
			<a href="index.php?module=config/welcometopic&action=edit&id=' . $welcomeRow['id'] . '">' . $lang->welcometopic_edittopic . '</a>&nbsp;|&nbsp; <a href="index.php?module=config/welcometopic&action=delete&id=' . $welcomeRow['id'] . '">' . $lang->welcometopic_delete . '</a>

			');
			$table->construct_row();
		}

		if($table->num_rows() == 0)
		{
			$table->construct_cell($lang->welcometopic_no_welcometopics, array('colspan' => 2));
			$table->construct_row();

		}

		$table->output($lang->welcometopic_welcometopics);

		$page->output_footer();



	}

	// Add Menu
	if ($mybb->input['action'] == 'add' || $mybb->input['action'] == 'add2')
	{
		$subject = '';
		$body = '';



		if ($mybb->input['action'] == 'add2')
		{
			// Check Post
			$subject = $mybb->input['subject'];
			$body = $mybb->input['body'];


			if (empty($subject))
			{
				$errors[] = $lang->welcometopic_err_nosubject;
			}

			if (empty($body))
			{
				$errors[] = $lang->welcometopic_err_nobody;
			}

			if($errors)
			{
				$page->output_inline_error($errors);
			}
			else
			{

				$db->write_query("INSERT IGNORE INTO ".TABLE_PREFIX."welcome
				(welcomesubject,welcomebody)
				VALUES('$subject','$body')");


				admin_redirect("index.php?module=config/welcometopic");

			}

		}


		$page->output_header($lang->welcometopic_addtopic);
		$page->add_breadcrumb_item($lang->welcometopic_addtopictopic);
		$page->output_nav_tabs($tabs, 'welcometopic_addtopic');



		$form = new Form("index.php?module=config/welcometopic&amp;action=add2", "post");
		$table = new Table;

		$table->construct_cell($lang->welcometopic_subject);
		$table->construct_cell('<input type="text" size="50" name="subject" value="' . $subject . '" />');
		$table->construct_row();

		$table->construct_cell($lang->welcometopic_topicbody);
		$table->construct_cell('<textarea name="body" rows="10" cols="50">' . $body . '</textarea>');
		$table->construct_row();


		$table->construct_cell($lang->welcometopic_topicnote, array('colspan' => 2));
		$table->construct_row();



		$table->construct_cell('<input type="submit" value="' .$lang->welcometopic_addtopic . '" />', array('colspan' => 2));
		$table->construct_row();

		$form->end;
		$table->output($lang->welcometopic_addtopic);

		$page->output_footer();
	}

	if ($mybb->input['action'] == 'edit' || $mybb->input['action'] == 'edit2')
	{


		$id = (int) $_REQUEST['id'];

		$query = $db->query("
			SELECT
				welcomebody,welcomesubject
			FROM ".TABLE_PREFIX."welcome

			WHERE id = $id LIMIT 1
		");
		$welcomeRow = $db->fetch_array($query);

		$subject = $welcomeRow['welcomesubject'];
		$body =  $welcomeRow['welcomebody'];


		if ($mybb->input['action'] == 'edit2')
		{
			// Check Post
			$subject = $mybb->input['subject'];
			$body = $mybb->input['body'];

			if (empty($subject))
			{
				$errors[] = $lang->welcometopic_err_nosubject;
			}

			if (empty($body))
			{
				$errors[] = $lang->welcometopic_err_nobody;
			}

			if($errors)
			{
				$page->output_inline_error($errors);
			}
			else
			{

				$db->write_query("UPDATE ".TABLE_PREFIX."welcome
				SET welcomesubject = '$subject', welcomebody = '$body'

				WHERE id = $id LIMIT 1
				");

				admin_redirect("index.php?module=config/welcometopic");

			}

		}


		$page->output_header($lang->welcometopic_edittopic);
		$page->add_breadcrumb_item($lang->welcometopic_edittopic);
		$page->output_nav_tabs($tabs, 'welcometopic_addtopic');



		$form = new Form("index.php?module=config/welcometopic&amp;action=edit2", "post");
		$table = new Table;

		$table->construct_cell($lang->welcometopic_subject);
		$table->construct_cell('<input type="text" size="50" name="subject" value="' . $subject . '" />');
		$table->construct_row();

		$table->construct_cell($lang->welcometopic_topicbody);
		$table->construct_cell('<textarea name="body" rows="10" cols="50">' . $body . '</textarea>');
		$table->construct_row();


		$table->construct_cell($lang->welcometopic_topicnote, array('colspan' => 2));
		$table->construct_row();



		$table->construct_cell('
		<input type="hidden" name="id" value="' . $id . '" />
		<input type="submit" value="' .$lang->welcometopic_edittopic . '" />', array('colspan' => 2));
		$table->construct_row();

		$form->end;
		$table->output($lang->welcometopic_edittopic);

		$page->output_footer();

	}




	if ($mybb->input['action'] == 'delete')
	{
		$id = (int) $_REQUEST['id'];
		$db->write_query("DELETE FROM ".TABLE_PREFIX."welcome WHERE id = $id
				");

		admin_redirect("index.php?module=config/welcometopic");
	}


	if ($mybb->input['action'] == 'update')
	{
		$welcome_boardid = (int) $_REQUEST['welcome_boardid'];
		$welcome_memberid = (int) $_REQUEST['welcome_memberid'];
		$welcome_membername  = $_REQUEST['welcome_membername'];

		$query = $db->write_query("UPDATE ".TABLE_PREFIX."welcomesettings SET

		welcome_boardid = $welcome_boardid,welcome_memberid = $welcome_memberid,welcome_membername = '$welcome_membername'
");
		admin_redirect("index.php?module=config/welcometopic");
	}


}


function welcometopic_createtopic()
{
	global $db, $user_info;

	$welcomeSettings = welcometopic_loadsettings();

	if (empty($welcomeSettings['welcome_boardid']))
		return ;

	if (empty($welcomeSettings['welcome_membername']) && empty($welcomeSettings['welcome_memberid']))
		return ;

	// Select Random Welcome topic
	$query = $db->write_query("SELECT welcomesubject, welcomebody FROM ".TABLE_PREFIX."welcome
		 ORDER BY RAND() LIMIT 1");
	$welcomeRow = $db->fetch_array($query);

	$finalSubject = str_replace("{username}",$user_info['username'],$welcomeRow['welcomesubject']);
	$finalBody = str_replace("{username}",$user_info['username'],$welcomeRow['welcomebody']);

	// Insert Message in forum
	require_once MYBB_ROOT."inc/datahandlers/post.php";
	$posthandler = new PostDataHandler("insert");
	$posthandler->action = "thread";

	// Set the thread data that came from the input to the $thread array.
	$new_thread = array(
		"fid" => $welcomeSettings['welcome_boardid'],
		"subject" => $finalSubject,
		"icon" => '',
		"uid" => $welcomeSettings['welcome_memberid'],
		"username" => $welcomeSettings['welcome_membername'],
		"message" => $finalBody,
		"ipaddress" => '127.0.0.1',
		"posthash" => ''
	);

	$posthandler->set_data($new_thread);
	$valid_thread = $posthandler->validate_thread();

	if(!$valid_thread)
	{
		$post_errors = $posthandler->get_friendly_errors();
	}
	else
		$thread_info = $posthandler->insert_thread();

}

function welcometopic_loadsettings()
{
	global $db;

	$query = $db->write_query("SELECT welcome_boardid,welcome_memberid,welcome_membername  FROM ".TABLE_PREFIX."welcomesettings
");
	$welcomeSettings = $db->fetch_array($query);

	return $welcomeSettings;


}


?>