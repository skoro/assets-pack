=== Assets Pack ===
Tags: assets, js, css, minification
Requires at least: 3.7
Tested up to: 4.8.1
Stable tag: 0.1.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

This plugin allows you to combine javascripts scripts and css styles into bundles.

== Description ==

This plugin helps you to increase network performace on your site. As you can see
in browser's network inspector a general page can contains a lot of resources
such as javascripts (jquery, sliders, so on) and styles. Each time those resources
are pulled from a server and increase the whole time of page loading.
This plugin will combine them into one bundle for javascripts and one bundle for
css styles. And instead of loading lots of resources you only need two those bundles.

== Installation ==

1. Unpack the entire contents of this plugin zip file into your `wp-content/plugins/` folder locally
1. Upload to your site
1. Navigate to `wp-admin/plugins.php` on your site (your WP Admin plugin page)
1. Activate this plugin

OR you can just install it with WordPress by going to Plugins >> Add New >> and type this plugin's name

== Frequently Asked Questions ==

= Can I skip some scripts or styles into combining ? =

Yes. You may enter script/style name in assets settings plugin page. For example,
"jquery-core" means ignore including jquery.js into bundle.

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
