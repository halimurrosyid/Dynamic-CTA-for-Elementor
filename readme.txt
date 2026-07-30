=== Dynamic CTA for Elementor ===
Contributors: halimurrosyid
Donate link: https://indahweb.com/
Tags: elementor, dynamic tag, cta, migration, popup link, automatic updater
Requires at least: 6.0
Tested up to: 6.7
Requires PHP: 8.1
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Dynamic area-based CTA link migration plugin for Elementor Pro. Dynamically routes traffic based on post slug, category, or tag with GitHub automatic 1-click updates.

== Description ==

"Dynamic CTA for Elementor" is a premium WordPress plugin created by **Mujaddid Halimurrosyid** ([indahweb.com](https://indahweb.com/)) designed to seamlessly migrate traffic from legacy articles to targeted area landing pages (e.g. `https://jasawifi.com/iconnet/bandung/`) using **ONLY ONE Elementor Popup**.

Instead of creating hundreds of different popups for each area/city, you simply add the **Dynamic CTA URL** dynamic tag to your Elementor popup's Image, Button, or Banner link field. The plugin automatically detects the page context (Post Slug -> Category -> Tag) and generates the correct area landing URL on the fly.

### ✨ Key Features
* **Elementor Dynamic Tag Integration**: Select "Dynamic CTA URL" directly from Elementor link fields.
* **Smart Priority Resolution**: Post Slug -> Post Categories -> Post Tags -> Default Fallback URL.
* **Auto Detect Generator**: 1-click scanner to analyze published post permalinks and auto-populate area mappings.
* **Import & Export CSV**: Easily backup and bulk-manage location mappings.
* **High Performance Caching**: Powered by WordPress Transients API for zero DB overhead on frontend requests.
* **Click Analytics**: Built-in dashboard to monitor clicks, top performing areas, and popular articles.
* **Automatic GitHub Updates**: Directly update the plugin from WordPress Dashboard whenever a new version is released on GitHub!

== Installation ==

1. Upload the `dynamic-cta-elementor` folder to the `/wp-content/plugins/` directory (or upload the ZIP file via WordPress Plugins > Add New).
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Navigate to **Dynamic CTA > Area Mapping** and click **Generate Mapping (Auto Detect)** to auto-scan your posts.
4. Edit your Elementor Popup or Widget, select the **Link** field, click **Dynamic Tags**, and choose **Dynamic CTA URL**.

== Automatic Updates ==

This plugin includes a built-in GitHub Automatic Updater. Whenever a new version or release tag is published at [github.com/halimurrosyid/Dynamic-CTA-for-Elementor](https://github.com/halimurrosyid/Dynamic-CTA-for-Elementor), WordPress will display a standard 1-click update notification in your Plugins dashboard.

== Changelog ==

= 1.0.0 =
* Initial release with Elementor Dynamic Tag, Area Mapping CRUD, Auto-Detect Scanner, CSV Import/Export, Transient Caching, Click Statistics, and GitHub Auto-Updater.
