=== Sharpr ===
Contributors: kendsnyder
Tags: sharpr, thought leadership, content curation, communication platform, agency, insight, Sharpr, rightintel
Requires at least: 3.2
Tested up to: 4.6
Stable tag: 4.4.2
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/old-licenses/gpl-2.0.html

The Sharpr Wordpress Plugin allows you to push posts from Sharpr to your WordPress blog. (Sharpr is formerly Right Intel)

== Description ==

Sharpr allows you to gather and push insights to your team and your clients wherever they are.

Simply connect your blog to Sharpr by logging into WordPress and using the Settings > Sharpr page. Then editors can visit your hub dashboard in Sharpr and click the "Send to blog..." option on the gear menu next to each post. It will give you a chance to edit the post content and assign categories and tags.

The Sharpr post will then become visible on your WordPress blog including the image and insight bubble.

== Installation ==

= Requirements =

*   PHP 5.3+
*   WordPress 3.2 or newer
*   Permalinks enabled under Settings > Permalinks
*   URL rewrite (e.g. via .htaccess) to make all traffic available to plugins
*   WordPress permission to create database tables

= WordPress Directory Installation =

This plugin is available in the WordPress directory listing and can be installed from the WordPress admin panel under Plugins > Add New.

= Manual Installation =

Unzip and upload the right-intel folder to the /wp-content/plugins/ directory.

= Testing Your Installation =

1. Test installation by visiting http://yourdomain.example.com/right_intel/receiver.php; you should see JSON output, not a 404 or a blog page.
1. Login into WordPress and activate the plugin on the "Plugins" menu
1. Go to Settings > Sharpr to connect the blog to Sharpr

== Frequently Asked Questions ==

= Can I customize the color or size of the insight bubble? =

Yes. Under Settings > Sharpr, there are display options. You can also override the styling using CSS.

= How will my posts look if I deactivate the Sharpr plugin? =

The insight text will look like a normal paragraph without a bubble. The inline image may cause the paragraph to look strange; you can hide or style the inline image in your theme.

= Where can I get help and support for this plugin? =

Please send an email to support@sharpr.com.

= Does Sharpr store my WordPress password? =

No. Passwords are not stored in Sharpr or WordPress. When a Sharpr instance is first created, authentication tokens are generated and stored for later use.

= Does WordPress store my Sharpr password? =

No.

== Screenshots ==

1. How Sharpr posts look on a WordPress blog.
2. Go to the Settings > Sharpr page to connect your blog to Sharpr.
3. On the hub dashboard, hover over the gear icon next to a post and click "Send to blog...".
4. Before the post is sent to WordPress you can edit the title, insight bubble text, description and assign WordPress categories and tags. Other WordPress options such as author and publish date are available as well.

== Changelog ==

= Version 4.4.2 - February 9, 2016 =
* Add option for Georgia Italic font
= Version 4.4.0 - January 31, 2016 =
* Right Intel is now Sharpr
* Certify as working with WordPress 4.4
= Version 3.9.1 - March 11, 2015 =
* Fix image conversion for posts without images
= Version 3.9.0 - February 6, 2015 =
* Support for shortcodes
= Version 3.8.4 - January 21, 2015 =
* Built-in options for altering insight bubble styling including CSS-only bubbles
* Support for media library
* Improved UI in the Sharpr Platform
* Verfied support for WordPress 4.x *
= Version 3.7.1 - November 8, 2013 = 
* Verfied support for WordPress 3.7 *
* Added instructions for testing installation *
= Version 3.5.5 - July 5, 2013 = 
* Fix image floating when image is not linked *
* Add right-intel-post as CSS class to body *
= Version 3.5.4 - July 2, 2013 = 
* Fix for bubble color in some situations *
= Version 3.5.3 - June 28, 2013 = 
* Support for featured image *
* Adds post images to media library for future use *
= Version 3.5.0 - April 20, 2013 =
* Verified support for WordPress 3.5 *
* Allow PHP allow_url_fopen to be disabled *
* Force CSS updates without users needing to Shift+refresh *
* Support sites using CSS3 box models *
= Version 3.4.5 - December 5, 2012 =
* Fix potential conflicts with other plugins
= Version 3.4.3 - November 30, 2012 =
* Click an image to view original, full-size image
= Version 3.4.2 - November 13, 2012 =
* Initial public version
