<?php

/*
Plugin Name: All AJAX Comments
Plugin URI: https://planetjon.ca/projects/all-ajax-comments/
Description: Make your comment system fly
Version: 1.0
Requires at least: 3.5.0
Tested up to: 5.7
Requires PHP: 5.4
Author: Jonathan Weatherhead
Author URI: https://planetjon.ca
License: GPL2
License URI: http://www.gnu.org/licenses/gpl-2.0.html
*/

namespace planetjon\wordpress\all_ajax_comments;

use \WP_Query;

add_action( 'wp_enqueue_scripts', function() {
	if( is_singular() ) {
		wp_dequeue_script( 'reply-comments' );
		wp_enqueue_script( 'aac-script', plugins_url( 'scripts.js', __FILE__), [], false, true );
		wp_localize_script( 'aac-script', 'aac_env', [
			'ajaxurl' => admin_url( 'admin-ajax.php' )
		]);
	}
}, 11 );

add_filter( 'comments_template', function() {
	return !wp_doing_ajax() ? plugin_dir_path( __FILE__ ) . 'comments.php' : false;
} );

add_action( 'wp_ajax_aac_load_comments', __NAMESPACE__ . '\aac_load_comments' );
add_action( 'wp_ajax_nopriv_aac_load_comments', __NAMESPACE__ . '\aac_load_comments' );

function aac_load_comments() {
	global $post;
	$postID = filter_input( INPUT_GET, 'postID', FILTER_VALIDATE_INT );

	if( !headers_sent() ) {
		header( 'Content-Type: text/html; charset=' . get_option( 'blog_charset' ) );
		status_header(200);
	}

	$posts = new WP_Query( [ 'p' => $postID ] );
	$GLOBALS['wp_query'] = $posts;

	if( have_posts() ) {
		while( have_posts() ) {
			the_post();
			comments_template();
		}
	}

	die();
}
