<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<title>Event Madness</title>
<style type="text/css">
<!--
body,td,th, p {
	color: #000;
	font-family: arial, halvetica, sans-serif;
}
body {
	background-color: #fff;
}
-->
</style></head>

<body>

<?php
require 'my_facebook.php';
require 'config.php';
$fb = new My_Facebook($AppKey, $AppSecret, $AppId, $FanPageId);

$session = $fb->Facebook->getSession();

$me = null;
// Session based API call.
if ($session) {
  try {
    $uid = $fb->Facebook->getUser();
    $me = $fb->Facebook->api('/me');
  } catch (FacebookApiException $e) {
    error_log($e);
  }
}

// login or logout url will be needed depending on current user state.
if ($me) {
  $logoutUrl = $fb->Facebook->getLogoutUrl();
} else {
  $loginUrl = $fb->Facebook->getLoginUrl();
}

?>
<h1>Creating events </h1>

<?php if (!$me): ?>

     <a href="<?php echo $loginUrl; ?>">
        <img src="http://static.ak.fbcdn.net/rsrc.php/zB6N8/hash/4li2k73z.gif">
      </a>
   
<?php else: ?>
    <a href="<?php echo $logoutUrl; ?>">
      <img src="http://static.ak.fbcdn.net/rsrc.php/z2Y31/hash/cxrz4k7j.gif">
    </a>

<p>Common data</p>

<?php

$Data = array();
$Data ['name'] = 'My Test Event';
$Data ['location'] = 'My test location';
$Data ['start_time'] = '1305768000';

$Data ['street'] = 'My street address';

//$Data ['latitude'] = '-22.12236125482498'; 
//$Data ['longitude'] = '166.36771615820317'; 
$Data ['country'] = 'New Caledonia';

var_dump($Data);

try {
    $event_id = $fb->addPageEvent($Data);
}
catch (Exception $e) {
    echo '<h3>Cannot create event</h3>';    
    var_dump($e->getMessage());
    $event_id=null;
}

if($event_id)
    echo '<a href="https://graph.facebook.com/'.$event_id.'">Event created</a>';


?>

<h1>Adding the city</h1>

<h2>Test one: Païta</h2>

<?php


$Data ['city'] = 'Païta';
$Data ['country'] = 'New Caledonia';

var_dump($Data);

try {
    $event_id = $fb->addProfileEvent($Data);
}
catch (Exception $e) {
    echo '<h3>Cannot create event</h3>';    
    var_dump($e->getMessage());
    $event_id=null;
}

if($event_id)
    echo '<a href="https://graph.facebook.com/'.$event_id.'">Event created</a>';


?>


<h2>Test two: San Francisco</h2>

<?php

unset($Data ['country']);
$Data ['city'] = 'San Francisco';

var_dump($Data);

try {
    $event_id = $fb->addProfileEvent($Data);
}
catch (Exception $e) {
    echo '<h3>Cannot create event</h3>';    
    var_dump($e->getMessage());
    $event_id=null;
}

if($event_id)
    echo '<a href="https://graph.facebook.com/'.$event_id.'">Event created</a>';


?>

<h2>Test three: San Francisco, CA</h2>

<?php

unset($Data ['country']);
$Data ['city'] = 'San Francisco, CA';

var_dump($Data);

try {
    $event_id = $fb->addProfileEvent($Data);
}
catch (Exception $e) {
    echo '<h3>Cannot create event</h3>';    
    var_dump($e->getMessage());
    $event_id=null;
}

if($event_id)
    echo '<a href="https://graph.facebook.com/'.$event_id.'">Event created</a>';


?>
<?php endif; ?>
</body>
</html>
