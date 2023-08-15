<?php
/**
 * Plugin Name: YouTube Livestream Embed
 * Plugin URI: https://github.com/nmbgeek/wordpress-youtube-live-embed/
 * Description: Embed the current livestream from a YouTube channel using AJAX.
 * Version: 0.1
 * Author: Clint Johnson
 * Author URI: https://nmbgeek.com
 * License: GPL-3.0
 * License URI: https://www.gnu.org/licenses/gpl-3.0.txt
 * Text Domain: wordpress-youtube-live-embed
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
 
define( 'WP_YLE_VERSION', '0.1' ); 

// Enqueue the JavaScript for AJAX.
function yle_enqueue_scripts() {
    wp_enqueue_script('youtube-livestream-ajax', plugin_dir_url(__FILE__) . 'js/youtube-livestream.js', array('jquery'), WP_YLE_VERSION, true);
    wp_localize_script('youtube-livestream-ajax', 'ajax_object', array('ajax_url' => admin_url('admin-ajax.php')));
}

add_action('wp_enqueue_scripts', 'yle_enqueue_scripts');

// AJAX handler
function yle_get_video_id() {
    $channel_id = $_POST['channel_id'];
    $autoplay = $_POST['autoplay'];
    $api_key = defined('YOUTUBE_LIVESTREAM_API_KEY') ? YOUTUBE_LIVESTREAM_API_KEY : get_option('yle_api_key', '');
    
        if (!$api_key) {
        return 'No YouTube API key provided.';
    }
    // Generate a transient key based on the channel ID and api key
    $transient_key = 'youtube_livestream_video_id_' . md5($channel_id . $api_key);

    // Attempt to get the video ID from the transient
    $video_id = get_transient($transient_key);

    if (!$video_id) {
        $api_url = "https://www.googleapis.com/youtube/v3/search?eventType=live&part=snippet&channelId={$channel_id}&type=video&key={$api_key}";
        $response = wp_remote_get($api_url);

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
		
		if(empty($data['items']) || is_wp_error($response)) {
			$maintenance_message = get_option('yle_error_message', 'There was an error fetching the stream.  Please check back soon.');
			echo $maintenance_message;
		}
    

        $video_id = $data['items'][0]['id']['videoId'];

        // Set the video ID in the transient, cache for 1 hour (3600 seconds)
        set_transient($transient_key, $video_id, 3600);

    }

    if (!$maintenance_message) {
        $embed_code = <<<EMBEDCODE
        <div class="embedyt-container">
        <iframe width='100%' height='auto' src='https://www.youtube.com/embed/{$video_id}?autoplay={$autoplay}' frameborder='0' allowfullscreen></iframe>
        </div>
        EMBEDCODE;
        echo $embed_code;
    }
    wp_die();
}

add_action('wp_ajax_yle_get_video_id', 'yle_get_video_id');

add_action('wp_ajax_nopriv_yle_get_video_id', 'yle_get_video_id');

// Shortcode to embed livestream
function youtube_livestream_embed($atts) {
    $channel_id = $atts['channel_id'];
    $autoplay = isset($atts['autoplay']) && strtolower($atts['autoplay']) === 'true' ? '1&mute=1' : 0;
    wp_register_style( 'yle-style', plugins_url( '/css/style.css', __FILE__ ), array(), WP_YLE_VERSION, 'all' );
    wp_enqueue_style( 'yle-style' );

    // Return a placeholder with data attributes
    return "<div class='youtube-livestream' data-channel-id='{$channel_id}' data-autoplay='{$autoplay}'</div>";
}
add_shortcode('youtube_livestream', 'youtube_livestream_embed');

// Settings for API Key
function yle_settings_page() {
    add_options_page('YouTube Livestream Settings', 'YouTube Livestream', 'manage_options', 'yle-settings', 'yle_render_settings_page');
}

add_action('admin_menu', 'yle_settings_page');

function youtube_livestream_embed_wp_enqueue_scripts() {
    wp_register_style( 'yle-style', plugins_url( '/css/style.css', __FILE__ ), array(), '1.0.0', 'all' );
}

function yle_render_settings_page() {
    ?>
    <div class="wrap">
        <h1>YouTube Livestream Settings</h1>
        <form method="post" action="options.php">
            <?php
                settings_fields('yle_settings_group');
                do_settings_sections('yle-settings');
                submit_button();
            ?>
        </form>
    </div>
    <?php
}

function yle_register_settings() {
    register_setting('yle_settings_group', 'yle_api_key');
    register_setting('yle_settings_group', 'yle_error_message');
    add_settings_section('yle_api_settings_section', 'API Settings', null, 'yle-settings');
    add_settings_field('yle_api_key_field', 'YouTube API Key', 'yle_api_key_field_render', 'yle-settings', 'yle_api_settings_section');
    add_settings_field('yle_error_message_field', 'Stream Maintenance Message', 'yle_error_message_field_render', 'yle-settings', 'yle_api_settings_section');
}
add_action('admin_init', 'yle_register_settings');

function yle_api_key_field_render() {
    $value = get_option('yle_api_key', '');

    // Check if YOUTUBE_LIVESTREAM_API_KEY is defined in wp-config.php
    if (defined('YOUTUBE_LIVESTREAM_API_KEY')) {
        echo '<input type="text" name="yle_api_key" value="' . esc_attr($value) . '" class="regular-text" disabled="disabled" />';
        echo '<p class="description">The YouTube API Key is defined in wp-config.php. To change it, please modify wp-config.php directly.</p>';
    } else {
        echo '<input type="text" name="yle_api_key" value="' . esc_attr($value) . '" class="regular-text" /><p>The API Key can also be set in your wp-config.php with: <br/> ' . "<code>define('YOUTUBE_LIVESTREAM_API_KEY', 'INSERT_YOUR_API_KEY_HERE');</code></p>";
    }
}

function yle_error_message_field_render() {
    $value = get_option('yle_error_message', ''); // Fetch stored error message
    wp_editor($value, 'yle_error_message', array(
        'textarea_name' => 'yle_error_message',
        'media_buttons' => true,  // No need for media buttons in this context
        'textarea_rows' => 10,
        'teeny'         => false    // Simplified editor
    ));
    
    echo '<p class="description">Enter the maintenance mode message that will be displayed when there is an issue fetching the livestream.</p>';
}

add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'yle_add_plugin_page_settings_link');

function yle_add_plugin_page_settings_link($links) {
    // Add the settings link at the beginning of the array
    $settings_link = '<a href="options-general.php?page=yle-settings">' . __('Settings', 'youtube-livestream-embed') . '</a>';
    array_unshift($links, $settings_link);
    return $links;
}
