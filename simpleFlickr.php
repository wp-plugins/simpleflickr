<?php
/*
Plugin Name: SimpleFlickr
Plugin URI: http://www.joshgerdes.com/blog/projects/simpleflickr-plugin/
Description: This plugin allows you to embed a Simpleviewer Flash Object integrated with a Flickr account.
Author: Josh Gerdes
Version: 2.0
Author URI: http://www.joshgerdes.com

Copyright (c) 2007
Released under the GPL license
http://www.gnu.org/licenses/gpl.txt
*/ 

// Required libraries
if(!class_exists("phpflickr"))	require_once(dirname(__FILE__)."/phpFlickr/phpFlickr.php");

// Global Variables and Defaults
define('SIMPLEFLICKR_VERSION', "2.0");
define('SIMPLEFLICKR_FLICKR_API_KEY', "97bb421765f720bd26faf71778cb51e6");
define('SIMPLEFLICKR_FLICKR_API_SECRET', "f0036586d57895e7");
define('SIMPLEFLICKR_OPTIONS_NAME', "simpleflickr_options");
define('SIMPLEFLICKR_TOKEN_NAME', "simpleflickr_token");

// Default tag values
define('SIMPLEFLICKR_DEFAULT_WIDTH', "100%");
define('SIMPLEFLICKR_DEFAULT_HEIGHT', "800");
define('SIMPLEFLICKR_DEFAULT_QUALITY', "best");
define('SIMPLEFLICKR_DEFAULT_BGCOLOR', "#FFFFFF");
define('SIMPLEFLICKR_DEFAULT_NAVPOSITION', "bottom");
define('SIMPLEFLICKR_DEFAULT_MAXIMAGEWIDTH', "500");
define('SIMPLEFLICKR_DEFAULT_MAXIMAGEHEIGHT', "300");
define('SIMPLEFLICKR_DEFAULT_TEXTCOLOR', "0x000000");
define('SIMPLEFLICKR_DEFAULT_FRAMECOLOR', "0xBBBBBB");
define('SIMPLEFLICKR_DEFAULT_FRAMEWIDTH', "15");
define('SIMPLEFLICKR_DEFAULT_STAGEPADDING', "40");
define('SIMPLEFLICKR_DEFAULT_THUMBNAILCOLUMNS', "3");
define('SIMPLEFLICKR_DEFAULT_THUMBNAILROWS', "3");
define('SIMPLEFLICKR_DEFAULT_ENABLERIGHTCLICKOPEN', "true");
define('SIMPLEFLICKR_DEFAULT_SHOWRECENT', "false");
define('SIMPLEFLICKR_DEFAULT_COUNT', "0");
define('SIMPLEFLICKR_DEFAULT_SHOWIMAGECAPTION', "true");
define('SIMPLEFLICKR_DEFAULT_SHOWIMAGELINK', "true");

class SimpleFlickrPlugin {
	function SimpleFlickrPlugin() {
	}
	
	function init_admin() {
		// Apply the filter 
		if (preg_match("/(\/\?feed=|\/feed)/i",$_SERVER['REQUEST_URI'])) {
			// RSS Feeds
			$request_type	= "feed";
		} else {
			// Everything else
			$request_type	= "nonfeed";
			add_action('wp_head', array(&$this, 'add_flashobject_js'));
		}

		// Apply all over except the admin section
		if (strpos($_SERVER['REQUEST_URI'], 'wp-admin') === false ) {
			add_filter('the_content', array(&$this, 'main_filter'));
		}

		// add the sub-panel under the OPTIONS panel
		add_action('admin_menu', array(&$this, 'admin_menu'));
	}

	function get_image() {
		// Redirect SimpleViewer to the image on flickr
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
	
	function add_flashobject_js() {
		echo('
		<!-- Added by SimpleFlickr - Version '. SIMPLEFLICKR_VERSION . ' -->
		<script src="' . $this->get_plugin_uri() . 'swfobject.js" type="text/javascript"></script>
		');
	}
	
	function get_plugin_uri() {
		$uri = get_settings('siteurl') . '/wp-content/plugins/'. basename(dirname(__FILE__)) .'/';
		return $uri;
	}
	
	function admin_menu() {
		if (function_exists('add_options_page')) {
			add_options_page('SimpleFlickr Options', 'SimpleFlickr', 8, basename(__FILE__), array(&$this, 'options_subpanel'));
	    }
	}
	
	function options_subpanel() {
	
		// Create the phpFlickr object
		$flickr = new phpFlickr(SIMPLEFLICKR_FLICKR_API_KEY, SIMPLEFLICKR_FLICKR_API_SECRET, false);

		// Check flickr account options
		if (isset($_POST['Authenticate'])) {
           $response = $flickr->auth_getToken($_POST['frob']);

            if ($response) {
				$nsid = $response['user']['nsid'];
				$username = $response['user']['username'];
				$photos_url = $flickr->urls_getUserPhotos($nsid);
				
				$token = $response['token'];
                update_option(SIMPLEFLICKR_TOKEN_NAME, $token);
            } else {
                $error = $flickr->getErrorMsg();
            }  
        } elseif (isset($_POST['Reset'])) {
            update_option(SIMPLEFLICKR_TOKEN_NAME, '');
			$flickr->setToken('');
        } 

		// Get auth token
		$auth_token = get_option(SIMPLEFLICKR_TOKEN_NAME);
		
		// Flickr authentication
		$flickrAuth = false;
		
        if (!$auth_token) {
            // No token so get frob to use to authenticate
			$frob = $flickr->auth_getFrob();
        } else {
			// Set token and try and authenticate
            $flickr->setToken($auth_token);
            $response = $flickr->auth_checkToken();
         
			// Check if token authenticated
			if (!$response) { 
                // Authentication failed
				$error = $flickr->getErrorMsg();
				
				// Reset token
                update_option(SIMPLEFLICKR_TOKEN_NAME, '');
                $flickr->setToken('');
				
				// Get new frob to retry authentication
                $frob = $flickr->auth_getFrob();
            } else {
				// Authentication successful
                $flickrAuth = true;
				
				// Get some user details
				$nsid = $response['user']['nsid'];
				$username = $response['user']['username'];
				$photos_url = $flickr->urls_getUserPhotos($nsid);
            }
        }

		// Display errors, if any
		if(isset($error) && $error != '') {
			echo('<div class="error"><p><strong>Error: </strong> ' . $error . '</p></div>');
		}
		
		// Check if we passed authentication
		if ($flickrAuth) {
			// Get the field values
			if(isset($_POST['info_update'])) {
				$simpleFlickr_showrecent = trim($_POST['simpleFlickr_showrecent']);
				$simpleFlickr_count = trim($_POST['simpleFlickr_count']);
				$simpleFlickr_navposition = trim($_POST['simpleFlickr_navposition']);
				$simpleFlickr_maximagewidth = trim($_POST['simpleFlickr_maximagewidth']);
				$simpleFlickr_maximageheight = trim($_POST['simpleFlickr_maximageheight']);
				$simpleFlickr_textcolor = trim($_POST['simpleFlickr_textcolor']);
				$simpleFlickr_framecolor = trim($_POST['simpleFlickr_framecolor']);
				$simpleFlickr_framewidth = trim($_POST['simpleFlickr_framewidth']);
				$simpleFlickr_stagepadding = trim($_POST['simpleFlickr_stagepadding']);
				$simpleFlickr_thumbnailcolumns = trim($_POST['simpleFlickr_thumbnailcolumns']);
				$simpleFlickr_thumbnailrows = trim($_POST['simpleFlickr_thumbnailrows']);
				$simpleFlickr_enablerightclickopen = trim($_POST['simpleFlickr_enablerightclickopen']);
				$simpleFlickr_showimagecaption = trim($_POST['simpleFlickr_showimagecaption']);
				$simpleFlickr_showimagelink = trim($_POST['simpleFlickr_showimagelink']);
				$simpleFlickr_title = trim($_POST['simpleFlickr_title']);
				$simpleFlickr_width = trim($_POST['simpleFlickr_width']);
				$simpleFlickr_height = trim($_POST['simpleFlickr_height']);
				$simpleFlickr_quality = trim($_POST['simpleFlickr_quality']);
				$simpleFlickr_bgcolor = trim($_POST['simpleFlickr_bgcolor']);

				// Get array from DB
				$simpleFlickrOptionsDB = get_option(SIMPLEFLICKR_OPTIONS_NAME);

				// Check if empty and fill
				if(empty($simpleFlickr_showrecent)) 
					$simpleFlickr_showrecent = $simpleFlickrOptionsDB['SHOW_RECENT'];
				if(empty($simpleFlickr_count) && $simpleFlickr_count != '0') 
					$simpleFlickr_count = $simpleFlickrOptionsDB['COUNT'];
				if(empty($simpleFlickr_navposition)) 
					$simpleFlickr_navposition = $simpleFlickrOptionsDB['NAV_POSITION'];
				if(empty($simpleFlickr_maximagewidth)) 
					$simpleFlickr_maximagewidth = $simpleFlickrOptionsDB['MAX_IMAGE_WIDTH'];
				if(empty($simpleFlickr_maximageheight)) 
					$simpleFlickr_maximageheight = $simpleFlickrOptionsDB['MAX_IMAGE_HEIGHT'];
				if(empty($simpleFlickr_textcolor)) 
					$simpleFlickr_textcolor = $simpleFlickrOptionsDB['TEXT_COLOR'];
				if(empty($simpleFlickr_framecolor)) 
					$simpleFlickr_framecolor = $simpleFlickrOptionsDB['FRAME_COLOR'];
				if(empty($simpleFlickr_framewidth)) 
					$simpleFlickr_framewidth = $simpleFlickrOptionsDB['FRAME_WIDTH'];
				if(empty($simpleFlickr_stagepadding)) 
					$simpleFlickr_stagepadding = $simpleFlickrOptionsDB['STAGE_PADDING'];
				if(empty($simpleFlickr_thumbnailcolumns) && $simpleFlickr_thumbnailcolumns != '0') 
					$simpleFlickr_thumbnailcolumns = $simpleFlickrOptionsDB['THUMBNAIL_COLUMNS'];
				if(empty($simpleFlickr_thumbnailrows) && $simpleFlickr_thumbnailrows != '0') 
					$simpleFlickr_thumbnailrows = $simpleFlickrOptionsDB['THUMBNAIL_ROWS'];
				if(empty($simpleFlickr_enablerightclickopen)) 
					$simpleFlickr_enablerightclickopen = $simpleFlickrOptionsDB['ENABLE_RIGHT_CLICK_OPEN'];
				if(empty($simpleFlickr_showimagecaption)) 
					$simpleFlickr_showimagecaption = $simpleFlickrOptionsDB['SHOW_IMAGE_CAPTION'];
				if(empty($simpleFlickr_showimagelink)) 
					$simpleFlickr_showimagelink = $simpleFlickrOptionsDB['SHOW_IMAGE_LINK'];
				if(empty($simpleFlickr_title)) 
					$simpleFlickr_title = $simpleFlickrOptionsDB['TITLE'];
				if(empty($simpleFlickr_width)) 
					$simpleFlickr_width = $simpleFlickrOptionsDB['WIDTH'];
				if(empty($simpleFlickr_height)) 
					$simpleFlickr_height = $simpleFlickrOptionsDB['HEIGHT'];
				if(empty($simpleFlickr_quality)) 
					$simpleFlickr_quality = $simpleFlickrOptionsDB['QUALITY'];
				if(empty($simpleFlickr_bgcolor)) 
					$simpleFlickr_bgcolor = $simpleFlickrOptionsDB['BGCOLOR'];
	
				// Add values to a new array to save
				$simpleFlickrOptionsNewArr = array();
				$simpleFlickrOptionsNewArr['SHOW_RECENT'] = $simpleFlickr_showrecent;
				$simpleFlickrOptionsNewArr['COUNT'] = $simpleFlickr_count;
				$simpleFlickrOptionsNewArr['NAV_POSITION'] = $simpleFlickr_navposition;
				$simpleFlickrOptionsNewArr['MAX_IMAGE_WIDTH'] = $simpleFlickr_maximagewidth;
				$simpleFlickrOptionsNewArr['MAX_IMAGE_HEIGHT'] = $simpleFlickr_maximageheight;
				$simpleFlickrOptionsNewArr['TEXT_COLOR'] = $simpleFlickr_textcolor;
				$simpleFlickrOptionsNewArr['FRAME_COLOR'] = $simpleFlickr_framecolor;
				$simpleFlickrOptionsNewArr['FRAME_WIDTH'] = $simpleFlickr_framewidth;
				$simpleFlickrOptionsNewArr['STAGE_PADDING'] = $simpleFlickr_stagepadding;
				$simpleFlickrOptionsNewArr['THUMBNAIL_COLUMNS'] = $simpleFlickr_thumbnailcolumns;
				$simpleFlickrOptionsNewArr['THUMBNAIL_ROWS'] = $simpleFlickr_thumbnailrows;
				$simpleFlickrOptionsNewArr['ENABLE_RIGHT_CLICK_OPEN'] = $simpleFlickr_enablerightclickopen;
				$simpleFlickrOptionsNewArr['SHOW_IMAGE_CAPTION'] = $simpleFlickr_showimagecaption;
				$simpleFlickrOptionsNewArr['SHOW_IMAGE_LINK'] = $simpleFlickr_showimagelink;
				$simpleFlickrOptionsNewArr['TITLE'] = $simpleFlickr_title;
				$simpleFlickrOptionsNewArr['WIDTH'] = $simpleFlickr_width;
				$simpleFlickrOptionsNewArr['HEIGHT'] = $simpleFlickr_height;
				$simpleFlickrOptionsNewArr['QUALITY'] = $simpleFlickr_quality;
				$simpleFlickrOptionsNewArr['BGCOLOR'] = $simpleFlickr_bgcolor;
			
				// Save new array to DB
				update_option(SIMPLEFLICKR_OPTIONS_NAME, $simpleFlickrOptionsNewArr);
				
				// Display success if updated
				echo('<div class="updated"><p><strong>Successfully Saved Settings</strong></p></div>');
			}

			// Get values from DB
			$simpleFlickrOptionsDB = get_option(SIMPLEFLICKR_OPTIONS_NAME);
			$simpleFlickr_showrecent = $simpleFlickrOptionsDB['SHOW_RECENT'];
			$simpleFlickr_count = $simpleFlickrOptionsDB['COUNT'];
			$simpleFlickr_navposition = $simpleFlickrOptionsDB['NAV_POSITION'];
			$simpleFlickr_maximagewidth = $simpleFlickrOptionsDB['MAX_IMAGE_WIDTH'];
			$simpleFlickr_maximageheight = $simpleFlickrOptionsDB['MAX_IMAGE_HEIGHT'];
			$simpleFlickr_textcolor = $simpleFlickrOptionsDB['TEXT_COLOR'];
			$simpleFlickr_framecolor = $simpleFlickrOptionsDB['FRAME_COLOR'];
			$simpleFlickr_framewidth = $simpleFlickrOptionsDB['FRAME_WIDTH'];
			$simpleFlickr_stagepadding = $simpleFlickrOptionsDB['STAGE_PADDING'];
			$simpleFlickr_thumbnailcolumns = $simpleFlickrOptionsDB['THUMBNAIL_COLUMNS'];
			$simpleFlickr_thumbnailrows = $simpleFlickrOptionsDB['THUMBNAIL_ROWS'];
			$simpleFlickr_enablerightclickopen = $simpleFlickrOptionsDB['ENABLE_RIGHT_CLICK_OPEN'];
			$simpleFlickr_showimagecaption = $simpleFlickrOptionsDB['SHOW_IMAGE_CAPTION'];
			$simpleFlickr_showimagelink = $simpleFlickrOptionsDB['SHOW_IMAGE_LINK'];
			$simpleFlickr_title = $simpleFlickrOptionsDB['TITLE'];
			$simpleFlickr_width = $simpleFlickrOptionsDB['WIDTH'];
			$simpleFlickr_height = $simpleFlickrOptionsDB['HEIGHT'];
			$simpleFlickr_quality = $simpleFlickrOptionsDB['QUALITY'];
			$simpleFlickr_bgcolor = $simpleFlickrOptionsDB['BGCOLOR'];
		
			// Fill with defaults if no DB value was given
			if(empty($simpleFlickr_showrecent)) 
				$simpleFlickr_showrecent = SIMPLEFLICKR_DEFAULT_SHOWRECENT;
			if(empty($simpleFlickr_count) && $simpleFlickr_count != '0') 
				$simpleFlickr_count = SIMPLEFLICKR_DEFAULT_COUNT;
			if(empty($simpleFlickr_navposition)) 
				$simpleFlickr_navposition = SIMPLEFLICKR_DEFAULT_NAVPOSITION;
			if(empty($simpleFlickr_maximagewidth)) 
				$simpleFlickr_maximagewidth = SIMPLEFLICKR_DEFAULT_MAXIMAGEWIDTH;
			if(empty($simpleFlickr_maximageheight)) 
				$simpleFlickr_maximageheight = SIMPLEFLICKR_DEFAULT_MAXIMAGEHEIGHT;
			if(empty($simpleFlickr_textcolor)) 
				$simpleFlickr_textcolor = SIMPLEFLICKR_DEFAULT_TEXTCOLOR;
			if(empty($simpleFlickr_framecolor)) 
				$simpleFlickr_framecolor = SIMPLEFLICKR_DEFAULT_FRAMECOLOR;
			if(empty($simpleFlickr_framewidth)) 
				$simpleFlickr_framewidth = SIMPLEFLICKR_DEFAULT_FRAMEWIDTH;
			if(empty($simpleFlickr_stagepadding)) 
				$simpleFlickr_stagepadding = SIMPLEFLICKR_DEFAULT_STAGEPADDING;
			if(empty($simpleFlickr_thumbnailcolumns) && $simpleFlickr_thumbnailcolumns != '0') 
				$simpleFlickr_thumbnailcolumns = SIMPLEFLICKR_DEFAULT_THUMBNAILCOLUMNS;
			if(empty($simpleFlickr_thumbnailrows) && $simpleFlickr_thumbnailrows != '0') 
				$simpleFlickr_thumbnailrows = SIMPLEFLICKR_DEFAULT_THUMBNAILROWS;
			if(empty($simpleFlickr_enablerightclickopen)) 
				$simpleFlickr_enablerightclickopen = SIMPLEFLICKR_DEFAULT_ENABLERIGHTCLICKOPEN;
			if(empty($simpleFlickr_showimagecaption)) 
				$simpleFlickr_showimagecaption = SIMPLEFLICKR_DEFAULT_SHOWIMAGECAPTION;
			if(empty($simpleFlickr_showimagelink)) 
				$simpleFlickr_showimagelink = SIMPLEFLICKR_DEFAULT_SHOWIMAGELINK;
			if(empty($simpleFlickr_width)) 
				$simpleFlickr_width = SIMPLEFLICKR_DEFAULT_WIDTH;
			if(empty($simpleFlickr_height)) 
				$simpleFlickr_height = SIMPLEFLICKR_DEFAULT_HEIGHT;
			if(empty($simpleFlickr_quality)) 
				$simpleFlickr_quality = SIMPLEFLICKR_DEFAULT_QUALITY;
			if(empty($simpleFlickr_bgcolor)) 
				$simpleFlickr_bgcolor = SIMPLEFLICKR_DEFAULT_BGCOLOR;
				
			// Add header html
			echo('<div class=wrap>');
			echo('<h2>SimpleFlickr (v' . SIMPLEFLICKR_VERSION . ') Options</h2>');
			echo("<div>");

			// Add paypal div
			echo("<div  style=\"float:right;width:160px;background:#ddd;border:1px solid #999;padding:10px;font-size:0.9em;margin-left:10px;\">");
			echo("<form name=\"paypal\" id=\"paypal\" action=\"https://www.paypal.com/cgi-bin/webscr\" method=\"post\">");
			echo("<h3>Enjoy this Plugin?</h3>");
			echo("<p>If you like this plugin, and wish to contribute to its development, consider making a donation.</p>");
			echo("<form action=\"https://www.paypal.com/cgi-bin/webscr\" method=\"post\">");
			echo("<input type=\"hidden\" name=\"cmd\" value=\"_s-xclick\">");
			echo("<input type=\"image\" src=\"https://www.paypal.com/en_US/i/btn/x-click-but04.gif\" border=\"0\" name=\"submit\" alt=\"Make payments with PayPal - it's fast, free and secure.\">");
			echo("<img alt=\"\" border=\"0\" src=\"https://www.paypal.com/en_US/i/scr/pixel.gif\" width=\"1\" height=\"1\">");
			echo("<input type=\"hidden\" name=\"encrypted\" value=\"-----BEGIN PKCS7-----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-----END PKCS7-----\"></form></div>");
			
			// Add description
			echo("<div style=\"float:left;margin-bottom=20\">");
							echo("<p>This plugin allows you to embed a Simpleviewer Flash Object integrated with a Flickr account.<br />Please visit <a href=\"http://www.joshgerdes.com/blog/projects/simpleflickr-plugin/\" target=\"_blank\">the official website</a> for the latest information on this plugin.</p>");
			echo("<br />");
			echo("<br />");

			echo('<form method="post">');
			
			// Add flickr authorization status
			echo('<ul style="list-style:none;">');
			echo('<li><label><strong>Current Authorized User:</strong></label>&nbsp;&nbsp;<a href="' . $photos_url . '" target="_blank">' . $username . '</a>&nbsp;&nbsp;&nbsp;&nbsp;<input type="submit" name="Reset" value="Reset Flickr Authorization" /></li>');
			echo('</ul>');
			echo("</div>");
			
			echo("</div>");
			echo("<br clear=\"all\" />");
			
			// Add parameter options
			echo("<fieldset class=\"options\">");
			echo("<legend>Default Tag Settings</legend>");
			echo("<hr />");
			echo("<ul style=\"list-style:none;\">");
			echo("<li>");
			echo("<label for=\"simpleFlickr_count\"><strong>Photo Count:</strong></label>&nbsp;&nbsp;");
			echo("<input type=\"text\" name=\"simpleFlickr_count\" id=\"simpleFlickr_count\" size=\"4\" value=\"" . $simpleFlickr_count . "\" /><br />");
			echo("<em>The number of images to be displayed.  The maximum number the flickr API allows is 500. (For unlimited set to 0.)  Default is '0'.</em> <br /><br />");
			echo("</li>");
			echo("<li>");
			echo("<label for=\"simpleFlickr_showrecent\"><strong>Show Recent Photos:</strong></label>&nbsp;&nbsp;");
			echo("<select name=\"simpleFlickr_showrecent\" id=\"simpleFlickr_showrecent\">");
			echo("<option value=\"true\"");
			if($simpleFlickr_showrecent=='true')	echo(" selected");
			echo(">True</option>");
			echo("<option value=\"false\"");
			if($simpleFlickr_showrecent=='false')	echo(" selected");
			echo(">False</option>");
			echo("</select><br />");
			echo("<em>Determines the user's recent photos are displayed by default.  This option takes precedences over the 'set' and 'group' options.  So, if this is set to true then your recent photos will be displayed even if you have added the 'set' or 'group' attribute to the tag.  Default is 'false'. </em> <br /><br />");
			echo("</li>");
			echo("<li>");
			echo("<label for=\"simpleFlickr_showimagecaption\"><strong>Show Image Caption:</strong></label>&nbsp;&nbsp;");
			echo("<select name=\"simpleFlickr_showimagecaption\" id=\"simpleFlickr_showimagecaption\">");
			echo("<option value=\"true\"");
			if($simpleFlickr_showimagecaption=='true')	echo(" selected");
			echo(">True</option>");
			echo("<option value=\"false\"");
			if($simpleFlickr_showimagecaption=='false')	echo(" selected");
			echo(">False</option>");
			echo("</select><br />");
			echo("<em>You must provide whether to display the image caption.  Can be \"true\" or \"false\". Default is 'true'.</em> <br /><br />");
			echo("</li>");
			echo("<li>");
			echo("<label for=\"simpleFlickr_showimagelink\"><strong>Show Image Link:</strong></label>&nbsp;&nbsp;");
			echo("<select name=\"simpleFlickr_showimagelink\" id=\"simpleFlickr_showimagelink\">");
			echo("<option value=\"true\"");
			if($simpleFlickr_showimagelink=='true')	echo(" selected");
			echo(">True</option>");
			echo("<option value=\"false\"");
			if($simpleFlickr_showimagelink=='false')	echo(" selected");
			echo(">False</option>");
			echo("</select><br />");
			echo("<em>You must provide whether to display the image link.  The image link is part of the caption so showimagecaption must be 'true' for the image link to be displayed.  Can be \"true\" or \"false\". Default is 'true'.</em> <br /><br />");
			echo("</li>");
			echo("<li>");
			echo("<label for=\"simpleFlickr_title\"><strong>Title:</strong></label>&nbsp;&nbsp;");
			echo("<input type=\"text\" name=\"simpleFlickr_title\" id=\"simpleFlickr_title\" size=\"30\" value=\"" . $simpleFlickr_title . "\" /><br />");
			echo("<em>This is the text to display as a gallery title.  Default is blank.</em> <br /><br />");
			echo("</li>");
			echo("<li>");
			echo("<label for=\"simpleFlickr_width\"><strong>Width:</strong></label>&nbsp;&nbsp;");
			echo("<input type=\"text\" name=\"simpleFlickr_width\" id=\"simpleFlickr_width\" size=\"4\" value=\"" . $simpleFlickr_width . "\" /><br />");
			echo("<em>You must provide the width of the simpleviewer flash object.  Percentages and Pixel values can be givne.  Default is 100%.</em> <br /><br />");
			echo("</li>");
			echo("<li>");
			echo("<label for=\"simpleFlickr_height\"><strong>Height:</strong></label>&nbsp;&nbsp;");
			echo("<input type=\"text\" name=\"simpleFlickr_height\" id=\"simpleFlickr_height\" size=\"4\" value=\"" . $simpleFlickr_height . "\" /><br />");
			echo(" <em>You must provide the height of the simpleviewer flash object.  Percentages and Pixel values can be givne.  Default is 100%.</em> <br /><br />");
			echo("</li>");
			echo("<li>");
			echo("<label for=\"simpleFlickr_quality\"><strong>Quality:</strong></label>&nbsp;&nbsp;");
			echo("<select name=\"simpleFlickr_quality\" id=\"simpleFlickr_quality\">");
			echo("	<option value=\"best\""); 
			if($simpleFlickr_quality=='best')	echo(" selected");
			echo(">Best</option>");
			echo("	<option value=\"high\""); 
			if($simpleFlickr_quality=='high')	echo(" selected");
			echo(">High</option>");
			echo("	<option value=\"medium\""); 
			if($simpleFlickr_quality=='medium')	echo(" selected");
			echo(">Medium</option>");
			echo("	<option value=\"autohigh\""); 
			if($simpleFlickr_quality=='autohigh')	echo(" selected");
			echo(">Autohigh</option>");
			echo("	<option value=\"autolow\""); 
			if($simpleFlickr_quality=='autolow')	echo(" selected");
			echo(">Autolow</option>");
			echo("	<option value=\"low\""); 
			if($simpleFlickr_quality=='low')	echo(" selected");
			echo(">Low</option>");
			echo("</select><br />");
			echo("<em>You must provide the quality of the simpleviewer flash object.  Can be 'low', 'high', 'autolow', 'autohigh', 'best'.  Default is 'best'.</em> <br /><br />");
			echo("</li>");
			echo("<li>");
			echo("<label for=\"simpleFlickr_bgcolor\"><strong>Background Color:</strong></label>&nbsp;&nbsp;");
			echo("<input type=\"text\" name=\"simpleFlickr_bgcolor\" id=\"simpleFlickr_bgcolor\" size=\"8\" maxlength=\"7\" value=\"" . $simpleFlickr_bgcolor . "\" /><br />");
			echo("<em>You must provide the background color of the simpleviewer flash object (hexidecimal color value e.g #FF00FF).  Default is #FFFFFF.</em> <br /><br />");
			echo("</li>");
			echo("<li>");
			echo("<label for=\"simpleFlickr_navposition\"><strong>Nav Position:</strong></label>&nbsp;&nbsp;");
			echo("<select name=\"simpleFlickr_navposition\" id=\"simpleFlickr_navposition\">");
			echo("<option value=\"bottom\""); 
			if($simpleFlickr_navposition=='bottom')	echo(" selected");
			echo(">Bottom</option>");
			echo("<option value=\"top\""); 
			if($simpleFlickr_navposition=='top')	echo(" selected");
			echo(">Top</option>");
			echo("<option value=\"left\""); 
			if($simpleFlickr_navposition=='left')	echo(" selected");
			echo(">Left</option>");
			echo("<option value=\"right\""); 
			if($simpleFlickr_navposition=='right')	echo(" selected");
			echo(">Right</option>");
			echo("</select><br />");
			echo(" <em>You must provide the position of the simpleviewer navigation menu relative to the image.  Can be 'top', 'bottom', 'left' or 'right'.  Default is 'bottom'.</em> <br /><br />");
			echo("</li>");
			echo("<li>");
			echo("<label for=\"simpleFlickr_thumbnailcolumns\"><strong>Thumbnail Columns:</strong></label>&nbsp;&nbsp;");
			echo("<input type=\"text\" name=\"simpleFlickr_thumbnailcolumns\" id=\"simpleFlickr_thumbnailcolumns\" size=\"4\" value=\"" . $simpleFlickr_thumbnailcolumns . "\" /><br />");
			echo("	 <em>You must provide the number of thumbnail columns. (To disable thumbnails completely set this value to 0.)  Default is '3'.</em> <br /><br />");
			echo("</li>");
			echo("<li>");
			echo("<label for=\"simpleFlickr_thumbnailrows\"><strong>Thumbnail Rows:</strong></label>&nbsp;&nbsp;");
			echo("<input type=\"text\" name=\"simpleFlickr_thumbnailrows\" id=\"simpleFlickr_thumbnailrows\" size=\"4\" value=\"" . $simpleFlickr_thumbnailrows . "\" /><br />");
			echo("	 <em>You must provide the number of thumbnail rows. (To disable thumbnails completely set this value to 0.)  Default is '3'.</em> <br /><br />");
			echo("</li>");
			echo("<li>");
			echo("<label for=\"simpleFlickr_maximagewidth\"><strong>Max Image Width:</strong></label>&nbsp;&nbsp;");
			echo("<input type=\"text\" name=\"simpleFlickr_maximagewidth\" id=\"simpleFlickr_maximagewidth\" size=\"4\" value=\"" . $simpleFlickr_maximagewidth . "\" /><br />");
			echo("	 <em>You must provide the width of your largest image in pixels. Used to determine the best layout for your gallery.  Default is '500'.</em> <br /><br />");
			echo("</li>");
			echo("<li>");
			echo("<label for=\"simpleFlickr_maximageheight\"><strong>Max Image Height:</strong></label>&nbsp;&nbsp;");
			echo("<input type=\"text\" name=\"simpleFlickr_maximageheight\" id=\"simpleFlickr_maximageheight\" size=\"4\" value=\"" . $simpleFlickr_maximageheight . "\" /><br />");
			echo("	 <em>You must provide height of your largest image in pixels. Used to determine the best layout for your gallery.  Default is '300'.</em> <br /><br />");
			echo("</li>");
			echo("<li>");
			echo("<label for=\"simpleFlickr_textcolor\"><strong>Text Color:</strong></label>&nbsp;&nbsp;");
			echo("<input type=\"text\" name=\"simpleFlickr_textcolor\" id=\"simpleFlickr_textcolor\" size=\"10\" maxlength=\"8\" value=\"" . $simpleFlickr_textcolor . "\" /><br />");
			echo("	 <em>You must provide the color of title and caption text (hexidecimal color value e.g 0xff00ff).  Default is '0x000000'.</em> <br /><br />");
			echo("</li>");
			echo("<li>");
			echo("<label for=\"simpleFlickr_framecolor\"><strong>Frame Color:</strong></label>&nbsp;&nbsp;");
			echo("<input type=\"text\" name=\"simpleFlickr_framecolor\" id=\"simpleFlickr_framecolor\" size=\"10\" maxlength=\"8\" value=\"" . $simpleFlickr_framecolor . "\" /><br />");
			echo("<em>You must provide the color of the image frame, navigation buttons and thumbnail frame (hexidecimal color value e.g 0xff00ff).  Default is '0xBBBBBB'.</em> <br /><br />");
			echo("</li>");
			echo("<li>");
			echo("<label for=\"simpleFlickr_framewidth\"><strong>Frame Width:</strong></label>&nbsp;&nbsp;");
			echo("<input type=\"text\" name=\"simpleFlickr_framewidth\" id=\"simpleFlickr_framewidth\" size=\"4\" value=\"" . $simpleFlickr_framewidth . "\" /><br />");
			echo("	 <em>You must provide the width of image frame in pixels.  Default is '15'.</em> <br /><br />");
			echo("</li>");
			echo("<li>");
			echo("<label for=\"simpleFlickr_stagepadding\"><strong>Stage Padding:</strong></label>&nbsp;&nbsp;");
			echo("<input type=\"text\" name=\"simpleFlickr_stagepadding\" id=\"simpleFlickr_stagepadding\" size=\"4\" value=\"" . $simpleFlickr_stagepadding . "\" /><br />");
			echo("	 <em>You must provide the distance between image and thumbnails and around gallery edge in pixels.  Default is '40'.</em> <br /><br />");
			echo("</li>");
			echo("<li>");
			echo("<label for=\"simpleFlickr_enablerightclickopen\"><strong>Enable Right Click Open:</strong></label>&nbsp;&nbsp;");
			echo("<select name=\"simpleFlickr_enablerightclickopen\" id=\"simpleFlickr_enablerightclickopen\">");
			echo("<option value=\"true\"");
			if($simpleFlickr_enablerightclickopen=='true')	echo(" selected");
			echo(">True</option>");
			echo("<option value=\"false\"");
			if($simpleFlickr_enablerightclickopen=='false')	echo(" selected");
			echo(">False</option>");
			echo("</select><br />");
			echo("<em>You must provide whether to display a 'Open In new Window...' dialog when right-clicking on an image. Can be 'true' or 'false'.  Default is 'true'. </em> <br /><br />");
			echo("</li>");
			echo("</ul>");
			
			// Add update button
			echo('<div class="submit"><input type="submit" name="info_update" value="Update options &raquo;" /></div>');
		}
		else {
		
			// Add header html
			echo('<div class=wrap><form method="post">');
			echo('<h2>SimpleFlickr (v' . SIMPLEFLICKR_VERSION . ') Options</h2>');
		
			// Setup authentication url
			$link = 'http://flickr.com/services/auth/?api_key=' . SIMPLEFLICKR_FLICKR_API_KEY . '&frob=' . $frob . '&perms=read';
			$parms = 'api_key' . SIMPLEFLICKR_FLICKR_API_KEY . 'frob' . $frob . 'permsread';
			$link .= '&api_sig=' . md5(SIMPLEFLICKR_FLICKR_API_SECRET . $parms);
			
			// Render the html
			echo('<fieldset class="options">');
			echo(' <legend>Initial Setup</legend>');
			echo('<input type="hidden" name="frob" value="' . $frob . '">');
		    echo('<p>Please complete the following steps to allow SimpleFlickr access to your Flickr photos.</p>');
		    echo('<p><strong>Step 1</strong>: <a href="' . $link . '" target="_blank">Authorize SimpleFlickr to access your Flickr account</a></p>');
		    echo('<p><strong>Step 2</strong>: <input type="submit" name="Authenticate" value="Get Authentication Token" /></p>');
		}
		
		// Add footer html
		echo('</fieldset>');
		echo('</form></div>');
	}
	
	function main_filter($content) {
		$pattern = '/(<p>[\s\n\r]*)??(([\[<]SIMPLEFLICKR.*\/[\]>])|([\[<]SIMPLEFLICKR.*[\]>][\[<]\/SIMPLEFLICKR[\]>]))([\s\n\r]*<\/p>)??/Umi'; 
		return preg_replace_callback($pattern,array(&$this, 'parse_tags'),$content);
	}
	
	function parse_tags ($match) {
		$ret = "";
		
		// Remove some of the unwanted tags
		$strip		= array(	'[SIMPLEFLICKR', '][/SIMPLEFLICKR]',
										'[simpleflickr', '][/simpleflickr]',
										'[simpleFlickr', '][/simpleFlickr]',
										'/]',
										'<SIMPLEFLICKR', '></SIMPLEFLICKR>',
										'<simpleflickr', '></simpleflickr>',
										'<simpleFlickr', '></simpleFlickr>',
										'/>', 
										'\n', '<br>', '<br />', '<p>', '</p>'
							);
							
		$elements	= str_replace($strip, '', $match[0]);
		
		$elements	= preg_replace("/=(\s*)\"/", "==`", $elements);
		$elements	= preg_replace("/=(\s*)&Prime;/", "==`", $elements);
		$elements	= preg_replace("/=(\s*)&prime;/", "==`", $elements);
		$elements	= preg_replace("/=(\s*)&#8221;/", "==`", $elements);
		$elements	= preg_replace("/\"(\s*)/", "`| ", $elements);
		$elements	= preg_replace("/&Prime;(\s*)/", "`|", $elements);
		$elements	= preg_replace("/&prime;(\s*)/", "`|", $elements);
		$elements	= preg_replace("/&#8221;(\s*)/", "`|", $elements);
		$elements	= preg_replace("/&#8243;(\s*)/", "`|", $elements);
		$elements	= preg_replace("/&#8216;(\s*)/", "'", $elements);
		$elements	= preg_replace("/&#8217;(\s*)/", "'", $elements);
		
		$attpairs	= preg_split('/\|/', $elements, -1, PREG_SPLIT_NO_EMPTY);
		$atts		= array();
		
		// Create an associative array of the attributes
		for ($x = 0; $x < count($attpairs); $x++) {
			
			$attpair		= explode('==', $attpairs[$x]);
			$attn			= trim(strtolower($attpair[0]));
			$attv			= preg_replace("/`/", "", trim($attpair[1]));
			$atts[$attn]	= $attv;
		}

		// Adjust for percentage heights
		$atts['height']				= ($height{strlen($atts['height']) - 1} == "%") ? '"' . $atts['height'] . '"' : $atts['height'];
		$atts['width']				= ($width{strlen($atts['width']) - 1} == "%") ? '"' . $atts['width'] . '"' : $atts['width'];
			
		// If we're not serving up a feed, generate the script tags
		if ($request_type  != "feed") {
			$ret = $this->build_script($atts);
		} else {
			$ret = $this->build_feed($atts);
		}

		return $ret;
	}
	
	function build_script($atts) {
		$result = "";
		
		// Create a random number to make the flash object unique
		$rand = mt_rand();
		
		// Extract out all of the option variables
		if (is_array($atts)) extract($atts);

		// Get the default values for some of the tags
		$simpleFlickrOptionsDB = get_option(SIMPLEFLICKR_OPTIONS_NAME);
		$default_width = $simpleFlickrOptionsDB['WIDTH'];
		$default_height = $simpleFlickrOptionsDB['HEIGHT'];
		$default_quality = $simpleFlickrOptionsDB['QUALITY'];
		$default_bgcolor = $simpleFlickrOptionsDB['BGCOLOR'];

		// Fill with defaults if no DB value was given
		if(empty($default_width)) 
			$default_width = SIMPLEFLICKR_DEFAULT_WIDTH;
		if(empty($default_height)) 
			$default_height = SIMPLEFLICKR_DEFAULT_HEIGHT;
		if(empty($default_quality)) 
			$default_quality = SIMPLEFLICKR_DEFAULT_QUALITY;
		if(empty($default_bgcolor)) 
			$default_bgcolor = SIMPLEFLICKR_DEFAULT_BGCOLOR;
			
		// Load some defaults if value not given
		if(empty($width))	$width = $default_width; 
		if(empty($height))	$height = $default_height;
		if(empty($quality))	$quality = $default_quality;
		if(empty($bgcolor))	$bgcolor = $default_bgcolor;

		// Combine the simpleviewer parameters
		$params[] = $showrecent;	
		$params[] = $set;
		$params[] = $group;
		$params[] = $navposition;
		$params[] = $maximagewidth;
		$params[] = $maximageheight;
		$params[] = $textcolor;
		$params[] = $framecolor;
		$params[] = $framewidth;
		$params[] = $stagepadding;
		$params[] = $thumbnailcolumns;
		$params[] = $thumbnailrows;
		$params[] = $enablerightclickopen;
		$params[] = $title;
		$params[] = $count;
		$params[] = $showimagecaption;
		$params[] = $showimagelink;
		
		$parameters = join(",", $params);
		
		// Create the script
		$output	= array();
		$output[] = '<div id="fo_targ_viewer' . $rand . '" class="flashmovie"><strong>You need to upgrade or install Adobe Flash Player</strong></div>';
		$output[] = '';
		$output[] = '<script type="text/javascript">';
		$output[] = '';
		$output[] = '// <![CDATA[';
		$output[] = '';
		$output[] = 'var so_' . $rand . ' = new SWFObject("'. $this->get_plugin_uri() .'viewer.swf","fm_viewer","' . $width . '","' . $height . '","6","' . $bgcolor . '","","' . $quality . '","","");';
		$output[] = 'so_' . $rand . '.addParam("base", "' . $this->get_plugin_uri() . '");';
		$output[] = 'so_' . $rand . '.addVariable("xmlDataPath","' . $this->get_plugin_uri() . 'simpleFlickr.php?parameters=' . $parameters . '");';
		$output[] = 'so_' . $rand . '.useExpressInstall("' . $this->get_plugin_uri() . 'expressinstall.swf");';
		$output[] = 'so_' . $rand . '.write("fo_targ_viewer' . $rand . '");';
		$output[] = '';
		$output[] = '// ]]>';
		$output[] = '';
		$output[] = '</script>';
		$output[] = '';
		$result = join("\n", $output);
	
		return $result;
	}
	
	function build_feed($atts) {
		$result = "";
		
		// Extract out all of the option variables
		if (is_array($atts)) extract($atts);

		// Get the default values for some of the tags
		$simpleFlickrOptionsDB = get_option(SIMPLEFLICKR_OPTIONS_NAME);
		$default_width = $simpleFlickrOptionsDB['WIDTH'];
		$default_height = $simpleFlickrOptionsDB['HEIGHT'];

		// Fill with defaults if no DB value was given
		if(empty($default_width)) 
			$default_width = SIMPLEFLICKR_DEFAULT_WIDTH;
		if(empty($default_height)) 
			$default_height = SIMPLEFLICKR_DEFAULT_HEIGHT;

		// Load some defualts if value not given
		if(empty($width))	$width = $default_width; 
		if(empty($height))	$height = $default_height;
		
		$output[] = '';    
		$output[] = '<object	type="application/x-shockwave-flash"';
		$output[] = '			data="' . $this->get_plugin_uri() . 'viewer.swf"'; 
		$output[] = '			base="' . $this->get_plugin_uri() . '"';
		$output[] = '			width="' . $width . '"';
		$output[] = '			height="' . $height . '">';
		$output[] = '	<param name="movie" value="' . $this->get_plugin_uri() . 'viewer.swf" />';
		$output[] = '	<param name=base" value="' . $this->get_plugin_uri() . '" />';
		$output[] = '</object>';     

		$result .= join("\n", $output);	
	
		return $result;
	}
	
	function get_xml($parameters) {
		// Get the saved default tag values
		$simpleFlickrOptionsDB = get_option(SIMPLEFLICKR_OPTIONS_NAME);
		$showrecent = $simpleFlickrOptionsDB['SHOW_RECENT'];
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
		$count = $simpleFlickrOptionsDB['COUNT'];
		$showimagecaption = $simpleFlickrOptionsDB['SHOW_IMAGE_CAPTION'];
		$showimagelink = $simpleFlickrOptionsDB['SHOW_IMAGE_LINK'];
		
		// Get the values from the tag if given
		$array = split(",", $_GET{parameters});
		if(!empty($array[0]))	$showrecent = $array[0];
		if(!empty($array[1]))	$setid = $array[1];
		if(!empty($array[2]))	$group = $array[2];
		if(!empty($array[3]))	$navposition = $array[3];
		if(!empty($array[4]))	$maximagewidth = $array[4];
		if(!empty($array[5]))	$maximageheight = $array[5];
		if(!empty($array[6]))	$textcolor = $array[6];
		if(!empty($array[7]))	$framecolor = $array[7];
		if(!empty($array[8]))	$framewidth = $array[8];
		if(!empty($array[9]))	$stagepadding = $array[9];
		if(!empty($array[10]))	$thumbnailcolumns = $array[10];
		if(!empty($array[11]))	$thumbnailrows = $array[11];
		if(!empty($array[12]))	$enablerightclickopen = $array[12];
		if(!empty($array[13]))	$title = $array[13];
		if(!empty($array[14]))	$count = $array[14];
		if(!empty($array[15]))	$showimagecaption = $array[15];
		if(!empty($array[16]))	$showimagelink = $array[16];

		// Check if set or group given or if recent set to true
		if( !isset($setid) && !isset($group) && $showrecent != 'true' ) {
			echo('Error: Set or Group parameter must be supplied or Recent must be set to true.');
			exit;
		}

		// Create the phpFlickr object
		$flickr = new phpFlickr(SIMPLEFLICKR_FLICKR_API_KEY, SIMPLEFLICKR_FLICKR_API_SECRET, false);

		// Get auth token
		$auth_token = get_option(SIMPLEFLICKR_TOKEN_NAME);

		if ($auth_token) {
			// Set token and try and authenticate
            $flickr->setToken($auth_token);
            $response = $flickr->auth_checkToken();
         
			// Check if token authenticated
			if (!$response) { 
                // Authentication failed
				$error = $flickr->getErrorMsg();
            } else {
				// Get some user details
				$nsid = $response['user']['nsid'];
				$username = $response['user']['username'];
				$photos_url = $flickr->urls_getUserPhotos($nsid);
            }
        }
		else {
			// Not authenticated
			$error = 'Error: No authorization token available.  Please authorize SimpleFlickr with Flickr before using this plugin.';
		}

		// Check for errors before continuing
		if($error) {
			echo($error);
			exit;
		}
		
		if($showrecent == 'true' ) {
			// Get the user's recent photos
			if($count > 0 ) {
				$photos = $flickr->photos_search(array("user_id" => $nsid, "per_page" => $count));
			}
			else {
				$photos = $flickr->photos_search(array("user_id" => $nsid));
			}
		}
		else {
			if(!empty($group)) {
				// Get the photos for the given group
				if($count > 0 ) {
					$photos = $flickr->groups_pools_getPhotos($group, NULL, NULL, NULL, $count, NULL);
				}
				else {
					$photos = $flickr->groups_pools_getPhotos($group);
				}
			}
			else {
				// Get the phtos for the given set
				if($count > 0 ) {
					$photos = $flickr->photosets_getPhotos($setid, NULL, NULL, $count, NULL);
				}
				else {
					$photos = $flickr->photosets_getPhotos($setid);
				}
			}
		}
		
		// Grab the erros if any
		$error = $flickr->getErrorMsg();
		
		// Do we have any photos? Any errors?
		if(!$photos)
		{
			if(!empty($error)) {
				echo("Flickr API Error: " . $error . "<br />");
			}
			else {
				echo "Error: No photos found.";
			}
			exit;
		}

		// Generate xml output
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
			imagePath="simpleFlickr.php?mode=img&amp;size=large&amp;image="
			thumbPath="simpleFlickr.php?mode=img&amp;size=thumb&amp;image=">
		';
		
		foreach ((array)$photos['photo'] as $photo)
		{
		   $xmlout .= "<IMAGE><NAME>{$photo[server]}/{$photo[id]}_{$photo[secret]}</NAME><CAPTION>";
		   if($showimagecaption == 'true')
		   {
		      if($showimagelink == 'true')
		      {
		         $xmlout .= "<![CDATA[<a href=\"{$photos_url}{$photo[id]}\" target=\"_blank\">{$photo[title]}<br /><u>View flickr photo page...</u></a>]]>";
		      }
		      else
		      {
		         $xmlout .= "<![CDATA[{$photo[title]}]]>";
		      }
		   }
		   $xmlout .= "</CAPTION></IMAGE>\n";
		}
		$xmlout .= "</SIMPLEVIEWER_DATA>";

		// now return the XML for SimpleViewer
		header("Content-Type: text/xml");
		header("X-Cache-Status: Miss");
		header("Last-Modified: " . gmdate("r", time()));
		header("Expires: " . gmdate("r", time()));
		echo $xmlout;
		exit;
	}
}

// Create a new simpleflickr plugin object
$SimpleFlickrPlugin = new SimpleFlickrPlugin();

// Check to see why this page is being called
$mode = isset($_GET['mode']) ? $_GET['mode'] : 'admin';

// Determine what to do based on parameters
if(isset($_GET['parameters'])) {
	// Added these so we can access wp options 
	require_once("../../../wp-config.php");
	require_once("../../../wp-includes/functions.php");
		
	// Create and display the xml data
	$SimpleFlickrPlugin->get_xml($_GET['parameters']);
}
else {
	if($mode == 'img') {
		// Get the image 
		$SimpleFlickrPlugin->get_image();
	}
	else {
		// Display the admin page
		$SimpleFlickrPlugin->init_admin();
	}
}
?>