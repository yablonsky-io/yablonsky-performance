=== Yablonsky Performance ===
Contributors: yablonsky
Tags: performance, cache, woocommerce, redis, nginx
Requires at least: 6.0
Tested up to: 7.1
Stable tag: 2.56.0
Requires PHP: 7.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Local-first WordPress performance control: measure, optimize, protect origin capacity, prove execution, and migrate safely.

== Description ==

Yablonsky Performance (YPerf) is a local-first WordPress performance control plane built around evidence instead of a wall of cache checkboxes.

Its operating model is:

Repeat HIT -> cache.  
First HIT -> make the request cheap.  
Concurrency -> bound the origin.

YPerf assigns each performance responsibility to a clear authority. Local WordPress controls provide the operational foundation, while native server and edge integrations extend coverage when available. State-changing operations use explicit authority, bounded writes, read-back proof, Runtime Acceptance and rollback where ownership is proven.

Current YPerf market baseline includes:

* portable YPerf Page Cache with cacheability policy, MISS -> HIT proof, bounded purge and prewarm;
* conservative Browser Cache policy for YPerf-owned public HTML;
* persistent Redis Object Cache activation with logical-database isolation, authenticated payloads, bounded health proof and rollback;
* route-aware Frontend Delivery Engine: emoji removal, WooCommerce transactional-asset gating, dependency-aware late execution for heavyweight WordPress/WooCommerce JavaScript, opt-in WordPress-native defer for eligible same-origin header scripts, optional late analytics execution, and bounded WooCommerce first-hit protection;
* Runtime Acceptance, origin-cost evidence, local benchmark and cacheability inspection;
* selective purge, semantic invalidation integration, local operational telemetry and purge audit;
* W3 Total Cache migration/handover with provider-exit gates, explicit user-controlled plugin activation status and rollback evidence;
* automatic WordPress/server visibility for normal use, with expert-only optional host diagnostics for self-managed servers;
* Origin Survival admission control that bounds crawler cold-origin concurrency before normal plugins, WooCommerce, WPML and theme execution, with an explicit early-runtime coordination phase for security-before-capacity ordering;
* Origin Budget evidence/adoption surfaces for reviewed PHP-FPM capacity planning;
* local Performance Autopilot with deterministic safety gates and rollback;
* optional Yablonsky AI Advisor for administrator-requested analysis.

The current market baseline focuses on page/object caching, delivery scheduling, origin survival, query-cost control and evidence-driven adoption. Additional database/fragment caching and broader asset transformation remain roadmap capabilities.

== Installation ==

1. Upload the plugin ZIP in Plugins -> Add Plugin -> Upload Plugin, or install the `yablonsky-performance` directory manually.
2. Activate Yablonsky Performance.
3. Open Yablonsky Performance in wp-admin.
4. Open Overview. YPerf presents the current performance state directly.
5. Use Optimize Now for the local one-click optimization path. Local WordPress operation is complete on its own; optional host and edge evidence extends available optimization depth.

Updating from an accepted YPerf build preserves compatible evidence only when the relevant execution boundary is unchanged. Evidence is invalidated when a changed runtime boundary makes carry-forward unsafe.

== Crawler-aware caching and deployment boundaries ==

YPerf 2.56.0 keeps one established page-cache owner. On shared hosting the optional YPerf-owned advanced-cache.php serves public anonymous HTML before WordPress bootstrap; Nginx, CDN and third-party drop-ins retain their previously adopted authority. Upgrading the plugin does not silently enable or replace any existing cache executor. On sites with an active older YPerf-owned drop-in, the administrator can use the existing verified artifact refresh control to adopt the new 2.56.0 response semantics after a proven rollback-safe handover. If the portable page cache is OFF, validators from this drop-in do not apply to independently owned Nginx/CDN responses.

A fresh public YPerf cache entry supports weak ETag / conditional GET 304, while robots.txt, XML sitemap and authenticated/transactional requests bypass that cache. No incoming User-Agent is considered verified identity: YSec owns verification and security rules; YSEO owns robots/indexing policy. This plugin does not bypass YSec protection or override YSEO indexing authority. Client requests blocked before WordPress by CDN/WAF cannot be verified through this plugin alone.

Version coordination follows YABLONSKY_PRODUCT_VERSIONING_CANON_v1.0.0_2026-09-21.md (major.minor.patch; MINOR 0-99; feature batches advance MINOR).

== Safety ==

YPerf follows a human-governed execution model:

Measure -> Prove -> Recommend -> Human approval -> Bounded write -> Read-back proof -> Runtime Acceptance -> Keep or rollback.

Important boundaries:

* one page-cache authority at a time;
* Redis operations stay namespace- and generation-based; FLUSHDB, FLUSHALL and Redis CONFIG SET remain outside the YPerf operation contract;
* existing WordPress cache drop-ins keep their authority until an explicit, proven handover;
* Nginx and PHP-FPM changes use the separate expert authority path with review, read-back and rollback;
* cache/drop-in writes are bounded to canonical WordPress paths and fail closed on ownership ambiguity;
* WooCommerce cart, checkout, account, logged-in and session-sensitive requests are excluded from aggressive frontend delivery scheduling and cache policy;
* deactivation retires only exact YPerf-owned drop-ins; ambiguous artifacts are left for review;
* settings and local evidence are preserved on deactivation/uninstall by policy;
* Provider activation status remains an explicit WordPress administrator decision; YPerf coordinates handover through evidence and ownership checks.
* Heavy frontend scripts are delayed only on eligible anonymous public GET/HEAD routes, are loaded on first user intent or a bounded late timeout, and WordPress dependency chains are kept together. The native defer layer is separately controlled by Optimize, uses WordPress 6.3+ dependency strategy resolution, and leaves jQuery plus security/consent bootstraps on their original path; YPerf-owned Page Cache invalidation follows the YPerf operation contract; external page-cache freshness requires a separately proven provider executor.

== Privacy ==

Core YPerf performance controls run locally with a complete account-independent operating path.

Local reports contain performance measurements and configuration evidence. Raw SQL, post/page content, cookies, nonces, passwords, tokens and API keys stay outside the report export envelope.

The optional Yablonsky AI Advisor sends a privacy-minimized technical evidence envelope only after an explicit administrator AI action. The envelope excludes site URLs, hostnames, IP addresses, filesystem paths, raw SQL, raw logs, post/page content, cookies, nonces, passwords, tokens and API keys.

The Yablonsky AI API key stays server-side and remains outside frontend HTML and JavaScript.

== External services ==

YPerf provides a complete local operating path. Page Cache, Object Cache, Browser Cache policy, Runtime Acceptance, benchmarks, migration tools, local Autopilot, purge/prewarm and reports run locally.

The optional Yablonsky AI Advisor uses Yablonsky AI for remote compute after an explicit administrator action. Saving an API key stays local. Remote compute starts only from the administrator-requested AI actions described below.

The explicit Verify AI connection action performs a read-only request to:
`GET https://ai.yablonsky.io/api/account`

AI recommendation/question actions use the verified workspace and:
`POST https://ai.yablonsky.io/api/ai/completions`

Credentials are sent only to the Yablonsky AI HTTPS service, redirects are disabled, and remote responses are treated as untrusted data.

Service: https://ai.yablonsky.io/  
Terms: https://ai.yablonsky.io/policies/terms  
Privacy: https://ai.yablonsky.io/policies/privacy

The optional Cloudflare Performance Adapter uses the Cloudflare management API only after an administrator explicitly saves a site-scoped API token and starts a provider action. YPerf can discover the selected zone, read supported performance settings, explicitly adopt the bounded Rocket Loader OFF + HTTP/3 ON profile, verify the result by read-back, and synchronize cache purge after adoption.

Cloudflare API base endpoint:
`https://api.cloudflare.com/client/v4/`

The Cloudflare token is encrypted server-side and stays outside rendered admin output. A site-scoped token with Zone Read, Zone Settings Edit and Cache Purge matches the YPerf authority contract. Cloudflare crawler/AI policy, Bot Management, WAF and DNS remain complementary security/discovery layers.

Cloudflare API documentation: https://developers.cloudflare.com/api/overview/  
Cloudflare Terms: https://www.cloudflare.com/terms/  
Cloudflare Privacy Policy: https://www.cloudflare.com/policies/privacy/

== Frequently Asked Questions ==

= Does local YPerf work independently of Yablonsky AI? =

Yes. Local/self-hosted performance functionality is complete on its own. Optional remote AI compute adds administrator-requested analysis and may have separate service usage costs.

= How are Nginx, PHP-FPM and Cloudflare changes governed? =

Normal WordPress operation stays local to WordPress. Cloudflare becomes an optional adopted edge layer after a least-privilege token, zone inspection and explicit administrator approval. Nginx and PHP-FPM use separate expert authority paths with review, read-back proof and rollback.

= How does YPerf protect Redis data? =

YPerf Object Cache uses a site-specific namespace and generation-based invalidation. Database-wide flush commands and Redis configuration writes stay outside the operation contract.

= What happens when YPerf is deactivated? =

YPerf retires exact YPerf-owned runtime drop-ins when ownership evidence matches. Ambiguous artifacts stay in place for review, while settings and evidence remain available for later reactivation.

= How does the capability catalog represent current and future coverage? =

The catalog distinguishes owned execution, detected/external capabilities and roadmap capabilities. Frontend Delivery Engine owns runtime scheduling and bounded first-paint CSS delivery for eligible public pages; additional database query cache, fragment cache and general-purpose asset transformation remain roadmap capabilities.


= Which PHP versions does YPerf support? =

`Requires PHP: 7.4` defines the supported minimum. YPerf is designed for PHP 7.4 and newer. Current field evidence includes Generator.pw running YPerf on PHP 8.1, while the release package is syntax-validated on PHP 8.4. A full 7.4 / 8.x CI matrix remains a release gate before broad public GA, and published compatibility claims follow completed evidence.

== Upgrade Notice ==

= 2.56.0 =
Adds crawler-aware safe portable-cache revalidation (ETag/304), preserves public SEO headers on cache hits, protects authenticated/variant requests, and leaves existing Nginx authority and settings intact.

= 2.55.0 =
Adds safer portable Page Cache for shared hosting: cold-key coordination, short stale rescue, session/language bypass, and 2 MiB object limit. Prevents accidental activation beside adopted Nginx cache. Existing settings stay unchanged.

= 2.54.1 =
Places Health Check execution and its JSON/TXT results in one toolbar. Gives SQL Hotspots one distinct Diagnostics home with working SQL-only export. Removes duplicate Runtime Report views and SQL export controls. No frontend or commerce changes.


= 2.53.0 =
Adds direct JSON/TXT and SQL-only export beside Origin Cost and restores direct Diagnostics routes without changing frontend or SQL execution.

= 2.52.1 =
Restores clear Diagnostics navigation for SQL Hotspots and the saved Runtime Report, with JSON/TXT export directly in Diagnostics. No new probes or frontend changes.

= 2.52.0 =
Adds diagnostic-only SQL Hotspots to identify costly WooCommerce query shapes without exposing SQL or changing database execution. Media and public caching remain unchanged.



= 2.51.0 =
Adds canary-only Vimeo embed optimization while preserving existing YouTube public behavior. Vimeo remains off on normal visitor pages until field approval.

= 2.50.2 =
After connection loss, Optimize Now verifies the recorded server result before showing a final status. Media and cache behavior are unchanged.

= 2.50.1 =
* Refines Video & Media Optimization with responsive provider cards, accessible status labels and a clearer primary control. Fixes the 2.50.0 upgrade notice warning. No changes to frontend media delivery or existing settings.

= 2.50.0 =
* Adds Video & Media management with YouTube support and an honest compatibility map. Existing settings are preserved.

= 2.49.0 =
* Introduces administrator-controlled YouTube Video Lite for eligible public routes, OFF by default on upgrades and untouched by Optimize Now.
* Keeps matched preview/control test URLs and lightweight click-to-play fallback with no changes to host cache settings.

= 2.48.0 =
* Adds an isolated, opt-in YouTube preview canary. Ordinary visitor traffic is unchanged.


= 2.47.0 =
* Adds matched control/native-defer browser canaries and separates production frontend proof from the experimental defer probe.

= 2.46.2 =
* Removes Plugin Check warnings from the read-only native-defer canary and preserves its behavior.

= 2.46.1 =
* Adds a probe/canary-only leaf defer fallback while keeping ordinary visitor traffic on the proven execution path.

= 2.46.0 =
Adds opt-in WordPress-native defer for eligible same-origin header scripts, with dependency-aware Core resolution, conservative inline/jQuery/security exclusions and Runtime proof of the remaining parser-blocking head path.

= 2.45.5 =
Stabilizes the proven frontend execution path, keeps Optimize protected by a fresh Health Check before changes, and requires successful HTTP responses before external cache execution is accepted as healthy.

= 2.45.3 =
Uses site-independent final-document evidence for first-paint CSS and LCP image priority, with fail-closed safety gates instead of theme-specific rules.

= 2.45.2 =
Keeps non-hero routes paint-safe by releasing delayed heavyweight dependencies soon after DOM readiness instead of waiting on an LCP gate reserved for positively identified paint-first heroes.

= 2.45.1 =
Adds consistent module spacing across all tabs and clarifies the read-only Health Check versus the change-and-prove Optimize Now workflow.

= 2.45.0 =
Recognizes a current, proven external page-cache authority as healthy optimization state without requiring YPerf to own Page Cache execution.

= 2.44.0 =
Adds one dedicated Health Check page and aligns frontend proof with the route gate that controls CSS inlining.

= 2.43.0 =
Accepts a valid zero-inlined CSS execution proof, keeps Optimize proof actions visible beside the campaign, and preserves exact failed-gate evidence for future rollback analysis.

= 2.42.0 =
Unifies YPerf self-probe identity so Cloudflare-blocked local-origin Runtime Acceptance can prove frontend execution consistently during Optimize.

= 2.41.0 =
Optimize now preserves a proven external page-cache authority while applying YPerf-owned local controls, with cache-authority evidence carried through Runtime Acceptance.

= 2.40.0 =
Health Check resilience and background workload intelligence: sitemap diagnostics stay inside a bounded step budget, coordination evidence is exported, and Action Scheduler backlog becomes visible as read-only performance evidence.

= 2.39.0 =
Adds explicit Managed MU execution ordering for cross-layer coordination: YPerf registers its capacity phase on `muplugins_loaded` at priority -900, with the earlier -1000 phase reserved for YSec security decisions. Origin Survival behavior, crawler budget and cache ownership stay consistent.

= 2.38.1 =
Corrects WordPress Plugin Check compliance for Origin Survival with WordPress-native random and URL helpers. Runtime behavior and the two-slot crawler cold-origin budget remain unchanged.

= 2.38.0 =
Adds portable Origin Survival: Managed Early Runtime bounds concurrent crawler cold-origin work with Retry-After admission while human/private traffic keeps its existing path. Adds Runtime Acceptance proof and preserves current cache executors.

= 2.37.2 =
Improves Runtime Acceptance on Cloudflare-protected slow origins with bounded local-origin fallback, sitemap proof and diagnostic evidence. Runtime optimization behavior is unchanged.

= 2.37.1 =
Managed Early Runtime: one version-matched MU bootstrap now handles bounded WooCommerce deep-archive protection and crawler state-action normalization. Runtime Acceptance also distinguishes public edge blocking from local-origin health.

= 2.36.0 =
Adds external page-cache authority detection so YPerf preserves an existing Nginx/FastCGI cache instead of activating a competing portable page cache.

= 2.35.1 =
Repairs orphan Page Cache locks that could leave Health Check at BYPASS-LOCK after an interrupted request. Fresh writer locks remain fail-closed.

= 2.35.0 =
Starts a late-discovered oversized active-theme stylesheet earlier while preserving its original cascade and render-blocking semantics. Adds Runtime Acceptance proof for the early preload hint.

= 2.34.7 =
Corrects package identity so WordPress updates the canonical yablonsky-performance directory instead of installing a parallel versioned copy. No runtime performance semantics change.

= 2.34.6 =
Optimize progress truth fix: active step mapping now follows the real phase, active stages use a circle-with-dot marker, and the installable package also shortens the upgrade notice for a clean Plugin Check.

= 2.34.5 =
Corrects Optimize Now 94% stalls by splitting accepted-profile prewarm into bounded per-URL AJAX steps after Runtime proof is committed; terminal 100% is published only after the visible warm-up finalizer. Keeps an already accepted baseline accepted if optional warm-up transport fails.

= 2.34.4 =
* Keeps Optimize Now progress authoritative: 100% is shown only for terminal state, and removes the duplicate browser-side prewarm that previously ran after the server had already completed and reported 100%.

= 2.34.3 =
* Decouples YPerf sitemap isolation from sitemap-producer health. Protected XML/service routes are proved BYPASS independently; broken sitemap responses are ATTENTION, not YPerf FAIL, when YPerf did not touch them.

= 2.34.2 =
* Corrects live Page Cache truth after deactivation/executor drift and makes blocked Autopilot preflight stages show failure instead of a misleading 100% success path.

= 2.34.1 =
* Corrects Page Cache refresh rollback scope, preserves early probe JS evidence under bounded response sampling, and sanitizes REQUEST_URI for WordPress Plugin Check. Normal visitor optimization behavior is unchanged.

= 2.34.0 =
* Makes delayed-JavaScript Runtime Acceptance candidate-aware: zero eligible scripts is a proven safe no-op instead of a false failure.

= 2.30.0 =
* Protects XML/sitemap, robots.txt, feed and XML service routes from YPerf HTML cache/frontend transforms and requires an explicit guarded Page Cache executor refresh because the pre-bootstrap policy changed.

= 2.29.2 =
* Makes Runtime Acceptance CSS/LCP evidence explicit in exported JSON: exact proof gates plus remaining stylesheet URLs. Frontend optimization runtime is unchanged.

= 2.29.1 =
* Fixes Runtime Acceptance CSS-proof collection: the signed frontend proof now bypasses a Page Cache entry warmed earlier in the same Health Check. Frontend optimization policy is unchanged.

= 2.29.0 =
* Integrates the probe-only CSS delivery ledger into Runtime Acceptance so one bounded Health Check can show whether rewritten styles resolved by durable anchor, captured-tail fallback or local path while preserving existing pass/fail safety semantics.

= 2.28.0 =
* Adds probe-only CSS delivery evidence with inlined bytes, anchor/tail/local resolution counts and remaining stylesheet count. Normal visitor HTML is unchanged; the proof is emitted only to YPerf runtime probes.

= 2.27.0 =
* Hardens ownership-anchor recovery across path-hiding output filters that insert bounded harmless comments or whitespace between a stylesheet tag and the YPerf anchor; orphan anchors are still removed before delivery.

= 2.26.0 =
* Adds a fail-closed unique captured-style fallback: rewritten public CSS can bind to already trusted local bytes by an unambiguous trailing asset path, with no remote fetch and no filesystem guessing.

= 2.25.0 =
* Adds durable opaque stylesheet ownership anchors so hidden-path/security rewrites can preserve the registered WordPress style identity without weakening CSP, realpath, same-origin or byte-cap gates.

= 2.24.3 =
* Plugin Check corrective patch: shortens the historical 2.24.1 upgrade notice so every Upgrade Notice entry stays within the 300-character limit. Runtime behavior is unchanged.

= 2.24.2 =
* Plugin Check corrective patch: shortens the current upgrade notice to the WordPress.org limit. Frontend scheduling, prewarm, Cloudflare and cache behavior are unchanged.

= 2.24.1 =
* Nonce-verification compliance fix for toolbar return handling. Frontend scheduling, automatic prewarm, Cloudflare adapter and stay-on-current-screen toolbar behavior are unchanged.

= 2.24.0 =
Direct toolbar operations, real automatic prewarm after accepted optimization, and bounded paint-first jQuery dependency scheduling for Cover-hero routes.

= 2.23.0 =
Keeps toolbar purge actions on the page where they were invoked, adds explicit Cloudflare URL-purge synchronization proof, and preserves the 2.22 frontend runtime.

= 2.22.0 =
Hardens the Cloudflare workspace with Quick Setup, responsive layout, visible provider-operation progress and explicit alignment state. Frontend performance runtime is unchanged from 2.21.0.

= 2.21.0 =
Adds an optional least-privilege Cloudflare Performance Adapter: inspect delivery state, explicitly adopt Rocket Loader OFF + HTTP/3 ON with read-back proof/rollback, and synchronize Cloudflare cache purge without touching crawler or SEO policy.

= 2.20.0 =
Finishes successful Optimize Now campaigns with bounded verified Prewarm and records provider-neutral edge delivery evidence without changing provider configuration.

= 2.19.0 =
Captures CSS before hidden-path rewriting and isolates the YPerf scheduler from Cloudflare Rocket Loader so the mobile first-paint path is less timing-dependent without changing desktop-safe behavior.

= 2.18.0 =
Captures trusted local CSS before security/path-hiding rewrites so the existing bounded inline-delivery engine can remove hidden-URL render blockers without guessing filesystem paths; built from the last live-green 2.16 baseline.

= 2.14.0 =
Tightens first-paint stability: heavy scripts cannot auto-release before an observed LCP quiet window, and rewritten stylesheet ownership survives final URL-hiding filters without weakening CSP or filesystem safety.

= 2.13.0 =
Corrects 2.12 live paint variance: preserves original stylesheet identity before path rewriting, separates heavy and analytics release, waits for LCP quiet time, and fixes clipped environment-status badges.

= 2.12.0 =
Stabilizes first paint using live 2.11 evidence, adds fail-closed support for safely resolving rewritten local CSS, keeps delayed heavy scripts off the immediate paint path, and reconciles the product registry to the 36-language family canon.

= 2.11.0 =
Adds bounded first-paint CSS delivery for eligible public hero pages, tightens heavy-script release stability, and rebuilds Expert Server Diagnostics as a clean responsive workspace.

= 2.10.0 =
Stabilizes paint-first delivery: the LCP hero remains high priority while heavyweight WordPress/WooCommerce scripts wait until the hero gets a paint opportunity, user intent, or a bounded fallback.

= 2.9.0 =
Adds real Optimize progress plus bounded hero priority and adaptive frontend scheduling. Server/native writes remain outside the one-click optimization boundary.

= 2.8.1 =
Fixes YPerf Page Cache compatibility recognition after upgrading from the proven 2.3.x executor, so Optimize Now can repair, refresh and re-prove the owned cache instead of stopping on a false preflight failure.

= 2.8.0 =
Frontend Delivery Engine closeout: dependency-aware delayed execution, WooCommerce route safety, late analytics scheduling, settings portability, Runtime proof and automatic rollback. Existing 2.3.x installs do not silently enable new delivery policies; run Optimize Now to adopt them.

= 2.3.2 =
Full-width Optimize layout; preserves the compliant user-controlled third-party plugin activation contract.

= 2.3.1 =
Fixes the WordPress.org upgrade-notice limit and makes existing Object Cache conflicts non-destructive and easier to understand. YPerf leaves unknown object-cache.php files untouched and keeps an already optimized site optimized.

= 2.3.0 =
Makes Cache a primary control surface, adds quick purge actions, keeps W3TC activation changes user-controlled, and reconciles compatible Page Cache state.

= 2.2.0 =
Market control release: makes Optimize Now actually configure and verify YPerf cache controls, adds direct on/off/repair controls, restores completed Migration history, and adds portable settings backup/restore.

== Changelog ==

= 2.56.0 =
Adds crawler-aware safe portable-cache revalidation (ETag/304), preserves public SEO headers on cache hits, protects authenticated/variant requests, and leaves existing Nginx authority and settings intact.

= 2.55.0 =
* Strengthens standalone file-based Page Cache on ordinary WordPress hosting, with 1.5s single-key miss waiting and 90s bounded stale-only-during-refresh relief.
* Excludes additional personalized WooCommerce, translation and session cookies; never caches query strings, personal routes or responses >2 MiB.
* Defers to an explicitly adopted native Nginx cache, preserving active site configuration and prior feature choices.
* Refreshes the release integrity manifest, preserving original UI, SQL Hotspots, media controls and managed runtime behavior.

= 2.54.1 =
* Consolidates report actions by ownership: Health Check = run + full report + detailed proof; Diagnostics = SQL analysis and SQL-only export; Overview = summary and navigation.
* Restores reliable SQL-only JSON download in browsers and removes duplicate report tables and action groups.
* Preserves existing public optimization, cache ownership and per-route telemetry.


= 2.53.0 =
* Unified SQL Hotspots and Runtime Report exports across Overview, Diagnostics and Health Check. Correct direct-tab navigation. No SQL or frontend behavior changes.

= 2.52.1 =
* Adds Diagnostics → SQL Hotspots and Runtime Report views with direct JSON/TXT exports of the saved Health Check.
* Preserves Overview Origin Cost, Health Check details, System & Audit, and Admin Health.
* No SQL execution changes, no new requests, no cache mutation, no media policy change.

= 2.52.0 =
* Signed Runtime Acceptance now ranks up to five anonymized SQL query shapes per executed route and shows timings in Diagnostics.
* Does not record queries for ordinary visitors, rewrite SQL, alter WooCommerce pagination, or change page-cache ownership.



= 2.51.0 =
* Adds a strict opt-in Vimeo iframe click-to-play canary for anonymous public GETs only. No public provider rollout or Nginx changes.
* Preserves Vimeo unlisted hashes, iframe query parameters and original fallback links. Excludes sandboxed, dynamic and unknown embeds.
* Keeps YouTube public settings, markup and activation behavior unchanged.

= 2.50.2 =
* Restores Optimize Now transport status from the server ledger after a lost AJAX response. Prevents false failure signals or duplicate starts when completion is uncertain.

= 2.48.0 =
* Adds a canary-only, click-to-load YouTube preview with external fallback and no ordinary visitor changes.


= 2.48.0 =
* Corrective: manual/toolbar purge notices now distinguish provider-executed purge from signaled-only external-cache invalidation.
* No changes to caching, frontend scheduling or normal traffic execution.


= 2.47.0 =
* Adds an A/B critical-path canary pair so the same uncached public route can be measured with production scheduling (`control`) and with native-defer enabled (`native-defer`).
* Keeps ordinary visitor traffic on the proven production path; the standard Runtime Acceptance frontend proof no longer activates the experimental native-defer layer.
* Adds a separate Runtime Acceptance native-defer canary proof, including intended/rendered/blocked/head-blocking evidence, so browser experiments are attributable before promotion.
* Preserves the legacy 2.46.x native-defer canary URL for field continuity.

= 2.46.2 =
* Removes Plugin Check warnings from the read-only native-defer canary selector and shortens the historical 2.46.1 upgrade notice. Runtime behavior is unchanged from 2.46.1.

= 2.46.1 =
* Keeps native defer probe/canary-only and adds a conservative leaf-only fallback for the diagnostic canary while ordinary visitor traffic remains on the proven execution path.

= 2.46.0 =
* Adds an opt-in WordPress-native defer intention for eligible same-origin header scripts on WordPress 6.3+; upgrading alone keeps the new behavior OFF until Optimize/apply-profile.
* Preserves Core dependency ordering and fails closed for jQuery, security/consent bootstraps, footer scripts, existing loading strategies and handles with inline before/after/data payloads.
* Extends Runtime Acceptance with intended/rendered/blocked defer counts and the remaining external parser-blocking head-script count.
* Keeps current CSS ownership/inlining, heavyweight dependency-closed late execution, WooCommerce private-route boundaries, external page-cache authority and rollback behavior.

= 2.45.5 =
* Stabilizes the proven frontend execution path while native defer receives broader cross-plugin compatibility evidence.
* Keeps Optimize protected by a fresh Health Check so local changes begin only from a functionally ready baseline.
* Requires a successful HTTP response before a detected external page-cache layer is reported as healthy execution.
* Preserves the proven route-agnostic CSS ownership, adaptive first-paint image priority, external cache coexistence and rollback boundaries.

= 2.45.3 =
* Applies bounded same-origin CSS delivery on every eligible public route, preserving CSP, ownership, media, @import and byte limits.
* Discovers at most one high-confidence first-paint image from final HTML using generic evidence rather than a theme/site allow-list, then adds eager/high priority and a matching preload when safe.
* Leaves low-confidence pages unchanged and releases delayed heavyweight scripts on non-candidate routes only after load/idle, user intent or the bounded fallback.
* Makes Runtime Acceptance require CSS execution proof across eligible public routes and expose adaptive LCP-candidate evidence.

= 2.45.2 =
* Keeps non-hero public routes responsive by releasing dependency-closed heavyweight scripts after DOM readiness and an initial paint opportunity, with a short bounded fallback.
* Applies the LCP-observed quiet gate only to positively identified paint-first hero routes.
* Keeps first-user-intent execution, late analytics scheduling, external cache coexistence and rollback behavior unchanged.

= 2.45.1 =
* Adds one application-wide top-level module spacing contract across all YPerf tabs, preserving internal card-grid spacing while removing direct-module collisions.
* Adds concise Health Check copy explaining that the check verifies current state while Optimize Now performs its own fresh preflight, applies YPerf-owned changes, proves the result and accepts or rolls back.
* Shows `Recheck after upgrade` when current-version Runtime evidence has not yet been captured.
* Preserves external page-cache authority, one dedicated Health Check evidence surface and existing execution/rollback boundaries.

= 2.45.0 =
* Corrects the header optimization-state model to use YPerf's canonical proven page-cache layer instead of requiring the YPerf-owned portable Page Cache.
* A clean current-version Runtime Acceptance plus proven external Nginx/FastCGI page-cache execution can now produce the green `Optimized` header state.
* Keeps YPerf Page Cache OFF when an accepted external page-cache authority owns execution; no Nginx/provider configuration is changed.
* Preserves the single dedicated Health Check evidence surface and all existing runtime/rollback gates.

= 2.44.0 =
* Consolidates Health Check into one main product page with run, details and JSON/TXT export.
* Removes duplicate Runtime Acceptance surfaces from Overview, Diagnostics and the global page header.
* Aligns CSS proof with the live `paint_first` route gate so route-scoped frontend policies are verified on the routes where they execute.
* Preserves external Nginx/FastCGI page-cache authority and existing recovery boundaries.

= 2.43.0 =
* Treats a valid zero-inlined CSS proof as successful frontend execution when no stylesheet is eligible for safe inlining.
* Places probe-only CSS execution evidence at the start of the document head so bounded diagnostics can always see it.
* Keeps Run Health Check, Download JSON and Open full diagnostics next to the Optimize campaign result.
* Preserves exact failed proof gates in campaign evidence when automatic recovery is triggered.

= 2.42.0 =
* Unifies public-edge and local-origin YPerf self-probe identity for consistent frontend execution proof.
* Keeps exact failed-test names in Optimize rollback evidence.
* Preserves external page-cache authority and the 2.41.0 cache-coexistence behavior.

= 2.41.0 =
* Preserves proven external Nginx/FastCGI page-cache authority during Optimize and applies YPerf-owned local frontend/runtime controls without installing a second page-cache executor.
* Carries external cache authority metadata through Runtime Acceptance normalization and also derives authority from preserved cache signals for schema resilience.
* Keeps bounded sitemap diagnostics, Managed MU coordination, Origin Survival and read-only background workload intelligence from 2.40.0.

= 2.40.0 =
* Adds bounded sitemap diagnostics so producer/edge evidence stays inside a seven-second Health Check budget.
* Exports Managed MU and Origin Survival coordination evidence in Runtime Acceptance JSON.
* Adds read-only Action Scheduler workload intelligence for pending, past-due and failed background work.
* Keeps the established two-slot Origin Survival behavior and security-before-capacity coordination contract.
* Updates the Health Check interruption message to affirmative product language.

= 2.38.1 =
* Uses `wp_rand()` for the Origin Survival fallback token path.
* Uses `wp_parse_url()` for private-route classification.
* Preserves the 2.38.0 Origin Survival feature surface and runtime behavior.

= 2.38.0 =
* Adds portable crawler cold-origin admission in the Managed Early Runtime. Cache HITs complete before the MU layer; crawler requests that reach WordPress share a bounded two-slot origin budget by default.
* Uses standards-based HTTP 503 + Retry-After only when the crawler-origin budget is concurrently full, releasing PHP capacity for human and private traffic.
* Adds Runtime Acceptance and Feature Catalog evidence for Origin Survival.
* Preserves the current YPerf Page Cache wire format, external Nginx/FastCGI cache authority, Browser Cache and Frontend Delivery semantics.
* Keeps Cloudflare, CDN and native server adapters optional; portable protection runs locally through WordPress.
* Applies Positive Product Language Canon v1.1.0 to the new public copy.

= 2.37.2 =
* Makes Cloudflare edge fallback portable on slow origins: bounded local-origin probes no longer become false functional FAILs when proof is inconclusive.
* Extends edge-aware proof to sitemap/service checks and exports public/origin proof metadata.
* Restores signed query-cost telemetry for local-origin probes. Runtime optimization behavior is unchanged.

= 2.37.1 =
* Corrects Plugin Check input warnings and makes Runtime Acceptance edge-aware: Cloudflare 403/429 self-probe blocks can fall back to a bounded local-origin proof while remaining visible as ATTENTION.

= 2.36.0 =
* Adds a read-only External page-cache authority proof to Runtime Acceptance.
* Preserves an existing native/host page cache during Optimize Now instead of activating the YPerf portable Page Cache on top of it.
* Blocks the portable Page Cache stability campaign when current Runtime evidence already proves an external page-cache authority.
* Extends cache-signal discovery to custom X-*Cache headers while excluding Cloudflare edge status and YPerf-owned headers.
* No Nginx, PHP-FPM, Redis or server configuration is changed.

= 2.35.1 =
* Corrective Page Cache lock recovery: Health Check now reaps only orphaned per-key lock files older than 120 seconds before MISS → HIT proof.
* The generated advanced-cache.php applies the same bounded stale-lock recovery so a killed writer cannot leave a permanent BYPASS-LOCK state.
* Fresh locks remain authoritative; CSS preload/runtime semantics from 2.35.0 are unchanged.

= 2.35.0 =
* Adds a bounded early preload hint for one late-discovered, same-origin active-theme stylesheet that is too large for safe inline CSS.
* Preserves the original stylesheet link, cascade order and render-blocking behavior to avoid FOUC/regression risk.
* Runtime Acceptance exports `css_preload_proof.count`.

= 2.34.7 =
* Corrects installable ZIP identity: the archive now contains the canonical `yablonsky-performance/` root directory, preventing parallel-copy activation fatals and text-domain mismatch scans.
* Keeps the 2.34.6 phase-true Optimize progress markers and active circle-with-dot UI unchanged.
* Runtime performance semantics are unchanged.

= 2.34.6 =
Optimize progress truth fix: active step mapping now follows the real phase, active stages use a circle-with-dot marker, and the installable package also shortens the upgrade notice for a clean Plugin Check.

= 2.34.5 =
Corrects Optimize Now 94% stalls by splitting accepted-profile prewarm into bounded per-URL AJAX steps after Runtime proof is committed; terminal 100% is published only after the visible warm-up finalizer. Keeps an already accepted baseline accepted if optional warm-up transport fails.

= 2.34.4 =
* Corrective Optimize Now progress patch: removes the second browser-side prewarm after the guarded server campaign already completed its bounded prewarm.
* Progress rendering now reserves 100% for explicit terminal server state, preventing a completed-looking UI while background work remains.
* Page Cache, Browser Cache, frontend optimizer, sitemap isolation, Cloudflare policy and cache safety semantics are unchanged.

= 2.34.3 =
* Decouples YPerf sitemap isolation from sitemap-producer health. Protected XML/service routes are proved BYPASS independently; broken sitemap responses are ATTENTION, not YPerf FAIL, when YPerf did not touch them.

= 2.34.2 =
* Prevents stale YPerf HIT evidence from presenting Page Cache as ON/VERIFIED when the live YPerf executor is disabled, absent or belongs to another version.
* Makes Autopilot preflight failure progress truthful: a blocked first-stage preflight no longer renders 100% with every stage green.
* No Page Cache, frontend optimization, XML/sitemap or provider runtime policy is changed by this corrective patch.

= 2.34.1 =
* Corrective patch: scopes Page Cache artifact-refresh rollback to Page Cache and sitemap-isolation gates instead of any unrelated Runtime Acceptance failure.
* Moves probe-only JavaScript delay proof to the opening head region so bounded Runtime Acceptance response sampling cannot truncate the no-op/candidate evidence.
* Sanitizes REQUEST_URI before protected-service path parsing, resolving the WordPress Plugin Check warning introduced by 2.30.0.
* Frontend optimization behavior for normal visitors and the 260 KB per-stylesheet / 420 KB total CSS safety caps are unchanged.

= 2.34.0 =
* Adds probe-only JavaScript candidate/delayed/loader evidence so zero eligible delayed scripts no longer create a false Runtime Acceptance failure.

= 2.33.0 =
* Adds probe-only CSS skip reasons such as unresolved ownership, byte cap, @import, integrity, print media or cross-origin.

= 2.32.0 =
* Adds a unique registered-handle + active-theme realpath resolver for path-hidden CSS, with no remote fetch and all existing CSP/size/@import gates preserved.

= 2.31.0 =
* Adds bounded same-origin sitemap-isolation evidence to Runtime Acceptance, including discovered child sitemap samples and separate XML/content-type attention.

= 2.30.0 =
* Adds a pre-bootstrap and WordPress-level protected-service boundary for XML/sitemaps, robots.txt, feeds, XML-RPC and OPML.
* Pre-2.30 YPerf Page Cache executors require explicit hash-guarded refresh before they count as current runtime.

= 2.24.0 =
* Optimize Now and Prewarm Now in the WordPress toolbar execute directly and return to the current admin/public URL instead of opening YPerf.
* Cloudflare-synchronized purge actions are named explicitly when the adopted edge profile is active.
* Accepted optimization campaigns now perform a bounded automatic prewarm instead of only describing one in the UI.
* Cover-hero routes can move jQuery/jQuery Migrate and their provably delayed dependency chain out of the render-blocking path; unknown inline jQuery consumers make the policy fail closed.
* Page Cache wire format is unchanged from 2.23.0.

= 2.23.0 =
* Toolbar purge actions return to the exact admin/front-end page where they were invoked instead of forcing a redirect into YPerf.
* Toolbar URL purge labels now state their scope explicitly: homepage URL in wp-admin, current URL on the public page.
* Adds a Cloudflare purge-sync verification action with visible progress and accepted/error evidence.
* Adopted Cloudflare purge can execute even when a local Page Cache executor is not active, unless the purge request itself failed or was blocked.
* Adds truthful PHP compatibility notes: minimum 7.4, live Generator runtime 8.1, release syntax validation on 8.4; full public compatibility matrix remains a GA gate.
* Frontend optimizer, prewarm behavior, Page Cache wire format and Cloudflare settings allowlist are unchanged from 2.22.0.

= 2.22.0 =
* Rebuilds Edge / Cloudflare into a responsive two-column workspace with a four-step Quick Setup guide, wrap-safe controls and mobile fallback.
* Adds visible progress feedback for Inspect / Adopt / Rollback and explicit aligned/review-needed state without changing provider authority semantics.
* Already-aligned zones can be adopted to establish YPerf ownership and purge sync without unnecessary setting rewrites.
* Corrects least-privilege token guidance to Zone Read, Zone Settings Edit and Cache Purge for the selected zone only.
* Adds Cloudflare external-service disclosure and keeps YSEO-owned crawler policy, DNS, WAF and Bot Management outside YPerf.
* Frontend performance runtime, Page Cache wire format, prewarm and Cloudflare allowlisted settings remain unchanged from 2.21.0.

= 2.21.0 =
* Adds an optional Cloudflare Performance Adapter while preserving provider-independent local YPerf operation. API credentials are encrypted at rest and never exposed in admin HTML, reports or telemetry.
* Uses a deliberately narrow performance authority contract: YPerf may inspect edge delivery, explicitly adopt Rocket Loader OFF and HTTP/3 ON, prove changes by API read-back, snapshot previous values and perform drift-safe rollback.
* Synchronizes successful YPerf URL/host purge operations to Cloudflare only after explicit provider adoption. URL purges are batched and same-origin bounded; YPerf does not use purge-everything as the normal bridge.
* Early Hints, Speed Brain, APO and effective response compression are observe-only in this release. Compression execution remains native to Cloudflare/web-server; YPerf does not revive the legacy Brotli toggle.
* Establishes a hard product boundary with Yablonsky SEO: YPerf never reads or writes Cloudflare AI Search, Training, Agent/User, Bot Preference Sync, robots.txt, Bot Management, WAF, DNS or crawler-policy settings.
* Recommended Cloudflare token scope is least privilege: Zone Read, Zone Settings Read, Zone Settings Edit and Cache Purge. Bot Management, WAF, DNS and account-administrator permissions are intentionally outside the YPerf contract.
* Page Cache wire/runtime format is unchanged from 2.20.0. Existing local performance behavior, Autopilot Hot Start and rollback semantics remain intact until the administrator explicitly adopts the Cloudflare adapter.

= 2.20.0 =
* Successful Optimize Now campaigns now finish with the existing bounded same-origin Prewarm operation, so accepted optimization is followed by a verified hot-start pass rather than handing cold public cache population to the first visitor.
* Prewarm remains outside the write/rollback boundary: if warmup cannot complete, the already accepted YPerf profile is preserved and the operational failure remains visible.
* Runtime Acceptance now records provider-neutral edge delivery evidence: effective compression, Cloudflare presence, Rocket Loader presence, Speed Brain header and HTTP/3 advertisement.
* YPerf owns policy, proof and conflict detection while edge/native transport compression remains provider-independent execution.

= 2.19.0 =
* Uses the exact 2.18.0 live-green baseline and targets the two remaining evidence-backed mobile variance sources: hidden-path stylesheet identity and double scheduling with Cloudflare Rocket Loader.
* Adds an early style_loader_src capture pass that reads only same-origin locally owned CSS under the existing file-size and @import gates. The later full-document pass still owns CSP, integrity, media and total-byte approval before any inline delivery.
* Adds data-cfasync=false only to the YPerf controller and YPerf-delayed script tags, preventing Rocket Loader from racing YPerf's own delayed execution policy while leaving Cloudflare configuration and unrelated scripts untouched.
* Keeps hero fetchpriority/eager/async decode and the 2.18 LCP-observed scheduler unchanged; no new decoding experiment, global CSS async rewrite or third-party activation change is introduced.
* Localization remains 36 language families / 41 locale variants with EN source + RU complete; later mass localization/UI-fit remains on yablonsky.io.

= 2.18.0 =
* Built from the last live green 2.16.0 baseline after 2.17.0 failed Plugin Check and did not improve the supplied mobile PSI cluster.
* Captures trusted local stylesheet bytes while WordPress still exposes the original registered handle/href, then reuses those bytes during the full-document inline pass even if a security/path-hiding layer rewrites the public URL.
* Keeps CSP, same-origin, local realpath, media, integrity, @import and byte-cap fail-closed gates; no remote CSS fetch and no raw stylesheet tag injection are added.
* Preserves the proven 2.14/2.16 LCP-observed heavy-script scheduler, WooCommerce private-route exclusions, Page Cache authority and rollback boundaries.
* Localization scope remains 36 language families / 41 locale variants with EN source + RU complete; later localization/UI-fit belongs on yablonsky.io.

= 2.16.0 =
* Recovery release built from the exact 2.14.0 source baseline after 2.15.0 live PSI evidence regressed from the 2.14 seven-run cluster to 89, 90, 90, 89.
* Rejects the 2.15.0 SVG synchronous-decode experiment and restores the exact 2.14 frontend scheduling/first-paint behavior.
* Discards the unproven 2.15 stylesheet ownership/import delta and keeps the stable LCP-observed script gate.
* Corrects the later localization/UI-fit host reference to yablonsky.io.

= 2.14.0 =
* Evidence-driven stability closeout candidate built from exact 2.13.0 after five new mobile PSI captures scored 88, 99, 90, 89 and 89 (median 89). Four slower runs kept LCP around 3.1-3.3 s while the 99 run reached 1.7 s.
* Fixes the scheduler race exposed by those reports: a missing LCP observation is no longer interpreted as an already-quiet 2.6 s window. Passive heavy-script release now requires a real PerformanceObserver LCP candidate plus 2.5 s quiescence; first user intent still releases immediately and the existing 8 s hard fallback remains.
* Keeps analytics in its separate later group so it cannot drag the heavyweight WordPress/WooCommerce dependency closure into the first-paint window.
* Adds a last-stage stylesheet-handle marker at PHP_INT_MAX so path-hiding/security plugins may rewrite the public CSS URL without destroying the WordPress ownership identity needed by the full-document inline pass.
* The marker is non-rendering, consumed before response completion, and does not bypass existing CSP, same-origin, realpath ownership, per-file/total byte-cap, integrity, media or @import fail-closed gates.
* Preserves final rewritten public CSS href as the url() resolution base while using the WordPress handle only to prove the local source file.
* Adds the new scheduler/style-marker policies as explicit profile-owned options. Updating alone does not silently adopt them; Optimize Now intentionally applies them and existing portable settings imports default the new behaviors OFF.
* Keeps the Page Cache wire/runtime format compatible with 2.13.0 and preserves singular cache authority, W3TC migration finality, WooCommerce private-route exclusions and user-controlled third-party plugin activation.
* Localization scope remains 36 language families / 41 locale variants with EN source + RU complete. The mass translation train is intentionally deferred until performance/cross-site validation, with yablonsky.io suitable for the later localization/UI-fit phase.

= 2.13.0 =
* Evidence-driven corrective release after exact 2.12.0 passed Plugin Check and Optimize Now but returned new mobile PSI samples at 79 and 88, so 2.12 is not treated as performance closeout.
* Marks the original WordPress stylesheet handle and same-origin href at style_loader_tag priority 1, before later path-hiding/security URL rewriting; the final full-document output pass still owns CSP, filesystem, byte-cap and @import safety gates.
* Uses the preserved original href only for local file ownership/read resolution while keeping the final public href as the base for rewritten CSS url() references.
* Splits dependency-closed delayed scripts into heavy and analytics groups so analytics cannot force the entire delayed set back into the first-paint/main-thread window.
* Replaces the 2.12 paint-idle release with an LCP-quiescence gate: heavyweight scripts wait for 2.5 s without a new LCP candidate plus idle time, but first real user intent still releases all delayed dependencies immediately.
* Removes mouse movement as a release trigger; pointer/touch/keyboard/scroll intent remains immediate. Heavy and analytics groups retain bounded hard fallbacks.
* Expands Site & Server environment status geometry so READY, AVAILABLE, ATTENTION, BLOCKED and UNKNOWN badges remain single-line at the accessibility font floor.
* Keeps Page Cache executor format compatible with 2.12.0; no new page-cache authority, W3TC migration, Redis adoption, server write or third-party activation-status change is introduced.
* Keeps the reconciled localization registry at 36 language families / 41 locale variants. Translation completion remains honestly 2/36 until the dedicated mass localization train produces real PO/MO catalogs and QA.

= 2.12.0 =
* Uses the accepted 2.11 seven-run mobile PageSpeed distribution as the evidence baseline for a focused stability pass.
* Resolves path-rewritten same-origin CSS only when one unique WordPress-registered stylesheet matches a three-segment suffix; ambiguous ownership fails closed and no remote CSS fetch is introduced.
* Adds an explicitly adopted paint-idle release policy for already delayed heavyweight scripts while preserving immediate first-user-intent execution and a bounded fallback.
* Keeps the 2.11 Page Cache executor format compatible so the upgrade does not create or force a second page-cache authority.
* Reconciles localization scope to 36 first-class language families / 41 canonical WordPress locale variants, splitting Simplified and Traditional Chinese and adding Hindi, Bengali, Urdu, Turkish and Korean.
* Keeps localization truth strict: only English source and the real Russian PO/MO pack are marked complete; untranslated target languages are not faked as complete.

= 2.11.0 =
* Adds bounded same-origin first-paint CSS inlining on eligible WordPress Cover hero routes, preserving stylesheet order, media semantics, relative asset URLs and CSP fail-closed behavior.
* Moves the LCP preload to the earliest safe head position and restores asynchronous image decoding while keeping fetch priority high.
* Extends the heavy-script paint gate so non-critical WordPress/WooCommerce execution cannot race the first meaningful paint on slower Lighthouse/mobile workers.
* Rebuilds Expert Server Diagnostics as three balanced overview cards with one full-width expanded workspace and accordion behavior.
* Fixes the remaining WordPress.org upgrade-notice warning for the historical 2.9.0 notice.
* Keeps Page Cache, Browser Cache, WooCommerce private routes, rollback and server/native authority boundaries unchanged.

= 2.10.0 =
* Adds a paint-aware heavy-JS release gate for WordPress Cover hero routes to reduce the observed 60–97 mobile PageSpeed variance without sacrificing LCP priority.
* Delayed scripts release after the prioritized hero load plus two animation frames, immediately on user intent, or after a bounded fallback.
* Keeps Page Cache, Browser Cache, WooCommerce private-route exclusions, rollback and third-party/server authority boundaries unchanged.
* Shortens the current upgrade notice to stay under the WordPress.org 300-character limit.

= 2.9.0 =
* Adds real in-app Optimize Now progress from the same guarded campaign state used for preflight, apply, proof, regression confirmation and rollback.
* Adds one bounded same-origin WordPress Cover hero priority hint, responsive post-hero WooCommerce image hints and paint-first adaptive heavy-JS scheduling on hero routes.
* Preserves Page Cache, Browser Cache, WooCommerce private-route safety, rollback and server/native authority boundaries.

= 2.8.1 =
* Fixes the 2.8.0 Page Cache compatibility-chain regression that incorrectly rejected the existing YPerf 2.3.1 advanced-cache.php artifact during preflight repair.
* Optimize Now can now recognize the owned compatible executor, refresh it to the current 2.8.1 artifact, prove MISS → HIT, and continue only after a clean Health Check.
* No third-party cache, Nginx, Redis, PHP-FPM, plugin activation status or frontend policy is changed by this patch.

= 2.8.0 =
* Closes the Frontend Delivery Engine with dependency-closed scheduling, late DOM/load listener compatibility, Runtime proof, settings export/import and fail-closed rollback.
* Existing legacy Frontend Optimization installs stay on their previous behavior until Optimize Now explicitly adopts the new policies.

= 2.7.0 =
* Adds bounded late execution for Google gtag/GTM on eligible anonymous public routes, paired with its WordPress inline configuration and preserving the consent bootstrap.

= 2.6.0 =
* Adds interaction-aware delayed execution for selected heavyweight WordPress/WooCommerce JavaScript families while leaving jQuery, theme navigation, JSON-LD and module scripts untouched.

= 2.5.0 =
* Integrates the route-aware frontend policy into Autopilot, settings snapshots, Page Cache invalidation and automatic rollback.

= 2.4.0 =
* Introduces the reversible Frontend Delivery Engine foundation and WooCommerce transactional-asset route gate while preserving the existing emoji optimization.

= 2.3.2 =
* Optimize Recommended uses the full available admin width on wide screens; no cache or migration execution logic changed.

= 2.3.1 =
* Keeps existing third-party/unknown object-cache.php conflicts as an informational review state instead of a global failure notice.
* Shows a clear existing-cache state in normal Object Cache controls and leaves the file untouched.
* Shortens WordPress.org upgrade notices to the 300-character limit.
* Preserves accepted Page Cache and Autopilot behavior from 2.3.0.

= 2.3.0 =
* Promotes Cache to primary navigation with direct controls first, operations second and the technical System Map third.
* Adds YPerf admin-bar quick actions for Optimize, Cache controls, current/home-page purge, Page Cache purge, all-cache purge and Prewarm.
* Makes W3 Total Cache migration WordPress.org-compliant: YPerf prepares a reversible handoff, but only the administrator may activate or deactivate W3TC from the normal Plugins screen.
* Removes all direct `activate_plugin()` / `deactivate_plugins()` calls from YPerf migration and rollback paths.
* Reworks Migration into a balanced one-column journey/history layout and keeps completed migration visible.
* Reconciles compatible live YPerf Page Cache drop-ins with stale authority metadata and keeps Repair & verify fail-closed on ownership ambiguity.
* Fixes the Plugin Check output-escaping, settings-input-sanitization and translator-comment findings reported against 2.2.0.

= 2.2.0 =
* Added real one-click optimization, direct cache controls, completed Migration history and portable settings backup/restore.

Full historical release notes are shipped in `changelog.txt`.
