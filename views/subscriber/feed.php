<?php $this->layout('layout', [
                      'title' => $title,
                    ]); ?>

<div class="single-column">

  <? foreach($posts as $post): ?>
    <section class="content h-entry">

      <div class="e-content p-name"><?= $post['content'] ?></div>
      <div class="p-author h-card"><?= $post['author'] ?></div>
      <time class="dt-published" datetime="<?= date('c', strtotime($post['published'])) ?>">
        <?= date('F j, Y g:ia', strtotime($post['published'])) ?>
      </time>

    </section>
    
  <? endforeach ?>

</div>
