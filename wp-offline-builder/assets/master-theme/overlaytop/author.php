<?php
/**
 * Author archive - a profile hero (photo, role, bio, stats) above the author's articles.
 *
 * @package Overlaytop
 */

get_header();

$ot_author = get_queried_object();
$ot_id     = (int) ( isset( $ot_author->ID ) ? $ot_author->ID : 0 );
$ot_name   = get_the_author_meta( 'display_name', $ot_id );
$ot_bio    = get_the_author_meta( 'description', $ot_id );
$ot_role   = get_the_author_meta( 'overlaytop_role', $ot_id );
$ot_count  = (int) count_user_posts( $ot_id, 'post', true );
?>
<?php overlaytop_breadcrumbs(); ?>

<section class="author-hero">
	<div class="author-hero__inner">
		<div class="author-hero__portrait"><?php echo get_avatar( $ot_id, 300, '', $ot_name ); ?></div>
		<p class="author-hero__eyebrow"><?php esc_html_e( 'Author', 'overlaytop' ); ?></p>
		<h1 class="author-hero__name"><?php echo esc_html( $ot_name ); ?></h1>
		<?php if ( $ot_role ) : ?>
			<p class="author-hero__role"><?php echo esc_html( $ot_role ); ?></p>
		<?php endif; ?>
		<?php if ( $ot_bio ) : ?>
			<p class="author-hero__bio"><?php echo esc_html( $ot_bio ); ?></p>
		<?php endif; ?>
		<p class="author-hero__facts">
			<span><b><?php echo (int) $ot_count; ?></b>
			<?php echo esc_html( _n( 'article published', 'articles published', $ot_count, 'overlaytop' ) ); ?></span>
		</p>
	</div>
</section>

<div class="container section">
	<header class="section__head">
		<div>
			<span class="eyebrow"><?php esc_html_e( 'Latest', 'overlaytop' ); ?></span>
			<h2 style="margin-top:.4rem"><?php printf( esc_html__( 'Articles by %s', 'overlaytop' ), esc_html( $ot_name ) ); ?></h2>
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
		<p><?php esc_html_e( 'No articles published yet.', 'overlaytop' ); ?></p>
	<?php endif; ?>
</div>

<?php
get_footer();
