<?php
namespace Rocks;
use p3k;
use ORM;

class Feed {

  public static function set_up_posts_in_feed($token) {
    $key = 'websub.rocks::feed::'.$token;
    if(p3k\redis()->llen($key) == 0) {
      $quotes = ORM::for_table('quotes')->order_by_expr('RAND()')->limit(3)->find_many();
      foreach($quotes as $quote) {
        self::add_post_to_feed($token, $quote);
      }
    }
    self::touch_feed($token);
  }

  // The subscriber's hub requests come from the user's server, not their
  // browser, so remember who opened the test page to credit them with the
  // subscription it creates. A week is plenty to finish a test.
  public static function set_owner($token, $user_id) {
    p3k\redis()->setex('websub.rocks::owner::'.$token, 86400*7, $user_id);
  }

  public static function owner($token) {
    $user_id = p3k\redis()->get('websub.rocks::owner::'.$token);
    return $user_id ? (int)$user_id : null;
  }

  public static function touch_feed($token) {
    $key = 'websub.rocks::feed::'.$token;
    p3k\redis()->expire($key, 86400);
  }

  public static function add_post_to_feed($token, $post) {
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

  public static function get_posts_in_feed($token) {
    $key = 'websub.rocks::feed::'.$token;
    $len = p3k\redis()->llen($key);
    $items = p3k\redis()->lrange($key, 0, $len-1);
    return array_map(function($i){ return json_decode($i, true); }, $items);
  }

}
