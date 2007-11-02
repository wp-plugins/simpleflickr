=== SimpleFlickr ===
Contributors: joshgerdes
Donate link: http://www.joshgerdes.com/blog/projects/simpleflickr-plugin/
Tags: images, flickr, simpleviewer, gallery
Requires at least: 2.0
Tested up to: 2.3.1
Stable tag: 2.5

This plugin allows you to embed a Simpleviewer Flash Object integrated with a Flickr account.

== Description ==

This is a plugin for Wordpress that I wrote which allows you to embed a flickr integrated 
simpleviewer into your Wordpress site.  It is my first attempt at a Wordpress plugin so 
please let me know if you have any issues using it.  

Please visit [the official website](http://www.joshgerdes.com/blog/projects/simpleflickr-plugin/ "SimpleFlickr") for the latest information on this plugin.

== Installation ==

1.	Extract all the contents of the plugin archive into your `wp-content/plugins/simpleFlickr` directory.
1.	Go to your wordpress admin pages and click on `Plugins` and activate the plugin called `SimpleFlickr`.
1.	Go to your wordpress admin and click on `Options` then `SimpleFlickr`.
1. Following the instructions to authorize the plugin with flickr. (You only do this once initially.)
1.	Set your configuration option values and click `update options` to save.
1. Add `<simpleflickr>` tag to your page or post with the set or group attribute defined.  (Please see *Usage* section for more details.)

== Frequently Asked Questions ==

= Where can I get more information? =

Please visit [the official website](http://www.joshgerdes.com/blog/projects/simpleflickr-plugin/ "SimpleFlickr") for the latest information on this plugin.

= I love this plugin! How can I show the developer how much I appreciate his work? =

Please visit [the official website](http://www.joshgerdes.com/blog/projects/simpleflickr-plugin/ "SimpleFlickr") and let him know your care.

== Screenshots ==

1. An Example of how SimpleFlickr displays in a page or post.

== Release Notes ==

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

*  **showrecent**: Determines if the user's recent photos are displayed by default.  This option takes precedences over the 'set' and 'group' options.  So, if this is set to true then your recent photos will be displayed even if you have added the 'set' or 'group' attribute to the tag.  Default is 'false'. 
* 	**set**: The set id of the flickr set you would like to display. (Set or Group Required)
* 	**group**: The group id of the flickr group you would like to display. Go to <http://idgettr.com/> to find a group id needed for this field. (Set or Group Required)
*  **count**:  The number of images to be displayed.  The maximum number the flickr API allows is 500. (For unlimited set to 0.)  Default is '0'.
*  **showimagecaption**:  Whether to display the image caption.  Can be "true" or "false". Default is 'true'.
*  **showimagelink**:   Whether to display the image link.  The image link is part of the caption so showimagecaption must be 'true' for the image link to be displayed.  Can be "true" or "false". Default is 'true'.
*  **imagelinktext**:  This is the text to display as a image link. Default is 'View flickr photo page...' 
* 	**imagesize**: You must provide the size of the image displayed in the simpleviewer flash object. Can be 'Square', 'Thumbnail', 'Small', 'Medium', 'Large', 'Original'. Default is 'Medium'. 
* 	**privacyfilter**: Determines what photos are displayed based on the level of privacy selected. Values can be 'Public photos', 'Private photos visible to friends', 'Private photos visible to family', 'Private photos visible to friends & family', 'Completely private photos'. Default is 'Public photos'. 
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

== Special Thanks ==

Special thanks to the developers and community around this great piece of software:

[SimpleViewer](http://www.airtightinteractive.com/simpleviewer/ "Simpleviewer") by Airtight Interactive

== Copyright == 

Copyright (c) 2007 
Released under the GPL license
<http://www.gnu.org/licenses/gpl.txt>

== Disclaimer ==

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.