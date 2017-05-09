<?php $this->layout('layout', [
                      'title' => $title,
                    ]); ?>

<div class="single-column">

  <section class="content">
    <h2>Testing your Hub</h2>

    <p>A hub is responsible for handling subscribe requests, and delivering notifications to the subscribers when the publisher updates the page.</p>

    <p>There are several options clients have when subscribing to the hub. Subscribers may want to receive signed notifications, and may request a specific subscription duration.</p>

    <ul>
      <li><a href="/hub/100">100</a> - Typical subscriber request</li>
      <li><a href="/hub/101">101</a> - Subscriber includes a secret</li>
      <li><a href="/hub/102">102</a> - Subscriber sends additional parameters</li>
      <li>more soon...</li>
      <!-- 
      <li><a href="/hub/103">103</a> - Subscriber re-subscribes before subscription expires</li>
      <li><a href="/hub/104">104</a> - Unsubscribe request</li>
      -->
    </ul>


  </section>

</div>
