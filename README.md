<img src="logo.svg" alt="Equalify Logo" width="300">

# Equalify WordPress Integration

Connect your WordPress site to Equalify to keep your accessibility audits in sync with your latest content.

- **Contributors:** azdak
- **Requires WordPress:** 5.0+
- **Tested up to:** 6.7
- **Stable tag:** 1.0.0
- **License:** [GPLv2 or later](http://www.gnu.org/licenses/gpl-2.0.html)

## Description

Equalify WordPress Integration generates a public CSV feed of all URLs on your WordPress site — posts, pages, custom post types, and optionally PDF files from your media library — so that Equalify can always audit your most up-to-date content.

**Features:**

- Public CSV feed endpoint, protected by a secret token generated on activation.
- Admin settings page under **Settings > Equalify Integration** with a one-click copy-to-clipboard button for the feed URL (token included).
- Paged table of every URL in the feed with search, showing its type (`html` or `pdf`) and enabled/disabled status.
- Per-URL enable/disable toggle — exclude specific pages or files from the feed without deleting them.
- Optional inclusion of direct PDF file URLs from the WordPress media library, controlled by a toggle in the Options section.
- DB-level pagination — only the current page of URLs is loaded into memory at a time.
- Streamed CSV output — posts are fetched in chunks and written directly to the response, keeping memory usage flat on large sites.
- Object cache integration — count and fetch results are cached when an external object cache (Redis, Memcached) is available, with automatic invalidation whenever posts are saved, deleted, or the PDF option changes.
- Multisite compatible — each subsite maintains its own independent feed, settings, and cache.

**CSV format:**

The feed outputs a two-column CSV compatible with Equalify's URL import:

```csv
url,type
https://example.com/sample-post/,html
https://example.com/wp-content/uploads/2026/01/document.pdf,pdf
```

## Installation

1. Upload the `equalify-wp-integration` folder to the `/wp-content/plugins/` directory, or install it through the WordPress Plugins screen.
2. Activate the plugin through the **Plugins** menu in WordPress.
3. Go to **Settings > Equalify Integration** to find your CSV feed URL.
4. Copy the feed URL and paste it into Equalify to begin auditing your site.

## Frequently Asked Questions

#### Where do I find the CSV feed URL?

Go to **Settings > Equalify Integration** in the WordPress admin. The feed URL — including the secret token — is displayed at the top of the page with a Copy to Clipboard button.

#### What URLs are included in the feed?

All published posts, pages, and public custom post types are included as `html` entries. PDF files from the media library can also be included as `pdf` entries — enable this under the Options section on the settings page.

#### How do I exclude a specific URL from the feed?

On the **Settings > Equalify Integration** page, find the URL in the table and click **Disable**. Disabled URLs remain visible in the table but are excluded from the CSV feed. You can re-enable them at any time.

#### Does disabling a URL in the feed affect my site?

No. The Disable/Enable toggle only controls whether a URL appears in the Equalify CSV feed. It has no effect on the content or visibility of the page itself.

#### How do I include PDF files from my media library?

On the settings page, check the **Include direct file URLs of PDF files in the media library** option under the Options section and click **Save Options**.

## Changelog

### 1.0.0 — 2026-04-08

- Secret token generated on activation; required as a query parameter to access the CSV feed.
- Disabled URLs now stored as post IDs rather than URL strings, preventing data loss on large sites and surviving permalink structure changes.
- DB-level pagination replaces PHP-side slicing — only the visible page of URLs is loaded into memory.
- CSV endpoint streams posts in 200-item chunks rather than loading all posts at once.
- Object cache integration: results are cached when Redis or Memcached is available, with invalidation on post save/delete and option change.
- URL table now includes a search box; heading shows match count alongside total.
- Pagination gains First and Last buttons.
- Plugin CSS and JS now load only on the Equalify Integration settings screen.
- Multisite: each subsite gets its own token on network activation and when new sites are created; plugin options and cache are scoped per subsite; uninstall cleans up all subsites.

### 0.9.0

- Initial release.
- Public CSV feed endpoint at `/?equalify_csv=1`.
- Admin settings page under Settings > Equalify Integration.
- Per-URL enable/disable toggle.
- Optional inclusion of media library PDF URLs.
