<?php
/**
 * Plugin Name: Display Posts Metabox
 * Description: Include a metabox in the post editor that allows for dynamic selection of related post content.
 * Version: 0.0
 * Author: Joe Worsham
 *
 * @package Display Posts Metabox
 * @version 0.0
 * @author Joe Worsham
 * @copyright Copyright (c) 2019, Joe Worsham
 */

register_activation_hook( __FILE__, 'child_plugin_activate' );
function child_plugin_activate()
{
	// Require parent plugin
	if ( ! is_plugin_active( 'display-posts-shortcode/display-posts-shortcode.php' ) and current_user_can( 'activate_plugins' ) ) {
		// Stop activation redirect and show error
		wp_die('Sorry, but this plugin requires the <a href="https://displayposts.com">Display Posts Shortcode</a> plugin to be installed and active. <br><a href="' . admin_url( 'plugins.php' ) . '">&laquo; Return to Plugins</a>');
	}
}

// add a metabox for selecting current posts in the db
function dpmb_add_custom_box()
{
	add_meta_box(
		'dpmb_select',           	// Unique ID
		'Related Post Selection', 	// Box title
		'dpmb_custom_box_html', 	// Content callback, must be of type callable
		'post'                  	// Post type
	);
}
add_action('add_meta_boxes', 'dpmb_add_custom_box');

function dpmb_custom_box_html($post)
{
	$value = get_post_meta($post->ID, 'dpmb_field', true);
	$value = explode(",", $value);
	?>
	<label for="dpmb_header">Related content section header: </label>
	<input type="text" name="dpmb_header" id="dpmb_header"></br>
	<label for="dpmb_field">Related Posts: </label>
	<select multiple name="dpmb_field[]" id="dpmb_field" class="postbox">
		<?php
		foreach( get_posts() as $post ) : setup_postdata($post); ?>
			<option <?php echo (in_array($post->ID, $value) ? 'selected="selected"' : ""); ?> value="<?php echo $post->ID; ?>"><?php echo $post->post_title; ?></option>
		<?php endforeach; ?>
	</select>
	<?php
}

function dpmb_save_postdata($post_id)
{
	update_post_meta(
		$post_id,
		'dpmb_header',
		$_POST['dpmb_header']
	);
	if (array_key_exists('dpmb_field', $_POST)) {
		update_post_meta(
			$post_id,
			'dpmb_field',
			implode(",", $_POST['dpmb_field'])
		);
	}
	else {
		delete_post_meta(
			$post_id,
			'dpmb_field'
		);
	}
}
add_action('save_post', 'dpmb_save_postdata');

// add the related content section before the end of the body
function dpmb_related_content($content)
{
	if (metadata_exists("post", get_the_ID(), 'dpmb_field'))
	{
		$dpmb = get_post_meta(get_the_ID(), 'dpmb_field', true);
		$dpmb = explode(",", $dpmb);
		$sc = sprintf('[display-posts id="%s"  image_size="medium" include_excerpt="true" wrapper="div" wrapper_class="display-posts-listing grid" meta_key="_thumbnail_id"]',
			implode(",",$dpmb));
		$after = sprintf('<h2>%s</h2>', get_post_meta(get_the_ID(), 'dpmb_header', true));
		$after = $after . do_shortcode($sc);
		$content = $content . $after;
	}
	return $content;
}
add_filter('the_content', 'dpmb_related_content');
?>