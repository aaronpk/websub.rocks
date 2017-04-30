<?php
date_default_timezone_set('UTC');

if(getenv('ENV')) {
  require(dirname(__FILE__).'/config.'.getenv('ENV').'.php');
} else {
  require(dirname(__FILE__).'/config.php');
}

p3k\initdb();

function view($template, $data=[]) {
  global $templates;
  return $templates->render($template, $data);
}

function e($text) {
  return htmlspecialchars($text);
}

function is_logged_in() {
  return isset($_SESSION) && array_key_exists('user_id', $_SESSION);
}

function login_required(&$response) {
  return $response->withHeader('Location', '/?login_required')->withStatus(302);
}

function logged_in_user() {
  return ORM::for_table('users')->where('id', $_SESSION['user_id'])->find_one();
}

function validate_url($url) {
  $url = parse_url($url);

  if(!$url) {
    return 'There was an error parsing the URL';
  }

  if(!isset($url['scheme'])) {
    return 'The URL was missing a scheme.';
  }

  if(!in_array($url['scheme'], ['http','https'])) {
    return 'The URL must have a scheme of either http or https.';
  }

  if(!isset($url['host'])) {
    return 'The URL was missing a hostname.';
  }

  $ip=gethostbyname($url['host']);
  if(!$ip || $url['host']==$ip) {
    return 'No DNS entry was found.';
  }

  return false;
}

function result_icon($passed, $id=false) {
  if($passed == 1) {
    return '<span id="'.$id.'" class="ui green circular label">&#x2714;</span>';
  } elseif($passed == -1) {
    return '<span id="'.$id.'" class="ui red circular label">&#x2716;</span>';
  } elseif($passed == 0) {
    return '<span id="'.$id.'" class="ui circular label">&nbsp;</span>';
  } else {
    return '';
  }
}

function streaming_publish($channel, $data) {
  $ch = curl_init(Config::$base . 'streaming/pub?id='.$channel);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
  curl_exec($ch);
}
