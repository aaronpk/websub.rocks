
/* Subscriber Tests */
$(function(){
  $("#subscriber-new-post-btn").click(function(){
    $(this).addClass("loading");
    $.post($(this).attr("href"), function(data){
      $("#subscriber-new-post-btn").removeClass("loading");
      if(data.html) {
        $("#subscriber-post-list").html(data.html);
      }
    });
    return false;
  });
});
