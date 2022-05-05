  <?php foreach($posts as $i=>$post): ?>

    <section class="content h-entry" id="quote-<?= $i ?>">
      <div class="e-content p-name"><?= $post['content'] ?></div>
      <div class="p-author h-card"><?= $post['author'] ?></div>
      <a href="#quote-<?= $i ?>" class="u-url">
        <time class="dt-published" datetime="<?= date('c', strtotime($post['published'])) ?>">
        <?= date('F j, Y g:ia', strtotime($post['published'])) ?>
        </time>
      </a>
    </section>
    
  <?php endforeach ?>
