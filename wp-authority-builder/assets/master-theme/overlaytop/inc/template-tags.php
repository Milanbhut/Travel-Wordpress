<?php
/**
 * Template tags: heading anchors, table of contents, author box, related posts.
 *
 * @package Overlaytop
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Add id anchors to h2/h3 in the post body so the TOC can link to them.
 */
function overlaytop_heading_ids( $content ) {
	if ( ! is_singular() || ! in_the_loop() || ! is_main_query() ) {
		return $content;
	}
	return preg_replace_callback(
		'/<(h2|h3)([^>]*)>(.*?)<\/\1>/is',
		function ( $m ) {
			$attrs = $m[2];
			if ( false === strpos( $attrs, 'id=' ) ) {
				$attrs .= ' id="' . esc_attr( sanitize_title( wp_strip_all_tags( $m[3] ) ) ) . '"';
			}
			return '<' . $m[1] . $attrs . '>' . $m[3] . '</' . $m[1] . '>';
		},
		$content
	);
}
add_filter( 'the_content', 'overlaytop_heading_ids', 8 );

/**
 * Build a table of contents from the post's h2 headings.
 */
function overlaytop_get_toc( $post_id = null ) {
	$content = get_post_field( 'post_content', $post_id ? $post_id : get_the_ID() );
	if ( ! preg_match_all( '/<h2[^>]*>(.*?)<\/h2>/is', $content, $mm ) ) {
		return '';
	}
	$items = '';
	foreach ( $mm[1] as $h ) {
		$text = trim( wp_strip_all_tags( $h ) );
		if ( '' === $text ) {
			continue;
		}
		$items .= '<li><a href="#' . esc_attr( sanitize_title( $text ) ) . '">' . esc_html( $text ) . '</a></li>';
	}
	if ( '' === $items ) {
		return '';
	}
	return '<nav class="toc" aria-label="' . esc_attr__( 'Table of contents', 'overlaytop' ) . '">'
		. '<p class="toc__title">' . esc_html__( 'In this guide', 'overlaytop' ) . '</p><ol>' . $items . '</ol></nav>';
}

/**
 * Premium author box.
 */
function overlaytop_author_box( $author_id = null ) {
	$author_id = $author_id ? $author_id : (int) get_the_author_meta( 'ID' );
	$name      = get_the_author_meta( 'display_name', $author_id );
	$bio       = get_the_author_meta( 'description', $author_id );
	$role      = get_the_author_meta( 'overlaytop_role', $author_id );
	if ( ! $bio ) {
		return;
	}
	$url   = get_author_posts_url( $author_id );
	$count = (int) count_user_posts( $author_id, 'post', true );
	?>
	<aside class="author-bio container">
		<div class="author-bio__avatar"><?php echo get_avatar( $author_id, 112, '', $name ); ?></div>
		<div class="author-bio__body">
			<p class="author-bio__eyebrow"><?php esc_html_e( 'About the author', 'overlaytop' ); ?></p>
			<h3 class="author-bio__name"><a href="<?php echo esc_url( $url ); ?>"><?php echo esc_html( $name ); ?></a></h3>
			<?php if ( $role ) : ?><p class="author-bio__role"><?php echo esc_html( $role ); ?></p><?php endif; ?>
			<p class="author-bio__text"><?php echo esc_html( $bio ); ?></p>
			<div class="author-bio__meta"><span><?php printf( esc_html__( '%d articles published', 'overlaytop' ), $count ); ?></span></div>
		</div>
	</aside>
	<?php
}

/**
 * Related posts (same category).
 */
function overlaytop_related_posts( $post_id = null ) {
	$post_id = $post_id ? $post_id : get_the_ID();
	$cats    = wp_get_post_categories( $post_id );
	if ( empty( $cats ) ) {
		return;
	}
	$q = new WP_Query(
		array(
			'category__in'        => $cats,
			'post__not_in'        => array( $post_id ),
			'posts_per_page'      => 3,
			'ignore_sticky_posts' => true,
			'no_found_rows'       => true,
			'orderby'             => 'rand',
		)
	);
	if ( ! $q->have_posts() ) {
		wp_reset_postdata();
		return;
	}
	echo '<section class="section section--soft related"><div class="container">';
	echo '<header class="section__head"><div><span class="eyebrow">' . esc_html__( 'Keep reading', 'overlaytop' ) . '</span><h2 style="margin-top:.4rem">' . esc_html__( 'Related guides', 'overlaytop' ) . '</h2></div></header>';
	echo '<div class="card-grid">';
	while ( $q->have_posts() ) {
		$q->the_post();
		get_template_part( 'template-parts/card' );
	}
	echo '</div></div></section>';
	wp_reset_postdata();
}
