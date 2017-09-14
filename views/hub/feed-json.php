<?
$feed = [
  'title' => $title,
  'home_page_url' => Config::$base,
  'feed_url' => $self,
  'items' => []
];

foreach($posts as $i=>$post) {
  $feed['items'][] = [
    'id' => $i,
    'content_text' => html_entity_decode($post['content']),
    'url' => $self.'#'.$i,
    'date_published' => date('c', strtotime($post['published'])),
    'author' => [
      'name' => $post['author'],
    ]
  ];
}

echo json_encode($feed, JSON_PRETTY_PRINT);
