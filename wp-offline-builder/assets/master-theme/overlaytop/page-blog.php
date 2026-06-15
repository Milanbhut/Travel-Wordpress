<?php
/**
 * Template Name: Blog
 *
 * @package Overlaytop
 */

get_header();
$ot_paged = max( 1, (int) get_query_var( 'paged' ), (int) get_query_var( 'page' ) );
$ot_q     = new WP_Query(
	array(
		'post_type'           => 'post',
		'posts_per_page'      => 12,
		'paged'               => $ot_paged,
		'ignore_sticky_posts' => true,
	)
);
?>
<div class="container section">
	<header class="section__head">
		<div>
			<span class="eyebrow"><?php esc_html_e( 'The blog', 'overlaytop' ); ?></span>
			<h1 style="margin-top:.4rem"><?php the_title(); ?></h1>
			<p class="lede" style="margin-top:.6rem"><?php esc_html_e( 'Every guide we publish, newest first.', 'overlaytop' ); ?></p>
		</div>
	</header>

	<?php if ( $ot_q->have_posts() ) : ?>
		<div class="card-grid">
			<?php
			while ( $ot_q->have_posts() ) :
				$ot_q->the_post();
				get_template_part( 'template-parts/card' );
			endwhile;
			?>
		</div>
		<div class="pagination">
			<?php
			echo paginate_links(
				array(
					'total'     => $ot_q->max_num_pages,
					'current'   => $ot_paged,
					'mid_size'  => 1,
					'prev_text' => __( '&larr; Newer', 'overlaytop' ),
					'next_text' => __( 'Older &rarr;', 'overlaytop' ),
				)
			);
			?>
		</div>
	<?php else : ?>
		<p><?php esc_html_e( 'New guides are on the way.', 'overlaytop' ); ?></p>
	<?php endif; ?>
</div>
<?php
wp_reset_postdata();
get_footer();
