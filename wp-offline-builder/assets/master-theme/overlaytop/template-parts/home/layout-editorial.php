<?php
/**
 * Home layout: EDITORIAL - a magazine-style front page.
 *
 * Feature hero (lead copy + one large featured post card) -> browse-by-topic
 * grid of niche-neutral category cards -> latest-posts card grid -> numbered
 * editor's picks list -> short brand statement band (no stat claims) -> newsletter.
 *
 * Niche-neutral by design: category cards use a tinted numbered/letter badge,
 * not hardcoded per-slug icons, so the layout works for any subject.
 *
 * @package Overlaytop
 */

/* ---------- Shared recent-post pool (newest first) ---------- */
$ot_pool = new WP_Query(
	array(
		'posts_per_page'      => 12,
		'ignore_sticky_posts' => true,
		'no_found_rows'       => true,
		'post_status'         => 'publish',
	)
);
$ot_posts = $ot_pool->posts;
wp_reset_postdata();
?>
<div class="home home--editorial">

<?php /* ---------- 1. FEATURE HERO (lead + one large featured card) ---------- */ ?>
<section class="ed-hero">
	<div class="container ed-hero__inner">
		<div class="ed-hero__lead">
			<span class="eyebrow"><?php echo esc_html( get_theme_mod( 'ot_hero_eyebrow', __( 'The editorial', 'overlaytop' ) ) ); ?></span>
			<h1 class="ed-hero__title"><?php echo esc_html( get_theme_mod( 'ot_hero_title', get_bloginfo( 'name' ) ) ); ?></h1>
			<p class="ed-hero__sub"><?php echo esc_html( get_theme_mod( 'ot_hero_sub', get_bloginfo( 'description' ) ) ); ?></p>
			<div class="ed-hero__cta">
				<a class="btn" href="#ed-latest"><?php esc_html_e( 'Start reading', 'overlaytop' ); ?> <span aria-hidden="true">&rarr;</span></a>
				<a class="btn btn--ghost" href="<?php echo esc_url( home_url( '/about/' ) ); ?>"><?php esc_html_e( 'About us', 'overlaytop' ); ?></a>
			</div>
		</div>

		<?php
		if ( ! empty( $ot_posts ) ) :
			$ot_feat    = $ot_posts[0];
			$ot_feat_cs = get_the_category( $ot_feat->ID );
			?>
			<a class="ed-hero__featured" href="<?php echo esc_url( get_permalink( $ot_feat ) ); ?>">
				<span class="ed-hero__featured-media"><?php echo get_the_post_thumbnail( $ot_feat, 'overlaytop_hero', array( 'loading' => 'eager' ) ); ?></span>
				<span class="ed-hero__featured-body">
					<?php if ( ! empty( $ot_feat_cs ) ) : ?>
						<span class="cat-pill"><?php echo esc_html( $ot_feat_cs[0]->name ); ?></span>
					<?php endif; ?>
					<span class="ed-hero__featured-title"><?php echo esc_html( get_the_title( $ot_feat ) ); ?></span>
					<span class="ed-hero__featured-more"><?php esc_html_e( 'Read the feature', 'overlaytop' ); ?> &rarr;</span>
				</span>
			</a>
		<?php endif; ?>
	</div>
</section>

<?php
/* ---------- 2. BROWSE BY TOPIC (niche-neutral category cards) ---------- */
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
			'number'     => 6,
			'hide_empty' => true,
		)
	);
}
$ot_cats = array_slice( $ot_cats, 0, 6 );
if ( $ot_cats ) :
	?>
	<section class="ed-topics section--soft">
		<div class="container">
			<header class="ed-head ed-head--center">
				<span class="eyebrow"><?php esc_html_e( 'Browse by topic', 'overlaytop' ); ?></span>
				<h2 class="ed-head__title"><?php esc_html_e( 'Find your way in', 'overlaytop' ); ?></h2>
			</header>
			<div class="ed-topic-grid">
				<?php
				$ot_ti = 0;
				foreach ( $ot_cats as $ot_cat ) :
					$ot_ti++;
					$ot_badge = ( count( $ot_cats ) > 1 )
						? sprintf( '%02d', $ot_ti )
						: strtoupper( mb_substr( $ot_cat->name, 0, 1 ) );
					$ot_desc  = trim( wp_strip_all_tags( category_description( $ot_cat->term_id ) ) );
					?>
					<a class="ed-topic-card" href="<?php echo esc_url( get_category_link( $ot_cat->term_id ) ); ?>">
						<span class="ed-topic-card__badge" aria-hidden="true"><?php echo esc_html( $ot_badge ); ?></span>
						<span class="ed-topic-card__name"><?php echo esc_html( $ot_cat->name ); ?></span>
						<?php if ( $ot_desc ) : ?>
							<span class="ed-topic-card__desc"><?php echo esc_html( wp_trim_words( $ot_desc, 16, '...' ) ); ?></span>
						<?php endif; ?>
						<span class="ed-topic-card__count">
							<?php
							printf(
								esc_html( _n( '%s article', '%s articles', $ot_cat->count, 'overlaytop' ) ),
								esc_html( number_format_i18n( $ot_cat->count ) )
							);
							?>
							&rarr;
						</span>
					</a>
				<?php endforeach; ?>
			</div>
		</div>
	</section>
<?php endif; ?>

<?php
/* ---------- 3. LATEST (card grid) ---------- */
$ot_latest = new WP_Query(
	array(
		'posts_per_page'      => 6,
		'offset'              => 1,
		'ignore_sticky_posts' => true,
		'no_found_rows'       => true,
		'post_status'         => 'publish',
	)
);
if ( $ot_latest->have_posts() ) :
	?>
	<section class="ed-latest" id="ed-latest">
		<div class="container">
			<header class="ed-head">
				<div>
					<span class="eyebrow"><?php esc_html_e( 'Fresh this week', 'overlaytop' ); ?></span>
					<h2 class="ed-head__title"><?php esc_html_e( 'Latest articles', 'overlaytop' ); ?></h2>
				</div>
				<a class="ed-head__link" href="<?php echo esc_url( get_permalink( get_option( 'page_for_posts' ) ) ? get_permalink( get_option( 'page_for_posts' ) ) : home_url( '/' ) ); ?>"><?php esc_html_e( 'View all', 'overlaytop' ); ?> &rarr;</a>
			</header>
			<div class="card-grid">
				<?php
				while ( $ot_latest->have_posts() ) :
					$ot_latest->the_post();
					get_template_part( 'template-parts/card' );
				endwhile;
				?>
			</div>
		</div>
	</section>
	<?php
endif;
wp_reset_postdata();

/* ---------- 4. EDITOR'S PICKS (numbered list) ---------- */
$ot_picks = new WP_Query(
	array(
		'posts_per_page'      => 5,
		'offset'              => 7,
		'ignore_sticky_posts' => true,
		'no_found_rows'       => true,
		'post_status'         => 'publish',
	)
);
if ( $ot_picks->have_posts() ) :
	?>
	<section class="ed-picks section--soft">
		<div class="container ed-picks__wrap">
			<header class="ed-head">
				<div>
					<span class="eyebrow"><?php esc_html_e( "Editor's picks", 'overlaytop' ); ?></span>
					<h2 class="ed-head__title"><?php esc_html_e( 'Worth your time', 'overlaytop' ); ?></h2>
				</div>
			</header>
			<ol class="ed-pick-list">
				<?php
				$ot_pn = 0;
				while ( $ot_picks->have_posts() ) :
					$ot_picks->the_post();
					$ot_pn++;
					$ot_pick_cs = get_the_category();
					?>
					<li class="ed-pick">
						<span class="ed-pick__num" aria-hidden="true"><?php echo esc_html( number_format_i18n( $ot_pn ) ); ?></span>
						<a class="ed-pick__media" href="<?php the_permalink(); ?>" tabindex="-1" aria-hidden="true">
							<?php
							if ( has_post_thumbnail() ) {
								the_post_thumbnail( 'overlaytop_thumb', array( 'loading' => 'lazy', 'alt' => '' ) );
							}
							?>
						</a>
						<span class="ed-pick__body">
							<?php if ( ! empty( $ot_pick_cs ) ) : ?>
								<span class="ed-pick__cat"><?php echo esc_html( $ot_pick_cs[0]->name ); ?></span>
							<?php endif; ?>
							<a class="ed-pick__title" href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
							<span class="ed-pick__meta"><?php printf( esc_html__( '%d min read', 'overlaytop' ), (int) overlaytop_reading_time() ); ?></span>
						</span>
					</li>
				<?php endwhile; ?>
			</ol>
		</div>
	</section>
	<?php
endif;
wp_reset_postdata();

/* ---------- 5. BRAND STATEMENT BAND (optional, no stat claims) ---------- */
$ot_about_title = get_theme_mod( 'ot_about_title', sprintf( /* translators: %s site name */ __( 'About %s', 'overlaytop' ), get_bloginfo( 'name' ) ) );
$ot_about_body  = trim( (string) get_theme_mod( 'ot_about_body', get_bloginfo( 'description' ) ) );
$ot_about_paras = array_filter( array_map( 'trim', preg_split( '/\n+/', $ot_about_body ) ) );
if ( $ot_about_paras ) :
	?>
	<section class="ed-statement">
		<div class="container ed-statement__inner">
			<span class="eyebrow"><?php esc_html_e( 'Why we are here', 'overlaytop' ); ?></span>
			<h2 class="ed-statement__title"><?php echo esc_html( $ot_about_title ); ?></h2>
			<div class="ed-statement__body">
				<?php foreach ( $ot_about_paras as $ot_p ) : ?>
					<p><?php echo esc_html( $ot_p ); ?></p>
				<?php endforeach; ?>
			</div>
			<a class="btn btn--ghost" href="<?php echo esc_url( home_url( '/about/' ) ); ?>"><?php esc_html_e( 'Read our story', 'overlaytop' ); ?> <span aria-hidden="true">&rarr;</span></a>
		</div>
	</section>
<?php endif; ?>

<?php /* ---------- 6. NEWSLETTER ---------- */ ?>
<section class="ed-news">
	<div class="container ed-news__inner">
		<div class="ed-news__copy">
			<h2 class="ed-news__title"><?php echo esc_html( get_theme_mod( 'ot_news_title', __( 'Get the weekly dispatch', 'overlaytop' ) ) ); ?></h2>
			<p class="ed-news__sub"><?php echo esc_html( get_theme_mod( 'ot_news_sub', __( 'One useful email a week, the best new guides and a few things worth your time. No spam, unsubscribe anytime.', 'overlaytop' ) ) ); ?></p>
		</div>
		<div class="ed-news__form">
			<?php
			$ot_news_sc = get_theme_mod( 'ot_news_shortcode', '' );
			if ( $ot_news_sc ) {
				echo do_shortcode( $ot_news_sc );
			} else {
				?>
				<form class="ed-news__fields" action="<?php echo esc_url( home_url( '/' ) ); ?>" method="get" onsubmit="return false">
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

</div><?php /* .home--editorial */ ?>
