<?php
define("IN_MYBB", 1);
define('THIS_SCRIPT', 'eztrader.php');

require_once "./global.php";
require_once MYBB_ROOT."inc/eztrader.lib.php";
require_once MYBB_ROOT."inc/class_parser.php";
$parser = new postParser;
$parser_options = array(
			"allow_html" => 0,
			"allow_mycode" => 1,
			"allow_smilies" => 1,
			"allow_imgcode" => 1,
			"filter_badwords" => 1
		);

$lang->load("eztrader");

add_breadcrumb($lang->eztrader_text_title, "eztrader.php");

// Load Trader Settings
LoadTraderSettings();
// Load Trader Permissions
TraderLoadPermissions();

	// Trader Actions
	$subActions = array(
	
		'report' => 'ezTrader_Report',
		'report2' => 'ezTrader_Report2',
		'submit' => 'ezTrader_Submit',
		'detail' => 'ezTrader_ViewDetail',
		'delete' => 'ezTrader_Delete',
		'delete2' => 'ezTrader_Delete2',
		'submit2' => 'ezTrader_Submit2',
	);
	
	@$sa = $mybb->input['sa'];
	if (!empty($subActions[$sa]))
		$subActions[$sa]();
	else
		ezTrader_mainview();
	

function ezTrader_mainview()
{
	global $mybb;
	
	global $context, $lang, $db, $scripturl, $traderSettings;

	@$memid = (int) $_REQUEST['id'];

	if (empty($memid))
		ezTrader_fatal_error2($lang->eztrader_nomemberselected, false);

	$request = $db->query("
	SELECT 
		username FROM " . TABLE_PREFIX."users
	WHERE uid = $memid LIMIT 1");
	$row = $db->fetch_array($request);

	$mcount = $db->num_rows($request);

	if ($mcount != 1)
		ezTrader_fatal_error2($lang->eztrader_nomemberselected, false);

	$context['traderid'] = $memid;
	$context['tradername'] = $row['username'];
	$context['page_title'] = $lang->eztrader_feedbacktitle . ' - ' . $row['username'];

	$request = $db->query("
	SELECT 
		feedbackid 
	FROM " . TABLE_PREFIX."trader_feedback 
	WHERE approved = 1 AND ID_MEMBER =" . $context['traderid']);
	$context['tradecount'] = $db->num_rows($request);
	
	
	$result = $db->query("
	SELECT 
		COUNT(*) AS total,salevalue 
	FROM " . TABLE_PREFIX."trader_feedback 
	WHERE approved = 1 AND ID_MEMBER = " . $context['traderid'] . " GROUP BY salevalue" );
	$context['neturalcount'] = 0;
	$context['pcount'] = 0;
	$context['ncount'] = 0;
	while($row = $db->fetch_array($result))
	{
		if ($row['salevalue'] == 0)
		{
			$context['pcount'] = $row['total'];
		}
		else if ($row['salevalue'] == 2)
		{
			$context['ncount'] = $row['total'];
		}	
		else if ($row['salevalue'] == 1)
		{
			$context['neturalcount'] = $row['total'];
		}
		
	}
	$db->free_result($result);
	
	$context['tradecount_nonetural'] = ($context['pcount'] +  $context['ncount']);
	
	// Get the view type
	@$view = (int) $_GET['view'];
	if (empty($view))
			$view = 0;
	
	
			$queryextra = '';
			switch($view)
			{
				case 0:
				$queryextra = '';
				break;
				case 1:
				$queryextra = ' AND f.saletype = 1';
			
				break;
				case 2:
				$queryextra = ' AND f.saletype = 0';
				break;
				case 3:
				$queryextra = ' AND f.saletype = 2';

				break;
				default:
				ezTrader_fatal_error2($lang->eztrader_invalidview, false);
				break;
			}

			
	$context['start'] = (int) $_REQUEST['start'];
	
	
	$dbresult = $db->query("
	SELECT 
		COUNT(*) AS total 
	FROM (" . TABLE_PREFIX."trader_feedback AS f)
	LEFT JOIN " . TABLE_PREFIX."users AS m ON (f.FeedBackMEMBER_ID = m.uid) 
	WHERE f.ID_MEMBER = " . $context['traderid'] . "  AND f.approved = 1 $queryextra ");
	$row = $db->fetch_array($dbresult);
	$total = $row['total'];
	$db->free_result($dbresult);
	
	$selectClassifiedsSQL = '';
	$leftJoinClassifieds = '';
	
		$perpage = $traderSettings['trader_feedbackperpage'];
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
			
	$dbresult = $db->query("
	SELECT 
			f.saletype, f.feedbackid, f.FeedBackMEMBER_ID,  f.topicurl, 
			f.comment_short, f.salevalue, f.saledate, m.username $selectClassifiedsSQL
	FROM (" . TABLE_PREFIX."trader_feedback AS f) 
	LEFT JOIN " . TABLE_PREFIX."users AS m ON (f.FeedBackMEMBER_ID = m.uid) 
	$leftJoinClassifieds
	WHERE f.ID_MEMBER = " . $context['traderid'] . "  AND f.approved = 1 $queryextra ORDER BY f.feedbackid DESC LIMIT $start,$perpage");
	$context['trader_feedback'] = array();
	while ($row = $db->fetch_array($dbresult))
	{
		$context['trader_feedback'][] = $row;
	}
	$db->free_result($dbresult);
	

	$pagingList = multipage($total, $perpage, $page, "eztrader.php?id='" . $context['traderid'] . "view=$view");

	// Set the page title
	
	eztrader_header();
	
	$neturalcount = $context['neturalcount'];
	$pcount = $context['pcount'];
	$ncount = $context['ncount'];

	if ($context['tradecount'] != 0)
		$feedpos =  round(($pcount / $context['tradecount_nonetural']) * 100, 2);
	else
		$feedpos = 0;
		
		
	if ($traderSettings['trader_use_pos_neg'])
		$context['tradecount'] = ($context['pcount'] -$context['ncount']);


echo '
<div class="tborder">
<table border="0" cellpadding="4" cellspacing="1" align="center" class="bordercolor" width="100%">
	<tr class="titlebg">
		<td >' . $lang->eztrader_feedbacktitle . ' - ' . $context['tradername']  . '</td>
	</tr>
	<tr>
		<td class="trow">
			<table border="0" cellspacing="0" cellpadding="2" width="100%">
				<tr>
					<td><b>', $lang->eztrader_title, '</b></td>
					<td><b>', $lang->eztrader_contact, '</b></td>
				</tr>
				<tr>
					<td><b>' . $lang->eztrader_profile . '(' . ($traderSettings['trader_use_pos_neg'] ? ($context['tradecount'] > 0 ? '+' . $context['tradecount'] : $context['tradecount']) : $context['tradecount']) . ')</b></td>
					<td><a href="member.php?action=profile&uid=' . $context['traderid'] . '">' . $lang->eztrader_viewprofile . '</a></td>
				</tr>
				<tr>
					<td><b>' . $lang->eztrader_positivefeedbackpercent  .   $feedpos . '%</b></td>
					<td><a href="private.php?action=send&uid=' . $context['traderid'] . '">' . $lang->eztrader_sendpm . '</a></td>
				</tr>
				<tr><td>' . $lang->eztrader_positivefeedback  . $pcount . '&nbsp;<img src="' . $mybb->settings['bburl'] . '/images/smilies/smile.gif" alt="positive" /></td></tr>
				<tr><td>' . $lang->eztrader_neutralfeedback  .  $neturalcount . '&nbsp;<img src="' . $mybb->settings['bburl'] . '/images/smilies/undecided.gif" alt="netural" /></td></tr>
				<tr><td>' . $lang->eztrader_negativefeedback   .  $ncount . '&nbsp;<img src="' . $mybb->settings['bburl'] . '/images/smilies/angry.gif" alt="negative" /></td></tr>
				<tr><td>' . $lang->eztrader_totalfeedback  .  ($pcount - $ncount) . '</td></tr>
				<tr><td colspan="2"><br /><a href="eztrader.php?sa=submit&id=' . $context['traderid']  . '">' . $lang->eztrader_submitfeedback . $context['tradername']  . '</a></td>
				</tr>
			</table>
			<hr />
			<table border="0" cellspacing="0" cellpadding="2" width="100%">
				<tr>
					<td>
				<a href="eztrader.php?id=' . $context['traderid']  . '">' . $lang->eztrader_allfeedback . '</a>&nbsp;|&nbsp;<a href="eztrader.php?id=' . $context['traderid']  . '&view=1">' . $lang->eztrader_sellerfeedback . '</a>&nbsp;|&nbsp;<a href="eztrader.php?id=' . $context['traderid']  . '&view=2">' .  $lang->eztrader_buyerfeedback . '</a>&nbsp;|&nbsp;<a href="eztrader.php?id=' . $context['traderid']  . '&view=3">' . $lang->eztrader_tradefeedback . '</a>
					</td>
				</tr>
			</table>
			<hr />
			<table border="0" cellspacing="0" cellpadding="2" width="100%">
			<tr>
				<td class="catbg2" width="5%">',$lang->eztrader_rating,'</td>
				<td class="catbg2" width="55%">',$lang->eztrader_comment,'</td>
				<td class="catbg2" width="10%">',$lang->eztrader_from,'</td>
				<td class="catbg2" width="10%">',$lang->eztrader_detail,'</td>
				<td class="catbg2" width="10%">',$lang->eztrader_date,'</td>
				<td class="catbg2" width="10%">',$lang->eztrader_report,'</td>
			</tr>
			';

			// Check if allowed to delete comment
			$deletefeedback = ezTrader_allowedTo('deletefeed');

			$styleclass = 'trow';
			
			foreach ($context['trader_feedback'] as $row)
			{
				echo '<tr class="',$styleclass,'">';

				switch($row['salevalue'])
				{

					case 0:
					echo '<td align="center"><img src="' . $mybb->settings['bburl'] . '/images/smilies/smile.gif" alt="positive" /></td>';

					break;
					case 1:
					echo '<td align="center"><img src="' . $mybb->settings['bburl'] . '/images/smilies/undecided.gif" alt="netural" /></td>';
					break;
					case 2:
					echo '<td align="center"><img src="' . $mybb->settings['bburl'] . '/images/smilies/angry.gif" alt="negative" /></td>';
					break;
					default:
					echo '<td align="center">', $row['salevalue'], '</td>';
					break;
				}



					if ($row['topicurl'] == '')
					{
						echo '<td>', $row['comment_short'], '</td>';
					}
					else 
						echo '<td><a href="',$row['topicurl'],'">' . $row['comment_short'], '</a></td>';
			
					
			
					
				$mtype = ' ';
				switch($row['saletype'])
				{
					case 0:
						$mtype = $lang->eztrader_buyer;
					break;
					case 1:
						$mtype = $lang->eztrader_seller;
					break;
					case 2:
						$mtype = $lang->eztrader_trade;
					break;
					default:
					$mtype = '';
					break;
				}

				if (!empty($row['username']))
					echo '<td>'. $mtype . '&nbsp;<a href="member.php?action=profile&uid=' . $row['FeedBackMEMBER_ID'] . '">' . $row['username'] . '</a></td>';
				else 
					echo '<td>'. $mtype . '&nbsp;' . $lang->eztrader_guest . '</td>';
				
				echo '<td><a href="eztrader.php?sa=detail&feedid=' . $row['feedbackid'] . '">',$lang->eztrader_viewdetail,'</a></td>';
				echo '<td>', my_date($mybb->settings['dateformat'], $row['saledate']), '</td>';
				echo '<td><a href="eztrader.php?sa=report&feedid=' . $row['feedbackid'] .  '">',$lang->eztrader_report,'</a>';
				if($deletefeedback)
				{
					echo '<br /><br /><a href="eztrader.php?sa=delete&feedid=' . $row['feedbackid'] .  '">',$lang->eztrader_delete,'</a>';
				}

				echo '</td>';
				echo '</tr>';
				
				
				if ($styleclass == 'trow')
					$styleclass = 'trow2';
				else 
					$styleclass = 'trow';
				
			}
			

echo '	

<tr class="titlebg">
					<td align="left" colspan="6">
					' . $pagingList . '
					</td>
				</tr>
</table>
		</td>
	</tr>
</table>
</div>';
eztraderCopyright();
	eztrader_footer();
	
}



function eztraderCopyright()
{
	echo '<div align="center">Powered by: <a href="http://www.mybbhacks.com" target="blank">ezTrader</a> by <a href="http://www.createaforum.com" title="Forum Hosting">CreateAForum.com</a></div>';
}


function eztrader_header()
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

function eztrader_footer()
{
	global $footer;
	
	echo $footer;
	
	echo '</body>
</html>';
}


function ezTrader_Delete()
{
	global $context, $db, $lang, $parser, $parser_options;

	// Check if they are allowed to delete feedback
	ezTrader_isAllowedTo('deletefeed');

	@$feedid = (int) $_REQUEST['feedid'];
	if (empty($feedid))
		ezTrader_fatal_error2($lang->eztrader_errnofeedselected, false);

	$context['feedid'] = $feedid;

	$context['page_title'] = $lang->eztrader_title . ' - ' . $lang->eztrader_deletefeedback;

	$result = "SELECT f.saletype, f.feedbackid, f.ID_MEMBER, f.FeedBackMEMBER_ID, 
	f.comment_short,  f.topicurl, f.comment_long, f.salevalue, f.saledate, m.username 
	FROM " . TABLE_PREFIX."trader_feedback AS f," . TABLE_PREFIX."users AS m 
	WHERE f.feedbackid = " . $context['feedid'] . " AND f.FeedBackMEMBER_ID = m.uid";

	$dbresult = $db->query($result);
	$row = $db->fetch_array($dbresult);
	$db->free_result($dbresult);
	
	$context['trader_feedback'] = $row;
	eztrader_header();
echo '
<div class="tborder">
<form action="eztrader.php?sa=delete2" method="post">
<table border="0" cellpadding="4" cellspacing="1" align="center" class="bordercolor" width="100%">
	<tr class="titlebg">
		<td  align="center">',$lang->eztrader_title,' - ',$lang->eztrader_deletefeedback,'</td>
	</tr>
	<tr>
		<td class="trow">
			<table border="0" cellspacing="0" cellpadding="0" width="100%">
				<tr>
					<td width="25%" valign="top">' . $lang->eztrader_shortcomment_small . '</td>
					<td align="left">' . $parser->parse_message($context['trader_feedback']['comment_short'],$parser_options ) . '</td>

				</tr>
				<tr>
					<td width="25%" valign="top">',$lang->eztrader_detailedcomment,'</td>
					<td align="left">' . $parser->parse_message($context['trader_feedback']['comment_long'], $parser_options ) . '<br />',$lang->eztrader_commentby,'<a href="member.php?action=profile&uid=' . $context['trader_feedback']['FeedBackMEMBER_ID'] . '">' . $context['trader_feedback']['username'] .  '</a><br /></td>

				</tr>
				<tr>
					<td colspan="2" align="center"><input type="submit" value="',$lang->eztrader_deletefeedback,'" name="cmdsubmit" /></td>
				</tr>
			</table>
			<input type="hidden" name="feedid" value="' . $context['feedid'] . '" />
			<input type="hidden" name="redirect" value="' . $context['trader_feedback']['ID_MEMBER'] . '" />

		</td>
	</tr>
</table>
</form>
</div>';
eztraderCopyright();
eztrader_footer();
	
	
}

function ezTrader_Delete2()
{
	global $lang;

	// Check if they are allowed to delete feedback
	ezTrader_isAllowedTo('deletefeed');
	
	@$feedid = (int) $_REQUEST['feedid'];
	if (empty($feedid))
		ezTrader_fatal_error2($lang->eztrader_errnofeedselected,false);

	@$redirectid = (int) $_REQUEST['redirect'];
	if (empty($redirectid))
		ezTrader_fatal_error2($lang->eztrader_notrader, false);

	ezTrader_DeleteByID($feedid);


	redirect('eztrader.php?id=' . $redirectid);
}


function ezTrader_Report()
{
	global $context, $lang, $mybb;


	@$feedid = (int) $_GET['feedid'];
	if (empty($feedid))
		ezTrader_fatal_error2($lang->eztrader_errnofeedselected, false);

	$context['feedid'] = $feedid;

	
	$context['page_title'] = $lang->eztrader_reporttitle;
	eztrader_header();
echo '
<div class="tborder">
<table border="0" cellpadding="4" cellspacing="1" align="center" class="bordercolor" width="100%">
	<tr class="titlebg">
		<td  align="center">' . $lang->eztrader_reporttitle . '</td>
	</tr>
	<tr>
		<td class="trow">
			<form action="eztrader.php?sa=report2" method="post">
			<table border="0" cellspacing="0" cellpadding="0" width="100%">
				<tr>
					<td width="25%" valign="top" align="right">', $lang->eztrader_comment,'</td>
					<td align="left"><textarea rows="10" name="comment" cols="64"></textarea></td>

				</tr>
				<tr>
					<td colspan="2" align="center"><br />
						<input type="submit" value="', $lang->eztrader_reportfeedback,'" name="cmdsubmit" />
					</td>
				</tr>
			</table>
			<input type="hidden" name="feedid" value="' . $context['feedid'] . '" />
			<input type="hidden" name="my_post_key" value="' .  htmlspecialchars_uni($mybb->post_code) . '" />
			</form>
		</td>
	</tr>
</table>
</div>';
eztraderCopyright();
eztrader_footer();

}

function ezTrader_Report2()
{
	global $db, $mybb, $lang;

	@$comment = htmlspecialchars($_REQUEST['comment'],ENT_QUOTES);
	
	// Verify incoming POST request
	verify_post_check($mybb->get_input('my_post_key'));

	if ($comment == '')
		ezTrader_fatal_error2($lang->eztrader_errnocomment, false);

	// Add link to trader to comment field.
	@$feedid = (int) $_REQUEST['feedid'];

	$result = "
	SELECT f.saletype, f.feedbackid, f.ID_MEMBER, f.FeedBackMEMBER_ID, f.comment_short,
	f.topicurl, f.comment_long, f.salevalue, f.saledate, m.username
	FROM " . TABLE_PREFIX."trader_feedback AS f," . TABLE_PREFIX."users AS m
	WHERE f.feedbackid = $feedid AND f.FeedBackMEMBER_ID = m.uid";

	$dbresult = $db->query($result);
	$row = $db->fetch_array($dbresult);
	$db->free_result($dbresult);
	$comment .= "\n"  . $lang->eztrader_commentmadeby . '[url=' . $mybb->settings['bburl'] . '/member.php?action=profile&uid=' . $row['FeedBackMEMBER_ID'] . ']' . $row['username'] . '[/url]';
	$comment .= "\n\n" . '[url=' . $mybb->settings['bburl'] . '/eztrader.php?id=' . $row['ID_MEMBER'] . ']' . $lang->eztrader_viewtrader . '[/url]';


	$comment .= "\n\n" . $lang->eztrader_title;
	
	require_once MYBB_ROOT."inc/datahandlers/pm.php";
	$dbresult = $db->query("
	SELECT 
		m.uid
	FROM " . TABLE_PREFIX."usergroups AS g," . TABLE_PREFIX."users AS m 
	WHERE m.usergroup = g.gid AND m.usergroup = 4");
	while($row2 = $db->fetch_array($dbresult))
	{
		
		$pmhandler = new PMDataHandler();
	
		$pm = array(
			'subject' => $lang->eztrader_title . ' ' . $lang->eztrader_badfeedback,
			'message' => $comment,
			'icon' => '',
			'toid' => array($row2['uid']),
			'fromid' => $mybb->user['uid'],
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
	$db->free_result($dbresult);


	redirect('eztrader.php?id=' . $row['ID_MEMBER'] );
}

function ezTrader_ViewDetail()
{
	global $context, $db, $lang, $parser, $parser_options;

	@$feedid = (int) $_REQUEST['feedid'];
	if (empty($feedid))
		ezTrader_fatal_error2($lang->eztrader_errnofeedselected, false);

	$context['page_title'] = $lang->eztrader_title . ' - ' . $lang->eztrader_detailedfeedback;
	$context['feedid'] = $feedid;
	eztrader_header();

	$result = "
	SELECT 
	f.saletype, f.feedbackid, f.ID_MEMBER, f.FeedBackMEMBER_ID, f.topicurl, 
	f.comment_long, f.salevalue, f.saledate, m.username
	FROM " . TABLE_PREFIX."trader_feedback AS f," . TABLE_PREFIX."users AS m 
	WHERE f.feedbackid = " . $context['feedid'] . " AND f.FeedBackMEMBER_ID = m.uid";

	$dbresult = $db->query($result);
	$row = $db->fetch_array($dbresult);
	$db->free_result($dbresult);
	
	$context['trading_detail'] = $row;
	

echo '
<div class="tborder">
<table border="0" cellpadding="4" cellspacing="1" align="center" class="bordercolor" width="100%">
	<tr class="titlebg">
		<td  align="center">',$lang->eztrader_title,' - ',$lang->eztrader_detailedfeedback,'</td>
	</tr>
	<tr>
		<td class="trow">
			<table border="0" cellspacing="0" cellpadding="0" width="100%">
				<tr>
					<td width="25%" valign="top">',$lang->eztrader_detailedcomment,'</td>
					<td align="left">' . $parser->parse_message($context['trading_detail']['comment_long'],$parser_options ) . '<br />',$lang->eztrader_commentby,'<a href="member.php?action=profile&uid=', $context['trading_detail']['FeedBackMEMBER_ID'],'">',$context['trading_detail']['username'],  '</a><br /></td>

				</tr>
				<tr>
					<td colspan="2" align="center"><a href="eztrader.php?id=', $context['trading_detail']['ID_MEMBER'], '">',$lang->eztrader_returntoratings,'</a></td>
				</tr>
			</table>
		</td>
	</tr>
</table>
</div>';
 eztraderCopyright();
eztrader_footer();
	
}

function ezTrader_Submit()
{
	global $context, $lang, $db, $mybb;


	// Check if they are allowed to submit feedback
	ezTrader_isAllowedTo('feedback');


	@$memid = (int) $_GET['id'];

	if (empty($memid))
		ezTrader_fatal_error2($lang->eztrader_nomemberselected,false);

	$request = $db->query("
	SELECT 
		username FROM " . TABLE_PREFIX."users
	WHERE uid = $memid LIMIT 1");
	$row = $db->fetch_array($request);

	$mcount = $db->num_rows($request);

	if ($mcount != 1)
		ezTrader_fatal_error2($lang->eztrader_nomemberselected,false);

	if ($mybb->user['uid'] == $memid)
		ezTrader_fatal_error2($lang->eztrader_errfeedbackself ,false);
		
	$context['traderid'] = $memid;
	$context['tradername'] = $row['username'];


	$context['page_title'] = $lang->eztrader_submittitle . ' - ' . $row['username'];
	eztrader_header();	

	
echo '
<div class="tborder">
<table border="0" cellpadding="4" cellspacing="1" align="center" class="bordercolor" width="100%">
	<tr class="titlebg">
		<td  align="center">' . $lang->eztrader_submittitle . ' - ' . $context['tradername']  . '</td>
	</tr>
	<tr>
		<td class="trow">
			<form action="eztrader.php?sa=submit2" method="post">
			<table border="0" cellspacing="0" cellpadding="0" width="100%">

				<tr>
					<td width="20%"><b>' . $lang->eztrader_whoareu . '</b></td>
					<td align="left">
						  <select size="1" name="saletype">
						  <option value="0" selected="selected">' . $lang->eztrader_buyer . '</option>
						  <option value="1">' . $lang->eztrader_seller . '</option>
						  <option value="2">' . $lang->eztrader_trade . '</option>
						  </select>
					</td>
				</tr>
				<tr>
					<td width="25%">' . $lang->eztrader_transaction . '</td>
					<td align="left">
						  <select size="1" name="salevalue">
						  <option value="0" selected="selected">' . $lang->eztrader_positive . '</option>
						  <option value="1">' . $lang->eztrader_neutral . '</option>
						  <option value="2">' . $lang->eztrader_negative . '</option>
						  </select>
					</td>
				</tr>
				<tr>
					<td width="25%">' . $lang->eztrader_shortcomment . '</td>
					<td align="left"><input type="text" max="100" name="shortcomment" size="75" />
					<br />' . $lang->eztrader_shortcommentnote . '
					</td>
				</tr>';


echo '
				<tr>
					<td width="25%">' . $lang->eztrader_topicurl . '</td>
					<td align="left"><input type="text" name="topicurl"  size="75" /></td>
				</tr>
				<tr>
					<td width="25%" valign="top">' . $lang->eztrader_longcomment . '</td>
					<td align="left"><textarea rows="10" name="longcomment" cols="64"></textarea></td>

				</tr>
				<tr>
					<td colspan="2" align="center"><br />
						<input type="submit" value="',$lang->eztrader_submitfeedback2,'" name="cmdsubmit" />
					</td>
				</tr>
			</table>
			<input type="hidden" name="id" value="' . $context['traderid'] . '" />
			<input type="hidden" name="my_post_key" value="' .  htmlspecialchars_uni($mybb->post_code) . '" />
			</form>
		</td>
	</tr>
</table>
</div>';
	eztrader_footer();	
}

function ezTrader_Submit2()
{
	global $db, $lang, $traderSettings, $mybb;


	// Check if they are allowed to submit feedback
	ezTrader_isAllowedTo('feedback');
	
	// Verify incoming POST request
	verify_post_check($mybb->get_input('my_post_key'));

	// Get the trader id
	$traderid = (int) $_REQUEST['id'];

	if ($mybb->user['uid'] == $traderid)
		ezTrader_fatal_error2($lang->eztrader_errfeedbackself ,false);

	// Check if comment posted
	$shortcomment = htmlspecialchars(substr($_REQUEST['shortcomment'], 0, 100),ENT_QUOTES);

	if ($shortcomment == '')
		ezTrader_fatal_error2($lang->eztrader_errshortcoment,false);


	$topicurl = htmlspecialchars($_REQUEST['topicurl'],ENT_QUOTES);
	$salevalue = (int) $_REQUEST['salevalue'];
	$saletype = (int) $_REQUEST['saletype'];
	$longcomment = htmlspecialchars($_REQUEST['longcomment'],ENT_QUOTES);
	switch($saletype)
	{
		case 0:
		break;

		case 1:
		break;

		case 2:

		break;

		default:
		ezTrader_fatal_error2($lang->eztrader_errsaletype,false);
		break;
	}
	switch($salevalue)
	{
		case 0:
		break;

		case 1:
		break;

		case 2:
		break;

		default:
		ezTrader_fatal_error2($lang->eztrader_errsalevalue,false);
		break;
	}

	// Get the date
	$tradedate = time();

	
	
	// Get the approval
	if ($traderSettings['trader_approval'] == 1)
	{
		$approval = (ezTrader_allowedTo('autorating') ? 1 : 0);
	}
	else 
		$approval = 1;
		

	
	// Finally Insert it into the db
	$db->query("INSERT INTO " . TABLE_PREFIX."trader_feedback
			(ID_MEMBER, comment_short, comment_long, topicurl, saletype, salevalue,
			 saledate, FeedBackMEMBER_ID, approved)
		VALUES ($traderid, '$shortcomment', '$longcomment', '$topicurl',$saletype,
		 $salevalue, $tradedate, " . $mybb->user['uid'] . ",$approval)");

	$id = $db->insert_id();
	
	
	if ($approval == 1)
	{
		ezTrader_SendTraderPMByID($id);
		redirect('eztrader.php?id=' . $traderid);
	}
	else
		ezTrader_fatal_error2($lang->eztrader_form_notapproved, false);

}

?>