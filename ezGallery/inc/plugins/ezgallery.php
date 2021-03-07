<?php
/*
ezGallery Lite
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

$plugins->add_hook('admin_config_action_handler','ezgallery_admin_action');
$plugins->add_hook('admin_config_menu','ezgallery_admin_config_menu');

$plugins->add_hook('admin_load','ezgallery_admin');
$plugins->add_hook("build_friendly_wol_location_end", "ezgallery_whosonline");
$plugins->add_hook("global_start", "ezgallery_loadmainlanguage");

function ezgallery_info()
{

	return array(
		"name"		=> "ezGallery Lite",
		"description"		=> "Simple photo gallery plugin for MyBB",
		"website"		=> "http://www.mybbhacks.com",
		"author"		=> "vbgamer45",
		"authorsite"		=> "http://www.mybbhacks.com",
		"version"		=> "2.2",
		"compatibility"	=> "18*",
		"guid" => "69b0adb3cd714eb1b03246937927c235",
		);
}


function ezgallery_install()
{
	global $db, $charset;

	$db->query("CREATE TABLE IF NOT EXISTS ".TABLE_PREFIX."gallery_pic(
	ID_PICTURE int(11) NOT NULL auto_increment,
	ID_MEMBER mediumint(8) unsigned NOT NULL default '0',
	date int(10) unsigned NOT NULL default '0',
	title VARCHAR(255) NOT NULL,
	description text,
	 views int(10) NOT NULL default '0',
	 filesize int(10) NOT NULL default '0',
	 height int(10) NOT NULL default '0',
	 width int(10) NOT NULL default '0',
	 orginalfilename tinytext,
	 filename tinytext,
	 thumbfilename tinytext,
	 commenttotal int(10) NOT NULL default '0',
	 ID_CAT int(10) NOT NULL default '0',
	 approved tinyint(4) NOT NULL default '0',
	 allowcomments tinyint(4) NOT NULL default '0',
	 keywords VARCHAR(100),
	 KEY ID_CAT (ID_CAT),
	 KEY ID_MEMBER (ID_MEMBER),
	PRIMARY KEY  (ID_PICTURE))");


	//Picture comments
	$db->query("CREATE TABLE IF NOT EXISTS ".TABLE_PREFIX."gallery_comment(
	ID_COMMENT int(11) NOT NULL auto_increment,
	ID_PICTURE int(11) NOT NULL,
	ID_MEMBER mediumint(8) unsigned NOT NULL default '0',
	approved tinyint(4) NOT NULL default '0',
	comment text,
	date int(10) unsigned NOT NULL default '0',
	 KEY  ID_PICTURE (ID_PICTURE),
	 KEY ID_MEMBER (ID_MEMBER),
	PRIMARY KEY  (ID_COMMENT))");

	//Gallery Category
	$db->query("CREATE TABLE IF NOT EXISTS ".TABLE_PREFIX."gallery_cat
	(ID_CAT mediumint(8) NOT NULL auto_increment,
	title VARCHAR(255) NOT NULL,
	ID_PARENT int(10) NOT NULL default '0',
	description text NOT NULL,
	roworder mediumint(8) unsigned NOT NULL default '0',
	image VARCHAR(255) NOT NULL,
	total int(11) NOT NULL default '0',
	LAST_ID_PICTURE int(11) NOT NULL default '0',
	 KEY  LAST_ID_PICTURE (LAST_ID_PICTURE),
	PRIMARY KEY  (ID_CAT))");


	//Gallery Reported Images
	$db->query("CREATE TABLE IF NOT EXISTS ".TABLE_PREFIX."gallery_report
	(ID int(11) NOT NULL auto_increment,
	ID_PICTURE int(11) NOT NULL,
	ID_MEMBER mediumint(8) unsigned NOT NULL default '0',
	comment text,
	date int(10) unsigned NOT NULL default '0',
	 KEY  ID_PICTURE (ID_PICTURE),
	PRIMARY KEY  (ID))");


	$db->query("CREATE TABLE IF NOT EXISTS ".TABLE_PREFIX."gallery_permissions
	(
	ID_GROUP int(11),
	`view` tinyint(1) default 0,
	`add` tinyint(1) default 0,
	`edit` tinyint(1) default 0,
	`delete` tinyint(1) default 0,
	`comment` tinyint(1) default 0,
	`report` tinyint(1) default 0,
	`autoapprove` tinyint(1) default 0,
	`manage` tinyint(1) default 0,

	PRIMARY KEY  (ID_GROUP))");

	// Guests
	$db->query("INSERT IGNORE INTO ".TABLE_PREFIX."gallery_permissions VALUES (1,1,1,0,0,0,0,0,0)");
	// Reg Members
	$db->query("INSERT IGNORE INTO ".TABLE_PREFIX."gallery_permissions VALUES (2,1,1,1,0,1,1,0,0)");
	// Super Mod
	$db->query("INSERT IGNORE INTO ".TABLE_PREFIX."gallery_permissions VALUES (3,1,1,1,0,1,1,0,0)");
	// Administrator
	$db->query("INSERT IGNORE INTO ".TABLE_PREFIX."gallery_permissions VALUES (4,1,1,1,1,1,1,1,1)");
	// Awaiting Activation
	$db->query("INSERT IGNORE INTO ".TABLE_PREFIX."gallery_permissions VALUES (5,1,0,0,0,0,0,0,0)");
	// Moderators
	$db->query("INSERT IGNORE INTO ".TABLE_PREFIX."gallery_permissions VALUES (6,1,1,1,0,1,1,0,0)");
	// Banned
	$db->query("INSERT IGNORE INTO ".TABLE_PREFIX."gallery_permissions VALUES (7,0,0,0,0,0,0,0,0)");



	$db->query("CREATE TABLE IF NOT EXISTS ".TABLE_PREFIX."gallery_settings (
	  variable tinytext NOT NULL,
	  value text NOT NULL,
	  PRIMARY KEY (variable(30))
	) Engine=MyISAM");


	// Insert the settings
	$db->query("INSERT IGNORE INTO ".TABLE_PREFIX."gallery_settings VALUES ('gallery_max_height', '2500')");
	$db->query("INSERT IGNORE INTO ".TABLE_PREFIX."gallery_settings VALUES ('gallery_max_width', '2500')");
	$db->query("INSERT IGNORE INTO ".TABLE_PREFIX."gallery_settings VALUES ('gallery_max_filesize', '5000000')");
	$db->query("INSERT IGNORE INTO ".TABLE_PREFIX."gallery_settings VALUES ('gallery_set_images_per_page', '16')");
	$db->query("INSERT IGNORE INTO ".TABLE_PREFIX."gallery_settings VALUES ('gallery_set_images_per_row', '4')");
	$db->query("INSERT IGNORE INTO ".TABLE_PREFIX."gallery_settings VALUES ('gallery_set_thumb_width', '120')");
	$db->query("INSERT IGNORE INTO ".TABLE_PREFIX."gallery_settings VALUES ('gallery_set_thumb_height', '78')");



	$db->query("INSERT IGNORE INTO ".TABLE_PREFIX."gallery_settings VALUES ('gallery_who_viewing', '0')");
	$db->query("INSERT IGNORE INTO ".TABLE_PREFIX."gallery_settings VALUES ('gallery_commentchoice', '0')");


	$db->query("INSERT IGNORE INTO ".TABLE_PREFIX."gallery_settings VALUES ('gallery_set_showcode_bbc_image', '0')");
	$db->query("INSERT IGNORE INTO ".TABLE_PREFIX."gallery_settings VALUES ('gallery_set_showcode_directlink', '0')");
	$db->query("INSERT IGNORE INTO ".TABLE_PREFIX."gallery_settings VALUES ('gallery_set_showcode_htmllink', '0')");


	$db->query("INSERT IGNORE INTO ".TABLE_PREFIX."gallery_settings VALUES ('gallery_version', '1.0')");


	// Try to make the folder writable at least on linux
	if (!is_writable(MYBB_ROOT . 'gallery/'))
	{
		@chmod(MYBB_ROOT . 'gallery/',0755);
	}

}

function ezgallery_is_installed()
{
	// Not needed for this plugin
	global $db;


	if($db->table_exists("gallery_settings"))
	{
		$query = $db->query("
				SELECT
					value
				FROM ".TABLE_PREFIX."gallery_settings

				WHERE variable = 'gallery_version'

			");
		$row = $db->fetch_array($query);


		if (!empty($row['value']))
			return true;
		else
			return false;


	}
	return false;
}


function ezgallery_uninstall()
{
	global $db;


	if($db->table_exists("gallery_settings"))
	{
		// Delete the version
		$db->query("DELETE FROM ".TABLE_PREFIX."gallery_settings WHERE variable = 'gallery_version'");

	}


}


function ezgallery_activate()
{
  require_once MYBB_ROOT."/inc/adminfunctions_templates.php";

  find_replace_templatesets("header", "#".preg_quote('{$menu_search}') . "#i", '{$menu_search}<li class="gallery"><a href="{$mybb->settings[\'bburl\']}/ezgallery.php" style="background-image: url({$mybb->settings[\'bburl\']}/images/icons/photo.png);">{$lang->ezgallery_menu}</a></li>');

}

function ezgallery_deactivate()
{
  require_once MYBB_ROOT."/inc/adminfunctions_templates.php";

  find_replace_templatesets(
  "header", "#".preg_quote('{$menu_search}<li class="gallery"><a href="{$mybb->settings[\'bburl\']}/ezgallery.php" style="background-image: url({$mybb->settings[\'bburl\']}/images/icons/photo.png);">{$lang->ezgallery_menu}</a></li>') . "#i",
  '{$menu_search}',0);


}

function ezgallery_admin_action(&$action)
{
	$action['ezgallery'] = array('active'=>'ezgallery');
}

function ezgallery_admin_config_menu(&$admim_menu)
{
	global $lang;

	// Load Language file
	ezgallery_loadlanguage();

	end($admim_menu);

	$key = (key($admim_menu)) + 10;

	$admim_menu[$key] = array
	(
		'id' => 'ezgallery',
		'title' => $lang->ezgallery_title,
		'link' => 'index.php?module=config/ezgallery'
	);

}

function ezgallery_loadlanguage()
{
	global $lang;

	$lang->load('ezgallery');

}

function ezgallery_admin()
{
	global $lang, $mybb, $db, $page, $tabs, $plugins;

	if ($page->active_action != 'ezgallery')
		return false;


	// Load Language file
	ezgallery_loadlanguage();

	require_once MYBB_ROOT.'inc/ezgallery.lib.php';

	LoadGallerySettings();


	$page->add_breadcrumb_item($lang->ezgallery_title);

	// Create Admin Tabs
	$tabs['ezgallery_settings'] = array
		(
			'title' => $lang->gallery_text_settings,
			'link' => 'index.php?module=config/ezgallery&action=adminset',
			'description' => $lang->gallery_set_description
		);
	$tabs['ezgallery_categories'] = array
		(
			'title' => $lang->gallery_form_managecats,
			'link' => 'index.php?module=config/ezgallery&action=admincat',
			'description' => $lang->gallery_managecats_description
		);
	$tabs['ezgallery_approveimages'] = array
		(
			'title' => $lang->gallery_form_approveimages,
			'link' => 'index.php?module=config/ezgallery&action=approvelist',
			'description' => $lang->gallery_approveimages_description,
		);

	$tabs['ezgallery_reportimages'] = array
		(
			'title' => $lang->gallery_form_reportimages,
			'link' => 'index.php?module=config/ezgallery&action=reportlist',
			'description' => $lang->gallery_reportimages_description,
		);

	$tabs['ezgallery_permissions'] = array
		(
			'title' => $lang->gallery_text_permissions,
			'link' => 'index.php?module=config/ezgallery&action=permissions',
			'description' => $lang->gallery_permissions_description
		);


	// Gallery Actions
	$subActions = array(
		'admincat' => 'AdminCats',
		'adminset'=> 'AdminSettings',
		'adminset2'=> 'AdminSettings2',
		'deletereport' => 'DeleteReport',
		'reportlist' => 'ReportList',
		'delcomment' => 'DeleteComment',
		'catup' => 'CatUp',
		'catdown' => 'CatDown',
		'addcat' => 'AddCategory',
		'addcat2' => 'AddCategory2',
		'editcat' => 'EditCategory',
		'editcat2' => 'EditCategory2',
		'deletecat' => 'DeleteCategory',
		'deletecat2' => 'DeleteCategory2',
		'approvelist' => 'ApproveList',
		'approve' => 'ApprovePicture',
		'unapprove' => 'UnApprovePicture',
		'permissions' => 'GalleryPermissions',
		'permissions2' => 'GalleryPermissions2',
	);

	$plugins->run_hooks("gallery_admin_subactions");


	// Follow the sa or just go to main function
	@$sa = $mybb->input['action'];
	if (!empty($subActions[$sa]))
		$subActions[$sa]();
	else
		AdminSettings();


}

function AdminSettings()
{
	global $lang, $page, $gallerySettings, $mybb;

	$page->output_header($lang->gallery_text_title . ' - ' . $lang->gallery_text_settings);

	DoGalleryAdminTabs();
echo 'Do you like ezGallery but want more features? Check out <a href="http://www.mybbhacks.com/ezgallerypro.php" target="_blank">ezGallery Pro</a>. The ultimate gallery solution for MyBB!<br /><br />
			<form method="post" action="index.php?module=config/ezgallery&action=adminset2">';
				$table = new Table;


				$table->construct_cell($lang->gallery_set_path);
				$table->construct_cell('<input type="text" name="gallery_path" value="' .  $gallerySettings['gallery_path'] . '" size="50" />');
				$table->construct_row();
				if (!is_writable($gallerySettings['gallery_path']))
				{
					$table->construct_cell('<font color="#FF0000"><b>' . $lang->gallery_write_error  . $gallerySettings['gallery_path'] . '</b></font>',array('colspan' => 2));
					$table->construct_row();
				}


				$table->construct_cell($lang->gallery_set_url);
				$table->construct_cell('<input type="text" name="gallery_url" value="' .  $gallerySettings['gallery_url'] . '" size="50" />');
				$table->construct_row();



				$table->construct_cell($lang->gallery_set_maxheight);
				$table->construct_cell('<input type="text" name="gallery_max_height" value="' .  $gallerySettings['gallery_max_height'] . '" />');
				$table->construct_row();


				$table->construct_cell($lang->gallery_set_maxwidth);
				$table->construct_cell('<input type="text" name="gallery_max_width" value="' .  $gallerySettings['gallery_max_width'] . '" />');
				$table->construct_row();

				$table->construct_cell($lang->gallery_set_filesize);
				$table->construct_cell('<input type="text" name="gallery_max_filesize" value="' .  $gallerySettings['gallery_max_filesize'] . '" /> (bytes)');
				$table->construct_row();

				$table->construct_cell($lang->gallery_upload_max_filesize);
				$table->construct_cell('<a href="http://www.php.net/manual/en/ini.core.php#ini.upload-max-filesize" target="_blank">' . @ini_get("upload_max_filesize") . '</a>');
				$table->construct_row();
				$table->construct_cell($lang->gallery_post_max_size);
				$table->construct_cell('<a href="http://www.php.net/manual/en/ini.core.php#ini.post-max-size" target="_blank">' . @ini_get("post_max_size") . '</a>');
				$table->construct_row();
				$table->construct_cell($lang->gallery_upload_limits_notes,array('colspan' => 2));
				$table->construct_row();

				$table->construct_cell($lang->gallery_set_images_per_page);
				$table->construct_cell('<input type="text" name="gallery_set_images_per_page" value="' .  $gallerySettings['gallery_set_images_per_page'] . '" />');
				$table->construct_row();

				$table->construct_cell($lang->gallery_set_images_per_row);
				$table->construct_cell('<input type="text" name="gallery_set_images_per_row" value="' .  $gallerySettings['gallery_set_images_per_row'] . '" />');
				$table->construct_row();

				$table->construct_cell($lang->gallery_set_thumb_height);
				$table->construct_cell('<input type="text" name="gallery_set_thumb_height" value="' .  $gallerySettings['gallery_set_thumb_height'] . '" />');
				$table->construct_row();

				$table->construct_cell($lang->gallery_set_thumb_width);
				$table->construct_cell('<input type="text" name="gallery_set_thumb_width" value="' .  $gallerySettings['gallery_set_thumb_width'] . '" />');
				$table->construct_row();




				$table->construct_cell($lang->gallery_set_whoonline);
				$table->construct_cell('<input type="checkbox" name="gallery_who_viewing" ' . ($gallerySettings['gallery_who_viewing'] ? ' checked="checked" ' : '') . ' />');
				$table->construct_row();


				$table->construct_cell($lang->gallery_set_commentschoice);
				$table->construct_cell('<input type="checkbox" name="gallery_commentchoice" ' . (!empty($gallerySettings['gallery_commentchoice']) ? ' checked="checked" ' : '') . ' />');
				$table->construct_row();
				$table->construct_cell('<b>' . $lang->gallery_txt_image_linking . '</b>',array('colspan' => 2));
				$table->construct_row();

				$table->construct_cell($lang->gallery_set_showcode_bbc_image);
				$table->construct_cell('<input type="checkbox" name="gallery_set_showcode_bbc_image" ' . ($gallerySettings['gallery_set_showcode_bbc_image'] ? ' checked="checked" ' : '') . ' />');
				$table->construct_row();
				$table->construct_cell($lang->gallery_set_showcode_directlink);
				$table->construct_cell('<input type="checkbox" name="gallery_set_showcode_directlink" ' . ($gallerySettings['gallery_set_showcode_directlink'] ? ' checked="checked" ' : '') . ' />');
				$table->construct_row();
				$table->construct_cell($lang->gallery_set_showcode_htmllink);
				$table->construct_cell('<input type="checkbox" name="gallery_set_showcode_htmllink" ' . ($gallerySettings['gallery_set_showcode_htmllink'] ? ' checked="checked" ' : '') . ' />');
				$table->construct_row();


				$table->construct_cell('<input type="submit" name="savesettings" value="' . $lang->gallery_save_settings .'" />', array('colspan' => 2));

				$table->construct_row();

				$table->output($lang->gallery_text_settings);

				echo '
                <input type="hidden" name="my_post_key" value="' . $mybb->post_code . '" />
			</form>


			<br />
			<b>' . $lang->gallery_text_permissions . '</b><br/><span class="smalltext">' . $lang->gallery_set_permissionnotice . '</span>
			<br /><a href="index.php?module=config/ezgallery&action=permissions">' . $lang->gallery_set_editpermissions  . '</a>





<b>Has ezGallery helped you?</b> Then support the developers:<br />
    <form action="https://www.paypal.com/cgi-bin/webscr" method="post">
	<input type="hidden" name="cmd" value="_xclick">
	<input type="hidden" name="business" value="sales@visualbasiczone.com">
	<input type="hidden" name="item_name" value="MyBB ezGallery">
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


function AdminSettings2()
{
	global $mybb;
    // Verify incoming POST request
    verify_post_check($mybb->get_input('my_post_key'));
	// Get the settings

	$gallery_max_height = intval($_REQUEST['gallery_max_height']);
	$gallery_max_width =  intval($_REQUEST['gallery_max_width']);
	$gallery_max_filesize =  intval($_REQUEST['gallery_max_filesize']);
	$gallery_set_images_per_page = intval($_REQUEST['gallery_set_images_per_page']);
	$gallery_set_images_per_row = intval($_REQUEST['gallery_set_images_per_row']);
	$gallery_commentchoice =  isset($_REQUEST['gallery_commentchoice']) ? 1 : 0;

	$gallery_path = $_REQUEST['gallery_path'];
	$gallery_url = $_REQUEST['gallery_url'];
	$gallery_who_viewing = isset($_REQUEST['gallery_who_viewing']) ? 1 : 0;

	$gallery_set_thumb_width = intval($_REQUEST['gallery_set_thumb_width']);
	$gallery_set_thumb_height = intval($_REQUEST['gallery_set_thumb_height']);


	// Image Linking codes
	$gallery_set_showcode_bbc_image = isset($_REQUEST['gallery_set_showcode_bbc_image']) ? 1 : 0;
	$gallery_set_showcode_directlink = isset($_REQUEST['gallery_set_showcode_directlink']) ? 1 : 0;
	$gallery_set_showcode_htmllink = isset($_REQUEST['gallery_set_showcode_htmllink']) ? 1 : 0;


	UpdateGallerySettings(
	array(
	'gallery_max_height' => $gallery_max_height,
	'gallery_max_width' => $gallery_max_width,
	'gallery_max_filesize' => $gallery_max_filesize,
	'gallery_path' => $gallery_path,
	'gallery_url' => $gallery_url,
	'gallery_set_images_per_page' => $gallery_set_images_per_page,
	'gallery_set_images_per_row' => $gallery_set_images_per_row,

	'gallery_set_thumb_width' => $gallery_set_thumb_width,
	'gallery_set_thumb_height' => $gallery_set_thumb_height,

	'gallery_commentchoice' => $gallery_commentchoice,
	'gallery_who_viewing' => $gallery_who_viewing,

	'gallery_set_showcode_bbc_image' => $gallery_set_showcode_bbc_image,
	'gallery_set_showcode_directlink' => $gallery_set_showcode_directlink,
	'gallery_set_showcode_htmllink' => $gallery_set_showcode_htmllink,

	));

	admin_redirect('index.php?module=config/ezgallery&action=adminset');

}

function AddCategory()
{
	global $context, $lang, $page, $mybb;

	$page->output_header($lang->gallery_text_title . ' - ' . $lang->gallery_text_addcategory);

	DoGalleryAdminTabs('ezgallery_categories');

	echo '
<form method="post" name="catform" id="catform" action="index.php?module=config/ezgallery&action=addcat2" accept-charset="', $context['character_set'], '">
';

	$table = new Table;

  $table->construct_cell($lang->gallery_form_title);
  $table->construct_cell('<input type="text" name="title" size="64" maxlength="100" />');


  $table->construct_row();
  $table->construct_cell($lang->gallery_form_description);
  $table->construct_cell('<textarea name="description" rows="5" cols="50"></textarea>');

  $table->construct_row();
  $table->construct_cell($lang->gallery_form_icon);
  $table->construct_cell('<input type="text" name="image" size="64" maxlength="100" />');


  $table->construct_row();


  $table->construct_cell('<input type="submit" value="' . $lang->gallery_text_addcategory . '" name="submit" />',array("colspan" =>2,'align' =>"center"));
  $table->construct_row();

  $table->output($lang->gallery_text_addcategory);

echo '
<input type="hidden" name="my_post_key" value="' . $mybb->post_code . '" />
</form>';

	$page->output_footer();

}

function AddCategory2()
{
	global $db, $lang, $mybb;

    // Verify incoming POST request
    verify_post_check($mybb->get_input('my_post_key'));

	$title = htmlspecialchars_uni($db->escape_string($_REQUEST['title']));
	$description = htmlspecialchars_uni($db->escape_string($_REQUEST['description']));
	$image =  htmlspecialchars_uni($db->escape_string($_REQUEST['image']));

	if (trim($title) == '')
		fatal_error($lang->gallery_error_cat_title,false);

	// Do the order
	$dbresult = $db->query("
	SELECT
		roworder
	FROM ".TABLE_PREFIX."gallery_cat ORDER BY roworder DESC");
	$row = $db->fetch_array($dbresult);

	$order = $row['roworder'];
	$order++;

	// Insert the category
	$db->query("INSERT INTO ".TABLE_PREFIX."gallery_cat
			(title, description,roworder,image)
		VALUES ('$title', '$description',$order,'$image')");



	 admin_redirect('index.php?module=config/ezgallery&action=admincat');
}

function AdminCats()
{
	global $context, $lang, $db, $page, $mybb;

	$page->output_header($lang->gallery_text_title . ' - ' . $lang->gallery_form_managecats);

	DoGalleryAdminTabs('ezgallery_categories');

	$dbresult = $db->query("
		SELECT
			ID_CAT, title, roworder, description, image
		FROM ".TABLE_PREFIX."gallery_cat ORDER BY roworder ASC");
	$context['gallery_manage_cats'] = array();
	while($row = $db->fetch_array($dbresult))
	{
		$context['gallery_manage_cats'][] = $row;
	}


	require_once MYBB_ROOT."inc/class_parser.php";
		$parser = new postParser();
		$parser_options = array(
			"allow_html" => 1,
			"allow_mycode" => 1,
			"allow_smilies" => 1,
			"allow_imgcode" => 1,
			"filter_badwords" => 1
		);



	$table = new Table;

	$table->construct_header($lang->gallery_text_galleryname);
	$table->construct_header($lang->gallery_text_gallerydescription);
	$table->construct_header($lang->gallery_text_totalimage);
	$table->construct_header($lang->gallery_text_reorder);
	$table->construct_header($lang->gallery_text_options);


		foreach($context['gallery_manage_cats'] as $row)
		{

			$totalpics = GetTotalPicturesBYCATID($row['ID_CAT']);

			$table->construct_cell('<a href="' . $mybb->settings['bburl'] . '/ezgallery.php?cat=' . $row['ID_CAT'] . '">' . $row['title'] . '</a>');

			$table->construct_cell($parser->parse_message($row['description'], $parser_options));

			$table->construct_cell('<div align="center">' . $totalpics . '</div>');

			// Show Edit Delete and Order category
			$table->construct_cell('<a href="index.php?module=config/ezgallery&action=catup&cat=' . $row['ID_CAT'] . '">' . $lang->gallery_text_up . '</a>&nbsp;<a href="index.php?module=config/ezgallery&action=catdown&cat=' . $row['ID_CAT'] . '">' . $lang->gallery_text_down . '</a>');

			$table->construct_cell('<a href="index.php?module=config/ezgallery&action=editcat&cat=' . $row['ID_CAT'] . '">' . $lang->gallery_text_edit .'</a>&nbsp;<a href="index.php?module=config/ezgallery&action=deletecat&cat=' . $row['ID_CAT'] . '">' . $lang->gallery_text_delete .'</a>');
			$table->construct_row();

		}


	$table->construct_cell('<a href="index.php?module=config/ezgallery&action=addcat">' . $lang->gallery_text_addcategory . '</a>',array("colspan"=>5,"align" =>'center'));


	$table->construct_row();

	$table->output($lang->gallery_form_managecats);


	$page->output_footer();

}

function CatUp()
{
	global $db, $lang;

	// Get the cat id
	@$cat = intval($_REQUEST['cat']);
	ReOrderCats($cat);

	//Check if there is a category above it
	//First get our row order
	$dbresult1 = $db->query("
	SELECT
		roworder
	FROM ".TABLE_PREFIX."gallery_cat
	WHERE ID_CAT = $cat");
	$row = $db->fetch_array($dbresult1);
	$oldrow = $row['roworder'];
	$o = $row['roworder'];
	$o--;


	$dbresult = $db->query("
	SELECT
		ID_CAT, roworder
	FROM ".TABLE_PREFIX."gallery_cat
	WHERE roworder = $o");
	if($db->num_rows($dbresult)== 0)
		fatal_error($lang->gallery_nocatabove,false);
	$row2 = $db->fetch_array($dbresult);


	// Swap the order Id's
	$db->query("UPDATE ".TABLE_PREFIX."gallery_cat
		SET roworder = $oldrow WHERE ID_CAT = " .$row2['ID_CAT']);

	$db->query("UPDATE ".TABLE_PREFIX."gallery_cat
		SET roworder = $o WHERE ID_CAT = $cat");




	// Redirect to index to view cats
	admin_redirect('index.php?module=config/ezgallery&action=admincat');
}

function CatDown()
{
	global $db, $lang;

	// Get the cat id
	@$cat = intval($_REQUEST['cat']);
	ReOrderCats($cat);
	// Check if there is a category below it
	// First get our row order
	$dbresult1 = $db->query("
	SELECT
		roworder
	FROM ".TABLE_PREFIX."gallery_cat
	WHERE ID_CAT = $cat LIMIT 1");
	$row = $db->fetch_array($dbresult1);
	$oldrow = $row['roworder'];
	$o = $row['roworder'];
	$o++;


	$dbresult = $db->query("
	SELECT
		ID_CAT, roworder
	FROM ".TABLE_PREFIX."gallery_cat
	WHERE roworder = $o");
	if($db->num_rows($dbresult) == 0)
		fatal_error($lang->gallery_nocatbelow,false);
	$row2 = $db->fetch_array($dbresult);


	//Swap the order Id's
	$db->query("UPDATE ".TABLE_PREFIX."gallery_cat
		SET roworder = $oldrow WHERE ID_CAT = " .$row2['ID_CAT']);

	$db->query("UPDATE ".TABLE_PREFIX."gallery_cat
		SET roworder = $o WHERE ID_CAT = $cat");


	// Redirect to index to view cats
	admin_redirect('index.php?module=config/ezgallery&action=admincat');
}


function ApproveList()
{
	global $context, $lang, $db, $page, $mybb, $gallerySettings;

	$page->output_header($lang->gallery_text_title . ' - ' . $lang->gallery_form_approveimages);

	DoGalleryAdminTabs('ezgallery_approveimages');

	$dbresult = $db->query("
		  	SELECT
		  		p.ID_PICTURE, p.thumbfilename, p.title, p.ID_MEMBER, m.username, p.date, p.description,
		  		p.filename, p.height, p.width
		  	FROM ".TABLE_PREFIX."gallery_pic as p
		  	LEFT JOIN ".TABLE_PREFIX."users AS m  on (p.ID_MEMBER = m.uid)
		  	WHERE p.approved = 0 ORDER BY p.ID_PICTURE DESC");
	$context['gallery_approve_list'] = array();
	while($row = $db->fetch_array($dbresult))
	{
		$context['gallery_approve_list'][] = $row;
	}


	$table = new Table;
	$table->construct_header($lang->gallery_app_image);

	$table->construct_header($lang->gallery_app_title);
	$table->construct_header($lang->gallery_app_description);
	$table->construct_header($lang->gallery_app_date);
	$table->construct_header($lang->gallery_app_membername);
	$table->construct_header($lang->gallery_text_options);

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

			foreach($context['gallery_approve_list'] as $row)
			{
				$table->construct_cell('<a href="' . $mybb->settings['bburl'] . '/ezgallery.php?action=view&id=' . $row['ID_PICTURE'] . '">
				<img src="' . $gallerySettings['gallery_url'] . $row['thumbfilename'] . '"   border="0" /></a>');

				$table->construct_cell($row['title']);

				$table->construct_cell($parser->parse_message($row['description'],$parser_options));

				$table->construct_cell(my_date($mybb->settings['dateformat'], $row['date']));

				if ($row['username'] != '')
					 $table->construct_cell('<a href="index.php?module=user/users&action=edit&uid=' . $row['ID_MEMBER'] . '">'  . $row['username'] . '</a>');
				else
					$table->construct_cell($lang->gallery_guest);


				$table->construct_cell('<a href="index.php?module=config/ezgallery&action=approve&id=' . $row['ID_PICTURE'] . '">' . $lang->gallery_text_approve  . '</a><br /><a href="' . $mybb->settings['bburl'] . '/ezgallery.php?action=edit&id=' . $row['ID_PICTURE'] . '">' . $lang->gallery_text_edit . '</a><br /><a href="' . $mybb->settings['bburl'] . '/ezgallery.php?action=delete&id=' . $row['ID_PICTURE'] . '">' . $lang->gallery_text_delete . '</a></td>');
				$table->construct_row();

			}


	$table->output($lang->gallery_form_approveimages);


	$page->output_footer();

}

function DeleteCategory()
{
	global $lang, $page, $mybb;

	$cat = intval($_REQUEST['cat']);

	$page->output_header($lang->gallery_text_title . ' - ' . $lang->gallery_text_delcategory);

	echo '
<form method="post" action="index.php?module=config/ezgallery&action=deletecat2">';

$table = new Table;

	$table->construct_cell('<div align="center">
    <b>' . $lang->gallery_warn_category . '</b>
    <br />
    <input type="hidden" value="' . $cat . '" name="cat" />
    <input type="submit" value="' . $lang->gallery_text_delcategory . '" name="submit" /></div>');
$table->construct_row();
$table->output($lang->gallery_text_delcategory);

echo '
<input type="hidden" name="my_post_key" value="' . $mybb->post_code . '" />
</form>
';

	$page->output_footer();
}

function DeleteCategory2()
{
	global $db, $gallerySettings, $mybb;

    // Verify incoming POST request
    verify_post_check($mybb->get_input('my_post_key'));
    
	$catid = intval($_REQUEST['cat']);

	$dbresult = $db->query("
	SELECT
		ID_PICTURE, thumbfilename, filename
	FROM ".TABLE_PREFIX."gallery_pic
	WHERE ID_CAT = $catid");

	while($row = $db->fetch_array($dbresult))
	{
		// Delete Files
		// Delete Large image
		@unlink($gallerySettings['gallery_path'] . $row['filename']);
		// Delete Thumbnail
		@unlink($gallerySettings['gallery_path'] . $row['thumbfilename']);

		$db->query("DELETE FROM ".TABLE_PREFIX."gallery_comment WHERE ID_PICTURE  = " . $row['ID_PICTURE']);

		$db->query("DELETE FROM ".TABLE_PREFIX."gallery_report WHERE ID_PICTURE  = " . $row['ID_PICTURE']);

	}
	// Delete All Pictures
	$db->query("DELETE FROM ".TABLE_PREFIX."gallery_pic WHERE ID_CAT = $catid");

	// Finally delete the category
	$db->query("DELETE FROM ".TABLE_PREFIX."gallery_cat WHERE ID_CAT = $catid LIMIT 1");


	admin_redirect('index.php?module=config/ezgallery&action=admincat');
}

function EditCategory()
{
	global $context, $lang, $db, $page, $mybb;

	$page->output_header($lang->gallery_text_title . ' - ' . $lang->gallery_text_editcategory);

	DoGalleryAdminTabs('ezgallery_categories');

	@$cat = intval($_REQUEST['cat']);

	if (empty($cat))
		fatal_error($lang->gallery_error_no_cat);

	$dbresult = $db->query("
	SELECT
		ID_CAT, title, image, description
	FROM ".TABLE_PREFIX."gallery_cat
	WHERE ID_CAT = $cat LIMIT 1");
	$row = $db->fetch_array($dbresult);

	echo '
<form method="post" name="catform" id="catform" action="index.php?module=config/ezgallery&action=editcat2" accept-charset="', $context['character_set'], '">
';
	$table = new Table;

	  $table->construct_cell($lang->gallery_form_title);
   	  $table->construct_cell('<input type="text" name="title" size="64" maxlength="100" value="' . $row['title'] . '" />');
	  $table->construct_row();
	  $table->construct_cell($lang->gallery_form_description);
	  $table->construct_cell('<textarea name="description" rows="5" cols="50">' . $row['description'] . '</textarea>');
	  $table->construct_row();
	  $table->construct_cell($lang->gallery_form_icon);
	  $table->construct_cell('<input type="text" name="image" size="64" maxlength="100" value="' . $row['image'] . '" />');
	  $table->construct_row();
	  $table->construct_cell('<input type="hidden" value="' . $row['ID_CAT'] . '" name="catid" />
	    <input type="submit" value="' . $lang->gallery_text_editcategory . '" name="submit" />',array("colspan" => 2));
	  $table->construct_row();

$table->output($lang->gallery_text_editcategory);
echo '
<input type="hidden" name="my_post_key" value="' . $mybb->post_code . '" />
</form>';


	$page->output_footer();

}

function EditCategory2()
{
	global $db, $lang, $mybb;
    
    
    // Verify incoming POST request
    verify_post_check($mybb->get_input('my_post_key'));

	// Clean the input
	$title = htmlspecialchars_uni($db->escape_string($_REQUEST['title']));
	$description = htmlspecialchars_uni($db->escape_string($_REQUEST['description']));
	$catid = intval($_REQUEST['catid']);
	$image = htmlspecialchars_uni($db->escape_string($_REQUEST['image']));

	if (trim($title) == '')
		fatal_error($lang->gallery_error_cat_title,false);

	// Update the category
	$db->query("UPDATE ".TABLE_PREFIX."gallery_cat
		SET title = '$title', image = '$image', description = '$description' WHERE ID_CAT = $catid LIMIT 1");


	admin_redirect('index.php?module=config/ezgallery&action=admincat');

}

function DeleteComment()
{
	global $db, $lang, $mybb;

	$id = intval($_REQUEST['id']);
	if (empty($id))
		fatal_error($lang->gallery_error_no_com_selected);


	//Get the picture ID for redirect
	$dbresult = $db->query("
	SELECT
		ID_PICTURE,ID_COMMENT, ID_MEMBER
	FROM ".TABLE_PREFIX."gallery_comment
	WHERE ID_COMMENT = $id LIMIT 1");
	$row = $db->fetch_array($dbresult);
	$picid = $row['ID_PICTURE'];


	// Now delete the comment.
	$db->query("DELETE FROM ".TABLE_PREFIX."gallery_comment WHERE ID_COMMENT = $id LIMIT 1");


	// Update Comment total
	  $dbresult = $db->query("UPDATE ".TABLE_PREFIX."gallery_pic
		SET commenttotal = commenttotal - 1 WHERE ID_PICTURE = $picid LIMIT 1");


	// Redirect to the picture
	admin_redirect($mybb->settings['bburl'] . '/ezgallery.php?sa=view&id=' . $picid);
}

function ReOrderCats($cat)
{
	global $db;


	$dbresult = $db->query("
	SELECT
		ID_CAT, roworder
	FROM ".TABLE_PREFIX."gallery_cat ORDER BY roworder ASC");
	if($db->num_rows($dbresult) != 0)
	{
		$count = 1;
		while($row2 = $db->fetch_array($dbresult))
		{
			$db->query("UPDATE ".TABLE_PREFIX."gallery_cat
			SET roworder = $count WHERE ID_CAT = " . $row2['ID_CAT']);
			$count++;
		}
	}

}

function ReportList()
{
	global $context, $lang, $db, $page, $mybb;

	$page->output_header($lang->gallery_text_title . ' - ' . $lang->gallery_form_reportimages);

	DoGalleryAdminTabs('ezgallery_reportimages');


	$table = new Table;

	$table->construct_header($lang->gallery_rep_piclink);
	$table->construct_header($lang->gallery_rep_comment);
	$table->construct_header($lang->gallery_app_date);
	$table->construct_header($lang->gallery_rep_reportby);
	$table->construct_header($lang->gallery_text_options);


	// List all reported pictures
	$dbresult = $db->query("
		  	SELECT
		  		r.ID, r.ID_PICTURE, r.ID_MEMBER, m.username, r.date, r.comment
		  	FROM ".TABLE_PREFIX."gallery_report as r
		  	LEFT JOIN ".TABLE_PREFIX."users AS m on (r.ID_MEMBER = m.uid) ORDER BY r.ID_PICTURE DESC");
	$context['gallery_report_list'] = array();
	while($row = $db->fetch_array($dbresult))
	{

		$table->construct_cell('<a href="' . $mybb->settings['bburl'] . '/ezgallery.php?action=view&id=' . $row['ID_PICTURE'] . '">' . $lang->gallery_rep_viewpic .'</a>');
		$table->construct_cell($row['comment']);
		$table->construct_cell(my_date($mybb->settings['dateformat'], $row['date']) . '</td>');

		if ($row['username'] != '')
			$table->construct_cell('<a href="index.php?module=user/users&action=edit&uid=' . $row['ID_MEMBER'] . '">'  . $row['username'] . '</a>');
		else
			$table->construct_cell($lang->gallery_guest);

		$table->construct_cell('<a href="' . $mybb->settings['bburl'] . '/ezgallery.php?action=delete&id=' . $row['ID_PICTURE'] . '">' . $lang->gallery_rep_deletepic  . '</a>
				<br /><a href="index.php?module=config/ezgallery&action=deletereport&id=' . $row['ID'] . '">' . $lang->gallery_rep_delete . '</a>');


		$table->construct_row();
	}

	$table->output($lang->gallery_form_reportimages);

	$page->output_footer();

}

function DeleteReport()
{
	global $db, $lang;

	$id = intval($_REQUEST['id']);
	if (empty($id))
		fatal_error($lang->gallery_error_no_report_selected);

	$db->query("DELETE FROM ".TABLE_PREFIX."gallery_report WHERE ID = $id LIMIT 1");

	// Redirect to redirect list
	admin_redirect('index.php?module=config/ezgallery&action=reportlist');
}

function DoGalleryAdminTabs($selectedTab = 'ezgallery_settings')
{
	global $page, $tabs;

	$page->output_nav_tabs($tabs, $selectedTab);
}

function GalleryPermissions()
{
	global $page, $db, $lang, $mybb;


	$page->output_header($lang->gallery_text_title . ' - ' . $lang->gallery_text_permissions);


	DoGalleryAdminTabs('ezgallery_permissions');

	echo '
<form method="post" name="frmpermissions" action="index.php?module=config/ezgallery&action=permissions2">
';

	$table = new Table;

	$table->construct_header($lang->gallery_membergroup);
	$table->construct_header($lang->permissionname_ezgallery_view);
	$table->construct_header($lang->permissionname_ezgallery_add);
	$table->construct_header($lang->permissionname_ezgallery_edit);
	$table->construct_header($lang->permissionname_ezgallery_delete);
	$table->construct_header($lang->permissionname_ezgallery_comment);
	$table->construct_header($lang->permissionname_ezgallery_report);
	$table->construct_header($lang->permissionname_ezgallery_autoapprove);
	$table->construct_header($lang->permissionname_ezgallery_manage);


	$dbresult = $db->query("
		  	SELECT
		  		u.title, p.ID_GROUP, u.gid,
		  		p.view,p.add,p.edit,p.delete,p.comment,p.report,p.autoapprove, p.manage
		  	FROM ". TABLE_PREFIX."usergroups AS u
		  	LEFT JOIN ". TABLE_PREFIX."gallery_permissions as p ON (p.ID_GROUP = u.gid)
		  	");

	while($row = $db->fetch_array($dbresult))
	{
		$table->construct_cell($row['title']);
		$table->construct_cell('<input type="checkbox" name="view_' . $row['gid'] . '" ' . ($row['view'] ? ' checked="checked"' : '') . ' />');
		$table->construct_cell('<input type="checkbox" name="add_' . $row['gid'] . '" ' . ($row['add'] ? ' checked="checked"' : '') . ' />');
		$table->construct_cell('<input type="checkbox" name="edit_' . $row['gid'] . '" ' . ($row['edit'] ? ' checked="checked"' : '') . ' />');
		$table->construct_cell('<input type="checkbox" name="delete_' . $row['gid'] . '" ' . ($row['delete'] ? ' checked="checked"' : '') . ' />');
		$table->construct_cell('<input type="checkbox" name="comment_' . $row['gid'] . '" ' . ($row['comment'] ? ' checked="checked"' : '') . ' />');
		$table->construct_cell('<input type="checkbox" name="report_' . $row['gid'] . '" ' . ($row['report'] ? ' checked="checked"' : '') . ' />');
		$table->construct_cell('<input type="checkbox" name="autoapprove_' . $row['gid'] . '" ' . ($row['autoapprove'] ? ' checked="checked"' : '') . ' />');
		$table->construct_cell('<input type="checkbox" name="manage_' . $row['gid'] . '" ' . ($row['manage'] ? ' checked="checked"' : '') . ' />');
		$table->construct_row();

	}

	$table->construct_cell('<input type="submit" value="' .$lang->gallery_update_permissions . '" />',array("colspan"=>9));
	$table->construct_row();

	$table->output($lang->gallery_text_permissions);

	echo '
    <input type="hidden" name="my_post_key" value="' . $mybb->post_code . '" />
    </form>';


	$page->output_footer();


}

function GalleryPermissions2()
{
	global  $db, $mybb;
    
    
    // Verify incoming POST request
    verify_post_check($mybb->get_input('my_post_key'));

	$dbresult = $db->query("
		  	SELECT
		  		u.gid

		  	FROM ". TABLE_PREFIX."usergroups AS u


		  	");

	while($row = $db->fetch_array($dbresult))
	{
		$view = isset($_REQUEST['view_' . $row['gid']]) ? 1 : 0;
		$add = isset($_REQUEST['add_' . $row['gid'] ]) ? 1 : 0;
		$edit = isset($_REQUEST['edit_' . $row['gid']]) ? 1 : 0;
		$delete = isset($_REQUEST['delete_' . $row['gid']]) ? 1 : 0;
		$comment = isset($_REQUEST['comment_' . $row['gid']]) ? 1 : 0;
		$report = isset($_REQUEST['report_' . $row['gid']]) ? 1 : 0;
		$autoapprove = isset($_REQUEST['autoapprove_' . $row['gid']]) ? 1 : 0;
		$manage = isset($_REQUEST['manage_' . $row['gid']]) ? 1 : 0;


		$dbresult2 = $db->query("
		  	SELECT
		  		COUNT(*) as total
		  	FROM ".TABLE_PREFIX."gallery_permissions as p
		WHERE p.ID_GROUP = ".  $row['gid']);
		$row2 = $db->fetch_array($dbresult2);

		if ($row2['total'] != 0)
		{
			// Update Member Group Permissions
			$db->query("
		  	UPDATE " . TABLE_PREFIX."gallery_permissions as p
		    SET
		  		p.view = $view ,p.add =$add,p.edit = $edit,p.delete =$delete ,p.comment = $comment,
		  		p.report = $report ,p.autoapprove = $autoapprove, p.manage = $manage
		  	WHERE p.ID_GROUP = ".  $row['gid']);
		}
		else
		{
			$db->query("
		  	INSERT IGNORE INTO " . TABLE_PREFIX."gallery_permissions
		  	(`ID_GROUP`,`view`,`add`,`edit`,`delete`,`comment`,`report`,`autoapprove`, `manage`)
		  	VALUES (" . $row['gid'] . ",$view,$add,$edit,$delete,$comment,$report,$autoapprove, $manage)
		  	"
		  );


		}


	}




	admin_redirect('index.php?module=config/ezgallery&action=permissions');
}

function fatal_error($errorMsg)
{
	global $page, $lang;

	$page->output_header($errorMsg);
	$page->output_inline_error($errorMsg);

	// Go Back link
	echo '<br /><a href="javascript:history.go(-1)">' . $lang->gallery_txt_goback . '</a>';

	$page->output_footer();
	exit;
}

function ezgallery_whosonline(&$plugin_array)
{
	global $lang;

	ezgallery_loadlanguage();

	if (preg_match('/ezgallery\.php/',$plugin_array['user_activity']['location']))
	{
		$plugin_array['location_name'] = "Viewing <a href=\"ezgallery.php\">" . $lang->gallery_whoonline . "</a>";
	}

	return $plugin_array;
}

function ezgallery_loadmainlanguage()
{
	global $lang;
	$lang->load("ezgallery");

}

?>