=== Plague Album Reviews ===
Contributors: jazzsequence
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=AWM2TG3D4HYQ6
Tags: plague music, album reviews, music reviews, netlabel, custom post type
Requires at least: 3.0
Tested up to: 3.7.1
Stable tag: 2.0.0

Add album reviews to your blog or music site.

== Description ==

An album review plugin for WordPress, brought to you by [Plague Music](http://plaguemusic.com). Part of the Plague Netlabel-in-a-Box.

Creates a new post type for Album Reviews, which display in the main blog feed. Single reviews automagically display new meta information like release date, rating, track list, etc.

**Coming Soon**
- Shortcode to display an archive of reviews
- Options page to enable/disable home page reviews & any other future options

== Installation ==

1. Upload the plugin to the `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress

== Frequently Asked Questions ==

Nothing to see here (yet).

== Screenshots ==

Nothing to see here (yet).

== Changelog ==

= 2.0 =
* added custom columns to reviews page
* added support for current (legacy) and new plague release post type
* added reviews to the home page and filtered the title to display Review: before the album title
* replaced references to "featured image" with "album cover"
* wrapped the review in a div so it can sit alongside the thumbnail without wrapping
* added release date meta value and jquery-ui datepicker for date
* added new `/inc/functions.php` file which adds `get_the_genres`, `get_the_artist_list` and `get_the_labels`
* added `the_content` filter to display review meta data on review posts
* added artist slug to permalinks

= 1.0 =
* Initial build for plaguemusic.com