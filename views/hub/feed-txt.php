<?= $title."\n" ?>
=============

<? foreach($posts as $i=>$post): ?>
<?= html_entity_decode($post['content']) ?>

by <?= $post['author'] ?>
on <?= date('F j, Y g:ia', strtotime($post['published'])) ?>  

<? endforeach ?>

