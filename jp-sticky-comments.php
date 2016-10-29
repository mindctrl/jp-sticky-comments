<?php
/**
 * Plugin Name: Sticky Comments
 * Description: Allows you to pin comments to the top of the comments list.
 * Version: 1.0
 * Author: John Parris
 * Author URI: https://www.johnparris.com/
 */
namespace JP\StickyComments;

defined( 'WPINC' ) or die;

define( 'JP_STICKY_COMMENTS_PATH', plugin_dir_path( __FILE__ ) );
define( 'JP_STICKY_COMMENTS_URL', plugin_dir_url( __FILE__ ) );

/**
 * Loads the plugin translation files.
 */
function textdomain() {
	load_plugin_textdomain( 'jp-sticky-comments', false, JP_STICKY_COMMENTS_PATH . 'languages' );
}
add_action( 'plugins_loaded', __NAMESPACE__ . '\textdomain' );


function loader() {
	add_filter( 'comments_clauses', __NAMESPACE__ . '\filter_comment_query', 10, 2 );
	add_filter( 'edit_comment_link', __NAMESPACE__ . '\add_sticky_link', 10, 3 );
	add_action( 'wp_enqueue_scripts', __NAMESPACE__ . '\scripts' );
	add_action( 'wp_ajax_jp_sticky_comments_stick_comment', __NAMESPACE__ . '\ajax_stick_comment' );
}
add_action( 'plugins_loaded', __NAMESPACE__ . '\loader' );

/**
 * Adjusts the comments query to order by karma
 */
function filter_comment_query( $clauses, $obj ) {
	$clauses['orderby'] = 'comment_karma DESC';
	return $clauses;
}

function add_sticky_link( $link, $comment_id, $text ) {

	$comment = get_comment( $comment_id );
	if ( ! is_a( $comment, 'WP_Comment' ) ) {
		return $link;
	}

	if ( 99999 == $comment->comment_karma ) {
		$link_text = __( 'Unsticky', 'jp-sticky-comments' );
		$status = 'data-status="sticky"';
	} else {
		$link_text = __( 'Sticky', 'jp-sticky-comments' );
		$status = 'data-status="notsticky"';
	}

	$link .= ' | ';
	$link .= '<a href="' . esc_url( wp_nonce_url( '#' ) ) . '" class="comment-sticky-link" data-id="' . absint( $comment_id ) . '" '. $status .'>' . $link_text . '</a>';
	return $link;
}

function scripts() {
	if ( is_singular() && comments_open() ) {
		wp_enqueue_script( 'jp-sticky-comments-js', JP_STICKY_COMMENTS_URL . '/js/jp-sticky-comments.js', array( 'jquery' ) );
		wp_localize_script(
			'jp-sticky-comments-js',
			'jp_sticky_comments',
			array(
				'ajaxurl'             => esc_url( admin_url( 'admin-ajax.php' ) ),
				'stick_comment_nonce' => wp_create_nonce( 'jp-sticky-comments-stick-comment-nonce' ),
				'strings' => array(
					'wait'    => __( 'Please wait...', 'jp-sticky-comments' ),
					'stuck'   => __( 'Unsticky', 'jp-sticky-comments' ),
					'unstuck' => __( 'Sticky', 'jp-sticky-comments' ),
					'error'   => __( 'An error occurred', 'jp-sticky-comments' )
				)
			)
		);
	}
}

function ajax_stick_comment() {

	if ( ! current_user_can( 'moderate_comments' ) ) {
		wp_send_json( array(
			'result' => 'failed',
			'reason' => 'access',
			'notice' => __( 'You do not have access to stick comments.', 'jp-sticky-comments' ),
		) );
	}

	check_ajax_referer( 'jp-sticky-comments-stick-comment-nonce', 'check' );

	if ( empty( $_POST['comment_status'] ) || ! in_array( $_POST['comment_status'], array( 'sticky', 'notsticky' ) ) ) {
		return;
	}

	$karma = 'notsticky' === $_POST['comment_status'] ? 99999 : 0;

	$result = wp_update_comment( array(
		'comment_ID'    => absint( $_POST['comment_id'] ),
		'comment_karma' => $karma
	) );

	wp_send_json( array(
		'result' => $result,
		'karma' => $karma,
		'rawpost' => $_POST
	) );
}

//@todo add comment class so themes can style