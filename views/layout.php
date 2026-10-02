<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">

  <title><?= $title ?></title>
  <link href="/assets/semantic.min.css" rel="stylesheet">
  <link href="/assets/style.css" rel="stylesheet">

  <script src="/assets/jquery-1.11.3.min.js"></script>
  <script src="/assets/semantic.min.js"></script>
  <script src="/assets/common.js"></script>
  <script src="/assets/passkey.js"></script>

  <?= $link_tag ?>

</head>
<body<?= is_logged_in() ? ' class="logged-in"' : '' ?>>

<div class="ui top fixed menu">
  <a class="item" href="/"><img src="/assets/websub-rocks-icon.png"></a>
  <a class="item" href="/">Home</a>
  <a class="item" href="/publisher">Publisher</a>
  <a class="item" href="/subscriber">Subscriber</a>
  <a class="item" href="/hub">Hub</a>
  <?php if(is_logged_in()): ?>
    <div class="right menu">
      <a class="item" href="/account"><?= e(p3k\url\display_url($_SESSION['email'])) ?></a>
      <a class="item" href="/auth/signout">Sign Out</a>
    </div>
  <?php endif; ?>
</div>

<?= $content ?>

</body>
</html>
