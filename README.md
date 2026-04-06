# DarkShield

A WordPress plugin that blocks external HTTP requests, protects user privacy, and improves site performance through configurable operating modes.

DarkShield provides three operating modes — Normal, National, and Offline — allowing site administrators to control which external resources are loaded. It is particularly useful for sites hosted in regions with restricted internet access, development environments, or any scenario where minimizing external dependencies is desired.

---

## Table of Contents

- [Features](#features)
- [Requirements](#requirements)
- [Installation](#installation)
- [Configuration](#configuration)
  - [Operating Modes](#operating-modes)
  - [Blockers](#blockers)
  - [Allowed Services](#allowed-services)
  - [Whitelist](#whitelist)
- [Scanner](#scanner)
- [Performance Analyzer](#performance-analyzer)
- [Logging](#logging)
- [Cache Compatibility](#cache-compatibility)
- [File Structure](#file-structure)
- [FAQ](#faq)
- [Changelog](#changelog)
- [License](#license)

---

## Features

- **Three operating modes**: Normal (no blocking), National (allow only Iranian domains), Offline (block all external requests)
- **Nine specialized blockers**: Fonts, CDN, Analytics, WordPress Updates, Gravatar, Embeds, reCAPTCHA, Heartbeat, Email
- **Whitelist management**: Add domains that should never be blocked, with subdomain matching
- **Allowed Services**: Quick-add support for Iranian and international payment gateways, SMS providers, messenger APIs, email services, and more
- **File and database scanner**: Detect external URLs in themes, plugins, core files, and database content
- **Performance analyzer**: Server-side resource ping and client-side Resource Timing API with waterfall visualization
- **Frontend analysis**: Admin bar button to collect real browser timing data on any frontend page
- **Request logging**: Filterable log with CSV export, date range filtering, and automatic cleanup
- **Cache plugin compatibility**: Automatic cache purge when switching modes (LiteSpeed, WP Rocket, W3 Total Cache, WP Super Cache, Autoptimize, WP Fastest Cache)
- **Output buffer filtering**: Removes blocked resource tags from rendered HTML on the frontend
- **Shamsi date support**: Displays dates in Jalali calendar when WP-Jalali or WP-Parsidate is active
- **Self-protection**: Plugin assets are loaded via direct print to prevent blockers from removing them

---

## Requirements

- WordPress 5.6 or higher
- PHP 7.4 or higher
- MySQL 5.6 or higher

---

## Installation
cd wp-content/plugins/ git clone https://github.com/silvercover/darkshield.git
1. Download or clone this repository into your `wp-content/plugins/` directory:


2. Activate the plugin through the WordPress admin panel under **Plugins**.

3. Navigate to **DarkShield > Dashboard** to configure your preferred mode.

Alternatively, upload the plugin ZIP file through **Plugins > Add New > Upload Plugin** in the WordPress admin.

---

## Configuration

### Operating Modes

| Mode | Behavior |
|------|----------|
| **Normal** | All external requests are allowed. No blocking occurs. |
| **National** | Only Iranian domains (`.ir` TLD and known Iranian services) are allowed. All other external requests are blocked. |
| **Offline** | All external HTTP requests are blocked. Only same-site and localhost URLs are permitted. |

Switch modes from the Dashboard or Settings page. Cache is automatically purged on mode change.

### Blockers

Each blocker can be enabled or disabled independently under **Settings > Blocker Options**:

| Blocker | Description |
|---------|-------------|
| Block External Fonts | Google Fonts, Typekit, Font Awesome |
| Block External CDNs | cdnjs, jsDelivr, unpkg, and similar |
| Block Analytics | Google Analytics, GTM, Hotjar, Clarity, Facebook Pixel |
| Block WordPress Updates | Core, plugin, and theme update checks |
| Block Gravatar | Replaces external avatars with a local SVG placeholder |
| Block Embeds | YouTube, Vimeo, Twitter, and other oEmbed providers |
| Block reCAPTCHA | reCAPTCHA, hCaptcha, Cloudflare Turnstile |
| Limit Heartbeat | Reduces frequency to 120s; fully disabled in Offline mode |
| Block Emails | Intercepts and logs outgoing emails in Offline mode |

### Allowed Services

Domains listed under **Settings > Allowed Service URLs** are never blocked, regardless of the active mode. This is intended for critical APIs such as:

- Payment gateways (Zarinpal, Stripe, PayPal, etc.)
- SMS providers (Kavenegar, Melipayamak, etc.)
- Messenger APIs (Telegram, Slack, Discord)
- Email delivery services (SendGrid, Mailgun, etc.)

Quick Add buttons are provided for common services. Subdomain matching is supported: adding `example.com` also allows `sub.example.com`.

### Whitelist

The Whitelist page provides a separate interface for managing allowed domains. It supports:

- Single domain addition
- Bulk import (one domain per line)
- Export to text file
- Search and filtering

Both Whitelist and Allowed Services bypass blocking. Use whichever interface is more convenient for your workflow.

---

## Scanner

The scanner detects external URLs embedded in your site:

- **File scan**: Searches PHP, JS, CSS, HTML, and JSON files in themes, plugins, mu-plugins, and wp-includes
- **Database scan**: Searches post content, post meta, options, and comments

Scanning runs in batches via AJAX to avoid timeouts. You can pause, resume, or stop a scan at any time. Results show the URL, domain, type classification, file path, and line number.

---

## Performance Analyzer

Three analysis modes are available under **DarkShield > Performance**:

### Server-Side Analysis

Enter any page URL. The plugin fetches the HTML, extracts all resource URLs (stylesheets, scripts, images, iframes, inline CSS imports), and pings each one to measure response time, HTTP status, content size, and cache headers.

### Frontend Analysis

On any frontend page, click **DarkShield > Analyze This Page** in the WordPress admin bar. The plugin collects Resource Timing API data from the browser, including:

- DNS lookup time
- TCP connection time
- Time to First Byte (TTFB)
- Download time
- Transfer size
- Cache status

Data is stored for 24 hours and viewable under the Frontend tab in the Performance page.

### Admin Page Analysis

Collects Resource Timing data for the current admin page to identify slow-loading admin assets.

All analysis modes include summary cards, a sortable resource table, a waterfall chart for the 20 slowest resources, and an external domains summary.

---

## Logging

When logging is enabled, all blocked and allowed requests are recorded with:

- Full URL and domain
- Request type and source
- Active mode at the time
- Block/allow status
- Timestamp

The log page supports filtering by domain, type, source, mode, status, and date range. Results can be exported to CSV. Automatic cleanup removes entries older than the configured retention period (default: 30 days).

---

## Cache Compatibility

DarkShield automatically purges caches when the operating mode changes. Supported plugins:

- LiteSpeed Cache
- WP Rocket
- W3 Total Cache
- WP Super Cache
- Autoptimize
- WP Fastest Cache

The WordPress object cache is also flushed on mode change.

---


---

## FAQ

### Does DarkShield affect the WordPress admin panel?

DarkShield applies blocking rules to both the frontend and admin panel. However, core WordPress admin assets (jQuery, admin scripts, etc.) and DarkShield's own files are protected and never blocked. If a third-party admin plugin loads external resources, those may be blocked depending on the active mode and blocker settings.

### Will my site break if I switch to Offline mode?

It depends on your site's external dependencies. Themes or plugins that rely on external CDNs, Google Fonts, or third-party APIs may lose functionality. Use the Scanner to identify external URLs before switching modes, and add critical services to the Allowed Services list.

### What is the difference between Whitelist and Allowed Services?

Both prevent domains from being blocked. Allowed Services is designed for API endpoints and payment gateways, with Quick Add buttons for common providers. Whitelist is a general-purpose list managed on its own page with import/export support. You can use either or both.

### Does DarkShield block WooCommerce payment gateways?

It can, if the gateway domain is not whitelisted or added to Allowed Services. Use the Quick Add buttons under Settings to add your payment gateway domains. All major Iranian gateways (Zarinpal, IDPay, Shaparak banks, etc.) and international gateways (Stripe, PayPal, etc.) are available as presets.

### How does the National mode determine Iranian domains?

A domain is considered Iranian if it ends with `.ir` or if it appears in the bundled `data/iranian-domains.php` list, which includes known Iranian CDNs, payment gateways, SMS providers, hosting services, and popular websites.

### Can I use DarkShield on a live production site?

Yes, but start with Normal mode and use the Scanner and Performance Analyzer to understand your site's external dependencies first. Then switch to National or Offline mode after configuring your Whitelist and Allowed Services.

### Does DarkShield work with page caching plugins?

Yes. DarkShield automatically purges caches from LiteSpeed Cache, WP Rocket, W3 Total Cache, WP Super Cache, Autoptimize, and WP Fastest Cache when you change the operating mode.

### How does the Frontend Performance Analyzer work?

When you visit any frontend page as an admin, the WordPress admin bar shows a "DarkShield > Analyze This Page" option. Clicking it collects Resource Timing data from the browser's Performance API, including DNS, TCP, TTFB, and download times for every loaded resource. The data is sent to the server and viewable in the Performance page under the Frontend tab.

### Will DarkShield block its own JavaScript and CSS files?

No. DarkShield loads its admin assets using direct HTML output rather than the standard WordPress enqueue system. This ensures that no blocker — including DarkShield itself — can interfere with the plugin's own files.

### Does DarkShield support multisite?

DarkShield is designed for single-site installations. Multisite support has not been tested and is not officially supported in the current version.

### How do I uninstall DarkShield completely?

Deactivate and delete the plugin through the WordPress admin. The `uninstall.php` file will remove all database tables, options, transients, and scheduled cron events created by the plugin.

### Can I contribute or report issues?

Yes. Open an issue or submit a pull request on the GitHub repository. Bug reports, feature requests, and translations are welcome.

---

## Changelog

### 1.0.0

- Initial release
- Three operating modes: Normal, National, Offline
- Nine specialized blockers
- Whitelist and Allowed Services management
- File and database scanner with batch processing
- Server-side and client-side performance analyzer
- Frontend analysis via admin bar
- Request logging with filters and CSV export
- Cache plugin compatibility
- Output buffer HTML filtering
- Shamsi date support

---

## License

This plugin is licensed under the [GPL v2 or later](https://www.gnu.org/licenses/gpl-2.0.html).



