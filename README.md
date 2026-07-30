# Dynamic CTA for Elementor

![Version](https://img.shields.io/badge/version-1.0.1-blue.svg)
![WordPress](https://img.shields.io/badge/wordpress-6.0%2B-blue.svg)
![PHP](https://img.shields.io/badge/php-8.1%2B-purple.svg)
![Elementor](https://img.shields.io/badge/elementor-pro-red.svg)
![License](https://img.shields.io/badge/license-GPLv2-green.svg)

**Dynamic CTA for Elementor** is a universal WordPress plugin created by **[Mujaddid Halimurrosyid](https://indahweb.com/)** to seamlessly manage traffic migration from legacy articles to area-specific landing pages using **ONLY ONE Elementor Popup**.

---

## 🚀 Purpose & Workflow

When migrating thousands of area-specific articles (such as `/pasang-internet-bandung/`, `/pasang-internet-bekasi/`, `/pasang-internet-jakarta/`), creating a separate popup for each city is tedious and unmaintainable. 

With **Dynamic CTA for Elementor**, you create **just ONE Elementor Popup** containing your CTA image or button, and set the link field to **Dynamic Tags → Dynamic CTA URL**. The plugin dynamically converts the link based on the active post's context!

```
Visitor opens: https://domain.com/pasang-internet-bandung/
         ↓
Elementor Popup Image / Button Link
         ↓ Dynamic Tag ("Dynamic CTA URL")
Output URL: https://your-destination-site.com/target-path/bandung/
```

---

## 🔥 What's New in v1.0.1

- 🗺️ **XML Sitemap Scanner**: Support for Rank Math, Yoast, and WP Core XML sitemaps.
- 🎯 **500+ Location Precision**: Strict dictionary matching for Indonesian cities, regencies, and districts (filters out non-area words).
- 🌐 **100% Universal**: Fully configurable Target Destination Base URL for any site.
- 🗑️ **Clear All Mappings**: 1-click purge button in Admin UI.

---

## 🛠️ Installation

1. Download `dynamic-cta-elementor.zip` from the [Releases](https://github.com/halimurrosyid/Dynamic-CTA-for-Elementor/releases) page.
2. In WordPress Admin, go to **Plugins → Add New → Upload Plugin**.
3. Upload `dynamic-cta-elementor.zip` and click **Activate**.
4. Go to **Dynamic CTA → Area Mapping** and click **Generate Mapping (Auto Detect / Sitemap)**.

---

## 👨‍💻 Author & Credits

- **Author**: Mujaddid Halimurrosyid
- **Website**: [indahweb.com](https://indahweb.com/)
- **Repository**: [github.com/halimurrosyid/Dynamic-CTA-for-Elementor](https://github.com/halimurrosyid/Dynamic-CTA-for-Elementor)

---

## 📄 License

Distributed under the GPLv2 or later License.
