<?php
use Rocks\View\Raw;
use Rocks\View\Template;

date_default_timezone_set('UTC');

if(getenv('ENV')) {
  require(dirname(__FILE__).'/config.'.getenv('ENV').'.php');
} else {
  require(dirname(__FILE__).'/config.php');
}

p3k\initdb();

// Session cookies are only ever needed by this site's own pages
ini_set('session.cookie_httponly', '1');
ini_set('session.cookie_samesite', 'Lax');
if(parse_url(Config::$base, PHP_URL_SCHEME) == 'https')
  ini_set('session.cookie_secure', '1');

function templates() {
  static $templates = null;
  if(!$templates)
    $templates = new Template(dirname(__FILE__).'/../views');
  return $templates;
}

// Renders a template on its own, for feeds and partials
function view($template, $data=[]) {
  return templates()->render($template, $data);
}

// Renders a page template inside the shared layout
function page($template, $data=[]) {
  return templates()->render('layout', [
    'title' => $data['title'] ?? 'WebSub Rocks!',
    'link_tag' => new Raw((string)($data['link_tag'] ?? '')),
    'content' => new Raw(view($template, $data)),
  ]);
}

// Quotes are stored with HTML entities already encoded, so they must not be escaped again
function raw_posts($posts) {
  return array_map(function($post){
    $post['content'] = new Raw((string)$post['content']);
    $post['author'] = new Raw((string)$post['author']);
    return $post;
  }, $posts);
}

// php-jwt requires HMAC keys of at least 256 bits, so derive one from the configured secret
function jwt_key() {
  return hash('sha256', Config::$secret, true);
}

function e($text) {
  return htmlspecialchars((string)$text);
}

function is_logged_in() {
  return isset($_SESSION) && array_key_exists('user_id', $_SESSION);
}

function logged_in_user() {
  return ORM::for_table('users')->where('id', $_SESSION['user_id'])->find_one();
}

// p3k\session_setup() warns if a session is already active, which happens
// when one controller renders another's output in the same request
function session_setup($create=false) {
  if(session_status() != PHP_SESSION_ACTIVE)
    p3k\session_setup($create);
}

function log_in($user) {
  session_setup(true);
  // A new session id on login, so one planted before signing in is useless afterwards
  session_regenerate_id(true);
  $user->last_login = date('Y-m-d H:i:s');
  $user->save();
  $_SESSION['user_id'] = $user->id;
  $_SESSION['email'] = $user->email;
  $_SESSION['login'] = 'success';
}

// A per-session token that state-changing requests from logged-in pages must echo back
function csrf_token() {
  if(empty($_SESSION['csrf']))
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
  return $_SESSION['csrf'];
}

function csrf_valid($request) {
  $sent = $request->header('X-CSRF-Token') ?? $request->post('csrf') ?? '';
  return !empty($_SESSION['csrf']) && hash_equals($_SESSION['csrf'], $sent);
}

// HTTP client for fetching URLs that users give us. Safe mode refuses
// private, loopback and other non-public addresses, including on every
// redirect hop, except for hosts listed in Config::$http_allow.
function http_client($timeout=null) {
  $http = new p3k\HTTP(Config::$useragent);
  $http->set_safe_mode(true, Config::$http_allow ?? []);
  if($timeout)
    $http->set_timeout($timeout);
  return $http;
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
