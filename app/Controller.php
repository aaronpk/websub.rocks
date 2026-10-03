<?php
namespace App;

use Rocks\Http\Request;
use Rocks\Http\Response;
use Rocks\Passkeys;
use ORM;
use Config;
use p3k;

class Controller {

  private function _redirectURI() {
    return Config::$base.'endpoints/callback';
  }

  public function index(Request $request) {
    session_setup();

    return Response::make(200, page('index', [
      'title' => 'WebSub Rocks!',
      'passkeys_available' => Passkeys::available(),
      'skipauth' => Config::$skipauth,
    ]));
  }

  public function implementation_reports(Request $request) {
    return Response::redirect('https://github.com/w3c/websub/tree/master/implementation-reports');
  }

  public function clean_logins(Request $request) {
    // Delete users who never logged in older than 7 days ago

    $count = ORM::for_table('users')
      ->where_lt('auth_code_exp', date('Y-m-d H:i:s', strtotime('-7 days')))
      ->where_null('last_login')
      ->count();

    ORM::for_table('users')
      ->where_lt('auth_code_exp', date('Y-m-d H:i:s', strtotime('-7 days')))
      ->where_null('last_login')
      ->delete_many();

    return Response::make(200, 'Deleted '.$count.' logins', ['Content-Type' => 'text/plain']);
  }

}
