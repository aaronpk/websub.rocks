<?php
namespace Rocks;

use ORM;

class Quote {

  public static function generateNewQuote() {
    // Attempt to fetch a new quote from an external source, and if that fails, return one that's cached
    $sources = [];
    $sources[] = [
      'url' => 'http://api.forismatic.com/api/1.0/?method=getQuote&key=457653&format=json&lang=en',
      'content' => 'quoteText',
      'author' => 'quoteAuthor',
      'original_url' => 'quoteLink',
      'container' => false
    ];
    $sources[] = [
      'url' => 'http://quotesondesign.com/wp-json/posts?filter[orderby]=rand&filter[posts_per_page]=1',
      'content' => 'content',
      'author' => 'title',
      'original_url' => 'link',
      'container' => 'array'
    ];
    shuffle($sources);

    $source = $sources[0];

    $ch = curl_init($source['url']);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 1);
    curl_setopt($ch, CURLOPT_TIMEOUT, 3);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
      'User-Agent: Mozilla/5.0 (Macintosh; Intel Mac OS X 10_11_6) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/54.0.2840.59 Safari/537.36'
    ]);
    $data = curl_exec($ch);

    $content = false;
    $author = false;
    $url = false;

    if($data) {
      $data = @json_decode($data, true);
      if($data) {
        if($source['container'] == 'array')
          $data = $data[0];
        if(array_key_exists($source['content'], $data))
          $content = $data[$source['content']];
        if(array_key_exists($source['author'], $data))
          $author = $data[$source['author']];
        if(array_key_exists($source['original_url'], $data))
          $url = $data[$source['original_url']];
      }
    }

    if($content && $author && $url) {
      $quote = ORM::for_table('quotes')->where('original_url', $url)->find_one();
      if(!$quote) {
        $quote = ORM::for_table('quotes')->create();
        $quote->author = $author;
        $quote->content = trim(strip_tags($content));
        $quote->original_url = $url;
        $quote->save();
      }
    } else {
      // Getting a new quote failed, so return a cached one
      $quote = ORM::for_table('quotes')->order_by_expr('RAND()')->find_one();
    }

    return $quote;
  }

}
