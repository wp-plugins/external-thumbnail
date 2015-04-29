<?php
/*
Plugin Name: External Thumbnail
Plugin URI: http://thucdem.mobi
Description: Using external images from anywhere to make thumbnail
Version: 1.1
Author: MrTaiw
Author URI: https://www.facebook.com/taiw96
*/

add_action('the_post', 'tw_et_replace_thumb_id');
add_filter('post_thumbnail_html', 'tw_et_replace_thumbnail', 11, 5);
if(is_admin()){
	add_action('add_meta_boxes', 'tw_et_add_meta_boxes');
	add_action('save_post', 'tw_et_save');
}

function tw_et_replace_thumb_id($post){
	
	if(is_array($post)){
		$post_ID      = $post['ID'];
		$post_content = $post['post_content'];
	}
	else
	{
		$post_ID = $post->ID;
		$post_content = $post->post_content;
	}
	$tw_thumbnail = esc_url(get_post_meta($post_ID, 'tw_thumbnail_url', true), array('http', 'https'));
	$wp_thumbnail = get_post_meta($post_ID, '_thumbnail_id', true);
	if(strlen($tw_thumbnail) <= 7 && preg_match("/(http|https):\/\/[^\s]+(\.gif|\.jpg|\.jpeg|\.png)/is", $post_content, $thumb))
		$tw_thumbnail = esc_url($thumb[0], array('http', 'https'));
	if(strlen($tw_thumbnail) > 7 && !$wp_thumbnail)
		update_post_meta($post_ID, '_thumbnail_id', -1);
}

function tw_et_add_meta_boxes() {

	$not_allow = array('attachment', 'nav_menu_item');
	foreach(get_post_types('', 'names') as $post_type){
		if(in_array($post_type, $not_allow))
			continue;
		add_meta_box('External-Thumbnail', 'External Thumbnail', 'tw_et_box', $post_type, 'side');
	}

}
	
function tw_et_save($post_id){
	
	if(defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
		return $post_id;
	}	

	if('page' == $_POST['post_type']){
		if(!current_user_can('edit_page', $post_id))
			return $post_id;
	}
	else
	{
		if(!current_user_can('edit_post', $post_id))
			return $post_id;
    }
    $thumbnail_url = sanitize_text_field($_POST['tw_thumbnail']);
	if($_POST['tw_thumbnail'])
		update_post_meta($post_id, 'tw_thumbnail_url', $thumbnail_url);
}


function tw_html_thumbnail($url, $size = false, $attr = array()){
	
	global $_wp_additional_image_sizes;
	$additional_image = '';
	if(!$attr || $attr == '')
		$attr = array();
	if(is_array($size)){
		$additional['width']  = $size[0];
		$additional['height'] = $size[1];
	}
	elseif(isset($_wp_additional_image_sizes[$size])){
		$additional['width']  = $_wp_additional_image_sizes[$size]['width'];
		$additional['height'] = $_wp_additional_image_sizes[$size]['height'];
		$attr['class']        = 'wp-post-image attachment-' . $size . (isset($attr['class']) ? ' ' . $attr['class'] : '');
	}
	foreach(array_merge($additional, $attr) as $key => $value){

		$additional_image .= $key . '="' . str_replace(array('/', '"', "'"), '', $value) . '" ';

	}

	$html = '<img src="' . $url . '" ' . $additional_image . '/>';
	return $html;
}

function tw_et_replace_thumbnail($html, $post_ID, $post_image_id, $size, $attr){
	
	global $post;
	if(is_array($post)){
		$post_content = $post['post_content'];
	}
	else
	{
		$post_content = $post->post_content;
	}
	$wp_thumbnail = get_post_meta($post_ID, '_thumbnail_id', true);
	$tw_thumbnail = esc_url(get_post_meta($post_ID, 'tw_thumbnail_url', true), array('http', 'https'));
	if(strlen($tw_thumbnail) <= 7 && preg_match("/(http|https):\/\/[^\s]+(\.gif|\.jpg|\.jpeg|\.png)/is", $post_content, $thumb))
		$tw_thumbnail = esc_url($thumb[0], array('http', 'https'));
	if((!$wp_thumbnail || $wp_thumbnail == -1) && strlen($tw_thumbnail) > 10)
		$html = tw_html_thumbnail($tw_thumbnail, $size, $attr);
	return $html;
}

function tw_et_box(){

	global $post;

	$tw_thumbnail = esc_url(get_post_meta($post->ID, 'tw_thumbnail_url', true), array('http', 'https'));
	?>
	<input type="text" style="width:100%" name="tw_thumbnail" value="<?php echo $tw_thumbnail?>" placeholder="http://" id="tw_thumbnail"/>
	<br/><a class="button button-small" href="javascript:void(0);" onclick="jQuery('#tw_thumbnail').val('');">Remove</a>
	<?php
}

?>