<?php $this->layout('layout', [
                      'title' => $title,
                    ]); ?>

<div class="single-column">

  <section class="content">
    <h2><?= $num ?>: <?= $name ?></h2>

    <?= $description ?>

    <h3>Discovery</h3>

    <p>Attempt to subscribe to the URL below. The next step will appear below once you've made the discovery request.</p>

    <div class="ui form">
      <input type="url" value="<?= $topic ?>" readonly="readonly" onclick="this.select()">
    </div>

    <div class="hidden ui success message" id="step-discover-success">
      Great! The discovery request was made with a <code></code> request! Now you need to subscribe to the hub.
    </div>

    <div id="step-subscribe" class="hidden">
      <h3>Subscription</h3>

      <div class="hidden ui success message" id="step-subscribe-success">
        Subscription activated!
      </div>
      <div class="hidden ui error message" id="step-subscribe-error">
        <span class="summary"></span>: <code></code>. <span class="description"></span>
      </div>

      <div id="subscription-callback-response" class="hidden">
        <p>Below is the response your subscriber sent to the verification request.</p>
        <pre class="debug"></pre>
      </div>

      <a href="" class="hidden ui blue button" id="continue-to-feed-btn">Continue</a>
    </div>

    <div style="margin-top: 1em;" id="loader">
      <div class="ui active indeterminate centered inline text loader"></div>
    </div>
    
  </section>
</div>
<script>
var token = "<?= $token ?>";
var socket = new EventSource('/streaming/sub?id='+token);

// Keep track of what step the subscriber is on
var step = 'discover';

socket.onmessage = function(event) {
  var data = JSON.parse(event.data);
  if(step == 'subscribe') {
    if(data.text.type == 'error') {
      $("#step-subscribe-error code").text(data.text.error);
      $("#step-subscribe-error .description").text(data.text.error_description);
      $("#step-subscribe-error").removeClass("hidden");
      $("#step-subscribe-success").addClass("hidden");
      $("#step-discover-success").addClass("hidden");
    } else if(data.text.type == 'success') {
      $("#step-subscribe-error").addClass("hidden");
      $("#step-subscribe-success").removeClass("hidden");
      $("#loader").remove();
      if(!data.text.skip_continue) {
        $("#continue-to-feed-btn").attr("href", data.text.topic).removeClass("hidden");
      }
      if(data.text.success_message) {
        $("#step-subscribe-success").text(data.text.success_message);
      }
      $("#step-discover-success").addClass("hidden");
    } else if(data.text.type == 'discover') {
      // Another discovery request was made while we were waiting for the confirmation.
      // This is not necessarily an error.
    } else {
      $("#step-subscribe-error code").text("unknown error");
      $("#step-subscribe-error .description").text("Something went wrong with the test.");
      $("#step-subscribe-error").removeClass("hidden");
      $("#step-subscribe-success").addClass("hidden");
    }
    if(data.text.callback_response) {
      if(!data.text.skip_continue) {
        $("#step-subscribe-error .summary").text("There was an error validating the subscription");
        $("#subscription-callback-response pre").text(data.text.callback_response)
        $("#subscription-callback-response").removeClass("hidden");
      }
    } else {
      $("#step-subscribe-error .summary").text("There was an error with your subscription request");
    }
    $("#step-subscribe").removeClass("hidden");
  }
  if(step == 'discover' && data.text.type == 'discover') {
    $("#step-discover-success code").text(data.text.method);
    $("#step-discover-success").removeClass("hidden");
    step = 'subscribe';
  }
};
</script>
