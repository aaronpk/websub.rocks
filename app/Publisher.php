<?php
namespace App;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Diactoros\Response\JsonResponse;
use ORM, Config;
use DOMXPath;
use Firebase\JWT\JWT;
use p3k\HTTP;
use p3k;

class Publisher {

  public $client;

  public function index(ServerRequestInterface $request, ResponseInterface $response) {
    p3k\session_setup();
    
    $response->getBody()->write(view('publisher/index', [
      'title' => 'WebSub Rocks!',
    ]));
    return $response;
  }

  public function discover(ServerRequestInterface $request, ResponseInterface $response) {
    p3k\session_setup();

    $this->client = new HTTP();
    $params = $request->getParsedBody();

    $topic_url = $params['topic'];
    $topic = $this->client->get($params['topic']);

    $http = [
      'hub' => [],
      'self' => [],
    ];
    $doc = [
      'hub' => [],
      'self' => [],
      'type' => false,
    ];
    if(array_key_exists('hub', $topic['rels'])) {
      $http['hub'] = $topic['rels']['hub'];
    }
    if(array_key_exists('self', $topic['rels'])) {
      $http['self'] = $topic['rels']['self'];
    }

    if(array_key_exists('Content-Type', $topic['headers'])) {
      if(preg_match('|text/html|', $topic['headers']['Content-Type'])) {

        $mf2 = \Mf2\parse($topic['body'], $topic_url);
        if(array_key_exists('hub', $mf2['rels'])) {
          $doc['hub'] = $mf2['rels']['hub'];
        }
        if(array_key_exists('self', $mf2['rels'])) {
          $doc['self'] = $mf2['rels']['self'];
        }
        $doc['type'] = 'html';

      } else if(preg_match('|xml|', $topic['headers']['Content-Type'])) {

        $dom = xml_to_dom_document($topic['body']);
        $xpath = new DOMXPath($dom);
        $xpath->registerNamespace('atom', 'http://www.w3.org/2005/Atom');

        if($xpath->query('/rss')->length) {
          $doc['type'] = 'rss';
        } elseif($xpath->query('/feed')->length) {
          $doc['type'] = 'atom';
        }

        // Look for atom link elements in the feed        
        foreach($xpath->query('/atom:feed/atom:link[@href]') as $href) {
          $rel = $href->getAttribute('rel');
          $url = $href->getAttribute('href');
          if($rel == 'hub') {
            $doc['hub'][] = $url;
          } else if($rel == 'self') {
            $doc['self'][] = $url;
          }
        }

        // Some RSS feeds include the link element as an atom attribute
        foreach($xpath->query('/rss/channel/atom:link[@href]') as $href) {
          $rel = $href->getAttribute('rel');
          $url = $href->getAttribute('href');
          if($rel == 'hub') {
            $doc['hub'][] = $url;
          } else if($rel == 'self') {
            $doc['self'][] = $url;
          }
        }

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

    $jwt = JWT::encode([
      'hub' => $hub,
      'topic' => $self,
    ], Config::$secret);

    $debug = json_encode($data, JSON_PRETTY_PRINT);

    return new JsonResponse([
      'hub' => $hub,
      'self' => $self,
      'jwt' => $jwt,
      'debug' => $debug
    ]);
  }

  public function subscribe(ServerRequestInterface $request, ResponseInterface $response) {
    p3k\session_setup();

    $this->client = new HTTP();
    $params = $request->getParsedBody();

    $data = (array)JWT::decode($params['jwt'], Config::$secret, ['HS256']);

    if(!$data) {
      return new JsonResponse([
        'error' => 'invalid_request'
      ], 400);
    }

    // There will only be one topic in the payload since they would have seen an error otherwise
    $topic = $data['topic'][0];

    // Ensure the specified hub is in the JWT
    $hub = $params['hub'];
    if(!in_array($params['hub'], $data['hub'])) {
      return new JsonResponse([
        'error' => 'invalid_request'
      ], 400);
    }

    // Save to the DB so the subscription gets a unique token
    $subscription = ORM::for_table('subscriptions')
      ->where('hub', $hub)
      ->where('topic', $topic)
      ->find_one();
    if(!$subscription) {
      $subscription = ORM::for_table('subscriptions')->create();
      $subscription->token = p3k\random_string(20);
      $subscription->hub = $hub;
      $subscription->topic = $topic;
      $subscription->date_created = date('Y-m-d H:i:s');
    }
    $subscription->date_subscription_requested = date('Y-m-d H:i:s');
    $subscription->pending = 1;
    $subscription->save();

    // Subscribe to the hub
    $res = $this->client->post($hub, http_build_query([
      'hub.callback' => Config::$base . 'publisher/callback?token='.$subscription->token,
      'hub.mode' => 'subscribe',
      'hub.topic' => $topic,
      'hub.lease_seconds' => 7200
    ]));

    $subscription->subscription_response_code = $res['code'];
    $subscription->subscription_response_body = $res['body'];
    $subscription->save();

    if($res['code'] == 202) {
      $result = 'success';
    } else {
      $result = 'error';
    }

    $debug = json_encode($data, JSON_PRETTY_PRINT);

    return new JsonResponse([
      'result' => $result,
      'token' => ($result == 'success' ? $subscription->token : false),
      'debug' => $subscription->subscription_response_body,
      'error' => $res['error'],
      'error_description' => $res['error_description'],
      'code' => $res['code']
    ]);
  }


  public function callback_verify(ServerRequestInterface $request, ResponseInterface $response) {
    $params = $request->getQueryParams();

    if(!array_key_exists('hub_topic', $params) 
      || !array_key_exists('hub_challenge', $params)
      || !array_key_exists('hub_lease_seconds', $params)) {
      return new JsonResponse([
        'error' => 'bad_request',
        'error_description' => 'Missing parameters'
      ], 400);
    }

    // Verify that the topic corresponds to a pending subscription
    $subscription = ORM::for_table('subscriptions')
      ->where('topic', $params['hub_topic'])
      ->where('pending', 1)
      ->find_one();

    if(!$subscription) {
      return new JsonResponse([
        'error' => 'not_found',
        'error_description' => 'There is no pending subscription for the provided topic'
      ], 404);
    }

    $subscription->pending = 0;
    $subscription->date_subscription_confirmed = date('Y-m-d H:i:s');
    $subscription->lease_seconds = $params['hub_lease_seconds'];
    $subscription->date_expires = date('Y-m-d H:i:s', time()+$params['hub_lease_seconds']);
    $subscription->save();

    streaming_publish($subscription->token, [
      'type' => 'active'
    ]);

    return $params['hub_challenge'];
  }

  public function subscription_status(ServerRequestInterface $request, ResponseInterface $response) {
    $query = $request->getQueryParams();

    if(!array_key_exists('token', $query)) {
      return new JsonResponse([
        'error' => 'bad_request',
      ], 400);
    }

    $subscription = ORM::for_table('subscriptions')
      ->where('token', $query['token'])
      ->find_one();

    if(!$subscription) {
      return new JsonResponse([
        'error' => 'not_found',
        'error_description' => 'Subscription not found'
      ], 404);
    }

    return new JsonResponse([
      'active' => $subscription->pending == 0 ? true : false
    ]);
  }


  public function callback_deliver(ServerRequestInterface $request, ResponseInterface $response) {
    $query = $request->getQueryParams();
    $body = $request->getBody();

    if(!array_key_exists('token', $query)) {
      return new JsonResponse([
        'error' => 'bad_request',
        'error_description' => 'Invalid callback URL'
      ], 400);
    }

    $subscription = ORM::for_table('subscriptions')
      ->where('token', $query['token'])
      ->find_one();

    if(!$subscription) {
      return new JsonResponse([
        'error' => 'not_found',
        'error_description' => 'Subscription not found'
      ], 404);
    }

    streaming_publish($subscription->token, [
      'type' => 'notification',
      'body' => (string)$body
    ]);

    $subscription->date_last_notification = date('Y-m-d H:i:s');
    $subscription->notification_content_type = $request->getHeaderLine('Content-Type');
    $subscription->notification_content = (string)$body;
    $subscription->save();

    return new JsonResponse([
      'result' => 'ok'
    ]);
  }

}

