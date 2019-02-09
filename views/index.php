<?php $this->layout('layout', ['title' => $title]); ?>

<div class="single-column">
  <div id="header-graphic"><img src="/assets/websub-rocks.png"></div>

  <section class="content">
    <h3>About this site</h3>
    <p><b><i>WebSub Rocks!</i></b> is a validator to help you test your <a href="https://www.w3.org/TR/websub/">WebSub</a> implementation. Several kinds of tests are available on the site.</p>
  </section>

  <? if(p3k\flash('login')): ?>
    <div class="ui success message">
      <div class="header">Welcome!</div>
      <p>You are logged in as <?= $_SESSION['email'] ?>!</p>
    </div>
  <? endif; ?>

  <? if(!is_logged_in()): ?>
    <section class="content">
      <h3>Sign in to begin</h3>

      <form action="/auth/start" method="POST">
        <div class="ui fluid action input">
          <input type="email" name="email" placeholder="you@example.com">
          <button class="ui button">Sign In</button>
          <input type="hidden" name="galaxy" value="cheese">
        </div>
      </form>

      <p>You will receive an email with a link to sign in.</p>
    </section>
  <? endif; ?>

  <section class="content">
    <h2>Roles</h2>

    <h3><a href="/publisher">Testing your Publisher</a></h3>
    <p>This section contains tests for your publisher. The WebSub spec doesn't place many requirements on publishers other than how the hub URL is discovered. This test will ensure you are advertising the hub and self URLs properly depending on the content type. It will look in the body of HTML, Atom and RSS documents, and for all other content types will use the HTTP headers.</p>

    <h3><a href="/subscriber">Testing your Subscriber</a></h3>
    <p>There are a few things to keep in mind when writing a WebSub subscriber. This section contains several tests for discovery, subscribing, and distribution. The tests check whether you are able to discover the URLs for various content types, and how your subscriber handles things when URLs are redirected. It also contains tests to make sure the subscriber rejects distribution requests with an invalid signature.</p>
    <p>Your subscriber needs to pass only the tests for the content types it is interested in, and does not need to pass the signature tests unless your subscriber uses a secret.</p>

    <h3><a href="/hub">Testing your Hub</a></h3>
    <p>If you are writing a hub, whether a standalone or integrated hub, you'll want to test it with the tests in this section. There are several requirements placed on hubs that these tests will help you cover.</p>
    <p>If you are writing a standalone open hub, you can enter the hub URL in the tests and a temporary blog will be created that points to your hub. If you are writing an integrated hub, then you can provide a URL that advertises your hub to test it, in which case you'll need to trigger adding new posts manually.</p>
  </section>

  <section class="content">
    <h2>Implementation Reports</h2>

    <p>As you're working through the websub.rocks tests, please fill out an implementation report to update the W3C working group about your implementation!</p>
    <p>It isn't too hard to do. Just fork the WebSub repo, and copy the appropriate <a href="https://github.com/w3c/websub/tree/master/implementation-reports">implementation report template</a> to a new file, check off the boxes that apply to you, then submit a pull request.</p>
  </section>

  <section class="content small">
    <p>This code is <a href="https://github.com/aaronpk/websub.rocks">open source</a>. Feel free to <a href="https://github.com/aaronpk/websub.rocks/issues">file an issue</a> if you notice any errors.</p>
  </section>

</div>
