"""Create the trust/legal pages (About, Contact, Privacy, Terms, Disclaimer, Editorial,
Affiliate) per references/legal-pages-requirements.md, plus a Contact Form 7 form.

Idempotent: skips pages that already exist. Run: python -m scripts.build_pages
"""
from __future__ import annotations

import json

from scripts.config import load_config
from scripts.wp import WPClient, WPError

SITE = "GrowHaven"
EMAIL = "maradmusalo1122@gmail.com"
UPDATED = "June 15, 2026"
COUNTRY = "[INSERT ACTUAL COUNTRY OF OPERATION]"  # owner replaces before relying on Terms


def p(*paras):
    return "\n".join(paras)


ABOUT = p(
    "<p>GrowHaven started with a familiar kind of frustration: a tray of leggy seedlings, a border that never quite filled out, and a search that turned up ten contradictory answers and no real reassurance. We built this site to be the opposite of that &mdash; calm, specific, plant-tested gardening you can actually act on, written by people who keep the plots they write about and have made the mistakes already.</p>",
    "<h2>What we're here to solve</h2>",
    "<p>Gardening advice online is noisy. It is full of vague listicles, copy-paste tips, and instructions that quietly assume perfect soil, a big budget, and a greenhouse you do not have. We cut through that. Every guide is built around a real question gardeners actually ask &mdash; what to plant, when to plant it, why last year failed, and what to do this weekend.</p>",
    "<h2>What you'll find here</h2>",
    "<p>Our guides cover six corners of home growing: vegetable and kitchen gardening, flowers and ornamental plants, houseplants and indoor greenery, soil and plant care, fruit trees and orchard, and seasonal tasks and projects. Each article answers one question clearly &mdash; not a vague overview, but a usable answer with the reasoning behind it, so you understand the &ldquo;why&rdquo; and not just the &ldquo;what.&rdquo;</p>",
    "<h2>How we work</h2>",
    "<p>Our writers are gardeners first. They have nursed the failed seed trays, fought the slugs, pruned the trees too hard one year and too little the next, and learned what actually works at home. Growing advice is grounded in hands-on horticulture, soil guidance in real composting and feeding, and every article is written in plain language and reviewed by an editor before it goes live.</p>",
    "<h2>Our content standards</h2>",
    "<ul><li>Practical and specific &mdash; no filler, no fear-mongering.</li><li>Honest about uncertainty, including when the right answer is &ldquo;it depends on your soil and climate.&rdquo;</li><li>Written and edited by real people with names, faces, and areas of expertise.</li><li>Updated as seasons, products, and best practice change.</li></ul>",
    "<h2>The team</h2>",
    "<p>GrowHaven is written by a small editorial team &mdash; Elena Marsh, Theo Bramley, Rosa Delgado, Arjun Patel, and Nina Whitlock &mdash; each covering the corners of gardening they know best, from kitchen gardens and soil to flower borders, fruit trees, houseplants, and seasonal planning. You will find their bylines and short bios on every article they write, so you always know who is giving you advice and why they are worth listening to.</p>",
    "<p>One important note: our guides are educational and reflect general experience, not the specific conditions of your garden, soil, or climate. Have a question, a correction, or a garden photo to share? We would genuinely like to hear it &mdash; reach us any time at "
    f"<a href=\"mailto:{EMAIL}\">{EMAIL}</a> or through our <a href=\"/contact/\">contact page</a>.</p>",
)

PRIVACY = p(
    f"<p><em>Last Updated: {UPDATED}</em></p>",
    f"<p>This Privacy Policy explains how {SITE} (\"we\", \"us\", \"our\") collects, uses, and protects information when you visit {SITE} (the \"Site\"). By using the Site, you agree to the practices described here.</p>",
    "<h2>Information we collect</h2>",
    "<p>We collect limited information automatically through standard web technologies &mdash; such as your browser type, device, approximate location, and the pages you view &mdash; primarily through analytics and advertising cookies. We collect personal information (like your name and email) only when you choose to give it to us, for example by emailing us or using our contact form.</p>",
    "<h2>How we use information</h2>",
    "<p>We use this information to operate and improve the Site, understand what content is useful, respond to your messages, and serve relevant advertising. We do not sell your personal information.</p>",
    "<h2>Cookies</h2>",
    "<p>Cookies are small files stored on your device. We and our partners use them to remember preferences, measure traffic, and personalise content and ads. You can control or delete cookies through your browser settings; doing so may affect how the Site works.</p>",
    "<h2>Advertising and personalization</h2>",
    "<p>We display advertising, including through Google and other third-party vendors. These partners may use cookies and similar technologies to serve and personalise ads based on your prior visits to this and other websites. Google's use of advertising cookies enables it and its partners to serve ads to you based on your visits to our Site and/or other sites on the internet.</p>",
    "<h3>Manage your ad personalization</h3>",
    "<p>You can review and control how ads are personalised to you using Google's official tools:</p>",
    '<ul><li><a href="https://adssettings.google.com" target="_blank" rel="noopener nofollow">Google Ads Settings &mdash; adssettings.google.com</a></li>'
    '<li><a href="https://myadcenter.google.com" target="_blank" rel="noopener nofollow">Google My Ad Center &mdash; myadcenter.google.com</a></li></ul>',
    '<p>You may also opt-out of personalised advertising from many other providers through the industry pages at '
    '<a href="https://optout.aboutads.info" target="_blank" rel="noopener nofollow">optout.aboutads.info</a> and '
    '<a href="https://www.youronlinechoices.com" target="_blank" rel="noopener nofollow">youronlinechoices.com</a>. '
    'Opting out does not remove ads &mdash; it makes them less tailored to you.</p>',
    "<h2>Third-party links</h2>",
    "<p>Our articles may link to other websites. We are not responsible for the privacy practices or content of sites we do not operate, and we encourage you to read their policies.</p>",
    "<h2>Children's privacy</h2>",
    "<p>The Site is intended for a general, adult audience and is not directed at children under 13. We do not knowingly collect personal information from children.</p>",
    "<h2>Your choices</h2>",
    "<p>You can manage cookies in your browser, use the ad-personalization tools above, and contact us to ask what information we hold or to request its deletion where applicable.</p>",
    "<h2>Changes to this policy</h2>",
    "<p>We may update this policy from time to time. The \"Last Updated\" date above reflects the latest revision.</p>",
    "<h2>Contact</h2>",
    f"<p>Questions about this policy? Email us at <a href=\"mailto:{EMAIL}\">{EMAIL}</a> or use our <a href=\"/contact/\">contact page</a>.</p>",
)

TERMS = p(
    f"<p><em>Last Updated: {UPDATED}</em></p>",
    f"<p>These Terms of Use govern your access to and use of {SITE} (the \"Site\"). By using the Site, you agree to these Terms. If you do not agree, please do not use the Site.</p>",
    "<h2>Use of the Site</h2>",
    "<p>You may use the Site for personal, non-commercial purposes. You agree not to misuse the Site, interfere with its operation, or attempt to access it in any way other than through the interface we provide.</p>",
    "<h2>Intellectual property</h2>",
    f"<p>All content on the Site &mdash; text, images, logos, and design &mdash; is owned by or licensed to {SITE} and is protected by applicable intellectual-property laws. You may not reproduce or republish it without permission.</p>",
    "<h2>Content and accuracy</h2>",
    "<p>Our content is provided for general information and education only and is not a substitute for professional horticultural, agricultural, or other expert advice. We work hard to keep it accurate and current, but growing guidance, products, and best practice evolve, and every garden, soil, and climate is different, so we make no guarantee that everything is complete or applies to your situation. See our <a href=\"/disclaimer/\">Disclaimer</a> for more.</p>",
    "<h2>Third-party links and advertising</h2>",
    "<p>The Site contains links to third-party websites and displays third-party advertising. We are not responsible for the content, products, or practices of third parties.</p>",
    "<h2>Limitation of liability</h2>",
    "<p>To the fullest extent permitted by law, the Site and its content are provided \"as is\" without warranties of any kind, and we are not liable for any loss arising from your use of the Site.</p>",
    "<h2>Governing Law</h2>",
    f"<p>These Terms shall be governed by and interpreted in accordance with the laws of <strong>{COUNTRY}</strong>, without regard to conflict-of-law principles. Any disputes arising under these Terms shall be subject to the applicable courts of <strong>{COUNTRY}</strong>, unless otherwise required by law.</p>",
    f"<p><em>Note for the site owner: replace \"{COUNTRY}\" with the actual country of operation before publication.</em></p>",
    "<h2>Changes to these Terms</h2>",
    "<p>We may revise these Terms at any time. Continued use of the Site after changes means you accept the revised Terms.</p>",
    "<h2>Contact</h2>",
    f"<p>Questions about these Terms? Email <a href=\"mailto:{EMAIL}\">{EMAIL}</a>.</p>",
)

DISCLAIMER = p(
    f"<p><em>Last Updated: {UPDATED}</em></p>",
    f"<p>The information on {SITE} is provided for general informational and educational purposes only. It is not professional horticultural, agricultural, medical, or other expert advice, and you should not treat it as such.</p>",
    "<h2>Every garden is different</h2>",
    "<p>Your soil, climate, light, water, and local plant varieties all shape what will work for you, and results will differ from ours. Treat our guides as a starting point, observe your own plants, and consult a local nursery, extension service, or qualified professional before making significant decisions about your garden, especially where chemicals, large trees, or structural work are involved.</p>",
    "<h2>Safety first</h2>",
    "<p>Garden tasks carry real risks. Always follow the label and local regulations when using fertilisers, pesticides, or other chemicals; handle tools, ladders, and powered equipment safely; and remember that many common plants are toxic to people, pets, and livestock if eaten or handled. If you suspect poisoning or have a serious reaction, contact your local poison control line, doctor, or veterinarian immediately. Do not wait.</p>",
    "<h2>No guarantees</h2>",
    "<p>We share general strategies and illustrative examples; your conditions, results, and costs will differ. We make no guarantee that any information is complete, current, or suitable for your specific situation.</p>",
    "<h2>External links</h2>",
    "<p>We link to third-party websites for your convenience. We do not control and are not responsible for their content or practices.</p>",
    "<h2>Affiliate relationships</h2>",
    "<p>Some links on this Site may be affiliate links. See our <a href=\"/affiliate-disclosure/\">Affiliate Disclosure</a> for details.</p>",
    "<h2>Use at your own risk</h2>",
    f"<p>Any action you take based on the information on this Site is strictly at your own risk. {SITE} will not be liable for any losses or damages connected with the use of the Site.</p>",
    f"<p>Questions? Contact us at <a href=\"mailto:{EMAIL}\">{EMAIL}</a>.</p>",
)

EDITORIAL = p(
    f"<p><em>Last Updated: {UPDATED}</em></p>",
    f"<p>This policy explains how {SITE} creates, reviews, and maintains its content, and the standards every article is held to.</p>",
    "<h2>Who writes our content</h2>",
    "<p>Every article is written by a named member of our editorial team and published under their byline, with a short biography describing their background and the topics they cover.</p>",
    "<h2>How we research and write</h2>",
    "<p>We draw on first-hand gardening experience, horticultural knowledge where relevant, and reputable sources. We write in plain, human language and aim to be genuinely useful rather than merely comprehensive, and we avoid scare tactics and overblown promises.</p>",
    "<h2>Review and human oversight</h2>",
    "<p>Content is reviewed by an editor before publication for accuracy, clarity, and tone. While we may use modern tools to assist research and drafting, every article is shaped and checked by a human editor before it goes live.</p>",
    "<h2>Corrections and updates</h2>",
    "<p>We update articles when guidance, products, or best practice change, and we correct errors promptly. If you spot something that looks wrong, please tell us.</p>",
    "<h2>Editorial independence</h2>",
    "<p>Our recommendations reflect our honest opinion. Advertising and affiliate relationships never determine our editorial conclusions, and they never come before giving you advice that actually works in your garden.</p>",
    f"<p>Questions about our process? Email <a href=\"mailto:{EMAIL}\">{EMAIL}</a>.</p>",
)

AFFILIATE = p(
    f"<p><em>Last Updated: {UPDATED}</em></p>",
    f"<p>{SITE} is reader-supported. Some of the links on this Site are affiliate links, which means we may earn a small commission if you click through and make a purchase &mdash; at no additional cost to you.</p>",
    "<h2>What this means</h2>",
    "<p>When we link to a product or service &mdash; tools, seeds, or supplies &mdash; that link may be an affiliate link. If you buy something after clicking it, the retailer may pay us a small referral fee. You pay exactly the same price either way.</p>",
    "<h2>Our promise</h2>",
    "<p>Affiliate commissions never change our opinion. We only recommend things we genuinely believe are useful for home gardeners, and we say so plainly when something isn't worth the money. Our <a href=\"/editorial-policy/\">Editorial Policy</a> keeps our advice independent of any commercial relationship.</p>",
    f"<p>Questions about our affiliate relationships? Email <a href=\"mailto:{EMAIL}\">{EMAIL}</a>.</p>",
)


def contact_html(form_shortcode: str) -> str:
    return p(
        "<p>We're a small team that genuinely reads its inbox. Whether you have a question, a correction, a partnership idea, or just a garden photo to share, we'd love to hear from you.</p>",
        "<h2>Email us</h2>",
        f"<p>The fastest way to reach us is by email at <a href=\"mailto:{EMAIL}\">{EMAIL}</a>. We aim to reply within 2&ndash;3 business days.</p>",
        "<h2>Send a message</h2>",
        (form_shortcode or "<p>Prefer a form? Email us at the address above and we'll get right back to you.</p>"),
        "<h2>What to expect</h2>",
        "<ul><li>We read every message a real person sends.</li><li>We reply within 2&ndash;3 business days, usually sooner.</li><li>We don't add you to any list or share your details.</li></ul>",
        "<h2>A quick note</h2>",
        "<p>We can't diagnose a sick plant from a distance or design your whole garden over email &mdash; for site-specific problems, a local nursery or extension service is your best bet &mdash; but for questions about our guides, corrections, or working together, we're all ears.</p>",
    )

PAGES = [
    ("About Us", "about", ABOUT),
    ("Privacy Policy", "privacy-policy", PRIVACY),
    ("Terms of Use", "terms", TERMS),
    ("Disclaimer", "disclaimer", DISCLAIMER),
    ("Editorial Policy", "editorial-policy", EDITORIAL),
    ("Affiliate Disclosure", "affiliate-disclosure", AFFILIATE),
]


def main() -> int:
    c = WPClient(load_config())
    c.verify()
    if c.channel != "wpcli":
        print("Needs WP-CLI channel.")
        return 1

    def wp_try(args):
        try:
            return c.wp(args)
        except WPError as e:
            return "ERR:" + str(e)

    # Contact Form 7 + default form shortcode
    wp_try(["plugin", "install", "contact-form-7", "--activate"])
    form_id = ""
    ids = wp_try(["post", "list", "--post_type=wpcf7_contact_form", "--field=ID", "--posts_per_page=1"]).strip()
    if ids and ids.split()[0].isdigit():
        form_id = ids.split()[0]
    form_sc = f'[contact-form-7 id="{form_id}"]' if form_id else ""

    pages = list(PAGES) + [("Contact", "contact", contact_html(form_sc))]

    for title, slug, html in pages:
        listed = wp_try(["post", "list", "--post_type=page", f"--name={slug}", "--post_status=any", "--fields=ID,post_status", "--format=json"])
        rows = json.loads(listed) if listed.strip().startswith("[") else []
        published = [r for r in rows if r.get("post_status") == "publish"]
        if published:
            print(f"[exists] {slug} (page {published[0]['ID']})")
            continue
        # Remove any non-published stub squatting this slug (e.g. WordPress' default
        # Privacy Policy draft) so the real page gets the clean slug, not "<slug>-2".
        for r in rows:
            wp_try(["post", "delete", str(r["ID"]), "--force"])
            print(f"[removed stub] {slug} (was page {r['ID']}, {r.get('post_status')})")
        pid = c.wp([
            "post", "create", "--post_type=page", "--post_status=publish",
            f"--post_title={title}", f"--post_name={slug}", f"--post_content={html}", "--porcelain",
        ]).strip()
        print(f"[created] {slug} -> page {pid}")

    c.close()
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
