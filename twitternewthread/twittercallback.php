<?php

define("IN_MYBB", 1);

require_once "./global.php";
require_once(MYBB_ROOT. 'inc/twitteroauth.php');
global $db, $mybb;
rebuild_settings();
if ($_REQUEST['sa'] == 'twittersignin')
{

/* Build TwitterOAuth object with client credentials. */
$connection = new TwitterOAuth($mybb->settings['con_key'], $mybb->settings['con_secret']);
 
/* Get temporary credentials. */
$request_token = $connection->getRequestToken($mybb->settings['bburl'] . '/twittercallback.php');

/* Save temporary credentials to session. */
$token = $request_token['oauth_token'];

 
/* If last connection failed don't display authorization link. */
switch ($connection->http_code) {
  case 200:
    /* Build authorize URL and redirect user to Twitter. */
   // updateSettings(array('oauth_token' => $token, 'oauth_token_secret' => $request_token['oauth_token_secret']));

    $db->update_query("settings", array('value' => $token), "name='oauth_token'");
  	$db->update_query("settings", array('value' => $request_token['oauth_token_secret']), "name='oauth_token_secret'");
   rebuild_settings();
    
    $url = $connection->getAuthorizeURL($token);
    header('Location: ' . $url); 
    exit;
    break;
  default:
  	 
  	  $db->update_query("settings", array('value' => ''), "name='oauth_token'");
  	  $db->update_query("settings", array('value' => ''), "name='oauth_token_secret'");
  	  rebuild_settings();
  	  


    /* Show notification if something went wrong. */
    die('Could not connect to Twitter. Refresh the page or try again later.');
}
	exit;
}

/* If the oauth_token is old redirect to the connect page. */
if (isset($_REQUEST['oauth_token']) && $mybb->settings['oauth_token'] !== $_REQUEST['oauth_token']) {
  die("No Twitter information passed");
}

/* Create TwitteroAuth object with app key/secret and token key/secret from default phase */
$connection = new TwitterOAuth($mybb->settings['con_key'], $mybb->settings['con_secret'], $mybb->settings['oauth_token'], $mybb->settings['oauth_token_secret']);

/* Request access tokens from twitter */
$access_token = $connection->getAccessToken($_REQUEST['oauth_verifier']);

/* Save the access tokens. Normally these would be saved in a database for future use. */

/* If HTTP response is 200 continue otherwise send to connect page to retry */
if (200 == $connection->http_code) {
  /* The user has been verified and the access tokens can be saved for future use */
   //updateSettings(array('oauth_token' => $access_token['oauth_token'], 'oauth_token_secret' => $access_token['oauth_token_secret']));

 	$db->update_query("settings", array('value' => $access_token['oauth_token']), "name='oauth_token'");
  	$db->update_query("settings", array('value' => $access_token['oauth_token_secret']), "name='oauth_token_secret'");
   rebuild_settings();
   
  header('Location: ' . $mybb->settings['bburl']);
} else {
	
	 $db->update_query("settings", array('value' => ''), "name='oauth_token'");
  	  $db->update_query("settings", array('value' => ''), "name='oauth_token_secret'");
  	  rebuild_settings();

  die("Erorr occured please retry");
}

?>