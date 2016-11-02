<?php
namespace App;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Diactoros\Response\JsonResponse;
use ORM, Config;
use DOMXPath;
use Rocks\HTTP;

class Publisher {

  public $client;

  public function index(ServerRequestInterface $request, ResponseInterface $response) {
    session_setup();
    
    $response->getBody()->write(view('publisher/index', [
      'title' => 'PubSub Rocks!',
    ]));
    return $response;
  }

  public function discover(ServerRequestInterface $request, ResponseInterface $response) {
    session_setup();

    $this->client = new HTTP();
    $params = $request->getParsedBody();

    $topic_url = $params['topic'];
    $topic = $this->client->get($params['topic']);

    $http = [
      'hub' => false,
      'self' => false,
    ];
    $doc = [
      'hub' => false,
      'self' => false,
      'type' => false,
    ];
    if(array_key_exists('hub', $topic['rels'])) {
      $http['hub'] = $topic['rels']['hub'][0];
    }
    if(array_key_exists('self', $topic['rels'])) {
      $http['self'] = $topic['rels']['self'][0];
    }

    if(array_key_exists('Content-Type', $topic['headers'])) {
      if(preg_match('|text/html|', $topic['headers']['Content-Type'])) {

        $mf2 = \Mf2\parse($topic['body'], $topic_url);
        if(array_key_exists('hub', $mf2['rels'])) {
          $doc['hub'] = $mf2['rels']['hub'][0];
        }
        if(array_key_exists('self', $mf2['rels'])) {
          $doc['self'] = $mf2['rels']['self'][0];
        }
        $doc['type'] = 'html';

      } else if(preg_match('|xml|', $topic['headers']['Content-Type'])) {

        $dom = html_to_dom_document($topic['body']);
        $xpath = new DOMXPath($dom);
        foreach($xpath->query('//link[@href]') as $href) {
          $rel = $href->getAttribute('rel');
          $url = $href->getAttribute('href');
          if($rel == 'hub') {
            $doc['hub'] = $url;
          } else if($rel == 'self') {
            $doc['self'] = $url;
          }
        }

        if($xpath->query('//rss')->length)
          $doc['type'] = 'rss';
        else if($xpath->query('//feed')->length)
          $doc['type'] = 'atom';

      }
    }

    $data = [
      'http' => $http,
      'doc' => $doc,
    ];

    $hub = false;
    $self = false;

    // Prioritize the HTTP headers
    if($http['hub'])
      $hub = $http['hub'];
    elseif($doc['hub'])
      $hub = $doc['hub'];

    if($http['self'])
      $self = $http['self'];
    elseif($doc['self'])
      $self = $doc['self'];

    $debug = json_encode($data, JSON_PRETTY_PRINT);

    return new JsonResponse([
      'hub' => $hub,
      'self' => $self,
      'debug' => $debug
    ]);
  }

  public function subscribe(ServerRequestInterface $request, ResponseInterface $response) {
    session_setup();

    $this->client = new HTTP();
    $params = $request->getParsedBody();


  }


}

