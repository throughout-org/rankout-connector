# RankOut Connector — Developer Documentation

> **Version:** 2.0.0 | **PHP:** 7.4+ | **WordPress:** 6.0+  
> **Repo:** [throughout-org/rankout-connector](https://github.com/throughout-org/rankout-connector)  
> **Website:** [rankout.app](https://rankout.app)

---

## Table of Contents

1. [What is RankOut Connector?](#1-what-is-rankout-connector)
2. [How It Works — Architecture Overview](#2-how-it-works--architecture-overview)
3. [MCP Protocol Primer](#3-mcp-protocol-primer)
4. [Plugin Structure](#4-plugin-structure)
5. [Tool System — Writing a Custom Tool](#5-tool-system--writing-a-custom-tool)
6. [Tool Categories & Full Tool List](#6-tool-categories--full-tool-list)
7. [Authentication — Bearer Tokens & OAuth 2.0/2.1](#7-authentication--bearer-tokens--oauth-2021)
8. [SEO/GEO/AEO/E-E-A-T Tool Suite](#8-seogeoaeoee-at-tool-suite)
9. [Filesystem & Database Tools](#9-filesystem--database-tools)
10. [Auto-Updater (GitHub Releases)](#10-auto-updater-github-releases)
11. [Security Model](#11-security-model)
12. [Hooks & Filters Reference](#12-hooks--filters-reference)
13. [Step-by-Step: Connect Your AI](#13-step-by-step-connect-your-ai)
14. [Troubleshooting](#14-troubleshooting)

---

## 1. What is RankOut Connector?

RankOut Connector turns your WordPress site into a **remote MCP (Model Context Protocol) server**. Once activated, any MCP-compatible AI assistant — Claude, ChatGPT, Cursor, Gemini, n8n — can read and write your WordPress content, manage media, pull SEO and analytics data, audit your site's structured data and E-E-A-T signals, and more, through **233 ready-made tools**.

**Key points:**
- **No Node.js, no proxy, no extra infrastructure.** The MCP server runs as a standard WordPress REST API endpoint.
- **You bring your own AI.** The plugin does not call any AI provider. AI clients call *your* site.
- **One endpoint, all tools.** `https://yourdomain.com/wp-json/rankout-connector/v1/mcp`
- **Free and open source** (GPL-2.0+), with optional paid integrations using *your own* third-party API keys (Semrush, DataforSEO, Google APIs).

---

## 2. How It Works — Architecture Overview

```
┌─────────────────────────────┐       MCP (JSON-RPC 2.0)       ┌────────────────────────────┐
│  AI Client                  │ ◄─────────────────────────────► │  WordPress + RankOut Connector   │
│  (Claude, ChatGPT, Cursor…) │       Streamable HTTP           │  /wp-json/rankout-connector/v1/  │
└─────────────────────────────┘                                 └────────────┬───────────────┘
                                                                             │
                              ┌──────────────────────────────────────────────▼─────────────────────────┐
                              │  MCP\Server                                                             │
                              │   ├── tools/list  →  Tool_Registry::get_all_definitions()              │
                              │   ├── tools/call  →  Tool_Registry::get_tool($name)->execute($args)    │
                              │   └── resources/read → Resource_Registry                               │
                              └─────────────────────────────────────────────────────────────────────────┘
```

### Request flow

1. AI client sends a JSON-RPC 2.0 request to `POST /wp-json/rankout-connector/v1/mcp`.
2. `MCP\Transport` authenticates the request (Bearer token or OAuth 2.0 access token).
3. `Auth\Permission_Guard` checks the token has permission for the requested tool.
4. `MCP\Server` dispatches to `Tool_Registry::get_tool($name)->execute($arguments)`.
5. The tool validates arguments, checks WordPress capabilities, performs the action, and returns an array.
6. The server wraps the result in a JSON-RPC 2.0 response and sends it back.
7. `History\Change_Recorder` (if enabled) snapshots before/after state for write tools.

### Key classes

| Class | Purpose |
|---|---|
| `Plugin` | Singleton. Registers hooks, loads includes, wires everything together. |
| `MCP\Server` | Handles `initialize`, `tools/list`, `tools/call`, `resources/list`, `resources/read`. |
| `MCP\Transport` | Registers the REST route, handles Streamable HTTP and SSE. |
| `Auth\Token_Manager` | Create, validate, hash, and delete Bearer tokens. |
| `Auth\Permission_Guard` | Checks token permission scope against requested tool name. |
| `Tools\Tool_Registry` | Stores tool instances. `auto_discover()` instantiates all registered classes. |
| `Tools\Base_Tool` | Abstract base class every tool extends. |
| `GitHub_Updater` | Hooks into WordPress's update system to pull releases from GitHub. |

---

## 3. MCP Protocol Primer

[Model Context Protocol (MCP)](https://modelcontextprotocol.io) is an open standard (created by Anthropic, now multi-vendor) that defines how AI models communicate with external tools and data sources. Think of it as a typed, discoverable REST-like API designed specifically for LLM tool-use.

**Core concepts:**

| Concept | Description |
|---|---|
| **Tool** | A callable function with a name, description, and JSON Schema input schema. Analogous to a REST endpoint. |
| **Resource** | A readable data source (like a file or database view). Read-only. |
| **Transport** | How messages are sent. RankOut Connector uses Streamable HTTP (POST + optional SSE stream). |
| **JSON-RPC 2.0** | The wire format. Every request has `method`, `params`, `id`. Every response has `result` or `error`. |

**How a tool call looks on the wire:**
```json
// Request
{ "jsonrpc": "2.0", "id": 1, "method": "tools/call",
  "params": { "name": "wp_get_post", "arguments": { "post_id": 42 } } }

// Response
{ "jsonrpc": "2.0", "id": 1,
  "result": { "content": [{ "type": "text", "text": "{...post JSON...}" }] } }
```

---

## 4. Plugin Structure

```
rankout-connector/
├── rankout-connector.php                   # Main plugin file, defines constants
├── includes/
│   ├── class-plugin.php              # Singleton, hooks, init
│   ├── class-github-updater.php      # GitHub-based auto-updater
│   ├── class-activator.php
│   ├── class-deactivator.php
│   ├── mcp/
│   │   ├── class-server.php          # MCP method dispatcher
│   │   ├── class-transport.php       # REST route + Streamable HTTP
│   │   ├── class-session.php
│   │   └── class-json-rpc.php
│   ├── auth/
│   │   ├── class-token-manager.php
│   │   ├── class-token-auth.php
│   │   └── class-permission-guard.php
│   ├── oauth/                        # Full OAuth 2.0/2.1 server
│   ├── tools/
│   │   ├── class-base-tool.php       # Abstract base class
│   │   ├── class-tool-registry.php   # Registry + auto_discover()
│   │   ├── posts/                    # wp_list_posts, wp_get_post, ...
│   │   ├── pages/
│   │   ├── media/
│   │   ├── taxonomy/
│   │   ├── comments/
│   │   ├── users/
│   │   ├── site/
│   │   ├── menus/
│   │   ├── plugins/
│   │   ├── themes/
│   │   ├── revisions/
│   │   ├── meta/
│   │   ├── search/
│   │   ├── blocks/
│   │   ├── cpt/
│   │   ├── templates/
│   │   ├── styles/
│   │   ├── history/
│   │   ├── schema/                   # Phase 1 — Structured Data
│   │   ├── geo/                      # Phase 2 — GEO
│   │   ├── aeo/                      # Phase 3 — AEO  ← new in v2.0.0
│   │   ├── eeat/                     # Phase 4 — E-E-A-T  ← new in v2.0.0
│   │   ├── reporting/                # Phase 5 — Reporting  ← new in v2.0.0
│   │   ├── filesystem/               # wp-content file access
│   │   ├── database/                 # read-only SQL
│   │   ├── woocommerce/
│   │   ├── acf/
│   │   ├── events-calendar/
│   │   ├── buddypress/
│   │   ├── seo/ (yoast, rank-math, aioseo)
│   │   ├── gsc/
│   │   ├── ga/
│   │   ├── dfs/
│   │   └── semrush/
│   ├── resources/
│   ├── history/
│   ├── admin/
│   └── ...
├── CHANGELOG.md                      # This project's full changelog
├── DEVELOPER.md                      # This file
└── readme.txt                        # WordPress.org readme
```

---

## 5. Tool System — Writing a Custom Tool

Every tool is a PHP class that extends `RankOut_Connector\Tools\Base_Tool`.

### Minimal example

```php
<?php
namespace RankOut_Connector\Tools\MyCategory;

use RankOut_Connector\Tools\Base_Tool;

if ( ! defined( 'ABSPATH' ) ) { exit; }

class Hello_World extends Base_Tool {

    public function get_name(): string {
        return 'wp_hello_world';   // must be snake_case, unique
    }

    public function get_description(): string {
        return 'Returns a greeting. Parameters: name (string, optional).';
    }

    public function get_category(): string {
        return 'mycat';            // used for grouping in the admin UI
    }

    public function get_required_capability(): string {
        return 'read';             // any WordPress capability
    }

    public function get_annotations(): array {
        return [
            'title'           => $this->get_title(),
            'readOnlyHint'    => true,
            'destructiveHint' => false,
            'openWorldHint'   => false,
        ];
    }

    public function get_input_schema(): array {
        return [
            'type'       => 'object',
            'properties' => [
                'name' => [ 'type' => 'string', 'description' => 'Name to greet.' ],
            ],
        ];
    }

    public function execute( array $arguments ): array {
        $name = sanitize_text_field( $arguments['name'] ?? 'World' );
        return [ 'message' => "Hello, $name!" ];
    }
}
```

### Registering the tool

Two places need updating:

**1. `includes/class-plugin.php` — add the directory to `$tool_dirs`:**
```php
$tool_dirs = array(
    'posts', 'pages', /* ... existing ... */,
    'mycat',   // ← add this
);
```

**2. `includes/tools/class-tool-registry.php` — add the class to `auto_discover()`:**
```php
$tool_classes = array(
    // ... existing ...
    'RankOut_Connector\\Tools\\MyCategory\\Hello_World',
);
```

### `Base_Tool` contract

| Method | Required | Purpose |
|---|---|---|
| `get_name()` | ✅ | Unique snake_case tool name exposed to MCP clients |
| `get_description()` | ✅ | Human-readable description (also used by AI for tool selection) |
| `get_category()` | ✅ | Groups tools in admin UI |
| `get_required_capability()` | ✅ | WordPress capability checked before `execute()` |
| `get_input_schema()` | ✅ | JSON Schema object for input validation |
| `execute(array $args)` | ✅ | Tool logic; return an associative array |
| `get_annotations()` | ✅ | `readOnlyHint`, `destructiveHint`, `openWorldHint` |
| `get_title()` | inherited | Human-friendly title derived from class name |

### Security checklist for new tools

- [ ] Always check capabilities with `current_user_can()` inside `execute()`
- [ ] Sanitize all string inputs with `sanitize_text_field()` / `wp_kses_post()` / `absint()`
- [ ] Reject path traversal: check for `..` in any file path and verify with `realpath()`
- [ ] Never pass unsanitized input to `$wpdb->query()`
- [ ] For write tools, set `'destructiveHint' => false` unless the action is irreversible
- [ ] For read-only tools, set `'readOnlyHint' => true`

---

## 6. Tool Categories & Full Tool List

### Core WordPress (93 tools)

| Category | Tools |
|---|---|
| Posts | `wp_list_posts`, `wp_get_post`, `wp_get_post_full`, `wp_create_post`, `wp_update_post`, `wp_delete_post`, `wp_search_posts`, `wp_count_posts`, `wp_replace_in_post`, `wp_add_post_terms` |
| Pages | `wp_list_pages`, `wp_get_page`, `wp_create_page`, `wp_update_page`, `wp_delete_page` |
| Media | `wp_list_media`, `wp_get_media`, `wp_upload_media`, `wp_upload_media_from_url`, `wp_update_media`, `wp_delete_media`, `wp_count_media` |
| Taxonomy | `wp_list_categories`, `wp_get_category`, `wp_create_category`, `wp_update_category`, `wp_delete_category`, `wp_list_tags`, `wp_get_tag`, `wp_create_tag`, `wp_update_tag`, `wp_delete_tag`, `wp_count_terms`, `wp_get_term`, `wp_create_term`, `wp_update_term`, `wp_delete_term` |
| Comments | `wp_list_comments`, `wp_get_comment`, `wp_create_comment`, `wp_update_comment`, `wp_delete_comment` |
| Users | `wp_list_users`, `wp_get_user`, `wp_create_user`, `wp_update_user`, `wp_delete_user` |
| Site | `wp_get_site_settings`, `wp_update_site_settings`, `wp_get_post_types`, `wp_get_taxonomies`, `wp_get_post_statuses` |
| Menus | `wp_list_menus`, `wp_get_menu`, `wp_create_menu`, `wp_update_menu`, `wp_delete_menu`, `wp_list_menu_items`, `wp_create_menu_item`, `wp_update_menu_item`, `wp_delete_menu_item` |
| CPT | `wp_list_cpt_items`, `wp_get_cpt_item`, `wp_create_cpt_item`, `wp_update_cpt_item`, `wp_delete_cpt_item` |
| Meta | `wp_get_post_meta`, `wp_update_post_meta`, `wp_delete_post_meta`, `wp_get_term_meta`, `wp_update_term_meta`, `wp_delete_term_meta`, `wp_get_user_meta`, `wp_update_user_meta`, `wp_delete_user_meta` |
| Revisions | `wp_list_revisions`, `wp_get_revision`, `wp_delete_revision`, `wp_restore_revision` |
| Blocks | `wp_list_blocks`, `wp_get_block`, `wp_create_block`, `wp_update_block`, `wp_delete_block` |
| Templates | `wp_list_templates`, `wp_get_template`, `wp_update_template` |
| Styles | `wp_get_global_styles`, `wp_update_global_styles` |
| Search | `wp_search` |
| Plugins/Themes | `wp_list_plugins`, `wp_list_themes`, `wp_get_active_theme` |
| History | `wp_history_list`, `wp_history_get`, `wp_history_diff` |

### SEO / GEO / AEO / E-E-A-T (18 tools)

| Phase | Tools |
|---|---|
| **Phase 1 — Schema** | `wp_get_post_schema`, `wp_update_post_schema`, `wp_audit_schema_coverage`, `wp_list_schema_types` |
| **Phase 2 — GEO** | `wp_get_llms_txt`, `wp_update_llms_txt`, `wp_get_entity_context`, `wp_audit_geo_readiness` |
| **Phase 3 — AEO** | `wp_get_faq_blocks`, `wp_create_faq_block`, `wp_audit_answer_readiness` |
| **Phase 4 — E-E-A-T** | `wp_get_eeat_signals`, `wp_get_content_freshness`, `wp_get_internal_links`, `wp_suggest_internal_links` |
| **Phase 5 — Reporting** | `wp_seo_audit_site`, `wp_content_gap_report` |

### Filesystem & Database (7 tools)

| Tool | Description |
|---|---|
| `wp_get_theme_file` | Read any file inside the active or any installed theme |
| `wp_list_theme_files` | List files in a theme directory (depth 1–5) |
| `wp_get_plugin_file` | Read any file inside any installed plugin |
| `wp_list_plugin_files` | List files in a plugin directory |
| `wp_list_wp_content` | List any directory under `wp-content/` |
| `wp_get_wp_content_file` | Read any file under `wp-content/` |
| `wp_run_db_query` | Run SELECT-only SQL (with `{prefix}` substitution, max 500 rows) |

### Plugin Integrations (optional, 103+ tools)

| Plugin | Tool count |
|---|---|
| WooCommerce | 46 |
| Advanced Custom Fields (ACF) | 6 |
| The Events Calendar | 10 |
| BuddyPress | 10 |
| Yoast SEO | 3 |
| Rank Math | 3 |
| All in One SEO (AIOSEO) | 2 |

### External Data (38 tools, requires API keys)

| Service | Tool count | Config location |
|---|---|---|
| Google Analytics 4 | 11 | RankOut Connector → External Data |
| Google Search Console | 6 | RankOut Connector → External Data |
| Semrush | 13 | RankOut Connector → External Data |
| DataforSEO | 8 | RankOut Connector → External Data |

---

## 7. Authentication — Bearer Tokens & OAuth 2.0/2.1

### Bearer tokens

1. Go to **RankOut Connector → API Tokens → Create New Token**.
2. Set a name, choose the WordPress user the AI will act as, select tool permissions.
3. Copy the token — it is shown **once only** (SHA-256 hashed before storage).
4. Pass it in the `Authorization` header: `Authorization: Bearer <token>`.

### OAuth 2.0/2.1 (recommended for interactive AI clients)

The plugin implements a full OAuth 2.1 authorization server:
- PKCE (S256)
- RFC 7591 Dynamic Client Registration
- Refresh-token rotation (RFC 9700)
- RFC 8707 audience binding
- RFC 8414 / RFC 9728 discovery endpoints

**Flow:**
1. AI client fetches `/.well-known/oauth-authorization-server` to discover endpoints.
2. Client registers itself via `POST /wp-json/rankout-connector/v1/oauth/register`.
3. Client redirects user to `/?rankout_connector_oauth=authorize`.
4. User logs in to WordPress, approves the consent screen (scope checkboxes).
5. Client exchanges auth code for access + refresh tokens.
6. Client sends `Authorization: Bearer <access_token>` on every MCP request.

---

## 8. SEO/GEO/AEO/E-E-A-T Tool Suite

The SEO suite spans 5 phases, building from structured data up to full site reporting.

### Phase 1 — Schema (Structured Data)

Goal: ensure every post has machine-readable JSON-LD that AI engines can parse.

**Workflow:**
```
wp_list_schema_types          → pick the right @type (Article, FAQPage, HowTo, etc.)
wp_get_post_schema  (post_id) → see what's there now
wp_update_post_schema         → write validated JSON-LD to _rankout_connector_schema meta
wp_audit_schema_coverage      → find all posts missing structured data
```

Schema stored via `wp_update_post_schema` is automatically output in `<head>` as:
```html
<script type="application/ld+json">{ "@context": "...", ... }</script>
```

### Phase 2 — GEO (Generative Engine Optimisation)

Goal: make content discoverable by AI crawlers (Perplexity, ChatGPT Browse, Gemini, etc.).

**Key signals audited by `wp_audit_geo_readiness`:**
- JSON-LD schema present (25 pts)
- Author entity with bio (20 pts)
- H2/H3 headings (15 pts)
- External citations ≥ 1 (15 pts)
- Internal links (10 pts)
- Word count ≥ 300 (10 pts)
- Featured image with alt text (5 pts)

**`llms.txt`** is an emerging standard (like `robots.txt` for LLMs). `wp_get_llms_txt` auto-generates one from your site structure; `wp_update_llms_txt` writes it to the webroot.

### Phase 3 — AEO (Answer Engine Optimisation)

Goal: capture featured snippets and "People also ask" boxes.

**Key signals audited by `wp_audit_answer_readiness`:**
- FAQ / Q&A content (25 pts)
- Opening paragraph ≤ 55 words (20 pts) — concise direct answer
- Title phrased as a question (20 pts)
- H2/H3 headings phrased as questions (20 pts)
- Bullet or numbered lists (15 pts)

**Typical AEO workflow:**
```
wp_audit_answer_readiness  →  find posts with low AEO scores
wp_get_faq_blocks (post_id) →  see existing FAQs
wp_create_faq_block         →  append FAQ section + FAQPage schema in one call
```

### Phase 4 — E-E-A-T / HEO

Google's E-E-A-T (Experience, Expertise, Authoritativeness, Trustworthiness) signals influence ranking for YMYL content. HEO (Human Experience Optimisation) is the practice of demonstrating real human expertise.

**`wp_get_eeat_signals` scoring (100 pts):**

| Dimension | Signal | Points |
|---|---|---|
| Experience | Author bio ≥ 30 chars | 20 |
| Experience | Author has social / website links | 15 |
| Expertise | Author entity in JSON-LD | 15 |
| Authoritativeness | ≥ 2 outbound citations | 15 |
| Trustworthiness | Updated within 12 months | 20 |
| Trustworthiness | Structured data present | 15 |

**Freshness workflow:**
```
wp_get_content_freshness (days: 365)  →  find stale content
wp_get_eeat_signals (post_id)          →  prioritise which to update first
```

**Internal linking workflow:**
```
wp_get_internal_links (post_id)        →  audit existing links
wp_suggest_internal_links (post_id)    →  find missed opportunities
```

### Phase 5 — Reporting & Aggregation

**`wp_seo_audit_site`** — run this first on any new site to get a baseline. It returns a prioritised `fix_priority` list so you can direct the AI to the highest-impact improvements.

**`wp_content_gap_report`** — give it a list of target search topics or keywords and it tells you which ones have no coverage. Use it to plan a content calendar:
```
wp_content_gap_report topics: ["how to train a dog", "puppy socialization", "dog nutrition"]
→ covered: ["dog nutrition"] (matched "The Best Dog Food Guide")
→ gaps:    ["how to train a dog", "puppy socialization"]
```

---

## 9. Filesystem & Database Tools

### Filesystem

All filesystem tools enforce:
1. **Capability check** — theme tools require `edit_themes`, plugin tools require `edit_plugins`, wp-content tools require `manage_options`
2. **Path traversal prevention** — any path containing `..` is rejected; `realpath()` is used to verify the resolved path starts within the expected base directory
3. **File size limit** — files larger than 512 KB are rejected with an error

```php
// Example: what base paths are used
$theme_base   = get_template_directory();           // /var/www/html/wp-content/themes/mytheme
$plugin_base  = WP_PLUGIN_DIR . '/' . $slug;        // /var/www/html/wp-content/plugins/myplugin
$content_base = WP_CONTENT_DIR;                     // /var/www/html/wp-content
```

### Database

`wp_run_db_query` runs read-only SQL via `$wpdb->get_results()`. Safety measures:
- Strips SQL comments before analysis
- Asserts the first keyword (after comment stripping) is `SELECT`
- Rejects any semicolon (prevents stacked statements)
- Blocklist: `INSERT`, `UPDATE`, `DELETE`, `DROP`, `CREATE`, `ALTER`, `TRUNCATE`, `REPLACE`, `CALL`, `EXEC`, `GRANT`, `REVOKE`
- Results capped at 500 rows
- Supports `{prefix}` substitution for portability

```sql
-- Example query
SELECT ID, post_title, post_status FROM {prefix}posts
WHERE post_type = 'post' AND post_status = 'publish'
ORDER BY post_date DESC LIMIT 10
```

---

## 10. Auto-Updater (GitHub Releases)

`includes/class-github-updater.php` hooks into WordPress's built-in plugin update system.

**How it works:**
1. Hooks into `pre_set_site_transient_update_plugins` to inject update info
2. Fetches `https://api.github.com/repos/throughout-org/rankout-connector/releases/latest` (cached 12 h via transient `rankout_connector_github_release`)
3. Compares remote `tag_name` (strip leading `v`) against `RANKOUT_CONNECTOR_VERSION`
4. If remote is newer, injects an update object into WordPress's update transient
5. WordPress handles the rest: update notice, one-click update, progress bar
6. After unzip, `fix_source_dir()` renames `rankout-connector-{version}/` → `rankout-connector/` so the plugin path stays consistent

**"Check for Updates" button:**  
On the Plugins page, a "Check for Updates" link is added to the plugin row. Clicking it:
1. Verifies a nonce
2. Calls `bust_cache()` (deletes the transient)
3. Calls `wp_clean_plugins_cache(true)` to force WordPress to re-check
4. Redirects back to `plugins.php`

**Releasing a new version:**
```bash
# 1. Bump version in rankout-connector.php (both header comment and define)
# 2. Bump Stable tag in readme.txt
# 3. Add changelog entry
git add -p
git commit -m "v2.x.y — description"
git tag v2.x.y
git push origin main --tags
# GitHub creates the release automatically from the tag
```

---

## 11. Security Model

### Authentication layers

| Layer | Mechanism |
|---|---|
| Transport | HTTPS (required for OAuth; recommended for Bearer) |
| Identity | SHA-256 hashed Bearer token or OAuth 2.1 access token |
| Authorisation | Per-token tool permission bitmask |
| Capability | WordPress `current_user_can()` on every tool call |
| Rate limiting | 60 req/min per token (configurable) |
| Audit trail | Every tool call logged with token ID, arguments, result, IP |

### Sensitive data handling

- Raw tokens are **never stored** — SHA-256 hash only
- External API credentials (Semrush, DataforSEO, Google) are **AES-256-GCM encrypted** at rest
- Change History snapshots redact keys matching `*_token`, `*_secret`, `*password*`, `*api_key*`
- The `wp_run_db_query` tool is SELECT-only; all write keywords are blocklisted

### OAuth security

- PKCE S256 (no implicit flow, no plain PKCE)
- Refresh-token rotation with reuse detection (RFC 9700)
- RFC 8707 audience binding prevents token replay across services
- No password is ever sent to or seen by the AI client

---

## 12. Hooks & Filters Reference

| Hook | Type | Purpose |
|---|---|---|
| `rankout_connector_oauth_enabled` | filter | Return `false` to disable the OAuth 2.0/2.1 server entirely |
| `rankout_connector_history_query_scope` | filter | Narrow the Change History query scope for non-admins |
| `rankout_connector_tool_categories` | filter | Add or rename tool categories in the admin UI |
| `wp_head` | action | `output_post_schema()` — outputs `_rankout_connector_schema` JSON-LD |
| `rest_api_init` | action | Registers all MCP REST routes |
| `plugins_loaded` | action | `Activator::maybe_upgrade()` — runs DB migrations on plugin update |
| `rankout_connector_cleanup_audit_log` | action | Cron hook — purges old audit log rows |
| `rankout_connector_cleanup_change_log` | action | Cron hook — purges old change history rows |
| `rankout_connector_cleanup_oauth` | action | Cron hook — purges expired OAuth codes and tokens |

---

## 13. Step-by-Step: Connect Your AI

### Claude Desktop (OAuth — recommended)

1. Install and activate RankOut Connector on your WordPress site.
2. Go to **RankOut Connector → Dashboard** and copy the MCP server URL.
3. Open Claude Desktop → Settings → Connectors → Add custom connector.
4. Paste the URL. Claude opens your browser.
5. Log in to WordPress if needed.
6. On the consent screen, tick the permission categories you want to grant.
7. Click **Approve**.
8. Claude is connected. Try: *"List my last 5 posts."*

### ChatGPT / Cursor / Windsurf (Bearer token)

1. Go to **RankOut Connector → API Tokens → Create New Token**.
2. Set a name, user, and permissions. Click **Create Token** and copy it.
3. In your AI client, add an MCP server:
   - **URL:** `https://yourdomain.com/wp-json/rankout-connector/v1/mcp`
   - **Auth:** Bearer token (paste the token you copied)
4. Start a conversation.

### n8n

1. Create a Bearer token as above.
2. In n8n, add an **MCP** node.
3. Set the URL and header `Authorization: Bearer <token>`.
4. Use the MCP node's tool-call action to call any `wp_*` tool.

### Claude Code (CLI)

Add to your `~/.claude/settings.json` or project `.claude/settings.json`:
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

## 14. Troubleshooting

### 404 on the MCP endpoint

Go to **Settings → Permalinks** and click **Save Changes**. Pretty permalinks must be enabled. Plain permalink mode is not supported.

### 401 Unauthorized

- Check the token in your client matches one in **RankOut Connector → API Tokens** (tokens are shown once — if lost, delete and recreate).
- Ensure the `Authorization: Bearer <token>` header is reaching WordPress. Some reverse proxies strip the header — add `SetEnvIf Authorization "(.*)" HTTP_AUTHORIZATION=$1` to `.htaccess` or the equivalent for nginx.

### Tools appear in the list but calls fail with "capability_check_failed"

The WordPress user the token is bound to does not have the required capability. Either:
- Bind the token to a higher-privilege user (Editor or Administrator), or
- Grant the specific capability to the existing user role using `add_role()` or a capabilities plugin.

### OAuth redirect loop / consent screen keeps reappearing

Ensure `home_url()` and `site_url()` both use HTTPS. OAuth requires a secure context (except on `127.0.0.1`). If behind a reverse proxy that terminates TLS, add to `wp-config.php`:
```php
$_SERVER['HTTPS'] = 'on';
define('FORCE_SSL_ADMIN', true);
```

### GitHub updater shows "up to date" even after a new release

Click **Check for Updates** in the plugin row on the Plugins page to bust the 12-hour transient cache and force a fresh API call. Alternatively, delete the `rankout_connector_github_release` transient directly from the Options table.

### FAQ block not showing FAQPage schema in `<head>`

1. Confirm the post has `_rankout_connector_schema` set (use `wp_get_post_schema` to check).
2. Ensure no other plugin (Yoast, Rank Math) is stripping or deduplicating `<script type="application/ld+json">` tags. Some SEO plugins have a "deduplicate schema" option that may remove ours.
3. Check the `wp_head` hook fires — some page-caching setups serve static HTML that bypasses it.

---

*For questions or contributions, open an issue at [github.com/throughout-org/rankout-connector](https://github.com/throughout-org/rankout-connector).*
