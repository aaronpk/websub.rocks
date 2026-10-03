<?php
class Config {
  public static $base = 'http://websubrocks.dev/';
  public static $useragent = '';

  // URLs entered by users are only fetched if they resolve to public addresses,
  // so the site can't be used to reach your private network. List hostnames, IP
  // addresses or CIDR ranges here to allow them anyway. When running locally,
  // include your own host (e.g. 'localhost') so the hub tests can fetch this
  // site's own topic URLs, plus any local services you're testing.
  public static $http_allow = [];

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
