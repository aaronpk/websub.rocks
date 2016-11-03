<?php $this->layout('layout', [
                      'title' => $title,
                    ]); ?>

<div class="single-column">

  <section class="content" id="setup">
    <h2>Testing your Publisher</h2>

    <p>Enter the URL to your page that advertises its hub. This website will attempt to discover the hub, and then subscribe to updates.</p>

    <div class="ui form">
      <div class="ui fluid action input">
        <input type="url" id="topic_input" placeholder="https://example.com/" value="https://pk.tunnlr.xyz/">
        <button class="ui button" id="subscribe" type="submit">Subscribe</button>
      </div>
    </div>

    <p>Click "Subscribe" above and this page will subscribe to updates from the page.</p>

    <div class="hidden ui yellow message" id="step-hub-loading">
      <h3>Discovering the hub...</h3>
    </div>

    <div class="hidden" id="step-hub">
      <div class="ui success message">
        <h3>Found the Hub!</h3>
        <b>hub:</b> <span id="hub"></span><br>
        <b>self:</b> <span id="self"></span><br>
      </div>

      <div class="hidden" id="step-subscribe">

        <div class="hidden ui yellow message" id="step-subscribe-loading">
          <h3>Sending subscription request...</h3>
        </div>

        <div id="hub-response-details">
          <h3>Response from the hub:</h3>
          <pre id="subscribe-debug" class="debug"></pre>
        </div>

        <div class="hidden ui yellow message" id="step-subscribe-verify-waiting">
          <h3>Waiting for the hub to verify the subscription...</h3>
        </div>


        <div class="hidden" id="step-active">
          <div class="ui success message" id="step-subscribe-success">
            <h3>Subscription Active!</h3>
            <p>The hub verified the subscription request and the subscription is now active.</p>
          </div>

        </div>

      </div>
    </div>

    <div class="hidden" id="step-hub-error">
      <div class="ui error message">
        Your <code>hub</code> and <code>self</code> were not found. Ensure your page has either HTTP <code>Link</code> headers or HTML or XML <code>&lt;link&gt;</code> tags indicating your hub and self URLs. See <a href="https://www.w3.org/TR/pubsub/#discovery">Discovery</a> for more information.
      </div>
    </div>

  </section>

  <section class="hidden content" id="notifications">
    <h2></h2>
    <div class="ui active indeterminate centered inline text loader">Waiting for notifications</div>
    <div id="notification-list"></div>
  </section>

</div>
<script>
var jwt;

function start_discover_step() {
  if($("#topic_input").val()) {
    $("#subscribe").addClass("loading");
    $("#step-hub").addClass("hidden");
    $("#step-hub-loading").removeClass("hidden");

    $.post("/publisher/discover", {
      topic: $("#topic_input").val()
    }, function(data){ 
      $("#subscribe").removeClass("loading");
      $("#step-hub-loading").addClass("hidden");
      if(data.hub && data.self) {
        $("#hub").text(data.hub);
        $("#self").text(data.self);
        $("#step-hub").removeClass("hidden");
        $("#step-hub-error").addClass("hidden");
        $("#step-subscribe").removeClass("hidden");
        jwt = data.jwt;
        start_subscribe_step();
      } else {
        $("#step-hub").addClass("hidden");
        $("#step-hub-error").removeClass("hidden");
      }
    });
  }
}

function start_subscribe_step() {
  $("#step-subscribe-loading").removeClass("hidden");

  $.post("/publisher/subscribe", {
    jwt: jwt
  }, function(data){
    if(data.debug) {
      $("#hub-response-details").removeClass("hidden");
      $("#subscribe-debug").text(data.debug);
    }
    $("#step-subscribe-loading").addClass("hidden");
    $("#step-subscribe-verify-waiting").removeClass("hidden");

    var socket = new EventSource('/streaming/sub?id='+data.token);
    socket.onmessage = function(event) {
      var data = JSON.parse(event.data);
      if(data.text.type == 'active') {
        $("#step-subscribe-verify-waiting").addClass("hidden");
        $("#step-active").removeClass("hidden");
        $("#notifications").removeClass("hidden");
        $("#notifications h2").text("Subscribed to "+$("#self").text());
      } else if(data.text.type == 'notification') {
        $("#setup").addClass("hidden");
        $("#notifications").removeClass("hidden");
        var body = data.text.body;
        if(body == "") body = "(The hub did not include any content in the notification)";
        $("#notification-list").prepend($('<pre>').addClass('debug').text(body));
      }
    }
  });


}



$(function(){
  $("#subscribe").click(function(){
    start_discover_step();
  });
});
</script>
