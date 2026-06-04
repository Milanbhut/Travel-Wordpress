<?php
/**
 * Footer template.
 *
 * @package Overlaytop
 */
?>
</main>

<footer class="site-footer">
	<div class="container footer__top">
		<div class="footer__brand">
			<a class="brand" href="<?php echo esc_url( home_url( '/' ) ); ?>" rel="home">
				<span class="brand__mark" aria-hidden="true">
					<svg viewBox="0 0 24 24" fill="none"><path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7Z" fill="currentColor" opacity=".28"/><circle cx="12" cy="9" r="2.6" fill="currentColor"/></svg>
				</span>
				<?php bloginfo( 'name' ); ?>
			</a>
			<p><?php echo esc_html( get_bloginfo( 'description' ) ); ?></p>
		</div>

		<div class="footer__col">
			<h4><?php esc_html_e( 'Explore', 'overlaytop' ); ?></h4>
			<ul>
				<?php
				wp_list_categories(
					array(
						'title_li'   => '',
						'orderby'    => 'count',
						'order'      => 'DESC',
						'number'     => 6,
						'hide_empty' => false,
					)
				);
				?>
			</ul>
		</div>

		<div class="footer__col">
			<h4><?php esc_html_e( 'Pages', 'overlaytop' ); ?></h4>
			<?php
			if ( has_nav_menu( 'footer' ) ) {
				wp_nav_menu(
					array(
						'theme_location' => 'footer',
						'container'      => false,
						'depth'          => 1,
						'fallback_cb'    => false,
					)
				);
			} else {
				echo '<ul>';
				wp_list_pages( array( 'title_li' => '', 'depth' => 1, 'number' => 6 ) );
				echo '</ul>';
			}
			?>
		</div>

		<div class="footer__col">
			<h4><?php esc_html_e( 'About', 'overlaytop' ); ?></h4>
			<p style="color:#aab4af;font-size:.92rem;line-height:1.6">
				<?php esc_html_e( 'Honest, road-tested advice for seeing more of the world on a smaller budget.', 'overlaytop' ); ?>
			</p>
		</div>
	</div>

	<div class="container footer__bottom">
		<span>&copy; <?php echo esc_html( gmdate( 'Y' ) ); ?> <?php bloginfo( 'name' ); ?>. <?php esc_html_e( 'All rights reserved.', 'overlaytop' ); ?></span>
		<span><?php esc_html_e( 'Written and edited by real humans.', 'overlaytop' ); ?></span>
	</div>
</footer>

<?php wp_footer(); ?>
</body>
</html>
