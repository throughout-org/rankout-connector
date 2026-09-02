# RankOut Connector – Claude, ChatGPT & SEO Data Connector

> **Connect Claude, ChatGPT & any AI to WordPress. Manage your entire site by chat — content, media, GA4, Search Console, SEO, GEO, AEO, E-E-A-T & more. 233 tools. Free.**

[![Version](https://img.shields.io/badge/version-2.0.0-blue)](https://github.com/throughout-org/rankout-connector/releases)
[![WordPress](https://img.shields.io/badge/WordPress-6.0%2B-21759b)](https://wordpress.org)
[![PHP](https://img.shields.io/badge/PHP-7.4%2B-8892bf)](https://php.net)
[![License](https://img.shields.io/badge/license-GPL--2.0%2B-green)](https://www.gnu.org/licenses/gpl-2.0.html)
[![Tools](https://img.shields.io/badge/tools-233-orange)](https://rankout.app/tools)

**Links:** [Website](https://rankout.app) · [WordPress.org Plugin](https://wordpress.org/plugins/rankout-connector/) · [Integration Guides](https://rankout.app/integrations) · [Developer Docs](DEVELOPER.md) · [Changelog](CHANGELOG.md)

---

## What is RankOut Connector?

[**RankOut Connector**](https://rankout.app/) is the most complete **free WordPress MCP server** — a remote MCP server built so AI assistants and autonomous AI agents can run your entire site workflow, from content and publishing to SEO research, traffic monitoring, and daily admin, through the [Model Context Protocol](https://modelcontextprotocol.io). It works as an MCP adapter for any MCP-compatible AI client, making your site agent-ready out of the box.

No Node.js. No external proxy. No complicated setup. Just install, generate a token, and start building.

**At a glance:**

- **233 tools** across posts, pages, media, users, comments, menus, Google Analytics 4, Google Search Console, Semrush, DataforSEO, SEO/GEO/AEO/E-E-A-T, filesystem, database, and more
- **1-click OAuth 2.0/2.1** with per-scope consent (Claude Desktop, Cursor, etc.)
- **Plugin integrations** — WooCommerce, ACF, The Events Calendar, BuddyPress, Yoast, Rank Math, AIOSEO
- **Google Analytics 4 & Google Search Console** — ask your AI about traffic, top pages, conversions, search queries, clicks, impressions, and indexing status
- **Semrush** — keyword research, domain overviews, organic keywords, competitor research, keyword difficulty, and backlink analytics
- **DataforSEO** — live SERP results, keyword search volumes, on-page SEO audits, backlink data, and ranked/site keywords
- **Auto-discovers WordPress 6.9+ Abilities API**
- **Full audit trail** — every AI action on your site is logged in a searchable user activity log
- **Change History** — every MCP-originated write is recorded with before/after snapshots, queryable via `wp_history_*` tools

---

## Works With Every Major AI

Connect any of the following AI assistants or AI agents to your site through the **WordPress MCP** endpoint — [full integration guides here](https://rankout.app/integrations):

| AI Client | Connection Method |
|---|---|
| **Claude** (Claude.ai, Claude Desktop, Claude Code) | One-click OAuth — no token needed |
| **ChatGPT** (OpenAI) | MCP server URL + Bearer token |
| **Gemini AI** (Gemini CLI / Google Antigravity) | MCP endpoint in client config |
| **Cursor, Windsurf, Cline, Roo Code** | MCP server settings in the editor |
| **Manus** | Autonomous agent with full multi-step workflow support |
| **n8n** | MCP node with Bearer token |
| **Any MCP-compatible client** | Open protocol, growing ecosystem |

---

## What Can Your AI Do On Your Site?

Once connected, your AI agent can handle everything you'd normally do in the WordPress admin:

**AI Content Writing & Publishing** — draft, rewrite, SEO-optimize, schedule, and publish WordPress posts and pages; update existing content

**AI Media Library & Alt Text** — upload images from chat, browse the media library, auto-generate AI alt text and captions

**Taxonomy & Navigation** — manage categories, tags, term meta, and WordPress navigation menus; assign terms from any taxonomy

**User Management** — create WordPress user accounts, assign roles, update profiles, and manage user meta

**Plugins & Themes** — list installed plugins and themes; see which theme is currently active

**WordPress Settings** — read and update site title, tagline, timezone, date format, time format, and posts-per-page

**WooCommerce AI Agent** — manage products, variations, attributes, orders, customers, coupons, and webhooks; pull sales and revenue reports; bulk update products, variations, and orders

**SEO with Yoast, Rank Math & AIOSEO** — read and update post SEO titles, meta descriptions, Open Graph/Twitter card fields, focus keywords, canonical URLs, and no-index flags

**Advanced Custom Fields (ACF)** — read and write ACF custom field values on posts, users, and terms; list ACF field groups

**Events Calendar & BuddyPress** — create, edit, and delete events; manage venues and organizers; list BuddyPress members, groups, and private message threads

**Comment Moderation** — list, approve, hold, mark as spam, edit, or delete WordPress comments

**Change History & Rollback Awareness** — every write is recorded with before/after snapshots; ask "what did the AI change last week?", diff any two revisions, or audit per-user activity

**Gutenberg & Full Site Editing** — create, edit, and reuse Gutenberg blocks; update block templates and global styles for FSE themes

**Custom Post Types (CPT)** — read and write any registered CPT — portfolios, listings, courses, reviews, anything

**AEO (Answer Engine Optimisation)** — extract and create FAQ blocks, audit posts for featured-snippet eligibility, score opening paragraphs and headings for "People also ask" capture

**E-E-A-T / HEO (Human Experience Optimisation)** — full per-post E-E-A-T audit; find stale content; get all internal links; discover internal linking opportunities between related posts

**Site-wide SEO Reporting** — master site audit (schema coverage %, stale content, E-E-A-T score distribution, AEO/GEO readiness); content gap report to compare target topics against existing coverage

**wp-content Filesystem** — read any theme file, plugin file, or file anywhere under `wp-content/` to review source code, debug templates, or audit plugin configs

**Raw SQL (read-only)** — run SELECT queries with `{prefix}` substitution; write/destructive operations are fully blocked

**Any Plugin** — automatically connects to plugins that support WordPress 6.9+ Abilities API, no custom code needed

### Example prompts

> "Write a 500-word blog post about healthy eating and publish it as a draft"  
> "Show me today's WooCommerce orders and their total revenue"  
> "What are the top 10 search queries bringing traffic to my site this month?"  
> "Update all product prices in the Summer Sale category by -20%"  
> "What keywords does my homepage rank for and what are the click counts?"  
> "Audit my last 50 posts for E-E-A-T signals and show me the lowest scoring ones"  
> "Find all posts I haven't updated in over a year"

---

## Tools

### [233 Tools, Ready to Use](https://rankout.app/tools)

#### Core WordPress (93 tools)

| Category | Tools |
|---|---|
| **Posts** | list, get, get full, create, update, delete, search, count, find-and-replace in content, add terms |
| **Pages** | list, get, create, update, delete |
| **Media** | list, get, upload, upload from URL, update, delete, count |
| **Categories** | list, get, create, update, delete, count |
| **Tags** | list, get, create, update, delete, count |
| **Taxonomy Terms** | generic create, get, update, delete for any registered taxonomy |
| **Comments** | list, get, create, update, delete |
| **Users** | list, get, create, update, delete |
| **Menus** | list, get, create, update, delete menus; list, create, update, delete menu items |
| **Custom Post Types** | list, get, create, update, delete CPT items |
| **Post / Term / User Meta** | get, update, delete meta on posts, terms, and users |
| **Revisions** | list, get, delete, restore |
| **Blocks** | list, get, create, update, delete AI blocks and reusable blocks |
| **Templates** | list, get, update block templates |
| **Styles** | get and update global styles |
| **Site** | get and update settings, list post types, taxonomies, post statuses |
| **Plugins / Themes** | list plugins, list themes, get active theme |
| **Search** | search across all content |
| **Change History** | list, get, and diff every MCP-originated write |

#### SEO / GEO / AEO / E-E-A-T (18 tools)

| Phase | Tools |
|---|---|
| **Phase 1 — Schema** | `wp_get_post_schema`, `wp_update_post_schema`, `wp_audit_schema_coverage`, `wp_list_schema_types` |
| **Phase 2 — GEO** | `wp_get_llms_txt`, `wp_update_llms_txt`, `wp_get_entity_context`, `wp_audit_geo_readiness` |
| **Phase 3 — AEO** | `wp_get_faq_blocks`, `wp_create_faq_block`, `wp_audit_answer_readiness` |
| **Phase 4 — E-E-A-T** | `wp_get_eeat_signals`, `wp_get_content_freshness`, `wp_get_internal_links`, `wp_suggest_internal_links` |
| **Phase 5 — Reporting** | `wp_seo_audit_site`, `wp_content_gap_report` |

#### 3 Change History Tools

- **`wp_history_list`** — query change records by user, object type, object ID, tool name, or date range; supports `since`/`until` filters and pagination
- **`wp_history_get`** — fetch a single change record with full before/after JSON snapshots
- **`wp_history_diff`** — compute a structured diff between any recorded snapshot and either another snapshot or the current live state

#### 11 Google Analytics 4 Tools

- **Account & Property** — list account summaries, get property details, check compatibility, get metadata
- **Reports** — run standard reports, pivot reports, and realtime reports
- **Configuration** — list data streams, conversion events, custom dimensions, and custom metrics

#### 6 Google Search Console Tools

- **Sites** — list verified properties
- **Search Analytics** — query top search terms, pages, countries, devices with clicks, impressions, CTR, and position
- **Sitemaps** — list and inspect submitted sitemaps
- **URL Inspection** — check indexing status and coverage for any URL

#### 13 Semrush Tools

- **Domain** — domain overview and organic competitor research
- **Keywords** — domain organic keywords, URL organic keywords, keyword overview, related keywords, keyword difficulty, phrase questions
- **Backlinks** — backlinks overview, backlinks list, referring domains, anchors
- **Account** — check Semrush API units balance

#### 8 DataforSEO Tools

- **SERP** — fetch live search engine results pages for any keyword and location
- **Keywords** — look up monthly search volume and trend data
- **Labs** — get ranked keywords for any domain, or find keywords a specific page ranks for
- **Backlinks** — backlink summary and referring domains for any target URL
- **On-Page** — full on-page SEO audit on any URL
- **Account** — check DataforSEO API account balance

#### 46 WooCommerce MCP Tools

| Area | Coverage |
|---|---|
| Products | list, get, create, update, delete, batch update |
| Variations | list, get, create, update, delete, batch update |
| Attributes | list, create, set product attributes |
| Orders | list, get, create, update; order notes and refunds |
| Customers | list, get, create, update, delete |
| Coupons | list, get, create, update, delete |
| Webhooks | list, get, create, update, delete |
| Shipping | list shipping zones and methods |
| Tax | list tax rates |
| Payment | list payment gateways |
| Reports | sales, orders, products, top sellers, customers |

#### 7 Plugin Integrations

| Plugin | Tools |
|---|---|
| **WooCommerce** | 46 tools |
| **Advanced Custom Fields (ACF)** | 6 tools — get/update ACF fields on posts, users, terms; list field groups |
| **The Events Calendar** | 10 tools — events, venues, organizers |
| **BuddyPress** | 10 tools — members, activity, groups, messages |
| **Yoast SEO** | get/update post SEO metadata and SEO head output |
| **Rank Math** | get/update post SEO metadata and SEO head output |
| **All in One SEO (AIOSEO)** | get/update post SEO metadata |

#### Connect Any Plugin with Abilities API

WordPress 6.9+ introduces the **Abilities API** — a standard way for plugins to declare what they can do. RankOut Connector acts as an **MCP adapter** for any plugin that registers Abilities — automatically discovering and exposing them as MCP tools with no custom code needed.

---

## One-Click Connect with OAuth 2.0/2.1

Skip manual token copy-paste. Your **WordPress MCP** endpoint ships with a full **OAuth 2.0/2.1** authorization server — PKCE, refresh-token rotation, and Dynamic Client Registration (RFC 7591) built in. Compatible MCP clients like Claude Desktop can connect with a single click: they register themselves, you approve the scopes on a consent screen, and you're done. Bearer tokens still work for power users and automation.

---

## Built for Security

Giving an AI access to your site is serious — so security is built into every layer:

- **Bearer token authentication** with SHA-256 hashing — the raw token is never stored
- **Per-token permissions** — create a read-only token for one AI, a full-access token for another
- **WordPress capability checks** on every single tool call
- **Rate limiting** per token (default 60 requests/min, configurable)
- **Full audit trail** — every tool call is logged with token used, arguments, result, and client IP
- **IP whitelisting** — optionally restrict which IPs can use the MCP endpoint

---

## Simple Admin Interface

- **Dashboard** — your MCP endpoint URL and one-click connection configs for every major AI client
- **API Tokens** — create and manage tokens with a checkbox-based tool permission tree
- **Audit Log** — a paginated, searchable log of every AI action taken on your site
- **Change History** — a dedicated page with before/after snapshots of every MCP-originated write, inline diff expand, and user / object / date filtering
- **Settings** — tune rate limits, audit and change-history retention, IP whitelist, and more

---

## Installation

### Automatic (recommended)

1. In your WordPress admin, go to **Plugins → Add New Plugin**
2. Search for **"RankOut Connector"**
3. Click **Install Now** and then **Activate**

### Manual

1. Download the plugin ZIP from the WordPress plugin directory
2. Go to **Plugins → Add New Plugin → Upload Plugin**
3. Upload the ZIP, click **Install Now**, then **Activate**

---

## Quick Start — Connect Your AI

### Path A — One-Click OAuth (recommended)

1. Go to **RankOut Connector → Dashboard** and copy your MCP server URL
2. In your AI client (e.g. Claude Desktop → Settings → Connectors → Add custom connector), paste the URL — no token needed
3. Your browser opens a WordPress login + consent screen. Sign in as the user the AI should act as
4. Tick the permission categories you want to grant, then **Approve**
5. Done. Start talking to your site
6. Manage or revoke connected clients anytime under **RankOut Connector → API Token & OAuth → OAuth** tab

### Path B — Manual Bearer Token

1. Go to **RankOut Connector → API Tokens** and click **Create New Token**
2. Give the token a name, choose the WordPress user the AI will act as, and select which tools to allow
3. Click **Create Token** and copy the token — it is only shown once
4. In your AI assistant, paste in the endpoint URL and token from the Dashboard page
5. Start talking to your site

### Claude Code (claude.ai/code or CLI)

Add to your `~/.claude/settings.json`:

```json
{
  "mcpServers": {
    "my-wordpress": {
      "type": "http",
      "url": "https://yourdomain.com/wp-json/rankout-connector/v1/mcp",
      "headers": {
        "Authorization": "Bearer YOUR_TOKEN_HERE"
      }
    }
  }
}
```

---

## External Services

This plugin connects to the following third-party services **only when a site administrator explicitly configures their own external account credentials** in **RankOut Connector → External Data**. Nothing is contacted on a default install.

| Service | Endpoint | When Used |
|---|---|---|
| **Semrush API** | `api.semrush.com` | Only if an admin saves a Semrush API key |
| **DataForSEO** | `api.dataforseo.com` | Only if an admin saves a DataForSEO login + API password |
| **Google Analytics 4** | `analyticsdata.googleapis.com` | Only if an admin uploads a Google service-account JSON |
| **Google Search Console** | `searchconsole.googleapis.com` | Only if an admin uploads a Google service-account JSON |

All credentials are stored AES-256-GCM encrypted with per-provider HKDF-derived keys. Nothing is sent to any third party until an AI actually calls a tool that needs it.

---

## Frequently Asked Questions

**What is RankOut Connector?**  
RankOut Connector is a free WordPress AI connector that turns your site into a remote MCP server. Once activated, any MCP-compatible AI assistant — Claude, ChatGPT, Cursor, Gemini AI, n8n — can read and write content, manage media, users, and settings, and pull SEO and analytics data through 233 ready-to-use tools. No Node.js, no proxy, no extra hosting.

**Is this a WordPress MCP server?**  
Yes. RankOut Connector implements the Model Context Protocol spec (v2025-11-25, with backwards compatibility for v2025-06-18 and v2025-03-26) directly inside WordPress. Your site exposes a single MCP endpoint at `/wp-json/rankout-connector/v1/mcp`.

**What is the Model Context Protocol (MCP)?**  
MCP is an open standard created by Anthropic that lets AI assistants securely connect to external tools and data sources. It's the universal protocol for AI-to-app communication, supported by Anthropic, OpenAI, Google, and dozens of other platforms. Learn more at [modelcontextprotocol.io](https://modelcontextprotocol.io).

**Does this plugin send my content to OpenAI, Anthropic, or Google?**  
No. RankOut Connector does not call any AI provider. The flow is the opposite: your AI assistant calls your WordPress site, and the plugin executes whatever tool the AI requested. Your content only leaves your server in the response that goes back to the AI client you connected.

**Is RankOut Connector free?**  
Yes. This WordPress MCP plugin is free and open source on the WordPress.org plugin directory. There are no paid tiers, no usage limits, and no telemetry. Optional external integrations (Semrush, DataForSEO, Google Analytics, Search Console) use your own third-party accounts.

**How does authentication work?**  
Two options:
1. **OAuth 2.0/2.1 one-click connect** (recommended) — open your AI client, paste your MCP URL, sign in to WordPress, approve the consent screen
2. **Manual Bearer token** — create a token under RankOut Connector → API Tokens, paste it into your AI client

Every token (OAuth or Bearer) is SHA-256 hashed before being saved — the raw value is never stored.

**Can I control what the AI is allowed to do?**  
Yes, fully. Each token has its own permission set — you choose exactly which tools it can call. Permissions are also enforced at the WordPress capability level, so the AI inherits exactly the capabilities of the WordPress user the token is bound to.

**Will the AI publish posts automatically?**  
Only if you let it. Enable **Force Draft on Create** under RankOut Connector → Settings and every newly created post or page will be forced to `draft` regardless of what the AI requested.

**Does it work with WordPress multisite?**  
Yes. RankOut Connector runs per-site on a multisite network — each subsite has its own MCP endpoint, its own tokens, and its own audit log.

**Does it work on localhost?**  
Yes. On loopback addresses (`127.0.0.1`, `::1`) the OAuth HTTPS requirement is automatically relaxed so you can test against `http://localhost`.

**Why does the endpoint return 404?**  
Go to **Settings → Permalinks** and click **Save Changes** to flush rewrite rules. Pretty permalinks must be enabled.

**Where do I report security bugs?**  
Please report security bugs through the [Patchstack Vulnerability Disclosure Program](https://patchstack.com/database/vdp/8e5e1a2e-1cd4-42d7-8a5d-9ff3d1a7f397).

---

## Changelog

### 2.0.0
- **Phase 3 — AEO:** `wp_get_faq_blocks`, `wp_create_faq_block`, `wp_audit_answer_readiness`
- **Phase 4 — E-E-A-T:** `wp_get_eeat_signals`, `wp_get_content_freshness`, `wp_get_internal_links`, `wp_suggest_internal_links`
- **Phase 5 — Reporting:** `wp_seo_audit_site`, `wp_content_gap_report`
- Total tool count: **233**

### 1.9.2
- GitHub auto-updater with "Check for Updates" button
- `wp_list_wp_content`, `wp_get_wp_content_file` — read files anywhere under wp-content/

### 1.9.0
- **Phase 2 — GEO:** `wp_get_llms_txt`, `wp_update_llms_txt`, `wp_get_entity_context`, `wp_audit_geo_readiness`

### 1.8.0
- **Phase 1 — Schema:** `wp_get_post_schema`, `wp_update_post_schema`, `wp_audit_schema_coverage`, `wp_list_schema_types`; JSON-LD auto-output in `<head>`

### 1.7.0
- Change History page with before/after snapshots and diffs
- `wp_history_list`, `wp_history_get`, `wp_history_diff`
- `wp_get_post_full`, `wp_replace_in_post`, `wp_upload_media_from_url`, generic taxonomy term tools

### 1.6.5
- 13 Semrush Analytics API tools

### 1.6.0
- 8 DataforSEO tools (SERP, keywords, backlinks, on-page audits, labs)

### 1.5.0
- 11 Google Analytics 4 tools
- 6 Google Search Console tools
- External Data admin page

### 1.4.0
- OAuth 2.0/2.1 one-click connect with PKCE, DCR, and refresh-token rotation

### 1.3.0
- WooCommerce (46 tools), ACF (6), The Events Calendar (10), BuddyPress (10), Yoast/Rank Math/AIOSEO SEO tools

### 1.0.0
- Initial release: 48 MCP tools, Bearer token auth, rate limiting, audit log, IP whitelist

→ Full history in [CHANGELOG.md](CHANGELOG.md)

---

## Developer Documentation

See [DEVELOPER.md](DEVELOPER.md) for:
- Architecture overview and request flow diagram
- How to write and register a custom tool
- Full tool list with categories
- Authentication deep-dive (Bearer + OAuth 2.0/2.1)
- SEO/GEO/AEO/E-E-A-T tool suite explanation
- Filesystem and database tool security model
- GitHub auto-updater internals
- Hooks and filters reference
- Step-by-step connection guides for every major AI client
- Troubleshooting guide

---

## License

GPL-2.0-or-later — see [LICENSE](https://www.gnu.org/licenses/gpl-2.0.html)

---

Developed by [RankOut](https://rankout.app) · [WordPress.org](https://wordpress.org/plugins/rankout-connector/) · [GitHub](https://github.com/throughout-org/rankout-connector)
