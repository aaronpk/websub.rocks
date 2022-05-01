<?php $this->layout('layout', [
                      'title' => $title,
                      'link_tag' => isset($link_tag) ? $link_tag : ''
                    ]); ?>

<div class="single-column">

  <?php if(is_logged_in()): ?>
    <section class="content">
      <p>Congrats! Now that your subscription is active, you can generate new posts that will be delivered to your subscriber! Click the button below to add a new post to this feed, and send a notification to subscribers of this feed.</p>

      <a href="/subscriber/<?= $num ?>/<?= $token ?>/publish" class="ui blue button" id="subscriber-new-post-btn">Create New Post</a>
    </section>
  <?php endif; ?>

  <div id="subscriber-post-list" class="h-feed">
    <span class="p-name hidden">WebSub.rocks Test <?= $num ?></span>
    <?php $this->insert('subscriber/post-list', ['posts'=>$posts, 'num'=>$num]) ?>
  </div>

</div>
