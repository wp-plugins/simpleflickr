
simpleFlickr Plugin for Wordpress
By Josh Gerdes
Updated: 21-02-2007

This is a plugin for Wordpress that I wrote which allows you to embed a flickr integrated 
simpleviewer into your Wordpress site.  It is my first attempt at a Wordpress plugin so 
please let me know if you have any issues using it.  Special thanks to the developers 
and community around these two great pieces of software:

SimpleViewer by Airtight - http://www.airtightinteractive.com/simpleviewer/
Flickrviewer by Mark Sweeting - http://www.sweeting.org/mark/flickrviewer/

Copyright (c) 2007
Released under the GPL license
http://www.gnu.org/licenses/gpl.txt
 
Release Notes
-------------

Version 1.0:
* Initial release tested only on version 2.1 of Wordpress
* Using version 1.8 of SimpleViewer
* Using a modified version 1.1 of Flickrviewer

Requirements
------------
* Wordpress 2.x or higher - http://wordpress.com/
* A Flickr API Key - http://www.flickr.com/services/api/keys/

Installation
------------
Extract the simpleFlickr directory and all of its contents into your wp-content/plugins/ directory.
Go to your wordpress admin pages and click on 'Plugins' activate the plugin called "simpleFlickr".

Configuration
-------------
1. Go to your wordpress admin and click on 'Options' then 'simpleFlickr'
2. Set your configuration option values and click 'update options' to save

Usage
-----
After the plugin has been installed, activated, and the configuration options have been set you will 
be able to a simpleviewer object to you content with the <simpleflickr> tag.  

Here is an example:
  
<simpleflickr width="100%" height="800" bgcolor="#222222" quality="best" navposition="bottom" title="Testing Set" set="12347594567854918" />


The following are the attributes available for the tag:

set:					The set id of the flickr set you would like to display. (Required)
width: 					The width of the flash object (optional). Default is '100%'.
height: 				The height of the flash object (optional). Default is '800'.
bgcolor:				The background color of the flash object (optional). Default is '#FFFFFF'.
quality:				The quality of the flash object (optional). Default is 'best'.
navPosition:			Position of thumbnails relative to image. Can be "top", "bottom","left" or "right". Default is 'bottom'.
title: 					Text to display as gallery Title.  Default is blank.
maximagewidth:			Width of your largest image in pixels. Used to determine the best layout for your gallery.  Default is '500'.
maximageheight:			Height of your largest image in pixels. Used to determine the best layout for your gallery.  Default is '300'.
textcolor:				Color of title and caption text (hexidecimal color value e.g 0xff00ff).  Default is '0xFFFFFF'.
framecolor:				Color of image frame, navigation buttons and thumbnail frame (hexidecimal color value e.g 0xff00ff).  Default is '0xFFFFFF'.
framewidth:				Width of image frame in pixels.  Default is '15'.
stagepadding:			Distance between image and thumbnails and around gallery edge in pixels.  Default is '40'.
thumbnailcolumns:		Number of thumbnail rows. (To disable thumbnails completely set this value to 0.)  Default is '3'.
thumbnailrows:			Number of thumbnail columns. (To disable thumbnails completely set this value to 0.)  Default is '3'.
enablerightclickopen:	Whether to display a 'Open In new Window...' dialog when right-clicking on an image. Can be "true" or "false". Default is 'true'. 

The following are the option settings available:

API Key: 			Go to http://www.flickr.com/services/api/keys/ to get an API key.  This is needed to access your tags.
Flickr Screen Name: 		This is used to determine the URL of your flickr site. (Ex: http://www.flickr.com/photos/screenname)
 