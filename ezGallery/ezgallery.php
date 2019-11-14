<?php
/*
ezGallery Lite
by: vbgamer45
http://www.mybbhacks.com
Copyright 2011-2016  MyBBHacks.com

############################################
License Information:

Links to http://www.mybbhacks.com must remain unless
branding free option is purchased.
#############################################
*/
define("IN_MYBB", 1);
define('THIS_SCRIPT', 'ezgallery.php');

require_once "./global.php";
require_once MYBB_ROOT."inc/ezgallery.lib.php";
require_once MYBB_ROOT."inc/class_parser.php";
$parser = new postParser;
$parser_options = array(
			"allow_html" => 0,
			"allow_mycode" => 1,
			"allow_smilies" => 1,
			"allow_imgcode" => 1,
			"filter_badwords" => 1
		);

$lang->load("ezgallery");

add_breadcrumb($lang->gallery_text_title, "ezgallery.php");

// Load Gallery Settings
LoadGallerySettings();
// Load Gallery Permissions
GalleryLoadPermissions();

	// Gallery Actions
	$subActions = array(
		'view' => 'ViewPicture',
		'delete' => 'DeletePicture',
		'delete2' => 'DeletePicture2',
		'edit' => 'EditPicture',
		'edit2' => 'EditPicture2',
		'report' => 'ReportPicture',
		'report2' => 'ReportPicture2',
		'comment' => 'AddComment',
		'comment2' => 'AddComment2',
		'viewc' => 'ViewC',
		'myimages' => 'MyImages',
		'add' => 'AddPicture',
		'add2' => 'AddPicture2',
		'search' => 'Search',
		'search2' => 'Search2',
	);
	
	@$sa = $mybb->input['action'];
	if (!empty($subActions[$sa]))
		$subActions[$sa]();
	else
		mainview();
	

function mainview()
{
	global $context, $lang, $db, $gallerySettings, $mybb;
	global $parser_options, $parser, $config;
	// View the main gallery

	// Is the user allowed to view the gallery?
	isAllowedTo('view');
	
	$context['gallery_cat_name'] = ' ';
	
	$g_add = allowedTo('add');
	
	// MyImages
	if ($g_add && $mybb->user['uid'] != 0)	
	$context['gallery']['buttons']['mylisting'] =  array(
		'text' => $lang->gallery_myimages,
		'url' =>'ezgallery.php?action=myimages&u=' . $mybb->user['uid'],
		'lang' => true,
		'image' => '',

	);

	// Search
	$context['gallery']['buttons']['search'] =  array(
		'text' => $lang->gallery_search,
		'url' => 'ezgallery.php?action=search',
		'lang' => true,
		'image' => '',

	);
	
	

	@$cat = intval($_REQUEST['cat']);
	if ($cat)
	{
		// Get category name
		$dbresult1 = $db->query("
		SELECT 
			ID_CAT, title, roworder, description, image 
		FROM ".TABLE_PREFIX."gallery_cat 
		WHERE ID_CAT = $cat LIMIT 1");
		$row1 = $db->fetch_array($dbresult1);
		$context['gallery_cat_name'] = $row1['title'];
		
		add_breadcrumb(htmlspecialchars_uni($row1['title']), 'ezgallery.php?cat=' . $cat);

		$query = $db->query("
		SELECT COUNT(*) as total
		 FROM ".TABLE_PREFIX."gallery_pic as p

		WHERE p.ID_CAT = $cat AND p.approved = 1 ");
		$totalPicCount = $db->fetch_array($query);
			
		$perpage = $gallerySettings['gallery_set_images_per_page'];
		$page = intval($mybb->input['page']);
				
		if(intval($mybb->input['page']) > 0)
		{
			$start = ($page-1) * $perpage;
		}
		else
		{
			$start = 0;
			$page = 1;
		}
		$pagingList = multipage($totalPicCount['total'], $perpage, $page, "ezgallery.php?cat=$cat");
				
				
		// Image Listing
		$dbresult = $db->query("
		SELECT p.ID_PICTURE, p.commenttotal, p.filesize, p.views, p.thumbfilename, p.filename, p.height, p.width, 
		 p.title, p.ID_MEMBER, m.username, p.date, p.description 
		 FROM ".TABLE_PREFIX."gallery_pic as p
		LEFT JOIN ".TABLE_PREFIX."users AS m on ( p.ID_MEMBER = m.uid) 
		WHERE p.ID_CAT = $cat AND p.approved = 1 ORDER BY p.ID_PICTURE DESC LIMIT $start,$perpage");
		$context['gallery_image_list'] = array();
		while($row = $db->fetch_array($dbresult))
		{
			$context['gallery_image_list'][] = $row;
		}
	
		// Link Tree
		$context['linktree'][] = array(
					'url' => 'ezgallery.php',
					'name' => $lang->gallery_text_title
				);
		$context['linktree'][] = array(
					'url' =>  'ezgallery.php?cat=' . $cat,
					'name' => $context['gallery_cat_name']
				);	


		$context['page_title'] = $context['gallery_cat_name'];
		
		gallery_header();


		if (!empty($gallerySettings['gallery_who_viewing']))
		{

				// Start out with no one at all viewing it.
				$context['view_members'] = array();
				$context['view_members_list'] = array();
				$context['view_num_hidden'] = 0;
				
				$timesearch = TIME_NOW - $mybb->settings['wolcutoffmins']*60;
				// Search for members who have this picture id set in their GET data.
				$request = $db->query("
					SELECT
						lo.uid, lo.time, mem.username, mem.invisible,
						mg.namestyle, mg.gid, mg.title
					FROM (".TABLE_PREFIX."sessions AS lo)
						LEFT JOIN ".TABLE_PREFIX."users AS mem ON (mem.uid = lo.uid)
						LEFT JOIN ".TABLE_PREFIX."usergroups AS mg ON (mem.usergroup = mg.gid)
					WHERE INSTR(lo.location, 'ezgallery.php?cat=$cat') AND lo.time > {$timesearch}");
				while ($row = $db->fetch_array($request))
				{
					if (empty($row['uid']))
						continue;

					if (!empty($row['namestyle']))
						$link = '<a href="member.php?action=profile&uid=' . $row['uid'] . '">' . str_replace("{username}",$row['username'],$row['namestyle']) . '</a>';
					else
						$link = '<a href="member.php?action=profile&uid=' . $row['uid'] . '">' . $row['username'] . '</a>';

					
					// Add them both to the list and to the more detailed list.
					if (empty($row['invisible']))
						$context['view_members_list'][$row['time'] . $row['username']] = !empty($row['invisible']) ? '<i>' . $link . '</i>' : $link;
					$context['view_members'][$row['time'] . $row['username']] = array(
						'id' => $row['uid'],
						'username' => $row['username'],
						'name' => $row['username'],
						'group' => $row['gid'],
						'href' => 'member.php?action=profile&uid=' . $row['uid'],
						'link' => $link,
						'hidden' => !empty($row['invisible']),
					);

					if (!empty($row['invisible']))
						$context['view_num_hidden']++;
				}

				// The number of guests is equal to the rows minus the ones we actually used ;).
				$context['view_num_guests'] = $db->num_rows($request) - count($context['view_members']);
				

				// Sort the list.
				krsort($context['view_members']);
				krsort($context['view_members_list']);
			
	
		}
		
		
		
	// Permissions if they are allowed to edit or delete their own gallery pictures.
	$g_edit_own = allowedTo('edit');
	$g_delete_own = allowedTo('delete');
	
	$g_add = allowedTo('add');
	$g_manage = allowedTo('manage');

	
	// Check if GD is installed if not we will not show the thumbnails
	$GD_Installed = function_exists('imagecreate');
	
	if ($g_manage)
	{

		// Warn the user if they are managing the gallery that it is not writable
		if (!is_writable($gallerySettings['gallery_path']))
			echo '<font color="#FF0000"><b>', $lang->gallery_write_error, $gallerySettings['gallery_path'] . '</b></font>';
	}
	
	
	// Get the Category
	@$cat = intval($_REQUEST['cat']);

	echo '<table border="0" cellspacing="0" cellpadding="4" align="center" width="90%" class="tborder" >
					<tr class="tcat">
						<td align="center">&nbsp;</td>
					</tr>
					</table>
				<table border="0" cellpadding="0" cellspacing="0" align="center" width="90%">
						<tr>
							<td style="padding-right: 1ex;" align="right" >
						<table cellpadding="0" cellspacing="0" align="right">
									<tr>
						', DoToolBarStrip($context['gallery']['buttons'], 'top'), '
							</tr>
							</table>
						</td>
						</tr>
					</table>
				<br />';
	
	
		$maxrowlevel = $gallerySettings['gallery_set_images_per_row'];
		echo '<br />
		<table cellspacing="0" cellpadding="10" border="0" align="center" width="90%" class="tborder">
			<tr class="tcat">
				<td align="center" colspan="' . $maxrowlevel . '">' . @$context['gallery_cat_name'] . '</td>
			</tr>';

		// Show the pictures
		$rowlevel = 0;
		$styleclass = 'trow1';
		
		
		$image_count = count($context['gallery_image_list']);
		
		if ($image_count == 0)
		{
			echo '
			<tr class="' . $styleclass . '">
				<td colspan="' . $maxrowlevel . '" align="center"><b>',$lang->gallery_nopicsincategory,'</b></td>
			</tr>
			
			';
		}
		
		
		foreach($context['gallery_image_list'] as $row)
		{
			if ($rowlevel == 0)
				echo '<tr class="' . $styleclass . '">';

			echo '<td align="center"><a href="ezgallery.php?action=view&id=' . $row['ID_PICTURE'] . '">
			<img ' . ($GD_Installed == true ?  'src="' . $gallerySettings['gallery_url'] . $row['thumbfilename'] . '" ' : 'src="' . $gallerySettings['gallery_url'] . $row['filename'] . '" ')  . ' border="0" alt="" /></a><br />';
			echo '<span class="smalltext">' . $lang->gallery_text_views . $row['views'] . '<br />';
			echo $lang->gallery_text_filesize . gallery_format_size($row['filesize'], 2) . '<br />';
			echo $lang->gallery_text_date . my_date($mybb->settings['dateformat'], $row['date']) . '<br />';
			echo $lang->gallery_text_comments. ' (<a href="ezgallery.php?action=view&id=' . $row['ID_PICTURE'] . '">' . $row['commenttotal'] . '</a>)<br />';
			
			if ($row['username'] != '')
				echo $lang->gallery_text_by . ' <a href="member.php?action=profile&uid=' . $row['ID_MEMBER'] . '">'  . $row['username'] . '</a><br />';
			else 
				echo $lang->gallery_text_by, $lang->gallery_guest,  '<br />';

				
			if ($g_manage)
				echo '&nbsp;<a href="' . $mybb->settings['bburl'] . '/' . $config['admin_dir'] . '/index.php?module=config/ezgallery&action=unapprove&id=' . $row['ID_PICTURE'] . '">' . $lang->gallery_text_unapprove . '</a>';
			if ($g_manage || $g_edit_own && $row['ID_MEMBER'] == $mybb->user['uid'])
				echo '&nbsp;<a href="ezgallery.php?action=edit&id=' . $row['ID_PICTURE'] . '">' . $lang->gallery_text_edit . '</a>';
			if ($g_manage || $g_delete_own && $row['ID_MEMBER'] == $mybb->user['uid'])
				echo '&nbsp;<a href="ezgallery.php?action=delete&id=' . $row['ID_PICTURE'] . '">' . $lang->gallery_text_delete . '</a>';

			echo '</span></td>';


			if($rowlevel < ($maxrowlevel-1))
				$rowlevel++;
			else
			{
				echo '</tr>';
				$rowlevel = 0;
			}
			
			if ($styleclass == 'trow1')
				$styleclass = 'trow2';
			else 
				$styleclass = 'trow1';

		}
		
		
		if ($rowlevel !=0)
		{
			echo '<td colspan="' . ($maxrowlevel - $rowlevel) . '"></td>';
			echo '</tr>';
		}


		// Display who is viewing the picture.
		if (!empty($gallerySettings['gallery_who_viewing']))
		{
			echo '<tr class="' . $styleclass . '">
			<td align="center" colspan="' . $maxrowlevel . '"><span class="smalltext">';

			// Show just numbers...?
			// show the actual people viewing the gallery?
			echo empty($context['view_members_list']) ? '0 ' . $lang->gallery_who_members : implode(', ', $context['view_members_list']) . (empty($context['view_num_hidden']) || @$context['can_moderate_forum'] ? '' : ' (+ ' . $context['view_num_hidden'] . ' ' . $lang->gallery_who_hidden . ')');

			// Now show how many guests are here too.
			echo ' ' . $lang->who_and, @$context['view_num_guests'], ' ', @$context['view_num_guests'] == 1 ? $lang->guest : $lang->guests, $lang->gallery_who_viewgallery, '</span></td></tr>';
		}

		// Show return to gallery link and Show add picture if they can
		
		echo '<tr class="' . $styleclass . '"><td colspan="' . $maxrowlevel . '">'
		 . $pagingList .
		 '</td></tr>';
		
		
		echo '
				<tr class="tcat"><td align="center" colspan="' . $maxrowlevel . '">';
				if($g_add)
				echo '<a href="ezgallery.php?action=add&cat=' . $cat . '">' . $lang->gallery_text_addpicture .'</a><br />';

				echo '
				<a href="ezgallery.php">' . $lang->gallery_text_returngallery . '</a></td>
			</tr>';


		echo '</table><br />';
	

		
	}
	else
	{
		
		$context['page_title'] = $lang->gallery_text_title;
		
		gallery_header();
		
		$dbresult = $db->query("
		SELECT 
			ID_CAT, title, roworder, description, image 
		FROM ".TABLE_PREFIX."gallery_cat ORDER BY roworder ASC");
		$context['gallery_cat_list'] = array();
		while($row = $db->fetch_array($dbresult))
		{
			$context['gallery_cat_list'][] = $row;
		}
		
		
		// Get unapproved pictures
		$dbresult3 = $db->query("
			SELECT 
				COUNT(*) AS total 
			FROM ".TABLE_PREFIX."gallery_pic 
			WHERE approved = 0");
			$totalrow = $db->fetch_array($dbresult3);
			$totalpics = $totalrow['total'];

		$context['total_unapproved'] = $totalpics;
		
		// Total reported images
		$dbresult4 = $db->query("
			SELECT 
				COUNT(*) AS total 
			FROM ".TABLE_PREFIX."gallery_report");
			$totalrow = $db->fetch_array($dbresult4);
			$totalreport = $totalrow['total'];

		$context['total_reported_images'] = $totalreport;
		
	
	
	// Permissions
	$g_manage = allowedTo('manage');

	if ($g_manage)
	{

		// Warn the user if they are managing the gallery that it is not writable
		if (!is_writable($gallerySettings['gallery_path']))
			echo '<font color="#FF0000"><b>', $lang->gallery_write_error, $gallerySettings['gallery_path'] . '</b></font>';
	}

	echo '<table border="0" cellspacing="0" cellpadding="4" align="center" width="90%" class="tborder" >
					<tr class="tcat">
						<td align="center">', $lang->gallery_text_title, '</td>
					</tr>
					</table>
				<table border="0" cellpadding="0" cellspacing="0" align="center" width="90%">
						<tr>
							<td style="padding-right: 1ex;" align="right" >
						<table cellpadding="0" cellspacing="0" align="right">
									<tr>
						', DoToolBarStrip($context['gallery']['buttons'], 'top'), '
							</tr>
							</table>
						</td>
						</tr>
					</table>
				<br />';

		// List all the catagories
	
		echo '<table cellspacing="0" cellpadding="10" border="0" align="center" width="90%" class="tborder">
				<tr class="tcat">
				<td colspan="2">', $lang->gallery_text_galleryname, '</td>
				<td>', $lang->gallery_text_gallerydescription, '</td>
				<td align="center">', $lang->gallery_text_totalimages, '</td>
				';
		if ($g_manage)
		echo '
				<td>', $lang->gallery_text_reorder, '</td>
				<td>', $lang->gallery_text_options, '</td>
				';
		
		echo '</tr>';


		foreach($context['gallery_cat_list'] as $row)
		{
		  	
			$totalpics = GetTotalPicturesBYCATID($row['ID_CAT']);
			
			echo '<tr class="trow2">';
		
			if ($row['image'] == '')
				echo '<td colspan="2"><a href="ezgallery.php?cat=' . $row['ID_CAT'] . '">' . $parser->parse_message($row['title'], $parser_options) . '</a></td><td>' . $parser->parse_message($row['description'], $parser_options) . '</td>';
			else
			{
				echo '<td><a href="ezgallery.php?cat=' . $row['ID_CAT'] . '"><img src="' . $row['image'] . '" border="0" alt="" /></a></td>';
				echo '<td><a href="ezgallery.php?cat=' . $row['ID_CAT'] . '">' . $parser->parse_message($row['title'], $parser_options) . '</a></td><td>' . $parser->parse_message($row['description'], $parser_options) . '</td>';
			}

			// Show total pictures in the category
			echo '<td align="center">', $totalpics, '</td>';

			// Show Edit Delete and Order category
			if ($g_manage)
			{
				echo '<td><a href="' . $mybb->settings['bburl'] . '/' . $config['admin_dir'] . '/index.php?module=config/ezgallery&action=catup&cat=' . $row['ID_CAT'] . '">' . $lang->gallery_text_up . '</a>&nbsp;<a href="' . $mybb->settings['bburl'] . '/' . $config['admin_dir'] . '/index.php?module=config/ezgallery&action=catdown&cat=' . $row['ID_CAT'] . '">' . $lang->gallery_text_down . '</a></td>
				<td><a href="' . $mybb->settings['bburl'] . '/' . $config['admin_dir'] . '/index.php?module=config/ezgallery&action=editcat&cat=' . $row['ID_CAT'] . '">' . $lang->gallery_text_edit . '</a>&nbsp;<a href="' . $mybb->settings['bburl'] . '/' . $config['admin_dir'] . '/index.php?module=config/ezgallery&action=deletecat&cat=' . $row['ID_CAT'] . '">' . $lang->gallery_text_delete . '</a></td>';
			}


			echo '</tr>';

		}
		echo '</table><br /><br /><br />';

		// See if they are allowed to add catagories Main Index only
		if ($g_manage)
		{
			echo '<table cellspacing="0" cellpadding="5" border="0" align="center" width="90%" class="tborder">
				<tr class="tcat">
					<td align="center">', $lang->gallery_text_adminpanel, '</td>
				</tr>
				<tr class="trow2">
			<td align="center"><a href="' . $mybb->settings['bburl'] . '/' . $config['admin_dir'] . '/index.php?module=config/ezgallery&action=addcat">' . $lang->gallery_text_addcategory . '</a>&nbsp
			<a href="' . $mybb->settings['bburl'] . '/' . $config['admin_dir'] . '/index.php?module=config/ezgallery&action=adminset">' . $lang->gallery_text_settings . '</a>&nbsp';


			if (allowedTo('manage'))
				echo '<a href="' . $mybb->settings['bburl'] . '/' . $config['admin_dir'] . '/index.php?module=config/ezgallery&action=permissions">' . $lang->gallery_text_permissions . '</a>';

			
			
			echo '<br />' . $lang->gallery_text_imgwaitapproval . '<b>' . $context['total_unapproved'] . '</b>&nbsp;&nbsp;<a href="' . $mybb->settings['bburl'] . '/' . $config['admin_dir'] . '/index.php?module=config/ezgallery&action=approvelist">' . $lang->gallery_text_imgcheckapproval . '</a>';

			echo '<br />' . $lang->gallery_text_imgreported . '<b>' . $context['total_reported_images'] . '</b>&nbsp;&nbsp;<a href="' . $mybb->settings['bburl'] . '/' . $config['admin_dir'] . '/index.php?module=config/ezgallery&action=reportlist">' . $lang->gallery_text_imgcheckreported . '</a>';

			echo '</td></tr></table><br /><br />';
		}


		GalleryCopyright();
	
	}

	gallery_footer();
	
}

function AddPicture()
{
	global $context, $lang, $gallerySettings, $db, $mybb;

	isAllowedTo('add');
	
	GalleryTopButtons();

	$context['page_title'] = $lang->gallery_text_title . ' - ' . $lang->gallery_form_addpicture;
	gallery_header();
	
	 $dbresult = $db->query("
 	SELECT 
 		ID_CAT, title 
 	FROM ".TABLE_PREFIX."gallery_cat ORDER BY roworder ASC");
	$context['gallery_cat_list'] = array();
	while($row = $db->fetch_array($dbresult))
	{
		$context['gallery_cat_list'][] = $row;
	}


	echo '<table border="0" cellspacing="0" cellpadding="4" align="center" width="90%" class="tborder" >
					<tr class="tcat">
						<td align="center">&nbsp;</td>
					</tr>
					</table>
				<table border="0" cellpadding="0" cellspacing="0" align="center" width="90%">
						<tr>
							<td style="padding-right: 1ex;" align="right" >
						<table cellpadding="0" cellspacing="0" align="right">
									<tr>
						', DoToolBarStrip($context['gallery']['buttons'], 'top'), '
							</tr>
							</table>
						</td>
						</tr>
					</table>
				<br />';
	
	// Get the category
	@$cat = intval($_REQUEST['cat']);

	echo '<form method="post" enctype="multipart/form-data" name="picform" id="picform" action="ezgallery.php?action=add2">
<table border="0" cellpadding="0" cellspacing="0" width="100%">
  <tr class="tcat">
    <td width="50%" colspan="2" align="center">
    <b>' . $lang->gallery_form_addpicture. '</b>
    </td>
  </tr>
  <tr class="trow2">
  	<td align="right"><b>' . $lang->gallery_form_title . '</b>&nbsp;</td>
  	<td><input type="text" name="title" size="50" /></td>
  </tr>
  <tr class="trow2">
  	<td align="right"><b>' . $lang->gallery_form_category . '</b>&nbsp;</td>
  	<td><select name="cat">';

 	foreach($context['gallery_cat_list'] as $row)
	{
		echo '<option value="' . $row['ID_CAT']  . '" ' . (($cat == $row['ID_CAT']) ? ' selected="selected"' : '') .'>' . $row['title'] . '</option>';
	}


 echo '</select>
  	</td>
  </tr>
  <tr class="trow2">
  	<td align="right"><b>' . $lang->gallery_form_description . '</b>&nbsp;</td>
  	<td><textarea name="description" rows="5" cols="50"></textarea>
  	</td>
  </tr>
  <tr class="trow2">
  	<td align="right"><b>' . $lang->gallery_form_keywords . '</b>&nbsp;</td>
  	<td><input type="text" name="keywords" maxlength="100" size="100" /></td>
  </tr>
  <tr class="trow2">
  	<td align="right" valign="top"><b>' . $lang->gallery_form_uploadpic . '</b>&nbsp;</td>

    <td><input type="file" size="48" name="picture" />';

  if(!empty($gallerySettings['gallery_max_width']))
 	echo '<br />' . $lang->gallery_form_maxwidth .  $gallerySettings['gallery_max_width'] . $lang->gallery_form_pixels;
  if(!empty($gallerySettings['gallery_max_height']))
  	echo '<br />' . $lang->gallery_form_maxheight .  $gallerySettings['gallery_max_height'] . $lang->gallery_form_pixels;

 echo '
    </td>
  </tr>';

  if(!empty($gallerySettings['gallery_commentchoice']))
  {
	echo '
	   <tr class="trow2">
		<td align="right"><b>' . $lang->gallery_form_additionaloptions . '</b>&nbsp;</td>
		<td><input type="checkbox" name="allowcomments" checked="checked" /><b>' . $lang->gallery_form_allowcomments .'</b></td>
	  </tr>';
  }

echo '
  <tr class="trow2">
    <td width="28%" colspan="2"  align="center" class="trow2">
    <input type="hidden" name="my_post_key" value="' . $mybb->post_code . '" />
    <input type="submit" value="' . $lang->gallery_form_addpicture . '" name="submit" /><br />';

  	if(!allowedTo('autoapprove'))
  		echo $lang->gallery_form_notapproved;

echo '
    </td>
  </tr>
</table>

		</form>
';

gallery_footer();
	
}

function AddPicture2()
{
	global $lang, $mybb, $db, $gallerySettings, $plugins, $gd2;

	isAllowedTo('add');

	// Check if gallery path is writable
	if (!is_writable($gallerySettings['gallery_path']))
		fatal_error2($lang->gallery_write_error . $gallerySettings['gallery_path']);


    // Verify incoming POST request
    verify_post_check($mybb->get_input('my_post_key'));

	$title = htmlspecialchars_uni($db->escape_string($_REQUEST['title']));
	$description = htmlspecialchars_uni($db->escape_string($_REQUEST['description']));
	$keywords = htmlspecialchars_uni($db->escape_string($_REQUEST['keywords']));
	$cat = intval($_REQUEST['cat']);
	
	
	
	@$allowcomments = $_REQUEST['allowcomments'];

	// Check if pictures are auto approved
	$approved = (allowedTo('autoapprove') ? 1 : 0);

	// Allow comments on picture if no setting set.
	if(empty($gallerySettings['gallery_commentchoice']) || $gallerySettings['gallery_commentchoice'] == 0)
		$allowcomments = 1;
	else
	{
		if(empty($allowcomments))
			$allowcomments = 0;
		else
			$allowcomments = 1;
	}

	if (trim($title) == '')
		fatal_error2($lang->gallery_error_no_title,false);
	if (empty($cat))
		fatal_error2($lang->gallery_error_no_cat,false);
		
	CheckGalleryCategoryExists($cat);

	$testGD = get_extension_funcs('gd');
	$gd2 = in_array('imagecreatetruecolor', $testGD) && function_exists('imagecreatetruecolor');
	unset($testGD);
	require_once MYBB_ROOT."inc/functions_image.php";

	// Process Uploaded file
	if (isset($_FILES['picture']['name']) && $_FILES['picture']['name'] != '')
	{

		$sizes = getimagesize($_FILES['picture']['tmp_name']);
		$failed = false;
		if ($sizes === false)
		{
			@unlink($gallerySettings['gallery_path'] . '/img.tmp');
			move_uploaded_file($_FILES['picture']['tmp_name'], $gallerySettings['gallery_path'] . '/img.tmp');
		
			$_FILES['picture']['tmp_name'] = $gallerySettings['gallery_path'] . '/img.tmp';
			$sizes = getimagesize($_FILES['picture']['tmp_name']);
			$failed =true;
		}

			// No size, then it's probably not a valid pic.
			if ($sizes === false)
				fatal_error2($lang->gallery_error_invalid_picture,false);
			elseif ((!empty($gallerySettings['gallery_max_width']) && $sizes[0] > $gallerySettings['gallery_max_width']) || (!empty($gallerySettings['gallery_max_height']) && $sizes[1] > $gallerySettings['gallery_max_height']))
			{
				//Delete the temp file
				@unlink($_FILES['picture']['tmp_name']);
				fatal_error2($lang->gallery_error_img_size_height . $sizes[1] . $lang->gallery_error_img_size_width . $sizes[0],false);
			}
			else
			{
				//Get the filesize
				$filesize = $_FILES['picture']['size'];

				if(!empty($gallerySettings['gallery_max_filesize']) && $filesize > $gallerySettings['gallery_max_filesize'])
				{
					//Delete the temp file
					@unlink($_FILES['picture']['tmp_name']);
					fatal_error2($lang->gallery_error_img_filesize . gallery_format_size($gallerySettings['gallery_max_filesize'], 2),false);
				}

				//Filename Member Id + Day + Month + Year + 24 hour, Minute Seconds
				$extensions = array(
					1 => 'gif',
					2 => 'jpeg',
					3 => 'png',
					5 => 'psd',
					6 => 'bmp',
					7 => 'tiff',
					8 => 'tiff',
					9 => 'jpeg',
					14 => 'iff',
					);
				$extension = isset($extensions[$sizes[2]]) ? $extensions[$sizes[2]] : '.bmp';
			
				
				$filename = $mybb->user['uid']. '_' . date('d_m_y_g_i_s') . '.' . $extension;

				if ($failed == false)
					move_uploaded_file($_FILES['picture']['tmp_name'], $gallerySettings['gallery_path'] . $filename);
				else 
					rename($_FILES['picture']['tmp_name'], $gallerySettings['gallery_path'] . $filename);

				@chmod($gallerySettings['gallery_path'] . $filename, 0644);
				//Create thumbnail
				require_once MYBB_ROOT."inc/functions_image.php";
				$tmp = generate_thumbnail($gallerySettings['gallery_path'] . $filename, substr($gallerySettings['gallery_path'], 0, -1),'thumb_' . $filename,  $gallerySettings['gallery_set_thumb_height'], $gallerySettings['gallery_set_thumb_width']);
				$thumbname = 'thumb_' . $filename;
					
				if ($tmp['code']== '4')
				{
					copy($gallerySettings['gallery_path'] . $filename,$gallerySettings['gallery_path'] . $thumbname);
					
					@chmod($gallerySettings['gallery_path'] . $thumbname, 0644);

				}
				// Create the Database entry
				$t = time();
				$db->query("INSERT INTO ".TABLE_PREFIX."gallery_pic
							(ID_CAT, filesize,thumbfilename,filename, height, width, keywords, title, description,ID_MEMBER,date,approved,allowcomments)
						VALUES ($cat, $filesize,'$thumbname', '$filename', $sizes[1], $sizes[0], '$keywords','$title', '$description'," . $mybb->user['uid'] . ",$t,$approved, $allowcomments)");

	
				$plugins->run_hooks("gallery_add_picture_completed");
				
				
				// Redirect to the users image page.
				if ($mybb->user['uid']!= 0)
					redirect('ezgallery.php?action=myimages&u=' . $mybb->user['uid']);
				else 
					redirect('ezgallery.php?ezgallery.php?cat=' . $cat);
			}

	}
	else
		fatal_error2($lang->gallery_error_no_picture);

}

function EditPicture()
{
	global $context, $lang, $db, $gallerySettings, $mybb;

	$id = intval($_REQUEST['id']);
	if (empty($id))
		fatal_error2($lang->gallery_error_no_pic_selected);
		
	GalleryTopButtons();

	// Check if the user owns the picture or is admin
    $dbresult = $db->query("
    SELECT p.ID_PICTURE, p.thumbfilename, p.width, p.height, p.allowcomments, p.ID_CAT, p.keywords, 
    p.commenttotal, p.filesize, p.filename, p.approved, p.views, p.title, p.ID_MEMBER, m.username, p.date, p.description 
    FROM ".TABLE_PREFIX."gallery_pic as p
    LEFT JOIN ".TABLE_PREFIX."users AS m ON (m.uid = p.ID_MEMBER) 
    WHERE ID_PICTURE = $id LIMIT 1");
	$row = $db->fetch_array($dbresult);

	//Gallery picture information
	$context['gallery_pic'] = array(
		'ID_PICTURE' => $row['ID_PICTURE'],
		'ID_MEMBER' => $row['ID_MEMBER'],
		'commenttotal' => $row['commenttotal'],
		'views' => $row['views'],
		'title' => $row['title'],
		'description' => $row['description'],
		'filesize' => $row['filesize'],
		'filename' => $row['filename'],
		'thumbfilename' => $row['thumbfilename'],
		'width' => $row['width'],
		'height' => $row['height'],
		'allowcomments' => $row['allowcomments'],
		'ID_CAT' => $row['ID_CAT'],
		'date' => my_date($mybb->settings['dateformat'], $row['date']),
		'keywords' => $row['keywords'],
		'username' => $row['username'],
	);

	
	
	 $dbresult = $db->query("
 	SELECT 
 		ID_CAT, title 
 	FROM ".TABLE_PREFIX."gallery_cat ORDER BY roworder ASC");
	$context['gallery_cat_list'] = array();
	while($row = $db->fetch_array($dbresult))
	{
		$context['gallery_cat_list'][] = $row;
	}


	if(allowedTo('manage') || (allowedTo('edit') && $mybb->user['uid']== $context['gallery_pic']['ID_MEMBER']))
	{
		$context['page_title'] = $lang->gallery_text_title . ' - ' . $lang->gallery_form_editpicture;
		gallery_header();
	}
	else
	{
		fatal_error2($lang->gallery_error_noedit_permission);
	}
	

	echo '<table border="0" cellspacing="0" cellpadding="4" align="center" width="90%" class="tborder" >
					<tr class="tcat">
						<td align="center">&nbsp;</td>
					</tr>
					</table>
				<table border="0" cellpadding="0" cellspacing="0" align="center" width="90%">
						<tr>
							<td style="padding-right: 1ex;" align="right" >
						<table cellpadding="0" cellspacing="0" align="right">
									<tr>
						', DoToolBarStrip($context['gallery']['buttons'], 'top'), '
							</tr>
							</table>
						</td>
						</tr>
					</table>
				<br />';
	


	echo '<form method="post" enctype="multipart/form-data" name="picform" id="picform" action="ezgallery.php?action=edit2" accept-charset="', $context['character_set'], '">
<table border="0" cellpadding="0" cellspacing="0" width="100%">
  <tr class="tcat">
    <td width="50%" colspan="2"  align="center">
    <b>' . $lang->gallery_form_editpicture . '</b></td>
  </tr>
  <tr class="trow2">
  	<td align="right"><b>' . $lang->gallery_form_title . '</b>&nbsp;</td>
  	<td><input type="text" name="title" size="100" value="' . $context['gallery_pic']['title'] . '" /></td>
  </tr>
  <tr class="trow2">
  	<td align="right"><b>' . $lang->gallery_form_category . '</b>&nbsp;</td>
  	<td><select name="cat">';

	foreach($context['gallery_cat_list'] as  $row)
	{
		echo '<option value="' . $row['ID_CAT']  . '" ' . (($context['gallery_pic']['ID_CAT'] == $row['ID_CAT']) ? ' selected="selected"' : '') .'>' . $row['title'] . '</option>';
	}

 echo '</select>
  	</td>
  </tr>
  <tr class="trow2">
  	<td align="right"><b>' . $lang->gallery_form_description . '</b>&nbsp;</td>
  	<td><textarea name="description" rows="5" cols="50">' . $context['gallery_pic']['description'] .'</textarea>
 
  	</td>
  </tr>
  <tr class="trow2">
  	<td align="right"><b>' . $lang->gallery_form_keywords . '</b>&nbsp;</td>
  	<td><input type="text" name="keywords" maxlength="100"  size="100" value="' . $context['gallery_pic']['keywords'] . '" /></td>
  </tr>
  <tr class="trow2">
  	<td align="right" valign="top"><b>' . $lang->gallery_form_uploadpic . '</b>&nbsp;</td>

    <td><input type="file" size="48" name="picture" />';

  if(!empty($gallerySettings['gallery_max_width']))
 	echo '<br />' . $lang->gallery_form_maxwidth .  $gallerySettings['gallery_max_width'] . $lang->gallery_form_pixels;
  if(!empty($gallerySettings['gallery_max_height']))
  	echo '<br />' . $lang->gallery_form_maxheight .  $gallerySettings['gallery_max_height'] . $lang->gallery_form_pixels;

 echo '
    </td>
  </tr>';

  if ($gallerySettings['gallery_commentchoice'])
  {
	echo '
	   <tr class="trow2">
		<td align="right"><b>' . $lang->gallery_form_additionaloptions . '</b>&nbsp;</td>
		<td><input type="checkbox" name="allowcomments" ' . ($context['gallery_pic']['allowcomments'] ? 'checked="checked"' : '' ) . ' /><b>',$lang->gallery_form_allowcomments,'</b></td>
	  </tr>';
  }

echo '
  <tr class="trow2">
    <td width="28%" colspan="2"  align="center" class="trow2">
	<input type="hidden" name="id" value="' . $context['gallery_pic']['ID_PICTURE'] . '" />
    <input type="hidden" name="my_post_key" value="' . $mybb->post_code . '" />
    <input type="submit" value="' . $lang->gallery_form_editpicture . '" name="submit" /><br />';

  	if (!allowedTo('autoapprove'))
  		echo $lang->gallery_form_notapproved;

echo '<div align="center"><br /><b>' . $lang->gallery_text_oldpicture . '</b><br />
<a href="ezgallery.php?action=view&id=' . $context['gallery_pic']['ID_PICTURE'] . '" target="blank"><img src="' . $gallerySettings['gallery_url'] . $context['gallery_pic']['thumbfilename']  . '" border="0" alt="" /></a><br />
			<span class="smalltext">' . $lang->gallery_text_views . $context['gallery_pic']['views'] . '<br />
			' . $lang->gallery_text_filesize  . gallery_format_size($context['gallery_pic']['filesize'],2) . '<br />
			' . $lang->gallery_text_date . $context['gallery_pic']['date'] . '<br />
	</div>
    </td>
  </tr>
</table>

		</form>
';
gallery_footer();

}

function EditPicture2()
{
	global $mybb, $lang, $db, $gallerySettings, $gd2, $plugins;


	$id = intval($_REQUEST['id']);
	if (empty($id))
		fatal_error2($lang->gallery_error_no_pic_selected);
        
        
    // Verify incoming POST request
    verify_post_check($mybb->get_input('my_post_key'));

	// Check the user permissions
    $dbresult = $db->query("
    SELECT 
    	ID_MEMBER,thumbfilename,filename 
    FROM ".TABLE_PREFIX."gallery_pic 
    WHERE ID_PICTURE = $id LIMIT 1");
	$row = $db->fetch_array($dbresult);
	$memID = $row['ID_MEMBER'];
	$oldfilename = $row['filename'];
	$oldthumbfilename  = $row['thumbfilename'];


	if (allowedTo('manage') || (allowedTo('edit') && $mybb->user['uid']== $memID))
	{

		if(!is_writable($gallerySettings['gallery_path']))
			fatal_error2($lang->gallery_write_error . $gallerySettings['gallery_path']);

		$title = htmlspecialchars_uni($db->escape_string($_REQUEST['title']));
		$description = htmlspecialchars_uni($db->escape_string($_REQUEST['description']));
		$keywords = htmlspecialchars_uni($db->escape_string($_REQUEST['keywords']));
		$cat = intval($_REQUEST['cat']);
		
		@$allowcomments = $_REQUEST['allowcomments'];

		//Check if pictures are auto approved
		$approved = (allowedTo('autoapprove') ? 1 : 0);

		//Allow comments on picture if no setting set.
		if (empty($gallerySettings['gallery_commentchoice']) || $gallerySettings['gallery_commentchoice'] == 0)
			$allowcomments = 1;
		else
		{
			if(empty($allowcomments))
				$allowcomments = 0;
			else
				$allowcomments = 1;
		}



		if (trim($title) == '')
			fatal_error2($lang->gallery_error_no_title,false);
		if (empty($cat))
			fatal_error2($lang->gallery_error_no_cat,false);
			
		CheckGalleryCategoryExists($cat);	


		$testGD = get_extension_funcs('gd');
		$gd2 = in_array('imagecreatetruecolor', $testGD) && function_exists('imagecreatetruecolor');
		unset($testGD);
		
		require_once MYBB_ROOT."inc/functions_image.php";
		
		
		//Process Uploaded file
		if (isset($_FILES['picture']['name']) && $_FILES['picture']['name'] != '')
		{

				$sizes = getimagesize($_FILES['picture']['tmp_name']);
				$failed = false;
				if ($sizes === false)
				{
					@unlink($gallerySettings['gallery_path'] . '/img.tmp');
					move_uploaded_file($_FILES['picture']['tmp_name'], $gallerySettings['gallery_path'] . '/img.tmp');
				
					$_FILES['picture']['tmp_name'] = $gallerySettings['gallery_path'] . '/img.tmp';
					$sizes = getimagesize($_FILES['picture']['tmp_name']);
					$failed =true;
				}

				// No size, then it's probably not a valid pic.
				if ($sizes === false)
					fatal_error2($lang->gallery_error_invalid_picture,false);
				elseif ((!empty($gallerySettings['gallery_max_width']) && $sizes[0] > $gallerySettings['gallery_max_width']) || (!empty($gallerySettings['gallery_max_height']) && $sizes[1] > $gallerySettings['gallery_max_height']))
				{
					fatal_error2($lang->gallery_error_img_size_height . $sizes[1] . $lang->gallery_error_img_size_width. $sizes[0],false);
				}
				else
				{

					//Get the filesize
					$filesize = $_FILES['picture']['size'];
					if(!empty($gallerySettings['gallery_max_filesize']) && $filesize > $gallerySettings['gallery_max_filesize'])
					{
						//Delete the temp file
						@unlink($_FILES['picture']['tmp_name']);
						fatal_error2($lang->gallery_error_img_filesize . gallery_format_size($gallerySettings['gallery_max_filesize'], 2),false);
					}
					//Delete the old files
					@unlink($gallerySettings['gallery_path'] . $oldfilename );
					@unlink($gallerySettings['gallery_path'] . $oldthumbfilename);

					//Filename Member Id + Day + Month + Year + 24 hour, Minute Seconds
					$extensions = array(
						1 => 'gif',
						2 => 'jpeg',
						3 => 'png',
						5 => 'psd',
						6 => 'bmp',
						7 => 'tiff',
						8 => 'tiff',
						9 => 'jpeg',
						14 => 'iff',
						);
					$extension = isset($extensions[$sizes[2]]) ? $extensions[$sizes[2]] : '.bmp';
			
					
					$filename = $mybb->user['uid']. '_' . date('d_m_y_g_i_s') . '.' . $extension;
					if ($failed == false)
						move_uploaded_file($_FILES['picture']['tmp_name'], $gallerySettings['gallery_path'] . $filename);
					else 
						rename($_FILES['picture']['tmp_name'], $gallerySettings['gallery_path'] . $filename);

					@chmod($gallerySettings['gallery_path'] . $filename, 0644);
					//Create thumbnail

					$tmp = generate_thumbnail($gallerySettings['gallery_path'] . $filename, substr($gallerySettings['gallery_path'], 0, -1),'thumb_' . $filename,  $gallerySettings['gallery_set_thumb_height'], $gallerySettings['gallery_set_thumb_width']);
					$thumbname = 'thumb_' . $filename;
					
					if ($tmp['code']== '4')
					{
						copy($gallerySettings['gallery_path'] . $filename,$gallerySettings['gallery_path'] . $thumbname);
					
						@chmod($gallerySettings['gallery_path'] . $thumbname, 0644);

					}
					

					//Update the Database entry
					$t = time();

					$db->query("UPDATE ".TABLE_PREFIX."gallery_pic
					SET ID_CAT = $cat, filesize = $filesize, filename = '$filename',  thumbfilename = '$thumbname', height = $sizes[1], width = $sizes[0], approved = $approved, date =  $t, title = '$title', description = '$description', keywords = '$keywords', allowcomments = $allowcomments WHERE ID_PICTURE = $id LIMIT 1");

					$plugins->run_hooks("gallery_edit_picture_completed");
					

					//Redirect to the users image page.
					redirect('ezgallery.php?action=myimages&u=' . $mybb->user['uid']);
				}

		}
		else
		{
			// Update the image properties if no upload has been set
			$db->query("UPDATE ".TABLE_PREFIX."gallery_pic
				SET ID_CAT = $cat, title = '$title', description = '$description', keywords = '$keywords', allowcomments = $allowcomments WHERE ID_PICTURE = $id LIMIT 1");

			// Redirect to the users image page.
			redirect('ezgallery.php?action=myimages&u=' . $mybb->user['uid']);

		}

	}
	else
		fatal_error2($lang->gallery_error_noedit_permission);


}

function DeletePicture()
{
	global $context, $lang, $db, $mybb, $gallerySettings;

	$id = intval($_REQUEST['id']);
	if (empty($id))
		fatal_error2($lang->gallery_error_no_pic_selected);
		
		
	GalleryTopButtons();

	// Check if the user owns the picture or is admin
    $dbresult = $db->query("
    SELECT 
    	p.ID_PICTURE, p.thumbfilename, p.width, p.height, p.allowcomments, p.ID_CAT, p.keywords, p.commenttotal, p.filesize, p.filename, p.approved, p.views, p.title, p.ID_MEMBER, m.username, p.date, p.description 
    FROM ".TABLE_PREFIX."gallery_pic as p
    LEFT JOIN ".TABLE_PREFIX."users AS m ON (m.uid = p.ID_MEMBER) 
    WHERE ID_PICTURE = $id  LIMIT 1");
	$row = $db->fetch_array($dbresult);

	// Gallery picture information
	$context['gallery_pic'] = array(
		'ID_PICTURE' => $row['ID_PICTURE'],
		'ID_MEMBER' => $row['ID_MEMBER'],
		'commenttotal' => $row['commenttotal'],
		'views' => $row['views'],
		'title' => $row['title'],
		'description' => $row['description'],
		'filesize' => $row['filesize'],
		'filename' => $row['filename'],
		'thumbfilename' => $row['thumbfilename'],
		'width' => $row['width'],
		'height' => $row['height'],
		'allowcomments' => $row['allowcomments'],
		'ID_CAT' => $row['ID_CAT'],
		'date' => my_date($mybb->settings['dateformat'], $row['date']),
		'keywords' => $row['keywords'],
		'username' => $row['username'],
		
	);


	if (AllowedTo('manage') || (AllowedTo('delete') && $mybb->user['uid']== $context['gallery_pic']['ID_MEMBER']))
	{	
		$context['page_title'] =  $lang->gallery_text_title . ' - ' . $lang->gallery_form_delpicture;
		gallery_header();


	echo '<table border="0" cellspacing="0" cellpadding="4" align="center" width="90%" class="tborder" >
					<tr class="tcat">
						<td align="center">&nbsp;</td>
					</tr>
					</table>
				<table border="0" cellpadding="0" cellspacing="0" align="center" width="90%">
						<tr>
							<td style="padding-right: 1ex;" align="right" >
						<table cellpadding="0" cellspacing="0" align="right">
									<tr>
						', DoToolBarStrip($context['gallery']['buttons'], 'top'), '
							</tr>
							</table>
						</td>
						</tr>
					</table>
				<br />';
	
	echo '
	<form method="post" action="ezgallery.php?action=delete2">
<table border="0" cellpadding="0" cellspacing="0" width="100%">
  <tr class="tcat">
    <td width="50%" colspan="2" align="center">
    <b>' . $lang->gallery_form_delpicture . '</b></td>
  </tr>
  <tr class="trow2">
    <td width="28%" colspan="2" align="center" class="trow2">
	' . $lang->gallery_warn_deletepicture . '
	<br />
<div align="center"><br /><b>' . $lang->gallery_form_delpicture . '</b><br />
<a href="ezgallery.php?action=view&id=' . $context['gallery_pic']['ID_PICTURE'] . '" target="blank"><img src="' . $gallerySettings['gallery_url'] . $context['gallery_pic']['thumbfilename']  . '" border="0" alt="" /></a><br />
			<span class="smalltext">' . $lang->gallery_text_views . $context['gallery_pic']['views'] . '<br />
			' . $lang->gallery_text_filesize  . gallery_format_size($context['gallery_pic']['filesize'],2) . '<br />
			' . $lang->gallery_text_date . $context['gallery_pic']['date'] . '<br />
			' . $lang->gallery_text_comments . ' (<a href="ezgallery.php?action=view&id=' .  $context['gallery_pic']['ID_PICTURE'] . '" target="blank">' .  $context['gallery_pic']['commenttotal'] . '</a>)<br />
	</div><br />
	<input type="hidden" name="id" value="' . $context['gallery_pic']['ID_PICTURE'] . '" />
    <input type="hidden" name="my_post_key" value="' . $mybb->post_code . '" />
    <input type="submit" value="' . $lang->gallery_form_delpicture . '" name="submit" /><br />
    </td>
  </tr>
</table>

		</form>
';

	GalleryCopyright();
		
		
	}
	else
	{
		fatal_error2($lang->gallery_error_nodelete_permission);
	}

	gallery_footer();

}

function ViewC()
{
	die(base64_decode('UG93ZXJlZCBieSBlekdhbGxlcnkgTGl0ZSBGb3IgTXlCQiAgbWFkZSBieSB2YmdhbWVyNDUgaHR0cDovL3d3dy5teWJiaGFja3MuY29t'));
}

function DeletePicture2()
{
	global $lang, $mybb, $db, $gallerySettings, $plugins;

	$id = intval($_REQUEST['id']);
	if (empty($id))
		fatal_error2($lang->gallery_error_no_pic_selected);
        
        
    // Verify incoming POST request
    verify_post_check($mybb->get_input('my_post_key'));

	// Check if the user owns the picture or is admin
    $dbresult = $db->query("
    SELECT 
    	p.ID_PICTURE, p.filename, p.thumbfilename,  p.ID_MEMBER 
    FROM ".TABLE_PREFIX."gallery_pic as p 
    WHERE ID_PICTURE = $id LIMIT 1");
	$row = $db->fetch_array($dbresult);
	$memID = $row['ID_MEMBER'];


	if (AllowedTo('manage') || (AllowedTo('delete') && $mybb->user['uid']== $memID))
	{
		//Delete Large image
		@unlink($gallerySettings['gallery_path'] . $row['filename']);
		//Delete Thumbnail
		@unlink($gallerySettings['gallery_path'] . $row['thumbfilename']);

		// Delete all the picture related db entries

		$db->query("DELETE FROM ".TABLE_PREFIX."gallery_comment WHERE ID_PICTURE  = $id LIMIT 1");
		
		$db->query("DELETE FROM ".TABLE_PREFIX."gallery_report WHERE ID_PICTURE  = $id LIMIT 1");

		// Delete the picture
		$db->query("DELETE FROM ".TABLE_PREFIX."gallery_pic WHERE ID_PICTURE = $id LIMIT 1");
			

		$plugins->run_hooks("gallery_delete_picture_completed");	
		
		// Redirect to the users image page.
		redirect('ezgallery.php?action=myimages&u=' . $mybb->user['uid']);


	}
	else
	{
		fatal_error2($lang->gallery_error_nodelete_permission);
	}


}

function ReportPicture()
{
	global $context, $lang, $mybb;

	isAllowedTo('report');

	$id = intval($_REQUEST['id']);
	if (empty($id))
		fatal_error2($lang->gallery_error_no_pic_selected);
		

	$context['gallery_pic_id'] = $id;

	$context['page_title'] = $lang->gallery_text_title . ' - ' . $lang->gallery_form_reportpicture;
	gallery_header();
	
		echo '
<form method="post" name="cprofile" id="cprofile" action="ezgallery.php?action=report2">
<table border="0" cellpadding="0" cellspacing="0" bordercolor="#FFFFFF" width="100%">
  <tr>
    <td width="50%" colspan="2" align="center" class="tcat">
    <b>' . $lang->gallery_form_reportpicture . '</b></td>
  </tr>
  <tr>
    <td width="28%"  valign="top" class="trow2" align="right"><span class="gen"><b>' . $lang->gallery_form_comment . '</b>&nbsp;</span></td>
    <td width="72%" class="trow2"><textarea rows="6" name="comment" cols="54"></textarea></td>
  </tr>
  <tr>
    <td width="28%" colspan="2"  align="center" class="trow2">
    <input type="hidden" name="id" value="' . $context['gallery_pic_id'] . '" />
    <input type="hidden" name="my_post_key" value="' . $mybb->post_code . '" />
    <input type="submit" value="' . $lang->gallery_form_reportpicture . '" name="submit" /></td>

  </tr>
</table>
</form>';

	GalleryCopyright();
	
}

function ReportPicture2()
{
	global $db, $lang, $mybb;

	isAllowedTo('report');
    
    // Verify incoming POST request
    verify_post_check($mybb->get_input('my_post_key'));

	$comment = htmlspecialchars_uni($db->escape_string($_REQUEST['comment']));
	$id = intval($_REQUEST['id']);
	if (empty($id))
		fatal_error2($lang->gallery_error_no_pic_selected);

	if (trim($comment) == '')
		fatal_error2($lang->gallery_error_no_comment,false);
        

	$commentdate = time();

	$db->query("INSERT INTO ".TABLE_PREFIX."gallery_report
			(ID_MEMBER, comment, date, ID_PICTURE)
		VALUES (" . $mybb->user['uid'] . ",'$comment', $commentdate,$id)");

	redirect('ezgallery.php?action=view&id=' . $id);

}

function AddComment()
{
	global $context, $lang, $db, $mybb;

	isAllowedTo('comment');

	GalleryTopButtons();

	$id = intval($_REQUEST['id']);
	if(empty($id) )
		fatal_error2($lang->gallery_error_no_pic_selected);

	$context['gallery_pic_id'] = $id;

	// Comments allowed check
    $dbresult = $db->query("
    SELECT 
    	p.allowcomments 
    FROM ".TABLE_PREFIX."gallery_pic as p 
    WHERE ID_PICTURE = $id LIMIT 1");
	$row = $db->fetch_array($dbresult);

	// Checked if comments are allowed
	if ($row['allowcomments'] == 0)
		fatal_error2($lang->gallery_error_not_allowcomment);


	$context['page_title'] = $lang->gallery_text_title . ' - ' . $lang->gallery_text_addcomment;
	gallery_header();
	echo '<table border="0" cellspacing="0" cellpadding="4" align="center" width="90%" class="tborder" >
					<tr class="tcat">
						<td align="center">&nbsp;</td>
					</tr>
					</table>
				<table border="0" cellpadding="0" cellspacing="0" align="center" width="90%">
						<tr>
							<td style="padding-right: 1ex;" align="right" >
						<table cellpadding="0" cellspacing="0" align="right">
									<tr>
						', DoToolBarStrip($context['gallery']['buttons'], 'top'), '
							</tr>
							</table>
						</td>
						</tr>
					</table>
				<br />';
	
	
	echo '
<form method="post" name="cprofile" id="cprofile" action="ezgallery.php?action=comment2">
<table border="0" cellpadding="0" cellspacing="0" bordercolor="#FFFFFF" width="100%">
  <tr>
    <td width="50%" colspan="2" align="center" class="tcat">
    <b>' . $lang->gallery_text_addcomment . '</b></td>
  </tr>

  <tr>
    <td width="28%"  valign="top" class="trow2" align="right"><span class="gen"><b>' . $lang->gallery_form_comment . '</b>&nbsp;</span></td>
    <td width="72%"  class="trow2"><textarea rows="10" cols="75" name="comment"></textarea></td>
  </tr>
  <tr>
    <td width="28%" colspan="2" align="center" class="trow2">
    <input type="hidden" name="my_post_key" value="' . $mybb->post_code . '" />
    <input type="hidden" name="id" value="' . $context['gallery_pic_id'] . '" />';
 
echo '
    <input type="submit" value="' . $lang->gallery_text_addcomment . '" name="submit" /></td>

  </tr>
</table>
</form>';

gallery_footer();

}

function AddComment2()
{
	global $db, $mybb, $lang, $plugins;

	isAllowedTo('comment');

	$comment = htmlspecialchars_uni($db->escape_string($_REQUEST['comment']));
	$id = intval($_REQUEST['id']);
	if (empty($id))
		fatal_error2($lang->gallery_error_no_pic_selected);
        
    // Verify incoming POST request
    verify_post_check($mybb->get_input('my_post_key'));

	// Check if that picture allows comments.
    $dbresult = $db->query("
    SELECT 
    	p.allowcomments 
    FROM ".TABLE_PREFIX."gallery_pic as p 
    WHERE ID_PICTURE = $id LIMIT 1");
	$row = $db->fetch_array($dbresult);

	// Checked if comments are allowed
	if ($row['allowcomments'] == 0)
		fatal_error2($lang->gallery_error_not_allowcomment);

	if (trim($comment) == '')
		fatal_error2($lang->gallery_error_no_comment,false);

	$commentdate = time();

	$db->query("INSERT INTO ".TABLE_PREFIX."gallery_comment
			(ID_MEMBER, comment, date, ID_PICTURE)
		VALUES (" . $mybb->user['uid'] . ",'$comment', $commentdate,$id)");
	
	
	$plugins->run_hooks("gallery_comment_add_completed");	
	
	
	//Update Comment total
	 $db->query("UPDATE ".TABLE_PREFIX."gallery_pic
		SET commenttotal = commenttotal + 1 WHERE ID_PICTURE = $id LIMIT 1");


	redirect('ezgallery.php?action=view&id=' . $id);

}

function MyImages()
{
	global $context, $mybb, $lang, $db, $gallerySettings, $config;

	isAllowedTo('view');
	

	GalleryTopButtons();

	$u = intval($_REQUEST['u']);
	if (empty($u))
		fatal_error2($lang->gallery_error_no_user_selected);
		

	// Store the gallery userid
	$context['gallery_userid'] = $u;

    $dbresult = $db->query("
    SELECT 
    	m.username 
    FROM ".TABLE_PREFIX."users AS m 
    WHERE m.uid = $u  LIMIT 1");
	$row = $db->fetch_array($dbresult);
	$context['gallery_usergallery_name'] = $row['username'];

	
	add_breadcrumb(htmlspecialchars_uni($row['username']), 'ezgallery.php?sa=myimages&u=' . $u);
	


	$context['page_title'] =  $lang->gallery_text_title . ' - ' . $context['gallery_usergallery_name'];
	gallery_header();
	
	
	if ($mybb->user['uid']== $context['gallery_userid'])
		$query = $db->query("
		SELECT COUNT(*) as total
		FROM ".TABLE_PREFIX."gallery_pic as p, ".TABLE_PREFIX."users AS m WHERE p.ID_MEMBER = " . $context['gallery_userid']  . " AND p.ID_MEMBER = m.uid");
	else 
	$query = $db->query("
		SELECT COUNT(*) as total
		FROM ".TABLE_PREFIX."gallery_pic as p, ".TABLE_PREFIX."users AS m WHERE p.ID_MEMBER = " . $context['gallery_userid']  . " AND p.ID_MEMBER = m.uid AND p.approved = 1");

	
	$totalPicCount = $db->fetch_array($query);
			
		$perpage = $gallerySettings['gallery_set_images_per_page'];
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
	$pagingList = multipage($totalPicCount['total'], $perpage, $page, "ezgallery.php?action=myimages&u=$u");
				

	
	if ($mybb->user['uid']== $context['gallery_userid'])
    	$dbresult = $db->query("SELECT p.ID_PICTURE, p.commenttotal, p.filesize, p.thumbfilename, p.approved, p.views, p.ID_MEMBER, m.username, p.date, p.filename, p.height, p.width  
    	FROM ".TABLE_PREFIX."gallery_pic as p, ".TABLE_PREFIX."users AS m WHERE p.ID_MEMBER = " . $context['gallery_userid']. " AND p.ID_MEMBER = m.uid LIMIT $start,$perpage");
	else
    	$dbresult = $db->query("SELECT p.ID_PICTURE, p.commenttotal, p.filesize, p.thumbfilename, p.approved, p.views, p.ID_MEMBER, m.username, p.date, p.filename, p.height, p.width  
    	FROM ".TABLE_PREFIX."gallery_pic as p, ".TABLE_PREFIX."users AS m WHERE p.ID_MEMBER = " . $context['gallery_userid']  . " AND p.ID_MEMBER = m.uid AND p.approved = 1 LIMIT $start,$perpage");
	$context['gallery_myimages'] = array();
	while($row = $db->fetch_array($dbresult))
	{
		$context['gallery_myimages'][] = $row;
	}

	
	

	//Get the permissions for the user
	$g_add = allowedTo('add');
	$g_manage = allowedTo('manage');
	$g_edit_own = allowedTo('edit');
	$g_delete_own = allowedTo('delete');


	// Check if GD is installed if not we will not show the thumbnails
	$GD_Installed = function_exists('imagecreate');


	echo '<table border="0" cellspacing="0" cellpadding="4" align="center" width="90%" class="tborder" >
					<tr class="tcat">
						<td align="center">&nbsp;</td>
					</tr>
					</table>
				<table border="0" cellpadding="0" cellspacing="0" align="center" width="90%">
						<tr>
							<td style="padding-right: 1ex;" align="right" >
						<table cellpadding="0" cellspacing="0" align="right">
									<tr>
						', DoToolBarStrip($context['gallery']['buttons'], 'top'), '
							</tr>
							</table>
						</td>
						</tr>
					</table>
				<br />';
	

	$maxrowlevel = $gallerySettings['gallery_set_images_per_row'];
	echo '<br /><table cellspacing="0" cellpadding="10" border="0" align="center" width="90%" class="tborder">
					<tr class="tcat">
						<td align="center" colspan="' . $maxrowlevel . '">' . $context['gallery_usergallery_name'] . '</td>
					</tr>';

	$rowlevel = 0;
	$userid = $context['gallery_userid'];
	// Check if it is the user ids gallery mainly to show unapproved pictures or not
	
    $styleclass = 'trow1';

	foreach($context['gallery_myimages'] as $row)
	{
			if($rowlevel == 0)
				echo '<tr class="' . $styleclass . '">';

			echo '<td align="center"><a href="ezgallery.php?action=view&id=' . $row['ID_PICTURE'] . '">
			<img ' . ($GD_Installed == true ?  'src="' . $gallerySettings['gallery_url'] . $row['thumbfilename'] . '" ' : 'src="' . $gallerySettings['gallery_url'] . $row['filename'] . '" height="78" width="120" ')  . ' border="0" alt="" /></a><br />';
			if($mybb->user['uid']== $userid)
			{
				if($row['approved'] == 1)
					echo '<b>' . $lang->gallery_myimages_app . '</b><br />';
				else
					echo '<b>' . $lang->gallery_myimages_notapp . '</b><br />';
			}

			echo '<span class="smalltext">' . $lang->gallery_text_views . $row['views'] . '<br />';
			echo $lang->gallery_text_filesize . gallery_format_size($row['filesize'], 2) . '<br />';
			echo $lang->gallery_text_date . my_date($mybb->settings['dateformat'], $row['date']) . '<br />';
			echo $lang->gallery_text_comments . ' (<a href="ezgallery.php?action=view&id=' . $row['ID_PICTURE'] . '">' . $row['commenttotal'] . '</a>)<br />';
			echo $lang->gallery_text_by . ' <a href="member.php?action=profile&uid=' . $row['ID_MEMBER'] . '">'  . $row['username'] . '</a><br />';
			if($g_manage)
				echo '&nbsp;<a href="' . $mybb->settings['bburl'] . '/' . $config['admin_dir'] . '/index.php?module=config/ezgallery&action=unapprove&id=' . $row['ID_PICTURE'] . '">' . $lang->gallery_text_unapprove . '</a>';
			if($g_manage || $g_edit_own && $row['ID_MEMBER'] == $mybb->user['uid'])
				echo '&nbsp;<a href="ezgallery.php?action=edit&id=' . $row['ID_PICTURE'] . '">' . $lang->gallery_text_edit . '</a>';
			if($g_manage || $g_delete_own && $row['ID_MEMBER'] == $mybb->user['uid'])
				echo '&nbsp;<a href="ezgallery.php?action=delete&id=' . $row['ID_PICTURE'] . '">' . $lang->gallery_text_delete . '</a>';

			echo '</span></td>';


			if($rowlevel < ($maxrowlevel-1))
				$rowlevel++;
			else
			{
				echo '</tr>';
				$rowlevel = 0;
			}
			
			
			if ($styleclass == 'trow1')
				$styleclass = 'trow2';
			else 
				$styleclass = 'trow1';
			
			
			
	}
		if($rowlevel !=0)
		{
			echo '<td colspan="' . ($maxrowlevel - $rowlevel) . '"></td>';
			echo '</tr>';
		}
		
	echo '<tr class="' . $styleclass . '"><td colspan="' . $maxrowlevel . '">'
		 . $pagingList .
		 '</td></tr>';

		//Show return to gallery link and Show add picture if they can
		echo '
				<tr class="tcat"><td align="center" colspan="' . $maxrowlevel . '">';
				if ($g_add)
				echo '<a href="ezgallery.php?action=add">' . $lang->gallery_text_addpicture . '</a><br />';

				echo '
				<a href="ezgallery.php">' . $lang->gallery_text_returngallery . '</a></td>
			</tr>';


		echo '</table>';
		

	GalleryCopyright();
	
	gallery_footer();
}

function GalleryCopyright()
{
	echo '<div align="center">Powered by: <a href="http://www.mybbhacks.com" target="blank">ezGallery Lite</a> by <a href="http://www.createaforum.com" title="Forum Hosting">CreateAForum.com</a></div>';
}

function Search()
{
	global $context, $lang, $mybb;

	//  the user allowed to view the gallery?
	isAllowedTo('view');
	
	$g_add = allowedTo('add');
	
	// MyImages
	if ($g_add && $mybb->user['uid'] != 0)	
	$context['gallery']['buttons']['mylisting'] =  array(
		'text' => $lang->gallery_myimages,
		'url' => 'ezgallery.php?action=myimages&u=' . $mybb->user['uid'],
		'lang' => true,
		'image' => '',

	);

	// Search
	$context['gallery']['buttons']['search'] =  array(
		'text' => $lang->gallery_search,
		'url' => 'ezgallery.php?action=search',
		'lang' => true,
		'image' => '',

	);
	
	
	// Link Tree
	$context['linktree'][] = array(
					'url' => 'ezgallery.php',
					'name' => $lang->gallery_text_title
				);

	


	$context['page_title'] = $lang->gallery_text_title . ' - ' . $lang->gallery_search;
	gallery_header();

	echo '<table border="0" cellspacing="0" cellpadding="4" align="center" width="90%" class="tborder" >
					<tr class="tcat">
						<td align="center">&nbsp;</td>
					</tr>
					</table>
				<table border="0" cellpadding="0" cellspacing="0" align="center" width="90%">
						<tr>
							<td style="padding-right: 1ex;" align="right" >
						<table cellpadding="0" cellspacing="0" align="right">
									<tr>
						', DoToolBarStrip($context['gallery']['buttons'], 'top'), '
							</tr>
							</table>
						</td>
						</tr>
					</table>
				<br />';
	

	echo '
<form method="post" action="ezgallery.php?action=search2">
<table border="0" cellpadding="0" cellspacing="0" bordercolor="#FFFFFF" width="50%"  class="tborder" align="center">
  <tr>
    <td width="100%" colspan="2" align="center" class="tcat">
    <b>', $lang->gallery_search_pic, '</b></td>
  </tr>
  <tr class="trow2">
    <td width="50%"  align="right"><b>' . $lang->gallery_search_for . '</b>&nbsp;</td>
    <td width="50%"><input type="text" name="searchfor" />
    </td>
  </tr>
  <tr class="trow2" align="center">
  	<td colspan="2"><input type="checkbox" name="searchtitle" checked="checked" />', $lang->gallery_search_title, '&nbsp;<input type="checkbox" name="searchdescription" checked="checked" />' . $lang->gallery_search_description . '<br />
  	<input type="checkbox" name="searchkeywords" />', $lang->gallery_search_keyword, '</td>
  </tr>
  <tr>
    <td width="100%" colspan="2" align="center" class="trow2">
    <input type="hidden" name="my_post_key" value="' . $mybb->post_code . '" />
    <input type="submit" value="', $lang->gallery_search, '" name="submit" /></td>

  </tr>
</table>
</form>';


	GalleryCopyright();
	
	gallery_footer();

}

function Search2()
{
	global $context, $config, $gallerySettings, $lang, $mybb, $db;

	// Is the user allowed to view the gallery?
	isAllowedTo('view');
	
	$g_add = allowedTo('add');
    
    
    // Verify incoming POST request
    if (!isset($_GET['key']))
    verify_post_check($mybb->get_input('my_post_key'));
	
	
	// MyImages
	if ($g_add && $mybb->user['uid'] != 0)	
	$context['gallery']['buttons']['mylisting'] =  array(
		'text' => $lang->gallery_myimages,
		'url' =>'ezgallery.php?action=myimages&u=' . $mybb->user['uid'],
		'lang' => true,
		'image' => '',

	);

	// Search
	$context['gallery']['buttons']['search'] =  array(
		'text' => $lang->gallery_search,
		'url' => 'ezgallery.php?action=search',
		'lang' => true,
		'image' => '',

	);
	
	// Link Tree
	$context['linktree'][] = array(
					'url' => 'ezgallery.php',
					'name' => $lang->gallery_text_title
				);
				
	

	// Check if keyword search was selected
	@$keyword =  htmlspecialchars_uni($db->escape_string($_REQUEST['key']));
	if($keyword == '')
	{
		//Probably a normal Search
		$searchfor =  htmlspecialchars_uni($db->escape_string($_REQUEST['searchfor']));
		if($searchfor == '')
			fatal_error2($lang->gallery_error_no_search,false);

		if(strlen($searchfor) <= 3)
			fatal_error2($lang->gallery_error_search_small,false);

		//Check the search options

		@$searchtitle = $_REQUEST['searchtitle'];
		@$searchdescription = $_REQUEST['searchdescription'];

		$s1 = 1;
		$searchquery = '';
		if($searchtitle)
			$searchquery = "p.title LIKE '%$searchfor%' ";
		else
			$s1 = 0;

		$s2 = 1;
		if($searchdescription)
		{
			if($s1 == 1)
				$searchquery = "p.title LIKE '%$searchfor%' OR p.description LIKE '%$searchfor%'";
			else
				$searchquery = "p.description LIKE '%$searchfor%'";
		}
		else
			$s2 = 0;

			/*
		if($searchkeywords)
		{
			if($s1 == 1 || $s2 == 1)
				$searchquery .= " OR p.keywords LIKE '%$searchfor%'";
			else
				$searchquery = "p.keywords LIKE '%$searchfor%'";
		}
		*/


		if($searchquery == '')
			$searchquery = "p.title LIKE '%$searchfor%' ";

		$context['gallery_search_query'] = $searchquery;



		$context['gallery_search'] = $searchfor;
	}
	else
	{
		//Search for the keyword


		//Debating if I should add string length check for keywords...
		//if(strlen($keyword) <= 3)
			//fatal_error2($lang->gallery_error_search_small);

		$context['gallery_search'] = $keyword;

		$context['gallery_search_query'] = "p.keywords LIKE '%$keyword%'";
	}



	$context['page_title'] = $lang->gallery_text_title . ' - ' . $lang->gallery_searchresults;
	gallery_header();
	
 	$dbresult = $db->query("
    SELECT 
    	p.ID_PICTURE, p.commenttotal, p.keywords, p.filesize, p.thumbfilename, p.approved, p.views, p.ID_MEMBER, m.username, p.date, p.width, p.height, p.filename FROM ".TABLE_PREFIX."gallery_pic as p
    LEFT JOIN ".TABLE_PREFIX."users AS m ON (p.ID_MEMBER = m.uid) 
    WHERE p.approved = 1 AND (" . $context['gallery_search_query'] . ")");
	$context['gallery_search_results'] = array();
	while($row = $db->fetch_array($dbresult))
	{
		$context['gallery_search_results'][] = $row;
	}

	
	
	// Get the permissions for the user
	$g_add = allowedTo('add');
	$g_manage = allowedTo('manage');
	$g_edit_own = allowedTo('edit');
	$g_delete_own = allowedTo('delete');


	// Check if GD is installed if not we will not show the thumbnails
	$GD_Installed = function_exists('imagecreate');


	echo '<table border="0" cellspacing="0" cellpadding="4" align="center" width="90%" class="tborder" >
					<tr class="tcat">
						<td align="center">&nbsp;</td>
					</tr>
					</table>
				<table border="0" cellpadding="0" cellspacing="0" align="center" width="90%">
						<tr>
							<td style="padding-right: 1ex;" align="right" >
						<table cellpadding="0" cellspacing="0" align="right">
									<tr>
						', DoToolBarStrip($context['gallery']['buttons'], 'top'), '
							</tr>
							</table>
						</td>
						</tr>
					</table>
				<br />';
	

	$maxrowlevel = $gallerySettings['gallery_set_images_per_row'];
	echo '<br /><table cellspacing="0" cellpadding="10" border="0" align="center" width="90%" class="tborder">
					<tr class="tcat">
						<td align="center" colspan="' . $maxrowlevel . '">' . $lang->gallery_searchresults . '</td>
					</tr>';

	$rowlevel = 0;
   
    $styleclass = 'trow1';
    
  	foreach($context['gallery_search_results'] as $row)
	{
			if ($rowlevel == 0)
				echo '<tr class="' . $styleclass . '">';

			echo '<td align="center"><a href="ezgallery.php?action=view&id=' . $row['ID_PICTURE'] . '">
			<img ' . ($GD_Installed == true ?  'src="' . $gallerySettings['gallery_url'] . $row['thumbfilename'] . '" ' : 'src="' . $gallerySettings['gallery_url'] . $row['filename'] . '" height="78" width="120" ')  . ' border="0" alt="" /></a><br />';
			echo '<span class="smalltext">' . $lang->gallery_text_views . $row['views'] . '<br />';
			echo $lang->gallery_text_filesize . gallery_format_size($row['filesize'], 2) . '<br />';
			echo $lang->gallery_text_date . my_date($mybb->settings['dateformat'], $row['date']) . '<br />';
			echo $lang->gallery_text_comments . ' (<a href="ezgallery.php?action=view&id=' . $row['ID_PICTURE'] . '">' . $row['commenttotal'] . '</a>)<br />';
			if ($row['username'] != '')
				echo $lang->gallery_text_by . ' <a href="member.php?action=profile&uid=' . $row['ID_MEMBER'] . '">'  . $row['username'] . '</a><br />';
			else 
				echo $lang->gallery_text_by . $lang->gallery_guest . '<br />';
			if ($g_manage)
				echo '&nbsp;<a href="' . $mybb->settings['bburl'] . '/' . $config['admin_dir'] . '/index.php?module=config/ezgallery&action=unapprove&id=' . $row['ID_PICTURE'] . '">' . $lang->gallery_text_unapprove . '</a>';
			if ($g_manage || $g_edit_own && $row['ID_MEMBER'] == $mybb->user['uid'])
				echo '&nbsp;<a href="ezgallery.php?action=edit&id=' . $row['ID_PICTURE'] . '">' . $lang->gallery_text_edit . '</a>';
			if ($g_manage || $g_delete_own && $row['ID_MEMBER'] == $mybb->user['uid'])
				echo '&nbsp;<a href="ezgallery.php?action=delete&id=' . $row['ID_PICTURE'] . '">' . $lang->gallery_text_delete . '</a>';

			echo '</span></td>';


			if ($rowlevel < ($maxrowlevel-1))
				$rowlevel++;
			else
			{
				echo '</tr>';
				$rowlevel = 0;
			}
			

			if ($styleclass == 'trow1')
				$styleclass = 'trow2';
			else 
				$styleclass = 'trow1';			
			
			
	}
		if($rowlevel !=0)
		{
			echo '<td colspan="' . ($maxrowlevel - $rowlevel) . '"></td>';
			echo '</tr>';
		}


		// Show return to gallery link and Show add picture if they can
		echo '
				<tr class="tcat"><td align="center" colspan="' . $maxrowlevel . '">';
				if ($g_add)
				echo '<a href="ezgallery.php?action=add">' . $lang->gallery_text_addpicture . '</a><br />';

				echo '
				<a href="ezgallery.php">' . $lang->gallery_text_returngallery . '</a></td>
			</tr>';

		echo '</table>';
		


	GalleryCopyright();
	
	gallery_footer();

}

function GalleryTopButtons()
{
	global $lang, $context, $mybb;
	
	$g_add = allowedTo('add');
	
	// MyImages
	if ($g_add && $mybb->user['uid'] != 0)	
	$context['gallery']['buttons']['mylisting'] =  array(
		'text' => $lang->gallery_myimages,
		'url' => 'ezgallery.php?action=myimages&u=' . $mybb->user['uid'],
		'lang' => true,
		'image' => '',

	);

	// Search
	$context['gallery']['buttons']['search'] =  array(
		'text' => $lang->gallery_search,
		'url' => 'ezgallery.php?action=search',
		'lang' => true,
		'image' => '',

	);
	
	// Link Tree
	$context['linktree'][] = array(
					'url' => 'ezgallery.php',
					'name' => $lang->gallery_text_title
				);
}

function DoToolBarStrip($button_strip, $direction )
{	

	echo '<td>';
		
	foreach ($button_strip as $tab)
			{

				echo '
							<a href="', $tab['url'], '">',$tab['text'], '</a>';

				if (empty($tab['is_last']))
					echo ' | ';
			}
			
	echo '</td>';	

}

function ViewPicture()
{
	global $context, $db, $mybb, $gallerySettings ,$lang, $config, $parser, $parser_options, $plugins;

	isAllowedTo('view');

	// Get the picture ID
	$id = intval($_REQUEST['id']);
	if (empty($id))
		fatal_error2($lang->gallery_error_no_pic_selected, false);
		
 	GalleryTopButtons();
	

	// Get the picture information
    $dbresult = $db->query("
    SELECT 
    	p.ID_PICTURE, p.width, p.height, p.allowcomments, p.ID_CAT, p.keywords, p.commenttotal, p.filesize, p.filename, p.approved, 
    	p.views, p.title, p.ID_MEMBER, m.username, p.date, p.description, c.title CATNAME  
    FROM ".TABLE_PREFIX."gallery_pic as p
    LEFT JOIN ".TABLE_PREFIX."gallery_cat AS c ON (c.ID_CAT= p.ID_CAT) 
    LEFT JOIN ".TABLE_PREFIX."users AS m ON (p.ID_MEMBER = m.uid) 
    WHERE p.ID_PICTURE = $id   LIMIT 1");
	$row = $db->fetch_array($dbresult);
	
	add_breadcrumb(htmlspecialchars_uni($row['CATNAME']), 'ezgallery.php?cat=' . $row['ID_CAT']);
	add_breadcrumb(htmlspecialchars_uni($row['title']), 'ezgallery.php?sa=view&id=' . $id);

	// Checked if they are allowed to view an unapproved picture.
	if ($row['approved'] == 0 && $mybb->user['uid']!= $row['ID_MEMBER'])
	{
		if(!allowedTo('manage'))
			fatal_error2($lang->gallery_error_pic_notapproved,false);
	}
	
	$plugins->run_hooks("gallery_view_picture_start");	


	$context['linktree'][] = array(
					'url' => 'ezgallery.php?cat=' . $row['ID_CAT'],
					'name' => $row['CATNAME'],
				);
	
	// Gallery picture information
	$context['gallery_pic'] = array(
		'ID_PICTURE' => $row['ID_PICTURE'],
		'ID_MEMBER' => $row['ID_MEMBER'],
		'commenttotal' => $row['commenttotal'],
		'views' => $row['views'],
		'title' => $row['title'],
		'description' => $row['description'],
		'filesize' => $row['filesize'],
		'filename' => $row['filename'],
		'width' => $row['width'],
		'height' => $row['height'],
		'allowcomments' => $row['allowcomments'],
		'ID_CAT' => $row['ID_CAT'],
		'date' => my_date($mybb->settings['dateformat'], $row['date']),
		'keywords' => $row['keywords'],
		'username' => $row['username'],
		'username' => $row['username'],
	);



	// Update the number of views.
	  $dbresult = $db->query("UPDATE ".TABLE_PREFIX."gallery_pic
		SET views = views + 1 WHERE ID_PICTURE = $id LIMIT 1");



	$context['page_title'] = $context['gallery_pic']['title'];
	gallery_header();
	if (!empty($gallerySettings['gallery_who_viewing']))
	{
		$context['can_moderate_forum'] = allowedTo('manage');
	
				// Start out with no one at all viewing it.
				$context['view_members'] = array();
				$context['view_members_list'] = array();
				$context['view_num_hidden'] = 0;

				$timesearch = TIME_NOW - $mybb->settings['wolcutoffmins']*60;
				// Search for members who have this picture id set in their GET data.
				$request = $db->query("SELECT
						lo.uid, lo.time, mem.username, mem.invisible,
						mg.namestyle, mg.gid, mg.title
					FROM (".TABLE_PREFIX."sessions AS lo)
						LEFT JOIN ".TABLE_PREFIX."users AS mem ON (mem.uid = lo.uid)
						LEFT JOIN ".TABLE_PREFIX."usergroups AS mg ON (mem.usergroup = mg.gid)
					WHERE INSTR(lo.location, 'ezgallery.php?action=view&amp;id=$id') AND lo.time > {$timesearch}");
				while ($row = $db->fetch_array($request))
				{
					if (empty($row['uid']))
						continue;

					if (!empty($row['namestyle']))
						$link = '<a href="member.php?action=profile&uid=' . $row['uid'] . '">' . str_replace("{username}",$row['username'],$row['namestyle']) . '</a>';
					else
						$link = '<a href="member.php?action=profile&uid=' . $row['uid'] . '">' . $row['username'] . '</a>';

	
					// Add them both to the list and to the more detailed list.
					if (empty($row['invisible']))
						$context['view_members_list'][$row['time'] . $row['username']] = !empty($row['invisible']) ? '<i>' . $link . '</i>' : $link;
					$context['view_members'][$row['time'] . $row['username']] = array(
						'id' => $row['uid'],
						'username' => $row['username'],
						'name' => $row['username'],
						'group' => $row['gid'],
						'href' => 'member.php?action=profile&uid=' . $row['uid'],
						'link' => $link,
						'hidden' => !empty($row['invisible']),
					);

					if (!empty($row['invisible']))
						$context['view_num_hidden']++;
				}

				// The number of guests is equal to the rows minus the ones we actually used ;).
				$context['view_num_guests'] = $db->num_rows($request) - count($context['view_members']);


				// Sort the list.
				krsort($context['view_members']);
				krsort($context['view_members_list']);

	}
	
		$dbresult = $db->query("
		SELECT 
			c.ID_PICTURE,  c.ID_COMMENT, c.date, c.comment, c.ID_MEMBER, m.postnum, m.username, m.avatar  
			FROM ".TABLE_PREFIX."gallery_comment as c
			LEFT JOIN ".TABLE_PREFIX."users AS m ON (c.ID_MEMBER = m.uid) 
		WHERE   c.ID_PICTURE = " . $context['gallery_pic']['ID_PICTURE'] . " ORDER BY c.ID_COMMENT DESC");
		
		$context['gallery_comment_list'] = array();
		while($row = $db->fetch_array($dbresult))
		{
			$context['gallery_comment_list'][] = $row;
		}

		$context['gallery_comment_count'] = count($context['gallery_comment_list']);
		

		
	$plugins->run_hooks("gallery_view_picture_finish");	
		
	// Load permissions
	$g_manage = allowedTo('manage');
	$g_edit_own = allowedTo('edit');
	$g_delete_own = allowedTo('delete');


	// Keywords
	$keywords = explode(' ',$context['gallery_pic']['keywords']);
 	$keywordscount = count($keywords);

	echo '<table border="0" cellspacing="0" cellpadding="4" align="center" width="90%" class="tborder" >
					<tr class="tcat">
						<td align="center">&nbsp;</td>
					</tr>
					</table>
				<table border="0" cellpadding="0" cellspacing="0" align="center" width="90%">
						<tr>
							<td style="padding-right: 1ex;" align="right" >
						<table cellpadding="0" cellspacing="0" align="right">
									<tr>
						', DoToolBarStrip($context['gallery']['buttons'], 'top'), '
							</tr>
							</table>
						</td>
						</tr>
					</table>
				<br />';
	


	echo '<br /><table cellspacing="0" cellpadding="10" border="0" align="center" width="90%" class="tborder">
			<tr class="tcat">
				<td align="center">' . $context['gallery_pic']['title'] . '</td>
			</tr>
			<tr class="trow2">
				<td align="center"><img height="' . $context['gallery_pic']['height']  . '" width="' . $context['gallery_pic']['width']  . '" src="' . $gallerySettings['gallery_url'] . $context['gallery_pic']['filename']  . '" alt="" /></td>
			</tr>
			<tr class="trow2">

				<td>
				<b>' . $lang->gallery_form_description . ' </b>' . ($parser->parse_message($context['gallery_pic']['description'], $parser_options))  . '
				<hr />
				' . $lang->gallery_text_picstats . '<br />

				' . $lang->gallery_text_views . $context['gallery_pic']['views'] . '<br />
				' . $lang->gallery_text_filesize  . gallery_format_size($context['gallery_pic']['filesize'],2) . '<br />
				'  . $lang->gallery_text_height . ' ' . $context['gallery_pic']['height']  . '  ' . $lang->gallery_text_width . ' ' . $context['gallery_pic']['width'] . '<br />
				';
	
				if (!empty($context['gallery_pic']['keywords']))
				{
				
					echo $lang->gallery_form_keywords . ' ';
					for($i = 0; $i < $keywordscount;$i++)
					{
						echo '<a href="ezgallery.php?action=search2&key=' . $keywords[$i] . '">' . $keywords[$i] . '</a>&nbsp;';
					}
					echo '<br />';
					
				}

				if ($context['gallery_pic']['username'] != '')
					echo$lang->gallery_text_postedby . '<a href="member.php?action=profile&uid=' . $context['gallery_pic']['ID_MEMBER'] . '">'  . $context['gallery_pic']['username'] . '</a>' . $lang->gallery_at . $context['gallery_pic']['date'] . '<br /><br />';
				else 
					echo $lang->gallery_text_postedby . $lang->gallery_guest  . $lang->gallery_at . $context['gallery_pic']['date'] . '<br /><br />';
				
				
				// Show image linking codes
				if ($gallerySettings['gallery_set_showcode_bbc_image']  || $gallerySettings['gallery_set_showcode_directlink'] || $gallerySettings['gallery_set_showcode_htmllink'])
				{
					echo '<b>',$lang->gallery_txt_image_linking,'</b><br />
					<table border="0">
					';
					
					if ($gallerySettings['gallery_set_showcode_bbc_image'])
					{
						echo '<tr><td width="30%">', $lang->gallery_txt_bbcimage, '</td><td> <input type="text" value="[img]' . $gallerySettings['gallery_url'] . $context['gallery_pic']['filename']  . '[/img]" size="50" /></td></tr>';
					}
					if ($gallerySettings['gallery_set_showcode_directlink'])
					{
						echo '<tr><td width="30%">', $lang->gallery_txt_directlink, '</td><td> <input type="text" value="' . $gallerySettings['gallery_url'] . $context['gallery_pic']['filename']  . '" size="50" /></td></tr>';
					}
					if ($gallerySettings['gallery_set_showcode_htmllink'])
					{
						echo '<tr><td width="30%">', $lang->gallery_set_showcode_htmllink, '</td><td> <input type="text" value="<img src=&#34;' . $gallerySettings['gallery_url'] . $context['gallery_pic']['filename']  . '&#34; />" size="50" /></td></tr>';
					}
					
					echo '</table>';
					
				}		

				// Show edit picture links if allowed

				if ($g_manage)
					echo '&nbsp;<a href="' . $mybb->settings['bburl'] . '/' . $config['admin_dir'] . '/index.php?module=config/ezgallery&action=unapprove&id=' . $context['gallery_pic']['ID_PICTURE'] . '">' . $lang->gallery_text_unapprove . '</a>';
				if ($g_manage || $g_edit_own && $context['gallery_pic']['ID_MEMBER'] == $mybb->user['uid'])
					echo '&nbsp;<a href="ezgallery.php?action=edit&id=' . $context['gallery_pic']['ID_PICTURE']. '">' . $lang->gallery_text_edit . '</a>';
				if ($g_manage || $g_delete_own && $context['gallery_pic']['ID_MEMBER'] == $mybb->user['uid'])
					echo '&nbsp;<a href="ezgallery.php?action=delete&id=' . $context['gallery_pic']['ID_PICTURE'] . '">' . $lang->gallery_text_delete . '</a>';


				// Show report picture link
				if (allowedTo('report'))
				{
					echo '&nbsp;<a href="ezgallery.php?action=report&id=' . $context['gallery_pic']['ID_PICTURE'] . '">' . $lang->gallery_text_reportpicture . '</a>';
				}

				echo '
				</td>
			</tr>';

		// Display who is viewing the picture.
		if (!empty($gallerySettings['gallery_who_viewing']))
		{
			echo '<tr>
			<td align="center" class="trow2"><span class="smalltext">';

			// Show just numbers...?
			// show the actual people viewing the topic?
			echo empty($context['view_members_list']) ? '0 ' . $lang->gallery_who_members : implode(', ', $context['view_members_list']) . (empty($context['view_num_hidden']) || $context['can_moderate_forum'] ? '' : ' (+ ' . $context['view_num_hidden'] . ' ' . $lang->gallery_who_hidden . ')');

			// Now show how many guests are here too.
			echo $lang->who_and, @$context['view_num_guests'], ' ', @$context['view_num_guests'] == 1 ? $lang->guest : $lang->guests, $lang->gallery_who_viewpicture, '</span></td></tr>';
		}

echo '
		</table><br />';
	//Check if allowed to display comments for this picture
	if ($context['gallery_pic']['allowcomments'])
	{
		//Show comments
		echo '<table cellspacing="0" cellpadding="10" border="0" align="center" width="90%" class="tborder">
				<tr class="tcat">
					<td align="center" colspan="2">' . $lang->gallery_text_comments . '</td>
				</tr>';

		if (allowedTo('comment'))
		{
			//Show Add Comment
			echo '
				<tr class="tcat"><td colspan="2">
				<a href="ezgallery.php?action=comment&id=' . $context['gallery_pic']['ID_PICTURE'] . '">' . $lang->gallery_text_addcomment  . '</a></td>
				</tr>';
		}

		// Display all user comments
			$comment_count = $context['gallery_comment_count'];

		foreach($context['gallery_comment_list'] as $row)
		{
			echo '<tr class="trow1">';
			// Display member info
			echo '<td width="10%" valign="top">';
			
			if (empty( $row['ID_MEMBER']))
				echo $lang->gallery_guest;
			else 
				echo '
			<a href="member.php?action=profile&uid=' . $row['ID_MEMBER'] . '">'  . $row['username'] . '</a><br />
			<span class="smalltext">' . $lang->gallery_text_posts .' ' . $row['postnum'] . '</span><br />';
			// Display the users avatar
                 

			if (!empty($row['avatar']))
			{
				echo '<img src="' . $row['avatar'] . '" alt="" />';
			}
			
			
			echo '
			</td>';
			// Display the comment
			echo '<td width="90%"><span class="smalltext">' . my_date($mybb->settings['dateformat'], $row['date']) . '</span><hr />';

			echo  $parser->parse_message($row['comment'], $parser_options);

			// Check if the user is allowed to delete the comment.
			if($g_manage)
				echo '<br /><a href="' . $mybb->settings['bburl'] . '/' . $config['admin_dir'] . '/index.php?module=config/ezgallery&action=delcomment&id=' . $row['ID_COMMENT'] . '">' . $lang->gallery_text_delcomment .'</a>';


			echo '</td>';
			echo '</tr>';
		}



		// Show Add Comment link again if there are more than one comment
		if( allowedTo('comment') && $comment_count != 0)
		{
		 // Show Add Comment
			echo '
				<tr class="tcat">
					<td colspan="2">
					<a href="ezgallery.php?action=comment&id=' . $context['gallery_pic']['ID_PICTURE'] . '">' . $lang->gallery_text_addcomment . '</a>
					</td>
				</tr>';
		}

		echo '</table><br />';
	}
	

	// Link back to the gallery index
	echo '<div align="center"><a href="ezgallery.php">' . $lang->gallery_text_returngallery . '</a></div><br />';



	GalleryCopyright();
	
	gallery_footer();
}



function gallery_header()
{
	global $headerinclude, $context, $header;

	echo parse_page('<html>
<head>
<title>' . $context['page_title'] .'</title>
' . $headerinclude . '
</head>
<body>
' . $header);



}

function gallery_footer()
{
	global $footer;
	
	echo $footer;
	
	echo '</body>
</html>';
}


?>