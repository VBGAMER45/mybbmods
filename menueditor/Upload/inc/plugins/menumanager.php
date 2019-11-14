<?php
/*
 * MyBB: Menu Manager 
 *
 * File: menumanager.php
 * 
 * Authors: MyBBHacks, vbgamer45, Vintagedaddyo http://community.mybb.com/user-6029.html
 *
 * MyBB Version: 1.8
 *
 * Plugin Version: 1.3
 * 
 * http://www.mybbhacks.com
 * Copyright 2010-2016  MyBBHacks.com
 *
############################################
License Information:

Links to http://www.mybbhacks.com must remain unless
branding free option is purchased.
#############################################
*/

if(!defined('IN_MYBB'))
	die('This file cannot be accessed directly.');

$plugins->add_hook("global_start", "menumanager_createmenu");
$plugins->add_hook('admin_config_action_handler','menumanager_admin_action');
$plugins->add_hook('admin_config_menu','menumanager_admin_config_menu');
$plugins->add_hook('admin_load','menumanager_admin');

function menumanager_info()
{

	return array(
		"name"		=> "Menu Manager",
		"description"		=> "Adds an easy to add menu system for MyBB. Allows you to add/remove/disable menu items.",
		"website"		=> "http://www.mybbhacks.com",
		"author"		=> "vbgamer45 & updated by vintagedaddyo",
		"authorsite"		=> "http://www.mybbhacks.com",
		"version"		=> "1.1",
		"guid" 			=> "1006e17ac075ccc20b036bfbfb961238",
		"compatibility"	=> "18*"
		);
}


function menumanager_install()
{
	global $db, $charset;

	// Create Tables/Settings
	$db->write_query("CREATE TABLE IF NOT EXISTS ".TABLE_PREFIX."menumanager (
		  id int unsigned NOT NULL auto_increment,
		  title varchar(255),
		  link varchar(255),
		  icon varchar(255),
		  permissions varchar(255),
		  newwindow tinyint(1) default 0,
		  id_order int(5) default 0,
		  disable tinyint(1) default 0,
		  PRIMARY KEY  (id)
		) Engine=MyISAM ;");


	// Insert the menu entries

	// Portal
	$db->write_query("INSERT IGNORE INTO ".TABLE_PREFIX."menumanager
	(id,title,link,icon,id_order)
	VALUES('1','Portal','\$mybburl/search.php','\$mybburl/inc/plugins/menumanager/images/toplinks/portal.png',1)");


	// Search
	$db->write_query("INSERT IGNORE INTO ".TABLE_PREFIX."menumanager
	(id,title,link,icon,id_order)
	VALUES('2','Search','\$mybburl/search.php','\$mybburl/inc/plugins/menumanager/images/toplinks/search.png',2)");

	// Memberlist
	$db->write_query("INSERT IGNORE INTO ".TABLE_PREFIX."menumanager
	(id,title,link,icon,id_order)
	VALUES('3','Memberlist','\$mybburl/memberlist.php','\$mybburl/inc/plugins/menumanager/images/toplinks/memberlist.png',3)");

	// Calender
	$db->write_query("INSERT IGNORE INTO ".TABLE_PREFIX."menumanager
	(id,title,link,icon,id_order)
	VALUES('4','Calender','\$mybburl/calendar.php','\$mybburl/inc/plugins/menumanager/images/toplinks/calendar.png',4)");

	// Help
	$db->write_query("INSERT IGNORE INTO ".TABLE_PREFIX."menumanager
	(id,title,link,icon,id_order)
	VALUES('5','Help','\$mybburl/misc.php?action=help','\$mybburl/inc/plugins/menumanager/images/toplinks/help.png',5)");

// Stylesheet install other / Not needed atm but keep

// add menumanager stylesheet
//    $stylesheet = '';
//    $new_stylesheet = array(
//        'name'         => 'menumanager.css',
//        'tid'          => 1,
//       'attachedto'   => 'globally',
//       'stylesheet'   => $stylesheet,
//        'lastmodified' => TIME_NOW
//    );
//
//    $sid = $db->insert_query('themestylesheets', $new_stylesheet);
//    $db->update_query('themestylesheets', array('cachefile' => "css.php?stylesheet={$sid}"), "sid='{$sid}'", 1);
//
//    $query = $db->simple_select('themes', 'tid');
//    while($theme = $db->fetch_array($query))
//    {
//        require_once MYBB_ADMIN_DIR.'inc/functions_themes.php';
//        update_theme_stylesheet_list($theme['tid']);
//    }

}

function menumanager_is_installed()
{
	// Not needed for this plugin
	global $db;
	if($db->table_exists("menumanager"))
	{
			return true;
	}
	return false;
}

function menumanager_is_activated()
{
	// Not needed for this plugin
	global $db;
	if($db->table_exists("menumanager"))
	{
			return true;
	}
	return false;


}

function menumanager_uninstall()
{
	global $db;

	// Drop the Table
	$db->drop_table("menumanager");

// Stylesheet uninstall other / Not needed atm but keep

//	// remove menumanager stylesheet
//    $db->delete_query('themestylesheets', "name='menumanager.css'");
//
//    $query = $db->simple_select('themes', 'tid');
//    while($theme = $db->fetch_array($query))
//    {
//        require_once MYBB_ADMIN_DIR.'inc/functions_themes.php';
//        update_theme_stylesheet_list($theme['tid']);
//    } 

}


function menumanager_activate()
{
  require_once MYBB_ROOT."/inc/adminfunctions_templates.php";

// Adds stylesheet

find_replace_templatesets("headerinclude", '#'.preg_quote('{$stylesheets}').'#', '<link rel="stylesheet" href="{$mybb->asset_url}/inc/plugins/menumanager/css/menumanager.css">{$stylesheets}');

// Adds menu manager
  find_replace_templatesets("header", "#".preg_quote('						{$menu_portal}
						{$menu_search}
						{$menu_memberlist}
						{$menu_calendar}
						<li><a href="{$mybb->settings[\'bburl\']}/misc.php?action=help" class="help">{$lang->toplinks_help}</a></li>') . "#i", '{$pluginmenumanager}');

}

function menumanager_deactivate()
{
  require_once MYBB_ROOT."/inc/adminfunctions_templates.php";

//	Removes stylesheet

find_replace_templatesets("headerinclude", '#'.preg_quote('<link rel="stylesheet" href="{$mybb->asset_url}/inc/plugins/menumanager/css/menumanager.css">').'#', '', 0);

// Removes menu manager
  find_replace_templatesets("header", "#".preg_quote('{$pluginmenumanager}') . "#i",'						{$menu_portal}
						{$menu_search}
						{$menu_memberlist}
						{$menu_calendar}
						<li><a href="{$mybb->settings[\'bburl\']}/misc.php?action=help" class="help">{$lang->toplinks_help}</a></li>',0);


}

function menumanager_createmenu()
{
	global $db, $mybb, $theme, $lang, $pluginmenumanager;

	$pluginmenumanager = '';

	if(!$db->table_exists("menumanager"))
	{
			menumanager_install();
	}

	$query = $db->query("
			SELECT
				newwindow, permissions, icon, link, title
			FROM ".TABLE_PREFIX."menumanager

			WHERE disable = 0
			ORDER BY id_order ASC
		");
	while($menuRow = $db->fetch_array($query))
	{

		$finalUrl = $menuRow['link'];
		$finalUrl = str_replace('$mybburl',$mybb->settings['bburl'], $finalUrl);
		$finalIconUrl = $menuRow['icon'];
		$finalIconUrl  = str_replace('$mybburl',$mybb->settings['bburl'], $finalIconUrl);
		$finalIconUrl  = str_replace('$imgdir',$theme['imgdir'], $finalIconUrl);


		$pluginmenumanager .= '<li><a href="' . $finalUrl . '"' . ($menuRow['newwindow'] == 1 ? ' target="_blank" ' : '') . '>';

		if (!empty($menuRow['icon']))
			$pluginmenumanager .= '<img src="' . $finalIconUrl . '" alt="" title="" />';

		$pluginmenumanager .=  $menuRow['title'];

		$pluginmenumanager .= '</a></li>';
	}

}

function menumanager_admin_action(&$action)
{
	$action['menumanager'] = array('active'=>'menumanager');
}

function menumanager_admin_config_menu(&$admim_menu)
{
	global $lang;

	// Load Language file
	menumanager_loadlanguage();

	end($admim_menu);

	$key = (key($admim_menu)) + 10;

	$admim_menu[$key] = array
	(
		'id' => 'menumanager',
		'title' => $lang->menumanager_title,
		'link' => 'index.php?module=config/menumanager'
	);

}

function menumanager_loadlanguage()
{
	global $lang;

	$lang->load('menumanager');

}

function menumanager_admin()
{
	global $lang, $mybb, $db, $page;

	if ($page->active_action != 'menumanager')
		return false;


	// Load Language file
	menumanager_loadlanguage();

	// Create Admin Tabs
	$tabs['menumanager'] = array
		(
			'title' => $lang->menumanager_title,
			'link' =>'index.php?module=config/menumanager',
			'description'=> $lang->menumanager_description
		);
	$tabs['menumanager_add'] = array
		(
			'title' => $lang->menumanager_add,
			'link' => 'index.php?module=config/menumanager&action=add',
			'description' => $lang->menumanager_add_description
		);

	// No action
	if(!$mybb->input['action'])
	{


		$page->output_header($lang->menumanager_title);
		$page->add_breadcrumb_item($lang->menumanager_title);
		$page->output_nav_tabs($tabs,'menumanager');

		$form = new Form("index.php?module=config/menumanager&amp;action=update", "post");
		$table = new Table;
		$table->construct_header($lang->menumanager_table_title);
		$table->construct_header($lang->menumanager_table_order, array('class' => 'align_center'));
		$table->construct_header($lang->menumanager_table_status, array('class' => 'align_center'));
		$table->construct_header($lang->menumanager_table_options);
		$query = $db->query("
			SELECT
				newwindow, permissions, icon, link, title, id, id_order, disable
			FROM ".TABLE_PREFIX."menumanager
			ORDER BY id_order ASC
		");
		while($menuRow = $db->fetch_array($query))
		{

			$finalIconUrl = $menuRow['icon'];
			$finalIconUrl  = str_replace('$mybburl',$mybb->settings['bburl'], $finalIconUrl);
			$table->construct_cell( (!empty($menuRow['icon']) ? '<img src="' . $finalIconUrl . '" alt="" title="" /> ' : '') . $menuRow['title']);
			$table->construct_cell('<input type="text" name="menu[' . $menuRow['id'] . ']" value="' .$menuRow['id_order'] . '" />');
			$table->construct_cell('<a href="index.php?module=config/menumanager&amp;action=disable&id=' . $menuRow['id'] . '">'.($menuRow['disable'] ? '<font color="#FF0000">' . $lang->menumanager_disabled . '</font>' : $lang->menumanager_enabled) . '</a>', array('class' => 'align_center'));
			$table->construct_cell('<a href="index.php?module=config/menumanager&action=edit&id=' . $menuRow['id'] . '">' . $lang->menumanager_edit . '</a>');
			$table->construct_row();
		}

		if($table->num_rows() == 0)
		{
			$table->construct_cell($lang->menumanager_no_menus, array('colspan' => 4));
			$table->construct_row();

		}
		else
		{
			$table->construct_cell('<input type="submit" value="' .$lang->menumanager_update . '" />', array('colspan' => 4));
			$table->construct_row();
		}


		$form->end;
		$table->output($lang->menumanager_table_menuitems);

		$page->output_footer();



	}

	// Add Menu
	if ($mybb->input['action'] == 'add' || $mybb->input['action'] == 'add2')
	{
		$title = '';
		$link = '';
		$icon = '';



		if ($mybb->input['action'] == 'add2')
		{
			// Check Post
			$title = $mybb->input['title'];
			$icon = $mybb->input['icon'];
			$link = $mybb->input['link'];
			$newwindow = isset($_REQUEST['newwindow']) ? 1 : 0;

			if (empty($title))
			{
				$errors[] = $lang->menumanager_error_no_title;
			}

			if (empty($link))
			{
				$errors[] = $lang->menumanager_error_no_link;
			}

			if($errors)
			{
				$page->output_inline_error($errors);
			}
			else
			{

				$db->write_query("INSERT IGNORE INTO ".TABLE_PREFIX."menumanager
				(title,link,icon,newwindow,id_order)
				VALUES('$title','$link','$icon','$newwindow',0)");

				menumanager_reordermenuitems();

				admin_redirect("index.php?module=config/menumanager");

			}

		}


		$page->output_header($lang->menumanager_add);
		$page->add_breadcrumb_item($lang->menumanager_add);
		$page->output_nav_tabs($tabs, 'menumanager_add');



		$form = new Form("index.php?module=config/menumanager&amp;action=add2", "post");
		$table = new Table;

		$table->construct_cell($lang->menumanager_table_title);
		$table->construct_cell('<input type="text" size="50" name="title" value="' . $title . '" />');
		$table->construct_row();

		$table->construct_cell($lang->menumanager_link);
		$table->construct_cell('<input type="text" size="50" name="link" value="' . $link . '" />');
		$table->construct_row();

		$table->construct_cell($lang->menumanager_icon);
		$table->construct_cell('<input type="text" size="50" name="icon" value="' . $icon . '" />');
		$table->construct_row();

		$table->construct_cell($lang->menumanager_window);
		$table->construct_cell('<input type="checkbox" name="newwindow" />');
		$table->construct_row();


		$table->construct_cell('<input type="submit" value="' .$lang->menumanager_add . '" />', array('colspan' => 2));
		$table->construct_row();

		$form->end;
		$table->output($lang->menumanager_add);

		$page->output_footer();
	}

	if ($mybb->input['action'] == 'edit' || $mybb->input['action'] == 'edit2')
	{


		$id = (int) $_REQUEST['id'];

		$query = $db->query("
			SELECT
				newwindow, icon, link, title
			FROM ".TABLE_PREFIX."menumanager

			WHERE id = $id LIMIT 1
		");
		$menuRow = $db->fetch_array($query);

		$link = $menuRow['link'];
		$title = $menuRow['title'];
		$icon = $menuRow['icon'];
		$newwindow = $menuRow['newwindow'];

		if ($mybb->input['action'] == 'edit2')
		{
			// Check Post
			$title = $mybb->input['title'];
			$icon = $mybb->input['icon'];
			$link = $mybb->input['link'];
			$newwindow = isset($_REQUEST['newwindow']) ? 1 : 0;

			if (empty($title))
			{
				$errors[] = $lang->menumanager_error_no_title;
			}

			if (empty($link))
			{
				$errors[] = $lang->menumanager_error_no_link;
			}

			if($errors)
			{
				$page->output_inline_error($errors);
			}
			else
			{

				$db->write_query("UPDATE ".TABLE_PREFIX."menumanager
				SET title ='$title', link ='$link' ,icon ='$icon' ,newwindow ='$newwindow'

				WHERE id = $id LIMIT 1
				");

				menumanager_reordermenuitems();

				admin_redirect("index.php?module=config/menumanager");

			}

		}


		$page->output_header($lang->menumanager_edit);
		$page->add_breadcrumb_item($lang->menumanager_edit);
		$page->output_nav_tabs($tabs, 'menumanage');



		$form = new Form("index.php?module=config/menumanager&amp;action=edit2", "post");
		$table = new Table;

		$table->construct_cell($lang->menumanager_table_title);
		$table->construct_cell('<input type="text" size="50" name="title" value="' . $title . '" />');
		$table->construct_row();

		$table->construct_cell($lang->menumanager_link);
		$table->construct_cell('<input type="text" size="50" name="link" value="' . $link . '" />');
		$table->construct_row();

		$table->construct_cell($lang->menumanager_icon);
		$table->construct_cell('<input type="text" size="50" name="icon" value="' . $icon . '" />');
		$table->construct_row();

		$table->construct_cell($lang->menumanager_window);
		$table->construct_cell('<input type="checkbox" name="newwindow" ' . ($newwindow  == 1 ? ' checked="checked" ' : '' ) . '/>');
		$table->construct_row();


		$table->construct_cell('
		<input type="hidden" name="id" value="' . $id . '" />
		<input type="submit" value="' .$lang->menumanager_edit . '" />', array('colspan' => 2));
		$table->construct_row();

		$form->end;
		$table->output($lang->menumanager_edit);

		$page->output_footer();

	}




	if ($mybb->input['action'] == 'disable')
	{
		menumanager_reordermenuitems();

		// Get menu item id
		$id = (int) $mybb->input['id'];

		$query = $db->query("
			SELECT
				disable
			FROM ".TABLE_PREFIX."menumanager
			WHERE id = $id
		");


		$menuRow = $db->fetch_array($query);

		$db->query("
			UPDATE ".TABLE_PREFIX."menumanager
			SET disable = " . ($menuRow['disable'] == 0 ? 1 : 0) . " WHERE id = " . $id);


		admin_redirect("index.php?module=config/menumanager");
	}

	if ($mybb->input['action'] == 'delete')
	{
		menumanager_reordermenuitems();

		admin_redirect("index.php?module=config/menumanager");
	}

	if ($mybb->input['action'] == 'update')
	{


		$menuItems = $_REQUEST['menu'];

		foreach($menuItems as $menu=>  $key  )
		{
			$key = (int) $key;
			$menu = (int) $menu;

			$db->query("
			UPDATE ".TABLE_PREFIX."menumanager
			SET id_order = $key WHERE id = " . $menu);
		}

		menumanager_reordermenuitems();

		admin_redirect("index.php?module=config/menumanager");
	}

}

function menumanager_reordermenuitems()
{
	global $db;

	$query = $db->query("
			SELECT
				id
			FROM ".TABLE_PREFIX."menumanager
			ORDER BY id_order ASC
		");

		$count = 1;
	while($row2 = $db->fetch_array($query))
	{

		$db->query("
			UPDATE ".TABLE_PREFIX."menumanager
			SET id_order = $count WHERE id = " . $row2['id']);

		$count++;
	}

}


?>