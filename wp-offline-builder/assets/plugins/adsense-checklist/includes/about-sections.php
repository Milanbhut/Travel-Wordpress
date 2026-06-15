<?php
/**
 * Maps registry keys to the source-checklist section they belong to.
 * The About tab uses this to group its coverage display.
 */
if (!defined('ABSPATH')) {
    exit;
}

return [
    // 1. Example violations table at top of source checklist (4)
    'about_page_suitability' => 'Example violations',
    'categories_volume'      => 'Example violations',
    'duplicate_images'       => 'Example violations',
    'author_box'             => 'Example violations',

    // 2. Domain & links (2)
    'domain_compat' => 'Domain & links',
    'broken_links'  => 'Domain & links',

    // 3. Required pages (5)
    'required_pages.privacy'    => 'Required pages',
    'required_pages.terms'      => 'Required pages',
    'required_pages.contact'    => 'Required pages',
    'required_pages.disclaimer' => 'Required pages',
    'required_pages.about'      => 'Required pages',

    // 4. Navigation (3)
    'nav_misleading'               => 'Navigation',
    'nav_clicks'                   => 'Navigation',
    'navigation.categories_balance' => 'Navigation',

    // 5. Content policies (11)
    'content.illegal'      => 'Content policies',
    'content.ip_abuse'     => 'Content policies',
    'content.derogatory'   => 'Content policies',
    'content.animal'       => 'Content policies',
    'content.misleading'   => 'Content policies',
    'content.unreliable'   => 'Content policies',
    'content.deceptive'    => 'Content policies',
    'content.dishonest'    => 'Content policies',
    'content.sex_explicit' => 'Content policies',
    'content.mail_brides'  => 'Content policies',
    'content.csae'         => 'Content policies',

    // 6. Restricted content (10)
    'restricted.sexual'       => 'Restricted content',
    'restricted.shocking'     => 'Restricted content',
    'restricted.explosives'   => 'Restricted content',
    'restricted.firearms'     => 'Restricted content',
    'restricted.tobacco'      => 'Restricted content',
    'restricted.rec_drugs'    => 'Restricted content',
    'restricted.alcohol'      => 'Restricted content',
    'restricted.gambling'     => 'Restricted content',
    'restricted.prescription' => 'Restricted content',
    'restricted.unapproved'   => 'Restricted content',

    // 7. Content quality (6)
    'quality.writing' => 'Content quality',
    'quality.ai'      => 'Content quality',
    'quality.length'  => 'Content quality',
    'quality.visuals' => 'Content quality',
    'quality.ux'      => 'Content quality',
    'quality.topics'  => 'Content quality',

    // 8. Account & technical (8)
    'https'                  => 'Account & technical',
    'ads_txt'                => 'Account & technical',
    'robots_txt'             => 'Account & technical',
    'mobile'                 => 'Account & technical',
    'technical.sitemap'      => 'Account & technical',
    'technical.analytics'    => 'Account & technical',
    'technical.cdn'          => 'Account & technical',
    'strategy.paid_traffic'  => 'Account & technical',

    // 9. E-E-A-T (9)
    'eeat.original'            => 'E-E-A-T',
    'eeat.volume'              => 'E-E-A-T',
    'eeat.depth'               => 'E-E-A-T',
    'eeat.trust_links'         => 'E-E-A-T',
    'eeat.maturity'            => 'E-E-A-T',
    'eeat.volume_aspirational' => 'E-E-A-T',
    'branding.logo'            => 'E-E-A-T',
    'eeat.cadence'             => 'E-E-A-T',
    'eeat.audience'            => 'E-E-A-T',

    // 10. Brand safety & privacy (3)
    'brand.safety'     => 'Brand safety & privacy',
    'brand.cmp'        => 'Brand safety & privacy',
    'brand.privacy'    => 'Brand safety & privacy',
];
