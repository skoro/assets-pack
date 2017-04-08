=== Assets Pack ===
Tags: assets, js, css, minification
Requires at least: 3.7
Tested up to: 4.7.3
Stable tag: 0.1.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Allows to aggregate scripts and styles into assets. Supports minification.

== Description ==

This plugin can include many scripts (js) and styles (css) into two assets files.

== Installation ==

1. Unpack the entire contents of this plugin zip file into your `wp-content/plugins/` folder locally
1. Upload to your site
1. Navigate to `wp-admin/plugins.php` on your site (your WP Admin plugin page)
1. Activate this plugin

OR you can just install it with WordPress by going to Plugins >> Add New >> and type this plugin's name

== Frequently Asked Questions ==

= Can I skip some scripts or styles into including ? =

Yes. You may enter script/style name in assets settings plugin page. For example,
"jquery-core" means ignore including jquery.js into assets.

= How can I know which script/style name ? =

Open Settings >> Assets and enable Debug checkboxes. After this, *.js.debug,
*.css.debug files will be created. Open them and you can see which scripts
and styles are included.

== Screenshots ==

1. This screen shot description corresponds to screenshot-1.(png|jpg|jpeg|gif). Note that the screenshot is taken from
the /assets directory or the directory that contains the stable readme.txt (tags or trunk). Screenshots in the /assets
directory take precedence. For example, `/assets/screenshot-1.png` would win over `/tags/4.3/screenshot-1.png`
(or jpg, jpeg, gif).
2. This is the second screen shot

== Changelog ==

= 0.1 =
* First version.
