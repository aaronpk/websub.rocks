<?php $this->layout('layout', [
                      'title' => $title,
                    ]); ?>

<div class="single-column">

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

</div>
