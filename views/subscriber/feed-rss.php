<?= '<?xml version="1.0" encoding="UTF-8" ?>'."\n" ?>
<?= '<?xml-stylesheet type="text/xsl" href="'.Config::$base.'assets/rss.xsl" ?>'."\n" ?>
<rss version="2.0"
  xmlns:content="http://purl.org/rss/1.0/modules/content/"
  xmlns:atom="http://www.w3.org/2005/Atom"
  >
<channel>
  <title>WebSub Rocks! <?= $name ?></title>
  <atom:link href="<?= $self ?>" rel="self" type="application/rss+xml" />
  <atom:link href="<?= $hub ?>" rel="hub" />
  <link><?= $self ?></link>
  <publishUrl><?= Config::$base ?>subscriber/<?= $num ?>/<?= $token ?>/publish</publishUrl>
  <lastBuildDate><?= date('r', strtotime($posts[count($posts)-1]['published'])) ?></lastBuildDate>
  <language>en-US</language>
  <?= is_logged_in() ? '<author>'.p3k\url\display_url($_SESSION['email']).'</author>' : '' ?>

  <description>This RSS feed has a stylesheet that will make it look like the websub.rocks site. If you are seeing this message, your browser doesn't support XSLT. To add a new post to this feed, follow this link <?= Config::$base ?>subscriber/<?= $num ?>/<?= $token ?>/publish</description>

  <?php foreach($posts as $i=>$post): ?>

    <item>
      <title></title>
      <pubDate><?= date('r', strtotime($post['published'])) ?></pubDate>
      <guid isPermaLink="true"><?= $self ?>#quote-<?= $i ?></guid>
      <description><![CDATA[<?= $post['content'] ?>]]></description>
      <link><?= $self ?>#quote-<?= $i ?></link>
      <author><?= $post['author'] ?></author>
    </item>
    
  <?php endforeach ?>
</channel>
</rss>