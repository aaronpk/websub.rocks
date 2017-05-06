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
        <td>Subscribing to a hub that sends a 302 temporary redirect</td>
      </tr>
      <tr>
        <td></td>
        <td><a href="/subscriber/204">204</a></td>
        <td>Subscribing to a hub that sends a 301 permanent redirect</td>
      </tr>
      <tr>
        <td></td>
        <td><a href="/subscriber/205">205</a></td>
        <td>Rejects a verification request with an invalid topic URL</td>
      </tr>
    </table>

    <!-- 
    <h3>Error Handling</h3>
    <ul>
      <li><a href="/subscriber/200">200</a> - Reject invalid topic URLs on subscription validation</li>
      <li><a href="/subscriber/201">201</a> - Reject invalid signatures for authenticated distribution</li>
      <li><a href="/subscriber/202">202</a> - Reject missing signature for authenticated distribution</li>
    </ul>
    -->

  </section>

</div>
