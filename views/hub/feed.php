<?php $this->layout('layout', [
                      'title' => $title,
                      'link_tag' => isset($link_tag) ? $link_tag : ''
                    ]); ?>

<div class="single-column">

  <div id="subscriber-post-list" class="h-feed">
    <span class="p-name hidden">WebSub.rocks Test <?= $num ?></span>
    <?php $this->insert('subscriber/post-list', ['posts'=>$posts, 'num'=>$num]) ?>
  </div>

</div>
