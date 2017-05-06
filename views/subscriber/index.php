<?php $this->layout('layout', [
                      'title' => $title,
                    ]); ?>

<div class="single-column">

  <section class="content">
    <h2>Testing your Subscriber</h2>

    <h3>Discovery</h3>
    <ul>
      <li><a href="/subscriber/100">100</a> - HTTP header discovery</li>
      <li><a href="/subscriber/101">101</a> - HTML tag discovery</li>
      <li><a href="/subscriber/102">102</a> - Atom feed discovery</li>
      <li><a href="/subscriber/103">103</a> - RSS feed discovery</li>
      <li><a href="/subscriber/104">104</a> - Discovery priority</li>
    </ul>

    <h3>Subscribing</h3>
    <ul>
      <li><a href="/subscriber/200">200</a> - Subscribing to a URL that reports a different rel=self</li>
      <li><a href="/subscriber/201">201</a> - Subscribing to a temporarily redirected topic</li>
      <li><a href="/subscriber/202">202</a> - Subscribing to a permanently redirected topic</li>
      <li><a href="/subscriber/203">203</a> - Subscribing to a temporarily redirected hub</li>
      <li><a href="/subscriber/204">204</a> - Subscribing to a permanently redirected hub</li>
      <li>more soon...</li>
      <!-- 
      <li><a href="/subscriber/103">103</a> - Test unsubscribing</li>
      -->
    </ul>

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
