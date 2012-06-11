<?php
/*
Plugin Name: FeedBurner FeedSmith Mod
Plugin URI:
Description: This is a plugin originally authored by <a href="http://www.orderedlist.com/">Steve Smith</a>. The modification allows you to redirect feeds for all post type and taxonomy archives both default and custom.
Author: Steve Smith, Jiayu (James) Ji, Modified by Robert O'Rourke
Author URI: http://interconnectit.com
Version: 1.0.2
*/

/*
    Copyright 2011  FeedBurner FeedSmith Mod  (email : rob@interconnectit.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

$data = array(
	'feedburner_url'		=> '',
	'feedburner_comments_url'	=> ''
);

$ol_flash = '';

function ol_is_authorized() {
	global $user_level;
	if ( function_exists( "current_user_can" ) ) {
		return current_user_can( 'activate_plugins' );
	} else {
		return $user_level > 5;
	}
}

add_option( 'FeedBurner FeedSmith Extend', $data, 'FeedBurner FeedSmith Extend Replacement Options' );

$feedburner_settings = get_option( 'feedburner_settings' );

function fb_is_hash_valid( $form_hash ) {
	$ret = false;
	$saved_hash = fb_retrieve_hash();
	if ( $form_hash === $saved_hash ) {
		$ret = true;
	}
	return $ret;
}

function fb_generate_hash() {
	return md5(uniqid(rand(), TRUE));
}

function fb_store_hash($generated_hash) {
	return update_option('feedsetting_token',$generated_hash,'FeedBurner Security Hash');
}

function fb_retrieve_hash() {
	$ret = get_option('feedsetting_token');
	return $ret;
}

function ol_add_feedburner_options_page() {
	if (function_exists('add_options_page')) {
		add_options_page('FeedBurner', 'FeedBurner', 8, basename(__FILE__), 'ol_feedburner_options_subpanel');
	}
}

function ol_feedburner_options_subpanel() {
	global $ol_flash, $feedburner_settings, $_POST, $wp_rewrite;

	if (ol_is_authorized()) {

		$updates_field = array();

		// custom taxonomies and terms
		foreach( get_taxonomies( array( 'public' => true ) ) as $taxonomy ) {
			$terms = get_terms( $taxonomy );
			foreach( $terms as $term ) {
				$field = "feedburner_{$taxonomy}_{$term->term_id}";
				if ( isset( $_POST[ $field ] ) )
					$updates_field[ $field ] = esc_url_raw( $_POST[ $field ] );
			}
		}

		// all posts types
		foreach( get_post_types( array( 'public' => true, 'has_archive' => true ) ) as $post_type ) {
			$field = "feedburner_post_type_{$post_type}";
			if ( isset( $_POST[ $field ] ) )
				$updates_field[ $field ] = esc_url_raw( $_POST[ $field ] );
		}


		// Easiest test to see if we have been submitted to
		if(isset($_POST['feedburner_url']) || isset($_POST['feedburner_comments_url']) || (count($updates_field)>0)) {
			// Now we check the hash, to make sure we are not getting CSRF
			if(fb_is_hash_valid($_POST['token'])) {

				// update the category feeds
				if (count($updates_field)>0)
					foreach($updates_field as $key=>$val)
					{
						$feedburner_settings[$key] = $val;
						update_option('feedburner_settings',$feedburner_settings);
						$ol_flash = "Your settings have been saved.";
					}
				if (isset($_POST['feedburner_url'])) {
					$feedburner_settings['feedburner_url'] = esc_url_raw( $_POST['feedburner_url'] );
					update_option('feedburner_settings',$feedburner_settings);
					$ol_flash = "Your settings have been saved.";
				}
				if (isset($_POST['feedburner_comments_url'])) {
					$feedburner_settings['feedburner_comments_url'] = esc_url_raw( $_POST['feedburner_comments_url'] );
					update_option('feedburner_settings',$feedburner_settings);
					$ol_flash = "Your settings have been saved.";
				}
			} else {
				// Invalid form hash, possible CSRF attempt
				$ol_flash = "Security hash missing.";
			} // endif fb_is_hash_valid
		} // endif isset(feedburner_url)
	} else {
		$ol_flash = "You don't have access rights.";
	}

	if ($ol_flash != '') echo '<div id="message" class="updated fade"><p>' . $ol_flash . '</p></div>';

	if (ol_is_authorized()) {
		wp_enqueue_script(‘jquery’);
		$temp_hash = fb_generate_hash();
		fb_store_hash($temp_hash);

		echo '<div class="wrap">';
		echo '<h2>FeedBurner FeedSmith</h2>';
		echo '<p>Enter your FeedBurner URLs in the inputs below. You can set alternative feeds for post type archives and taxonomy terms as well as the main feeds.</p>
		<form action="" method="post">
			<input type="hidden" name="redirect" value="true" />
			<input type="hidden" name="token" value="' . fb_retrieve_hash() . '" />

		<table class="form-table">
			<tbody id="main-feeds">
				<tr>
					<th><strong>Main Feed:</strong></th>
					<td><input type="text" name="feedburner_url" value="' . htmlentities($feedburner_settings['feedburner_url']) . '" size="45" /></td>
				</tr>
				<tr>
					<th><strong>Comments Feed:</strong></th>
					<td><input type="text" name="feedburner_comments_url" value="' . htmlentities($feedburner_settings['feedburner_comments_url']) . '" size="45" /></td>
				</tr>
			</tbody>';

		echo '
			<tbody id="post-type-' . sanitize_html_class( $post_type->name ) . '">';
			echo '
				<tr><th colspan="2"><h3>Post type feeds:</h3></tr></th>';

		// post type feeds
		foreach( get_post_types( array( 'public' => true, 'has_archive' => true ), 'objects' ) as $post_type ) {

			echo "
				<tr>
					<th>{$post_type->labels->name} Feed:</th>
					<td><input type=\"text\" name=\"feedburner_post_type_{$post_type->name}\" value=\"" . htmlentities($feedburner_settings['feedburner_post_type_'.$post_type->name] ) . "\" size=\"45\" /></td>
				</tr>";

		}

		echo '
			</tbody>';

		// custom taxonomy feeds
		foreach( get_taxonomies( array( 'public' => true ), 'objects' ) as $taxonomy ) {

			echo '
			<tbody id="taxonomy-' . sanitize_html_class( $taxonomy->name ) . '">';
			echo '
				<tr><th colspan="2"><h3>' . $taxonomy->labels->singular_name . ' feeds:</h3>';
			//if ( $taxonomy->hierarchical )
			//	echo '<br /> <input type="button" value="Show all sub terms" name="showAll" onclick="showSubCat(this);"/>';
			echo '</tr></th>';

			$terms = get_terms( $taxonomy->name, array( 'hide_empty' => false, 'parent' => 0 ) );
			foreach( $terms as $term ) {
				echo "
				<tr>
					<th>{$term->name} Feed:</th>
					<td><input type=\"text\" name=\"feedburner_{$taxonomy->name}_{$term->term_id}\" value=\"" . htmlentities($feedburner_settings['feedburner_'.$taxonomy->name.'_'.$term->term_id]) . "\" size=\"45\" /></td>
				</tr>";
			}

			echo '
			</tbody>';

		}

		echo '
		</table>';

		echo '<p><input type="submit" value="Save" class="button button-primary" /></p>
		</form>';
		echo '</div>';
	} else {
		echo '<div class="wrap"><p>Sorry, you are not allowed to access this page.</p></div>';
	}
}

add_action('wp_ajax_my_action', 'my_action_callback');

function my_action_callback() {
    global $feedburner_settings;
    $show = $_POST['show'];
    if($show == 'showAll'){
			echo '<input type="button" value="Show Top Level Only" name="showParent" onclick="showSubCat(this);"/><br/>';
			$cats=get_categories("orderby=id&hide_empty=0");
		}else{
			echo '<input type="button" value="Show All Sub-Category" name="showAll" onclick="showSubCat(this);"/><br/>';
			$cats=get_categories("orderby=id&hide_empty=0&parent=0");
		}
    foreach($cats as $cat)
		echo "<strong>$cat->name</strong> &nbsp;&nbsp;Feed:<input type=\"text\" name=\"feedburner_category_$cat->term_id\" value=\"" . htmlentities($feedburner_settings['feedburner_category_'.$cat->term_id]) . "\" size=\"45\" /><br/>";

    die(); // this is required to return a proper result

}

function ol_feed_redirect() {
	global $wp, $feedburner_settings, $feed, $withcomments;

	// main feed
	if ( is_feed() && $feed != 'comments-rss2' && ! is_single() && ! is_tax() && ! is_category() && ! is_tag() && ! is_post_type_archive() && ( $withcomments != 1 ) && trim( $feedburner_settings['feedburner_url'] ) != '' ) {
		if ( function_exists( 'status_header' ) ) status_header( 302 );
		header( "Location:" . trim( $feedburner_settings[ 'feedburner_url' ] ) );
		header( "HTTP/1.1 302 Temporary Redirect" );
		exit();
	}

	// is term
	elseif ( is_feed() && $feed != 'comments-rss2' && ! is_single() && ( is_tax() || is_category() || is_tag() ) && ( $withcomments != 1 ) ) {

		$term = get_queried_object();
		if( trim( $feedburner_settings[ "feedburner_{$term->taxonomy}_{$term->term_id}" ] ) != '' ) {
			if ( function_exists( 'status_header' ) ) status_header( 302 );
			header( "Location:" . trim( $feedburner_settings[ "feedburner_{$term->taxonomy}_{$term->term_id}" ] ) );
			header( "HTTP/1.1 302 Temporary Redirect" );
			exit();
		}

	}

	// post type archive
	elseif ( is_feed() && $feed != 'comments-rss2' && ! is_single() && is_post_type_archive() && $withcomments != 1 ) {

		$post_type = get_queried_object();
		if( trim( $feedburner_settings[ "feedburner_post_type_{$post_type->name}" ] ) != '' ) {
			if ( function_exists( 'status_header' ) ) status_header( 302 );
			header( "Location:" . trim( $feedburner_settings[ "feedburner_post_type_{$post_type->name}" ] ) );
			header( "HTTP/1.1 302 Temporary Redirect" );
			exit();
		}

	}

	// comment feed
	elseif ( is_feed() && ( $feed == 'comments-rss2' || $withcomments == 1 ) && trim( $feedburner_settings[ 'feedburner_comments_url' ] ) != '' ) {
		if ( function_exists( 'status_header' ) ) status_header( 302 );
		header( "Location:" . trim( $feedburner_settings[ 'feedburner_comments_url' ] ) );
		header( "HTTP/1.1 302 Temporary Redirect" );
		exit();
	}

}

function ol_check_url() {
	global $feedburner_settings;
	switch (basename($_SERVER['PHP_SELF'])) {
		case 'wp-rss.php':
		case 'wp-rss2.php':
		case 'wp-atom.php':
		case 'wp-rdf.php':
			if (trim($feedburner_settings['feedburner_url']) != '') {
				if (function_exists('status_header')) status_header( 302 );
				header("Location:" . trim($feedburner_settings['feedburner_url']));
				header("HTTP/1.1 302 Temporary Redirect");
				exit();
			}
			break;
		case 'wp-commentsrss2.php':
			if (trim($feedburner_settings['feedburner_comments_url']) != '') {
				if (function_exists('status_header')) status_header( 302 );
				header("Location:" . trim($feedburner_settings['feedburner_comments_url']));
				header("HTTP/1.1 302 Temporary Redirect");
				exit();
			}
			break;
	}
}

if (!preg_match("/feedburner|feedvalidator/i", $_SERVER['HTTP_USER_AGENT'])) {
	add_action('template_redirect', 'ol_feed_redirect');
	add_action('init','ol_check_url');
}

add_action('admin_menu', 'ol_add_feedburner_options_page');

?>
