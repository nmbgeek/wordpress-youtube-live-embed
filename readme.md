# YouTube Live Stream Embed for WordPress

1. Get a YouTube API Key by following the steps here: https://developers.google.com/youtube/v3/getting-started
2. Install plugin and set your API Key on the settings page. Alternatively it can be defined in wp-config.php with `define('YOUTUBE_LIVESTREAM_API_KEY', 'YOUR_YOUTUBE_API_KEY_HERE');`
3. Set a message to display if the live stream is not active or there is a problem getting the stream.
4. Add your shortcode where you want your stream to appear. Be sure to set the channel_id to your own in the shortcode. autoplay is optional and will default to false if not specified. `[youtube_livestream channel_id="YOUR_CHANNEL_ID" autoplay="true"]`
