=== Old Article Notice ===
Contributors: maggiemcguire
Donate link: https://venmo.com/maggie-mcguire-18
Tags: old article, archive, notice, warning, outdated, news
Requires at least: 5.0
Tested up to: 6.7
Requires PHP: 7.4
Stable tag: 1.1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Displays a configurable notice on old articles so readers know when content may be outdated. Built for news sites, blogs, and documentation.

== Description ==

Every publisher has the same problem: old articles stick around forever, and readers don't always check the date.

I run a [small-town newspaper](https://moabsunnews.com) in Moab, Utah. One day I realized someone was making decisions based on a three-year-old article about water rates. So I wrote 13 lines of PHP to slap a warning on anything over a year old. It worked. Then I thought: every news site, blog, and documentation site has this exact problem. So I turned those 13 lines into a real plugin.

**Old Article Notice** adds a clear, customizable warning to articles that are past their freshness date. You control the message, the look, the timing, and which posts get it. It's one file, zero JavaScript on the front end, and it just works.

= Features =

* **Set your threshold** — days, months, or years. You decide when "old" starts.
* **Write your own message** — use template tags like `{time_ago}` and `{date}` so each notice is specific to the article
* **Make it yours** — border color, text color, background, border width, corner radius. Match your site or make it impossible to miss.
* **Live preview** — see exactly what your notice looks like as you design it. No save-refresh-squint cycle.
* **Pick your post types** — posts, pages, custom post types. Show it where it matters, hide it where it doesn't.
* **Exclude categories** — obituaries don't go stale. Neither do "About Us" pages. Skip what doesn't need a warning.
* **Coverage links** — automatically point readers to your category or tag archive for newer coverage on the same topic. Works with any taxonomy including custom ones.
* **SEO-aware** — respects Yoast SEO and Rank Math primary term settings when picking which category or tag to link to.
* **Per-post override** — got an evergreen article that's old but still accurate? Disable the notice with a checkbox. Done.
* **Before or after content** — put it where it makes sense for your layout
* **Lightweight** — no external dependencies, no front-end JavaScript, no performance impact. Your PageSpeed score won't even notice.

= Who Is This For? =

* **News publishers** — your archived coverage should tell readers it's archived
* **Bloggers** — that tutorial from 2019 might not work anymore, and your readers deserve a heads-up
* **Documentation sites** — old docs are worse than no docs if people follow outdated instructions
* **Basically anyone** who keeps old content published and cares about their readers

= Template Tags =

Use these in your notice message to make it dynamic:

* `{time_ago}` — human-readable time (e.g., "2 years ago")
* `{years}` — number of years since publication
* `{months}` — number of months since publication
* `{days}` — number of days since publication
* `{date}` — the original publication date
* `{coverage_link}` — a link to newer coverage (requires Coverage Link to be enabled in settings)
* `{term_name}` — the name of the article's primary category or tag (requires Coverage Link)

Example: "This article was published {time_ago} on {date}. Some information may no longer be current."

Example with coverage link: "This article was published {time_ago}. {coverage_link}"

== Installation ==

1. Upload the `old-article-notice` folder to `/wp-content/plugins/`
2. Activate the plugin through the Plugins menu
3. Go to **Settings > Old Article Notice** to configure

That's it. Three steps and your readers will always know when they're looking at old content.

== Frequently Asked Questions ==

= How do I disable the notice on a specific post? =

When editing a post, look for the "Old Article Notice" meta box in the sidebar. Check "Disable notice on this article." This is perfect for evergreen content — guides, reference pages, anything that stays accurate regardless of age.

= Can I use HTML in the notice message? =

Yes. Links, line breaks, bold, italic — all supported. Want to link to an updated version of the article? Go for it.

= Does this work with custom post types? =

Yes. Any public post type registered on your site will show up in the settings. WooCommerce products, portfolio items, recipes — whatever you've got.

= Does this add any JavaScript to my site? =

Nope. The notice is pure HTML and CSS. Nothing to render-block, nothing to slow down your page. Your visitors (and Google) will never know it's there until they see an old article.

= How does the Coverage Link feature work? =

Enable it under Settings > Old Article Notice > Coverage Link. Pick a taxonomy (categories, tags, or custom), write your link text using `{term_name}`, then add `{coverage_link}` to your notice message. When an old article has a term from that taxonomy, readers get a direct link to the archive page where they can find newer coverage on the same topic. If the article has no matching terms, the tag is silently removed.

= I run a news site. Any tips? =

That's exactly what I built this for. Set your threshold to something reasonable (we use 365 days), exclude categories that don't age (obituaries, "About Us" pages), and use a message that's honest without being alarming. Something like: "This article is over {time_ago} old and is kept for archival purposes. Some details may have changed."

== Screenshots ==

1. Settings page with live preview — design your notice without guessing
2. Notice displayed on an old article
3. Per-post disable checkbox in the editor sidebar

== Support Development ==

This plugin is free and always will be. I built it because I needed it, and I shared it because you might too.

If it saves you time or makes your site better for your readers, consider buying me a coffee. I'll drink it while publishing tomorrow's paper.

[Venmo: @maggie-mcguire-18](https://venmo.com/maggie-mcguire-18)

More tools and projects at [maggie-mcguire.com](https://maggie-mcguire.com).

== Changelog ==

= 1.1.0 =
* New: Coverage Link feature — automatically link readers to the article's category or tag archive for newer coverage
* New: `{coverage_link}` template tag — inserts a linked call-to-action pointing to the term archive
* New: `{term_name}` template tag — inserts the article's primary category/tag name
* New: Choose any public taxonomy (categories, tags, or custom) for coverage links
* New: Respects Yoast SEO and Rank Math primary term selection
* New: Coverage Link settings with show/hide toggle and live preview support

= 1.0.0 =
* Initial release
* Configurable age threshold (days, months, years)
* Template tags for dynamic messages
* Color and border customization with live preview
* Post type selection and category exclusions
* Per-post disable via meta box
* Before or after content positioning
