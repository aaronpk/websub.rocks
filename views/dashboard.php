<?php $this->layout('layout', [
                      'title' => $title,
                    ]); ?>

<div class="single-column">

  <? if(p3k\flash('login')): ?>
    <div class="ui success message">
      <div class="header">Welcome!</div>
      <p>You are logged in as <?= $_SESSION['email'] ?>!</p>
    </div>
  <? endif; ?>

  <section class="content">
    <h2>Roles</h2>

    <ul>
      <li><a href="/publisher">Testing your Publisher</a></li>
      <li><a href="/subscriber">Testing your Subscriber</a></li>
      <li><a href="/hub">Testing your Hub</a></li>
    </ul>
  </section>

</div>
