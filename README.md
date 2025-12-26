HM Pro Çeviri

Custom, SEO-friendly, URL-based multilingual system for WordPress + WooCommerce.
Built to avoid third-party plugin lock-in (WPML / TranslatePress) and designed for performance, control, and scalability.

🎯 Project Goals

URL-based multilingual structure (/en/, /de/, /ro/, etc.)

Default language without prefix (e.g. Turkish → /)

Google Translate API for initial translations (later commit)

Manual override for all translated content

Full SEO compatibility (Rank Math, hreflang, canonical)

WooCommerce compatible

No JS-only translation (real URLs, real indexable pages)

🧱 Architecture Overview

Language handling: Cookie + URL prefix

Routing: Prefix stripping via parse_request (no heavy rewrite rules)

Storage: Language-specific post meta

Switcher: Shortcode + header/footer integration

SEO: hreflang + canonical (planned)

Auto-detect: Browser language (planned)

📦 Current Features (Completed)
✅ 1. Plugin Skeleton

Namespaced, modular structure

Clean init flow

Activation / deactivation hooks

✅ 2. Admin Settings Page

Location:
WP Admin → Settings → HM Pro Çeviri

Features:

Default language selection

Enabled languages (checkbox)

Language catalog (EU + neighbors + Kurdish variants)

Shortcode info displayed

Shortcodes:

[hm_lang_switcher]
[hm_lang_switcher style="dropdown"]

✅ 3. Language Catalog

Currently supported (expandable):

Turkish (tr)

English (en)

German (de)

French (fr)

Italian (it)

Spanish (es)

Portuguese (pt)

Dutch (nl)

Polish (pl)

Romanian (ro)

Bulgarian (bg)

Greek (el)

Russian (ru)

Ukrainian (uk)

Georgian (ka)

Armenian (hy)

Azerbaijani (az)

Persian / Farsi (fa)

Arabic (ar)

Hebrew (he)

Serbian (sr)

Croatian (hr)

Bosnian (bs)

Albanian (sq)

Hungarian (hu)

Czech (cs)

Slovak (sk)

Slovenian (sl)

Swedish (sv)

Norwegian (no)

Danish (da)

Finnish (fi)

Kurdish Kurmanji (ku)

Kurdish Sorani (ckb)

✅ 4. Language Switcher

Works via shortcode

Dropdown or inline style

Stores selection in cookie

Supports header/footer placement (Astra, Gutenberg, Elementor)

✅ 5. URL-Based Language Handling (IMPORTANT)

No rewrite rules used for routing.

Instead:

Language prefix is detected in parse_request

Prefix is stripped before WordPress resolves the page

Examples:

/test/        → Turkish
/en/test/     → English
/en/          → English homepage


This avoids:

404 issues

WooCommerce conflicts

Category/product resolution problems

🔧 Technical Details
Language Detection Priority

URL prefix (/en/)

Query param (?hm_lang=en)

Cookie

Default language

Core Router Logic

/en/test/ → strip en → resolve test

Language stored via cookie

Global language available as:

$GLOBALS['hmpc_current_lang']

🧪 Manual Testing Checklist

After activation:

Save permalinks once
Settings → Permalinks → Save

Test URLs:

/test/
/en/test/
/en/
/test/?hm_lang=en


Expected:

Correct page loads

Language persists via cookie

No blog index fallback

🧾 Git Commits (So Far)
git commit -m "feat(hmpc): initial plugin skeleton and settings page"
git commit -m "chore(hmpc): expand language catalog (EU + neighbors + Kurdish variants)"
git commit -m "feat(hmpc): add language switcher shortcode"
git commit -m "fix(hmpc): handle /lang/ URLs by stripping prefix in parse_request"

🚧 Planned Next Steps
🔜 Next Commit – SEO Layer

hreflang tags

canonical URLs

Rank Math integration

🔜 Content Translation Layer

Language-specific post meta:

_hmpc_en_title
_hmpc_en_content

🔜 Google Translate Integration

One-click auto translate

Cached results

Manual edit after translation

🔜 WooCommerce Support

Product title

Short description

Long description

Categories

Attributes

🔜 Auto-Detect Visitor Language

Based on browser headers

First visit only

Enabled languages only

Bot-safe (Google excluded)

🧠 Design Principles

No vendor lock-in

No JavaScript-only translations

SEO first

Predictable URLs

Minimal performance overhead

Developer-friendly

🧭 Resume Development

If conversation context is lost:

Read this README

Check last commit

Continue from Planned Next Steps

🛠️ How to Add This README
touch README.md
# paste content
git add README.md
git commit -m "docs(hmpc): add comprehensive project README"
git push
