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

  public function dashboard(ServerRequestInterface $request, ResponseInterface $response) {
    p3k\session_setup();

    if(!is_logged_in()) {
      return login_required($response);
    }
    
    $user = logged_in_user();

    $response->getBody()->write(view('dashboard', [
      'title' => 'WebSub Rocks!',
    ]));
    return $response;
  }


}
