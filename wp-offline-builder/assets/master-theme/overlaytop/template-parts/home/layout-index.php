<?php
/**
 * Home layout: INDEX - a minimal, list-driven, whitespace-heavy front.
 * Minimal hero (no media) -> LATEST as a hairline-separated list -> 2-column
 * category index -> a quiet soft-tint inline newsletter. Hairlines only, no
 * shadows, maximal whitespace. Scoped under .home--index in css/home/index.css.
 *
 * @package Overlaytop
 */

?>
<div class="home home--index">

	<?php /* ---------- 1. MINIMAL HERO (no media) ---------- */ ?>
	<section class="idx-hero">
		<div class="container idx-hero__inner">
			<span class="eyebrow"><?php echo esc_html( get_theme_mod( 'ot_hero_eyebrow', __( 'Practical, honestly tested', 'overlaytop' ) ) ); ?></span>
			<h1 class="idx-hero__title"><?php echo esc_html( get_theme_mod( 'ot_hero_title', get_bloginfo( 'name' ) ) ); ?></h1>
			<p class="idx-hero__sub"><?php echo esc_html( get_theme_mod( 'ot_hero_sub', get_bloginfo( 'description' ) ) ); ?></p>
			<a class="idx-hero__cta" href="#idx-latest"><?php esc_html_e( 'Browse the latest', 'overlaytop' ); ?> <span aria-hidden="true">&rarr;</span></a>
		</div>
	</section>

	<?php /* ---------- 2. LATEST (hairline-separated list) ---------- */ ?>
	<?php
	$ot_latest = new WP_Query(
		array(
			'posts_per_page'      => 8,
			'ignore_sticky_posts' => true,
			'no_found_rows'       => true,
			'post_status'         => 'publish',
		)
	);
	if ( $ot_latest->have_posts() ) :
		?>
		<section class="idx-latest" id="idx-latest">
			<div class="container">
				<header class="idx-head">
					<span class="eyebrow"><?php esc_html_e( 'Fresh this week', 'overlaytop' ); ?></span>
					<h2 class="idx-head__title"><?php esc_html_e( 'Latest', 'overlaytop' ); ?></h2>
				</header>
				<ul class="idx-list">
					<?php
					while ( $ot_latest->have_posts() ) :
						$ot_latest->the_post();
						$ot_row_cats = get_the_category();
						?>
						<li class="idx-row">
							<a class="idx-row__link" href="<?php the_permalink(); ?>">
								<span class="idx-row__thumb">
									<?php
									if ( has_post_thumbnail() ) {
										the_post_thumbnail(
											'overlaytop_thumb',
											array(
												'loading' => 'lazy',
												'alt'     => the_title_attribute( array( 'echo' => false ) ),
											)
										);
									}
									?>
								</span>
								<span class="idx-row__body">
									<?php if ( ! empty( $ot_row_cats ) ) : ?>
										<span class="idx-row__kicker"><?php echo esc_html( $ot_row_cats[0]->name ); ?></span>
									<?php endif; ?>
									<span class="idx-row__title"><?php the_title(); ?></span>
									<span class="idx-row__meta">
										<span><?php echo esc_html( get_the_date() ); ?></span>
										<span aria-hidden="true">&middot;</span>
										<span><?php printf( esc_html__( '%d min read', 'overlaytop' ), (int) overlaytop_reading_time() ); ?></span>
									</span>
								</span>
							</a>
						</li>
						<?php
					endwhile;
					wp_reset_postdata();
					?>
				</ul>
			</div>
		</section>
	<?php endif; ?>

	<?php /* ---------- 3. CATEGORY INDEX (tidy 2-column list) ---------- */ ?>
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
				'number'     => 8,
				'hide_empty' => true,
			)
		);
	}
	if ( ! empty( $ot_cats ) ) :
		?>
		<section class="idx-cats">
			<div class="container">
				<header class="idx-head">
					<span class="eyebrow"><?php esc_html_e( 'Browse by topic', 'overlaytop' ); ?></span>
					<h2 class="idx-head__title"><?php esc_html_e( 'Categories', 'overlaytop' ); ?></h2>
				</header>
				<ul class="idx-catlist">
					<?php foreach ( $ot_cats as $ot_cat ) : ?>
						<li class="idx-catlist__item">
							<a class="idx-catlist__link" href="<?php echo esc_url( get_category_link( $ot_cat ) ); ?>">
								<span class="idx-catlist__name"><?php echo esc_html( $ot_cat->name ); ?></span>
								<span class="idx-catlist__count"><?php echo esc_html( number_format_i18n( (int) $ot_cat->count ) ); ?></span>
							</a>
						</li>
					<?php endforeach; ?>
				</ul>
			</div>
		</section>
	<?php endif; ?>

	<?php /* ---------- 4. QUIET NEWSLETTER (soft-tint inline card) ---------- */ ?>
	<section class="idx-news">
		<div class="container">
			<div class="idx-news__card">
				<div class="idx-news__copy">
					<h2 class="idx-news__title"><?php echo esc_html( get_theme_mod( 'ot_news_title', __( 'Get the weekly dispatch', 'overlaytop' ) ) ); ?></h2>
					<p class="idx-news__sub"><?php echo esc_html( get_theme_mod( 'ot_news_sub', __( 'One useful email a week, the best new guides and a few things worth your time. No spam, unsubscribe anytime.', 'overlaytop' ) ) ); ?></p>
				</div>
				<div class="idx-news__form">
					<?php
					$ot_news_sc = get_theme_mod( 'ot_news_shortcode', '' );
					if ( $ot_news_sc ) {
						echo do_shortcode( $ot_news_sc );
					} else {
						?>
						<form class="idx-news__fields" action="<?php echo esc_url( home_url( '/' ) ); ?>" method="get" onsubmit="return false">
							<label class="screen-reader-text" for="ot-news-email"><?php esc_html_e( 'Your email', 'overlaytop' ); ?></label>
							<input type="email" id="ot-news-email" placeholder="<?php esc_attr_e( 'you@example.com', 'overlaytop' ); ?>" autocomplete="email">
							<button class="btn" type="submit"><?php esc_html_e( 'Subscribe', 'overlaytop' ); ?></button>
						</form>
						<?php
					}
					?>
				</div>
			</div>
		</div>
	</section>

</div><?php /* .home--index */ ?>
