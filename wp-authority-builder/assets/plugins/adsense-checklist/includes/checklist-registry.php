<?php
if (!defined('ABSPATH')) {
    exit;
}

use AdSenseChecklist\Checks\About_Page_Suitability_Check;
use AdSenseChecklist\Checks\Ads_Txt_Check;
use AdSenseChecklist\Checks\Analytics_Check;
use AdSenseChecklist\Checks\Article_Count_Check;
use AdSenseChecklist\Checks\Author_Box_Check;
use AdSenseChecklist\Checks\Broken_Links_Check;
use AdSenseChecklist\Checks\Categories_Balance_Check;
use AdSenseChecklist\Checks\Categories_Volume_Check;
use AdSenseChecklist\Checks\CDN_Check;
use AdSenseChecklist\Checks\Cmp_Detect_Check;
use AdSenseChecklist\Checks\Domain_Maturity_Check;
use AdSenseChecklist\Checks\Duplicate_Images_Check;
use AdSenseChecklist\Checks\Encouraging_Clicks_Check;
use AdSenseChecklist\Checks\Https_Check;
use AdSenseChecklist\Checks\Logo_Check;
use AdSenseChecklist\Checks\Manual_Review_Check;
use AdSenseChecklist\Checks\Misleading_Nav_Check;
use AdSenseChecklist\Checks\Mobile_Responsive_Check;
use AdSenseChecklist\Checks\Post_Visuals_Check;
use AdSenseChecklist\Checks\Privacy_Disclosure_Check;
use AdSenseChecklist\Checks\Publication_Cadence_Check;
use AdSenseChecklist\Checks\Readability_Check;
use AdSenseChecklist\Checks\Required_Pages_Check;
use AdSenseChecklist\Checks\Restricted_Keywords_Check;
use AdSenseChecklist\Checks\Robots_Txt_Check;
use AdSenseChecklist\Checks\Sitemap_Check;
use AdSenseChecklist\Checks\Trust_Pages_Link_Check;
use AdSenseChecklist\Checks\UX_CSS_Check;
use AdSenseChecklist\Checks\Word_Count_Check;

return [
    // === Example violations from the top-of-page table (4) ===
    ['key' => 'about_page_suitability', 'situation' => '"About Us" Page Suitability',         'severity' => 'severe',   'check_class' => About_Page_Suitability_Check::class,
        'fix' => 'Edit the About page (Pages → All Pages → About). Add at least 300 words covering who you are, what the site covers, and your credentials. Google\'s quality bar rejects thin About pages.'],
    ['key' => 'categories_volume',      'situation' => 'Categories with Low Content Volume',  'severity' => 'severe',   'check_class' => Categories_Volume_Check::class,
        'fix' => 'Open Posts → Categories. For each flagged category, either publish 3+ posts under it or merge it into a related category with more content.'],
    ['key' => 'duplicate_images',       'situation' => 'Duplicate Images',                    'severity' => 'moderate', 'check_class' => Duplicate_Images_Check::class,
        'fix' => 'Open Media → Library. For each duplicate group in the evidence, keep one image, delete the others, and update any posts that referenced the deleted ones.'],
    ['key' => 'author_box',             'situation' => 'Include the author box',              'severity' => 'minor',    'check_class' => Author_Box_Check::class,
        'fix' => 'Switch to a theme that renders an author box on posts (Astra, GeneratePress, Newspaper) or install a plugin like \'Simple Author Box\'. AdSense expects clear authorship signals.'],

    // === Compliance Checklist (33) ===
    ['key' => 'domain_compat', 'situation' => 'Domain compatibility with published content', 'severity' => 'severe', 'check_class' => Manual_Review_Check::class, 'hint' => 'Confirm site title, tagline, and dominant categories match what the domain name promises.',
        'fix' => 'Open your homepage and dominant categories. If the domain name promises one topic but the content covers something unrelated (e.g. carblog.com running fitness posts), realign one or the other.'],
    ['key' => 'broken_links',  'situation' => 'Search for broken links',                     'severity' => 'severe', 'check_class' => Broken_Links_Check::class,
        'fix' => 'Click the broken URL in the evidence below to confirm it\'s still dead, then edit the source post and either update the link or remove it.'],

    ['key' => 'required_pages.privacy',    'situation' => 'Required page: Privacy Policy',  'severity' => 'urgent', 'check_class' => Required_Pages_Check::class,
        'check_args' => ['aliases' => ['Privacy Policy','privacy-policy','privacy','privacy-notice','privacy notice'], 'keywords' => ['privacy']],
        'fix' => 'Create a \'Privacy Policy\' page. Pages → Add New. Paste a policy template that mentions third-party advertising, Google ads, cookies, and opt-out instructions. Publish.'],
    ['key' => 'required_pages.terms',      'situation' => 'Required page: Terms of Use',    'severity' => 'urgent', 'check_class' => Required_Pages_Check::class,
        'check_args' => ['aliases' => ['Terms of Use','Terms of Service','terms','terms-of-service','terms-of-use','terms-and-conditions','terms-conditions','t-and-c','tos','legal'], 'keywords' => ['terms','tos','conditions']],
        'fix' => 'Create a \'Terms of Service\' or \'Terms & Conditions\' page (Pages → Add New) and publish it.'],
    ['key' => 'required_pages.contact',    'situation' => 'Required page: Contact',         'severity' => 'urgent', 'check_class' => Required_Pages_Check::class,
        'check_args' => ['aliases' => ['Contact','Contact Us','contact-us','contact','reach-us','get-in-touch','reach us','get in touch'], 'keywords' => ['contact']],
        'fix' => 'Create a \'Contact\' page (Pages → Add New). Add a contact form (WPForms / Contact Form 7) or just your email. Publish.'],
    ['key' => 'required_pages.disclaimer', 'situation' => 'Required page: Disclaimer',      'severity' => 'urgent', 'check_class' => Required_Pages_Check::class,
        'check_args' => ['aliases' => ['Disclaimer','disclaimer','legal-disclaimer','disclosures','affiliate-disclosure'], 'keywords' => ['disclaimer','disclosure']],
        'fix' => 'Create a \'Disclaimer\' page covering affiliate links, opinions, and any \'not professional advice\' language relevant to your niche.'],
    ['key' => 'required_pages.about',      'situation' => 'Required page: About',           'severity' => 'urgent', 'check_class' => Required_Pages_Check::class,
        'check_args' => ['aliases' => ['About','About Us','about-us','about','who-we-are','about-me','who we are','about me'], 'keywords' => ['about']],
        'fix' => 'Create an \'About\' (or \'About Us\') page explaining who you are, what the site covers, and your credentials. Aim for 300+ words.'],

    ['key' => 'nav_misleading', 'situation' => 'Navigation — misleading on the site',             'severity' => 'severe', 'check_class' => Misleading_Nav_Check::class,
        'fix' => 'Auto-checked patterns: ads inside &lt;nav&gt; blocks, fake search inputs that submit to an external host, and "Download" links pointing to unrelated hosts. The finding\'s evidence names which pattern triggered. Remove or rewire the offending element.'],
    ['key' => 'nav_clicks',     'situation' => 'Navigation — encouraging clicks or views',         'severity' => 'urgent', 'check_class' => Encouraging_Clicks_Check::class,
        'fix' => 'Auto-checked patterns: arrow glyphs (→, ➜, ►, etc.) within 200 chars of an AdSense block, or click-encouraging text like "click here" / "recommended sites" / "sponsored offer" near an ad block. The finding\'s evidence names which pattern triggered. Remove the surrounding markup or reposition the ad.'],

    ['key' => 'content.illegal',      'situation' => 'Content Policy — Illegal content',                'severity' => 'urgent', 'check_class' => Restricted_Keywords_Check::class, 'check_args' => ['keywords' => ['buy drugs online', 'buy heroin', 'buy meth', 'buy cocaine', 'fake id maker', 'fake passport for sale', 'fake driving license', 'tax evasion guide', 'evade taxes', 'money laundering guide', 'fake currency', 'counterfeit money', 'fake degree', 'fake diploma'], 'opt_in_setting' => 'enable_illegal_scan'],
        'fix' => 'Review all posts for content that promotes illegal activities or infringes third-party rights. Delete or rewrite any offending post before re-applying.'],
    ['key' => 'content.ip_abuse',     'situation' => 'Content Policy — Intellectual property abuse',   'severity' => 'urgent', 'check_class' => Restricted_Keywords_Check::class, 'check_args' => ['keywords' => ['warez', 'keygen', 'crack download', 'cracked software', 'pirated software', 'pirated movie', 'pirated game', 'torrent download site', 'free torrent download', 'free download crack', 'serial number download', 'license key generator', 'activation crack', 'patched apk', 'modded apk free', 'paid app free']],
        'fix' => 'Remove any copyrighted text, images, or downloads used without permission. Delete posts promoting counterfeit or pirated goods.'],
    ['key' => 'content.derogatory',   'situation' => 'Content Policy — Dangerous or derogatory content','severity' => 'urgent', 'check_class' => Restricted_Keywords_Check::class, 'check_args' => ['keywords' => ['slur1','slur2'], 'opt_in_setting' => 'enable_slur_scan'],
        'fix' => 'Open each flagged post and remove or replace derogatory language. Edit posts using Posts → All Posts, search for the keyword shown in evidence.'],
    ['key' => 'content.animal',       'situation' => 'Content Policy — Animal cruelty',                'severity' => 'urgent', 'check_class' => Restricted_Keywords_Check::class, 'check_args' => ['keywords' => ['dog fighting', 'cock fighting', 'cockfighting ring', 'bear baiting', 'trophy hunting endangered', 'ivory trade', 'rhino horn for sale', 'shark finning', 'puppy mill', 'fur farm', 'foie gras force feeding'], 'opt_in_setting' => 'enable_animal_scan'],
        'fix' => 'Review all posts for content promoting animal cruelty or trade in endangered species. Delete any offending post or section immediately.'],
    ['key' => 'content.misleading',   'situation' => 'Content Policy — Misleading content',            'severity' => 'urgent', 'check_class' => Restricted_Keywords_Check::class, 'check_args' => ['keywords' => ['call microsoft support number', 'microsoft tech support number', 'call apple support number', 'google tech support phone', 'authorized reseller', 'verified by google', 'as seen on cnn', 'as seen on bbc', 'celebrity endorsed']],
        'fix' => 'Review posts for fake credentials, misuse of brand logos, or misleading claims about content authorship. Remove or correct anything that misrepresents the publisher.'],
    ['key' => 'content.unreliable',   'situation' => 'Content Policy — Unreliable and harmful claims', 'severity' => 'urgent', 'check_class' => Restricted_Keywords_Check::class, 'check_args' => ['keywords' => ['vaccines cause autism', 'anti-vax', 'vaccine injury hoax', 'stolen election', 'rigged election', 'election fraud proof', 'covid hoax', 'covid vaccine kills', 'plandemic', 'flat earth proof', '5g causes cancer', 'chemtrails proof', 'great reset agenda', 'qanon truth'], 'opt_in_setting' => 'enable_unreliable_scan'],
        'fix' => 'Review posts for anti-vaccine content, medical misinformation, or election conspiracy claims. Edit to add factual sourcing or delete posts that cannot be corrected.'],
    ['key' => 'content.deceptive',    'situation' => 'Content Policy — Deceptive practices',           'severity' => 'urgent', 'check_class' => Manual_Review_Check::class, 'hint' => 'Confirm no posts trick users with false pretenses or phish for personal information.',
        'fix' => 'Remove any forms or pages that collect user data under false pretenses. Delete posts that mimic official sites or trick users into sharing personal information.'],
    ['key' => 'content.dishonest',    'situation' => 'Content Policy — Enabling dishonest behavior',   'severity' => 'urgent', 'check_class' => Restricted_Keywords_Check::class, 'check_args' => ['keywords' => ['hacking tutorial', 'how to hack', 'phishing kit', 'phishing template', 'ddos tutorial', 'ddos for hire', 'sql injection guide', 'sql injection tutorial', 'password cracking tool', 'password cracking software', 'wifi cracking', 'router hack', 'instagram hack', 'facebook hack', 'how to bypass paywall', 'bypass paywall guide', 'jailbreak guide']],
        'fix' => 'Remove posts that provide hacking tutorials, phishing templates, or instructions for unauthorized system access. Edit posts to remove such sections.'],
    ['key' => 'content.sex_explicit', 'situation' => 'Content Policy — Sexually explicit content',      'severity' => 'urgent', 'check_class' => Restricted_Keywords_Check::class, 'check_args' => ['keywords' => ['porn','xxx','nsfw','nude','erotic']],
        'fix' => 'Open the flagged post (Posts → All Posts). Remove or rewrite the explicit section. If the entire post is adult content, delete it.'],
    ['key' => 'content.mail_brides',  'situation' => 'Content Policy — Mail-order brides',             'severity' => 'urgent', 'check_class' => Restricted_Keywords_Check::class, 'check_args' => ['keywords' => ['mail order bride', 'mail-order bride', 'russian bride agency', 'asian wife catalog', 'ukrainian bride agency', 'foreign bride for sale', 'thai wife for marriage', 'marriage broker russian', 'find a foreign wife', 'matchmaking for marriage visa'], 'opt_in_setting' => 'enable_mail_brides_scan'],
        'fix' => 'Review posts for content advertising or facilitating international marriage services for immigration purposes. Delete any offending posts.'],
    ['key' => 'content.csae',         'situation' => 'Content Policy — Child sexual abuse and exploitation','severity' => 'urgent', 'check_class' => Restricted_Keywords_Check::class, 'check_args' => ['keywords' => ['child porn', 'cp video', 'underage girl video', 'minor explicit', 'teen escort', 'lolita video', 'preteen explicit']],
        'fix' => 'Zero-tolerance review. Confirm no posts or images sexualize minors. If found, delete immediately and consider professional moderation tools.'],

    ['key' => 'restricted.sexual',      'situation' => 'Restricted: Sexual content',                       'severity' => 'severe', 'check_class' => Restricted_Keywords_Check::class, 'check_args' => ['keywords' => ['porn','nude','escort','sex toy']],
        'fix' => 'Open the linked post. If the keyword context is benign (e.g. a news article), mark this finding resolved. If the post promotes or sells adult content, remove or rewrite that section.'],
    ['key' => 'restricted.shocking',    'situation' => 'Restricted: Shocking content',                     'severity' => 'severe', 'check_class' => Restricted_Keywords_Check::class, 'check_args' => ['keywords' => ['graphic gore', 'shock footage', 'real death video', 'execution video', 'liveleak gore', 'beheading video', 'graphic injury photos', 'autopsy photos viral', 'shock site content'], 'opt_in_setting' => 'enable_shocking_scan'],
        'fix' => 'Open the linked post. If the keyword context is benign, mark this finding resolved. If the post promotes graphic gore or shock imagery, remove that content.'],
    ['key' => 'restricted.explosives',  'situation' => 'Restricted: Explosives',                           'severity' => 'severe', 'check_class' => Restricted_Keywords_Check::class, 'check_args' => ['keywords' => ['explosive','tnt','dynamite','grenade','c4 charge']],
        'fix' => 'Open the linked post. If the keyword context is benign (e.g. a news article mentioning the topic), mark this finding resolved. If the post promotes, sells, or instructs how to obtain explosives, remove or rewrite that section.'],
    ['key' => 'restricted.firearms',    'situation' => 'Restricted: Firearms / parts',                     'severity' => 'severe', 'check_class' => Restricted_Keywords_Check::class, 'check_args' => ['keywords' => ['handgun','rifle','shotgun','silencer','magazine clip','ar-15']],
        'fix' => 'Open the linked post. If the keyword context is benign (e.g. a news article mentioning the topic), mark this finding resolved. If the post promotes, sells, or instructs how to obtain the restricted item, remove or rewrite that section.'],
    ['key' => 'restricted.tobacco',     'situation' => 'Restricted: Tobacco',                              'severity' => 'severe', 'check_class' => Restricted_Keywords_Check::class, 'check_args' => ['keywords' => ['tobacco','cigarette','cigar','vape','e-cig']],
        'fix' => 'Open the linked post. If the keyword context is benign (e.g. a news article mentioning the topic), mark this finding resolved. If the post promotes, sells, or instructs how to obtain the restricted item, remove or rewrite that section.'],
    ['key' => 'restricted.rec_drugs',   'situation' => 'Restricted: Recreational drugs',                   'severity' => 'severe', 'check_class' => Restricted_Keywords_Check::class, 'check_args' => ['keywords' => ['marijuana','cocaine','heroin','meth','lsd','mdma']],
        'fix' => 'Open the linked post. If the keyword context is benign (e.g. a news article mentioning the topic), mark this finding resolved. If the post promotes, sells, or instructs how to obtain the restricted item, remove or rewrite that section.'],
    ['key' => 'restricted.alcohol',     'situation' => 'Restricted: Sale or misuse of alcohol',            'severity' => 'severe', 'check_class' => Restricted_Keywords_Check::class, 'check_args' => ['keywords' => ['vodka','whiskey','beer sale','liquor store']],
        'fix' => 'Open the linked post. If the keyword context is benign (e.g. a news article mentioning the topic), mark this finding resolved. If the post promotes, sells, or instructs how to obtain the restricted item, remove or rewrite that section.'],
    ['key' => 'restricted.gambling',    'situation' => 'Restricted: Online gambling',                      'severity' => 'severe', 'check_class' => Restricted_Keywords_Check::class, 'check_args' => ['keywords' => ['casino','blackjack','poker','sportsbook','bet365']],
        'fix' => 'Open the linked post. If the keyword context is benign (e.g. a news article mentioning the topic), mark this finding resolved. If the post promotes, sells, or instructs how to obtain the restricted item, remove or rewrite that section.'],
    ['key' => 'restricted.prescription','situation' => 'Restricted: Prescription drugs',                    'severity' => 'severe', 'check_class' => Restricted_Keywords_Check::class, 'check_args' => ['keywords' => ['oxycodone','xanax','adderall','viagra','cialis']],
        'fix' => 'Open the linked post. If the keyword context is benign (e.g. a news article mentioning the topic), mark this finding resolved. If the post promotes, sells, or instructs how to obtain the restricted item, remove or rewrite that section.'],
    ['key' => 'restricted.unapproved',  'situation' => 'Restricted: Unapproved pharmaceuticals/supplements','severity' => 'severe', 'check_class' => Restricted_Keywords_Check::class, 'check_args' => ['keywords' => ['miracle cure','weight loss pill','testosterone booster']],
        'fix' => 'Open the linked post. If the keyword context is benign (e.g. a news article mentioning the topic), mark this finding resolved. If the post promotes, sells, or instructs how to obtain the restricted item, remove or rewrite that section.'],

    ['key' => 'quality.writing', 'situation' => 'Content Quality — writing quality',          'severity' => 'moderate', 'check_class' => Readability_Check::class,
        'fix' => 'Pick 5 random posts and read them for grammar, structure, and originality. Edit anything that reads as rushed or copy-pasted.'],
    ['key' => 'quality.ai',      'situation' => 'Content Quality — AI caution',               'severity' => 'severe',   'check_class' => Manual_Review_Check::class, 'hint' => 'If AI-assisted, confirm a human editor reviewed every article before publication.',
        'fix' => 'If posts were AI-generated, have a human editor read each one before publication. Google penalizes unedited AI text.'],
    ['key' => 'quality.length',  'situation' => 'Content Quality — articles below word count threshold','severity' => 'moderate', 'check_class' => Word_Count_Check::class, 'check_args' => ['min_words' => 1000],
        'fix' => 'Open the linked post and expand it past the word threshold (see Settings → Thresholds). Add clear H2 subheadings to break up sections.'],

    // === Technical / E-E-A-T / Brand safety (13) ===
    ['key' => 'https',            'situation' => 'Enforce HTTPS sitewide',                          'severity' => 'urgent',   'check_class' => Https_Check::class,
        'fix' => 'Activate a free SSL certificate from your hosting control panel (most hosts offer free Let\'s Encrypt with one click). Then in Settings → General change both \'WordPress Address\' and \'Site Address\' from http:// to https://.'],
    ['key' => 'ads_txt',          'situation' => 'ads.txt configured natively',                     'severity' => 'severe',   'check_class' => Ads_Txt_Check::class,
        'fix' => 'Create a file named `ads.txt` in your site\'s web root (same folder as wp-config.php). Add one line: `google.com, pub-XXXXXXXXXXXXXXXX, DIRECT, f08c47fec0942fa0` — replace pub-XX with your AdSense Publisher ID.'],
    ['key' => 'robots_txt',       'situation' => 'robots.txt allows Mediapartners-Google',          'severity' => 'severe',   'check_class' => Robots_Txt_Check::class,
        'fix' => 'Edit `robots.txt` in your site root. Either remove the line blocking `/`, or add a Mediapartners-Google group with `Allow: /` to override the wildcard block.'],
    ['key' => 'mobile',           'situation' => 'Mobile responsiveness',                           'severity' => 'severe',   'check_class' => Mobile_Responsive_Check::class,
        'fix' => 'Switch to a modern responsive theme (Astra, GeneratePress, Twenty Twenty-Four). The current theme\'s homepage HTML is missing the viewport meta tag, which Google\'s mobile-first index penalizes.'],

    ['key' => 'eeat.original',    'situation' => 'Publish original content (no unedited AI)',       'severity' => 'urgent',   'check_class' => Manual_Review_Check::class, 'hint' => 'Confirm no scraped, translated, or unedited-AI text. If AI-assisted, a human editor must review every article.',
        'fix' => 'Make sure every published post is original. Don\'t publish scraped content, machine translations, or unedited AI text. If you use AI, a human must edit each article.'],
    ['key' => 'eeat.volume',      'situation' => 'Volume threshold: 20+ indexed articles',          'severity' => 'severe',   'check_class' => Article_Count_Check::class,
        'fix' => 'Publish more articles. AdSense generally needs 20+ indexed posts before approving a new site. Stagger publication over several weeks.'],
    ['key' => 'eeat.depth',       'situation' => 'Semantic depth (word count + H2 headings)',      'severity' => 'moderate', 'check_class' => Word_Count_Check::class, 'check_args' => ['min_words' => 1000, 'min_headings' => 1],
        'fix' => 'Open the linked post. Expand past the word threshold (see Settings → Thresholds) and add at least one H2 subheading to give it structure.'],
    ['key' => 'eeat.trust_links', 'situation' => 'Trust pages linked from menus',                   'severity' => 'severe',   'check_class' => Trust_Pages_Link_Check::class,
        'fix' => 'In Appearance → Menus, add your About, Contact, Privacy Policy, and Terms pages to the primary or footer menu.'],
    ['key' => 'eeat.maturity',    'situation' => 'Domain maturation period',                        'severity' => 'moderate', 'check_class' => Domain_Maturity_Check::class,
        'fix' => 'Wait. Keep publishing consistently for several more weeks before submitting the AdSense application. Domains under 30 days face higher rejection rates.'],

    ['key' => 'brand.safety',     'situation' => 'Brand safety — hate speech / derogatory imagery', 'severity' => 'urgent', 'check_class' => Restricted_Keywords_Check::class, 'check_args' => ['keywords' => ['hatewords'], 'opt_in_setting' => 'enable_hate_scan'],
        'fix' => 'Audit posts for hate speech, derogatory imagery, or politically/racially polarizing content. Remove anything that violates Google\'s brand-safety guidelines.'],
    ['key' => 'brand.cmp',        'situation' => 'Deploy a certified CMP (IAB TCF v2.2)',           'severity' => 'severe', 'check_class' => Cmp_Detect_Check::class,
        'fix' => 'Install a Google-certified Consent Management Platform. Free options: CookieYes, Complianz, or Iubenda. Set up with IAB TCF v2.2 mode enabled.'],
    ['key' => 'brand.privacy',    'situation' => 'Comprehensive privacy disclosures',               'severity' => 'severe', 'check_class' => Privacy_Disclosure_Check::class,
        'fix' => 'Edit your Privacy Policy page to explicitly include the words \'third-party\', \'Google\', \'cookies\', and \'opt-out\' (e.g. \'We use third-party services including Google ads which set cookies; visit https://adssettings.google.com to opt-out\').'],

    // === Blog QA expansion (2026-05-23) — see spec 2026-05-23-blog-checklist-expansion-design.md ===
    ['key' => 'eeat.volume_aspirational', 'situation' => 'Volume tier: 80+ articles (stronger approval signal)',
        'severity' => 'moderate', 'check_class' => Article_Count_Check::class, 'check_args' => ['min_articles' => 80],
        'fix' => 'Aim for 80+ published posts before applying. AdSense approval is more reliable once the catalog feels substantial.'],

    ['key' => 'technical.sitemap', 'situation' => 'XML sitemap available',
        'severity' => 'severe', 'check_class' => Sitemap_Check::class,
        'fix' => 'Install Yoast SEO or RankMath, or enable WordPress core sitemaps (WP 5.5+). Sitemap should be reachable at /wp-sitemap.xml, /sitemap.xml, or /sitemap_index.xml.'],

    ['key' => 'branding.logo', 'situation' => 'Site logo set in the active theme',
        'severity' => 'moderate', 'check_class' => Logo_Check::class,
        'fix' => 'In Appearance → Customize → Site Identity, upload a logo. Themes vary in how they expose this; ensure has_custom_logo() returns true.'],

    ['key' => 'technical.analytics', 'situation' => 'Analytics tag installed (GA4 or Google Tag Manager)',
        'severity' => 'moderate', 'check_class' => Analytics_Check::class,
        'fix' => 'Install a GA4 or GTM tag. Use a plugin like Site Kit by Google, or paste the gtag.js snippet into your theme header. Universal Analytics (UA-) is no longer accepted — GA4 only.'],

    ['key' => 'navigation.categories_balance', 'situation' => 'Each category has at least 15 published articles',
        'severity' => 'moderate', 'check_class' => Categories_Balance_Check::class,
        'fix' => 'Publish more posts in under-balanced categories, or consolidate sparse categories into broader ones. A well-organized blog needs ~15 posts per category for the navigation to feel substantive.'],

    ['key' => 'quality.visuals', 'situation' => 'Posts contain at least one image, video, or embed',
        'severity' => 'moderate', 'check_class' => Post_Visuals_Check::class,
        'fix' => 'Add an image or video to the linked post. Setting a featured image (post thumbnail) also counts. Short posts (<100 words) are skipped automatically.'],

    ['key' => 'eeat.cadence', 'situation' => 'Publication rhythm: 1+ post on at least 15 of the last 30 days',
        'severity' => 'moderate', 'check_class' => Publication_Cadence_Check::class,
        'fix' => 'During the AdSense approval phase, publish at least one article on most days. Aim for 15+ active days out of the last 30. Schedule posts in advance if needed.'],

    ['key' => 'technical.cdn', 'situation' => 'Content Delivery Network detected',
        'severity' => 'minor', 'check_class' => CDN_Check::class,
        'fix' => 'Optional but recommended. Enable Cloudflare (free tier works), or use a managed host that fronts your site with a CDN (Kinsta, WP Engine, etc).'],

    ['key' => 'quality.ux', 'situation' => 'UX & readability — verify navigation, spacing, typography are reader-friendly',
        'severity' => 'moderate', 'check_class' => UX_CSS_Check::class,
        'fix' => 'Adjust typography (line-height 1.5-1.6, body font 16-18px), simplify the menu, reduce ads above the fold. Run a Lighthouse audit for accessibility hints.'],

    ['key' => 'quality.topics', 'situation' => 'Choose topics with low competition / few existing posts elsewhere',
        'severity' => 'moderate', 'check_class' => Manual_Review_Check::class,
        'hint' => 'Pick 3 recent posts and search their titles in Google. If page 1 is filled by major outlets, the topic is too competitive for a new blog.',
        'fix' => 'Pivot to underserved niches: practical how-tos for specific situations, regional content, or specialized review categories. AdSense rewards uniqueness.'],

    ['key' => 'strategy.paid_traffic', 'situation' => 'If using paid traffic for approval (e.g., PlugRush), stop immediately after AdSense approves',
        'severity' => 'minor', 'check_class' => Manual_Review_Check::class,
        'hint' => 'Confirm: am I currently sending paid traffic? If yes, is it from a low-cost source (PlugRush, popunder networks)? Will I turn it off the day AdSense approves?',
        'fix' => 'Once approved, paid low-cost traffic hurts your account because invalid-click detection sees the poor engagement. Switch to SEO / social organic ASAP.'],

    ['key' => 'eeat.audience', 'situation' => 'Genuine organic audience (rough proxy: ~250 visits/month)',
        'severity' => 'severe', 'check_class' => Manual_Review_Check::class,
        'hint' => 'Check Google Search Console or your analytics. AdSense expects a baseline audience — not zero traffic. ~250 visits/month from organic search is a reasonable floor before applying.',
        'fix' => 'Index the site in Google Search Console, build a few backlinks from related niches, and publish consistently. Don\'t apply with zero traffic — wait until search starts sending some.'],
];
