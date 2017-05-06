<?php $this->layout('layout', [
                      'title' => $title,
                      'link_tag' => isset($link_tag) ? $link_tag : ''
                    ]); ?>

<div class="single-column">

  <?php if(is_logged_in()): ?>
    <section class="content">
      <p>Congrats! Now that your subscription is active, you can generate new posts that will be delivered to your subscriber! Click the button below to add a new post to this feed, and send a notification to subscribers of this feed.</p>

      <a href="/subscriber/<?= $num ?>/<?= $token ?>/publish" class="ui blue button" id="subscriber-create-post">Create New Post</a>
    </section>

    <div class="hidden ui success message" id="step-delivery-result">
    </div>

  <?php endif; ?>

  <div id="subscriber-post-list" class="h-feed">
    <span class="p-name hidden">WebSub.rocks Test <?= $num ?></span>
    <? $this->insert('subscriber/post-list', ['posts'=>$posts, 'num'=>$num]) ?>
  </div>

</div>
<?php if(is_logged_in()): ?>
<script>
/* Subscriber Tests */
$(function(){
  $("#subscriber-create-post").click(function(){
    $(this).addClass("loading");
    $.post($(this).attr("href"), function(data){
      $("#subscriber-create-post").removeClass("loading");
      if(data.result == 'success') {
        $("#step-delivery-result").removeClass("hidden").text(data.message);
      } else {
        $("#step-delivery-result").removeClass("success hidden").addClass("error").text(data.message);
      }
      if(data.html) {
        $("#subscriber-post-list").html(data.html);
      }
    });
    return false;
  });
});
</script>
<?php endif ?>
