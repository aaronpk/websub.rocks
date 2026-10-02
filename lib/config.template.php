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

  // When set to true, a development login form is shown that signs in as any
  // email address entered, bypassing authentication. Use it for running locally
  // where passkeys can't work, which is anywhere except https:// or http://localhost.
  public static $skipauth = false;

  // Used when an encryption key is needed. Set to a long random string.
  public static $secret = 'xxxx';
}
