<?php
namespace App;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Diactoros\Response\JsonResponse;
use ORM;
use Config;
use Rocks\Hub;

class Subscriber {

  public function index(ServerRequestInterface $request, ResponseInterface $response) {
    session_setup();
    
    $response->getBody()->write(view('subscriber/index', [
      'title' => 'PubSub Rocks!',
    ]));
    return $response;
  }

  public function get_test(ServerRequestInterface $request, ResponseInterface $response, $args) {
    session_setup();
    $num = $args['num'];

    $token = random_string(20);

    $response->getBody()->write(view('subscriber/'.$num, [
      'title' => 'PubSub Rocks!',
      'token' => $token,
    ]));
    return $response;
  }

  public function head_feed(ServerRequestInterface $request, ResponseInterface $response, $args) {
    session_setup();
    $num = $args['num'];
    $token = $args['token'];

    streaming_publish($token, [
      'type' => 'discover',
    ]);

    self::set_up_posts_in_feed($token);

    return $response
      ->withHeader('Link', '<'.Config::$base.'subscriber/'.$num.'/'.$token.'>; rel="self"')
      ->withAddedHeader('Link', '<'.Config::$base.'subscriber/'.$num.'/'.$token.'/hub>; rel="hub"');
  }

  public function get_feed(ServerRequestInterface $request, ResponseInterface $response, $args) {
    session_setup();
    $num = $args['num'];
    $token = $args['token'];

    streaming_publish($token, [
      'type' => 'discover',
    ]);

    self::set_up_posts_in_feed($token);
    
    $posts = self::get_posts_in_feed($token);

    $response->getBody()->write(view('subscriber/feed', [
      'title' => 'PubSub Rocks!',
      'token' => $token,
      'posts' => $posts,
    ]));
    return $response
      ->withHeader('Link', '<'.Config::$base.'subscriber/'.$num.'/'.$token.'>; rel="self"')
      ->withAddedHeader('Link', '<'.Config::$base.'subscriber/'.$num.'/'.$token.'/hub>; rel="hub"');
  }

  public function hub(ServerRequestInterface $request, ResponseInterface $response, $args) {
    session_setup();
    $num = $args['num'];
    $token = $args['token'];

    $posts = self::get_posts_in_feed($token);

    // The hub doesn't exist until there are posts in it. Discovering the hub will create posts.
    if(count($posts) == 0) {
      return new JsonResponse([
        'error' => 'not_found'
      ], 404);
    }

    $params = $request->getParsedBody();

    $mode = array_key_exists('hub_mode', $params) ? $params['hub_mode'] : false;

    switch($mode) {
      case 'subscribe':

        // Required parameters
        if(!array_key_exists('hub_callback', $params)
           || !array_key_exists('hub_topic', $params)) {

          // TODO: publish this to the UI
          if(!array_key_exists('hub_callback', $params) && array_key_exists('hub_topic', $params))
            $description = 'The request is missing the callback parameter';
          elseif(array_key_exists('hub_callback', $params) && !array_key_exists('hub_topic', $params))
            $description = 'The request is missing the topic parameter';
          else
            $description = 'The request is missing the callback and topic parameters';

          return new JsonResponse([
            'error' => 'missing_params',
            'error_description' => $description
          ], 400);
        }

        // Check that callback is a valid URL
        if(($error=validate_url($params['hub_callback'])) !== false) {
          return new JsonResponse([
            'error' => 'invalid_callback',
            'error_description' => 'There was an error with the callback URL: ' . $error
          ], 400);
        }

        // Check that the hub and topic match
        if($params['hub_topic'] != Config::$base . 'subscriber/' . $num . '/' . $token) {
          return new JsonResponse([
            'error' => 'invalid_topic',
            'error_description' => 'The topic provided is not allowed at this hub.'
          ], 400);
        }

        // If there is a secret, check that it's <200 bytes
        if(array_key_exists('hub_secret', $params)) {
          if(strlen($params['hub_secret']) > 200) {
            return new JsonResponse([
              'error' => 'invalid_secret',
              'error_description' => 'The secret must be less than 200 bytes.'
            ], 400);
          }
        }

        // TODO: lease_seconds


        // Create the subscriber if it doesn't yet exist
        $subscriber = ORM::for_table('subscriber_hub')
          ->where('test', $num)->where('token', $token) # this identifies the topic
          ->where('callback', $params['hub_callback'])  # the callback URL
          ->find_one();
        if(!$subscriber) {
          $subscriber = ORM::for_table('subscriber_hub')->create();
          $subscriber->test = $num;
          $subscriber->token = $token;
          $subscriber->date_created = date('Y-m-d H:i:s');
          $subscriber->callback = $params['hub_callback'];
          if(array_key_exists('hub_secret', $params))
            $subscriber->secret = $params['hub_secret'];
        }

        // Generate a new challenge
        $subscriber->challenge = random_string(20);
        $subscriber->save();

        // Send the verification to the callback URL
        $result = Hub::verify($num, $token, 'subscribe', $params['hub_callback'], $subscriber->challenge);

        // TODO: check verification results
        echo "-------\n";
        print_r($result);

        die();
        return $response->withStatus(202);


      case 'unsubscribe':



      default:
        return new JsonResponse([
          'error' => 'invalid_mode'
        ], 400);
    }
  }

  public function publish(ServerRequestInterface $request, ResponseInterface $response, $args) {
    session_setup();
    $num = $args['num'];
    $token = $args['token'];

    $posts = self::get_posts_in_feed($token);

    if(count($posts) == 0) {
      return new JsonResponse([
        'error' => 'not_found'
      ], 404);
    }

    $ids = array_map(function($i){ return $i['id']; }, $posts);
    $post = ORM::for_table('quotes')
      ->where_not_in('id', $ids)->order_by_expr('RAND()')
      ->limit(1)->find_one();

    $data = self::add_post_to_feed($token, $post);
    
    Hub::publish($num, $token);

    return new JsonResponse([
      'post' => $data
    ]);
  }

  private static function set_up_posts_in_feed($token) {
    $key = 'pubsub.rocks::feed::'.$token;
    if(redis()->llen($key) == 0) {
      $quotes = ORM::for_table('quotes')->order_by_expr('RAND()')->limit(3)->find_many();
      foreach($quotes as $quote) {
        self::add_post_to_feed($token, $quote);
      }
    }
    self::touch_feed($token);
  }

  private static function touch_feed($token) {
    $key = 'pubsub.rocks::feed::'.$token;
    redis()->expire($key, 86400);
  }

  private static function add_post_to_feed($token, $post) {
    $key = 'pubsub.rocks::feed::'.$token;
    $data = [
      'id' => $post->id,
      'author' => $post->author,
      'content' => $post->content,
      'published' => date('Y-m-d H:i:s'),
    ];
    redis()->lpush($key, json_encode($data));
    // Trim the list to show the last N posts
    redis()->ltrim($key, 0, 9);
    return $data;
  }

  private static function get_posts_in_feed($token) {
    $key = 'pubsub.rocks::feed::'.$token;
    $len = redis()->llen($key);
    $items = redis()->lrange($key, 0, $len-1);
    return array_map(function($i){ return json_decode($i, true); }, $items);
  }


}

