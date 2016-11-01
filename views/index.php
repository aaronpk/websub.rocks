<?php $this->layout('layout', ['title' => $title]); ?>

<div class="single-column">
  <div id="header-graphic"><img src="/assets/pubsub-rocks.png"></div>

  <section class="content">
    <h3>About this site</h3>
    <p><b><i>PubSub Rocks!</i></b> is a validator to help you test your <a href="https://www.w3.org/TR/pubsub/">PubSub</a> implementation. Several kinds of tests are available on the site.</p>
  </section>

  <section class="content">
  <? if(!is_logged_in()): ?>
    <h3>Sign in to begin</h3>

    <form action="/auth/start" method="POST">
      <div class="ui fluid action input">
        <input type="email" name="email" placeholder="you@example.com">
        <button class="ui button">Sign In</button>
      </div>
    </form>

    <p>You will receive an email with a link to sign in.</p>

  <? else: ?>
    <h3>Welcome!</h3>
    <p>You are already signed in.</p>
    <p><a href="/dashboard" class="ui button">Continue</a></p>
  <? endif; ?>
  </section>

  <section class="content small">
    <p>This code is <a href="https://github.com/aaronpk/pubsub.rocks">open source</a>. Feel free to <a href="https://github.com/aaronpk/pubsub.rocks/issues">file an issue</a> if you notice any errors.</p>
  </section>

</div>
