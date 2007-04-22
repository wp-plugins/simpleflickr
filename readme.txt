=== SimpleFlickr ===
Contributors: joshgerdes
Donate link: http://www.joshgerdes.com/blog/projects/simpleflickr-plugin/
Tags: images, flickr, simpleviewer
Requires at least: 2.0
Tested up to: 2.1.3
Stable tag: 1.2.2

This plugin allows you to embed a Simpleviewer Flash Object integrated with a Flickr account.

== Description ==

This is a plugin for Wordpress that I wrote which allows you to embed a flickr integrated 
simpleviewer into your Wordpress site.  It is my first attempt at a Wordpress plugin so 
please let me know if you have any issues using it.  

Please visit [the official website](http://www.joshgerdes.com/blog/projects/simpleflickr-plugin/ "SimpleFlickr") for the latest information on this plugin.

== Installation ==

1.	Extract all the contents of the plugin archive into your `wp-content/plugins/simpleFlickr` directory.
1.	Go to your wordpress admin pages and click on `Plugins` and activate the plugin called `simpleFlickr`.
1.	Go to your wordpress admin and click on `Options` then `simpleFlickr`.
1.	Set your configuration option values and click `update options` to save.
1. Add `<simpleflickr>` tag to your page or post with the set or group attribute defined.  (Please see *Usage* section for more details.)

== Frequently Asked Questions ==

= Where can I get more information? =

Please visit [the official website](http://www.joshgerdes.com/blog/projects/simpleflickr-plugin/ "SimpleFlickr") for the latest information on this plugin.

= I love this plugin! How can I show the developer how much I appreciate his work? =

Please visit [the official website](http://www.joshgerdes.com/blog/projects/simpleflickr-plugin/ "SimpleFlickr") and let him know your care.

== Screenshots ==

1. An Example of how SimpleFlickr displays in a page or post.
2. The options configuration page for the SimpleFlickr plugin.

== Release Notes ==

*Version 1.2.2*:

*	Fixed compatibility with 'All in One SEO Pack' Plugin
*	Fixed plugin directory name dependency.  Directory can now be named anything.
*   Added simpleFlickr tag version to plugin. Now SIMPLEFLICKR, simpleflickr, and simpleFlickr available. 
*   Added group option.  Now can display a selection photos from a group.  The 'group' attribute takes precedence over 'set' attribute.

*Version 1.2.1*:

*	Modified flickrviewer to use check for curl or fopen and use either if available
*	Updated instructions to explain the installation in more detail

*Version 1.2*:

*	Modified flickrviewer to use curl instead of fopen

*Version 1.1*:

*	Added default parameters to options configuration page

*Version 1.0*:

*	Initial release tested only on version 2.1 of Wordpress
*	Using version 1.8 of SimpleViewer
*	Using a modified version 1.1 of Flickrviewer

== Requirements ==

* 	Wordpress 2.x or higher <http://wordpress.com/>
* 	A Flickr API Key <http://www.flickr.com/services/api/keys/>

== Usage ==

After the plugin has been installed, activated, and the configuration options have been set you will 
be able to a simpleviewer object to you content with the `<simpleflickr>` tag.  

Here is an example with minimal options:
  
`<simpleflickr set="72157594408754918" />`

Here is an example with all custom options:

`<simpleflickr width="100%" height="800" bgcolor="#FFFFFF" quality="best" navposition="bottom" title="Testing Demo" set="72157594408754918" maximagewidth="500" maximageheight="300" textcolor="0x000000" framecolor="0xBBBBBB" framewidth="10" stagepadding="40" thumbnailcolumns="3" thumbnailrows="3" enablerightclickopen="false" />`

== Available Tag Attributes ==

The following are the attributes available for the tag:

* 	**set**: The set id of the flickr set you would like to display. (Set or Group Required)
* 	**group**: The group id of the flickr group you would like to display. Go to <http://www.mentalaxis.com/idfindr/> to find a group id needed for this field. (Set or Group Required)
* 	**width**: The width of the flash object (optional). Default is '100%'.
* 	**height**: The height of the flash object (optional). Default is '800'.
* 	**bgcolor**: The background color of the flash object (optional). Default is '#FFFFFF'.
* 	**quality**: The quality of the flash object (optional). Default is 'best'.
* 	**navPosition**: Position of thumbnails relative to image. Can be "top", "bottom", "left" or "right". Default is 'bottom'.
* 	**title**: Text to display as gallery Title.  Default is blank.
* 	**maximagewidth**: Width of your largest image in pixels. Used to determine the best layout for your gallery.  Default is '500'.
* 	**maximageheight**: Height of your largest image in pixels. Used to determine the best layout for your gallery.  Default is '300'.
* 	**textcolor**: Color of title and caption text (hexidecimal color value e.g 0xff00ff).  Default is '0x000000'.
* 	**framecolor**: Color of image frame, navigation buttons and thumbnail frame (hexidecimal color value e.g 0xff00ff).  Default is '0xBBBBBB'.
* 	**framewidth**: Width of image frame in pixels.  Default is '15'.
* 	**stagepadding**: Distance between image and thumbnails and around gallery edge in pixels.  Default is '40'.
* 	**thumbnailcolumns**: Number of thumbnail rows. (To disable thumbnails completely set this value to 0.)  Default is '3'.
* 	**thumbnailrows**: Number of thumbnail columns. (To disable thumbnails completely set this value to 0.)  Default is '3'.
* 	**enablerightclickopen**: Whether to display a 'Open In new Window...' dialog when right-clicking on an image. Can be "true" or "false". Default is 'true'. 

The following are the option settings available:

* 	**API Key**: Go to <http://www.flickr.com/services/api/keys/> to get an API key.  This is needed to access your tags.
* 	**Flickr Screen Name**: This is used to determine the URL of your flickr site. (Ex: http://www.flickr.com/photos/screenname)

== Special Thanks ==

Special thanks to the developers and community around these two great pieces of software:

[SimpleViewer](http://www.airtightinteractive.com/simpleviewer/ "Simpleviewer") by Airtight

[Flickrviewer](http://www.sweeting.org/mark/flickrviewer/ "Flickrviewer") by Mark Sweeting

== Copyright == 

Copyright (c) 2007 
Released under the GPL license
<http://www.gnu.org/licenses/gpl.txt>