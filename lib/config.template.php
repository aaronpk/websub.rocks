<?php
class Config {
  public static $base = 'http://websubrocks.dev/';
  public static $useragent = '';

  public static $redis = 'tcp://127.0.0.1:6379';

  public static $db = [
    'host' => '127.0.0.1',
    'database' => 'websubrocks',
    'username' => 'websubrocks',
    'password' => 'websubrocks',
  ];

  // When set to true, authentication is bypassed, and you can log in by 
  // entering any email you want in the login form. This is useful when developing
  // this or running it locally. Email login links have been removed, so this is
  // currently the only way to sign in.
  public static $skipauth = false;

  // Used when an encryption key is needed. Set to a long random string.
  public static $secret = 'xxxx';
}
