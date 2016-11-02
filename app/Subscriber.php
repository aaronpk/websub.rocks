<?php
namespace App;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use ORM;
use Config;

class Subscriber {

  public function index(ServerRequestInterface $request, ResponseInterface $response) {
    session_setup();
    
    $response->getBody()->write(view('subscriber/index', [
      'title' => 'PubSub Rocks!',
    ]));
    return $response;
  }
  
}

