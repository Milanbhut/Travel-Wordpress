<?php
/**
 * Home layout: BENTO - the original recipe-driven 5-section homepage.
 * Hero -> About (text+image) -> Latest 6 (3x2) -> 3 bento category sections -> Newsletter.
 * Per-section variant classes (overlaytop_variant()) restyle each section; palette/fonts/radius
 * come from tokens.css. Styled by the homepage block already in main.css (no separate CSS file).
 *
 * @package Overlaytop
 */

$ot_v_hero   = overlaytop_variant( 'hero' );
$ot_v_about  = overlaytop_variant( 'about' );
$ot_v_latest = overlaytop_variant( 'latest' );
$ot_v_cat    = overlaytop_variant( 'category' );
$ot_v_news   = overlaytop_variant( 'newsletter' );

$ot_recent = new WP_Query(
	array(
		'posts_per_page'      => 9,
		'ignore_sticky_posts' => true,
		'no_found_rows'       => true,
		'post_status'         => 'publish',
	)
);
$ot_posts = $ot_recent->posts;
?>
<div class="home home--bento">

<?php /* ---------- 1. HERO ---------- */ ?>
<section class="hero hero--<?php echo esc_attr( $ot_v_hero ); ?>">
	<div class="container hero__inner">
		<div class="hero__lead">
			<span class="eyebrow"><?php echo esc_html( get_theme_mod( 'ot_hero_eyebrow', __( 'Practical, honestly tested', 'overlaytop' ) ) ); ?></span>
			<h1 class="hero__title"><?php echo esc_html( get_theme_mod( 'ot_hero_title', get_bloginfo( 'name' ) ) ); ?></h1>
			<p class="hero__sub"><?php echo esc_html( get_theme_mod( 'ot_hero_sub', get_bloginfo( 'description' ) ) ); ?></p>
			<div class="hero__cta">
				<a class="btn" href="#latest"><?php esc_html_e( 'Start reading', 'overlaytop' ); ?> <span aria-hidden="true">&rarr;</span></a>
				<a class="btn btn--ghost" href="<?php echo esc_url( home_url( '/about/' ) ); ?>"><?php esc_html_e( 'About us', 'overlaytop' ); ?></a>
			</div>
		</div>

		<?php if ( ! empty( $ot_posts ) ) : ?>
			<div class="hero__media">
				<a class="hero__feature" href="<?php echo esc_url( get_permalink( $ot_posts[0] ) ); ?>">
					<span class="hero__feature-media"><?php echo get_the_post_thumbnail( $ot_posts[0], 'overlaytop_hero', array( 'loading' => 'eager' ) ); ?></span>
					<span class="hero__feature-body">
						<?php $ot_fc = get_the_category( $ot_posts[0]->ID ); ?>
						<?php if ( ! empty( $ot_fc ) ) : ?><span class="pill"><?php echo esc_html( $ot_fc[0]->name ); ?></span><?php endif; ?>
						<span class="hero__feature-title"><?php echo esc_html( get_the_title( $ot_posts[0] ) ); ?></span>
					</span>
				</a>
				<div class="hero__minis">
					<?php foreach ( array_slice( $ot_posts, 1, 2 ) as $ot_m ) : ?>
						<a class="mini" href="<?php echo esc_url( get_permalink( $ot_m ) ); ?>">
							<span class="mini__media"><?php echo get_the_post_thumbnail( $ot_m, 'overlaytop_thumb' ); ?></span>
							<span class="mini__title"><?php echo esc_html( get_the_title( $ot_m ) ); ?></span>
						</a>
					<?php endforeach; ?>
				</div>
			</div>
		<?php endif; ?>
	</div>
</section>

<?php /* ---------- 2. ABOUT (text + image) ---------- */ ?>
<?php
$ot_about_img   = (int) get_theme_mod( 'ot_about_image', 0 );
$ot_about_title = get_theme_mod( 'ot_about_title', sprintf( /* translators: %s site name */ __( 'About %s', 'overlaytop' ), get_bloginfo( 'name' ) ) );
$ot_about_body  = get_theme_mod( 'ot_about_body', get_bloginfo( 'description' ) );
?>
<section class="about about--<?php echo esc_attr( $ot_v_about ); ?>">
	<div class="container about__inner">
		<div class="about__media">
			<?php
			if ( $ot_about_img ) {
				echo wp_get_attachment_image( $ot_about_img, 'overlaytop_card', false, array( 'class' => 'about__img' ) );
			} elseif ( ! empty( $ot_posts ) && has_post_thumbnail( $ot_posts[ min( 3, count( $ot_posts ) - 1 ) ] ) ) {
				echo get_the_post_thumbnail( $ot_posts[ min( 3, count( $ot_posts ) - 1 ) ], 'overlaytop_card', array( 'class' => 'about__img' ) );
			}
			?>
		</div>
		<div class="about__copy">
			<span class="eyebrow"><?php esc_html_e( 'About us', 'overlaytop' ); ?></span>
			<h2 class="about__title"><?php echo esc_html( $ot_about_title ); ?></h2>
			<?php foreach ( array_filter( array_map( 'trim', preg_split( '/\n+/', $ot_about_body ) ) ) as $ot_p ) : ?>
				<p><?php echo esc_html( $ot_p ); ?></p>
			<?php endforeach; ?>
			<?php
			$ot_stats = array_filter( array_map( 'trim', explode( '|', get_theme_mod( 'ot_about_stats', '' ) ) ) );
			if ( $ot_stats ) :
				?>
				<div class="about__stats">
					<?php
					foreach ( $ot_stats as $ot_s ) :
						$ot_sp = preg_split( '/\s+/', $ot_s, 2 );
						?>
						<div><b><?php echo esc_html( $ot_sp[0] ); ?></b><span><?php echo isset( $ot_sp[1] ) ? esc_html( $ot_sp[1] ) : ''; ?></span></div>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
			<a class="about__link" href="<?php echo esc_url( home_url( '/about/' ) ); ?>"><?php esc_html_e( 'Read our story', 'overlaytop' ); ?> &rarr;</a>
		</div>
	</div>
</section>

<?php /* ---------- 3. LATEST (max 6, 3x2) ---------- */ ?>
<?php
$ot_latest = new WP_Query(
	array(
		'posts_per_page'      => 6,
		'ignore_sticky_posts' => true,
		'no_found_rows'       => true,
		'post_status'         => 'publish',
	)
);
if ( $ot_latest->have_posts() ) :
	?>
	<section class="section latest latest--<?php echo esc_attr( $ot_v_latest ); ?>" id="latest">
		<div class="container">
			<header class="section__head">
				<div>
					<span class="eyebrow"><?php esc_html_e( 'Fresh this week', 'overlaytop' ); ?></span>
					<h2><?php esc_html_e( 'Latest articles', 'overlaytop' ); ?></h2>
				</div>
				<a class="section__more" href="<?php echo esc_url( get_permalink( get_option( 'page_for_posts' ) ) ? get_permalink( get_option( 'page_for_posts' ) ) : home_url( '/' ) ); ?>"><?php esc_html_e( 'View all', 'overlaytop' ); ?> &rarr;</a>
			</header>
			<div class="latest-grid">
				<?php
				while ( $ot_latest->have_posts() ) :
					$ot_latest->the_post();
					get_template_part( 'template-parts/card' );
				endwhile;
				wp_reset_postdata();
				?>
			</div>
		</div>
	</section>
<?php endif; ?>

<?php /* ---------- 4. THREE BENTO CATEGORY SECTIONS ---------- */ ?>
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
$ot_ci   = 0;
foreach ( $ot_cats as $ot_cat ) :
	$ot_ci++;
	$ot_cq = new WP_Query(
		array(
			'cat'                 => $ot_cat->term_id,
			'posts_per_page'      => 5,
			'ignore_sticky_posts' => true,
			'no_found_rows'       => true,
			'post_status'         => 'publish',
		)
	);
	if ( ! $ot_cq->have_posts() ) {
		continue;
	}
	$ot_cp = $ot_cq->posts;
	?>
	<section class="cat cat--<?php echo esc_attr( $ot_v_cat ); ?>">
		<div class="container">
			<header class="cat__head">
				<span class="cat__num"><?php echo esc_html( sprintf( '%02d', $ot_ci ) ); ?></span>
				<div>
					<h2 class="cat__title"><a href="<?php echo esc_url( get_category_link( $ot_cat ) ); ?>"><?php echo esc_html( $ot_cat->name ); ?></a></h2>
					<?php if ( $ot_cat->description ) : ?><p class="cat__desc"><?php echo esc_html( wp_trim_words( $ot_cat->description, 18 ) ); ?></p><?php endif; ?>
				</div>
				<a class="cat__more" href="<?php echo esc_url( get_category_link( $ot_cat ) ); ?>"><?php esc_html_e( 'All', 'overlaytop' ); ?> <?php echo esc_html( $ot_cat->name ); ?> &rarr;</a>
			</header>
			<div class="bento">
				<?php foreach ( $ot_cp as $ot_i => $ot_post ) : ?>
					<a class="bento__cell bento__cell--<?php echo esc_attr( 0 === $ot_i ? 'lead' : ( $ot_i <= 2 ? 'media' : 'text' ) ); ?>" href="<?php echo esc_url( get_permalink( $ot_post ) ); ?>">
						<?php if ( $ot_i <= 2 && has_post_thumbnail( $ot_post ) ) : ?>
							<span class="bento__media"><?php echo get_the_post_thumbnail( $ot_post, 0 === $ot_i ? 'overlaytop_card' : 'overlaytop_thumb' ); ?></span>
						<?php endif; ?>
						<span class="bento__body">
							<span class="pill"><?php echo esc_html( $ot_cat->name ); ?></span>
							<span class="bento__title"><?php echo esc_html( get_the_title( $ot_post ) ); ?></span>
							<span class="bento__meta"><?php echo esc_html( get_the_date( '', $ot_post ) ); ?></span>
						</span>
					</a>
				<?php endforeach; ?>
			</div>
		</div>
	</section>
	<?php
	wp_reset_postdata();
endforeach;
?>

<?php /* ---------- 5. NEWSLETTER ---------- */ ?>
<section class="news news--<?php echo esc_attr( $ot_v_news ); ?>">
	<div class="container news__inner">
		<div class="news__copy">
			<h2 class="news__title"><?php echo esc_html( get_theme_mod( 'ot_news_title', __( 'Get the weekly dispatch', 'overlaytop' ) ) ); ?></h2>
			<p class="news__sub"><?php echo esc_html( get_theme_mod( 'ot_news_sub', __( 'One useful email a week, the best new guides and a few things worth your time. No spam, unsubscribe anytime.', 'overlaytop' ) ) ); ?></p>
		</div>
		<div class="news__form">
			<?php
			$ot_news_sc = get_theme_mod( 'ot_news_shortcode', '' );
			if ( $ot_news_sc ) {
				echo do_shortcode( $ot_news_sc );
			} else {
				?>
				<form class="news__fields" action="<?php echo esc_url( home_url( '/' ) ); ?>" method="get" onsubmit="return false">
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

</div><?php /* .home--bento */ ?>
<?php wp_reset_postdata(); ?>
