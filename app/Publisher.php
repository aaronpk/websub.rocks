<?php
namespace App;

use Rocks\Http\Request;
use Rocks\Http\Response;
use ORM, Config;
use DOMXPath;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use p3k;

class Publisher {

  public $client;

  public function index(Request $request) {
    p3k\session_setup();

    return Response::make(200, page('publisher/index', [
      'title' => 'WebSub Rocks!',
    ]));
  }

  public function discover(Request $request) {
    p3k\session_setup();

    $this->client = http_client(10);

    $topic_url = (string)$request->input('topic');
    $topic = $this->client->get($topic_url);

    if($topic['error']) {
      return Response::json([
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
          if(in_array('hub', preg_split('/\s+/', $rel)))
            $doc['hub'][] = $url;
          if(in_array('self', preg_split('/\s+/', $rel)))
            $doc['self'][] = $url;
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
    $topic_base = parse_url($topic_url, PHP_URL_SCHEME).'://'.parse_url($topic_url, PHP_URL_HOST);
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
    ], jwt_key(), 'HS256');

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

    return Response::json([
      'hub' => $hub,
      'self' => $self,
      'jwt' => $jwt,
      'debug' => $topic
    ]);
  }

  public function subscribe(Request $request) {
    p3k\session_setup();

    $this->client = http_client(10);

    try {
      $data = (array)JWT::decode((string)$request->post('jwt'), new Key(jwt_key(), 'HS256'));
    } catch(\Exception $e) {
      $data = false;
    }

    if(!$data || !is_array($data['hub'] ?? null) || !is_array($data['topic'] ?? null)) {
      return Response::json([
        'error' => 'invalid_request'
      ], 400);
    }

    // There will only be one topic in the payload since they would have seen an error otherwise
    $topic = $data['topic'][0];

    // Ensure the specified hub is in the JWT
    $hub = $request->post('hub');
    if(!in_array($hub, $data['hub'])) {
      return Response::json([
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

    return Response::json([
      'result' => $result,
      'token' => ($result == 'success' ? $subscription->token : false),
      'debug' => $subscription->subscription_response_body,
      'error' => $res['error'],
      'error_description' => $res['error_description'],
      'code' => $res['code']
    ]);
  }


  public function callback_verify(Request $request) {
    $params = $request->query;

    if(!array_key_exists('hub_topic', $params)
      || !array_key_exists('hub_challenge', $params)
      || !array_key_exists('hub_lease_seconds', $params)) {
      return Response::json([
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
      return Response::json([
        'error' => 'not_found',
        'error_description' => 'There is no pending subscription for the provided topic'
      ], 404);
    }

    $subscription->pending = 0;
    $subscription->date_subscription_confirmed = date('Y-m-d H:i:s');
    $subscription->lease_seconds = $params['hub_lease_seconds'];
    $subscription->date_expires = date('Y-m-d H:i:s', time()+(int)$params['hub_lease_seconds']);
    $subscription->save();

    streaming_publish($subscription->token, [
      'type' => 'active'
    ]);

    return Response::make(200, (string)$params['hub_challenge'], ['Content-Type' => 'text/plain']);
  }

  public function subscription_status(Request $request) {
    $query = $request->query;

    if(!array_key_exists('token', $query)) {
      return Response::json([
        'error' => 'bad_request',
      ], 400);
    }

    $subscription = ORM::for_table('subscriptions')
      ->where('token', $query['token'])
      ->find_one();

    if(!$subscription) {
      return Response::json([
        'error' => 'not_found',
        'error_description' => 'Subscription not found'
      ], 404);
    }

    return Response::json([
      'active' => $subscription->pending == 0 ? true : false
    ]);
  }


  public function callback_deliver(Request $request) {
    $query = $request->query;
    $body = $request->body;

    if(!array_key_exists('token', $query)) {
      return Response::json([
        'error' => 'bad_request',
        'error_description' => 'Invalid callback URL'
      ], 400);
    }

    $subscription = ORM::for_table('subscriptions')
      ->where('token', $query['token'])
      ->find_one();

    if(!$subscription) {
      return Response::json([
        'error' => 'not_found',
        'error_description' => 'Subscription not found'
      ], 404);
    }

    streaming_publish($subscription->token, [
      'type' => 'notification',
      'body' => $body
    ]);

    $subscription->date_last_notification = date('Y-m-d H:i:s');
    $subscription->notification_content_type = $request->header('Content-Type') ?? '';
    $subscription->notification_content = $body;
    $subscription->save();

    return Response::json([
      'result' => 'ok'
    ]);
  }

}

