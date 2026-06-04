<?php
/**
 * Breadcrumb trail.
 *
 * @package Overlaytop
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function overlaytop_breadcrumbs() {
	if ( is_front_page() ) {
		return;
	}
	$sep = '<span class="breadcrumbs__sep" aria-hidden="true">/</span>';
	echo '<nav class="breadcrumbs container" aria-label="' . esc_attr__( 'Breadcrumb', 'overlaytop' ) . '"><ol>';
	echo '<li><a href="' . esc_url( home_url( '/' ) ) . '">' . esc_html__( 'Home', 'overlaytop' ) . '</a></li>';

	if ( is_singular( 'post' ) ) {
		$cats = get_the_category();
		if ( ! empty( $cats ) ) {
			echo $sep . '<li><a href="' . esc_url( get_category_link( $cats[0]->term_id ) ) . '">' . esc_html( $cats[0]->name ) . '</a></li>';
		}
		echo $sep . '<li aria-current="page">' . esc_html( get_the_title() ) . '</li>';
	} elseif ( is_category() || is_tax() || is_tag() ) {
		echo $sep . '<li aria-current="page">' . esc_html( single_term_title( '', false ) ) . '</li>';
	} elseif ( is_page() ) {
		echo $sep . '<li aria-current="page">' . esc_html( get_the_title() ) . '</li>';
	} elseif ( is_search() ) {
		echo $sep . '<li aria-current="page">' . esc_html__( 'Search', 'overlaytop' ) . '</li>';
	}
	echo '</ol></nav>';
}
