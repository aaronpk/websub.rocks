<?php
namespace App;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Diactoros\Response\JsonResponse;
use ORM;
use Config;
use Rocks\Hub;
use p3k\HTTP;
use p3k;

class Subscriber {

  public function index(ServerRequestInterface $request, ResponseInterface $response) {
    p3k\session_setup();
    
    $response->getBody()->write(view('subscriber/index', [
      'title' => 'WebSub Rocks!',
    ]));
    return $response;
  }

  public static function test_name($num) {
    switch($num) {
      case 100:
        return 'HTTP Header Discovery';
      case 101:
        return 'HTML Tag Discovery';
      case 102:
        return 'Atom Feed Discovery';
      case 103:
        return 'RSS Feed Discovery';
    }
  }

  public function get_test(ServerRequestInterface $request, ResponseInterface $response, $args) {
    p3k\session_setup();
    $num = $args['num'];

    $token = p3k\random_string(20);

    $topic = Config::$base . 'blog/' . $num . '/' . $token;

    switch($num) {
      case 100:
        $description = '<p>This test provides a sample blog that you can subscribe to. You\'ll be able to have this site generate new posts in the blog once you are subscribed.</p>'
          .'<p>This test advertises the hub and self URLs only in the HTTP headers, not in the HTML contents. This verifies that your subscriber checks the HTTP headers to find the necessary URLs.</p>';
        break;
      case 101:
        $description = '<p>This test provides a sample HTML page with a few microblog posts that you can subscribe to. You\'ll be able to have this site generate new posts in the blog once you are subscribed.</p>'
          .'<p>This test advertises the hub and self URLs only in HTML tags, not in HTTP headers. This verifies that your subscriber parses the HTML to find the hub and self.</p>';
        break;
      case 102:
        $description = '';
        break;
      case 103:
        $description = '';
        break;
      default:
        throw new \Exception('This test is not configured');
    }

    $response->getBody()->write(view('subscriber/test', [
      'title' => 'WebSub Rocks!',
      'token' => $token,
      'topic' => $topic,
      'num' => $num,
      'name' => self::test_name($num),
      'description' => $description
    ]));
    return $response;
  }

  public function head_feed(ServerRequestInterface $request, ResponseInterface $response, $args) {
    p3k\session_setup();
    $num = $args['num'];
    $token = $args['token'];

    streaming_publish($token, [
      'type' => 'discover',
      'method' => 'HEAD',
    ]);

    self::set_up_posts_in_feed($token);

    $hub = Config::$base.'blog/'.$num.'/'.$token.'/hub';
    $self = Config::$base.'blog/'.$num.'/'.$token;

    switch($num) {
      case 100:
        $response = $response
          ->withHeader('Link', '<'.$self.'>; rel="self"')
          ->withAddedHeader('Link', '<'.$hub.'>; rel="hub"');
        break;
      case 101: 
        break;
      case 102:
      case 103:
        $response = $response->withHeader('Content-Type', 'text/xml');
        break;
    }
    return $response;
  }

  public function get_feed(ServerRequestInterface $request, ResponseInterface $response, $args) {
    p3k\session_setup();
    $num = $args['num'];
    $token = $args['token'];

    streaming_publish($token, [
      'type' => 'discover',
      'method' => 'GET',
    ]);

    self::set_up_posts_in_feed($token);
    
    $posts = self::get_posts_in_feed($token);

    $hub = Config::$base.'blog/'.$num.'/'.$token.'/hub';
    $self = Config::$base.'blog/'.$num.'/'.$token;

    $link_tag = '';
    switch($num) {
      case 100:
        $view = 'subscriber/feed';
        break;
      case 101:
        $view = 'subscriber/feed';
        $link_tag = '<link rel="hub" href="'.$hub.'">'."\n".'<link rel="self" href="'.$self.'">';
        break;
      case 102:
        $view = 'subscriber/feed-atom';
        $response = $response->withHeader('Content-Type', 'text/xml');
        break;
      case 103:
        $view = 'subscriber/feed-rss';
        $response = $response->withHeader('Content-Type', 'text/xml');
        break;
    }

    $response->getBody()->write(view($view, [
      'title' => 'WebSub Rocks!',
      'num' => $num,
      'name' => self::test_name($num),
      'token' => $token,
      'posts' => $posts,
      'link_tag' => $link_tag,
      'hub' => $hub,
      'self' => $self
    ]));
    if($num == 100) {
      return $response
        ->withHeader('Link', '<'.Config::$base.'blog/'.$num.'/'.$token.'>; rel="self"')
        ->withAddedHeader('Link', '<'.Config::$base.'blog/'.$num.'/'.$token.'/hub>; rel="hub"');
    } else {
      return $response;
    }
  }

  public function hub(ServerRequestInterface $request, ResponseInterface $response, $args) {
    p3k\session_setup();
    $num = $args['num'];
    $token = $args['token'];

    $posts = self::get_posts_in_feed($token);

    // The hub doesn't exist until there are posts in it. Discovering the hub will create posts.
    if(count($posts) == 0) {
      return self::hub_error($token, ['error' => 'not_found'], 404);
    }

    $params = $request->getParsedBody();

    $mode = array_key_exists('hub_mode', $params) ? $params['hub_mode'] : false;

    switch($mode) {
      case 'subscribe':
      case 'unsubscribe':

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

          return self::hub_error($token, [
            'error' => 'missing_params',
            'error_description' => $description
          ], 400);
        }

        // Check that callback is a valid URL
        if(($error=validate_url($params['hub_callback'])) !== false) {
          return self::hub_error($token, [
            'error' => 'invalid_callback',
            'error_description' => 'There was an error with the callback URL: ' . $error
          ], 400);
        }

        // Check that the hub and topic match
        if($params['hub_topic'] != Config::$base . 'blog/' . $num . '/' . $token) {
          return self::hub_error($token, [
            'error' => 'invalid_topic',
            'error_description' => 'The topic provided is not allowed at this hub.'
          ], 400);
        }

        // If there is a secret, check that it's <200 bytes
        if(array_key_exists('hub_secret', $params)) {
          if(strlen($params['hub_secret']) > 200) {
          return self::hub_error($token, [
              'error' => 'invalid_secret',
              'error_description' => 'The secret must be less than 200 bytes.'
            ], 400);
          }
        }

        // TODO: lease_seconds


        $subscriber = ORM::for_table('subscriber_hub')
          ->where('test', $num)->where('token', $token) # this identifies the topic
          ->where('callback', $params['hub_callback'])  # the callback URL
          ->find_one();

        if($mode == 'subscribe') {
          // Create the subscriber if it doesn't yet exist
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
          $subscriber->challenge = p3k\random_string(20);
          $subscriber->save();
        } else {
          if(!$subscriber) {
            return self::hub_error($token, [
              'error' => 'invalid_subscription',
              'error_description' => 'No subscription was found for the given topic and callback.'
            ], 404);
          }
        }

        // Send the verification to the callback URL
        $result = Hub::verify($num, $token, $mode, $params['hub_callback'], $subscriber->challenge);

        // Check that the subscriber echo'd back the challenge
        if(floor($result['code']/100) == 2 && $result['body'] == $subscriber->challenge) {
          if($mode == 'subscribe') {
            $subscriber->active = 1;
            $subscriber->date_expires = date('Y-m-d H:i:s', time() + Hub::$LEASE_SECONDS);
          } else {
            $subscriber->active = 0;
            $subscriber->date_expires = date('Y-m-d H:i:s');
          }
          $subscriber->challenge_response_code = $result['code'];
          $subscriber->challenge_response = $result['debug'];
          $subscriber->save();
          streaming_publish($token, [
            'type' => 'success',
            'mode' => $mode,
            'callback_response' => $result['debug'],
            'topic' => $params['hub_topic'],
          ]);
          return $response->withStatus(202);
        } else {
          // Normally the hub would check this asynchronously so 202 would always be returned.
          // This debug hub checks the challenge synchronously so it already knows that it failed now.
          return self::hub_error($token, [
            'error' => 'verification_failed',
            'error_description' => 'The callback URL did not confirm the verification request.',
            'code' => $result['code'],
            'callback_response' => $result['debug']
          ], 400);
        }

      default:
        return self::hub_error($token, [
          'error' => 'invalid_mode'
        ], 400);
    }
  }

  public function publish(ServerRequestInterface $request, ResponseInterface $response, $args) {
    p3k\session_setup();
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
    
    $delivered = Hub::publish($num, $token);

    if($request->getMethod() == 'GET') {
      return $response->withHeader('Location', '/blog/'.$num.'/'.$token)->withStatus(302);
    } else {
      $posts = self::get_posts_in_feed($token);
      $templates = new \League\Plates\Engine(dirname(__FILE__).'/../views');
      $html = $templates->render('subscriber/post-list', ['posts'=>$posts, 'num'=>$num]);

      return new JsonResponse([
        'post' => $data,
        'delivered' => $delivered,
        'html' => $html,
      ]);
    }
  }

  private static function hub_error($token, $params, $code=400) {
    $params['type'] = 'error';
    streaming_publish($token, $params);
    return new JsonResponse($params, $code);
  }

  private static function set_up_posts_in_feed($token) {
    $key = 'websub.rocks::feed::'.$token;
    if(p3k\redis()->llen($key) == 0) {
      $quotes = ORM::for_table('quotes')->order_by_expr('RAND()')->limit(3)->find_many();
      foreach($quotes as $quote) {
        self::add_post_to_feed($token, $quote);
      }
    }
    self::touch_feed($token);
  }

  private static function touch_feed($token) {
    $key = 'websub.rocks::feed::'.$token;
    p3k\redis()->expire($key, 86400);
  }

  private static function add_post_to_feed($token, $post) {
    $key = 'websub.rocks::feed::'.$token;
    $data = [
      'id' => $post->id,
      'author' => $post->author,
      'content' => $post->content,
      'published' => date('Y-m-d H:i:s'),
    ];
    p3k\redis()->lpush($key, json_encode($data));
    // Trim the list to show the last N posts
    p3k\redis()->ltrim($key, 0, 9);
    return $data;
  }

  private static function get_posts_in_feed($token) {
    $key = 'websub.rocks::feed::'.$token;
    $len = p3k\redis()->llen($key);
    $items = p3k\redis()->lrange($key, 0, $len-1);
    return array_map(function($i){ return json_decode($i, true); }, $items);
  }


}

