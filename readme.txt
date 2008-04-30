=== SimpleFlickr ===
Plugin Name: SimpleFlickr
Plugin URI: http://www.joshgerdes.com/projects/simpleflickr-plugin/
Donate link: http://www.joshgerdes.com/projects/simpleflickr-plugin/
Description: This plugin allows you to embed a Simpleviewer Flash Object integrated with a Flickr account.
Version: 3.0
Author: Josh Gerdes
Author URI: http://www.joshgerdes.com
Contributors: joshgerdes
Tags: flickr, simpleviewer, gallery, images, image, simpleflickr, photos, photo
Requires at least: 2.0
Tested up to: 2.5.1
Stable tag: 3.0

This plugin allows you to embed a Simpleviewer Flash Object integrated with a Flickr account.

== Description ==

This is a plugin for Wordpress that allows you to embed a flickr integrated 
simpleviewer into your Wordpress site.  In additional, you are able to specific a path 
to a standard SimpleViewer XML configuration file to display images from a local gallery.  

Please visit [the official website](http://www.joshgerdes.com/projects/simpleflickr-plugin/ "SimpleFlickr") for the latest information on this plugin.

== Installation ==

1.	Extract all the contents of the plugin archive into your `wp-content/plugins/simpleFlickr` directory.
1.	Go to your wordpress admin pages and click on `Plugins` and activate the plugin called `SimpleFlickr`.
1.	Go to your wordpress admin and click on `Options` then `SimpleFlickr`.
1. Following the instructions to authorize the plugin with flickr. (You only do this once initially.)
1.	Set your configuration option values and click `update options` to save.
1. Add `<simpleflickr>` tag to your page or post with the set or group attribute defined.  (Please see *Usage* section for more details.)

== Frequently Asked Questions ==

= My page/post is blank in Firefox but it is showing up in Internet Explorer.  What gives? =

Please check your height settings for the plugin.  In Firefox a setting of 100% height only works if all parent elements also have height set at 100%.  This seems to be the cause of things showing up blank when viewing a page or post with the plugin.

= I am unable to authorize this plugin with my Flickr account.  What do I do? =

Please read the instructions located on the SimpleFlickr settings page.  The process to authorize this plugin with your Flickr account is straight-forward you just need to follow the instructions.

= How do you get rid of the whitespace? =

This was an issue with the SimpleViewer flash applicaiton and has been resolved as of version 1.8.5.  This version is part of SimpleFlickr version 3.0.

= Where can I get more information? =

Please visit [the official website](http://www.joshgerdes.com/projects/simpleflickr-plugin/ "SimpleFlickr") for the latest information on this plugin.

= I love this plugin! How can I show the developer how much I appreciate his work? =

Please visit [the official website](http://www.joshgerdes.com/projects/simpleflickr-plugin/ "SimpleFlickr") and let him know your care.

== Screenshots ==

1. An Example of how SimpleFlickr displays in a page or post.

== Release Notes ==

*Version 3.0*:

*  Complete overhaul of the code base.
*  Upgraded to version 1.8.5 of SimpleViewer flash application.
*  Upgraded to swfobject 2.0.  This should solve some flash upgrade request issues.
*  Added ability to set xmldatapath directly for displaying local galleries.
*  Options added/removed to work with new version of SimpleViewer.
*  Updated Admin page to have integrated look and feel with Wordpress 2.5+.
*  HTML output now standards compliant for both page/post output and admin pages.

*Version 2.5.3*:

*  Updated and tested with Wordpress 2.5

*Version 2.5.2*:

*  Added background transparency attribute to plugin.  Thanks to David Pitman for the code update.

*Version 2.5.1*:

*  Fixed bug with setting some attributes to 0 (thumbnailrows and thumbnailcolumns for example).
*  Altered image calls to Flickr to increase speed.  Setting image size to 'Medium' is faster.

*Version 2.5*:

*  Added TinyMCE quick tag button for SimpleFlickr to the Wordpress editor.
*  Added ability to alter the image link text displayed for a given photo.
*  Added ability to set the size of the images that are returned from flickr. (Including Original size)
*  Added ability to set the privacy filter for what images are returned from flickr.

*Version 2.1*:

*  Fixed flash detection and express install by updating to Unobtrusive Flash Objects (UFO) script 

*Version 2.0.1*:

*  Fixed title to allow and save blank titles.
*  Fixed 'View flickr photo page..' links when showing group pools.

*Version 2.0*:

*  Revamped Data calls to use phpFlickr library (version 2.1.0).
*  Combined code into one class and php page.
*  Removed use of flickrviewer.
*  Upgraded to SWFObject 1.5 and added express install feature.
*  Added user authentication so private photos can now be displayed.
*  Added ability to display photos from a given group.
*  Added ability to display recent user photos.
*  Added ability to show/hide image caption and image link in simpleviewer.
*  Fixed thumbnailrow and thumbnailcolumn zero settings so they work now.

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

== Upgrading ==

The easiest way to upgrade to version 2.0 from previous versions is to deactivate the plugin in wordpress and delete the plugin folder from your web server.  Once the new plugin files have been uploaded and you have activated the plugin in wordpress then you have authorized the new plugin with flickr before you can use the plugin.  It is also suggested to update your options to resave the values in the database to make sure you have all the latest option values saved.

== Usage ==

After the plugin has been installed, activated, and the configuration options have been set you will 
be able to a simpleviewer object to you content with the `<simpleflickr>` tag.  

Here is an example to show a set:
  
`<simpleflickr set="72157594408754918" />`

Here is an example to show a group:
  
`<simpleflickr group="47755705@N00" />`

Here is an example to show user's recent photos:
  
`<simpleflickr showrecent="true" />`

Here is an example with custom options:

`<simpleflickr width="100%" height="800" bgcolor="#FFFFFF" quality="best" navposition="bottom" title="Testing Demo" set="72157594408754918" maximagewidth="500" maximageheight="300" textcolor="0x000000" framecolor="0xBBBBBB" framewidth="10" stagepadding="40" thumbnailcolumns="3" thumbnailrows="3" enablerightclickopen="false" />`

== Available Tag Attributes ==

The following are the attributes available for the tag:

**Flickr Options**

The following options are related to how the plugin accesses and retrieves images from flickr.

* 	**Photo Count (count)** 	
The number of images to be displayed. The maximum number the flickr API allows is 500. For unlimited set to '0'. Default is '0'.
* 	**Show Recent Photos (showrecent)** 	
Determines the user's recent photos are displayed by default. This option takes precedences over the 'set' and 'group' options. So, if this is set to true then your recent photos will be displayed even if you have added the 'set' or 'group' attribute to the tag. Default is 'false'.
* 	**Image Size (imagesize)** 	
You must provide the size of the image displayed in the simpleviewer flash object. Can be 'Square', 'Thumbnail', 'Small', 'Medium', 'Large', 'Original'. Default is 'Medium'.
* 	**Privacy Filter (privacyfilter)** 	
Determines what photos are displayed based on the level of privacy selected. Values can be 'Public photos', 'Private photos visible to friends', 'Private photos visible to family', 'Private photos visible to friends & family', 'Completely private photos'. Default is 'Public photos'.

**Flash Object Options**

The following options are general options related to the flash object displayed by this plugin.

* 	**Width (width)** 	
Specifies the width of the movie in either pixels or percentage of browser window. Default is '480'.
* 	**Height (height)** 	
Specifies the height of the movie in either pixels or percentage of browser window. Default is '680'.
* 	**Quality (quality)** 	
Specifies the quality of the simpleviewer flash object. Can be 'low', 'high', 'autolow', 'autohigh', 'best'. Default is 'best'.
* 	**Background Color (bgcolor)** 	
Specifies the background color (hexidecimal color value e.g #FF00FF) of the movie. This attribute does not affect the background color of the HTML page. Default is #FFFFFF. Is ignored if Window Mode is set to 'transparent'.
* 	**Window Mode (wmode)** 	
Sets the Window Mode property of the Flash movie for transparency, layering, and positioning in the browser. Can be 'window', 'opaque', 'transparent'. Default is 'window'. Overrides Background Color if set to 'transparent'.

**SimpleViewer Options**

This plugin uses the SimpleViewer flash application for displaying images from Flickr. SimpleViewer can be customized by setting the following options. Please visit the SimpleViewer Options Page for more details.

* 	**Max Image Width (maximagewidth)** 	
Width of the widest image in the gallery. Used to determine the best layout for your gallery (pixels). Default is '480'.
* 	**Max Image Height (maximageheight)** 	
Height of tallest image in the gallery. Used to determine the best layout for your gallery (pixels). Default is '680'.
* 	**Text Color (textcolor)** 	
Color of title and caption text (hexidecimal color e.g. 0xFF00FF). Default is '0xFFFFFF'.
* 	**Frame Color (framecolor)** 	
Color of image frame, navigation buttons and thumbnail frame (hexidecimal color value e.g. 0xFF00FF). Default is '0xFFFFFF'.
* 	**Frame Width (framewidth)** 	
Width of image frame (pixels). Default is '20'.
* 	**Stage Padding (stagepadding)** 	
Width of padding around gallery edge (pixels). To have the image flush to the edge of the swf, set this to '0'. Default is '40'.
* 	**Nav Padding (navpadding)** 	
Distance between image and thumbnails (pixels). Default is '40'.
* 	**Thumbnail Columns (thumbnailcolumns)** 	
Number of thumbnail columns. To disable thumbnails completely set this value to '0'. Default is '3'.
* 	**Thumbnail Rows (thumbnailrows)** 	
Number of thumbnail rows. To disable thumbnails completely set this value to '0'. Default is '3'.
* 	**Nav Position (navposition)** 	
Position of thumbnails relative to image. Can be 'top', 'bottom', 'left' or 'right'. Default is 'left'.
* 	**Vertical Alignment (valign)** 	
Vertical placment of the image and thumbnails within the SWF. Can be 'center', 'top' or 'bottom'. Default is 'center'.
For large format galleries this is best set to 'center'. For small format galleries setting this to 'top' or 'bottom' can help get the image flush to the edge of the swf.
* 	**Horizontal Alignment (halign)** 	
Horizontal placement of the image and thumbnails within the SWF. Can be 'center', 'left' or 'right'. Default is 'center'.
For large format galleries this is best set to 'center'. For small format galleries setting this to 'left' or 'right' can help get the image flush to the edge of the swf.
* 	**Title (title)** 	
Text to display as gallery Title. Default is blank.
* 	**Enable Right Click Open (enablerightclickopen)** 	
Whether to display a 'Open In new Window...' dialog when right-clicking on an image. Can be 'true' or 'false'. Default is 'true'.
* 	**Background Image Path (backgroundimagepath)** 	
Relative or absolute path to a JPG or SWF to load as the gallery background. Default is blank.
Relative paths are relative to the HTML document that contains SimpleViewer. For example: 'images/bkgnd.jpg'.
* 	**First Image Index (firstimageindex)** 	
Index of image to display when gallery loads. Images are numbered beginning at zero. You can use this option to display a specific number based on the URL. Default is '0'.
* 	**Open Image Text (langopenimage)** 	
The text displayed for the right-click 'Open Image in New Window' menu option. Can be used to translate SimpleViewer into a non-English language. Default is 'Open Image in New Window'.
* 	**About Text (langabout)** 	
The text displayed for the right-click 'About' menu option. Can be used to translate SimpleViewer into a non-English language. Default is 'About'.
* 	**Preloader Color (preloadercolor)** 	
Preloader color (hexidecimal color value). Default is '0xFFFFFF'.

**Additional Options**

The following are additional options to control how SimpleViewer displays images within this plugin.

* 	**Show Image Caption (showimagecaption)** 	
Specifies if the image caption is displayed. Can be 'true' or 'false'. Default is 'true'.
* 	**Image Caption Link (imagecaptionlink)** 	
Specifies if the image caption text is a link to the flickr image page. Can be 'true' or 'false'. Default is 'true'.
* 	**Image Caption Style (imagecaptionstyle)** 	
Specifies the font style for the image caption text if displayed. Can be 'bold', 'italic', 'underline', or 'none'. Default is 'none'.

**Alternate SimpleViewer Options**

The following is an alternate option for using SimpleViewer within this plugin. If you provide a path to a standard SimpleViewer XML configuration file then this plugin will display the gallery and all settings specified in that file. This option overrides all other Simpleviewer options set for this plugin.

* 	**XML Configuration File Path (xmldatapath)** 	
Relative or absolute URL of the gallery XML file. Relative paths are relative to the HTML page that contains the swf. Default is blank.

== Special Thanks ==

Special thanks to the developers and community around this great piece of software:

[SimpleViewer](http://www.airtightinteractive.com/simpleviewer/ "Simpleviewer") by Airtight Interactive

== Copyright == 

Copyright (c) 2007-2008 
Released under the GPL license
<http://www.gnu.org/licenses/gpl.txt>

== Disclaimer ==

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.