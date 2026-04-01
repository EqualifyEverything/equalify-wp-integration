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

- Public CSV feed endpoint at `/?equalify_csv=1`, ready to paste directly into Equalify.
- Admin settings page under **Settings > Equalify Integration** with a one-click copy-to-clipboard button for the feed URL.
- Paged table of every URL in the feed, showing its type (`html` or `pdf`) and enabled/disabled status.
- Per-URL enable/disable toggle — exclude specific pages or files from the feed without deleting them.
- Optional inclusion of direct PDF file URLs from the WordPress media library, controlled by a toggle in the Options section.

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

Go to **Settings > Equalify Integration** in the WordPress admin. The feed URL is displayed at the top of the page with a Copy to Clipboard button.

#### What URLs are included in the feed?

All published posts, pages, and public custom post types are included as `html` entries. PDF files from the media library can also be included as `pdf` entries — enable this under the Options section on the settings page.

#### How do I exclude a specific URL from the feed?

On the **Settings > Equalify Integration** page, find the URL in the table and click **Disable**. Disabled URLs remain visible in the table but are excluded from the CSV feed. You can re-enable them at any time.

#### Does disabling a URL in the feed affect my site?

No. The Disable/Enable toggle only controls whether a URL appears in the Equalify CSV feed. It has no effect on the content or visibility of the page itself.

#### How do I include PDF files from my media library?

On the settings page, check the **Include direct file URLs of PDF files in the media library** option under the Options section and click **Save Options**.

## Changelog

### 1.0.0

- Initial release.
- Public CSV feed endpoint at `/?equalify_csv=1`.
- Admin settings page under Settings > Equalify Integration.
- Per-URL enable/disable toggle.
- Optional inclusion of media library PDF URLs.
