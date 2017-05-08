<?php
namespace App;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Diactoros\Response\JsonResponse;
use ORM;
use Config;
use Rocks\Hub;
use Rocks\Feed;
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
      case 104:
        return 'Discovery Priority';
      case 200:
        return 'Subscribing to a URL with a Different rel=self';
      case 201:
        return 'Subscribing to a Temporarily Redirected Topic';
      case 202:
        return 'Subscribing to a Permanently Redirected Topic';
      case 203:
        return 'Subscribing to a Temporarily Redirected Hub';
      case 204:
        return 'Subscribing to a Permanently Redirected Hub';
      case 205:
        return 'Rejects a Verification Request for an Invalid Topic';
      case 300:
        return 'Returns 2xx for Successful Delivery';
      case 301:
        return 'Rejects Invalid Signatures';
      case 302: 
        return 'Rejects Distribution with No Signature';
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
        $description = '<p>This test provides a sample Atom feed that advertises the hub and self URLs as &lt;link&gt; elements within the feed. You\'ll be able to generate new posts in this feed once you are subscribed.</p>';
        break;
      case 103:
        $description = '<p>This test provides a sample RSS feed that advertises the hub and self URLs as &lt;atom:link&gt; elements within the feed. You\'ll be able to generate new posts in this feed once you are subscribed.</p>';
        break;
      case 104:
        $description = '<p>This test checks that you are prioritizing HTTP Link headers over document link tags. If you can successfully subcribe to this feed you have passed the test. You will fail the test if you attempt to subscribe to the wrong feed.</p>';
        break;
      case 200:
        $description = '<p>This test reports a different rel=self URL from the URL used to retrieve it.</p>';
        break;
      case 201:
        $description = '<p>This test checks that you can subscribe to a topic that sends a 302 temporary redirect to a new topic. This is used to migrate subscriptions to a new URL, such as when moving an account to a new domain name.</p>';
        break;
      case 202:
        $description = '<p>This test checks that you can subscribe to a topic that sends a 301 permanent redirect to a new topic. This is used to migrate subscriptions to a new URL, such as when moving an account to a new domain name.</p>';
        break;
      case 203:
        $description = '<p>This test checks that you can subscribe to a hub that sends a 307 temporary redirect to a new hub. This is used when the hub changes its own URL.</p>';
        break;
      case 204:
        $description = '<p>This test checks that you can subscribe to a hub that sends a 308 permanent redirect to a new hub. This is used when the hub changes its own URL.</p>';
        break;
      case 205:
        $description = '<p>This test checks that the subscriber properly rejects a verification request for an invalid topic URL. To start this test, attempt to subscribe to the URL below. The hub will send a subscription verification request with a different topic URL, and your subscriber should reject the request.</p>';
        break;
      case 300:
        $description = '<p>This test confirms that your subscriber returns HTTP 2xx when the notification payload is delivered. If your subscription request includes a secret, a valid signature will be sent in the notification distribution.</p>';
        break;
      case 301:
        $description = '<p>This test confirms that your subscriber rejects a distribution request that contains an invalid signature. You will need to include a secret when you subscribe to this URL. If your subscriber doesn\'t support authenticated distributions, you can ignore this test.</p>';
        break;
      case 302:
        $description = '<p>This test confirms that your subscriber rejects a distribution request that does not contain a signature if the subscription was created with a secret. You will need to include a secret when you subscribe to this URL. If your subscriber doesn\'t support authenticated distributions, you can ignore this test.</p>';
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

    $query = $request->getQueryParams();

    streaming_publish($token, [
      'type' => 'discover',
      'method' => 'HEAD',
    ]);

    Feed::set_up_posts_in_feed($token);

    $hub = Config::$base.'blog/'.$num.'/'.$token.'/hub';
    $self = Config::$base.'blog/'.$num.'/'.$token;

    switch($num) {
      case 100:
      case 205:
      case 300:
      case 301:
      case 302:
        $response = $response
          ->withHeader('Link', '<'.$self.'>; rel="self"')
          ->withAddedHeader('Link', '<'.$hub.'>; rel="hub"');
        break;
      case 101: 
        break;
      case 102:
        $response = $response->withHeader('Content-Type', 'application/atom+xml');
        break;
      case 103:
        $response = $response->withHeader('Content-Type', 'application/rss+xml');
        break;
      case 104:
        $response = $response
          ->withHeader('Content-Type', 'application/atom+xml')
          ->withAddedHeader('Link', '<'.$self.'>; rel="self"')
          ->withAddedHeader('Link', '<'.$hub.'>; rel="hub"');
        break;
      case 200:
        $self = p3k\url\add_query_params_to_url($self, ['self'=>'other']);
        $response = $response
          ->withHeader('Link', '<'.$self.'>; rel="self"')
          ->withAddedHeader('Link', '<'.$hub.'>; rel="hub"');
        break;
      case 201:
      case 202:
        $self = p3k\url\add_query_params_to_url($self, ['redirect'=>'complete']);
        if(!isset($query['redirect'])) {
          return $response
            ->withStatus($num == 201 ? 302 : 301)
            ->withHeader('Location', $self);
        } else {
          $response = $response
            ->withHeader('Link', '<'.$self.'>; rel="self"')
            ->withAddedHeader('Link', '<'.$hub.'>; rel="hub"');
        }
        break;
      case 203:
      case 204:
        $hub = p3k\url\add_query_params_to_url($hub, ['redirect'=>'true']);
        $response = $response
          ->withHeader('Link', '<'.$self.'>; rel="self"')
          ->withAddedHeader('Link', '<'.$hub.'>; rel="hub"');
        break;
    }
    return $response;
  }

  public function get_feed(ServerRequestInterface $request, ResponseInterface $response, $args) {
    p3k\session_setup();
    $num = $args['num'];
    $token = $args['token'];

    $query = $request->getQueryParams();

    streaming_publish($token, [
      'type' => 'discover',
      'method' => 'GET',
    ]);

    Feed::set_up_posts_in_feed($token);
    
    $posts = Feed::get_posts_in_feed($token);

    $hub = Config::$base.'blog/'.$num.'/'.$token.'/hub';
    $self = Config::$base.'blog/'.$num.'/'.$token;

    $link_tag = '';
    switch($num) {
      case 100:
      case 205:
        $view = 'subscriber/feed';
        $response = $response
          ->withHeader('Link', '<'.$self.'>; rel="self"')
          ->withAddedHeader('Link', '<'.$hub.'>; rel="hub"');
        break;
      case 300:
      case 301:
      case 302:
        $view = 'subscriber/feed-3xx';
        $response = $response
          ->withHeader('Link', '<'.$self.'>; rel="self"')
          ->withAddedHeader('Link', '<'.$hub.'>; rel="hub"');
        break;
      case 101:
        $view = 'subscriber/feed';
        $link_tag = '<link rel="hub" href="'.$hub.'">'."\n".'<link rel="self" href="'.$self.'">';
        break;
      case 102:
        $view = 'subscriber/feed-atom';
        $response = $response->withHeader('Content-Type', 'text/xml'); // text/xml for XSLT
        break;
      case 103:
        $view = 'subscriber/feed-rss';
        $response = $response->withHeader('Content-Type', 'text/xml'); // text/xml for XSLT
        break;
      case 104:
        $response = $response
          ->withHeader('Content-Type', 'text/xml') // text/xml for XSLT
          ->withAddedHeader('Link', '<'.$self.'>; rel="self"')
          ->withAddedHeader('Link', '<'.$hub.'>; rel="hub"');
        // Overwrite the $hub variables to set different values for the XML feed
        $hub = p3k\url\add_query_params_to_url($hub, ['error'=>'wrongtag']);
        $view = 'subscriber/feed-atom';
        break;
      case 200:
        $view = 'subscriber/feed';
        $self = p3k\url\add_query_params_to_url($self, ['self'=>'other']);
        $response = $response
          ->withHeader('Link', '<'.$self.'>; rel="self"')
          ->withAddedHeader('Link', '<'.$hub.'>; rel="hub"');
        break;      
      case 201:
      case 202:
        $view = 'subscriber/feed';
        $self = p3k\url\add_query_params_to_url($self, ['redirect'=>'complete']);
        if(!isset($query['redirect'])) {
          return $response
            ->withStatus(($num == 201 ? 302 : 301))
            ->withHeader('Location', $self);
        } else {
          $response = $response
            ->withHeader('Link', '<'.$self.'>; rel="self"')
            ->withAddedHeader('Link', '<'.$hub.'>; rel="hub"');
        }
        break;
      case 203:
      case 204:
        $view = 'subscriber/feed';
        $hub = p3k\url\add_query_params_to_url($hub, ['redirect'=>'true']);
        $response = $response
          ->withHeader('Link', '<'.$self.'>; rel="self"')
          ->withAddedHeader('Link', '<'.$hub.'>; rel="hub"');
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
    return $response;
  }

  public function hub(ServerRequestInterface $request, ResponseInterface $response, $args) {
    p3k\session_setup();
    $num = $args['num'];
    $token = $args['token'];

    $posts = Feed::get_posts_in_feed($token);

    // The hub doesn't exist until there are posts in it. Discovering the hub will create posts.
    if(count($posts) == 0) {
      return self::hub_error($token, ['error' => 'not_found'], 404);
    }

    $query = $request->getQueryParams();
    $params = $request->getParsedBody();

    $mode = array_key_exists('hub_mode', $params) ? $params['hub_mode'] : false;

    switch($mode) {
      case 'subscribe':
      case 'unsubscribe':

        // Required parameters
        if(!array_key_exists('hub_callback', $params)
           || !array_key_exists('hub_topic', $params)) {

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

        // Check that the topic matches
        $expected_topic = Config::$base . 'blog/' . $num . '/' . $token;

        if($num == 201 || $num == 202) {
          $expected_topic = p3k\url\add_query_params_to_url($expected_topic, ['redirect'=>'complete']);
        }
        if($num == 200) {
          $expected_topic = p3k\url\add_query_params_to_url($expected_topic, ['self'=>'other']);
        }

        if($params['hub_topic'] != $expected_topic) {
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

        // Check for test-specific errors
        switch($num) {
          case 104:
            if(isset($query['error']) && $query['error'] == 'wrongtag') {
              return self::hub_error($token, [
                'error' => 'wrong_discovery_priority',
                'error_description' => 'The subscriber used the tags found in the document body rather than in the HTTP Link headers.'
              ], 400);
            }
            break;
          case 203:
          case 204:
            if(isset($query['redirect']) && $query['redirect'] == 'true') {
              // Send a redirect
              $hub = Config::$base.'blog/'.$num.'/'.$token.'/hub';
              return $response->withStatus(($num == 203 ? 307 : 308))
                ->withHeader('Location', $hub);
            }
            break;
          case 301:
          case 302:
            // These tests require the subscription is created with a secret
            if(!isset($params['hub_secret'])) {
              return self::hub_error($token, [
                'error' => 'missing_secret',
                'error_description' => 'This test requires that you create the subscription with a secret. If you do not want to support checking the signature of notification payloads you can skip this test.'
              ]);
            }
            break;
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
          } else {
            $subscriber->challenge = p3k\random_string(20);
            $subscriber->save();
          }
        }

        // Send the verification to the callback URL
        $result = Hub::verify($num, $token, $mode, $params['hub_callback'], $subscriber->challenge);

        // For 205, the correct response from the subscriber is rejecting the request
        if($num == 205) {
          if($result['code'] == 404) {
            // Subscriber MUST return 404 to properly reject it

            streaming_publish($token, [
              'type' => 'success',
              'mode' => $mode,
              'callback_response' => $result['debug'],
              'topic' => $params['hub_topic'],
              'skip_continue' => false,
              'success_message' => 'Great! Your subscriber properly rejected the subscription request for an invalid topic URL'
            ]);

            $response->getBody()->write('Your subscriber properly rejected the subscription request for an invalid topic URL.');
            return $response;
          } else {
            return self::hub_error($token, [
              'error' => 'subscription_not_rejected',
              'error_description' => 'The callback URL did not reject the incorrect verification request. The callback URL must return HTTP 404 to reject the request.',
              'code' => $result['code'],
              'callback_response' => $result['debug']
            ], 400);
          }
        }

        // Check that the subscriber echo'd back the challenge
        if(floor($result['code']/100) == 2 && $result['body'] == $subscriber->challenge) {
          if($mode == 'subscribe') {
            $subscriber->active = 1;
            $subscriber->date_expires = date('Y-m-d H:i:s', time() + Hub::$LEASE_SECONDS);
          } elseif($mode == 'unsubscribe') {
            $subscriber->active = 0;
            $subscriber->date_expires = date('Y-m-d H:i:s');
          }
          $subscriber->challenge_response_code = $result['code'];
          $subscriber->challenge_response = $result['debug'];
          $subscriber->save();

          $success_message = false;
          if($num == 104) {
            $success_message = 'Great! You discovered the correct URL to subscribe to, prioritizing the HTTP Link headers.';
          }

          streaming_publish($token, [
            'type' => 'success',
            'mode' => $mode,
            'callback_response' => $result['debug'],
            'topic' => $params['hub_topic'],
            'skip_continue' => ($num == 104),
            'success_message' => $success_message
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

    $posts = Feed::get_posts_in_feed($token);

    if(count($posts) == 0) {
      return new JsonResponse([
        'error' => 'not_found'
      ], 404);
    }

    $ids = array_map(function($i){ return $i['id']; }, $posts);
    $post = ORM::for_table('quotes')
      ->where_not_in('id', $ids)->order_by_expr('RAND()')
      ->limit(1)->find_one();

    $data = Feed::add_post_to_feed($token, $post);
    
    switch($num) {
      case 301:
        $send_secret = 'invalid'; break;
      case 302:
        $send_secret = 'omit'; break;
      default:
        $send_secret = 'valid'; break;
    }

    $delivered = Hub::publish($num, $token, $send_secret);

    if($request->getMethod() == 'GET') {
      return $response->withHeader('Location', '/blog/'.$num.'/'.$token)->withStatus(302);
    } else {
      $posts = Feed::get_posts_in_feed($token);
      $templates = new \League\Plates\Engine(dirname(__FILE__).'/../views');
      $html = $templates->render('subscriber/post-list', ['posts'=>$posts, 'num'=>$num]);

      $result = null;
      $message = null;
      if(in_array($num, [300,301,302])) {
        if(count($delivered) == 0) {
          $result = 'fail';
          $message = 'There are no active subscribers for this feed.';
        } elseif(count($delivered) == 1) {
          $d = $delivered[0];

          switch($num) {
            case 300:
              if(floor($d['code']/100) == 2) {
                $result = 'success';
                $message = 'Great! Your subscriber returned '.$d['code'].' acknowledging the successful receipt of the delivery.';
              } else {
                $result = 'fail';
                $message = 'Your subscriber failed to acknowledge the delivery successfully. You need to return HTTP 2xx to consider the delivery a success. Your subscriber returned '.$d['code'].'.';
              }
              break;
            case 301:
              if(floor($d['code']/100) == 2) {
                $result = 'success';
                $message = 'Great! Your subscriber returned '.$d['code'].'. The payload delivered contains an invalid signature, so you should ensure your subscriber does not process this notification.';
              } else {
                $result = 'fail';
                $message = 'Your subscriber failed to acknowledge the delivery successfully. You need to return HTTP 2xx even if the signature doesn\'t match. Your subscriber returned '.$d['code'].'.';
              }
              break;
            case 302:
              if(floor($d['code']/100) == 2) {
                $result = 'fail';
                $message = 'Your subscriber failed to reject the delivery of this notification. This delivery did not include a signature, but your subscription was created with a secret. Your subscriber returned '.$d['code'].'.';
              } else {
                $result = 'success';
                $message = 'Great! Your subscriber returned '.$d['code'].'. The payload delivered contains no signature, but your subscription was created with a secret.';
              }
              break;
          }

        } else {
          $result = 'fail';
          $message = 'Multiple subscriptions were created for this feed so we aren\'t sure which you are testing. Try again with a new feed and only create one subscription.';
        }
      }

      return new JsonResponse([
        'post' => $data,
        'delivered' => $delivered,
        'html' => $html,
        'result' => $result,
        'message' => $message,
      ]);
    }
  }

  private static function hub_error($token, $params, $code=400) {
    $params['type'] = 'error';
    streaming_publish($token, $params);
    return new JsonResponse($params, $code);
  }

}

