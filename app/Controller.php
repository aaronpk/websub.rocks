<?php
namespace App;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use ORM;
use IndieAuth;
use Config;
use p3k;

class Controller {

  private function _redirectURI() {
    return Config::$base.'endpoints/callback';
  }

  public function index(ServerRequestInterface $request, ResponseInterface $response) {
    p3k\session_setup();

    $response->getBody()->write(view('index', [
      'title' => 'WebSub Rocks!',
    ]));
    return $response;
  }

  public function implementation_reports(ServerRequestInterface $request, ResponseInterface $response) {
    return $response->withHeader('Location', 'https://github.com/w3c/websub/tree/master/implementation-reports')->withStatus(302);
  }

  public function clean_logins(ServerRequestInterface $request, ResponseInterface $response) {

    // Delete users who never logged in older than 7 days ago

    $count = ORM::for_table('users')
      ->where_lt('auth_code_exp', date('Y-m-d H:i:s', strtotime('-7 days')))
      ->where_null('last_login')
      ->count();

    ORM::for_table('users')
      ->where_lt('auth_code_exp', date('Y-m-d H:i:s', strtotime('-7 days')))
      ->where_null('last_login')
      ->delete_many();

    $response->getBody()->write('Deleted '.$count.' logins');
    return $response->withHeader('Content-type', 'text/plain');
  }

}
