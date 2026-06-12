<?php
/**
 * Main fallback template (blog index, archives, search).
 *
 * @package Overlaytop
 */

get_header();
?>
<div class="container section">
	<header class="section__head">
		<div>
			<span class="eyebrow">
				<?php
				if ( is_search() ) {
					esc_html_e( 'Search', 'overlaytop' );
				} elseif ( is_category() || is_tag() || is_tax() ) {
					esc_html_e( 'Category', 'overlaytop' );
				} else {
					esc_html_e( 'Latest', 'overlaytop' );
				}
				?>
			</span>
			<h1 style="margin-top:.4rem">
				<?php
				if ( is_search() ) {
					/* translators: %s: search query. */
					printf( esc_html__( 'Results for &ldquo;%s&rdquo;', 'overlaytop' ), esc_html( get_search_query() ) );
				} elseif ( is_category() || is_tag() || is_tax() ) {
					single_term_title();
				} elseif ( is_home() && ! is_front_page() ) {
					single_post_title();
				} else {
					esc_html_e( 'From the blog', 'overlaytop' );
				}
				?>
			</h1>
			<?php
			if ( ( is_category() || is_tag() || is_tax() ) && term_description() ) {
				echo '<p class="lede" style="margin-top:.6rem">' . wp_kses_post( term_description() ) . '</p>';
			}
			?>
		</div>
	</header>

	<?php if ( have_posts() ) : ?>
		<div class="card-grid">
			<?php
			while ( have_posts() ) :
				the_post();
				get_template_part( 'template-parts/card' );
			endwhile;
			?>
		</div>
		<?php
		the_posts_pagination(
			array(
				'mid_size'  => 1,
				'prev_text' => __( '&larr; Newer', 'overlaytop' ),
				'next_text' => __( 'Older &rarr;', 'overlaytop' ),
			)
		);
		?>
	<?php else : ?>
		<p><?php esc_html_e( 'Nothing here yet, new guides are on the way.', 'overlaytop' ); ?></p>
	<?php endif; ?>
</div>
<?php
get_footer();
