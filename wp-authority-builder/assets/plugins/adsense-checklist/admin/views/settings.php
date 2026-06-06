<?php
/**
 * Settings tab — card-grouped sections with toggle switches + thresholds grid.
 *
 * @var \AdSenseChecklist\Settings $settings
 */
if (!defined('ABSPATH')) { exit; }

$values = $settings->all();
$header_args = ['subtitle' => 'AdSense Checklist', 'title' => 'Settings', 'show_actions' => false];
include ADSENSE_CHECKLIST_PATH . 'admin/views/partials/header.php';
?>

<form method="post" action="options.php" class="adsc-settings">
    <?php settings_fields('adsense_checklist'); ?>

    <!-- Card 1: Scan behavior -->
    <section class="adsc-card">
        <header class="adsc-card-header">
            <h3>Scan behavior</h3>
            <p class="adsc-card-sub">What the plugin checks during a scan.</p>
        </header>
        <div class="adsc-card-body">
            <div class="adsc-toggle-row">
                <div class="adsc-toggle-label">
                    <div class="adsc-toggle-title">Scan external links</div>
                    <div class="adsc-toggle-desc">When on, the broken-links check HEADs every external URL too (slower).</div>
                </div>
                <label class="adsc-toggle">
                    <input type="checkbox" name="adsense_checklist_settings[scan_external_links]" value="1" <?php checked($values['scan_external_links']); ?>>
                    <span class="adsc-toggle-track"></span>
                </label>
            </div>
            <div class="adsc-toggle-row">
                <div class="adsc-toggle-label">
                    <div class="adsc-toggle-title">Enable slur keyword scan</div>
                    <div class="adsc-toggle-desc">Opt-in. High false-positive risk on news/politics blogs.</div>
                </div>
                <label class="adsc-toggle">
                    <input type="checkbox" name="adsense_checklist_settings[enable_slur_scan]" value="1" <?php checked($values['enable_slur_scan']); ?>>
                    <span class="adsc-toggle-track"></span>
                </label>
            </div>
            <div class="adsc-toggle-row">
                <div class="adsc-toggle-label">
                    <div class="adsc-toggle-title">Enable hate-speech keyword scan</div>
                    <div class="adsc-toggle-desc">Opt-in. Same false-positive caveat as slurs.</div>
                </div>
                <label class="adsc-toggle">
                    <input type="checkbox" name="adsense_checklist_settings[enable_hate_scan]" value="1" <?php checked($values['enable_hate_scan']); ?>>
                    <span class="adsc-toggle-track"></span>
                </label>
            </div>
        </div>
    </section>

    <!-- Card 1b: Content policy scans (opt-in) -->
    <section class="adsc-card">
        <header class="adsc-card-header">
            <h3>Content policy scans (opt-in)</h3>
            <p class="adsc-card-sub">Keyword-based scans with higher false-positive risk. Enable per category.</p>
        </header>
        <div class="adsc-card-body">
            <div class="adsc-toggle-row">
                <div class="adsc-toggle-label">
                    <div class="adsc-toggle-title">Illegal content scan</div>
                    <div class="adsc-toggle-desc">Scans posts for drug, fake-document, and tax-evasion keywords. Enable cautiously on news/journalism blogs.</div>
                </div>
                <label class="adsc-toggle">
                    <input type="checkbox" name="adsense_checklist_settings[enable_illegal_scan]" value="1" <?php checked($values['enable_illegal_scan']); ?>>
                    <span class="adsc-toggle-track"></span>
                </label>
            </div>
            <div class="adsc-toggle-row">
                <div class="adsc-toggle-label">
                    <div class="adsc-toggle-title">Animal cruelty scan</div>
                    <div class="adsc-toggle-desc">Scans for cock-fighting, ivory-trade, and shark-finning keywords. Disable on wildlife / journalism blogs.</div>
                </div>
                <label class="adsc-toggle">
                    <input type="checkbox" name="adsense_checklist_settings[enable_animal_scan]" value="1" <?php checked($values['enable_animal_scan']); ?>>
                    <span class="adsc-toggle-track"></span>
                </label>
            </div>
            <div class="adsc-toggle-row">
                <div class="adsc-toggle-label">
                    <div class="adsc-toggle-title">Unreliable / harmful claims scan</div>
                    <div class="adsc-toggle-desc">Scans for anti-vaccine, election-fraud, and pseudoscience keywords. Disable on debate / politics blogs.</div>
                </div>
                <label class="adsc-toggle">
                    <input type="checkbox" name="adsense_checklist_settings[enable_unreliable_scan]" value="1" <?php checked($values['enable_unreliable_scan']); ?>>
                    <span class="adsc-toggle-track"></span>
                </label>
            </div>
            <div class="adsc-toggle-row">
                <div class="adsc-toggle-label">
                    <div class="adsc-toggle-title">Mail-order brides scan</div>
                    <div class="adsc-toggle-desc">Scans for marriage-agency keywords. Disable on awareness / journalism blogs.</div>
                </div>
                <label class="adsc-toggle">
                    <input type="checkbox" name="adsense_checklist_settings[enable_mail_brides_scan]" value="1" <?php checked($values['enable_mail_brides_scan']); ?>>
                    <span class="adsc-toggle-track"></span>
                </label>
            </div>
            <div class="adsc-toggle-row">
                <div class="adsc-toggle-label">
                    <div class="adsc-toggle-title">Shocking content scan</div>
                    <div class="adsc-toggle-desc">Scans for graphic-gore / shock-imagery keywords. Disable on news / true-crime blogs.</div>
                </div>
                <label class="adsc-toggle">
                    <input type="checkbox" name="adsense_checklist_settings[enable_shocking_scan]" value="1" <?php checked($values['enable_shocking_scan']); ?>>
                    <span class="adsc-toggle-track"></span>
                </label>
            </div>
        </div>
    </section>

    <!-- Card 2: Thresholds -->
    <section class="adsc-card">
        <header class="adsc-card-header">
            <h3>Thresholds</h3>
            <p class="adsc-card-sub">Numeric limits a check uses to decide pass / fail.</p>
        </header>
        <div class="adsc-thresholds">
            <div class="adsc-threshold">
                <div class="adsc-threshold-label">Min word count per post</div>
                <input type="number" min="0" name="adsense_checklist_settings[min_word_count]" value="<?php echo (int) $values['min_word_count']; ?>">
            </div>
            <div class="adsc-threshold">
                <div class="adsc-threshold-label">Min article count</div>
                <input type="number" min="0" name="adsense_checklist_settings[min_article_count]" value="<?php echo (int) $values['min_article_count']; ?>">
            </div>
            <div class="adsc-threshold">
                <div class="adsc-threshold-label">Min domain age (days)</div>
                <input type="number" min="0" name="adsense_checklist_settings[min_domain_age_days]" value="<?php echo (int) $values['min_domain_age_days']; ?>">
            </div>
            <div class="adsc-threshold">
                <div class="adsc-threshold-label">Max posts to scan</div>
                <input type="number" min="1" name="adsense_checklist_settings[max_posts_to_scan]" value="<?php echo (int) $values['max_posts_to_scan']; ?>">
            </div>
        </div>
    </section>

    <!-- Card 3: AdSense identity -->
    <section class="adsc-card">
        <header class="adsc-card-header">
            <h3>AdSense identity</h3>
            <p class="adsc-card-sub">Optional. Used to verify the ads.txt line matches.</p>
        </header>
        <div class="adsc-card-body">
            <div class="adsc-threshold">
                <div class="adsc-threshold-label">Publisher ID</div>
                <input type="text" name="adsense_checklist_settings[publisher_id]" value="<?php echo esc_attr($values['publisher_id']); ?>" placeholder="pub-1234567890123456" style="font-family:monospace;width:280px;">
            </div>
        </div>
    </section>

    <div class="adsc-settings-footer">
        <button type="submit" class="adsc-btn adsc-btn--primary">Save changes</button>
    </div>
</form>
