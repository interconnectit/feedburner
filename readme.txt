=== FeedBurner Advanced ===
Contributors: sanchothefat
Tags: FeedBurner, category, tag, FeedSmith, custom post type, custom taxonomy
Requires at least: 3.0.0
Tested up to: 3.3.2
Stable tag: 1.0.1

== Description ==

This is a plugin originally authored by <a href="http://www.orderedlist.com/">Steve Smith</a>. It detects all ways to access your original WordPress feeds and redirects them to your FeedBurner feed(s). The modification allows redirecting feeds for any post type archive and any terms in any taxonomies.

Modification are built on top of work by Steve Smith and Jiayu (James) Ji.

== Installation ==

1. Download the plugin and expand it to the plugin folder(wp-content/plugins/).

2. Login into the WordPress administration area and go to the plugin page.

3. Click the activate link of the 'Feedburner feedsmith extend' plugin.

4. You can then go to Settings -> FeedBurner to configure all your FeedBurner feeds.



== Frequently Asked Questions ==

= 1. How do I get links to feeds for taxonomy terms =

Normally, the format should be like the link below:

http://yoursite/index.php/tag/tag_slug/feed/

or

http://yoursite/?feed=rss2&tag=tag_slug

It uses the tag_slug parameter to define which tag should it be.

There is a native method provided by WordPress which allows you to get the term's feed link easily:

`$link = get_term_feed_link( $term_id, $taxonomy, $feed_type = '' );
`
