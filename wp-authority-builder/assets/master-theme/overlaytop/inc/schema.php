<?php
/**
 * JSON-LD structured data + Open Graph / Twitter meta.
 *
 * @package Overlaytop
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * A clean meta description for the current view.
 */
function overlaytop_meta_description() {
	if ( is_singular() ) {
		$d = has_excerpt() ? get_the_excerpt() : wp_trim_words( wp_strip_all_tags( get_post_field( 'post_content', get_the_ID() ) ), 30 );
	} elseif ( is_category() || is_tax() || is_tag() ) {
		$d = term_description() ? wp_strip_all_tags( term_description() ) : get_bloginfo( 'description' );
	} else {
		$d = get_bloginfo( 'description' );
	}
	return trim( preg_replace( '/\s+/', ' ', (string) $d ) );
}

/**
 * Meta description, Open Graph, Twitter Card tags.
 */
function overlaytop_meta_tags() {
	$desc  = overlaytop_meta_description();
	$title = wp_get_document_title();
	$img   = ( is_singular() && has_post_thumbnail() ) ? get_the_post_thumbnail_url( get_the_ID(), 'overlaytop_hero' ) : '';
	$url   = is_singular() ? get_permalink() : home_url( add_query_arg( array(), null ) );

	echo "\n" . '<meta name="description" content="' . esc_attr( $desc ) . '">' . "\n";
	echo '<meta property="og:site_name" content="' . esc_attr( get_bloginfo( 'name' ) ) . '">' . "\n";
	echo '<meta property="og:title" content="' . esc_attr( $title ) . '">' . "\n";
	echo '<meta property="og:description" content="' . esc_attr( $desc ) . '">' . "\n";
	echo '<meta property="og:type" content="' . ( is_singular( 'post' ) ? 'article' : 'website' ) . '">' . "\n";
	echo '<meta property="og:url" content="' . esc_url( $url ) . '">' . "\n";
	if ( $img ) {
		echo '<meta property="og:image" content="' . esc_url( $img ) . '">' . "\n";
	}
	echo '<meta name="twitter:card" content="summary_large_image">' . "\n";
	echo '<meta name="twitter:title" content="' . esc_attr( $title ) . '">' . "\n";
	echo '<meta name="twitter:description" content="' . esc_attr( $desc ) . '">' . "\n";
	if ( $img ) {
		echo '<meta name="twitter:image" content="' . esc_url( $img ) . '">' . "\n";
	}
}
// Skip the theme's own meta tags when an SEO plugin (e.g. Rank Math) is handling them.
if ( ! function_exists( 'rank_math' ) ) {
	add_action( 'wp_head', 'overlaytop_meta_tags', 5 );
}

/**
 * The JSON-LD @graph: Organization, WebSite, and (on posts) Person, BlogPosting,
 * BreadcrumbList and FAQPage.
 */
function overlaytop_json_ld() {
	$logo = get_theme_file_uri( 'assets/img/logo.png' );
	$cl   = get_theme_mod( 'custom_logo' );
	if ( $cl ) {
		$src = wp_get_attachment_image_src( $cl, 'full' );
		if ( $src ) {
			$logo = $src[0];
		}
	}

	$org = array(
		'@type' => 'Organization',
		'@id'   => home_url( '/#org' ),
		'name'  => get_bloginfo( 'name' ),
		'url'   => home_url( '/' ),
		'logo'  => array( '@type' => 'ImageObject', 'url' => $logo ),
	);
	$website = array(
		'@type'           => 'WebSite',
		'@id'             => home_url( '/#website' ),
		'name'            => get_bloginfo( 'name' ),
		'url'             => home_url( '/' ),
		'publisher'       => array( '@id' => home_url( '/#org' ) ),
		'potentialAction' => array(
			'@type'       => 'SearchAction',
			'target'      => array( '@type' => 'EntryPoint', 'urlTemplate' => home_url( '/?s={search_term_string}' ) ),
			'query-input' => 'required name=search_term_string',
		),
	);
	$graph = array( $org, $website );

	if ( is_singular( 'post' ) ) {
		$post_id   = get_the_ID();
		$author_id = (int) get_the_author_meta( 'ID' );
		$author_url = get_author_posts_url( $author_id );

		$person = array(
			'@type'       => 'Person',
			'@id'         => $author_url . '#person',
			'name'        => get_the_author_meta( 'display_name', $author_id ),
			'url'         => $author_url,
			'description' => get_the_author_meta( 'description', $author_id ),
		);
		$avatar = get_avatar_url( $author_id, array( 'size' => 300 ) );
		if ( $avatar ) {
			$person['image'] = array( '@type' => 'ImageObject', 'url' => $avatar );
		}

		$blog = array(
			'@type'            => 'BlogPosting',
			'@id'              => get_permalink() . '#article',
			'headline'         => get_the_title(),
			'description'      => overlaytop_meta_description(),
			'datePublished'    => get_the_date( 'c' ),
			'dateModified'     => get_the_modified_date( 'c' ),
			'author'           => array( '@id' => $author_url . '#person' ),
			'publisher'        => array( '@id' => home_url( '/#org' ) ),
			'mainEntityOfPage' => get_permalink(),
			'isPartOf'         => array( '@id' => home_url( '/#website' ) ),
		);
		$img = get_the_post_thumbnail_url( $post_id, 'overlaytop_hero' );
		if ( $img ) {
			$blog['image'] = array( '@type' => 'ImageObject', 'url' => $img );
		}

		$items = array( array( '@type' => 'ListItem', 'position' => 1, 'name' => 'Home', 'item' => home_url( '/' ) ) );
		$pos   = 2;
		$cats  = get_the_category();
		if ( ! empty( $cats ) ) {
			$items[] = array( '@type' => 'ListItem', 'position' => $pos++, 'name' => $cats[0]->name, 'item' => get_category_link( $cats[0]->term_id ) );
		}
		$items[]   = array( '@type' => 'ListItem', 'position' => $pos, 'name' => get_the_title(), 'item' => get_permalink() );
		$breadcrumb = array( '@type' => 'BreadcrumbList', '@id' => get_permalink() . '#breadcrumb', 'itemListElement' => $items );

		$graph[] = $person;
		$graph[] = $blog;
		$graph[] = $breadcrumb;

		$content = get_post_field( 'post_content', $post_id );
		if ( preg_match_all( '/faq__q[^>]*>(.*?)<\/p>.*?faq__a[^>]*>(.*?)<\/div>/is', $content, $mm, PREG_SET_ORDER ) ) {
			$faqs = array();
			foreach ( $mm as $m ) {
				$q = trim( wp_strip_all_tags( $m[1] ) );
				$a = trim( wp_strip_all_tags( $m[2] ) );
				if ( $q && $a ) {
					$faqs[] = array( '@type' => 'Question', 'name' => $q, 'acceptedAnswer' => array( '@type' => 'Answer', 'text' => $a ) );
				}
			}
			if ( $faqs ) {
				$graph[] = array( '@type' => 'FAQPage', '@id' => get_permalink() . '#faq', 'mainEntity' => $faqs );
			}
		}
	}

	$data = array( '@context' => 'https://schema.org', '@graph' => $graph );
	echo "\n" . '<script type="application/ld+json">' . wp_json_encode( $data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . '</script>' . "\n";
}
if ( ! function_exists( 'rank_math' ) ) {
	add_action( 'wp_head', 'overlaytop_json_ld', 10 );
}
