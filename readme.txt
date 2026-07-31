=== Dynamic CTA for Elementor ===
Contributors: halimurrosyid
Donate link: https://indahweb.com/
Tags: elementor, dynamic tag, cta, migration, popup link, auto db migration
Requires at least: 6.0
Tested up to: 6.7
Requires PHP: 8.1
Stable tag: 1.1.2
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Universal area-based CTA link migration plugin for Elementor Pro. Dynamically routes traffic based on URL path segments, post slug, category, or tag with Destination Website Sitemap Importer and GitHub automatic 1-click updates.

== Description ==

"Dynamic CTA for Elementor" is a universal WordPress plugin created by **Mujaddid Halimurrosyid** ([indahweb.com](https://indahweb.com/)) designed to seamlessly migrate traffic from legacy articles to targeted area landing pages using **ONLY ONE Elementor Popup**.

== Changelog ==

= 1.1.2 =
* Added automatic database column migration check (`DB::ensure_schema_up_to_date`) on every admin page load & sitemap import call, enabling seamless updates without requiring plugin deactivation.
* Changed sitemap import insertion engine to `REPLACE INTO` for 100% insertion guarantee.

= 1.1.1 =
* Fixed XML Sitemap Importer for custom sitemaps.

= 1.1.0 =
* Fixed Word Boundary & Substring Collision bug.

= 1.0.8 =
* Added multi-day click comparison analytics.

= 1.0.0 =
* Initial release.
