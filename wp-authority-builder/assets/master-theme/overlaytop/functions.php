<?php
/**
 * Overlaytop theme functions.
 *
 * @package Overlaytop
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'OVERLAYTOP_VERSION', '1.0.0' );

/**
 * Theme setup.
 */
function overlaytop_setup() {
	add_theme_support( 'title-tag' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'automatic-feed-links' );
	add_theme_support( 'responsive-embeds' );
	add_theme_support( 'align-wide' );
	add_theme_support(
		'custom-logo',
		array(
			'height'      => 48,
			'width'       => 200,
			'flex-height' => true,
			'flex-width'  => true,
		)
	);
	add_theme_support( 'html5', array( 'search-form', 'gallery', 'caption', 'style', 'script', 'navigation-widgets', 'comment-form', 'comment-list' ) );

	register_nav_menus(
		array(
			'primary' => __( 'Primary Menu', 'overlaytop' ),
			'footer'  => __( 'Footer Menu', 'overlaytop' ),
		)
	);

	add_image_size( 'overlaytop_hero', 1240, 760, true );
	add_image_size( 'overlaytop_card', 760, 480, true );
	add_image_size( 'overlaytop_thumb', 180, 130, true );

	if ( ! isset( $GLOBALS['content_width'] ) ) {
		$GLOBALS['content_width'] = 720;
	}
}
add_action( 'after_setup_theme', 'overlaytop_setup' );

/**
 * Enqueue styles + scripts.
 */
function overlaytop_assets() {
	$ver = OVERLAYTOP_VERSION;

	wp_enqueue_style(
		'overlaytop-fonts',
		'https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,400;9..144,500;9..144,600;9..144,700&family=Inter:wght@400;500;600;700&display=swap',
		array(),
		null
	);
	wp_enqueue_style( 'overlaytop-tokens', get_theme_file_uri( 'assets/css/tokens.css' ), array(), $ver );
	wp_enqueue_style( 'overlaytop-main', get_theme_file_uri( 'assets/css/main.css' ), array( 'overlaytop-tokens' ), $ver );

	wp_enqueue_script( 'overlaytop-main', get_theme_file_uri( 'assets/js/main.js' ), array(), $ver, true );

	if ( is_singular() && comments_open() && get_option( 'thread_comments' ) ) {
		wp_enqueue_script( 'comment-reply' );
	}
}
add_action( 'wp_enqueue_scripts', 'overlaytop_assets' );

/**
 * Preconnect to the Google Fonts CDN for faster first paint.
 */
function overlaytop_resource_hints( $hints, $relation ) {
	if ( 'preconnect' === $relation ) {
		$hints[] = array(
			'href'        => 'https://fonts.gstatic.com',
			'crossorigin' => 'anonymous',
		);
	}
	return $hints;
}
add_filter( 'wp_resource_hints', 'overlaytop_resource_hints', 10, 2 );

/**
 * Excerpt tweaks.
 */
function overlaytop_excerpt_length() {
	return 26;
}
add_filter( 'excerpt_length', 'overlaytop_excerpt_length' );

function overlaytop_excerpt_more() {
	return '&hellip;';
}
add_filter( 'excerpt_more', 'overlaytop_excerpt_more' );

/**
 * Estimated reading time (minutes) for a post.
 */
function overlaytop_reading_time( $post_id = null ) {
	$post_id = $post_id ? $post_id : get_the_ID();
	$words   = str_word_count( wp_strip_all_tags( get_post_field( 'post_content', $post_id ) ) );
	return max( 1, (int) round( $words / 220 ) );
}

/**
 * Body classes for design states.
 */
function overlaytop_body_classes( $classes ) {
	if ( is_singular( 'post' ) ) {
		$classes[] = 'is-single';
	}
	if ( ! is_active_sidebar( 'sidebar-1' ) ) {
		$classes[] = 'no-sidebar';
	}
	return $classes;
}
add_filter( 'body_class', 'overlaytop_body_classes' );

/**
 * Load includes (schema, breadcrumbs, template tags) when present.
 */
foreach ( array( 'template-tags', 'breadcrumbs', 'schema' ) as $inc ) {
	$file = get_theme_file_path( "inc/{$inc}.php" );
	if ( file_exists( $file ) ) {
		require_once $file;
	}
}
