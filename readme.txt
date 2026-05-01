=== DarkShield ===
Contributors: silvercover
Donate link: https://github.com/silvercover/darkshield
Tags: security, privacy, performance, block-requests, firewall
Requires at least: 5.6
Tested up to: 6.9
Stable tag: 1.0.1
Requires PHP: 7.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Block external HTTP requests, protect user privacy, and improve site performance.

== Description ==

DarkShield provides three operating modes to control external requests:

* **Normal** — All requests allowed
* **National** — Only Iranian domains allowed
* **Offline** — All external requests blocked

Features:

* Nine specialized blockers: Fonts, CDN, Analytics, Updates, Gravatar, Embeds, reCAPTCHA, Heartbeat, Email
* Whitelist and Allowed Services for payment gateways, SMS, Telegram, and more
* File and database scanner for external URLs
* Performance analyzer with server-side and client-side analysis
* Request logging with filters and CSV export
* Cache plugin compatibility (LiteSpeed, WP Rocket, W3TC, WP Super Cache)
* Output buffer filtering for HTML resources

== Installation ==

1. Upload the `darkshield` folder to `/wp-content/plugins/`
2. Activate the plugin through the Plugins menu
3. Go to DarkShield > Dashboard to configure

== Frequently Asked Questions ==

= Does DarkShield affect the admin panel? =

Core WordPress admin assets and DarkShield's own files are protected and never blocked. Third-party admin plugin resources may be blocked depending on mode and settings.

= Will my site break in Offline mode? =

It depends on external dependencies. Use the Scanner first to identify external URLs, then add critical services to Allowed Services before switching modes.

= What is the difference between Whitelist and Allowed Services? =

Both prevent domains from being blocked. Allowed Services has Quick Add buttons for common providers. Whitelist is a general-purpose list with import/export. Use either or both.

= Does it block WooCommerce payment gateways? =

Only if the gateway domain is not in Whitelist or Allowed Services. Use Quick Add buttons to add your payment gateway.

= How does National mode detect Iranian domains? =

Domains ending with .ir or listed in the bundled iranian-domains.php file are considered Iranian.

= Does it work with caching plugins? =

Yes. Cache is automatically purged when switching modes. Supports LiteSpeed, WP Rocket, W3TC, WP Super Cache, Autoptimize, and WP Fastest Cache.

== Screenshots ==

1. Dashboard with mode switcher and stats
2. Scanner results showing external URLs
3. Performance analyzer with waterfall chart
4. Settings page with blocker options
5. Log page with filters and export

== Changelog ==

= 1.0.0 =
* Initial release
* Three operating modes: Normal, National, Offline
* Nine specialized blockers
* Whitelist and Allowed Services management
* File and database scanner
* Server-side and client-side performance analyzer
* Request logging with CSV export
* Cache plugin compatibility

== Upgrade Notice ==

= 1.0.0 =
Initial release.
