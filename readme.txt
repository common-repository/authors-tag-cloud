=== Plugin Name ===
Contributors: kegentile
Donate link:
Tags: author, tag,tag-cloud
Requires at least: 3.0
Tested up to: 3.0
Stable tag: 1.05

Creates a template tag to create an author specific tag cloud and creates a new permalink structure to drill down into content by author and tag.

== Description ==

This plugin will create a tag cloud for a specific author.  This will allow a visitor on your site to drill deep down into an author’s tagged specific content.  

It uses all of the same parameters as wp_tag_cloud(), but adds an additional one for the author_id.  When the author_id is passed an integer of a published author that has tagged his or her posts, a tag cloud will be generated.

It will also create a new permalink structure on your site of

www.yoursite.com/author/author-slug/tag/tag-slug

***Note: that you must be using any custom permalink structure for this plugin to function.  Also, custom taxonomies is currently not functioning with this version, please look forward for this in a later version.

== Installation ==

Before you install this plugin, the site you are planning to use it on must use a custom permalink structure.  This plugin will not function on the standard WordPress install and use it you must change your permalink settings.

1. Download and upload these files into `/wp-content/plugins/` directory
2. In the WordPress Admin goto plugins and activate this plugin
3. class the function like this

(function_exists('author_tag_cloud')){
    author_tag_cloud( array('author_id' => 1) );
}

You can read more about the parameters this function will accept here: http://www.refactord.com/plugins/author-tag-cloud#parameters

== Frequently Asked Questions ==

If you have any question please post them here: http://www.refactord.com/plugins/author-tag-cloud

== Changelog ==

= 1.0 =
The plugin has been released.  Still needs support for custom taxonomies and only functions on sites using customs permalink structures.

== Upgrade Notice ==

= 1.05 =
Version 1.0 had a debugging option still active which showed the rewrite rule