<?php
/*
ezGallery Lite
by: vbgamer45
http://www.mybbhacks.com
Copyright 2011-2014  MyBBHacks.com 

############################################
License Information:

Links to http://www.mybbhacks.com must remain unless
branding free option is purchased.
#############################################
*/

function CheckGalleryCategoryExists($cat)
{
	global $db, $lang;
	
	$dbresult2 = $db->query("
		  	SELECT 
		  		COUNT(*) AS total
		  	FROM ".TABLE_PREFIX."gallery_cat
		  	WHERE ID_CAT = $cat ");
	$rowTotal = $db->fetch_array($dbresult2);

	
	if ($rowTotal['total'] == 0)
		fatal_error($lang['gallery_error_category'],false);  	
}

function gallery_format_size($size, $round = 0)
{
    //Size must be bytes!
    $sizes = array('B', 'kB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB');
    for ($i=0; $size > 1024 && $i < count($sizes) - 1; $i++) $size /= 1024;
    return round($size,$round).$sizes[$i];
}

function GetTotalPicturesBYCATID($ID_CAT)
{
	global $db;
	
	$dbresult2 = $db->query("
		  	SELECT 
		  		COUNT(*) AS total
		  	FROM ".TABLE_PREFIX."gallery_pic 
		  	WHERE ID_CAT = ". $ID_CAT. ' AND approved = 1');
	$rowTotal = $db->fetch_array($dbresult2);

		  	
	return $rowTotal['total'];
}

function UnApprovePicture()
{
	global $db, $lang;


	$id = intval($_REQUEST['id']);
	if (empty($id))
		fatal_error($lang['gallery_error_no_pic_selected']);

	// Update the approval
	 $db->query("UPDATE ".TABLE_PREFIX."gallery_pic SET approved = 0 WHERE ID_PICTURE = $id LIMIT 1");

	// Redirect to approval list
	admin_redirect('index.php?module=config/ezgallery&action=approvelist');
}

function ApprovePicture()
{
	global $db, $lang;


	$id = intval($_REQUEST['id']);
	if (empty($id))
		fatal_error($lang['gallery_error_no_pic_selected']);

	// Update the approval
	$db->query("UPDATE ".TABLE_PREFIX."gallery_pic SET approved = 1 WHERE ID_PICTURE = $id LIMIT 1");


	// Redirect to approval list
	admin_redirect('index.php?module=config/ezgallery&action=approvelist');
	

}


function LoadGallerySettings()
{
	global $gallerySettings, $db, $mybb;
	
	if(!$db->table_exists("gallery_settings"))
		return;
	
	$dbresult = $db->query("
		SELECT 
			`variable`, `value`
		FROM ".TABLE_PREFIX."gallery_settings");
		
	$gallerySettings = array();
	while ($row = $db->fetch_array($dbresult))
		$gallerySettings[$row['variable']] = $row['value'];

		
	// Check if the url is empty
	if (empty($gallerySettings['gallery_url']))
		$gallerySettings['gallery_url'] = $mybb->settings['bburl'] . '/gallery/';
		
	// Check if the path is empty
	if (empty($gallerySettings['gallery_path']))
		$gallerySettings['gallery_path'] = MYBB_ROOT . 'gallery/';
		
	if (empty($gallerySettings['gallery_set_images_per_page']))
		$gallerySettings['gallery_set_images_per_page'] = 16;

}

function UpdateGallerySettings($changeArray)
{
	global $db, $gallerySettings;

	if (empty($changeArray) || !is_array($changeArray))
		return;

	$replaceArray = array();
	foreach ($changeArray as $variable => $value)
	{
		// Don't bother if it's already like that ;).
		if (isset($gallerySettings[$variable]) && $gallerySettings[$variable] == stripslashes($value))
			continue;
		// If the variable isn't set, but would only be set to nothing'ness, then don't bother setting it.
		elseif (!isset($gallerySettings[$variable]) && empty($value))
			continue;

		$replaceArray[] = "(SUBSTRING('$variable', 1, 255), SUBSTRING('$value', 1, 65534))";
		$gallerySettings[$variable] = stripslashes($value);
	}

	if (empty($replaceArray))
		return;

	$db->query("
		REPLACE INTO ".TABLE_PREFIX."gallery_settings
			(variable, value)
		VALUES " . implode(',
			', $replaceArray));

	

}

function isAllowedTo($permission = '')
{
	global $mybb, $lang;
	
	$permTitle = 'cannot_ezgallery_' . $permission;
	if (allowedTo($permission) == false)
		fatal_error2($permTitle);
}

function fatal_error2($errorMsg)
{
	global $page, $lang, $context, $errors;
	
	$context['page_title'] = $errorMsg;
	$errors = inline_error($errorMsg);
	gallery_header();
	
	echo $errors;
	
	gallery_footer();
	exit;
}

function allowedTo($permission = '')
{
	global $mybb, $galleryPermissions;
	

    $permRow = $galleryPermissions[$mybb->user['usergroup']];

    // access are : deny, allow
    if ($permission == 'view'        && $permRow['view'])        return $permRow['view'];
    if ($permission == 'add'         && $permRow['add'])         return $permRow['add'];
    if ($permission == 'edit'        && $permRow['edit'])        return $permRow['edit'];
    if ($permission == 'delete'      && $permRow['delete'])      return $permRow['delete'];
    if ($permission == 'comment'     && $permRow['comment'])     return $permRow['comment'];
    if ($permission == 'report'      && $permRow['report'])      return $permRow['report'];        
    if ($permission == 'autoapprove' && $permRow['autoapprove']) return $permRow['autoapprove'];
    if ($permission == 'manage'      && $permRow['manage'])      return $permRow['manage'];    
    
    // Get access for additionnal userGroups
    $additionalGroups = preg_split('/,/', $mybb->user['additionalgroups']);
    foreach ($additionalGroups as $additionalGroup) {
        $permRow = $galleryPermissions[$additionalGroup];
        if ($permission == 'view'        && $permRow['view'])        return $permRow['view'];
        if ($permission == 'add'         && $permRow['add'])         return $permRow['add'];
        if ($permission == 'edit'        && $permRow['edit'])        return $permRow['edit'];
        if ($permission == 'delete'      && $permRow['delete'])      return $permRow['delete'];
        if ($permission == 'comment'     && $permRow['comment'])     return $permRow['comment'];
        if ($permission == 'report'      && $permRow['report'])      return $permRow['report'];        
        if ($permission == 'autoapprove' && $permRow['autoapprove']) return $permRow['autoapprove'];
        if ($permission == 'manage'      && $permRow['manage'])      return $permRow['manage'];
    }
    
    return 0;
}

function GalleryLoadPermissions()
{
	global $db, $galleryPermissions;
	$dbresult = $db->query("
		SELECT 
		p.ID_GROUP,
		  p.view,p.add,p.edit,p.delete,p.comment,p.report,p.autoapprove, p.manage
		FROM ".TABLE_PREFIX."gallery_permissions as p");
	$galleryPermissions = array();
	while ($row = $db->fetch_array($dbresult))
		$galleryPermissions[$row['ID_GROUP']] = $row;


}

?>