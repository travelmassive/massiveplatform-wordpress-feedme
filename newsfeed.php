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

		// title
		$item["title"] = $row->post_title;

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
		$item["text"] = $tag_html;

		$feed_items[] = $item;
	}

	return $feed_items;
}


// show newsfeed
newsfeed();


