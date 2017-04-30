<?php
namespace App;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use ORM;
use Config;
use p3k\HTTP;
use p3k;

class Hub {

  public function index(ServerRequestInterface $request, ResponseInterface $response) {
    p3k\session_setup();
    
    $response->getBody()->write(view('hub/index', [
      'title' => 'WebSub Rocks!',
    ]));
    return $response;
  }

  public function get_test(ServerRequestInterface $request, ResponseInterface $response, $args) {
    p3k\session_setup();
    $num = $args['num'];



    $response->getBody()->write(view('hub/'.$num, [
      'title' => 'WebSub Rocks!',
    ]));
    return $response;
  }

}

