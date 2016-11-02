<?php $this->layout('layout', [
                      'title' => $title,
                    ]); ?>

<div class="single-column">

  <section class="content">
    <h2>Testing your Publisher</h2>

    <p>Enter the URL to your page that advertises its hub. This website will attempt to discover the hub, and then subscribe to updates.</p>

    <div class="ui form">
      <div class="ui fluid action input">
        <input type="url" id="topic_input" placeholder="https://example.com/" value="https://pk.dev/">
        <button class="ui button" id="subscribe" type="submit">Subscribe</button>
      </div>
    </div>

    <p>Click "Subscribe" above and this page will subscribe to updates from your page.</p>

    <div class="hidden" id="step-hub">
      <b>hub:</b> <span id="hub"></span><br>
      <b>self:</b> <span id="self"></span><br>

      <div class="hidden" id="step-subscribe">


      </div>
    </div>

    <div class="hidden" id="step-hub-error">
      <div class="ui error message">
        Your <code>hub</code> and <code>self</code> were not found. Ensure your page has either HTTP <code>Link</code> headers or HTML or XML <code>&lt;link&gt;</code> tags indicating your hub and self URLs. See <a href="https://www.w3.org/TR/pubsub/#discovery">Discovery</a> for more information.
      </div>
    </div>

  </section>

</div>
<script>
function start_discover_step() {
  if($("#topic_input").val()) {
    $("#subscribe").addClass("loading");
    $("#step-hub").addClass("hidden");

    $.post("/publisher/discover", {
      topic: $("#topic_input").val()
    }, function(data){ 
      $("#subscribe").removeClass("loading");
      if(data.hub && data.self) {
        $("#hub").text(data.hub);
        $("#self").text(data.self);
        $("#step-hub").removeClass("hidden");
        $("#step-hub-error").addClass("hidden");
        $("#step-subscribe").removeClass("hidden");
        start_subscribe_step();
      } else {
        $("#step-hub").addClass("hidden");
        $("#step-hub-error").removeClass("hidden");
      }
    });
  }
}

function start_subscribe_step() {

}

$(function(){
  $("#subscribe").click(function(){
    start_discover_step();
  });
});
</script>
