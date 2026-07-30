=== Dynamic CTA for Elementor ===
Contributors: halimurrosyid
Donate link: https://indahweb.com/
Tags: elementor, dynamic tag, cta, migration, popup link, url segment resolver
Requires at least: 6.0
Tested up to: 6.7
Requires PHP: 8.1
Stable tag: 1.0.2
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Universal area-based CTA link migration plugin for Elementor Pro. Dynamically routes traffic based on URL path segments, post slug, category, or tag with GitHub automatic 1-click updates.

== Description ==

"Dynamic CTA for Elementor" is a universal WordPress plugin created by **Mujaddid Halimurrosyid** ([indahweb.com](https://indahweb.com/)) designed to seamlessly migrate traffic from legacy articles to targeted area landing pages using **ONLY ONE Elementor Popup**.

Instead of creating hundreds of different popups for each area/city, you simply add the **Dynamic CTA URL** dynamic tag to your Elementor popup's Image, Button, or Banner link field. The plugin automatically detects the page context (URL Path Segments -> Post Slug -> Title -> Category -> Tag) and generates the correct area landing URL on the fly.

### ✨ Key Features
* **URL Path Segment Extraction**: Automatically converts URLs like `https://iconnet.biz.id/promo/bandung/` directly to `https://jasawifi.com/iconnet/bandung/`.
* **Universal Domain Support**: Configurable Target Destination Base URL for any domain migration.
* **Strict Area Precision**: Built-in dictionary of 500+ Indonesian cities/regencies/districts to eliminate non-area words.
* **Elementor Dynamic Tag Integration**: Select "Dynamic CTA URL" directly from Elementor link fields.
* **Multi-Layer Priority Resolution**: Custom Overrides -> URL Path Segment -> Post Slug/Title -> Categories -> Tags -> Default Fallback URL.
* **Clear All & Bulk Actions**: Easily purge or bulk edit mappings.
* **High Performance Caching**: Powered by WordPress Transients API for zero DB overhead on frontend requests.
* **Click Analytics**: Built-in dashboard to monitor clicks, top performing areas, and popular articles.
* **Automatic GitHub Updates**: Directly update the plugin from WordPress Dashboard whenever a new version is released on GitHub!

== Changelog ==

= 1.0.2 =
* Added Multi-Layer Smart URL Path Segment Resolver (e.g. `/promo/bandung/` automatically maps to `https://jasawifi.com/iconnet/bandung/`).
* Enhanced Post Title & Request Path keyword matching for irregular URL structures.

= 1.0.1 =
* Added XML Sitemap Scanner (Rank Math / Yoast / WP Sitemaps support).
* Added 500+ Indonesian location dictionary for strict high-accuracy area detection.
* Made Destination Base URL 100% universal for any domain migration.
* Added "Clear All Mappings" button.

= 1.0.0 =
* Initial release with Elementor Dynamic Tag, Area Mapping CRUD, Auto-Detect Scanner, CSV Import/Export, Transient Caching, Click Statistics, and GitHub Auto-Updater.
