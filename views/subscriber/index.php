<?php $this->layout('layout', [
                      'title' => $title,
                    ]); ?>

<div class="single-column">

  <section class="content">
    <h2>Testing your Subscriber</h2>

    <h3>Discovery</h3>
    <table class="ui compact table">
      <tr>
        <td width="40"></td>
        <td width="50"><a href="/subscriber/100">100</a></td>
        <td>HTTP header discovery</td>
      </tr>
      <tr>
        <td></td>
        <td><a href="/subscriber/101">101</a></td>
        <td>HTML tag discovery</td>
      </tr>
      <tr>
        <td></td>
        <td><a href="/subscriber/102">102</a></td>
        <td>Atom feed discovery</td>
      </tr>
      <tr>
        <td></td>
        <td><a href="/subscriber/103">103</a></td>
        <td>RSS feed discovery</td>
      </tr>
      <tr>
        <td></td>
        <td><a href="/subscriber/104">104</a></td>
        <td>Discovery priority</td>
      </tr>
    </table>

    <h3>Subscribing</h3>
    <table class="ui compact table">
      <tr>
        <td width="40"></td>
        <td width="50"><a href="/subscriber/200">200</a></td>
        <td>Subscribing to a URL that reports a different rel=self</td>
      </tr>
      <tr>
        <td></td>
        <td><a href="/subscriber/201">201</a></td>
        <td>Subscribing to a topic URL that sends an HTTP 302 temporary redirect</td>
      </tr>
      <tr>
        <td></td>
        <td><a href="/subscriber/202">202</a></td>
        <td>Subscribing to a topic URL that sends an HTTP 301 permanent redirect</td>
      </tr>
      <tr>
        <td></td>
        <td><a href="/subscriber/203">203</a></td>
        <td>Subscribing to a hub that sends a 307 temporary redirect</td>
      </tr>
      <tr>
        <td></td>
        <td><a href="/subscriber/204">204</a></td>
        <td>Subscribing to a hub that sends a 308 permanent redirect</td>
      </tr>
      <tr>
        <td></td>
        <td><a href="/subscriber/205">205</a></td>
        <td>Rejects a verification request with an invalid topic URL</td>
      </tr>
    </table>

    <h3>Distribution</h3>
    <table class="ui compact table">
      <tr>
        <td width="40"></td>
        <td width="50"><a href="/subscriber/300">300</a></td>
        <td>Returns HTTP 2xx when the notification payload is delivered</td>
      </tr>
      <tr>
        <td></td>
        <td><a href="/subscriber/301">301</a></td>
        <td>Rejects a distribution request with an invalid signature</td>
      </tr>
      <tr>
        <td></td>
        <td><a href="/subscriber/302">302</a></td>
        <td>Rejects a distribution request with no signature when the subscription was made with a secret</td>
      </tr>
    </table>
    <p>Note: 301 and 302 only apply if your subscriber includes a secret with the subscription request.</p>

  </section>

</div>
