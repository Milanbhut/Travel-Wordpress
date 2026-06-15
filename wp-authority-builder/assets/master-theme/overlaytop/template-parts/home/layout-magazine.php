<?php
/**
 * Home layout: MAGAZINE - a dense, print-magazine editorial front.
 * Split featured LEAD (big image + kicker + serif title + excerpt + date) ->
 * mixed asymmetric grid (1 large + small recent posts with kickers + hairline rules) ->
 * per-category horizontal STRIPS (3 categories, compact card rows) -> newsletter.
 * Scoped under .home--magazine; styled by assets/css/home/magazine.css.
 *
 * @package Overlaytop
 */

$ot_recent = new WP_Query(
	array(
		'posts_per_page'      => 7,
		'ignore_sticky_posts' => true,
		'no_found_rows'       => true,
		'post_status'         => 'publish',
	)
);
$ot_posts = $ot_recent->posts;
wp_reset_postdata();

/**
 * Print a small uppercase category kicker for a post.
 *
 * @param WP_Post $ot_post Post object.
 */
if ( ! function_exists( 'overlaytop_mag_kicker' ) ) {
function overlaytop_mag_kicker( $ot_post ) {
	$ot_cats = get_the_category( $ot_post->ID );
	if ( empty( $ot_cats ) ) {
		return;
	}
	?>
	<a class="mag-kicker" href="<?php echo esc_url( get_category_link( $ot_cats[0]->term_id ) ); ?>"><?php echo esc_html( $ot_cats[0]->name ); ?></a>
	<?php
}
}
?>
<div class="home home--magazine">

<?php /* ---------- 1. SPLIT FEATURED LEAD ---------- */ ?>
<?php if ( ! empty( $ot_posts ) ) : $ot_lead = $ot_posts[0]; ?>
	<section class="mag-lead">
		<div class="container mag-lead__inner">
			<a class="mag-lead__media" href="<?php echo esc_url( get_permalink( $ot_lead ) ); ?>" tabindex="-1" aria-hidden="true">
				<?php echo get_the_post_thumbnail( $ot_lead, 'overlaytop_hero', array( 'loading' => 'eager' ) ); ?>
			</a>
			<div class="mag-lead__body">
				<?php overlaytop_mag_kicker( $ot_lead ); ?>
				<h1 class="mag-lead__title"><a href="<?php echo esc_url( get_permalink( $ot_lead ) ); ?>"><?php echo esc_html( get_the_title( $ot_lead ) ); ?></a></h1>
				<p class="mag-lead__excerpt"><?php echo esc_html( wp_trim_words( get_the_excerpt( $ot_lead ), 38 ) ); ?></p>
				<span class="mag-lead__date"><?php echo esc_html( get_the_date( '', $ot_lead ) ); ?></span>
			</div>
		</div>
	</section>
<?php endif; ?>

<?php /* ---------- 2. MIXED ASYMMETRIC GRID (1 large + recent) ---------- */ ?>
<?php
$ot_grid = array_slice( $ot_posts, 1, 5 );
if ( ! empty( $ot_grid ) ) :
	$ot_big   = $ot_grid[0];
	$ot_small = array_slice( $ot_grid, 1 );
	?>
	<section class="section mag-grid" id="latest">
		<div class="container">
			<header class="mag-grid__head">
				<span class="mag-eyebrow"><?php esc_html_e( 'Latest', 'overlaytop' ); ?></span>
				<h2 class="mag-grid__heading"><?php esc_html_e( 'The dispatch', 'overlaytop' ); ?></h2>
			</header>
			<div class="mag-grid__layout">
				<a class="mag-grid__lead" href="<?php echo esc_url( get_permalink( $ot_big ) ); ?>">
					<span class="mag-grid__lead-media"><?php echo get_the_post_thumbnail( $ot_big, 'overlaytop_card', array( 'loading' => 'lazy' ) ); ?></span>
					<span class="mag-grid__lead-body">
						<?php overlaytop_mag_kicker( $ot_big ); ?>
						<span class="mag-grid__lead-title"><?php echo esc_html( get_the_title( $ot_big ) ); ?></span>
						<span class="mag-grid__lead-excerpt"><?php echo esc_html( wp_trim_words( get_the_excerpt( $ot_big ), 22 ) ); ?></span>
						<span class="mag-meta"><?php echo esc_html( get_the_date( '', $ot_big ) ); ?></span>
					</span>
				</a>
				<ul class="mag-grid__list">
					<?php foreach ( $ot_small as $ot_s ) : ?>
						<li class="mag-grid__item">
							<a class="mag-grid__item-link" href="<?php echo esc_url( get_permalink( $ot_s ) ); ?>">
								<span class="mag-grid__item-thumb"><?php echo get_the_post_thumbnail( $ot_s, 'overlaytop_thumb', array( 'loading' => 'lazy' ) ); ?></span>
								<span class="mag-grid__item-body">
									<?php overlaytop_mag_kicker( $ot_s ); ?>
									<span class="mag-grid__item-title"><?php echo esc_html( get_the_title( $ot_s ) ); ?></span>
									<span class="mag-meta"><?php echo esc_html( get_the_date( '', $ot_s ) ); ?></span>
								</span>
							</a>
						</li>
					<?php endforeach; ?>
				</ul>
			</div>
		</div>
	</section>
<?php endif; ?>

<?php /* ---------- 3. PER-CATEGORY HORIZONTAL STRIPS ---------- */ ?>
<?php
$ot_cat_slugs = array_filter( array_map( 'trim', explode( ',', get_theme_mod( 'ot_home_cats', '' ) ) ) );
$ot_cats      = array();
if ( $ot_cat_slugs ) {
	foreach ( $ot_cat_slugs as $ot_cs ) {
		$ot_term = get_category_by_slug( $ot_cs );
		if ( $ot_term ) {
			$ot_cats[] = $ot_term;
		}
	}
}
if ( count( $ot_cats ) < 3 ) {
	$ot_cats = get_categories(
		array(
			'orderby'    => 'count',
			'order'      => 'DESC',
			'number'     => 3,
			'hide_empty' => true,
		)
	);
}
$ot_cats = array_slice( $ot_cats, 0, 3 );
foreach ( $ot_cats as $ot_cat ) :
	$ot_cq = new WP_Query(
		array(
			'cat'                 => $ot_cat->term_id,
			'posts_per_page'      => 4,
			'ignore_sticky_posts' => true,
			'no_found_rows'       => true,
			'post_status'         => 'publish',
		)
	);
	if ( ! $ot_cq->have_posts() ) {
		wp_reset_postdata();
		continue;
	}
	?>
	<section class="section mag-strip">
		<div class="container">
			<header class="mag-strip__head">
				<h2 class="mag-strip__title"><a href="<?php echo esc_url( get_category_link( $ot_cat ) ); ?>"><?php echo esc_html( $ot_cat->name ); ?></a></h2>
				<a class="mag-strip__more" href="<?php echo esc_url( get_category_link( $ot_cat ) ); ?>"><?php esc_html_e( 'All', 'overlaytop' ); ?> <?php echo esc_html( $ot_cat->name ); ?> <span aria-hidden="true">&rarr;</span></a>
			</header>
			<div class="mag-strip__row">
				<?php
				while ( $ot_cq->have_posts() ) :
					$ot_cq->the_post();
					?>
					<a class="mag-strip__card" href="<?php the_permalink(); ?>">
						<span class="mag-strip__card-media">
							<?php
							if ( has_post_thumbnail() ) {
								the_post_thumbnail(
									'overlaytop_card',
									array(
										'loading' => 'lazy',
										'alt'     => the_title_attribute( array( 'echo' => false ) ),
									)
								);
							}
							?>
						</span>
						<span class="mag-strip__card-body">
							<span class="mag-kicker mag-kicker--plain"><?php echo esc_html( $ot_cat->name ); ?></span>
							<span class="mag-strip__card-title"><?php the_title(); ?></span>
							<span class="mag-meta"><?php echo esc_html( get_the_date() ); ?></span>
						</span>
					</a>
					<?php
				endwhile;
				?>
			</div>
		</div>
	</section>
	<?php
	wp_reset_postdata();
endforeach;
?>

<?php /* ---------- 4. NEWSLETTER ---------- */ ?>
<section class="mag-news">
	<div class="container mag-news__inner">
		<div class="mag-news__copy">
			<span class="mag-eyebrow"><?php esc_html_e( 'Newsletter', 'overlaytop' ); ?></span>
			<h2 class="mag-news__title"><?php echo esc_html( get_theme_mod( 'ot_news_title', __( 'Get the weekly dispatch', 'overlaytop' ) ) ); ?></h2>
			<p class="mag-news__sub"><?php echo esc_html( get_theme_mod( 'ot_news_sub', __( 'One useful email a week, the best new guides and a few things worth your time. No spam, unsubscribe anytime.', 'overlaytop' ) ) ); ?></p>
		</div>
		<div class="mag-news__form">
			<?php
			$ot_news_sc = get_theme_mod( 'ot_news_shortcode', '' );
			if ( $ot_news_sc ) {
				echo do_shortcode( $ot_news_sc );
			} else {
				?>
				<form class="mag-news__fields" action="<?php echo esc_url( home_url( '/' ) ); ?>" method="get" onsubmit="return false">
					<label class="screen-reader-text" for="ot-news-email"><?php esc_html_e( 'Your email', 'overlaytop' ); ?></label>
					<input type="email" id="ot-news-email" placeholder="<?php esc_attr_e( 'you@example.com', 'overlaytop' ); ?>" autocomplete="email">
					<button class="btn" type="submit"><?php esc_html_e( 'Subscribe', 'overlaytop' ); ?></button>
				</form>
				<?php
			}
			?>
		</div>
	</div>
</section>

</div><?php /* .home--magazine */ ?>
