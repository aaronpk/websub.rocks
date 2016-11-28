<?php
class Config {
  public static $base = 'http://websubrocks.dev/';

  public static $redis = 'tcp://127.0.0.1:6379';

  public static $dbhost = '127.0.0.1';
  public static $dbname = 'websubrocks';
  public static $dbuser = 'websubrocks';
  public static $dbpass = 'websubrocks';

  // When set to true, authentication is bypassed, and you can log in by 
  // entering any email you want in the login form. This is useful when developing
  // this or running it locally.
  public static $skipauth = false;

  // Used when an encryption key is needed. Set to something random.
  public static $secret = 'xxxx';

  public static $mailgun = [
    'key' => '',
    'domain' => '',
    'from' => '"websub.rocks" <login@websub.rocks>'
  ];
}
