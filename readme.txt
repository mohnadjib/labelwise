=== Labelwise ===
Contributors: marzoni
Tags: ai, disclosure, ai-content, transparency, labels
Requires at least: 6.2
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 0.1.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Label AI-assisted and AI-generated content, record human review, and show a clear disclosure badge on your posts and pages.

== Description ==

Labelwise helps you be open about how AI is used on your website. Mark any post or page as **AI-assisted**, **AI-generated** or **AI-assisted and human-reviewed**, and a clean, accessible badge tells your readers.

**Free features**

* A simple “AI disclosure” box in the editor for posts, pages and any public post type you choose.
* An accessible badge shown above or below the content, or placed anywhere with the `[labelwise_badge]` shortcode.
* Light and dark badge styles, with your own wording for every label.
* Optional “Reviewed by” name and date, and a “Learn more” link to your AI policy page.
* An “AI label” column in the content lists and an overview of how many items carry each label.
* No tracking and no external requests.

**Labelwise Pro**

* Publishing gate: hold AI-labelled content as “Pending review” until an editor marks it as human-reviewed.
* Review log: who changed which label, and when.
* Machine-readable markers: a `<meta>` tag, a REST API field and an RSS element.
* Chatbot notice shortcode: `[labelwise_chatbot_notice]`.
* AI-use policy page generator (a template you review and adapt).

[Get Labelwise Pro](https://checkout.freemius.com/plugin/44354/plan/78159/) — optional paid upgrade, sold by Freemius.

**Important**

Labelwise helps you document and disclose AI use. It is not legal advice and does not guarantee compliance with any law or regulation. Check the rules that apply to you.

== Privacy ==

Labelwise stores its settings and the label you choose for each post in your own WordPress database. It does not collect, analyse or send any data to external services.

== Installation ==

1. Upload the plugin to `/wp-content/plugins/labelwise`, or install it from the Plugins screen.
2. Activate it.
3. Go to **Labelwise** in the admin menu and choose where the badge appears.
4. Edit a post or page and pick a status in the “AI disclosure” box.

== Frequently Asked Questions ==

= Does this plugin detect AI-written text? =

No. You choose the label for each piece of content. Labelwise does not analyse or send your text anywhere.

= Does Labelwise make my site legally compliant? =

No. It helps you show and document what you disclose. It is not legal advice and does not guarantee compliance.

= Can I show the badge only on some pages? =

Yes. The badge only appears on content that has a label. Set the position to “Do not insert” to place it manually with the `[labelwise_badge]` shortcode.

= Does it work with page builders and block themes? =

The badge is added to the post content and is also available as a shortcode, so it works wherever shortcodes are supported.

== Changelog ==

= 0.1.4 =
* Activating Labelwise no longer deactivates any other plugin. If two copies are active, a notice asks the user which one to keep.

= 0.1.3 =
* The WordPress.org version no longer bundles any licensing code; the Pro package is distributed separately.

= 0.1.2 =
* Plugin name shortened so the WordPress.org slug matches the text domain.

= 0.1.1 =
* Packaging fix: premium code is now fully separated from the free build.

= 0.1.0 =
* First release.
