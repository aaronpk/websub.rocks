<?php $this->layout('layout', [
                      'title' => $title,
                    ]); ?>

<div class="single-column">

  <section id="step-1" class="content">
    <h2><?= $num ?>: <?= $name ?></h2>

    <div class="ui top attached tabular menu">
      <a class="item active" data-tab="first">Public Hub</a>
      <?php if(!in_array($num, [105,106])): ?>
      <a class="item" data-tab="second">Integrated Hub</a>
      <?php endif; ?>
    </div>
    <div class="ui bottom attached tab segment active" data-tab="first">
      <?php if(!in_array($num, [105,106])): ?>
      <p>If your hub is a "public hub", e.g. it is a standalone service that can be used by any publisher, then you can use this option. If your hub is integrated with the publishing software, choose the other tab.</p>
      <?php endif; ?>
      <p>Provide the URL of your hub below. Once you do so, a sample blog will be generated that points to your hub, and this test will attempt to subscribe to updates about the blog from your hub.</p>
      <p>Make sure your hub allows subscriptions for this domain. (Some hubs will require registration before a new domain is allowed to use them.)</p>

      <div class="ui form">
        <label>Your Hub URL</label>
        <div class="ui fluid action input">
          <input type="url" id="hub-url">
          <button class="ui button" type="submit" id="start-test-from-hub">Start</button>
        </div>
      </div>
    </div>
    <?php if(!in_array($num, [105,106])): ?>
    <div class="ui bottom attached tab segment" data-tab="second">
      <p>Enter a topic URL that advertises its hub as the hub you want to test. You will be responsible for triggering the hub to send notifications to the subscriber.</p>
      <div class="ui form">
        <label>Your Topic URL</label>
        <div class="ui fluid action input">
          <input type="url" id="topic-url">
          <button class="ui button" type="submit" id="start-test-from-topic">Start</button>
        </div>
      </div>
    </div>
    <?php endif; ?>

    <br>

    <p><?= $description ?></p>
  </section>

  <section id="step-2" class="hidden content">
    <div class="ui message hidden" id="step-2-result">
      <h3></h3>
      <p></p>
    </div>

    <h3>The raw response from the subscription request is below</h3>
    <pre class="debug"></pre>
  </section>

  <section class="content hidden" id="waiting-for-subscription">
    <div class="ui active indeterminate centered inline text loader">Waiting for subscription to be confirmed</div>
  </section>

  <section id="step-verify" class="hidden content">
    <div class="ui message" id="step-verify-result">
      <h3></h3>
      <p></p>
    </div>

    <div class="hidden" id="continue-remote-publisher">
      <p>Now that websub.rocks is subscribed to your hub, we can test the delivery of the WebSub notification.</p>
      <p>Since you entered your own topic URL, you should now create a new post at that topic and trigger your hub to deliver notifications to all subscribers.</p>
      <p>When this subscriber receives a notification, you will see the results below.</p>
    </div>
    <div class="hidden" id="continue-local-publisher">
      <p>Now that websub.rocks is subscribed to your hub, we can test the delivery of the WebSub notification.</p>
      <p>Click the button below to create a new post at the test topic URL. This will send a POST request to your hub notifying that there is new content.</p>
      <p>The POST request will contain the following parameters:</p>
      <ul>
        <li><code>hub.mode=publish</code></li>
        <li><code>hub.topic=&lt;the topic URL for this test&gt;</code></li>
      </ul>
      <p>Upon receiving this request, the hub should fetch the topic URL, and deliver the contents to the subscriber.</p>
      <button class="ui button" id="publish-new-post">Create Post</button>
    </div>
  </section>

  <section class="content hidden" id="waiting-for-notification">
    <div class="ui active indeterminate centered inline text loader">Waiting for notification</div>
  </section>

  <section class="content hidden" id="notification">
    <div class="ui message">
      <h3></h3>
      <p></p>
    </div>
    <pre class="debug"></pre>
  </section>

  <?php if($num == 103): ?>

    <section class="content hidden" id="continue-resubscribe">
      <p>To continue the test, click the "re-subscribe" button below. This will cause this subscriber to subscribe to the hub again, before the subscription has expired.</p>

      <button class="ui button" id="btn-resubscribe">Re-Subscribe</button>
    </section>

    <section class="content hidden" id="resubscribe-result">
      <div class="ui message hidden">
        <h3></h3>
        <p></p>
      </div>

      <h3>The raw response from the subscription request is below</h3>
      <pre class="debug"></pre>
    </section>

    <section class="content hidden" id="waiting-for-resubscription">
      <div class="ui active indeterminate centered inline text loader">Waiting for subscription to be confirmed</div>
    </section>

    <section id="step-resubscribe-confirmed" class="hidden content">
      <div class="ui message">
        <h3></h3>
        <p></p>
      </div>

      <div class="hidden" id="continue-resubscribe-remote-publisher">
        <p>Now that websub.rocks has re-subscribed to your hub, we will check to make sure it receives just one notification when you make a new post.</p>
        <p>Publish a new post and trigger your hub to send a notification.</p>
      </div>
      <div class="hidden" id="continue-resubscribe-local-publisher">
        <p>Now that websub.rocks has re-subscribed to your hub, we will check to make sure it receives just one notification when you make a new post.</p>
        <p>Click the button below to create a new post and trigger your hub to send out notifications.</p>
        <button class="ui button" id="resubscribe-publish-new-post">Create Post</button>
      </div>
    </section>

    <section class="content hidden" id="resubscribe-notification">
      <div class="ui message">
        <h3></h3>
        <p></p>
      </div>
      <pre class="debug"></pre>
    </section>

  <?php endif ?>

  <?php if($num == 104): ?>

    <section class="content hidden" id="continue-unsubscribe">
      <p>To continue the test, click the "unsubscribe" button below. This will cause this subscriber to request an unsubscription from the hub.</p>

      <button class="ui button" id="btn-unsubscribe">Unsubscribe</button>
    </section>

    <section class="content hidden" id="unsubscribe-result">
      <div class="ui message hidden">
        <h3></h3>
        <p></p>
      </div>

      <h3>The raw response from the unsubscription request is below</h3>
      <pre class="debug"></pre>
    </section>

    <section class="content hidden" id="waiting-for-unsubscription">
      <div class="ui active indeterminate centered inline text loader">Waiting for unsubscription to be confirmed</div>
    </section>

    <section id="step-unsubscribe-confirmed" class="hidden content">
      <div class="ui message">
        <h3></h3>
        <p></p>
      </div>

      <div class="hidden" id="continue-unsubscribe-remote-publisher">
        <p>Now that websub.rocks has unsubscribed from your hub, we will check to make sure it does not receive further notifications when you make a new post.</p>
        <p>Publish a new post and trigger your hub to send notifications to any remaining subscribers. You should <b>not</b> see a notification appear below.</p>
      </div>
      <div class="hidden" id="continue-unsubscribe-local-publisher">
        <p>Now that websub.rocks has unsubscribed from your hub, we will check to make sure it does not receive further notifications when you make a new post.</p>
        <p>Click the button below to create a new post and trigger your hub to send out notifications to any remaining subscribers. You should <b>not</b> see a notification appear below.</p>
        <button class="ui button" id="unsubscribe-publish-new-post">Create Post</button>
      </div>
    </section>

    <section class="content hidden" id="unsubscribe-notification">
      <div class="ui message">
        <h3></h3>
        <p></p>
      </div>
      <pre class="debug"></pre>
    </section>

    <section class="content hidden" id="unsubscribe-success">
      <div class="ui success message">
        <h3>Success!</h3>
        <p>It looks like your hub has correctly removed the subscription, since we didn't get a notification after waiting a while!</p>
      </div>
    </section>

  <?php endif ?>

  <div id="bottom"></div>
</div>
<script>
var test=<?= $num ?>;
var token;
var socket;
var publisher;

var subscribed = false;

var resubscribe_notifications = 0;
var unsubscribe_notifications = 0;

$(function(){
  $(".menu .item").tab();
  $("#start-test-from-topic").click(function(){
    publisher = "remote";
    $("#start-test-from-topic").addClass("loading");
    $.post("/hub/"+test+"/start", {
      topic: $("#topic-url").val()
    }, function(data){
      $("#start-test-from-topic").removeClass("loading");
      handle_start_response(data);
    });
  });
  $("#start-test-from-hub").click(function(){
    publisher = "local";
    $("#start-test-from-hub").addClass("loading");
    $.post("/hub/"+test+"/start", {
      hub: $("#hub-url").val()
    }, function(data){
      $("#start-test-from-hub").removeClass("loading");
      handle_start_response(data);
    });
  });
});

function handle_start_response(data) {
  token = data.token;
  socket = new EventSource('/streaming/sub?id='+token);

  socket.onmessage = function(event) {
    var data = JSON.parse(event.data);
    switch(data.text.type) {
      case 'verify_success':
        // For test 103, we want to do something different when the second subscription is confirmed
        <?php if($num == 103): ?>
        if(subscribed) {
          $("#step-resubscribe-confirmed .message h3").text("Subscription Confirmed!");
          $("#step-resubscribe-confirmed .message p").text(data.text.description);
          $("#step-resubscribe-confirmed .message").addClass("success");
          $("#step-resubscribe-confirmed").removeClass("hidden");
          $("#waiting-for-resubscription").addClass("hidden");
          subscribed = true;
          continue_resubscribe_publishing();
        } else
        <?php elseif($num == 104): ?>
        if(subscribed) {
          $("#step-unsubscribe-confirmed .message h3").text("Unsubscription Confirmed!");
          $("#step-unsubscribe-confirmed .message p").text(data.text.description);
          $("#step-unsubscribe-confirmed .message").addClass("success");
          $("#step-unsubscribe-confirmed").removeClass("hidden");
          $("#waiting-for-unsubscription").addClass("hidden");
          subscribed = true;
          continue_unsubscribe_publishing();
        } else
        <?php endif ?>
        {
          $("#step-verify-result h3").text("Subscription Confirmed!");
          $("#step-verify-result p").text(data.text.description);
          $("#step-verify-result").addClass("success");
          $("#step-verify").removeClass("hidden");
          $("#waiting-for-subscription").addClass("hidden");
          subscribed = true;
          continue_publishing();
        }
        break;
      case 'verify_error': 
        <?php if($num == 103): ?>
        if(subscribed) {
          $("#step-resubscribe-confirmed .message h3").text("Error!");
          $("#step-resubscribe-confirmed .message p").text(data.text.description);
          $("#step-resubscribe-confirmed .message").addClass("error");
          $("#step-resubscribe-confirmed").removeClass("hidden");
          $("#waiting-for-subscription").addClass("hidden");
        } else
        <?php elseif($num == 104): ?>
        if(subscribed) {
          $("#step-unsubscribe-confirmed .message h3").text("Error!");
          $("#step-unsubscribe-confirmed .message p").text(data.text.description);
          $("#step-unsubscribe-confirmed .message").addClass("error");
          $("#step-unsubscribe-confirmed").removeClass("hidden");
          $("#waiting-for-subscription").addClass("hidden");
        } else
        <?php endif ?>
        {
          $("#step-verify-result h3").text("Error!");
          $("#step-verify-result p").text(data.text.description);
          $("#step-verify-result").addClass("error");
          $("#step-verify").removeClass("hidden");
          $("#waiting-for-subscription").addClass("hidden");
        }
        break;
      case 'notification':
        // For test 103, the second notification does something different
        <?php if($num == 103): ?>
        if(!$("#step-resubscribe-confirmed").hasClass("hidden")) {
          if(resubscribe_notifications != 0) {
            // only one notification should be received. show an error if more than one was received.
            $("#resubscribe-notification .message").addClass("error").removeClass("success");
            $("#resubscribe-notification .message h3").text('Error');
            $("#resubscribe-notification .message p").text("A second notification was received. This likely indicates that your hub created a second subscription rather than updating the existing subscription.");
          } else if(data.text.error) {
            $("#resubscribe-notification .message").addClass("error").removeClass("success");
            $("#resubscribe-notification .message h3").text('Error');
            $("#resubscribe-notification .message p").text(data.text.description);
          } else {
            resubscribe_notifications++;
            $("#resubscribe-notification .message").addClass("success").removeClass("error");
            $("#resubscribe-notification .message h3").text('Success');
            $("#resubscribe-notification .message p").text(data.text.description);
            $("#waiting-for-notification").addClass("hidden");
          }
          $("#resubscribe-notification").removeClass("hidden");
          if(data.text.debug) {
            $("#resubscribe-notification pre").removeClass("hidden").text(data.text.debug);
          } else {
            $("#resubscribe-notification pre").addClass("hidden");
          }
        } else 
        <?php elseif($num == 104): ?>
        if(!$("#step-unsubscribe-confirmed").hasClass("hidden")) {
          // If any followup notification is received, they failed 
          $("#unsubscribe-notification .message").addClass("error").removeClass("success");
          $("#unsubscribe-notification .message h3").text('Error');
          $("#unsubscribe-notification .message p").text('A notification was received, but this subscriber attempted to unsubscribe from the topic. Make sure your hub properly deactivates the subscription.');
        } else
        <?php endif ?>
        {
          // a WebSub notification was received
          // show success/error messages
          if(data.text.error) {
            $("#notification .message").addClass("error").removeClass("success");
            $("#notification .message h3").text('Error');
            $("#notification .message p").text(data.text.description);
          } else {
            $("#notification .message").addClass("success").removeClass("error");
            $("#notification .message h3").text('Success');
            $("#notification .message p").text(data.text.description);
            $("#waiting-for-notification").addClass("hidden");
          }
          $("#notification").removeClass("hidden");
          if(data.text.debug) {
            $("#notification pre").removeClass("hidden").text(data.text.debug);
          } else {
            $("#notification pre").addClass("hidden");
          }
          <?php if($num == 103): ?>
          $("#continue-resubscribe").removeClass("hidden");
          <?php elseif($num == 104): ?>
          $("#continue-unsubscribe").removeClass("hidden");
          <?php endif; ?>
        }
        scroll_to_bottom();
        break;
    }
  }

  $.post("/hub/"+test+"/subscribe", {
    token: token,
    action: "subscribe"
  }, handle_subscribe_response);
}

function scroll_to_bottom() {
  document.getElementById("bottom").scrollIntoView();  
}

function handle_subscribe_response(data) {
  $("#waiting-for-subscription").removeClass("hidden");

  $("#step-2-result h3").text(data.result);
  $("#step-2-result p").text(data.description);
  if(data.status == 'error') {
    $("#step-2-result").addClass("error");
  } else {
    $("#step-2-result").addClass("success");
    $("#step-1").addClass("hidden");
  }
  $("#step-2-result").removeClass("hidden");

  $("#step-2 pre").text(data.hub_response);
  $("#step-2").removeClass("hidden");
}

function continue_publishing() {
  if(publisher == "remote") {
    $("#continue-remote-publisher").removeClass("hidden");
    $("#waiting-for-notification").removeClass("hidden");
  } else {
    $("#continue-local-publisher").removeClass("hidden");
  }
}

function continue_resubscribe_publishing() {
  if(publisher == "remote") {
    $("#continue-resubscribe-remote-publisher").removeClass("hidden");
    waiting = $("#waiting-for-notification").detach();
    $("#step-resubscribe-confirmed").after(waiting);
    $("#waiting-for-notification").removeClass("hidden");
  } else {
    $("#continue-resubscribe-local-publisher").removeClass("hidden");
  }
  scroll_to_bottom();
}

function continue_unsubscribe_publishing() {
  if(publisher == "remote") {
    $("#continue-unsubscribe-remote-publisher").removeClass("hidden");
    waiting = $("#waiting-for-notification").detach();
    $("#step-unsubscribe-confirmed").after(waiting);
    $("#waiting-for-notification").removeClass("hidden");
    wait_for_unsubscribe_success();
  } else {
    $("#continue-unsubscribe-local-publisher").removeClass("hidden");
  }
  scroll_to_bottom();
}

$("#publish-new-post").click(function(){
  $("#publish-new-post").addClass("loading");
  scroll_to_bottom();
  $.post("/hub/"+test+"/pub/"+token, {
    action: "create"
  }, function(data){
    $("#publish-new-post").removeClass("loading");
    $("#waiting-for-notification").removeClass("hidden");
    scroll_to_bottom();
  });
});

$("#btn-resubscribe").click(function(){
  $("#btn-resubscribe").addClass("loading");
  $.post("/hub/"+test+"/subscribe", {
    token: token,
    action: "resubscribe"
  }, function(data){
    $("#continue-resubscribe").addClass("hidden");
    $("#waiting-for-resubscription").removeClass("hidden");

    $("#resubscribe-result .message h3").text(data.result);
    $("#resubscribe-result .message p").text(data.description);
    if(data.status == 'error') {
      $("#resubscribe-result .message").addClass("error");
    } else {
      $("#resubscribe-result .message").addClass("success");
      $("#step-2").addClass("hidden");
    }
    $("#resubscribe-result .message").removeClass("hidden");

    $("#resubscribe-result pre").text(data.hub_response);
    $("#resubscribe-result").removeClass("hidden");

    scroll_to_bottom();
  });
});

$("#btn-unsubscribe").click(function(){
  $("#btn-unsubscribe").addClass("loading");
  $.post("/hub/"+test+"/subscribe", {
    token: token,
    action: "unsubscribe"
  }, function(data){
    $("#continue-unsubscribe").addClass("hidden");
    $("#waiting-for-unsubscription").removeClass("hidden");

    $("#unsubscribe-result .message h3").text(data.result);
    $("#unsubscribe-result .message p").text(data.description);
    if(data.status == 'error') {
      $("#unsubscribe-result .message").addClass("error");
    } else {
      $("#unsubscribe-result .message").addClass("success");
      $("#step-2").addClass("hidden");
    }
    $("#unsubscribe-result .message").removeClass("hidden");

    $("#unsubscribe-result pre").text(data.hub_response);
    $("#unsubscribe-result").removeClass("hidden");

    scroll_to_bottom();
  });
});

$("#resubscribe-publish-new-post").click(function(){
  $("#resubscribe-publish-new-post").addClass("loading");
  scroll_to_bottom();
  $.post("/hub/"+test+"/pub/"+token, {
    action: "create"
  }, function(data){
    $("#resubscribe-publish-new-post").removeClass("loading");
    waiting = $("#waiting-for-notification").detach();
    $("#step-resubscribe-confirmed").after(waiting);
    $("#waiting-for-notification").removeClass("hidden");
    scroll_to_bottom();
  });
});

$("#unsubscribe-publish-new-post").click(function(){
  $("#unsubscribe-publish-new-post").addClass("loading");
  scroll_to_bottom();
  $.post("/hub/"+test+"/pub/"+token, {
    action: "create"
  }, function(data){
    $("#unsubscribe-publish-new-post").removeClass("loading");
    waiting = $("#waiting-for-notification").detach();
    $("#step-unsubscribe-confirmed").after(waiting);
    $("#waiting-for-notification").removeClass("hidden");
    wait_for_unsubscribe_success();
    scroll_to_bottom();
  });
});

function wait_for_unsubscribe_success() {
  setTimeout(function(){
    $("#waiting-for-notification").addClass("hidden");
    $("#unsubscribe-success").removeClass("hidden");
  }, 15000);
}

</script>
