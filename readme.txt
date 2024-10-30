=== Minor Improvements ===
Contributors: Minor
Tags: recaptcha, google, protect, update, youtube, xmlrpc
Requires at least: 4.6
Tested up to: 5.9
Stable tag: 1.8
Requires PHP: 7.2
License: GPLv3
License URI: http://www.gnu.org/licenses/gpl-3.0.html
Donate link: https://www.paypal.me/NovaMi

Package of several minor improvements. Why to install several plugins? You need this one only.

== Description ==
Protect your WordPress against spam comments and brute-force attacks (login, registration, comments, new password) thanks to modern Google reCAPTCHA v3.

Change base author slug, activate auto updates for theme, plugins and WordPress core.

Possibility to enable/disable www field in the comment form, XML-RPC and email notifications about auto update (WordPress 5.5+).

With this plugin you will be able to show videos from YouTube on full width thanks to shortcodes.

== Installation ==
1. Upload the plugin files to the "/wp-content/plugins/minor-improvements" directory, or install the plugin through the WordPress plugins screen directly.

2. Activate the plugin through the "Plugins" screen in WordPress.

3. Use the Settings => Improvements screen to configure the plugin.

== Frequently Asked Questions ==
= Why to install this plugin? =
No ads and any other needless changes in the WordPress. Free for all with all functions, no premium account policy!

= How to disable this plugin? =
Just use standard Plugin overview page in WordPress admin section and deactivate it or rename plugin folder /wp-content/plugins/minor-improvements over FTP access.

= How to show YouTube video =
Simply thx to shortcode. Put this: `[mi_yt_last channel='WorldofWarshipsOfficialChannel']` into your post to show last video from channel "WorldofWarshipsOfficialChannel" or `[mi_yt id='byF6eFbNy2M']` to show specific video by his id. Video is inserted like responsive iframe on full width and without user tracking. No API needed.

== Screenshots ==
1. Options page - Google reCAPTCHA
2. Options page - Auto updates
3. Options page - WWW field in comments
4. Options page - Change author slug

== Changelog ==
= 1.8 =
* New: Disabled XML-RPC (possibility to enable)
* Bugfix: reCAPTCHA verification

= 1.7 =
* Bugfix: Get last YT video by channel

= 1.6 =
* New: Disabled email notification about auto updates (possibility to enable)

= 1.5 =
* Warning: After update you have to put new reCAPTCHA keys v3 to activate that!
* Warning: Removed possibility to extend header - e.g. about GA tracking code!
* New: Upgraded reCAPTCHA from v2 Checkbox to v3
* New: Shortcodes to show video from YouTube in responsive mode, full width and no tracking

= 1.4 =
* Bugfix: No more unnecessary loading reCAPTCHA on the other pages
* Bugfix: No more reCAPTCHA window over Clef waves (if you are using Clef plugin) on the login page

= 1.3 =
* Warning: reCAPTCHA verification on the Add new comment form for logged in users has been removed
* New: Language settings of reCAPTCHA is based on WordPress locale now
* New: Default WordPress submit buttons are disabled until reCAPTCHA isn't solved
* New: Added reCAPTCHA for Resset password form
* Bugfix: reCAPTCHA verification just on the standard WordPress pages (unmodified by plugins/templates)

= 1.2 =
* Bugfix: reCAPCTHA will be required only If the form has been submitted

= 1.1 =
* Bugfix: If you forget to disable some previous reCAPTCHA plugin
* Update: Screenshots updated

= 1.0 =
* New: Initial release!