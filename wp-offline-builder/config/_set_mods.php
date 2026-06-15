<?php
// GrowHaven homepage content mods for the recipe-driven front-page (no em dashes).
set_theme_mod( 'ot_hero_eyebrow', 'A home-garden journal' );
set_theme_mod( 'ot_hero_title', 'Grow a garden that feeds you, season by season.' );
set_theme_mod( 'ot_hero_sub', 'Practical, plant-tested guides for the patch you call home, from first seedlings to a full plot.' );
set_theme_mod( 'ot_hero_trust', '90 in-depth guides|6 garden topics|5 resident growers' );
set_theme_mod( 'ot_about_title', 'Real gardening, grown and tested at home' );
set_theme_mod( 'ot_about_body', "GrowHaven is a small team of gardeners who actually keep the plots we write about, from kitchen beds to a home orchard.\nEvery guide answers one real question, with the reasoning behind it, and a human editor checks each one before it goes live." );
set_theme_mod( 'ot_about_stats', '90 guides|6 topics|5 growers' );
set_theme_mod( 'ot_home_cats', 'vegetables,flowers,trees' );
set_theme_mod( 'ot_news_title', 'Seeds in your inbox' );
set_theme_mod( 'ot_news_sub', 'Seasonal jobs, planting reminders, and the guides worth your weekend. A few times a month, never spam.' );
echo "content mods set: hero/about/news/home_cats\n";
echo "active theme: " . get_option('stylesheet') . "\n";
foreach ( array('ot_v_hero','ot_v_about','ot_v_category','ot_v_newsletter','ot_v_footer','ot_fonts_href','ot_home_cats') as $k ) {
    echo "  $k = " . get_theme_mod($k, '(unset)') . "\n";
}
