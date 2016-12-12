<?php

include("../../../wp-load.php");

// wordpress page to load on front page feed

// how many items to display
$SHOW_MAX_ITEMS = 5;

function feedme() {

	// frontpage, chapter, company
	$mode = $_GET["mode"]; 
	if ($mode == null) {
		print("<div class='feedme_help' style='display: none;'>");
		print("Must provide a mode and search query.<br>");
		print("ie: ?mode=frontpage<br>");
		print("ie: ?mode=chapter&q=Berlin<br>");
		print("ie: ?mode=company&q=GoEuro<br>");
		print("ie: ?mode=company&q=GoEuro&theme=dark<br>");
		print("ie: ?mode=member&q=10543&theme=dark<br>");
		print("</div>");
		die();
	}

	// query tag
	$q = $_GET["q"];

	// show num items
	if (isset($_GET["num_items"])) {
		global $SHOW_MAX_ITEMS;
		if ((($_GET["num_items"]) > 0) && ($_GET["num_items"] < 100)) {
			$SHOW_MAX_ITEMS = $_GET["num_items"];
		}
	}

	$mode = $_GET["mode"]; 

	// theme css
	// light theme is default
	$theme = $_GET["theme"];
	if ($theme == "dark") {
		global $css_dark;
		print($css_dark);
	} else {
		global $css_light;
		print($css_light);
	}

	// frontpage mode
	if ($mode == "frontpage") {

		// front page content
		$id = $_GET["frontpage_id"];
		$post = get_post($id); 
		$content = apply_filters('the_content', $post->post_content);
		// hide the front page content by default, so we can display it on load via js
		print("<div id='feedme_frontpage_content' style='display: none;'>" . $content . "</div>");  

		// print front page feed
		$html = render_frontpage_feed();
		print($html);
		return;
	}


	// chapter mode
	if ($mode == "chapter") {
		$html = render_chapter_feed($q);
		print($html);
		return;
	}

	// company mode
	if ($mode == "company") {
		$html = render_company_feed($q);
		print($html);
		return;
	}

	// member mode
	if ($mode == "member") {
		$html = render_member_feed($q);
		print($html);
		return;
	}

}

function query_to_search_tag($q) {
	// turn name into search tag
	// ie: San Francisco => san-francisco
	$search_tag = strtolower(str_replace(" ", "-",trim($q)));
	return $search_tag;
}

function render_frontpage_feed() {

	global $SHOW_MAX_ITEMS;
	$title = "Latest news";

	$args = array(
	'post_type' => 'post',
    'posts_per_page' => $SHOW_MAX_ITEMS,
    'orderby'        => 'most_recent',
    'has_password'	 => false
	);

	$query = new WP_Query($args);
	$rows = $query->get_posts();

	$more_link = "/blog/";
	$more_text = "View more news";

	$feed_items = wp_rows_to_feed_items($rows);
	return render_feed($feed_items, $title, $more_link, $more_text);
}

// show related blog posts to chapter
function render_chapter_feed($chapter_name) {

	$search_tag = query_to_search_tag($chapter_name);

	// search tags first
	$title = "Recent posts";
	$query = new WP_Query( 'has_password!=1&post_type=post&tag=' . $search_tag );
	$rows = $query->get_posts();

	// it no articles, try general search
	if (sizeof($rows) == 0) {
		$title = "Related posts";
		$query = new WP_Query( 'has_password!=1&post_type=post&s=' . $chapter_name );
		$rows = $query->get_posts();
	}

	// if still no articles, don't show anything
	if (sizeof($rows) == 0) {
		return;
	}

	$more_link = "/blog/?s=" . $chapter_name . "&op=Search";
	$more_text = "View related articles";

	$feed_items = wp_rows_to_feed_items($rows);
	return render_feed($feed_items, $title, $more_link, $more_text);
}

// show related blog posts to company
function render_company_feed($company_name) {

	$search_tag = query_to_search_tag($company_name);

	// search tags first
	$title = "Articles about " . $company_name; //Recent articles";
	$query = new WP_Query( 'has_password!=1&post_type=post&tag=' . $search_tag );
	$rows = $query->get_posts();

	// if still no articles, don't show anything
	if (sizeof($rows) == 0) {
		return;
	}

	$more_link = "/blog/?s=" . $company_name . "&op=Search";
	$more_text = "View related articles";

	$feed_items = wp_rows_to_feed_items($rows);
	return render_feed($feed_items, $title, $more_link, $more_text);
}

// show related blog posts to a member
// requires content is tagged with members name: ie: Erika
function render_member_feed($member_name) {

	$search_tag = query_to_search_tag($member_name);

	// search tags first
	$title = "Recent articles"; //Recent articles";
	$query = new WP_Query( 'has_password!=1&post_type=post&tag=' . $search_tag );
	$rows = $query->get_posts();

	// if still no articles, don't show anything
	if (sizeof($rows) == 0) {
		return;
	}

	$more_link = "/blog/tag/" . $search_tag;
	$more_text = "View related articles";

	$feed_items = wp_rows_to_feed_items($rows);
	return render_feed($feed_items, $title, $more_link, $more_text);
}

// convert wp_query rows to feed items
// $feed_item = array("title" => $title, "url" => $url, "thumbnail_url" => $thumbnail_url, "feed_text" => $feed_text);
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

// render rows
function render_feed($feed_items, $title, $more_link, $more_text) {

	global $template;
	global $article_template;
	global $more_template;
	global $SHOW_MAX_ITEMS;

	$articles_html = "";

	$count = 0;
	foreach($feed_items as $item) {
		$count++;
		if ($count > $SHOW_MAX_ITEMS) { 
			continue;
		}

		$article_html = $article_template;
		$article_html = str_replace("__ARTICLE_URL__", $item["url"], $article_html);
		$article_html = str_replace("__ARTICLE_IMAGE__", $item["thumbnail_url"], $article_html);
		$article_html = str_replace("__ARTICLE_TAGS__", $item["text"], $article_html);
		$article_html = str_replace("__ARTICLE_TITLE__", $item["title"], $article_html);

		$articles_html .= $article_html;
	}


	$more_template = str_replace("__MORE_URL__", $more_link, $more_template);
	$more_template = str_replace("__MORE_TEXT__", $more_text, $more_template);

	$template = str_replace("__MORE_LINK__", $more_template, $template);

	$template = str_replace("__TITLE__", $title, $template);
	$template = str_replace("__NEWS_ITEMS__", $articles_html, $template);

	// add css
	$html = $template;
	return $html;
}

$css_light = <<<EOT
	<style style="text/css">
	.contained.contained-block.feedme { border-left: 8px solid #3080b2; }
	img.feedme-image { max-height: 64px;}
	</style>
EOT;

$css_dark = <<<EOT
	<style style="text/css">
	.contained.contained-block.feedme { background-color: #121212; color: #fff; border-left: 8px solid #d75345; }
	a.feedme { background-color: #121212 !important; color: #fff;}
	a.feedme:hover { background-color: #222 !important;}
	h1.feedme { color: #fefefe;}
	h1.feedme.top { color: #fff;}
	a.feedme.more { background-color: #121212 !important;}
	li.feedme { border-bottom: 1px solid #888; }
	img.feedme-image { max-height: 64px;}
	</style>
EOT;


$template = <<<EOT
<!--<div class="row" style="margin-top: 1em; margin-bottom: 0px;">
	<div class="column first" style="float: right;">-->
		<section class="contained contained-block feedme">
			<header class="contained-head">
				<h1 class="prime-title feedme top">__TITLE__</h1>
			</header>
			<div class="contained-body">
				<ul class="wordpress-feedme-list related-list">
					__NEWS_ITEMS__
				</ul>
			</div>
			__MORE_LINK__
		</section>
	<!--</div>
</div>-->
EOT;

$more_template = <<<EOT
<div class="more-link" style="font-size: 14px;">
  <a href="__MORE_URL__" class="feedme more">__MORE_TEXT__</a>
</div>
EOT;

$article_template = <<<EOT
<li class="feedme">
   <article class="card contained feedme view-mode-grid clearfix">
    <!-- Needed to activate contextual links -->
        <a href="__ARTICLE_URL__" class="feedme">
	        <div class="media">
		        <div class="avatar">
		        	<span class="badge-wordpress-feedme">
		        		<img class="feedme-image" typeof="foaf:Image" src="__ARTICLE_IMAGE__" width="256" height="256" alt="">
		        	</span>
		        </div>
	        </div>

	    	<div class="teaser">
	      		<h1 class="prime-title feedme">__ARTICLE_TITLE__</h1>
	      		<p class="meta feedme"><span class="role">__ARTICLE_TAGS__</span>
	      	</div>
		    
      	</a>
	</article>
</li>
EOT;

// it all happens here
feedme();
