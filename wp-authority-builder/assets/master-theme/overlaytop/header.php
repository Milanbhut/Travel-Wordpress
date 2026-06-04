<?php
/**
 * Header template.
 *
 * @package Overlaytop
 */
?><!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<a class="skip-link" href="#main"><?php esc_html_e( 'Skip to content', 'overlaytop' ); ?></a>

<header class="site-header" id="site-header">
	<div class="container header__inner">
		<?php if ( has_custom_logo() ) : ?>
			<?php the_custom_logo(); ?>
		<?php else : ?>
			<a class="brand" href="<?php echo esc_url( home_url( '/' ) ); ?>" rel="home">
				<span class="brand__mark" aria-hidden="true">
					<svg viewBox="0 0 24 24" fill="none"><path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7Z" fill="currentColor" opacity=".28"/><circle cx="12" cy="9" r="2.6" fill="currentColor"/></svg>
				</span>
				<?php bloginfo( 'name' ); ?>
			</a>
		<?php endif; ?>

		<nav class="primary-nav" aria-label="<?php esc_attr_e( 'Primary', 'overlaytop' ); ?>">
			<?php
			wp_nav_menu(
				array(
					'theme_location' => 'primary',
					'container'      => false,
					'depth'          => 2,
					'fallback_cb'    => false,
				)
			);
			?>
		</nav>

		<div class="header__actions">
			<button class="icon-btn js-search-open" aria-label="<?php esc_attr_e( 'Open search', 'overlaytop' ); ?>">
				<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="11" cy="11" r="7"/><path d="m20 20-3-3"/></svg>
			</button>
			<button class="icon-btn nav-toggle js-drawer-open" aria-label="<?php esc_attr_e( 'Open menu', 'overlaytop' ); ?>" aria-expanded="false" aria-controls="mobile-drawer">
				<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M4 7h16M4 12h16M4 17h16"/></svg>
			</button>
		</div>
	</div>
</header>

<div class="drawer-backdrop js-drawer-close"></div>
<aside class="mobile-drawer" id="mobile-drawer" aria-label="<?php esc_attr_e( 'Mobile menu', 'overlaytop' ); ?>" aria-hidden="true">
	<div class="drawer__head">
		<a class="brand" href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php bloginfo( 'name' ); ?></a>
		<button class="icon-btn js-drawer-close" aria-label="<?php esc_attr_e( 'Close menu', 'overlaytop' ); ?>">
			<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M6 6l12 12M18 6 6 18"/></svg>
		</button>
	</div>
	<nav aria-label="<?php esc_attr_e( 'Mobile primary', 'overlaytop' ); ?>">
		<?php
		wp_nav_menu(
			array(
				'theme_location' => 'primary',
				'container'      => false,
				'depth'          => 1,
				'fallback_cb'    => false,
			)
		);
		?>
	</nav>
</aside>

<div class="search-overlay js-search-overlay" role="dialog" aria-modal="true" aria-label="<?php esc_attr_e( 'Search the site', 'overlaytop' ); ?>">
	<div class="search-overlay__box">
		<?php get_search_form(); ?>
	</div>
</div>

<main id="main" class="site-main">
