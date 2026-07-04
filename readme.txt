=== Maota Metamarkup ===
Contributors: maota
Tags: seo, ai, llm, structured-data, json-ld, open-graph
Requires at least: 6.4
Tested up to: 6.7
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Maps your site identity and organization context into meta tags, Open Graph tags, JSON-LD structured data, and a virtual llms.txt so search engines and AI agents understand who you are and what your site provides.

== Description ==

Maota Metamarkup adds one settings page (Settings > Maota Metamarkup) with three sections:

* **Organization Identity** - legal name, type, logo, description, contact details, address, social profiles, founding date. Feeds a schema.org Organization + WebSite JSON-LD graph on every page.
* **Agent Behavioral Controls** - a site-wide robots meta directive (index/noindex, follow/nofollow) read by both search engines and AI crawlers.
* **Dynamic Context Fields** - a short summary, primary offerings, target audience, knowledge topics, a last-reviewed date, and freeform notes for AI agents. Feeds structured data and the virtual /llms.txt file.

Your existing Site Title, Tagline, Site Icon, and Site Logo (Settings > General / the Customizer) are read automatically and used as fallbacks throughout.

== Changelog ==

= 1.2.1 =
* Fix: the settings page now correctly follows the WPML admin-bar language selector (reads the ?lang= admin URL parameter), so each language's content fields are edited and saved separately. The editing-language notice now shows the language code.

= 1.2.0 =
* Translations are now edited directly on the settings page: switch language with the WPML admin-bar selector and enter language-specific content (description, summary, offerings, topics, AI notes). Values are stored per language in the plugin and output in the matching language. Replaces the 1.1.0 String Translation approach. No effect on single-language sites.

= 1.1.0 =
* Multilingual output via WPML. Global text fields (description, summary, offerings, topics, AI notes) become translatable in WPML > String Translation, and meta tags, JSON-LD, and llms.txt output in the current language. Adds og:locale + og:locale:alternate, and a language-versions list in llms.txt. No effect on single-language sites.

= 1.0.1 =
* Self-updater now prefers a packaged .zip release asset over the source zipball, so manual installs and automatic updates use the identical artifact.

= 1.0.0 =
* Initial release: meta tags, Open Graph, Twitter Card, JSON-LD Organization/WebSite schema, and virtual llms.txt.
