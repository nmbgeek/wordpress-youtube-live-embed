jQuery(document).ready(function ($) {
  var $livestream = $('.youtube-livestream');

  $livestream.each(function () {
    var $this = $(this);
    var channelId = $this.data('channel-id');
    var autoplay = $this.data('autoplay');

    $.post(
      ajax_object.ajax_url,
      {
        action: 'yle_get_video_id',
        channel_id: channelId,
        autoplay: autoplay,
      },
      function (response) {
        $this.replaceWith(response);
      }
    );
  });
});
