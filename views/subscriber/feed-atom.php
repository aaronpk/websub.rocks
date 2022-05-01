<?= '<?xml version="1.0" encoding="UTF-8" ?>'."\n" ?>
<?= '<?xml-stylesheet type="text/xsl" href="'.Config::$base.'assets/atom.xsl" ?>'."\n" ?>
<feed xmlns="http://www.w3.org/2005/Atom">
  <title>WebSub Rocks! <?= $name ?></title>
  <link href="<?= $self ?>" rel="self" type="application/atom+xml" />
  <link href="<?= $hub ?>" rel="hub" />
  <id><?= $self ?></id>
  <publishUrl><?= Config::$base ?>subscriber/<?= $num ?>/<?= $token ?>/publish</publishUrl>
  <updated><?= date('c', strtotime($posts[count($posts)-1]['published'])) ?></updated>
  <?= is_logged_in() ? '<author>'.p3k\url\display_url($_SESSION['email']).'</author>' : '' ?>

  <subtitle>This Atom feed has a stylesheet that will make it look like the websub.rocks site. If you are seeing this message, your browser doesn't support XSLT. To add a new post to this feed, follow this link <?= Config::$base ?>subscriber/<?= $num ?>/<?= $token ?>/publish</subtitle>

  <?php foreach($posts as $i=>$post): ?>

  <entry>
    <id><?= $self ?>#quote-<?= $i ?></id>
    <title></title>
    <published><?= date('c', strtotime($post['published'])) ?></published>
    <content type="html"><![CDATA[<?= $post['content'] ?>]]></content>
    <link rel="alternate" type="text/html" href="<?= $self ?>#quote-<?= $i ?>" />
    <author>
      <name><?= $post['author'] ?></name>
    </author>
  </entry>

  <?php endforeach ?>
</feed>
