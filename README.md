# Old Article Notice

A WordPress plugin that displays a configurable notice on old articles so readers know when content may be outdated. Built for news sites, blogs, and documentation.

**Version:** 1.1.0 | **Requires:** WordPress 5.0+ / PHP 7.4+ | **License:** GPL v2

## The Problem

Old articles stick around forever, and readers don't always check the date. I run a [small-town newspaper](https://moabsunnews.com) in Moab, Utah. One day I realized someone was making decisions based on a three-year-old article about water rates. So I wrote 13 lines of PHP to slap a warning on anything over a year old. It worked. Then I thought: every news site, blog, and documentation site has this exact problem.

**Old Article Notice** adds a clear, customizable warning to articles past their freshness date. One file, zero front-end JavaScript, and it just works.

## Features

- **Set your threshold** — days, months, or years
- **Write your own message** — template tags like `{time_ago}` and `{date}` make each notice specific
- **Coverage links** — automatically point readers to your category or tag archive for newer coverage on the same topic
- **SEO-aware** — respects Yoast SEO and Rank Math primary term settings
- **Full styling control** — border color, text color, background, border width, corner radius
- **Live preview** — see your notice as you design it
- **Post type support** — posts, pages, custom post types
- **Category exclusions** — skip categories that don't age (obituaries, evergreen pages)
- **Per-post override** — disable with a checkbox on any individual article
- **Lightweight** — no external dependencies, no front-end JavaScript, no performance impact

## Installation

1. Download the [latest release](https://github.com/MaggieMcgu/old-article-notice/releases) or clone this repo
2. Upload the `old-article-notice` folder to `/wp-content/plugins/`
3. Activate through the Plugins menu
4. Configure at **Settings > Old Article Notice**

## Template Tags

Use these in your notice message:

| Tag | Output |
|-----|--------|
| `{time_ago}` | Human-readable time (e.g., "2 years ago") |
| `{years}` | Number of years since publication |
| `{months}` | Number of months since publication |
| `{days}` | Number of days since publication |
| `{date}` | Original publication date |
| `{coverage_link}` | Link to newer coverage (requires Coverage Link enabled) |
| `{term_name}` | Primary category/tag name (requires Coverage Link enabled) |

### Example Messages

**Basic:**
```
This article was published {time_ago} and is kept for archival purposes. Some information may be outdated.
```

**With coverage link:**
```
This article was published {time_ago}. {coverage_link}
```

**Direct:**
```
Heads up — this is {years}-year-old content. Check our {term_name} section for the latest.
```

## Coverage Link

New in v1.1.0. When enabled, `{coverage_link}` automatically generates a link to the article's primary category or tag archive page, so readers can find newer coverage on the same topic.

**How it works:**

1. Enable under Settings > Old Article Notice > Coverage Link
2. Choose a taxonomy (categories, tags, or any custom taxonomy)
3. Set your link text (use `{term_name}` for the category/tag name)
4. Add `{coverage_link}` to your notice message

The plugin picks the article's primary term from your chosen taxonomy. If Yoast SEO or Rank Math has set a primary term, that's used. Otherwise it falls back to the first assigned term. If the article has no matching terms, the tag quietly resolves to nothing — no broken output.

**Example output:**

> This article was published 2 years ago and is kept for archival purposes. Some information may be outdated. [See our latest Local Government coverage &rarr;](https://example.com/category/local-government/)

## Who Is This For?

- **News publishers** — your archived coverage should tell readers it's archived
- **Bloggers** — that tutorial from 2019 might not work anymore
- **Documentation sites** — old docs are worse than no docs if people follow outdated instructions
- **Anyone** who keeps old content published and cares about their readers

## FAQ

**Can I disable the notice on specific posts?**
Yes. Each post has an "Old Article Notice" meta box in the sidebar with a disable checkbox. Perfect for evergreen content.

**Can I use HTML in the message?**
Yes — links, bold, italic, line breaks are all supported.

**Does this add JavaScript to my front end?**
No. The notice is pure HTML and inline CSS. Zero performance impact.

**Does this work with custom post types?**
Yes. Any public post type shows up in settings.

## Support

This plugin is free and always will be. If it saves you time or makes your site better for your readers, consider [buying me a coffee](https://venmo.com/maggie-mcguire-18). I'll drink it while publishing tomorrow's paper.

More tools and projects at [maggie-mcguire.com](https://maggie-mcguire.com).

## Changelog

### 1.1.0
- Coverage Link feature — automatically link readers to newer coverage via category/tag archives
- `{coverage_link}` and `{term_name}` template tags
- Taxonomy picker (categories, tags, or custom)
- Yoast SEO and Rank Math primary term support
- Coverage Link settings with show/hide toggle and live preview

### 1.0.0
- Initial release
- Configurable age threshold, template tags, styling, live preview
- Post type selection, category exclusions, per-post disable
