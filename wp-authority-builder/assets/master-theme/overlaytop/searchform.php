<?php
/**
 * Search form (used by the header search overlay).
 *
 * @package Overlaytop
 */
?>
<form role="search" method="get" class="search-form" action="<?php echo esc_url( home_url( '/' ) ); ?>">
	<label class="screen-reader-text" for="ot-search-field"><?php esc_html_e( 'Search for:', 'overlaytop' ); ?></label>
	<input type="search" id="ot-search-field" class="search-field" name="s" value="<?php echo esc_attr( get_search_query() ); ?>" placeholder="<?php esc_attr_e( 'Search guides…', 'overlaytop' ); ?>" autocomplete="off">
	<button type="submit" class="search-submit"><?php esc_html_e( 'Search', 'overlaytop' ); ?></button>
</form>
