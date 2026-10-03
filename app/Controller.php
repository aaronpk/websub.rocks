<?php
namespace App;

use Rocks\Http\Request;
use Rocks\Http\Response;
use Rocks\Passkeys;
use Config;

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

}
