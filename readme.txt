=== RankOut Connector – Claude, ChatGPT & SEO Data Connector ===
Contributors: rankout
Tags: mcp, ai, ai-seo, claude, mcp-server
Requires at least: 6.0
Tested up to: 7.0
Requires PHP: 7.4
Stable tag: 2.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Connect Claude, ChatGPT & any AI to WordPress. Manage your entire site by chat — content, media, GA4, Search Console, SEO, GEO, AEO, E-E-A-T & more. 233 tools. Free.

== Description ==

[**RankOut Connector**](https://rankout.app/) is the most complete **free WordPress MCP server** — a remote MCP server built so AI assistants and autonomous AI agents can run your entire site workflow, from content and publishing to SEO research, traffic monitoring, and daily admin, through the [Model Context Protocol](https://modelcontextprotocol.io). It works as an MCP adapter for any MCP-compatible AI client, making your site agent-ready out of the box. Ask your AI about Google Analytics, Google Search Console, and SEO data without leaving your chat. You bring the direction. Your AI handles the execution.

No Node.js. No external proxy. No complicated setup. Just install, generate a token, and start building.

**At a glance:**

* **233 tools** across posts, pages, media, users, comments, menus, Google Analytics 4, Google Search Console, Semrush, DataforSEO, SEO/GEO/AEO/E-E-A-T, filesystem, database, and more
* **1-click OAuth 2.0/2.1** with per-scope consent (Claude Desktop, Cursor, etc.)
* **Plugin integrations** — WooCommerce, ACF, The Events Calendar, BuddyPress, Yoast, Rank Math, AIOSEO
* **Google Analytics 4 & Google Search Console** — ask your AI about traffic, top pages, conversions, search queries, clicks, impressions, and indexing status
* **Semrush** — ask your AI for keyword research, domain overviews, organic keywords, competitor research, keyword difficulty, related keywords, question phrases, and backlink analytics
* **DataforSEO** — ask your AI for live SERP results, keyword search volumes, on-page SEO audits, backlink data, and ranked/site keywords
* **Auto-discovers WordPress 6.9+ Abilities API**
* **Full audit trail** — every AI action on your site is logged in a searchable user activity log
* **Change History** — every MCP-originated write (posts, meta, terms, users, options, comments, WooCommerce, BuddyPress) is recorded with before/after snapshots and queryable via 3 dedicated `wp_history_*` tools

= Works With Every Major AI =

Connect any of the following AI assistants or AI agents to your site through the **WordPress MCP** endpoint — [full integration guides here](https://rankout.app/integrations):

* **Manus** — the autonomous AI agent that can run multi-step workflows start to finish
* **Claude** (Claude.ai, Claude Desktop, Claude Code) — connect Claude to WordPress in one click via OAuth
* **ChatGPT** (OpenAI) — connect ChatGPT to WordPress and manage your entire site by chat
* **Gemini AI** (Gemini CLI / Google Antigravity) — Google's AI tools with MCP support
* **Cursor, Windsurf, Cline, Roo Code** — AI-powered code editors that can also manage your content
* **n8n** — automation for content pipelines and publishing workflows
* **Any MCP-compatible client** — the protocol is open and supported by a growing ecosystem

= What Can Your AI Do On Your Site? =

Once connected, your **AI agent** can handle everything you'd normally do in the WordPress admin:

**AI Content Writing & Publishing** — let your **AI agent** draft, rewrite, SEO-optimize, schedule, and publish WordPress posts and pages; update existing posts and pages

**AI Media Library & Alt Text** — upload images from chat, browse the media library, and auto-generate AI alt text and captions for SEO and accessibility

**Taxonomy & Navigation** — manage categories, tags, term meta, and WordPress navigation menus; assign terms from any taxonomy to posts

**User Management** — create WordPress user accounts, assign roles, update profiles, and manage user meta

**Plugins & Themes** — list installed plugins and themes; see which theme is currently active

**WordPress Settings** — read and update site title, tagline, timezone, date format, time format, and posts-per-page

**WooCommerce AI Agent** — manage WooCommerce products, variations, attributes, orders, customers, coupons, and webhooks; view order refunds, shipping zones, shipping methods, tax rates, and payment gateways; pull sales, top-seller, and revenue reports; bulk update products, variations, and orders

**SEO with Yoast, Rank Math & AIOSEO** — read and update Yoast SEO, Rank Math, and All in One SEO (AIOSEO) post metadata: SEO titles, meta descriptions, Open Graph and Twitter card fields, plus focus keywords (Yoast, Rank Math), canonical URLs (Rank Math, AIOSEO), no-index (AIOSEO), and cornerstone content flag (Yoast)

**Advanced Custom Fields (ACF)** — read and write ACF custom field values on posts and users; read ACF fields on taxonomy terms; list ACF field groups

**Events Calendar & BuddyPress** — create, edit, and delete events with The Events Calendar; create and view venues; create and list organizers; list BuddyPress members, groups, group members, and private message threads; create and delete activity stream posts

**Comment Moderation** — let AI list, approve, hold, mark as spam, edit, or delete WordPress comments

**Change History & Rollback Awareness** — every write your AI makes is recorded with structured before/after snapshots. Ask "what did the AI change on this post last week?", diff any two revisions, or audit per-user activity through the `wp_history_list`, `wp_history_get`, and `wp_history_diff` tools — plus a full **Change History** admin page with retention and on/off controls

**Gutenberg & Full Site Editing** — create, edit, and reuse Gutenberg blocks; update block templates and global styles for FSE themes

**Custom Post Types (CPT)** — read and write any registered custom post type — portfolios, listings, courses, reviews, anything

**Google Analytics 4** — ask about traffic, top pages, conversions, custom dimensions/metrics, and realtime active users

**Google Search Console** — ask about top search queries, clicks, impressions, sitemaps, and URL indexing status

**Semrush** — pull domain overviews, keyword research, organic competitors, keyword difficulty and related keywords, question phrases, and backlink overview / referring domains / anchors for any target

**DataforSEO** — run on-page SEO audits on any URL, check keyword search volumes and trends, pull live SERP results, analyse backlinks, and look up ranked keywords for any domain

**AEO (Answer Engine Optimisation)** — extract and create FAQ blocks, audit posts for featured-snippet eligibility, score opening paragraphs, headings, and list structure for "People also ask" capture

**E-E-A-T / HEO (Human Experience Optimisation)** — full per-post E-E-A-T audit across Experience, Expertise, Authoritativeness, and Trustworthiness; find stale content by staleness age; get all internal links in a post; discover internal linking opportunities between related posts

**Site-wide SEO Reporting** — master site audit (schema coverage %, stale content, E-E-A-T score distribution, AEO/GEO readiness); content gap report to compare target topics against existing coverage

**wp-content Filesystem** — Claude can read any theme file, plugin file, or file anywhere under `wp-content/` to review source code, debug templates, or audit plugin configs

**Raw SQL (read-only)** — run SELECT queries against any table with `{prefix}` substitution; blocked from any write or destructive operation

**Any Plugin** — automatically connects to plugins that support WordPress 6.9+ Abilities API, no custom code needed

**Ask your AI anything — for example:**
* "Write a 500-word blog post about healthy eating and publish it as a draft"
* "Show me today's WooCommerce orders and their total revenue"
* "What are the top 10 search queries bringing traffic to my site this month?"
* "Update all product prices in the Summer Sale category by -20%"
* "What keywords does my homepage rank for and what are the click counts?"
* "Rewrite the introduction of my About page to sound more professional"

= Tools =

[**214 Tools, Ready to Use**](https://rankout.app/tools)

**93 core tools** covering every major WordPress content type:

**Posts** — list, get, create, update, delete, search, count; get full post (with meta + terms in one call); find-and-replace inside post content
**Pages** — list, get, create, update, delete
**Media** — list, get, upload, upload from URL, update, delete, count; update AI alt text on any image
**Categories** — list, get, create, update, delete, count
**Tags** — list, get, create, update, delete, count
**Taxonomy Terms (any taxonomy)** — generic create, get, update, delete for any registered taxonomy
**Comments** — list, get, create, update, delete
**Users** — list, get, create, update, delete
**Menus** — list menus, get, create, update, delete; list, create, update, delete menu items
**Custom Post Types** — list, get, create, update, delete CPT items
**Post Meta** — get, update, delete post meta; add taxonomy terms to a post
**Term Meta** — get, update, delete term meta
**User Meta** — get, update, delete user meta
**Revisions** — list, get, delete, restore post revisions
**Blocks** — list, get, create, update, delete AI blocks and reusable blocks
**Templates** — list, get, update block templates
**Styles** — get and update global styles
**Site** — get and update settings, list post types, taxonomies, and post statuses
**Plugins** — list installed plugins
**Themes** — list themes, get active theme
**Search** — search across all content
**Change History** — list, get, and diff every MCP-originated write across posts, meta, terms, users, options, comments, WooCommerce, and BuddyPress

= 3 Change History Tools =

**wp_history_list** — query change records by user, object type, object id, tool name, or date range; supports `since` / `until` filters and pagination
**wp_history_get** — fetch a single change record with full before/after JSON snapshots
**wp_history_diff** — compute a structured diff between any recorded snapshot and either another snapshot or the current live state of the object

Non-admin tokens see only their own changes. Administrators (with the new `rankout_connector_view_all_history` capability — granted to the Administrator role on activation) see every user's changes. Sensitive keys are redacted before storage, sensitive post meta keys (matching patterns like `*_token`, `*_secret`, `*password*`, `*api_key*`) are redacted at write time, and snapshot size is capped. The `wp_history_diff` tool also enforces these gates when reading the *current* live state: meta requires `edit_post`, options require `manage_options`, and protected meta keys are excluded entirely. Site owners can narrow query scope further via the `rankout_connector_history_query_scope` filter (the self-pin for non-admins cannot be weakened by the filter).

= 11 Google Analytics 4 Tools =

**Account & Property** — list account summaries, get property details, check compatibility, get metadata
**Reports** — run standard reports, pivot reports, and realtime reports
**Configuration** — list data streams, conversion events, custom dimensions, and custom metrics

= 6 Google Search Console Tools =

**Sites** — list verified properties
**Search Analytics** — query top search terms, pages, countries, devices with clicks, impressions, CTR, and position
**Sitemaps** — list and inspect submitted sitemaps
**URL Inspection** — check indexing status and coverage for any URL on your site

= 13 Semrush Tools =

**Domain** — domain overview and organic competitor research
**Keywords** — keyword research tools: domain organic keywords, URL organic keywords, keyword overview, related keywords, keyword difficulty, and phrase questions
**Backlinks** — backlinks overview, backlinks list, referring domains, and anchors
**Account** — check your Semrush API units balance at any time

= 8 DataforSEO Tools =

**SERP** — fetch live search engine results pages for any keyword and location
**Keywords** — look up monthly search volume and trend data for one or more keywords
**Labs** — get ranked keywords for any domain, or find keywords a specific page ranks for
**Backlinks** — get a backlink summary and list of referring domains for any target URL
**On-Page** — run a full on-page SEO audit on any URL and get a list of actionable issues
**Account** — check your DataforSEO API account balance at any time

= 46 WooCommerce MCP Tools =

**Products** — list, get, create, update, delete products
**Product Variations** — list, get, create, update, delete product variations
**Product Attributes** — list, create, and set product attributes
**Product Categories** — list product categories
**Orders** — list, get, create, update orders; list order notes, create order note; list order refunds (read-only)
**Customers** — list, get, create, update, delete customers
**Coupons** — list, get, create, update, delete coupons
**Webhooks** — list, get, create, update, delete webhooks
**Shipping** — list shipping zones, list shipping methods
**Tax** — list tax rates
**Payment** — list payment gateways
**Reports** — sales, orders, products, top sellers, customers
**Batch** — bulk create, update, or delete products, variations, and orders in a single request

= 7 Plugin Integrations =

**WooCommerce** — 46 WooCommerce AI tools for products, orders, customers, coupons, shipping, reports, and more
**Advanced Custom Fields (ACF)** — 6 tools to get and update ACF fields on posts, users, and terms; list ACF field groups
**The Events Calendar** — 10 tools to create and manage events, venues, and organizers
**BuddyPress** — 10 tools for members, activity stream, groups, group members, and private messages
**Yoast SEO** — get and update post SEO metadata, meta description, and rendered SEO head output
**Rank Math** — get and update post SEO metadata, meta description, and rendered SEO head output
**All in One SEO (AIOSEO)** — get and update post SEO metadata

= Connect Any Plugin with Abilities API =

WordPress 6.9+ introduces **Abilities API** — a standard way for plugins to declare what they can do. RankOut Connector acts as an **MCP adapter** for any plugin that registers Abilities — automatically discovering and exposing them as MCP tools with no custom code needed. If a plugin supports the Abilities API, your AI can use it out of the box.

= One-Click Connect with OAuth 2.0/2.1 =

Skip manual token copy-paste. Your **WordPress MCP** endpoint ships with a full **OAuth 2.0/2.1** authorization server — PKCE, refresh-token rotation, and Dynamic Client Registration (RFC 7591) built in. Compatible MCP clients like Claude Desktop can connect with a single click: they register themselves, you approve the scopes on a consent screen, and you're done. Bearer tokens still work for power users and automation.

= Built for Security =

Giving an AI access to your site is serious — so security is built into every layer:

* **Bearer token authentication** with SHA-256 hashing — the raw token is never stored
* **Per-token permissions** — create a read-only token for one AI, a full-access token for another
* **WordPress capability checks** on every single tool call
* **Rate limiting** per token (default 60 requests/min, configurable)
* **Full audit trail** — every tool call is logged in a searchable user activity log with the token used, arguments, result, and client IP
* **IP whitelisting** — optionally restrict which IPs can use the MCP endpoint

= Simple Admin Interface =

* **Dashboard** — your MCP endpoint URL and one-click connection configs for every major AI client
* **API Tokens** — create and manage tokens with a checkbox-based tool permission tree
* **Audit Log** — a paginated, searchable user activity log of every AI action taken on your site
* **Change History** — a dedicated page with before/after snapshots of every MCP-originated write, inline diff expand, and user / object / date filtering
* **Settings** — tune rate limits, audit and change-history retention, IP whitelist, and more

== Installation ==

= Automatic Installation =

1. In your WordPress admin, go to **Plugins → Add New Plugin**.
2. Search for "RankOut Connector".
3. Click **Install Now** and then **Activate**.

= Manual Installation =

1. Download the plugin ZIP from the WordPress plugin directory.
2. In your WordPress admin, go to **Plugins → Add New Plugin → Upload Plugin**.
3. Upload the ZIP, click **Install Now**, then **Activate**.

= After Activation =

**Which should I use?** Use Path A if your client supports OAuth.

= Path A — One-Click Connect (OAuth) =

1. Go to **RankOut Connector → Dashboard** and copy your MCP server URL.
2. In your AI client (e.g. Claude Desktop → Settings → Connectors → Add custom connector), paste the server URL. No token needed.
3. Your browser opens a WordPress login + consent screen. Sign in as the user the AI should act as.
4. Tick the permission categories (Read / Write per content type, GA4, Search Console, etc.) you want to grant, then **Approve**.
5. The client is connected. Start talking to your site.
6. Manage or revoke connected clients anytime under **RankOut Connector → API Token & OAuth → OAuth** tab.

= Path B — Manual Token (Bearer) =

1. Go to **RankOut Connector → API Tokens** in your WordPress admin sidebar.
2. Click **Create New Token**.
3. Give the token a name, choose the WordPress user the AI will act as, and select which tools to allow.
4. Click **Create Token** and copy the token — it is only shown once.
5. Open your AI assistant, paste in the endpoint URL and token from the Dashboard page.
6. Start talking to your site.


== External services ==

This plugin connects to the following third-party services **only when a site administrator explicitly configures their own external account credentials** in **RankOut Connector → External Data**. Nothing is contacted on a default install.

**Semrush API** — `api.semrush.com`, `www.semrush.com`

* When: only if an admin saves a Semrush API key.
* What is sent: the configured Semrush API key plus the parameters supplied per call (target domain, target URL, keyword/phrase, database/region code, display limits).
* Terms: https://www.semrush.com/company/legal/terms-of-service/
* Privacy: https://www.semrush.com/company/legal/privacy-policy/

**DataForSEO** — `api.dataforseo.com`

* When: only if an admin saves a DataForSEO account login + API password.
* What is sent: the configured DataForSEO login + API password (HTTP Basic auth), plus the parameters supplied per call (keyword, target domain, target URL, location code, language code).
* Terms: https://dataforseo.com/terms-of-use
* Privacy: https://dataforseo.com/privacy-policy

**Google Analytics 4 Data API** — `analyticsdata.googleapis.com` (token exchange via `oauth2.googleapis.com`)

* When: only if an admin uploads a Google service-account JSON.
* What is sent: a signed JWT minted from the service-account key, plus the GA4 property id and report definition (dimensions, metrics, date range, filters) chosen per call.
* Terms: https://policies.google.com/terms
* Privacy: https://policies.google.com/privacy

**Google Search Console API** — `searchconsole.googleapis.com` / `www.googleapis.com/webmasters/v3` (token exchange via `oauth2.googleapis.com`)

* When: only if an admin uploads a Google service-account JSON.
* What is sent: a signed JWT minted from the service-account key, plus the Google Search Console site URL and per-call parameters (date range, dimensions, URL to inspect, sitemap URL).
* Terms: https://policies.google.com/terms
* Privacy: https://policies.google.com/privacy

== Frequently Asked Questions ==

= What is RankOut Connector? =

RankOut Connector is a free **WordPress AI connector** that turns your site into a remote **MCP (Model Context Protocol) server**. Once activated, any MCP-compatible AI assistant or AI agent — Claude (Anthropic), ChatGPT (OpenAI), Cursor, Gemini AI, n8n, and more — can read and write content, manage media, users, and settings, and pull SEO and analytics data through 204 ready-to-use tools. No Node.js, no proxy, no extra hosting.

= Is this a WordPress MCP server? =

Yes. RankOut Connector acts as a **WordPress MCP adapter** — a full MCP server implementing the Model Context Protocol spec (v2025-11-25, with backwards compatibility for v2025-06-18 and v2025-03-26) directly inside WordPress. Your site exposes a single MCP endpoint at `/wp-json/rankout-connector/v1/mcp` that any MCP client can connect to over HTTPS.

= What is the Model Context Protocol (MCP)? =

MCP is an open standard created by Anthropic that lets AI assistants and AI agents securely connect to external tools and data sources. It's quickly becoming the universal protocol for AI-to-app communication, supported by Anthropic, OpenAI, Google, and dozens of other platforms. Learn more at [modelcontextprotocol.io](https://modelcontextprotocol.io).

= How is RankOut Connector different from other WordPress AI plugins? =

Most WordPress AI plugins embed a single AI provider (OpenAI, Claude, etc.) inside the wp-admin and bill you for usage. RankOut Connector does the opposite — it makes your WordPress site an agent-ready backend that **any** AI assistant can connect to over MCP. You bring your own AI client, you bring your own model, and the plugin focuses on giving that AI safe, scoped access to your site: 214 tools, OAuth 2.0/2.1 one-click connect, per-token permissions, and a full audit trail.

= Is RankOut Connector free? =

Yes. This **WordPress MCP** plugin is free and open source on the WordPress.org plugin directory. There are no paid tiers, no usage limits, and no telemetry. Optional external integrations (Semrush, DataForSEO, Google Analytics, Search Console) use **your own** third-party accounts — RankOut Connector never bills you for API usage.

= How do I connect Claude, ChatGPT, Cursor, Gemini, or n8n to my WordPress site? =

After activation, go to **RankOut Connector → Dashboard** and copy your MCP server URL. Then:

* **Claude Desktop / Claude.ai / Claude Code** (by Anthropic) — Settings → Connectors → Add custom connector, paste the URL, approve the OAuth consent screen. One click, no token.
* **ChatGPT (OpenAI)** — add as an MCP server using the same URL.
* **Cursor / Windsurf / Cline / Roo Code** — add MCP server in the client's settings using the URL.
* **Gemini AI** (Gemini CLI / Google Antigravity) — register the MCP endpoint in the client config.
* **n8n** — use the MCP node and point it at the URL plus a Bearer token created under **RankOut Connector → API Tokens**.

See the [integrations page](https://rankout.app/integrations) for step-by-step guides per client.

= Does it work with WooCommerce, Yoast, Rank Math, ACF, BuddyPress, and The Events Calendar? =

Yes. RankOut Connector ships with first-party WooCommerce AI tool sets: **WooCommerce** (46 tools — products, orders, customers, coupons, reports, shipping, webhooks), **Advanced Custom Fields (ACF)** (6 tools to get and update ACF fields and ACF field groups on posts, users, and terms), **The Events Calendar** (10 tools), **BuddyPress** (10 tools), **Yoast SEO**, **Rank Math**, and **All in One SEO (AIOSEO)**. Each integration only loads if the underlying plugin is active, and each tool group can be toggled individually under **RankOut Connector → Plugin Integrations**.

= Can I use RankOut Connector as an AI writing assistant for WordPress? =

Yes. Once connected, your AI acts as a writing assistant for WordPress — drafting posts, editing existing content, updating meta descriptions for SEO, and publishing — all from a single conversation. It works with Claude, ChatGPT, Gemini AI, or any other AI tool that supports MCP.

= How do I connect Semrush, DataForSEO, Google Analytics, and Google Search Console? =

Go to **RankOut Connector → External Data**. Each service has its own section:

* **Semrush** — paste your API key, click Test, then toggle the 13 keyword research and SEO tools you want enabled.
* **DataForSEO** — enter your account login + API password, click Test, then enable the 8 DFS tools including on-page SEO audits and SERP tools.
* **Google Analytics 4** — upload a Google Cloud service-account JSON, set the default GA4 property id.
* **Google Search Console** — upload a service-account JSON, set the default site URL.

All credentials are stored AES-256-GCM encrypted with per-provider HKDF-derived keys. Nothing is sent to any third party until an AI actually calls a tool that needs it.

= Does this plugin send my content to OpenAI, Anthropic, or Google? =

**No.** RankOut Connector does not call any AI provider. The flow is the opposite: your AI assistant (Claude by Anthropic, ChatGPT by OpenAI, etc.) calls **your** WordPress site, and the plugin executes whatever tool the AI requested. Your content only leaves your server in the response that goes back to the AI client you connected — never to a third party you didn't choose. Outbound connections to Semrush / DataForSEO / Google APIs only happen if you explicitly configure those credentials, and they only receive the per-call parameters (keywords, target URLs, date ranges) — not your post content.

= How does authentication work? =

Two options, both production-grade:

1. **OAuth 2.0/2.1 one-click connect** (recommended) — open your AI client, paste your MCP URL, sign in to WordPress, approve the consent screen. Done.
2. **Manual Bearer token** — create a token under **RankOut Connector → API Tokens**, paste it into your AI client.

Under the hood, every token (OAuth or Bearer) is SHA-256 hashed before being saved — the raw value is never stored and cannot be recovered after creation.

= How does OAuth 2.0/2.1 one-click connect work? =

Skip the copy-paste. In a supported client like Claude Desktop or Cursor, paste your MCP URL, sign in to WordPress, tick the permission categories (Read / Write per content type, GA4, Google Search Console, Semrush, etc.) on the consent screen, and click Approve. The client receives a short-lived access token plus a rotating refresh token, and you can revoke it anytime from the admin.

Under the hood the plugin implements the full OAuth 2.1 spec: PKCE (S256), RFC 7591 Dynamic Client Registration, refresh-token reuse detection (RFC 9700), RFC 8707 audience binding, RFC 8414 and RFC 9728 discovery endpoints, and RFC 7009 revocation. No AI client ever sees your WordPress password.

= Do I need to enable OAuth? =

No configuration required — OAuth 2.0/2.1 endpoints are live as soon as the plugin is activated. You can manage registered clients and revoke per-user grants under **RankOut Connector → API Token & OAuth → OAuth** tab. Bearer tokens continue to work alongside OAuth for power users and automation.

= Can I control what the AI is allowed to do? =

Yes, fully. Each token has its own permission set — you choose exactly which of the 214 tools it can call. Create a read-only token for a summarization AI, a content-only token for your AI writing assistant, and a full-access token for your trusted automation workflows.

= Can I limit which posts or pages the AI can edit? =

Permissions are enforced at the **WordPress capability level**, not per-post. RankOut Connector runs every tool call as the WordPress user the token is bound to, so the AI inherits exactly that user's `edit_posts` / `edit_others_posts` / `publish_posts` caps. If you want an AI restricted to, say, drafts only, create a dedicated low-privilege WordPress user (Contributor or Author) and bind the token to that user. Additionally, the **Force Draft** setting under Settings forces every create operation to draft status regardless of the AI's request.

= How do I revoke access for an AI client? =

For OAuth-connected clients, go to **RankOut Connector → API Token & OAuth → OAuth** and click Revoke next to the grant — the client immediately loses access and any active refresh tokens are invalidated. For Bearer tokens, go to **RankOut Connector → API Tokens** and delete the token. Either action is instant and irreversible.

= Where can I see a history of every AI action? =

Go to **RankOut Connector → Audit Log**. Every tool call is recorded in the user activity log with the token used, the tool name, the arguments, the result, the client IP, and a timestamp. The audit trail is paginated and searchable, and retention is configurable under Settings (default 30 days, after which old rows are auto-purged).

= Will the AI publish posts automatically? =

Only if you let it. By default, the AI can create posts in whatever status it asks for (draft, publish, etc.) — but you can flip the **Force Draft on Create** setting under **RankOut Connector → Settings** and every newly created post or page will be forced to `draft` regardless of what the AI requested. Combine that with a Contributor-level WordPress user for the AI to require human review before anything goes live.

= Is it safe to run on a live site? =

Yes — RankOut Connector is built for production. Every request is authenticated (OAuth 2.0/2.1 or Bearer), capability-checked against WordPress core permissions, rate-limited (default 60 req/min per token, configurable), and recorded in the audit trail. You can additionally restrict the endpoint to specific IP addresses, force all created content to draft, disable specific tools globally, and bind tokens to low-privilege WordPress users. The plugin only requires HTTPS for OAuth flows — bearer-token access is allowed over HTTP for local development but should never be exposed that way on a live site.

= Does it work with WordPress multisite? =

Yes. RankOut Connector runs per-site on a multisite network — each subsite has its own MCP endpoint, its own tokens, and its own audit log. Network-scoped operations (network options, sitewide plugin/theme activation) are additionally gated on Super Admin + `manage_network_options` / `manage_network_plugins` capabilities, so a per-site admin token cannot reach network-level state.

= Can I use this on localhost or a staging site? =

Yes. On loopback addresses (`127.0.0.1`, `::1`) the OAuth HTTPS requirement is automatically relaxed so you can test against `http://localhost`. For non-loopback dev setups behind a reverse proxy that terminates TLS elsewhere, add `define('RANKOUT_CONNECTOR_OAUTH_ALLOW_HTTP', true);` to `wp-config.php`. **Never set that flag on a production site.** Bearer-token access works over HTTP without any flag, but again, only for dev.

= Does it work with custom post types and Gutenberg blocks? =

Yes to both. The post and page tools accept a `post_type` parameter so your AI can work with any registered CPT on your site (`wp_list_cpt_items`, `wp_create_cpt_item`, etc.). For Gutenberg, there are dedicated tools for AI blocks and reusable blocks (`wp_list_blocks`, `wp_create_block`, `wp_update_block`) and block templates (`wp_list_templates`, `wp_get_template`, `wp_update_template`), plus full global styles support (`wp_get_global_styles`, `wp_update_global_styles`).

= Can I connect multiple AI assistants at once? =

Yes. Create one token (or one OAuth grant) per assistant. Each tracks its own usage, has its own scoped permissions, and is logged independently in the user activity log — so you can see exactly which AI did what.

= What WordPress and PHP versions are required? =

WordPress 6.0+ and PHP 7.4+. PHP 8.0 or higher is recommended. WordPress 6.9+ unlocks the Abilities API auto-discovery feature, which exposes any Abilities-compatible plugin as MCP tools with no extra code.

= Does this require Node.js or a special server? =

No long-running processes, no Node.js, no Docker. The plugin runs entirely inside WordPress as a normal PHP plugin. The plugin contacts external services (Semrush, DataForSEO, Google Analytics 4, Google Search Console) only if you explicitly add those third-party account credentials under **RankOut Connector → External Data** — see the External services section above. Out of the box, nothing leaves your server.

= Why does the endpoint return 404 or 401 Unauthorized? =

* **404 Not Found** — go to **Settings → Permalinks** in WordPress admin and click **Save Changes** to flush rewrite rules. Pretty permalinks must be enabled.
* **401 Unauthorized** — double-check the Bearer token in your AI client matches one shown under **RankOut Connector → API Tokens** (tokens are only shown once at creation — if you lost it, delete and recreate). For OAuth clients, try disconnecting and re-approving the connector. Also confirm your `Authorization: Bearer <token>` header is being sent (some reverse proxies strip it).

= Where do I report security bugs found in this plugin? =

Please report security bugs found in the source code of the RankOut Connector for WordPress plugin through the [Patchstack Vulnerability Disclosure Program](https://patchstack.com/database/vdp/8e5e1a2e-1cd4-42d7-8a5d-9ff3d1a7f397). The Patchstack team will assist you with verification, CVE assignment, and notify the developers of this plugin.

== Screenshots ==

1. Dashboard — your MCP endpoint URL and quick-start configs for every major AI client
2. API Tokens & OAuth — token list with one-time token display and quick-connect guide
3. Abilities Browser — expose WordPress 6.9+ abilities as MCP tools with a single click
4. Settings — rate limits, IP whitelist, force draft, audit retention, and disabled tools
5. Plugin Integrations — enable MCP tool groups for WooCommerce, ACF, Yoast, Rank Math, and more
6. External Data — connect Google Search Console, Google Analytics 4, Semrush, and DataForSEO with encrypted credentials

== Changelog ==

= 2.0.0 =
* **Phase 3 — AEO (Answer Engine Optimisation):** 3 new tools — `wp_get_faq_blocks` (extract all FAQ/Q&A blocks from a post), `wp_create_faq_block` (append a FAQPage-schema-ready block + JSON-LD in one call), `wp_audit_answer_readiness` (score posts on featured-snippet signals: direct opening answer, question headings, FAQ content, bullet lists)
* **Phase 4 — E-E-A-T / HEO (Human Experience Optimisation):** 4 new tools — `wp_get_eeat_signals` (full per-post E-E-A-T audit with scores across Experience, Expertise, Authoritativeness, Trustworthiness), `wp_get_content_freshness` (list stale posts not updated within N days), `wp_get_internal_links` (list all internal links in a post with resolved titles/IDs), `wp_suggest_internal_links` (find related posts to link to or from, scored by topic overlap)
* **Phase 5 — Reporting & Aggregation:** 2 new tools — `wp_seo_audit_site` (master site-wide audit: schema coverage %, stale count, E-E-A-T distribution, AEO/GEO readiness, top issues, prioritised fix list), `wp_content_gap_report` (compare target topics against existing posts to surface uncovered areas)
* Total tool count: **233**
* No breaking changes. New tool directories `aeo/`, `eeat/`, `reporting/` are loaded automatically.

= 1.9.2 =
* Added GitHub auto-updater — "Check for Updates" link on the Plugins page; one-click update from GitHub releases
* Added wp-content filesystem tools: `wp_list_wp_content`, `wp_get_wp_content_file` — read any file under wp-content/

= 1.9.1 =
* Added wp-content filesystem tools — list and read files in any directory under wp-content/

= 1.9.0 =
* **Phase 2 — GEO (Generative Engine Optimisation):** 4 new tools — `wp_get_llms_txt`, `wp_update_llms_txt`, `wp_get_entity_context`, `wp_audit_geo_readiness`

= 1.8.0 =
* **Phase 1 — Schema / Structured Data:** 4 new tools — `wp_get_post_schema`, `wp_update_post_schema`, `wp_audit_schema_coverage`, `wp_list_schema_types`; JSON-LD auto-output in `<head>` via `_rankout_connector_schema` post meta

= 1.7.2 =
* Fixed: AI connections that dropped when an access token expired now reconnect on their own instead of silently failing — your AI client refreshes its login and keeps working.
* Fixed: posts and pages created or edited by AI no longer corrupt Gutenberg blocks that contain special characters (such as `&` in block settings), which previously caused the editor's "this block contains unexpected content" recovery prompt.
* Improved: added no-cache headers to MCP responses so a CDN or server cache can't serve a stale "not authorized" response to a valid request.

= 1.7.1 =
* Fixed: enabling a plugin-provided ability on the **Abilities** page now sticks — previously, after saving, every ability except the built-in core ones could vanish from the list and fail to turn into a tool. They now stay enabled and become usable AI tools as expected.
* Fixed: the OAuth consent screen now lists abilities from all your plugins, not just the built-in core ones — so you can grant an AI client access to a specific plugin's abilities when connecting.

= 1.7.0 =
* New **Change History** page — see every change your AI made to posts, media, users, comments, WooCommerce, and more, with before/after snapshots and one-click diff
* Ask your AI "what did you change last week?" — 3 new tools (`wp_history_list`, `wp_history_get`, `wp_history_diff`) let any AI client query its own change history
* 7 new tools: full post fetch (post + meta + terms in one call), find-and-replace inside post content, upload media from a URL, and create/get/update/delete terms on any custom taxonomy
* Security and OAuth hardening across the board
* Tool count is now 214

= 1.6.9 =
* Fixed Plugin Integrations admin tab showing 37/37 WooCommerce tools instead of the full 46 — added the 9 missing entries (single-variation get/update/delete, product attribute list/create/set, and batch update for products, variations, and orders)
* Tested up to WordPress 7.0

= 1.6.8 =
* Added 21 new tools: count posts, count terms, count media, restore revision, add post terms, delete post meta, get/update/delete term meta, get/update/delete user meta, batch update WooCommerce products/orders/variations, list/create/set product attributes, and get/update/delete single product variation
* Total tool count is now 204

= 1.6.5 =
* Added 13 Semrush Analytics API tools (`wp_semrush_*`): domain overview, organic keywords, organic competitors, keyword overview, related keywords, keyword difficulty, phrase questions, backlinks overview/list/referring-domains/anchors, URL organic keywords, and a free API-units balance check
* Extended **RankOut Connector → External Data** with a fourth Semrush section — paste an API key, test the connection, and toggle individual tools
* Added new OAuth scope `mcp:semrush:read` covering all 13 Semrush tools, with consent-screen entry gated on a saved API key
* Subdomain and Subfolder reports are deferred to a future release pending docs verification

= 1.6.1 =
* Improved translation quality across 50 languages for a more natural, accurate admin experience
* Fixed tool definitions that were causing errors when using RankOut Connector with ChatGPT

= 1.6.0 =
* Ask your AI about SEO data from DataforSEO — live SERP results, keyword search volumes, backlinks, on-page issues, and ranked keywords for any domain
* Ask "what keywords does example.com rank for?" or "what are the top backlinks to this page?" and get real data back
* Ask your AI to audit any URL for on-page SEO issues and get a list of what to fix
* Ask for live search results for any keyword in any country — useful for competitor research and content planning

= 1.5.0 =
* Ask your AI about your Google Search Console data — top queries, clicks, impressions, sitemaps, and URL indexing status
* Ask your AI about your Google Analytics 4 data — traffic, top pages, conversions, realtime active users, and more
* New **External Data** page under RankOut Connector to connect your Google service account once and enable/disable individual tools
* Your Google credentials stay encrypted on your server and never leave WordPress
* New OAuth scopes for fine-grained access: `mcp:ga:read` (Google Analytics tools) and `mcp:gsc:read` (Search Console tools)

= 1.4.0 =
* One-click connection for Claude Desktop, Cursor, and other MCP clients — no more manually creating and copy-pasting tokens
* New consent screen: pick exactly what each AI is allowed to read and write, per content type
* New OAuth Clients admin page — see every connected AI, revoke access anytime, adjust permissions per client
* Updated to the latest MCP protocol (2025-11-25), still compatible with older clients
* Hardened security across the new connection flow
* Under the hood: OAuth 2.1 with PKCE S256, RFC 7591 DCR, RFC 8707 audience binding, RFC 7009 token revocation endpoint (/oauth/revoke), RFC 9728 protected-resource metadata discovery, MCP spec 2025-11-25

= 1.3.2 =
* Fixed per-post permission check on Yoast SEO and Rank Math SEO update tools — Author-level tokens can no longer overwrite SEO metadata on posts they do not own
* Removed phantom wp_bp_send_message entry from Plugin Integration Registry that had no backing implementation

= 1.3.1 =
* Fixed translation quality issues across 7 locales (Bulgarian, French, Indonesian, Italian, Slovak, Serbian, Urdu) identified in comprehensive audit of all 50 translation files
* Fixed Recent Posts resource count capping and sort/total calculation
* Fixed Scheduled Posts resource total count and post filtering logic

= 1.3.0 =
* Added WooCommerce integration — 37 tools covering products, variations, product categories, orders, order notes, order refunds (read-only), customers, coupons, webhooks, shipping zones, shipping methods, tax rates, payment gateways, and sales reports
* Added Advanced Custom Fields (ACF / Secure Custom Fields) integration — 6 tools to get and update custom fields on posts, users, and terms; list field groups
* Added The Events Calendar integration — 10 tools to create and manage events, venues, and organizers
* Added BuddyPress integration — 10 tools covering members, activity stream, groups, group members, and private message threads
* Added SEO integration — 8 tools spanning Yoast SEO, Rank Math, and All in One SEO (AIOSEO); get and update post SEO metadata and rendered SEO head output
* Added Plugin Integrations admin page — enable or disable each plugin group individually with collapsible cards, per-group tool lists, and type filter (read-only / destructive)
* Plugin tool groups are opt-in; tools for inactive or disabled plugins are automatically excluded from the tool list
* Security hardening: Bearer token authentication updated to match MCP spec requirements

= 1.2.0 =
* Admin interface now available in 50+ languages with a searchable language selector
* Added direct links to AI client settings pages from the Dashboard quick-start guides
* Delete page tool now returns the page title in the response
* Security and reliability improvements

= 1.1.1 =
* 26 new tools across 7 new categories: Custom Post Types, Post Meta, Revisions, Blocks, Styles, Templates, and Search
* 11 new MCP Resources — your AI can now read site info, stats, and recent content as structured data
* Tool count increased from 48 to 74
* Fixed plugin activation/deactivation failing due to URL-encoded plugin slugs
* Fixed tool whitelist bug that blocked all tools when no wildcard patterns were set
* Renamed REST endpoint from `wp-mcp/v1` to `rankout-connector/v1`
* Various security and code quality improvements

= 1.0.0 =
* Initial release
* 48 MCP tools covering all core WordPress REST APIs (now 74 in v1.1.1)
* Bearer token authentication with SHA-256 hashing
* Per-token tool permissions with admin checkbox UI
* WordPress capability enforcement on every tool call
* Rate limiting per token
* Full audit logging with configurable retention
* IP whitelisting
* Quick-start connection guides for Manus, Claude, ChatGPT, Cursor, n8n, and more
* MCP spec 2025-03-26, Streamable HTTP transport, JSON-RPC 2.0
* Fully internationalized (i18n ready)

== Upgrade Notice ==

= 2.0.0 =
No breaking changes. Adds 10 new tools across AEO, E-E-A-T/HEO, and Reporting categories. All new tools are loaded automatically — no configuration required.

= 1.7.2 =
Bug-fix release. AI connections now recover automatically when a login token expires, and AI-edited Gutenberg blocks with special characters are no longer corrupted. No breaking changes.

= 1.7.1 =
Bug-fix release. Plugin-provided abilities now save and activate correctly on the Abilities page. No breaking changes.

= 1.7.0 =
No breaking changes. A `change_log` table is added; Change History recording is OFF by default. Enable it under RankOut Connector → Settings (90-day retention). Only MCP tool writes are recorded — admin-UI and cron edits are not.

= 1.6.0 =
No breaking changes. DataforSEO tools are inactive until you add your API credentials under RankOut Connector → External Data.

= 1.3.0 =
No breaking changes. WooCommerce, ACF, The Events Calendar, BuddyPress, and SEO plugin tools are opt-in — enable them from RankOut Connector → Plugin Integrations.

= 1.1.1 =
The MCP endpoint has moved from `wp-mcp/v1` to `rankout-connector/v1`. Update your AI client connection URLs after upgrading.

= 1.0.0 =
Initial release. No upgrade steps required.

== Author ==

Developed by [RankOut](https://rankout.app).
