<?php
/**
 * Front page - router for the rotating homepage layouts.
 *
 * The structure system is constant (hero + intro + latest + category browse +
 * newsletter, drawn from the same content), but each build picks one of several
 * distinct layout COMPOSITIONS via the `ot_home_layout` theme mod (set per site by
 * scripts.apply_theme). Each layout lives in template-parts/home/layout-<slug>.php
 * with its own scoped CSS in assets/css/home/<slug>.css (enqueued in functions.php).
 * Falls back to "bento" if the chosen layout is missing.
 *
 * @package Overlaytop
 */

get_header();

$ot_layout = sanitize_key( get_theme_mod( 'ot_home_layout', 'bento' ) );
if ( ! locate_template( "template-parts/home/layout-{$ot_layout}.php" ) ) {
	$ot_layout = 'bento';
}
get_template_part( 'template-parts/home/layout', $ot_layout );

get_footer();
