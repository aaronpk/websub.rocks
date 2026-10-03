<?php
namespace App;

use Rocks\Http\Request;
use Rocks\Http\Response;
use Rocks\View\Raw;
use ORM;
use Config;
use Rocks\Feed;
use p3k\HTTP;
use p3k;
use IndieWeb;

class Hub {

  public function index(Request $request) {
    session_setup();

    return Response::make(200, page('hub/index', [
      'title' => 'WebSub Rocks!',
    ]));
  }

  public function get_test(Request $request, $args) {
    session_setup();
    $num = $args['num'];

    switch($num) {
      case 100:
        $name = 'Typical Subscriber Request';
        $description = 'This subscriber will include only the parameters <code>hub.mode</code>, <code>hub.topic</code> and <code>hub.callback</code>. The hub should deliver notifications with no signature.';
        break;
      case 101:
        $name = 'Subscription with Secret';
        $description = 'This subscriber will include the parameters <code>hub.mode</code>, <code>hub.topic</code>, <code>hub.callback</code> and <code>hub.secret</code>. The hub should deliver notifications with a signature computed using this secret.';
        break;
      case 102:
        $name = 'Subscriber Sends Additional Parameters';
        $description = 'This subscriber will include some additional parameters in the request, which must be ignored by the hub if the hub doesn\'t recognize them.';
        break;
      case 103:
        $name = 'Re-Subscribes Before Expiration';
        $description = 'This subscriber tests whether the hub allows subscriptions to be re-subscribed before they expire. The hub must allow a subscription to be re-activated, and must update the previous subscription based on the topic+callback pair, rather than creating a new subscription.';
        break;
      case 104:
        $name = 'Unsubscription';
        $description = 'This test will first subscribe to a topic, and will then send an unsubscription request. You will be able to test that the unsubscription is confirmed by seeing that a notification is not received when a new post is published.';
        break;
      case 105:
        $name = 'Plaintext Topic';
        $description = 'This test will check whether your hub can handle delivering content that is not HTML or XML. The content at the topic URL of this test is plaintext.';
        break;
      case 106:
        $name = 'JSON Topic';
        $description = 'This test will check whether your hub can handle delivering content that is not HTML or XML. The content at the topic URL of this test is JSON.';
        break;
      default:
        return Response::make(404);
    }

    return Response::make(200, page('hub/test', [
      'title' => 'WebSub Rocks!',
      'num' => $num,
      'name' => $name,
      'description' => new Raw($description)
    ]));
  }

  // Start a new test
  public function post_start(Request $request, $args) {
    session_setup();
    $num = $args['num'];

    $params = $request->post;

    // Generate a new token for this test
    $token = p3k\random_string(20);

    $http = http_client();
    $client = new p3k\WebSub\Client($http);

    // If they provided a topic URL, then we first need to discover the hub
    if(isset($params['topic'])) {
      $endpoints = $client->discover($params['topic']);
      if(!$endpoints['hub']) {
        return Response::json([
          'error' => 'missing_hub',
          'error_description' => 'We did not find a rel=hub advertised at the topic provided.'
        ]);
      }
      if(!$endpoints['self']) {
        return Response::json([
          'error' => 'missing_self',
          'error_description' => 'We did not find a rel=self advertised at the topic provided.'
        ]);
      }

      $hub_url = $endpoints['hub'];
      $topic_url = $endpoints['self'];
      $publisher = 'remote';

    } elseif(isset($params['hub'])) {
      // If they did not provide a topic, and are testing an open hub, then we'll set up a new publisher that uses this hub

      $hub_url = $params['hub'];
      $topic_url = Config::$base.'hub/'.$num.'/pub/'.$token;
      $publisher = 'local';

      Feed::set_up_posts_in_feed($token);

    } else {
      return Response::json([
        'error' => 'bad_request'
      ], 400);
    }

    // Store this hub with the token
    // TODO: update the existing hub for this user if they are logged in
    $hub = ORM::for_table('hubs')->create();
    $hub->user_id = is_logged_in() ? $_SESSION['user_id'] : 0;
    $hub->date_created = date('Y-m-d H:i:s');
    $hub->url = $hub_url;
    $hub->token = $token;
    $hub->topic = $topic_url;
    $hub->publisher = $publisher;

    switch($num) {
      case 101:
        $hub->secret = p3k\random_string(20);
        break;
    }

    $hub->save();

    return Response::json([
      'token' => $token,
    ]);
  }

  // Start the subscription request, triggered automatically after the user presses start
  public function post_subscribe(Request $request, $args) {
    session_setup();
    $num = $args['num'];

    $token = (string)$request->post('token');

    $hub = ORM::for_table('hubs')->where('token', $token)->find_one();
    if(!$hub) {
      return Response::json(['error'=>'not_found','error_description'=>'No hub was found for this token'], 404);
    }
    $hub_url = $hub->url;
    $topic_url = $hub->topic;

    $http = http_client();

    // Start the subscription process at the hub
    $callback = Config::$base.'hub/'.$num.'/sub/'.$token;

    $subscription_params = [
      'hub.mode' => ($request->post('action') == 'unsubscribe' ? 'unsubscribe' : 'subscribe'),
      'hub.topic' => $topic_url,
      'hub.callback' => $callback,
    ];
    if($hub->secret)
      $subscription_params['hub.secret'] = $hub->secret;

    switch($num) {
      case 102:
        $subscription_params['foo'] = 'bar';
        $subscription_params['hub.foo'] = 'hub.bar';
        break;
    }

    $subscription = $http->post($hub_url, http_build_query($subscription_params));

    if($subscription['code'] == 202) {
      $result = 'Queued';
      $description = 'The hub accepted the subscription request and should now attempt to verify the subscription. After the hub verifies the subscription, the next step will appear below.';
      $status = 'success';
    } else {
      $result = 'Hub Error';
      $description = 'The hub did not accept the subscription request.';
      $status = 'error';
    }

    return Response::json([
      'result' => $result,
      'status' => $status,
      'token' => $token,
      'description' => $description,
      'hub_response' => $subscription['body']
    ]);
  }

  // The hub sends the verification challenge here
  public function get_subscriber(Request $request, $args) {
    session_setup();
    $num = $args['num'];
    $token = $args['token'];

    $hub = ORM::for_table('hubs')->where('token', $token)->find_one();

    if(!$hub) {
      return Response::json(['error'=>'not_found','error_description'=>'No hub was found for this token'], 404);
    }

    $params = $request->query;

    // Verify the hub sent the correct challenge

    if(!isset($params['hub_mode'])) {
      return self::verify_error($token, 'The verification request was missing the hub.mode parameter');
    }

    if(!in_array($params['hub_mode'], ['subscribe','unsubscribe'])) {
      return self::verify_error($token, 'The hub.mode parameter was not set to "subscribe" or "unsubscribe"');
    }

    if(!isset($params['hub_topic'])) {
      return self::verify_error($token, 'The verification request was missing the hub.topic parameter');
    }
    if($params['hub_topic'] != $hub->topic) {
      return self::verify_error($token, 'The hub.topic parameter was incorrect');
    }

    if(!isset($params['hub_challenge'])) {
      return self::verify_error($token, 'The verification request was missing the hub.challenge parameter');
    }

    if($params['hub_mode'] == 'subscribe') {
      if(!isset($params['hub_lease_seconds'])) {
        return self::verify_error($token, 'The verification request was missing the hub.lease_seconds parameter');
      }
    }

    streaming_publish($token, [
      'type' => 'verify_success',
      'description' => 'The hub sent the verification request'
    ]);

    return Response::make(200, (string)$params['hub_challenge'], ['Content-Type' => 'application/octet-stream']);
  }

  private static function verify_error($token, $description) {
    streaming_publish($token, [
      'type' => 'verify_error',
      'description' => $description
    ]);
    return Response::json(['error'=>'bad_request','error_description'=>$description], 404);
  }


  // The hub gets the content of the topic here
  public function get_publisher(Request $request, $args) {
    session_setup();
    $num = $args['num'];
    $token = $args['token'];

    $posts = Feed::get_posts_in_feed($token);

    if(!$posts) {
      return Response::json(['error'=>'no_posts'], 404);
    }

    $hub = ORM::for_table('hubs')->where('token', $token)->find_one();

    if(!$hub) {
      return Response::json(['error'=>'not_found'], 404);
    }

    $self_url = Config::$base.'hub/'.$num.'/pub/'.$token;
    $hub_url = $hub->url;


    $response = Response::make()
      ->withHeader('Link', '<'.$self_url.'>; rel="self"')
      ->withAddedHeader('Link', '<'.$hub_url.'>; rel="hub"');

    switch($num) {
      case 105:
        // Plaintext body
        return $response->withHeader('Content-Type', 'text/plain')
          ->withBody(view('hub/feed-txt', [
            'title' => 'WebSub Rocks! Test '.$num,
            'num' => $num,
            'token' => $token,
            'posts' => raw_posts($posts),
          ]));
      case 106:
        // JSON body
        return $response->withHeader('Content-Type', 'application/json')
          ->withBody(view('hub/feed-json', [
            'title' => 'WebSub Rocks! Test '.$num,
            'num' => $num,
            'token' => $token,
            'posts' => raw_posts($posts),
            'self' => $self_url
          ]));
      default:
        return $response->withBody(page('hub/feed', [
          'title' => 'WebSub Rocks! Test '.$num,
          'num' => $num,
          'token' => $token,
          'post_list' => new Raw(view('subscriber/post-list', ['posts'=>raw_posts($posts), 'num'=>$num])),
          'link_tag' => '',
        ]));
    }
  }

  // For public hubs, the user will trigger a new post be added here
  public function post_publisher(Request $request, $args) {
    session_setup();
    $num = $args['num'];
    $token = $args['token'];

    $hub = ORM::for_table('hubs')->where('token', $token)->find_one();

    if(!$hub) {
      return Response::json(['error'=>'not_found','error_description'=>'No hub was found for this token'], 404);
    }

    $posts = Feed::get_posts_in_feed($token);
    $ids = array_column($posts, 'id');
    $post = ORM::for_table('quotes')
      ->where_not_in('id', $ids)->order_by_expr('RAND()')
      ->limit(1)->find_one();

    // Add a new post to the blog
    $data = Feed::add_post_to_feed($token, $post);

    // Notify the hub of new content
    $http = http_client();
    $http->post($hub->url, http_build_query([
      'hub.mode' => 'publish',
      'hub.topic' => $hub->topic,
    ]));

    return Response::json([
      'result' => 'published'
    ]);
  }

  // a WebSub delivery notification
  public function post_subscriber(Request $request, $args) {
    $response = Response::make();
    session_setup();
    $num = $args['num'];
    $token = $args['token'];

    $hub = ORM::for_table('hubs')->where('token', $token)->find_one();

    if(!$hub) {
      return Response::json([
        'error' => 'not_found',
        'error_description' => 'No hub was found for this token'
      ], 404);
    }

    // Expire subscribers after 15 minutes
    if(strtotime($hub->date_created) < (time()-(60*15))) {
      return Response::json([
        'error' => 'expired',
        'error_description' => 'This subscriber is only active for 15 minutes. You\'ll need to start a new test to continue.'
      ], 404);
    }

    $http = http_client();

    // Fetch the topic URL so we know what the notification should look like
    $topic = $http->get($hub->topic);

    // Check for notification payload
    $notification_body = $request->body;

    if(trim($notification_body) == '') {
      streaming_publish($token, [
        'type' => 'notification',
        'error' => 'empty_payload',
        'description' => 'The notification body did not include any content. Make sure the hub sends the contents of the topic URL in the notification payload. This is known as a "fat ping".'
      ]);
      return $response;
    }

    // Make sure it matches what's expected
    if($hub->publisher == 'remote') {
      // Allow slight differences in the body for remote feeds
      // in order to allow cookie/csrf/other per-request differences
      similar_text($notification_body, $topic['body'], $percent);
      $invalid = $percent < 5;
    } else {
      $invalid = (trim($notification_body) != trim($topic['body']));
    }
    if($invalid) {
      $description = 'The notification body did not match the contents of the topic URL. Length of topic URL: ('.strlen($topic['body']).') Length of notification body: ('.strlen($notification_body).')';

      streaming_publish($token, [
        'type' => 'notification',
        'error' => 'body_mismatch',
        'description' => $description,
      ]);
      return $response;
    }

    $content_type_debug = 'Topic Content-Type: '.$topic['headers']['Content-Type']."\n"
      . "Content-Type sent:  ".$request->header('Content-Type')."\n";

    // Make sure they sent a content type header that matches the source
    if($request->header('Content-Type') != $topic['headers']['Content-Type']) {
      streaming_publish($token, [
        'type' => 'notification',
        'error' => 'content_type_mismatch',
        'description' => 'The content-type of the notification sent did not match the content-type of the topic URL. The hub must send a content-type header that matches the topic URL.',
        'debug' => $content_type_debug
      ]);
      return $response;
    }

    // The notification MUST contain a rel=self and rel=hub header
    $link_header = 'Link: '.$request->header('Link'); // multiple Link headers arrive combined into one
    $parsed_link_headers = IndieWeb\http_rels($link_header);
    if(!isset($parsed_link_headers['hub'])) {
      streaming_publish($token, [
        'type' => 'notification',
        'error' => 'link_header',
        'description' => 'The notification is missing the HTTP Link header with rel=hub indicating the hub this came from.',
      ]);
      return $response;
    }
    if($parsed_link_headers['hub'][0] != $hub->url) {
      streaming_publish($token, [
        'type' => 'notification',
        'error' => 'link_header',
        'description' => 'The HTTP Link header with rel=hub was not set to the correct value.',
        'debug' => $link_header
      ]);
      return $response;
    }
    if(!isset($parsed_link_headers['self'])) {
      streaming_publish($token, [
        'type' => 'notification',
        'error' => 'link_header',
        'description' => 'The notification is missing the HTTP Link header with rel=self indicating the topic URL of this notification.',
      ]);
      return $response;
    }
    if($parsed_link_headers['hub'][0] != $hub->url) {
      streaming_publish($token, [
        'type' => 'notification',
        'error' => 'link_header',
        'description' => 'The HTTP Link header with rel=self was not set to the correct value.',
        'debug' => $link_header
      ]);
      return $response;
    }


    // Check for presence of or absence of signature
    $sent_signature = $request->header('X-Hub-Signature');
    $signature_debug = '';

    if($hub->secret == '') {
      // Make sure the hub did not send a signature
      if($sent_signature) {
        streaming_publish($token, [
          'type' => 'notification',
          'error' => 'signature',
          'description' => 'The hub sent a signature, but the subscriber was not expecting one.'
        ]);
        return $response;
      }
    } else {
      // Check that the hub sent a signature
      if(!$sent_signature) {
        streaming_publish($token, [
          'type' => 'notification',
          'error' => 'signature',
          'description' => 'The hub did not send a signature, but the subscriber sent a secret during the subscription process. Hubs must support sending a signature when the subscription was made with a secret.'
        ]);
        return $response;
      }

      // Compute the signature and make sure it matches what the hub sent
      $verified = p3k\WebSub\Client::verify_signature($notification_body, $sent_signature, $hub->secret);
      $signature_debug = "Signature: ".$sent_signature."\n";
      if(!$verified) {
        streaming_publish($token, [
          'type' => 'notification',
          'error' => 'signature_mismatch',
          'description' => 'The signature sent by the hub did not match what we expected. Check that you are using a valid hashing algorithm and computing the signature correctly.',
          'debug' => $signature_debug
        ]);
        return $response;
      }
    }

    streaming_publish($token, [
      'type' => 'notification',
      'error' => false,
      'description' => 'Great! Your hub sent a valid WebSub notification payload to the subscriber!',
      'debug' => $content_type_debug.$signature_debug
    ]);
    return $response;
  }

}

