<?php

// return json data of last 10 articles

include("../../../wp-load.php");

// how many items to display
$SHOW_MAX_ITEMS = 10;

function newsfeed() {

	global $SHOW_MAX_ITEMS;

	$args = array(
	'post_type' => 'post',
    'posts_per_page' => $SHOW_MAX_ITEMS,
    'orderby'        => 'most_recent',
    'has_password'	 => false
	);

	$query = new WP_Query($args);
	$rows = $query->get_posts();

	$feed_items = wp_rows_to_feed_items($rows);
	echo json_encode($feed_items);
}

// convert wp_query rows to feed items
function wp_rows_to_feed_items($rows) {

	$feed_items = array();
	$last_thumbnail_num = 1;
	foreach ($rows as $row) {

		$item = array();

		// title
		$item["post_date"] = $row->post_date;
		$item["seconds_ago"] = (time() - strtotime($row->post_date));

		// title
		$item["title"] = $row->post_title;

		// excerpt
		$item["content_snippet"] = custom_field_excerpt($row->post_content);
		
		// permalink
		$permalink = get_permalink( $row->ID, false );
		$item["url"] = $permalink;

		// thumbnail
		$thumbnail_parts = wp_get_attachment_image_src( get_post_thumbnail_id($row->ID), array( 256,256 ), false, '' );
		$thumbnail = $thumbnail_parts[0];
		if ($thumbnail_parts[0] == "") {
			$thumbnail_num = (strlen($row->post_title) % 3) + 1; // make number between 1 and 3 from title
			// don't allow consecutive thumbnails
			if ($thumbnail_num == $last_thumbnail_num) {
				$thumbnail_num = (($thumbnail_num + 1) % 3) + 1;
			}
			$thumbnail = "/blog/wp-content/plugins/tm-feeds/feed_default_image_" . $thumbnail_num . ".jpg";
			$last_thumbnail_num = $thumbnail_num;
		}
		$item["thumbnail_url"] = $thumbnail;


		// text
		$tag_html = "";
		$tag_names = array();
		$tags = wp_get_post_tags($row->ID);
		foreach ($tags as $tag) {
			if (strtolower($search_tag) != strtolower($tag->name)) {
				$tag_names[] = $tag->name;
			}
		}
		$tag_html = implode(", ", $tag_names);
		$item["tags"] = $tag_html;

		$feed_items[] = $item;
	}

	return $feed_items;
}


function custom_field_excerpt($text) {

	$text = strip_shortcodes( $text );
	$text = strip_tags($text);
	// ian - removed because it was slow 
	// $text = apply_filters('the_content', $text);
	$text = str_replace(']]>', ']]>', $text);
	$excerpt_length = 20; // 20 words
	$excerpt_more = apply_filters('excerpt_more', ' ' . '[...]');
	$text = wp_trim_words( $text, $excerpt_length, $excerpt_more );

	return $text;
}

// show newsfeed
newsfeed();


