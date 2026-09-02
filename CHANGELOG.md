# Changelog

## 2.0.2

- Register RankOut's production WordPress OAuth callback by default while retaining a filter for local and staging environments.

## 2.0.1

- Fix activation after the RankOut namespace migration by using the current plugin version constant.
- Complete the RankOut Connector branding migration.
- Restore packaged GitHub releases for WordPress one-click updates.

All notable changes to RankOut Connector are documented here.  
Format follows [Keep a Changelog](https://keepachangelog.com/en/1.0.0/).  
Versions follow [Semantic Versioning](https://semver.org/).

---

## [2.0.0] — 2026-06-12

### Added — Phase 3: AEO (Answer Engine Optimisation)

**`wp_get_faq_blocks`** `readonly`  
Extracts all FAQ and Q&A blocks from a post. Detects:
- Yoast FAQ blocks (`<!-- wp:yoast/faq-block -->`)
- Rank Math FAQ blocks (`<!-- wp:rank-math/faq-block -->`)
- Inline `FAQPage` JSON-LD already embedded in content
- Generic HTML `<details>/<summary>` patterns

Returns `{ faq_count, source_counts, faqs: [{ source, question, answer }] }`.

**`wp_create_faq_block`** `write`  
Appends (or prepends) a Yoast-compatible FAQ block to a post and simultaneously saves a `FAQPage` JSON-LD schema to `_rankout_connector_schema` post meta so it renders in `<head>`. Merges with existing FAQPage schemas instead of overwriting. Parameters: `post_id`, `faqs: [{ question, answer }]`, `append` (default `true`).

**`wp_audit_answer_readiness`** `readonly`  
Scores published posts out of 100 for Answer Engine Optimisation (featured snippets, "People also ask"). Rubric:
| Signal | Points |
|---|---|
| FAQ / Q&A content | 25 |
| Concise opening paragraph (≤ 55 words) | 20 |
| Title phrased as a question | 20 |
| H2/H3 headings phrased as questions | 20 |
| Bullet or numbered lists | 15 |

Returns per-post scores, grades (A–F), `quick_wins` suggestions, and a site average.

---

### Added — Phase 4: E-E-A-T / HEO (Human Experience Optimisation)

**`wp_get_eeat_signals`** `readonly`  
Full E-E-A-T audit for a single post (100-point scale):
| Dimension | Signal | Points |
|---|---|---|
| Experience | Author bio ≥ 30 chars | 20 |
| Experience | Author social / website links | 15 |
| Expertise | Author entity in JSON-LD schema | 15 |
| Authoritativeness | Outbound citations ≥ 2 | 15 |
| Trustworthiness | Content updated within 12 months | 20 |
| Trustworthiness | Structured data present | 15 |

Returns per-dimension scores, detailed signal breakdown, and actionable recommendations.

**`wp_get_content_freshness`** `readonly`  
Lists published posts that have not been updated within a configurable number of days (default: 180), sorted by staleness oldest-first. Use this to find content that needs a freshness review to maintain E-E-A-T signals.  
Parameters: `post_type`, `days`, `limit`, `offset`.

**`wp_get_internal_links`** `readonly`  
Lists every internal link within a post's content, resolved to the target post ID, title, and status. Useful for auditing link equity, finding broken internal links, and spotting orphaned content.

**`wp_suggest_internal_links`** `readonly`  
Finds related posts that (a) should link **to** the given post, or (b) the given post should link **to**. Matches by shared taxonomy terms and title/content word overlap. Returns suggestions scored by relevance, never modifies content.

---

### Added — Phase 5: Reporting & Aggregation

**`wp_seo_audit_site`** `readonly`  
Master site-wide SEO/GEO/AEO/E-E-A-T audit. Scans up to 500 published posts and returns:
- `schema_coverage_pct` — % of posts with JSON-LD structured data
- `missing_seo_title` / `missing_meta_desc` — count of posts lacking SEO meta
- `stale_content_count` — posts not updated in 12+ months
- `author_bio_pct` — % of posts whose author has a bio
- `aeo_ready_count` / `aeo_ready_pct` — posts with FAQ/Q&A content
- `geo_ready_count` / `geo_ready_pct` — posts with schema + headings + 300+ words
- `avg_eeat_score` — average E-E-A-T score (0–100)
- `eeat_grade_distribution` — breakdown of grades A through F
- `top_issues` — most common problems sorted by frequency
- `fix_priority` — ranked list of the 5 highest-impact fixes

**`wp_content_gap_report`** `readonly`  
Compares a list of target topics/keywords against all published posts to surface content gaps. Scores each post's relevance to each topic by matching title words, category names, and tag names. Returns `covered` topics with their best-matching post, `gaps` with closest match scores, and a `coverage_pct`.  
Parameters: `topics` (array, required), `post_type`, `threshold` (0–100, default 30).

---

### Changed
- Version bumped to **2.0.0**
- Plugin description updated to reflect 233 tools and the new AEO/E-E-A-T/Reporting categories
- `class-plugin.php`: added `'aeo'`, `'eeat'`, `'reporting'` to `$tool_dirs`
- `class-tool-registry.php`: added 10 new class names to `auto_discover()`

---

## [1.9.2] — 2026-05-xx

### Added
- GitHub auto-updater (`class-github-updater.php`) — plugin checks `throughout-org/rankout-connector` releases every 12 h via transient cache; "Check for Updates" link on Plugins page busts the cache and forces a fresh check
- `wp_list_wp_content` — list directories and files anywhere under `wp-content/`
- `wp_get_wp_content_file` — read any file under `wp-content/` (requires `manage_options`)

---

## [1.9.0] — Phase 2: GEO

### Added
- `wp_get_llms_txt` — reads or auto-generates an `llms.txt` file (AI-crawler sitemap standard)
- `wp_update_llms_txt` — writes `llms.txt` via WP_Filesystem
- `wp_get_entity_context` — extracts author entity, taxonomy terms, headings, links, images, and schema from any post
- `wp_audit_geo_readiness` — scores posts on GEO signals (schema, author bio, headings, external citations, internal links, word count, image alt)

---

## [1.8.0] — Phase 1: Schema / Structured Data

### Added
- `wp_get_post_schema` — reads JSON-LD schema from post meta, Yoast, Rank Math, or inline content
- `wp_update_post_schema` — stores validated JSON-LD in `_rankout_connector_schema` post meta; `null` deletes it
- `wp_audit_schema_coverage` — scans up to 200 posts and reports coverage percentage
- `wp_list_schema_types` — returns 14 schema.org types with required fields and AI-ready examples
- Auto-output: schemas stored via the plugin are output as `<script type="application/ld+json">` in `<head>` via `wp_head` hook

---

## [1.7.2]

### Fixed
- AI connections that dropped on token expiry now auto-reconnect
- Gutenberg blocks with special characters (`&` etc.) no longer corrupt on AI edits
- Added no-cache headers to MCP responses

---

## [1.7.0]

### Added
- Change History page with before/after snapshots and inline diffs
- `wp_history_list`, `wp_history_get`, `wp_history_diff` tools
- `wp_get_post_full` — post + meta + terms in one call
- `wp_replace_in_post` — find-and-replace inside post content
- `wp_upload_media_from_url` — upload media from any URL
- Generic taxonomy term tools (create/get/update/delete on any taxonomy)

---

## [1.6.5]

### Added
- 13 Semrush Analytics API tools

---

## [1.6.0]

### Added
- 8 DataforSEO tools (SERP, keywords, backlinks, on-page, labs)

---

## [1.5.0]

### Added
- 11 Google Analytics 4 tools
- 6 Google Search Console tools
- External Data admin page

---

## [1.4.0]

### Added
- OAuth 2.0/2.1 one-click connect with PKCE, DCR, refresh-token rotation

---

## [1.3.0]

### Added
- WooCommerce (46 tools), ACF (6), The Events Calendar (10), BuddyPress (10), Yoast/Rank Math/AIOSEO SEO tools

---

## [1.0.0]

### Added
- Initial release: 48 MCP tools, Bearer token auth, rate limiting, audit log, IP whitelist
