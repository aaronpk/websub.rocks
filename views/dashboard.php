<?php $this->layout('layout', [
                      'title' => $title,
                    ]); ?>

<div class="single-column">

  <? if(flash('login')): ?>
    <div class="ui success message">
      <div class="header">Welcome!</div>
      <p>You are logged in as <?= $_SESSION['email'] ?>!</p>
    </div>
  <? endif; ?>

</div>
