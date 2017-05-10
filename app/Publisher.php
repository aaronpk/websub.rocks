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

    $this->client = new HTTP(Config::$useragent);
    $this->client->set_timeout(10);
    $params = $request->getParsedBody();
    if(!$params) {
      $params = $request->getQueryParams();
    }

    $topic_url = $params['topic'];
    $topic = $this->client->get($params['topic']);

    if($topic['error']) {
      return new JsonResponse([
        'error' => $topic['error'],
        'error_description' => $topic['error_description']
      ]);
    }

    $http = [
      'hub' => [],
      'self' => [],
    ];
    $doc = [
      'hub' => [],
      'self' => [],
      'type' => false,
    ];
    $hostmeta = [
      'hub' => [],
    ];

    // Get the values from the Link headers
    if(array_key_exists('hub', $topic['rels'])) {
      $http['hub'] = $topic['rels']['hub'];
    }
    if(array_key_exists('self', $topic['rels'])) {
      $http['self'] = $topic['rels']['self'];
    }

    $content_type = '';
    if(array_key_exists('Content-Type', $topic['headers'])) {
      $content_type = $topic['headers']['Content-Type'];
      if(is_array($content_type))
        $content_type = $content_type[count($content_type)-1];

      if(preg_match('|text/html|', $content_type)) {

        $dom = p3k\html_to_dom_document($topic['body']);
        $xpath = new DOMXPath($dom);

        foreach($xpath->query('*/link[@href]') as $link) {
          $rel = $link->getAttribute('rel');
          $url = $link->getAttribute('href');
          if($rel == 'hub') {
            $doc['hub'][] = $url;
          } else if($rel == 'self') {
            $doc['self'][] = $url;
          }
        }

        $doc['type'] = 'html';

      } else if(preg_match('|xml|', $content_type)) {

        $dom = p3k\xml_to_dom_document($topic['body']);
        $xpath = new DOMXPath($dom);
        $xpath->registerNamespace('atom', 'http://www.w3.org/2005/Atom');

        if($xpath->query('/rss')->length) {
          $doc['type'] = 'rss';
        } elseif($xpath->query('/atom:feed')->length) {
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

    // Check for a .well-known file
    $topic_base = parse_url($params['topic'], PHP_URL_SCHEME).'://'.parse_url($params['topic'], PHP_URL_HOST);
    $hostmeta_response = $this->client->get($topic_base.'/.well-known/host-meta');
    if($hostmeta_response['code'] == 200) {
      if(isset($hostmeta_response['headers']['Content-Type']) && is_string($hostmeta_response['headers']['Content-Type'])) {
        if(strpos($hostmeta_response['headers']['Content-Type'], 'xml') !== false) {
          $dom = p3k\xml_to_dom_document($hostmeta_response['body']);
          foreach($dom->getElementsByTagName('Link') as $link) {
            if($link->getAttribute('rel') == 'hub') {
              $hostmeta['hub'][] = $link->getAttribute('href');
            }
          }
        }
      }
    }

    $data = [
      'http' => $http,
      'doc' => $doc,
      'hostmeta' => $hostmeta
    ];

    $hub = false;
    $hub_source = false;
    $self = false;
    $self_source = false;

    // Prioritize the HTTP headers
    if($http['hub']) {
      $hub = $http['hub'];
      $hub_source = 'http';
    }
    elseif($doc['hub']) {
      $hub = $doc['hub'];
      $hub_source = 'body';
    }
    elseif($hostmeta['hub']) {
      $hub = $hostmeta['hub'];
      $hub_source = 'hostmeta';
    }

    if($http['self']) {
      $self = $http['self'];
      $self_source = 'http';
    }
    elseif($doc['self']) {
      $self = $doc['self'];
      $self_source = 'body';
    }

    $jwt = JWT::encode([
      'hub' => $hub,
      'topic' => $self,
    ], Config::$secret);

    // Log this in the database if there is a hub and self
    if($hub && $self) {
      $publisher = ORM::for_table('publishers')
        ->where('user_id', is_logged_in() ? $_SESSION['user_id'] : 0)
        ->where('input_url', $topic_url)
        ->find_one();
      if(!$publisher) {
        $publisher = ORM::for_table('publishers')->create();
        $publisher->user_id = is_logged_in() ? $_SESSION['user_id'] : 0;
        $publisher->input_url = $topic_url;
      }
      $publisher->date_created = date('Y-m-d H:i:s');
      $publisher->hub_url = $hub[0];
      $publisher->hub_source = $hub_source;
      $publisher->self_url = $self[0];
      $publisher->self_source = $self_source;
      $publisher->content_type = $content_type;
      $publisher->http_links = json_encode($http,JSON_UNESCAPED_SLASHES);
      $publisher->body_links = json_encode($doc,JSON_UNESCAPED_SLASHES);
      $publisher->hostmeta_links = json_encode($hostmeta,JSON_UNESCAPED_SLASHES);
      $publisher->save();
    }

    $debug = json_encode($data, JSON_PRETTY_PRINT);
    $debug = $data;

    return new JsonResponse([
      'hub' => $hub,
      'self' => $self,
      'jwt' => $jwt,
      'debug' => $topic
    ]);
  }

  public function subscribe(ServerRequestInterface $request, ResponseInterface $response) {
    p3k\session_setup();

    $this->client = new HTTP(Config::$useragent);
    $this->client->set_timeout(10);
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

