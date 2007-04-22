<?php
/////////////////////////////////////////////////////////////////////////
// Modified Version for use with simpleFlickr Wordpress plugin
/////////////////////////////////////////////////////////////////////////
/* 
by Josh Gerdes
Version 1.2.2 - 22 Apr 2007

This version of FlickrViewer was modified to integrate with the simpleFlickr plugin.
The modifications allows configuration parameters to be retrieved from the wordpress 
plugin options database as well as from parameters sent directly from the plugin 
generated script.

Modifications to the original flickrviewer code are commented with multiple slashes so
they can be easily identified.  All original copyright licensing applies to original and
modified code.

Copyright (c) 2007
Released under the GPL license
http://www.gnu.org/licenses/gpl.txt
*/
/////////////////////////////////////////////////////////////////////////
/*********************************************************************
FlickrViewer - an Flickr enhancement for SimpleViewer
Copyright (C) 2006 Mark Sweeting 

This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, 
MA  02110-1301, USA.


Version 1.1 - 20 May 2006 

FlickrViewer homepage:

   http://www.sweeting.org/mark/flickrviewer/

For the latest news and updates about FlickrViewer, keep an eye on:

   http://www.sweeting.org/mark/blog/category/flickrviewer/

IMPORTANT!
Before you can use this you must fill out the configuration values
below. You must get a Flickr API key, decide which of your flickr
photo sets you want to publish, and fill out a few more bits.

If you're having problems getting FLickrViewer to work, set $debug to
true to enable more verbose error messages. You should turn this off
(set to false) when you are done.
*/

$debug = false;

/********************************************************************
 * START OF CONFIGURATION SECTION
 ********************************************************************/

/////////////////////////////////////////////////////////////////////////
// Added configuration for simpleFlickr plugin integration
/////////////////////////////////////////////////////////////////////////

/* Added these so we could access the options of the simpleFlickr plugin */
require_once("../../../wp-config.php");
require_once("../../../wp-includes/functions.php");

/* Retrieve simpleFlickr options from DB for use with flickrviewer. */
define('SIMPLEFLICKR_OPTIONS_NAME', "simpleflickr_options");
$simpleFlickrOptionsDB = get_option(SIMPLEFLICKR_OPTIONS_NAME);
$apikey = $simpleFlickrOptionsDB['API_KEY'];
$screen_name = $simpleFlickrOptionsDB['SCREEN_NAME'];
$userurl = 'http://www.flickr.com/photos/' . $screen_name . '/';

$navposition = $simpleFlickrOptionsDB['NAV_POSITION'];
$maximagewidth = $simpleFlickrOptionsDB['MAX_IMAGE_WIDTH'];
$maximageheight = $simpleFlickrOptionsDB['MAX_IMAGE_HEIGHT'];
$textcolor = $simpleFlickrOptionsDB['TEXT_COLOR'];
$framecolor = $simpleFlickrOptionsDB['FRAME_COLOR'];
$framewidth = $simpleFlickrOptionsDB['FRAME_WIDTH'];
$stagepadding = $simpleFlickrOptionsDB['STAGE_PADDING'];
$thumbnailcolumns = $simpleFlickrOptionsDB['THUMBNAIL_COLUMNS'];
$thumbnailrows = $simpleFlickrOptionsDB['THUMBNAIL_ROWS'];
$enablerightclickopen = $simpleFlickrOptionsDB['ENABLE_RIGHT_CLICK_OPEN'];
$title = $simpleFlickrOptionsDB['TITLE'];

/* Had to send as an array of parameters so multiple parameters would get picked up. */
$array = split(",", $_GET{parameters});
if(!empty($array[0]))	$setid = $array[0];
if(!empty($array[1]))	$group = $array[1];
if(!empty($array[2]))	$navposition = $array[2];
if(!empty($array[3]))	$maximagewidth = $array[3];
if(!empty($array[4]))	$maximageheight = $array[4];
if(!empty($array[5]))	$textcolor = $array[5];
if(!empty($array[6]))	$framecolor = $array[6];
if(!empty($array[7]))	$framewidth = $array[7];
if(!empty($array[8]))	$stagepadding = $array[8];
if(!empty($array[9]))	$thumbnailcolumns = $array[9];
if(!empty($array[10]))	$thumbnailrows = $array[10];
if(!empty($array[11]))	$enablerightclickopen = $array[11];
if(!empty($array[12]))	$title = $array[12];

/////////////////////////////////////////////////////////////////////////
// End of configuration additions for simpleFlickr plugin integration
/////////////////////////////////////////////////////////////////////////
 
/* What is your API key? Enter it below in the quotes
 * If you don't know your API key you can get if here:
 *
 *   http://www.flickr.com/services/api/misc.api_keys.html
 */
//$apikey = 'ae6a5c9426e23baf5c1234c9b2d49cf9';

/* What is the URL of your main photo page?
 * For example, mine is:
 *    http://www.flickr.com/photos/markymoo/
 * 
 * Remember to include the trailing slash!
 */
//$userurl = 'http://www.flickr.com/photos/joshgerdes/';

/* What is the set ID for the photos you would like to display?
 * All photos must be public and added to this set.
 *
 * The set id can be got from the set url. For example:
 *
 * Photo set URL:
 *    http://www.flickr.com/photos/markymoo/sets/72057594052387557/
 *
 * So the photo set ID is
 *    72057594052387557
 */
//$setid = '72057594052387557';

/* Set the title that will appear just above the thumbnails.
 */
//$title = "Flickr Photos";


/* FlickrViewer needs a place where it can write a temporary file.
 * This is used to cache the data downloaded from Flickr so that your
 * website goes faster!
 *
 * The web server must be able to read/write to your chosen location.
 */
$cache = "/tmp/flickr.xml";


/* Cache age - set the maximum age of the cache file in seconds.
 * This needs to reflect the frequency you update your photo set, so
 * you may get away with a day or two cache age.
 *
 * To set the cache to 12 hours (recommended):
 *
 *    $ttl = 43200 
 *
 * If you don't want to use caching, or if you are unable to write
 * the cache file to your web server's disc, set this to 0. E.g.:
 *
 *    $ttl = 0;
 *
 * Note that this is not recommended, and you would be better off
 * using FlickrViewer Lite.
 */
/////////////////////////////////////////////////////////////////////////
// SIMPLEFLICKR: Best to keep at 0 since this page is used by multiple locations
/////////////////////////////////////////////////////////////////////////
$ttl = 0; //43200; // 12 hours

/* Do you want to disable the link to the flickr photo/comments page?
 * Setting this value to false will remove the link, setting it to
 * true will show the link. To turn the link off:
 *
 *    $showImageLink = false;
 *
 * To turn the link on (recommended):
 *
 *   $showImageLink = true;
 */
$showImageLink = true;
 
 
/* Do you want to show the image title/caption?
 * To turn the caption off:
 *
 *   $showImageCaption = false;
 *
 * To display the caption (recommended):
 *
 *   $showImageCaption = true;
 */
$showImageCaption = true;


/* END OF CONFIGURATION SECTION 
 ********************************************************************
 * Don't edit anything below this line unless you know what 
 * you're doing!
 *******************************************************************/

$mode = isset($_GET['mode']) ? $_GET['mode'] : 'xml';

if($mode == 'img')
{
   // redirect SimpleViewer to the image on flickr
   $size  = isset($_GET['size'])  ? $_GET['size'] : 'thumb';
   $image = isset($_GET['image']) ? $_GET['image']: '0';
   if($size == 'thumb')
   {
      $url = 'http://static.flickr.com/' . $image . '_s.jpg';
   }
   else
   {
      $url = 'http://static.flickr.com/'. $image . '.jpg';
   }
   header("HTTP/1.1 301 Moved Permanently");
   header("Location: $url");
   exit;
}

/* Still here? Then we're after the XLM...
 *
 * First, see if there is a valid cache file.
 */
if($ttl && $data = @file_get_contents($cache))
{
   if($data = unserialize($data))
   {
      // check age of cache file
      if(isset($data[0]) && is_numeric($data[0]))
      {
         if((time() - $data[0]) < $ttl)
         {
            // cached data is OK, so print and quit!
            header("Content-Type: text/xml");
            header("Last-Modified: " . gmdate("r", $data[0]));
            header("Expires: " . gmdate("r", ($data[0] + $ttl)));
            header("X-Cache-Status: Hit");
            print $data[1];
            exit;
         }
      }
   }
   else
   {
      print "Couldn't unserialize the cache file.";
      exit;
   }
}

/* If we're here then the cache file either 
 * doesn't exist, it's too old, or caching is turned off.
 */
 
// array to hold photo data
$photos = array();

// string to hold the flickr api response xml
$xmlin = '';

// xml startElement handler
function startElement($parser, $name, $attrs) 
{
   global $photos, $userurl;
   if ($name == "PHOTO")
   {
      ////////////////////////////////////////////////////////////////////////////////
	  // SIMPLEFLICKR: Added group so must return data is different depending on which request 
	  ////////////////////////////////////////////////////////////////////////////////
	  if ($group) 
	  {
		$photos[] = array("{$attrs['SERVER']}/{$attrs['ID']}_{$attrs['SECRET']}", $attrs['TITLE'], 'http://www.flickr.com/photos/'.$attrs['OWNER'].'/' . $attrs['ID']);
	  } 
	  else 
	  {
		$photos[] = array("{$attrs['SERVER']}/{$attrs['ID']}_{$attrs['SECRET']}", $attrs['TITLE'], $userurl . $attrs['ID']);
	  }
   }
   elseif ($name == "ERR")
   {
      print "Flickr API error: Code {$attrs['CODE']}: {$attrs['MSG']}";
      exit;
   }
}

// xml endElement handler - not needed!
function endElement($parser, $name){ }

//////////////////////////////////////////////////////
// SIMPLEFLICKR: Add group the contruction of the request
//////////////////////////////////////////////////////
// construct the flickr api request\
if ($group != null) 
{
	$url = "http://www.flickr.com/services/rest/?method=flickr.groups.pools.getPhotos&api_key=$apikey&group_id=$group";
} 
else 
{
	$url = "http://www.flickr.com/services/rest/?method=flickr.photosets.getPhotos&api_key=$apikey&photoset_id=$setid";
}

//////////////////////////////////////////////////////////////////////////////
// SIMPLEFLICKR: Changed to check if curl or fopen are available and use the one that is
//////////////////////////////////////////////////////////////////////////////

// Check to see if curl or fopen are available to retrieve flickr data
if( function_exists('curl_init') )
{
	// Curl is available so use it
	$ch = curl_init($url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	$xmlin = curl_exec($ch);
	curl_close($ch);
}
else if(function_exists('fopen'))
{
	// fopen is available so use it instead
	if($handle = @fopen("$url", "rb"))
	{
		   while (!feof($handle)) {
		      $xmlin .= fread($handle, 8192);
		   }
		   fclose($handle);
	 }
}
 // parse the flickr response
 if((is_string($xmlin) == true) && (strlen($xmlin) > 0)) 
 {
   $xml_parser = xml_parser_create();
   xml_parser_set_option($xml_parser, XML_OPTION_CASE_FOLDING, true);
   xml_set_element_handler($xml_parser, "startElement", "endElement");
   xml_parse($xml_parser, $xmlin, true);
   xml_parser_free($xml_parser);
}
else
{
   if($debug)
   {
      print "Error: Couldn't open $url. Does your web server allow outgoing HTTP requests?";
   }
   else
   {
      print "Error: Couldn't connect to Flickr Web Service";
   }
   exit;
}

// Do we have any photos?
if(!count($photos))
{
   print "Error: No photos found in photo set.";
   exit;
}

/* Generate the XML OUT */
/////////////////////////////////////////////////////////////////////////
// SIMPLEFLICKR: Added option values from plugin parameters
/////////////////////////////////////////////////////////////////////////
$xmlout = '<?xm' . 'l version="1.0" encoding="UTF-8"?>';
$xmlout .= '<!-- Last updated: ' . date("r") . ' -->';
$xmlout .= '<SIMPLEVIEWER_DATA 
	maxImageHeight="' . $maximageheight . '"
	maxImageWidth="' . $maximagewidth . '"
	textColor="' . $textcolor . '" 
	frameColor="' . $framecolor . '" 
	bgColor="0x000000"
	frameWidth="' . $framewidth . '"
	stagePadding="' . $stagepadding . '"
	thumbnailColumns="' . $thumbnailcolumns . '"
	thumbnailRows="' . $thumbnailrows . '"
	navPosition="' . $navposition . '"
	navDirection="LTR"
	enableRightClickOpen="' . $enablerightclickopen . '"
	title="' . $title . '"
	imagePath="flickrViewer.php?mode=img&amp;size=large&amp;image="
	thumbPath="flickrViewer.php?mode=img&amp;size=thumb&amp;image=">
';



// loop over the photos array and build xml
foreach($photos as $photo)
{
   $xmlout .= "<IMAGE><NAME>{$photo[0]}</NAME><CAPTION>";
   if($showImageCaption)
   {
      if($showImageLink)
      {
         $xmlout .= "<![CDATA[<a href=\"{$photo[2]}\">{$photo[1]}<br /><u>View full size...</u></a>]]>";
      }
      else
      {
         $xmlout .= "<![CDATA[{$photo[1]}]]>";
      }
   }
   $xmlout .= "</CAPTION></IMAGE>\n";
}
$xmlout .= "</SIMPLEVIEWER_DATA>";

// get the time
$now = time();

// should we cache the results?
if($ttl)
{
   // cache the results so we don't need to keep generating it.
   $sData = serialize(array($now, $xmlout));

if (!$handle = @fopen($cache, 'w')) {
      if($debug)
      {
         echo "Error: Cannot open $cache for writing.\n";
	 
	 if(!is_dir(dirname($cache)))
	 {
	    print "The directory " . dirname($cache) . " doesn't exist. Please correct the \$cache variable, or create the directory.\n";
	 }
	 elseif(is_file($cache) && !is_writable($cache))
	 {
            print "The cache file exists, but I can't write to it. Please check the pemissions of this file and try again.\n";
	 }
	 else
	 {
            print "Please check the permissions on " . dirname($cache) . " as I don't have write permission here.\n";
	 }
      }
      else
      {
         echo "Error: Cannot open cache file";
      }
      exit;
   }
   if (@fwrite($handle, $sData) === FALSE) {
      echo "Error: Cannot write to cache file";
      exit;
   }
   fclose($handle);
}

// now return the XML for SimpleViewer
header("Content-Type: text/xml");
header("X-Cache-Status: Miss");
header("Last-Modified: " . gmdate("r", $now));
header("Expires: " . gmdate("r", ($now + $ttl)));
print $xmlout;
exit;

?>
