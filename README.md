# Dynamic CTA for Elementor

![Version](https://img.shields.io/badge/version-1.0.0-blue.svg)
![WordPress](https://img.shields.io/badge/wordpress-6.0%2B-blue.svg)
![PHP](https://img.shields.io/badge/php-8.1%2B-purple.svg)
![Elementor](https://img.shields.io/badge/elementor-pro-red.svg)
![License](https://img.shields.io/badge/license-GPLv2-green.svg)

**Dynamic CTA for Elementor** is a premium WordPress plugin created by **[Mujaddid Halimurrosyid](https://indahweb.com/)** to seamlessly manage traffic migration from legacy articles to area-specific landing pages (e.g. `https://jasawifi.com/iconnet/bandung/`) using **ONLY ONE Elementor Popup**.

---

## 🚀 Purpose & Workflow

When migrating thousands of area-specific articles (such as `/pasang-iconnet-bandung/`, `/pasang-iconnet-bekasi/`, `/pasang-iconnet-jakarta/`), creating a separate popup for each city is tedious and unmaintainable. 

With **Dynamic CTA for Elementor**, you create **just ONE Elementor Popup** containing your CTA image or button, and set the link field to **Dynamic Tags → Dynamic CTA URL**. The plugin dynamically converts the link based on the active post's context!

```
Visitor opens: https://domain.com/pasang-iconnet-bandung/
         ↓
Elementor Popup Image / Button Link
         ↓ Dynamic Tag ("Dynamic CTA URL")
Output URL: https://jasawifi.com/iconnet/bandung/
```

---

## 🔥 Features

- 🎯 **Elementor Dynamic Tag (`Dynamic CTA URL`)**: Fully integrated into Elementor's native URL Category. Selectable directly from Image, Button, Call To Action, Banner, and Popup link fields.
- ⚡ **Smart Resolution Order**:
  1. **Post Slug** (e.g. `pasang-iconnet-bandung` → keyword `bandung`)
  2. **Categories** (matches category slug or name)
  3. **Tags** (matches tag slug or name)
  4. **Fallback Default URL** (configurable in Settings)
- 🔍 **Auto Detect Scanner**: 1-click scan button that analyzes all published post permalinks, extracts city/location keywords, and auto-populates mapping rules without duplicates.
- 📊 **Import & Export CSV**: Seamlessly backup, update, and manage location mappings in bulk using standard CSV files.
- 🚀 **High Performance Transients Caching**: Results are cached in WordPress Transients API for 12 hours (configurable). Ensures **0 extra database queries** during frontend page views. Cache automatically flushes on any mapping update.
- 📈 **Click Analytics Dashboard**: Record and monitor visitor clicks with detailed metrics: Date, Post Title, Area Name, Destination URL, Referer, IP Address, and User Agent.
- 🔄 **Automatic GitHub Updates**: Built-in 1-click update checker directly inside the WordPress Plugins dashboard powered by GitHub Releases.

---

## 🛠️ Installation

1. Download the latest `dynamic-cta-elementor.zip` from the [Releases](https://github.com/halimurrosyid/Dynamic-CTA-for-Elementor/releases) page.
2. In WordPress Admin, go to **Plugins → Add New → Upload Plugin**.
3. Upload `dynamic-cta-elementor.zip` and click **Activate**.
4. Go to **Dynamic CTA → Area Mapping** and click **Generate Mapping (Auto Detect)** to auto-scan your posts.

---

## 🔄 Automatic Updates Setup (For Developers)

Whenever you commit a new release to GitHub:
1. Increment `DYNAMIC_CTA_VERSION` in `dynamic-cta-elementor.php` and `readme.txt`.
2. Create a GitHub Release with tag `v1.0.1` (or `1.0.1`).
3. WordPress sites with this plugin installed will automatically detect the new release and offer a **1-click Update** link right inside WordPress Plugins menu!

---

## 👨‍💻 Author & Credits

- **Author**: Mujaddid Halimurrosyid
- **Website**: [indahweb.com](https://indahweb.com/)
- **Repository**: [github.com/halimurrosyid/Dynamic-CTA-for-Elementor](https://github.com/halimurrosyid/Dynamic-CTA-for-Elementor)

---

## 📄 License

Distributed under the GPLv2 or later License.
