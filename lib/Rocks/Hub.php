<?php
namespace Rocks;

use ORM;
use Config;

class Hub {

  public static $LEASE_SECONDS = 86400;

  public static function publish($num, $token) {

    $subscriptions = ORM::for_table('subscriber_hub')
      ->where('test', $num)->where('token', $token)
      ->where('active', 1)
      ->find_many();

    $delivered = [];

    if(count($subscriptions)) {
      // Render the page content to send to the subscriber
      $html = self::render_page($num, $token);

      foreach($subscriptions as $sub) {
        if(strtotime($sub->date_expires) > time()) {
          if($sub->secret)
            $sig = hash_hmac('sha256', $html, $sub->secret);
          else
            $sig = false;

          $sub->date_last_notified = date('Y-m-d H:i:s');
          $response = self::deliver($num, $token, $sub->callback, $html, $sig);

          // If the subscriber returns a non-2xx response, deactivate the subscription
          // (a real hub should retry for some amount of time)
          if(floor($response['code'] / 100) != 2) {
            $sub->active = 0;
          }

          $sub->notification_response_code = $response['code'];
          $sub->notification_response = $response['debug'];

          $sub->save();

          $delivered[] = [
            'subscriber' => $sub->callback,
            'code' => $response['code']
          ];
        }
      }
    }

    return $delivered;
  }

  public static function verify($num, $token, $mode, $callback, $challenge) {
    $client = new HTTP();
    // build new callback URL with additional query params
    $topic = Config::$base . 'subscriber/' . $num . '/' . $token;
    $params = [
      'hub.mode' => $mode,
      'hub.topic' => $topic,
      'hub.challenge' => $challenge,
      'hub.lease_seconds' => self::$LEASE_SECONDS
    ];
    // Parse the URL
    $callback = add_query_params_to_url($callback, $params);

    return $client->get($callback);
  }

  private static function deliver($num, $token, $callback, $content, $sig) {
    $self = Config::$base . 'subscriber/' . $num . '/' . $token;
    $hub = Config::$base . 'subscriber/' . $num . '/' . $token . '/hub';

    $headers = [
      'Content-Type: text/html',
      'Link: <' . $self . '>; rel="self", <' . $hub . '>; rel="hub"',
    ];

    if($sig) {
      $headers[] = 'X-Hub-Signature: sha256='.$sig;
    }

    $client = new HTTP();
    return $client->post($callback, $content, $headers);
  }

  private static function render_page($num, $token) {
    $response = new \Zend\Diactoros\Response();
    $request = \Zend\Diactoros\ServerRequestFactory::fromGlobals(
      $_SERVER, $_GET, $_POST, $_COOKIE, $_FILES
    );

    $route = new \App\Subscriber();
    $feed = $route->get_feed($request, $response, ['num'=>$num, 'token'=>$token]);
    $html = $feed->getBody();

    return (string)$html;
  }

}
