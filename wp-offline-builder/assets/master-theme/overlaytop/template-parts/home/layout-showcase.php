<?php
/**
 * Home layout: SHOWCASE - a bold, image-forward front.
 * Visual HERO (latest post as a near full-width image with dark gradient overlay)
 * -> "Featured by topic" SHOWCASE (one large image card per category, its top post)
 * -> Latest STRIP (3-4 card row of recent posts) -> Newsletter.
 * Body markup only: front-page.php wraps this in get_header()/get_footer().
 * Styled by assets/css/home/showcase.css (scoped under .home--showcase).
 *
 * @package Overlaytop
 */

$ot_recent = new WP_Query(
	array(
		'posts_per_page'      => 5,
		'ignore_sticky_posts' => true,
		'no_found_rows'       => true,
		'post_status'         => 'publish',
	)
);
$ot_posts = $ot_recent->posts;
wp_reset_postdata();
?>
<div class="home home--showcase">

<?php /* ---------- 1. VISUAL HERO (latest post as image with overlay) ---------- */ ?>
<?php if ( ! empty( $ot_posts ) ) : ?>
	<?php
	$ot_lead   = $ot_posts[0];
	$ot_leadc  = get_the_category( $ot_lead->ID );
	$ot_eyebrow = get_theme_mod( 'ot_hero_eyebrow', __( 'Latest feature', 'overlaytop' ) );
	$ot_title   = get_theme_mod( 'ot_hero_title', get_bloginfo( 'name' ) );
	?>
	<section class="showcase-hero">
		<a class="showcase-hero__frame" href="<?php echo esc_url( get_permalink( $ot_lead ) ); ?>">
			<span class="showcase-hero__media">
				<?php
				if ( has_post_thumbnail( $ot_lead ) ) {
					echo get_the_post_thumbnail( $ot_lead, 'overlaytop_hero', array( 'loading' => 'eager' ) );
				}
				?>
			</span>
			<span class="showcase-hero__overlay">
				<span class="eyebrow"><?php echo esc_html( $ot_eyebrow ); ?></span>
				<?php if ( ! empty( $ot_leadc ) ) : ?>
					<span class="pill showcase-hero__pill"><?php echo esc_html( $ot_leadc[0]->name ); ?></span>
				<?php endif; ?>
				<h1 class="showcase-hero__title"><?php echo esc_html( get_the_title( $ot_lead ) ); ?></h1>
				<span class="showcase-hero__sub"><?php echo esc_html( get_theme_mod( 'ot_hero_sub', get_bloginfo( 'description' ) ) ); ?></span>
				<span class="btn showcase-hero__cta"><?php esc_html_e( 'Read the story', 'overlaytop' ); ?> <span aria-hidden="true">&rarr;</span></span>
			</span>
		</a>
	</section>
<?php endif; ?>

<?php /* ---------- 2. FEATURED BY TOPIC (one big image card per category) ---------- */ ?>
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
$ot_cats  = array_slice( $ot_cats, 0, 3 );
$ot_cards = array();
foreach ( $ot_cats as $ot_cat ) {
	$ot_cq = new WP_Query(
		array(
			'cat'                 => $ot_cat->term_id,
			'posts_per_page'      => 1,
			'ignore_sticky_posts' => true,
			'no_found_rows'       => true,
			'post_status'         => 'publish',
		)
	);
	if ( $ot_cq->have_posts() ) {
		$ot_cards[] = array(
			'cat'  => $ot_cat,
			'post' => $ot_cq->posts[0],
		);
	}
	wp_reset_postdata();
}
if ( ! empty( $ot_cards ) ) :
	?>
	<section class="section showcase-topics">
		<div class="container">
			<header class="showcase-topics__head">
				<span class="eyebrow"><?php esc_html_e( 'Featured by topic', 'overlaytop' ); ?></span>
				<h2 class="showcase-topics__heading"><?php esc_html_e( 'Where to start', 'overlaytop' ); ?></h2>
			</header>
			<div class="showcase-topics__grid">
				<?php foreach ( $ot_cards as $ot_card ) : ?>
					<a class="showcase-topic" href="<?php echo esc_url( get_permalink( $ot_card['post'] ) ); ?>">
						<span class="showcase-topic__media">
							<?php
							if ( has_post_thumbnail( $ot_card['post'] ) ) {
								echo get_the_post_thumbnail( $ot_card['post'], 'overlaytop_card', array( 'loading' => 'lazy' ) );
							}
							?>
						</span>
						<span class="showcase-topic__body">
							<span class="cat-pill showcase-topic__pill"><?php echo esc_html( $ot_card['cat']->name ); ?></span>
							<span class="showcase-topic__title"><?php echo esc_html( get_the_title( $ot_card['post'] ) ); ?></span>
						</span>
					</a>
				<?php endforeach; ?>
			</div>
		</div>
	</section>
<?php endif; ?>

<?php /* ---------- 3. LATEST STRIP (row of recent cards) ---------- */ ?>
<?php
$ot_strip = new WP_Query(
	array(
		'posts_per_page'      => 4,
		'ignore_sticky_posts' => true,
		'no_found_rows'       => true,
		'post_status'         => 'publish',
	)
);
if ( $ot_strip->have_posts() ) :
	?>
	<section class="section showcase-latest" id="latest">
		<div class="container">
			<header class="showcase-latest__head">
				<div>
					<span class="eyebrow"><?php esc_html_e( 'Fresh this week', 'overlaytop' ); ?></span>
					<h2 class="showcase-latest__heading"><?php esc_html_e( 'Latest articles', 'overlaytop' ); ?></h2>
				</div>
				<a class="showcase-latest__more" href="<?php echo esc_url( get_permalink( get_option( 'page_for_posts' ) ) ? get_permalink( get_option( 'page_for_posts' ) ) : home_url( '/' ) ); ?>"><?php esc_html_e( 'View all', 'overlaytop' ); ?> <span aria-hidden="true">&rarr;</span></a>
			</header>
			<div class="showcase-latest__strip">
				<?php
				while ( $ot_strip->have_posts() ) :
					$ot_strip->the_post();
					get_template_part( 'template-parts/card' );
				endwhile;
				wp_reset_postdata();
				?>
			</div>
		</div>
	</section>
<?php endif; ?>

<?php /* ---------- 4. NEWSLETTER ---------- */ ?>
<section class="showcase-news">
	<div class="container showcase-news__inner">
		<div class="showcase-news__copy">
			<h2 class="showcase-news__title"><?php echo esc_html( get_theme_mod( 'ot_news_title', __( 'Get the weekly dispatch', 'overlaytop' ) ) ); ?></h2>
			<p class="showcase-news__sub"><?php echo esc_html( get_theme_mod( 'ot_news_sub', __( 'One useful email a week, the best new guides and a few things worth your time. No spam, unsubscribe anytime.', 'overlaytop' ) ) ); ?></p>
		</div>
		<div class="showcase-news__form">
			<?php
			$ot_news_sc = get_theme_mod( 'ot_news_shortcode', '' );
			if ( $ot_news_sc ) {
				echo do_shortcode( $ot_news_sc );
			} else {
				?>
				<form class="showcase-news__fields" action="<?php echo esc_url( home_url( '/' ) ); ?>" method="get" onsubmit="return false">
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

</div><?php /* .home--showcase */ ?>
