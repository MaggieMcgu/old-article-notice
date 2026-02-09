=== Old Post Notice ===
Contributors: maggiemcguire
Donate link: https://venmo.com/maggie-mcguire-18
Tags: old post, archive, notice, warning, outdated, news
Requires at least: 5.0
Tested up to: 6.7
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Automatically displays a configurable notice on posts older than a set threshold, so readers know when content may be outdated.

== Description ==

**Old Post Notice** adds a clear, customizable warning to posts that are past their freshness date. Built by a small-town newspaper publisher who needed readers to know when they were looking at archived coverage.

Perfect for news sites, blogs, documentation, and any site where old content stays published but may no longer be current.

= Features =

* **Configurable age threshold** — set by days, months, or years
* **Customizable message** — template tags for dynamic text: `{time_ago}`, `{years}`, `{months}`, `{days}`, `{date}`
* **Visual styling** — border color, text color, background color, border width, corner radius
* **Live preview** — see your notice as you design it on the settings page
* **Post type support** — choose which post types show the notice
* **Category exclusions** — skip the notice for categories like obituaries or evergreen guides
* **Per-post override** — disable the notice on individual posts via a sidebar checkbox
* **Before or after content** — place the notice where it makes sense for your layout
* **Lightweight** — no external dependencies, no JavaScript on the front end, no database queries beyond what WordPress already does

= Who Is This For? =

* **News publishers** — archived stories should be clearly marked
* **Bloggers** — old tutorials or reviews may have outdated information
* **Documentation sites** — flag old docs that haven't been updated
* **Any WordPress site** that keeps old content published

= Template Tags =

Use these in your notice message:

* `{time_ago}` — human-readable time (e.g., "2 years ago")
* `{years}` — number of years since publication
* `{months}` — number of months since publication
* `{days}` — number of days since publication
* `{date}` — the original publication date

== Installation ==

1. Upload the `old-post-notice` folder to `/wp-content/plugins/`
2. Activate the plugin through the Plugins menu
3. Go to **Settings → Old Post Notice** to configure

== Frequently Asked Questions ==

= How do I disable the notice on a specific post? =

When editing a post, look for the "Old Post Notice" meta box in the sidebar. Check "Disable notice on this post." This is useful for evergreen content that stays relevant regardless of age.

= Can I use HTML in the notice message? =

Yes. Basic HTML (links, line breaks, bold, italic) is supported.

= Does this work with custom post types? =

Yes. Any public post type registered on your site will appear in the settings.

= Does this add any JavaScript to my site? =

No. The notice is pure HTML/CSS — no front-end JavaScript, no render-blocking scripts, no performance impact.

== Screenshots ==

1. Settings page with live preview
2. Notice displayed on a post
3. Per-post disable checkbox in the editor

== Support Development ==

If this plugin is useful to you, consider buying me a coffee:

[Venmo: @maggie-mcguire-18](https://venmo.com/maggie-mcguire-18)

== Changelog ==

= 1.0.0 =
* Initial release
* Configurable age threshold (days, months, years)
* Template tags for dynamic messages
* Color and border customization with live preview
* Post type selection and category exclusions
* Per-post disable via meta box
* Before or after content positioning
