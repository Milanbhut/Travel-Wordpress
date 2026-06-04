<?php
/**
 * Front page — editorial hero + featured + latest guides.
 *
 * @package Overlaytop
 */

get_header();

$ot_hero = new WP_Query(
	array(
		'posts_per_page'      => 10,
		'ignore_sticky_posts' => true,
		'no_found_rows'       => true,
		'post_status'         => 'publish',
	)
);
?>
<section class="hero hero--editorial">
	<div class="hero__accents" aria-hidden="true">
		<svg class="hero__accent hero__accent--1" viewBox="0 0 160 120"><path d="M6 96 Q80 6 154 70" fill="none" stroke="currentColor" stroke-width="2.5" stroke-dasharray="2 11" stroke-linecap="round"/></svg>
		<svg class="hero__accent hero__accent--2" viewBox="0 0 64 64"><path d="M3 37 61 5 41 61l-9-21-29-3Z" fill="currentColor"/></svg>
	</div>
	<div class="container hero__inner">
		<div class="hero__lead">
			<span class="eyebrow"><?php esc_html_e( 'Budget travel, done right', 'overlaytop' ); ?></span>
			<h1 class="hero__title"><?php esc_html_e( 'See more of the world without spending more than you have to.', 'overlaytop' ); ?></h1>
			<p class="hero__sub"><?php echo esc_html( get_bloginfo( 'description' ) ); ?></p>
			<div class="hero__cta">
				<a class="btn" href="#latest"><?php esc_html_e( 'Start exploring', 'overlaytop' ); ?> <span class="btn__arrow" aria-hidden="true">&rarr;</span></a>
				<a class="btn btn--ghost" href="<?php echo esc_url( home_url( '/about/' ) ); ?>"><?php esc_html_e( 'About Overlaytop', 'overlaytop' ); ?></a>
			</div>
			<ul class="hero__trust">
				<li><span aria-hidden="true">&#9992;&#65039;</span> <?php esc_html_e( '90+ in-depth guides', 'overlaytop' ); ?></li>
				<li><span aria-hidden="true">&#129517;</span> <?php esc_html_e( '6 budget-travel topics', 'overlaytop' ); ?></li>
				<li><span aria-hidden="true">&#9997;&#65039;</span> <?php esc_html_e( 'Written by real travelers', 'overlaytop' ); ?></li>
			</ul>
		</div>

		<?php
		if ( $ot_hero->have_posts() ) :
			$ot_hero->the_post();
			$ot_hero_cats = get_the_category();
			?>
			<a class="hero__featured" href="<?php the_permalink(); ?>">
				<span class="hero__featured-media">
					<?php
					if ( has_post_thumbnail() ) {
						the_post_thumbnail( 'overlaytop_hero', array( 'loading' => 'eager' ) );
					}
					?>
				</span>
				<span class="hero__featured-body">
					<?php if ( ! empty( $ot_hero_cats ) ) : ?>
						<span class="cat-pill"><?php echo esc_html( $ot_hero_cats[0]->name ); ?></span>
					<?php endif; ?>
					<span class="hero__featured-title"><?php the_title(); ?></span>
					<span class="hero__featured-more"><?php esc_html_e( 'Read the guide', 'overlaytop' ); ?> &rarr;</span>
				</span>
			</a>
		<?php endif; ?>
	</div>
</section>

<?php if ( $ot_hero->have_posts() ) : ?>
	<section class="section" id="latest">
		<div class="container">
			<header class="section__head">
				<div>
					<span class="eyebrow"><?php esc_html_e( 'Fresh off the road', 'overlaytop' ); ?></span>
					<h2 style="margin-top:.4rem"><?php esc_html_e( 'Latest guides', 'overlaytop' ); ?></h2>
				</div>
			</header>
			<div class="card-grid">
				<?php
				while ( $ot_hero->have_posts() ) :
					$ot_hero->the_post();
					get_template_part( 'template-parts/card' );
				endwhile;
				?>
			</div>
		</div>
	</section>
<?php endif; ?>
<?php
wp_reset_postdata();
get_footer();
