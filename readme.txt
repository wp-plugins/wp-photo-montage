=== WP Photo Montage ===

Contributors: TWDRichard
Tags: image, images, montage
Requires at least: 3.5
Stable tag: 1.0.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

An easy-to-use image montage creator

== Description ==

WP Photo Montage creates a composite montage of a range of images into an (hopefully) attractive montage.
A shortcode is used to display a montage anywhere in your WordPress posts or pages.
get-the-image plugin is used so that must be installed too.
A tmp folder is created to hold temporary images. The folder must be readable and writeable by WordPress.

== Installation ==

1. Upload `wp-photo-montage` to the `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Add the appropriate code to your template files e.g.
	[photo_montage category="portfolio" width="600" height="400" background="#fff" columns="3" rows="2"]
	to display a 3 x 2 montage of images from posts in the "portfolio" category

== Screenshots ==

1. Sample photo montage.

== Changelog ==

### Version 1.0.0 ###

* First release

