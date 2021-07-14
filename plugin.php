<?php

/*
Plugin Name: WP AJAX Comments
Plugin URI: https://planetjon.ca/projects/wp-ajax-comments/
Description: Make your comment system fly with a full AJAX implementation of your existing template.
Version: 1.0
Requires at least: 3.5.0
Tested up to: 5.7
Requires PHP: 5.4
Author: Jonathan Weatherhead
Author URI: https://planetjon.ca
License: GPL3
License URI: https://www.gnu.org/licenses/gpl-3.0.html
*/

namespace planetjon\wordpress\wp_ajax_comments;

use \WP_Query;

add_action( 'wp_enqueue_scripts', function() {
	if( is_singular() ) {
		wp_dequeue_script( 'reply-comments' );
		wp_enqueue_script( 'wac-script', plugins_url( 'scripts.js', __FILE__), [], false, true );
		wp_localize_script( 'wac-script', 'wac_env', [
			'ajaxurl' => admin_url( 'admin-ajax.php' ),
			'pending_msg' => __( '&#x29D6; Thanks! Your comment will be available shortly. If you dont see it immediately, it is pending moderation.', 'wp-ajax-comments' ),
			'error_msg' => __( '&#x274C; Something went wrong. Please try again later.', 'wp-ajax-comments' )
		]);
	}
}, 11 );

add_filter( 'comments_template', function() {
	return !wp_doing_ajax() ? plugin_dir_path( __FILE__ ) . 'comments.php' : false;
} );

add_action( 'wp_ajax_wac_load_comments', __NAMESPACE__ . '\wac_load_comments' );
add_action( 'wp_ajax_nopriv_wac_load_comments', __NAMESPACE__ . '\wac_load_comments' );

function wac_load_comments() {
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
