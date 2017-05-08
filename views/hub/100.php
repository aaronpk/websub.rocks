<?php $this->layout('layout', [
                      'title' => $title,
                    ]); ?>

<div class="single-column">

  <section id="step-1" class="content">
    <h2><?= $num ?>: Typical Subscriber Request</h2>

    <div class="ui top attached tabular menu">
      <a class="item active" data-tab="first">Public Hub</a>
      <a class="item" data-tab="second">Integrated Hub</a>
    </div>
    <div class="ui bottom attached tab segment active" data-tab="first">
      <p>If your hub is a "public hub", e.g. it is a standalone service that can be used by any publisher, then you can use this option. If your hub is integrated with the publishing software, choose the other tab.</p>
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

    <br>

    <p>This subscriber will include only the parameters <code>hub.mode</code>, <code>hub.topic</code> and <code>hub.callback</code>. The hub should deliver notifications with no signature.</p>
  </section>

  <section id="step-2" class="hidden content">
    <div class="ui message hidden" id="step-2-result">
      <h3></h3>
      <p></p>
    </div>

    <h3>The raw response from the subscription request is below</h3>
    <pre class="debug"></pre>
  </section>

  <section id="step-verify" class="hidden content">
    <div class="ui message" id="step-verify-result">
      <h3></h3>
      <p></p>
    </div>

    <p>Now that websub.rocks is subscribed to your hub, we can test the delivery of the WebSub notification.</p>

    <div class="hidden" id="continue-remote-publisher">
      <p>Since you entered your own topic URL, you should now create a new post at that topic and trigger your hub to deliver notifications to all subscribers.</p>
      <p>When this subscriber receives a notification, you will see the results below.</p>
    </div>
    <div class="hidden" id="continue-local-publisher">
      <p>Click the button below to create a new post at the test topic URL. This will send a POST request to your hub notifying that there is new content.</p>
      <p>The POST request will contain the following parameters:</p>
      <ul>
        <li><code>hub.mode=publish</code></li>
        <li><code>hub.topic=&lt;the generated topic URL&gt;</code></li>
      </ul>
      <p>Upon receiving this request, the hub should fetch the topic URL, and deliver the contents to the subscriber.</p>
      <button class="ui button" id="publish-new-post">Create Post</button>
    </div>
  </section>

  <section class="content hidden" id="waiting-for-notification">
    <div class="ui active indeterminate centered inline text loader">Waiting for notification</div>
  </section>

</div>
<script>
var test=<?= $num ?>;
var token;
var socket;
var publisher;

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
        $("#step-verify-result h3").text("Subscription Confirmed!");
        $("#step-verify-result p").text(data.text.description);
        $("#step-verify-result").addClass("success");
        $("#step-verify").removeClass("hidden");
        continue_publishing();
        break;
      case 'verify_error': 
        $("#step-verify-result h3").text("Error!");
        $("#step-verify-result p").text(data.text.description);
        $("#step-verify-result").addClass("error");
        $("#step-verify").removeClass("hidden");
        break;
      case 'notification':
        // a WebSub notification was received
        // TODO show success/error messages
    }
  }

  $.post("/hub/"+test+"/subscribe", {
    token: token
  }, handle_subscribe_response);
}

function handle_subscribe_response(data) {
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

$("#publish-new-post").click(function(){
  $.post("/hub/"+test+"/pub/"+token, {
  }, function(data){

  });
});

</script>
