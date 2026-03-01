=== Rabbit Builds Anti-Spam Comment Shield ===
Contributors: rabbitbuilds
Donate link: https://rabbitbuilds.com/
Tags: anti spam, stop spam, comment spam, security, gdpr
Requires at least: 5.0
Requires PHP: 7.4
Tested up to: 6.9
Stable tag: 2.0.4
License: GPL-2.0-or-later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Advanced, lightweight, GDPR-compliant anti-spam protection for WordPress comments. No setup required.

== Description ==

Rabbit Builds Anti-Spam Comment Shield is a fast, reliable anti-spam plugin for the **default WordPress commenting system**. It blocks automated bot submissions using layered validation, while keeping the experience clean for real visitors.

== Important ==
* Clear your page cache after activation or after changing plugin settings.
* Works with the default WordPress comment form only (not AMP comment forms).

= Features =
* 🛡️ **Hash-Based Verification** — Blocks bots by hiding the comment form action URL
* 🍯 **Honeypot Trap** — Hidden field that catches bots filling every input
* ⏱️ **Time-Based Check** — Rejects comments submitted too quickly
* 🔒 **REST API Protection** — Blocks unauthenticated REST API comments
* 📊 **Spam Statistics Dashboard** — Real-time total & daily blocked spam counter
* ⚙️ **Modern Settings Page** — Beautiful card-based UI with toggle switches
* 🔔 **Admin Bar Counter** — See blocked spam count in the admin bar
* ✏️ **Custom Block Message** — Personalize the blocked spam response
* 100% GDPR Compliant — No external requests, no cookies, no tracking
* Captcha-Free — Completely invisible to your visitors
* ~200 bytes inline JavaScript — Zero performance impact
* Compatible with all page caching and performance optimization plugins

= How does it work? =
1. The plugin removes the comment form action URL from raw HTML output.
2. When real user interaction is detected (scroll, mouse move, touch, or focus), JavaScript restores the action URL with a unique hash token.
3. On submit, the server validates the hash token, checks the honeypot field, and verifies submission timing.
4. If any check fails, the request is blocked with a 403 response before spam can reach your database.

== Installation ==

To install from the WordPress Dashboard:

1. Go to Plugins menu > Add New
1. Search for Rabbit Builds Anti-Spam Comment Shield
1. Activate the plugin
1. If using a page cache, clear/purge the cache

To install manually:

1. Download the Rabbit Builds Anti-Spam Comment Shield plugin
1. Upload to the `/wp-content/plugins/` directory
1. Activate through the 'Plugins' menu
1. If using a page cache, clear/purge the cache

== Frequently Asked Questions ==

= How to configure the plugin? =
Go to Settings > WP Anti-Spam to enable or disable protection modules, adjust timing thresholds, customize the blocked message, and review spam statistics.

= How to test whether the plugin is working? =
Open any post with comments enabled and submit a normal comment. If the comment is accepted, the plugin is working. If you see a 403 error, clear your page cache and try again. If it still fails, your theme may use a custom comment form ID—please open a support topic.

= Which commenting system is supported? =
Only the default WordPress commenting system is supported. Third-party systems such as Disqus, JetPack Comments, wpDiscuz, and AMP comments are not supported.

= How to get technical assistance? =
Please open a support topic on WordPress.org or contact us via rabbitbuilds.com. We typically respond within 24 hours.

== License ==

This plugin is licensed under **GPL-2.0-or-later**.
You may use, modify, and redistribute it under the terms of the GNU General Public License.

== Changelog ==

= 2.0.4 =
* Fixed WordPress.org plugin review issues
* Updated function/constant prefixes to avoid conflicts (rbascs_ and RBASCS_)
* Fixed text domain to match plugin slug (rabbitbuilds-anti-spam-comment-shield)
* Converted inline JavaScript to use wp_enqueue_scripts and wp_add_inline_script()
* Converted block response to use wp_die() instead of raw HTML
* Fixed file path detection to use plugin_dir_path() instead of ABSPATH
* Improved escaping on all translatable strings
* Added contributors to README

= 2.0.3 =
* Rebranded plugin display name to "Rabbit Builds Anti-Spam Comment Shield"
* Updated plugin slug to "rabbit-builds-anti-spam-comment-shield"

= 2.0.2 =
* **Major Rebrand** — Renamed from "Forget Spam Comment" to "Anti-Spam Comment Shield"
* **New:** Honeypot trap field for catching bots
* **New:** Time-based submission check (configurable threshold)
* **New:** REST API comment protection
* **New:** Modern settings page with card-based UI and toggle switches
* **New:** Real-time spam statistics (total, today, last blocked)
* **New:** Admin bar spam counter
* **New:** Custom blocked message setting
* **New:** Reset to defaults button
* **New:** Expanded event triggers (scroll + mousemove + touchstart + focus)
* **New:** Expanded comment form ID selector for more theme compatibility
* **Improved:** Block response page with modern, branded design
* **Improved:** Activation notice with modern styling
* **Updated:** Requires WordPress 5.0+ and PHP 7.4+

= 1.1.8 =
* Tested with 6.6.1 Updated on 09-Aug-2024.

= 1.1.7 =
* Tested with 6.5.5 Updated on 09-July-2024.

= 1.1.6 =
* Hotfix for Bedrock Compatibility. Updated on 30-Jan-2024.

= 1.1.5 =
* Enhanced Bedrock Compatibility. Updated on 24-Jan-2024.

= 1.1.4 =
* Added a fallback to cover unexpected cases. Updated on 20-Nov-2023.

= 1.1.3 =
* Enhanced uniqueness in query string parameters. Updated on 19-Nov-2023.
* Kindly clear your page cache after updating to version 1.1.3.

= 1.1.2 =
* Tested with WordPress 6.4.1 and PHP 8.2.12
* Addressed referral handling for increased precision
* Improved code readability and consistency following WordPress coding standards

= 1.1.1 =
* Tested with WordPress 6.2 and PHP 8.2.4

= 1.1.0 =
* Tested with WordPress 6.1.1 and PHP 8.2.3
* Updated links

= 1.0.9 =
* Tested with WordPress 6.0

= 1.0.8 =
* Tested with WordPress 5.9.1 and PHP 8.1.3

= 1.0.7 =
* Tested with WordPress 5.8
* Enhanced compatibility

= 1.0.6 =
* Tested plugin up to 5.7

= 1.0.5 =
* Added hint for purging cache in the generated error page to assist first time plugin user.

= 1.0.4 =
* Minor tweaks

= 1.0.3 =
* Minified inline JS for better performance
* Translation made available for Hindi, Bengali, English (United States), English (UK).
* Minor tweaks

= 1.0.2 =
* Added hint for purging cache upon plugin activation
* Added support links

= 1.0.1 =
* Performance optimization

= 1.0.0 =
* Initial release

== Upgrade Notice ==

= 2.0.4 =
Fixed WordPress.org plugin review issues including function prefix conflicts, text domain, enqueuing scripts/styles, and escaping.

= 2.0.3 =
Updated branding and slug to "Rabbit Builds Anti-Spam Comment Shield" (`rabbit-builds-anti-spam-comment-shield`).
