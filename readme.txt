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

= 1.0.1 =
* Self-updater now prefers a packaged .zip release asset over the source zipball, so manual installs and automatic updates use the identical artifact.

= 1.0.0 =
* Initial release: meta tags, Open Graph, Twitter Card, JSON-LD Organization/WebSite schema, and virtual llms.txt.
