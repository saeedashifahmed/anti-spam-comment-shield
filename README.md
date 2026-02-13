# WP Anti-Spam Comment

#### Advanced, lightweight, GDPR-compliant anti-spam protection for WordPress comments. No setup required.

![WordPress Plugin Downloads](https://img.shields.io/wordpress/plugin/dd/wp-anti-spam-comment?&color=6C5CE7&style=for-the-badge&labelColor=2d3436&cacheSeconds=300) ![WordPress Plugin Active Installs](https://img.shields.io/wordpress/plugin/installs/wp-anti-spam-comment?style=for-the-badge&labelColor=2d3436&cacheSeconds=300&&color=6C5CE7)

**Download:** [WP Anti-Spam Comment](https://wordpress.org/plugins/wp-anti-spam-comment/)

---

> [!TIP]
> No configuration required. Just activate and all spam protection is enabled by default. Clear your page cache after activation.

---

### 🛡️ Features

| Feature | Description |
|---|---|
| **Hash-Based Verification** | Blocks bots by hiding the comment form action URL and restoring it via JavaScript only on real user interaction |
| **Honeypot Trap** | Hidden form field that catches bots filling every field — invisible to humans |
| **Time-Based Check** | Rejects comments submitted too quickly (configurable threshold) |
| **REST API Protection** | Blocks unauthenticated comment creation via the WP REST API |
| **Spam Statistics** | Real-time dashboard showing total/daily blocked spam with animated counters |
| **Modern Settings Page** | Beautiful card-based admin UI with toggle switches for each protection module |
| **Admin Bar Counter** | See your spam-blocked count right in the WordPress admin bar |
| **Custom Block Message** | Customize the message shown when spam is blocked |
| **100% GDPR Compliant** | No external requests, no cookies, no tracking |
| **Captcha-Free** | No annoying CAPTCHAs — completely invisible to your visitors |
| **~200 Bytes JS** | Ultra-lightweight inline JavaScript with zero performance impact |

---

### How Does It Work?

1. **Action URL Removed** — The comment form's action URL is stripped from the HTML
2. **User Interaction Required** — JavaScript restores the URL with a unique hash when the user scrolls, moves the mouse, touches the screen, or focuses on the form
3. **Multi-Layer Validation** — Server validates the hash token, checks the honeypot field, and verifies submission timing
4. **Spam Blocked** — Any failed validation instantly returns a 403 response

---

### Installation

#### From the WordPress Dashboard:
1. Go to **Plugins** > **Add New**
2. Search for **WP Anti-Spam Comment**
3. Activate the plugin
4. **Clear your page cache** if using a caching plugin

#### Manual Installation:
1. Download the plugin
2. Upload to `/wp-content/plugins/` directory
3. Activate through the **Plugins** menu
4. **Clear your page cache** if using a caching plugin

---

### Settings

Navigate to **Settings → WP Anti-Spam** to:
- Toggle individual protection modules on/off
- Configure the minimum submit time threshold
- Customize the blocked message
- View real-time spam statistics

---

### Frequently Asked Questions

<details>
<summary>How do I test if the plugin is working?</summary>

Go to any post with comments enabled and try posting a comment. If it goes through, everything is working. If you see an error, clear your page cache first. If comments still fail, your theme may use a non-standard comment form ID — please open a support topic with your site URL.

</details>

<details>
<summary>What commenting systems are supported?</summary>

This plugin supports the **default WordPress commenting system** only. Third-party systems such as Disqus, JetPack Comments, and wpDiscuz are not supported.

</details>

<details>
<summary>Is this compatible with page caching?</summary>

Yes! The plugin is fully compatible with all page caching and performance optimization plugins. Just remember to clear your cache after activating or deactivating the plugin.

</details>

<details>
<summary>Does this work with AMP?</summary>

No. AMP pages do not support the JavaScript required for the anti-spam mechanism.

</details>

<details>
<summary>How can I get support?</summary>

Please [open a support topic](https://wordpress.org/support/plugin/wp-anti-spam-comment/) or visit [rabbitbuilds.com](https://rabbitbuilds.com/contact/). We respond within 24 hours.

</details>

---

### License

This plugin is released under the **GPL-2.0-or-later** license.
You may use, modify, and redistribute it under the GNU General Public License terms.
