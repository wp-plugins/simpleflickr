<?php
/*
Plugin Name: SimpleFlickr
Plugin URI: http://www.joshgerdes.com/projects/simpleflickr-plugin/
Donate link: http://www.joshgerdes.com/projects/simpleflickr-plugin/
Description: This plugin allows you to embed a Simpleviewer Flash Object integrated with a Flickr account.
Version: 3.0.2
Author: Josh Gerdes
Author URI: http://www.joshgerdes.com
Contributors: joshgerdes
Tags: flickr, simpleviewer, gallery, images, image, simpleflickr, photos, photo
Requires at least: 2.0
Tested up to: 2.5.1
Stable tag: 3.0.2

Copyright (c) 2007-2008
Released under the GPL license
http://www.gnu.org/licenses/gpl.txt
*/ 

// Required libraries
if(!class_exists("phpflickr"))	require_once(dirname(__FILE__)."/phpFlickr/phpFlickr.php");

// Global Variables and Defaults
define('SIMPLEFLICKR_VERSION', "3.0.2");
define('SIMPLEFLICKR_FLICKR_API_KEY', "97bb421765f720bd26faf71778cb51e6");
define('SIMPLEFLICKR_FLICKR_API_SECRET', "f0036586d57895e7");
define('SIMPLEFLICKR_OPTIONS_NAME', "simpleflickr_options");
define('SIMPLEFLICKR_TOKEN_NAME', "simpleflickr_token");

// Default tag values
define('SIMPLEFLICKR_DEFAULT_WIDTH', "480");
define('SIMPLEFLICKR_DEFAULT_HEIGHT', "680");
define('SIMPLEFLICKR_DEFAULT_QUALITY', "best");
define('SIMPLEFLICKR_DEFAULT_BGCOLOR', "#FFFFFF");
define('SIMPLEFLICKR_DEFAULT_WMODE', "window");

class SimpleFlickrPlugin {
	function SimpleFlickrPlugin() {
	}
	
	function init_admin() {
        // Add the JS to the head
        add_action('wp_head', array(&$this, 'add_swfobject_js'));

        // Add the sub-panel under the OPTIONS panel
		add_action('admin_menu', array(&$this, 'admin_menu'));
        
		// Apply all over except the admin section
		if (strpos($_SERVER['REQUEST_URI'], 'wp-admin') === false ) {
            add_filter('the_content', array(&$this, 'main_filter'));
		}
	}

	function get_image() {
		// Redirect SimpleViewer to the image on flickr
	   $size  = isset($_GET['size'])  ? $_GET['size'] : 'Square';
	   $image = isset($_GET['image']) ? $_GET['image']: '0';
	   $ext = ".jpg";
	   $urlbase = "http://static.flickr.com/";
	   
	   switch(strtolower($size)) {
			case "square":
			$ext = "_s.jpg";
			break;
			case "thumbnail":
			$ext = "_t.jpg";
			break;
			case "small":
			$ext = "_m.jpg";
			break;
			case "medium":
			$ext = ".jpg";
			break;
			case "large":
			$ext = "_b.jpg";
			break;
			case "original":
			$ext = "_o.jpg";
			$image = substr($image, strpos($image, "__") + 2 , strlen($image)); 
			break;
			case "squareoriginal":
			$ext = "_s.jpg";
			$image = substr($image, 0, strpos($image, "__")); 
			break;
	   }

	   // Setup the url for the image
	   $url = $urlbase . $image . $ext;

	   header("HTTP/1.1 301 Moved Permanently");
	   header("Location: $url");
	   exit;
	}
	
	function add_swfobject_js() {
		echo('
		<!-- Added by SimpleFlickr - Version '. SIMPLEFLICKR_VERSION . ' -->
		<script src="' . $this->get_plugin_uri() . 'swfobject/swfobject.js" type="text/javascript"></script>
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

        // Create base HTML
        $html_output = '';
        $html_output .= '<div class="wrap">'. PHP_EOL;
        
		// Display errors, if any
		if(isset($error) && $error != '') {
			$html_output .= '<div class="error"><p><strong>Error: </strong> ' . $error . '</p></div>'. PHP_EOL;
		}
		
        // Get the field values
        if(isset($_POST['info_update'])) {
            
            // Get the values from the form
            
            // Flickr Options
            $simpleflickr_count = trim($_POST['simpleflickr_count']);
            $simpleflickr_showrecent = trim($_POST['simpleflickr_showrecent']);
            $simpleflickr_imagesize = trim($_POST['simpleflickr_imagesize']);
            $simpleflickr_privacyfilter = trim($_POST['simpleflickr_privacyfilter']);
            
            // Flash Options
            $simpleflickr_width = trim($_POST['simpleflickr_width']);
            $simpleflickr_height = trim($_POST['simpleflickr_height']);
            $simpleflickr_quality = trim($_POST['simpleflickr_quality']);
            $simpleflickr_bgcolor = trim($_POST['simpleflickr_bgcolor']);
            $simpleflickr_wmode = trim($_POST['simpleflickr_wmode']);
            
            // SimpleViewer Options
            $simpleflickr_maximagewidth = trim($_POST['simpleflickr_maximagewidth']);
            $simpleflickr_maximageheight = trim($_POST['simpleflickr_maximageheight']);
            $simpleflickr_textcolor = trim($_POST['simpleflickr_textcolor']);
            $simpleflickr_framecolor = trim($_POST['simpleflickr_framecolor']);
            $simpleflickr_framewidth = trim($_POST['simpleflickr_framewidth']);
            $simpleflickr_stagepadding = trim($_POST['simpleflickr_stagepadding']);
            $simpleflickr_navpadding = trim($_POST['simpleflickr_navpadding']);
            $simpleflickr_thumbnailcolumns = trim($_POST['simpleflickr_thumbnailcolumns']);
            $simpleflickr_thumbnailrows = trim($_POST['simpleflickr_thumbnailrows']);
            $simpleflickr_navposition = trim($_POST['simpleflickr_navposition']);
            $simpleflickr_valign = trim($_POST['simpleflickr_valign']);
            $simpleflickr_halign = trim($_POST['simpleflickr_halign']);
            $simpleflickr_title = trim($_POST['simpleflickr_title']);
            $simpleflickr_enablerightclickopen = trim($_POST['simpleflickr_enablerightclickopen']);
            $simpleflickr_backgroundimagepath = trim($_POST['simpleflickr_backgroundimagepath']);
            $simpleflickr_firstimageindex = trim($_POST['simpleflickr_firstimageindex']);
            $simpleflickr_langopenimage = trim($_POST['simpleflickr_langopenimage']);
            $simpleflickr_langabout = trim($_POST['simpleflickr_langabout']);
            $simpleflickr_preloadercolor = trim($_POST['simpleflickr_preloadercolor']);
            
            // Additional Options
            $simpleflickr_showimagecaption = trim($_POST['simpleflickr_showimagecaption']);
            $simpleflickr_imagecaptionlink = trim($_POST['simpleflickr_imagecaptionlink']);
            $simpleflickr_imagecaptionstyle = str_replace(",", "", trim($_POST['simpleflickr_imagecaptionstyle']));
            
            // Alternate Options
            $simpleflickr_xmldatapath = trim($_POST['simpleflickr_xmldatapath']);


            // Add values to a new array to save
            $simpleFlickrOptionsNewArr = array();
            
            // Flickr Options
            $simpleFlickrOptionsNewArr['COUNT'] = $simpleflickr_count;
            $simpleFlickrOptionsNewArr['SHOW_RECENT'] = $simpleflickr_showrecent;
            $simpleFlickrOptionsNewArr['IMAGE_SIZE'] = $simpleflickr_imagesize;
            $simpleFlickrOptionsNewArr['PRIVACY_FILTER'] = $simpleflickr_privacyfilter;
            
            // Flash Options
            $simpleFlickrOptionsNewArr['WIDTH'] = $simpleflickr_width;
            $simpleFlickrOptionsNewArr['HEIGHT'] = $simpleflickr_height;
            $simpleFlickrOptionsNewArr['QUALITY'] = $simpleflickr_quality;
            $simpleFlickrOptionsNewArr['BGCOLOR'] = $simpleflickr_bgcolor;
            $simpleFlickrOptionsNewArr['WMODE'] = $simpleflickr_wmode;
            
            // SimpleViewer Options
            $simpleFlickrOptionsNewArr['MAX_IMAGE_WIDTH'] = $simpleflickr_maximagewidth;
            $simpleFlickrOptionsNewArr['MAX_IMAGE_HEIGHT'] = $simpleflickr_maximageheight;
            $simpleFlickrOptionsNewArr['TEXT_COLOR'] = $simpleflickr_textcolor;
            $simpleFlickrOptionsNewArr['FRAME_COLOR'] = $simpleflickr_framecolor;
            $simpleFlickrOptionsNewArr['FRAME_WIDTH'] = $simpleflickr_framewidth;
            $simpleFlickrOptionsNewArr['STAGE_PADDING'] = $simpleflickr_stagepadding;
            $simpleFlickrOptionsNewArr['NAV_PADDING'] = $simpleflickr_navpadding;
            $simpleFlickrOptionsNewArr['THUMBNAIL_COLUMNS'] =  $simpleflickr_thumbnailcolumns;
            $simpleFlickrOptionsNewArr['THUMBNAIL_ROWS'] = $simpleflickr_thumbnailrows;
            $simpleFlickrOptionsNewArr['NAV_POSITION'] = $simpleflickr_navposition;
            $simpleFlickrOptionsNewArr['VALIGN'] = $simpleflickr_valign;
            $simpleFlickrOptionsNewArr['HALIGN'] = $simpleflickr_halign;
            $simpleFlickrOptionsNewArr['TITLE'] = $simpleflickr_title;
            $simpleFlickrOptionsNewArr['ENABLE_RIGHT_CLICK_OPEN'] = $simpleflickr_enablerightclickopen;
            $simpleFlickrOptionsNewArr['BACKGROUND_IMAGE_PATH'] = $simpleflickr_backgroundimagepath;
            $simpleFlickrOptionsNewArr['FIRST_IMAGE_INDEX'] = $simpleflickr_firstimageindex;
            $simpleFlickrOptionsNewArr['LANG_OPEN_IMAGE'] = $simpleflickr_langopenimage;
            $simpleFlickrOptionsNewArr['LANG_ABOUT'] = $simpleflickr_langabout;
            $simpleFlickrOptionsNewArr['PRELOADER_COLOR'] = $simpleflickr_preloadercolor;
            
            // Additional Options
            $simpleFlickrOptionsNewArr['SHOW_IMAGE_CAPTION'] = $simpleflickr_showimagecaption;
            $simpleFlickrOptionsNewArr['IMAGE_CAPTION_LINK'] = $simpleflickr_imagecaptionlink;
            $simpleFlickrOptionsNewArr['IMAGE_CAPTION_STYLE'] = $simpleflickr_imagecaptionstyle;
            
            // Alternate Options
            $simpleFlickrOptionsNewArr['XML_DATA_PATH'] = $simpleflickr_xmldatapath;
            
            // Save new array to DB
            update_option(SIMPLEFLICKR_OPTIONS_NAME, $simpleFlickrOptionsNewArr);
            
            // Display success if updated
            $html_output .= '<div class="updated"><p><strong>Successfully Saved Settings</strong></p></div>'. PHP_EOL;
        }
        else
        {  
            // Get values from DB
            $simpleFlickrOptionsDB = get_option(SIMPLEFLICKR_OPTIONS_NAME);
            
            // Flickr Options
            $simpleflickr_count = $simpleFlickrOptionsDB['COUNT'];
            $simpleflickr_showrecent = $simpleFlickrOptionsDB['SHOW_RECENT'];
            $simpleflickr_imagesize = $simpleFlickrOptionsDB['IMAGE_SIZE'];
            $simpleflickr_privacyfilter = $simpleFlickrOptionsDB['PRIVACY_FILTER'];
            
            // Flash Options
            $simpleflickr_width = $simpleFlickrOptionsDB['WIDTH'];
            $simpleflickr_height = $simpleFlickrOptionsDB['HEIGHT'];
            $simpleflickr_quality = $simpleFlickrOptionsDB['QUALITY'];
            $simpleflickr_bgcolor = $simpleFlickrOptionsDB['BGCOLOR'];
            $simpleflickr_wmode = $simpleFlickrOptionsDB['WMODE'];
            
            // SimpleViewer Options
            $simpleflickr_maximagewidth = $simpleFlickrOptionsDB['MAX_IMAGE_WIDTH'];
            $simpleflickr_maximageheight = $simpleFlickrOptionsDB['MAX_IMAGE_HEIGHT'];
            $simpleflickr_textcolor = $simpleFlickrOptionsDB['TEXT_COLOR'];
            $simpleflickr_framecolor = $simpleFlickrOptionsDB['FRAME_COLOR'];
            $simpleflickr_framewidth = $simpleFlickrOptionsDB['FRAME_WIDTH'];
            $simpleflickr_stagepadding = $simpleFlickrOptionsDB['STAGE_PADDING'];
            $simpleflickr_navpadding = $simpleFlickrOptionsDB['NAV_PADDING'];
            $simpleflickr_thumbnailcolumns = $simpleFlickrOptionsDB['THUMBNAIL_COLUMNS'];
            $simpleflickr_thumbnailrows = $simpleFlickrOptionsDB['THUMBNAIL_ROWS'];
            $simpleflickr_navposition = $simpleFlickrOptionsDB['NAV_POSITION'];
            $simpleflickr_valign = $simpleFlickrOptionsDB['VALIGN'];
            $simpleflickr_halign = $simpleFlickrOptionsDB['HALIGN'];
            $simpleflickr_title = $simpleFlickrOptionsDB['TITLE'];
            $simpleflickr_enablerightclickopen = $simpleFlickrOptionsDB['ENABLE_RIGHT_CLICK_OPEN'];
            $simpleflickr_backgroundimagepath = $simpleFlickrOptionsDB['BACKGROUND_IMAGE_PATH'];
            $simpleflickr_firstimageindex = $simpleFlickrOptionsDB['FIRST_IMAGE_INDEX'];
            $simpleflickr_langopenimage = $simpleFlickrOptionsDB['LANG_OPEN_IMAGE'];
            $simpleflickr_langabout = $simpleFlickrOptionsDB['LANG_ABOUT'];
            $simpleflickr_preloadercolor = $simpleFlickrOptionsDB['PRELOADER_COLOR'];
            
            // Additional Options
            $simpleflickr_showimagecaption = $simpleFlickrOptionsDB['SHOW_IMAGE_CAPTION'];
            $simpleflickr_imagecaptionlink = $simpleFlickrOptionsDB['IMAGE_CAPTION_LINK'];
            $simpleflickr_imagecaptionstyle = $simpleFlickrOptionsDB['IMAGE_CAPTION_STYLE'];
            
            // Alternate Options
            $simpleflickr_xmldatapath = $simpleFlickrOptionsDB['XML_DATA_PATH'];
        }
        
        // Add HTML for header
        $html_output .= '<h2>SimpleFlickr (v' . SIMPLEFLICKR_VERSION . ') Settings</h2>'. PHP_EOL;
        $html_output .= '<div style="float:right;width:300px;background:#eee;border:1px solid #999;padding:10px;font-size:0.9em;margin-left:10px;">'. PHP_EOL;
        $html_output .= '<h4>Enjoying this Plugin?</h4>'. PHP_EOL;
        $html_output .= '<p>If you like this plugin, and wish to contribute to its development, consider making a donation.</p>'. PHP_EOL;
        $html_output .= '<form action="https://www.paypal.com/cgi-bin/webscr" method="post">'. PHP_EOL;
        $html_output .= '<input type="hidden" name="cmd" value="_s-xclick" />'. PHP_EOL;
        $html_output .= '<input type="image" src="https://www.paypal.com/en_US/i/btn/x-click-but04.gif" style="border-width=\'0\';" name="submit" alt="Make payments with PayPal - it is fast, free and secure!" />'. PHP_EOL;
        $html_output .= '<img alt="" border="0" src="https://www.paypal.com/en_US/i/scr/pixel.gif" width="1" height="1" />'. PHP_EOL;
        $html_output .= '<input type="hidden" name="encrypted" value="-----BEGIN PKCS7-----MIIHXwYJKoZIhvcNAQcEoIIHUDCCB0wCAQExggEwMIIBLAIBADCBlDCBjjELMAkGA1UEBhMCVVMxCzAJBgNVBAgTAkNBMRYwFAYDVQQHEw1Nb3VudGFpbiBWaWV3MRQwEgYDVQQKEwtQYXlQYWwgSW5jLjETMBEGA1UECxQKbGl2ZV9jZXJ0czERMA8GA1UEAxQIbGl2ZV9hcGkxHDAaBgkqhkiG9w0BCQEWDXJlQHBheXBhbC5jb20CAQAwDQYJKoZIhvcNAQEBBQAEgYAYaYwKfOt1Lelc+6RpWAeS81VuBedFX3eMUR1XPYKBR+mjfy0vSN1Mg2p/dXwk9AhZqyI6zywUgJrPpWcb0oiMCBk39fsi3Ur/wBrUUA7WxMH8+SPJZNxIR8/i8ELTnterHtV4Zr7maBwAu8lsIlRfWiryFwxiyn/tc7E3ezkrojELMAkGBSsOAwIaBQAwgdwGCSqGSIb3DQEHATAUBggqhkiG9w0DBwQIZMyzs5D7eReAgbhM41AvuAz8De4IUFKbFRIUvDWNKZctH0Ul8+N7UpOtHULe5yQi+mTwKkyHpsYiXg8fZ9RMdp+gYMFnaO1Hvwq/+ldnhLxAvjkyJICNoDgPbon5oxHNvkCPEe+hMKfGkhnc4+mhX41O4kaWgJFrE00p2KOxx9IXvOVq1BTbtLSiTd45m5nOhRgpknpiN1O6QyN7iiJQa9oewiaVZksnmC1ETS/ZPrlSWgFDEM2ppul7aVgoEIRCYi6SoIIDhzCCA4MwggLsoAMCAQICAQAwDQYJKoZIhvcNAQEFBQAwgY4xCzAJBgNVBAYTAlVTMQswCQYDVQQIEwJDQTEWMBQGA1UEBxMNTW91bnRhaW4gVmlldzEUMBIGA1UEChMLUGF5UGFsIEluYy4xEzARBgNVBAsUCmxpdmVfY2VydHMxETAPBgNVBAMUCGxpdmVfYXBpMRwwGgYJKoZIhvcNAQkBFg1yZUBwYXlwYWwuY29tMB4XDTA0MDIxMzEwMTMxNVoXDTM1MDIxMzEwMTMxNVowgY4xCzAJBgNVBAYTAlVTMQswCQYDVQQIEwJDQTEWMBQGA1UEBxMNTW91bnRhaW4gVmlldzEUMBIGA1UEChMLUGF5UGFsIEluYy4xEzARBgNVBAsUCmxpdmVfY2VydHMxETAPBgNVBAMUCGxpdmVfYXBpMRwwGgYJKoZIhvcNAQkBFg1yZUBwYXlwYWwuY29tMIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQDBR07d/ETMS1ycjtkpkvjXZe9k+6CieLuLsPumsJ7QC1odNz3sJiCbs2wC0nLE0uLGaEtXynIgRqIddYCHx88pb5HTXv4SZeuv0Rqq4+axW9PLAAATU8w04qqjaSXgbGLP3NmohqM6bV9kZZwZLR/klDaQGo1u9uDb9lr4Yn+rBQIDAQABo4HuMIHrMB0GA1UdDgQWBBSWn3y7xm8XvVk/UtcKG+wQ1mSUazCBuwYDVR0jBIGzMIGwgBSWn3y7xm8XvVk/UtcKG+wQ1mSUa6GBlKSBkTCBjjELMAkGA1UEBhMCVVMxCzAJBgNVBAgTAkNBMRYwFAYDVQQHEw1Nb3VudGFpbiBWaWV3MRQwEgYDVQQKEwtQYXlQYWwgSW5jLjETMBEGA1UECxQKbGl2ZV9jZXJ0czERMA8GA1UEAxQIbGl2ZV9hcGkxHDAaBgkqhkiG9w0BCQEWDXJlQHBheXBhbC5jb22CAQAwDAYDVR0TBAUwAwEB/zANBgkqhkiG9w0BAQUFAAOBgQCBXzpWmoBa5e9fo6ujionW1hUhPkOBakTr3YCDjbYfvJEiv/2P+IobhOGJr85+XHhN0v4gUkEDI8r2/rNk1m0GA8HKddvTjyGw/XqXa+LSTlDYkqI8OwR8GEYj4efEtcRpRYBxV8KxAW93YDWzFGvruKnnLbDAF6VR5w/cCMn5hzGCAZowggGWAgEBMIGUMIGOMQswCQYDVQQGEwJVUzELMAkGA1UECBMCQ0ExFjAUBgNVBAcTDU1vdW50YWluIFZpZXcxFDASBgNVBAoTC1BheVBhbCBJbmMuMRMwEQYDVQQLFApsaXZlX2NlcnRzMREwDwYDVQQDFAhsaXZlX2FwaTEcMBoGCSqGSIb3DQEJARYNcmVAcGF5cGFsLmNvbQIBADAJBgUrDgMCGgUAoF0wGAYJKoZIhvcNAQkDMQsGCSqGSIb3DQEHATAcBgkqhkiG9w0BCQUxDxcNMDcxMDI2MTU0NzQzWjAjBgkqhkiG9w0BCQQxFgQUwP2yeUht4mn+/0mafuKNvcmR47EwDQYJKoZIhvcNAQEBBQAEgYBooexRUFNaQd9/TfoQl6US9VNFaxLmCCnTkW8UMBdAFkBZHXUU3PoIrHb84XMQF+lyGAq6GKUDzZ21PEsFKmCZY/dZQM25PoqvDN496EDQM5nEoGbK3cJBQbOqam6Sfcose/s1yoBAUE9Pi5kCWHIKO+rXHhm3JG1J/fR0WoOEbw==-----END PKCS7-----
" />';
        $html_output .= '</form></div>'. PHP_EOL;

        $html_output .= '<p>This is a plugin for Wordpress which allows you to display images from a Flickr account by emdedding the SimpleViewer flash application into your Wordpress site.  Please visit the <a href="http://www.joshgerdes.com/projects/simpleflickr-plugin/" target="_blank">official website</a> for the latest information on this plugin.</p>'. PHP_EOL;

        $html_output .= '<br clear="all" />'. PHP_EOL;
        $html_output .= '<form class="form-table" name="dofollow" action="" method="post">'. PHP_EOL;
            
        
        // Create HTML for flickr options
        $html_flickroptions = array();
        $html_flickroptions[] = '<h3>Flickr Options</h3>';
        $html_flickroptions[] = '<p>The following options are related to how the plugin accesses and retrieves images from flickr.</p>';

        $html_flickroptions[] = '<p><strong>Current Authorized Flickr User:</strong>&nbsp;<a href="'. $photos_url .'" target="_blank">'. $username .'</a>&nbsp;&nbsp;<input type="submit" name="Reset" value="Reset Flickr Authorization" /></p>';

        $html_flickroptions[] = '<table class="form-table">';
        $html_flickroptions[] = '<tr valign="top">';
        $html_flickroptions[] = '<th scope="row">Photo Count (count)</th>';
        $html_flickroptions[] = '<td><input name="simpleflickr_count" id="simpleflickr_count" type="text" value="'. $simpleflickr_count .'" size="4" maxlength="3" />';
        $html_flickroptions[] = '<br />';
        $html_flickroptions[] = 'The number of images to be displayed. The maximum number the flickr API allows is 500. For unlimited set to \'0\'.  Default is \'0\'. ';
        $html_flickroptions[] = '</td>';
        $html_flickroptions[] = '</tr>';
        $html_flickroptions[] = '<tr valign="top">';
        $html_flickroptions[] = '<th scope="row">Show Recent Photos (showrecent)</th>';
        $html_flickroptions[] = '<td><select name="simpleflickr_showrecent" id="simpleflickr_showrecent">';
        $option = '<option value="false"';
        if($simpleflickr_showrecent=='false')	$option .= ' selected="selected"';
        $html_flickroptions[] = $option .'>False</option>';
        $option = '<option value="true"';
        if($simpleflickr_showrecent=='true')	$option .= ' selected="selected"';
        $html_flickroptions[] = $option .'>True</option>';
        $html_flickroptions[] = '</select>';
        $html_flickroptions[] = '<br />';
        $html_flickroptions[] = 'Determines the user\'s recent photos are displayed by default. This option takes precedences over the \'set\' and \'group\' options. So, if this is set to true then your recent photos will be displayed even if you have added the \'set\' or \'group\' attribute to the tag. Default is \'false\'.'; 
        $html_flickroptions[] = '</td>';
        $html_flickroptions[] = '</tr>';
        $html_flickroptions[] = '<tr valign="top">';
        $html_flickroptions[] = '<th scope="row">Image Size (imagesize)</th>';
        $html_flickroptions[] = '<td><select name="simpleflickr_imagesize" id="simpleflickr_imagesize">';
        $option = '	<option value="Medium"'; 
        if($simpleflickr_imagesize=='Medium')	$option .= ' selected="selected"';
        $html_flickroptions[] = $option .'>Medium</option>';
        $option = '	<option value="Square" '; 
        if($simpleflickr_imagesize=='Square')	$option .= ' selected="selected"';
        $html_flickroptions[] = $option .'>Square</option>';
        $option = '	<option value="Thumbnail"'; 
        if($simpleflickr_imagesize=='Thumbnail')	$option .= ' selected="selected"';
        $html_flickroptions[] = $option .'>Thumbnail</option>';
        $option = '	<option value="Small"'; 
        if($simpleflickr_imagesize=='Small')	$option .= ' selected="selected"';
        $html_flickroptions[] = $option .'>Small</option>';
        $option = '	<option value="Large"'; 
        if($simpleflickr_imagesize=='Large')	$option .= ' selected="selected"';
        $html_flickroptions[] = $option .'>Large</option>';
        $option = '	<option value="Original"'; 
        if($simpleflickr_imagesize=='Original')	$option .= ' selected="selected"';
        $html_flickroptions[] = $option .'>Original</option>';
        $html_flickroptions[] = '</select>'; 
        $html_flickroptions[] = '<br />'; 
        $html_flickroptions[] = 'You must provide the size of the image displayed in the simpleviewer flash object. Can be \'Square\', \'Thumbnail\', \'Small\', \'Medium\', \'Large\', \'Original\'. Default is \'Medium\'.';  
        $html_flickroptions[] = '</td>'; 
        $html_flickroptions[] = '</tr>'; 
        $html_flickroptions[] = '<tr valign="top">'; 
        $html_flickroptions[] = '<th scope="row">Privacy Filter (privacyfilter)</th>'; 
        $html_flickroptions[] = '<td><select name="simpleflickr_privacyfilter" id="simpleflickr_privacyfilter">'; 
        $option = '   <option value="1"'; 
        if($simpleflickr_privacyfilter=='1')	$option .= ' selected="selected"';
        $html_flickroptions[] = $option .'>Public photos</option>'; 
        $option = '    <option value="2"'; 
        if($simpleflickr_privacyfilter=='2')	$option .= ' selected="selected"';
        $html_flickroptions[] = $option .'>Private photos visible to friends</option>'; 
        $option = '    <option value="3"'; 
        if($simpleflickr_privacyfilter=='3')	$option .= ' selected="selected"';
        $html_flickroptions[] = $option .'>Private photos visible to family</option>'; 
        $option = '    <option value="4"'; 
        if($simpleflickr_privacyfilter=='4')	$option .= ' selected="selected"';
        $html_flickroptions[] = $option .'>Private photos visible to friends &amp; family</option>'; 	
        $option = '    <option value="5"'; 
        if($simpleflickr_privacyfilter=='5')	$option .= ' selected="selected"';
        $html_flickroptions[] = $option .'>Completely private photos</option>'; 
        $html_flickroptions[] = '</select>'; 
        $html_flickroptions[] = '<br />'; 
        $html_flickroptions[] = 'Determines what photos are displayed based on the level of privacy selected. Values can be \'Public photos\', \'Private photos visible to friends\', \'Private photos visible to family\', \'Private photos visible to friends &amp; family\', \'Completely private photos\'. Default is \'Public photos\'. '; 
        $html_flickroptions[] = '</td>'; 
        $html_flickroptions[] = '</tr>'; 
        $html_flickroptions[] = '</table>'; 

        // Create HTML for flash options
        $html_flashoptions = array();
        $html_flashoptions[] = '<h3>Flash Object Options</h3>'; 
        $html_flashoptions[] = '<p>The following options are general options related to the flash object displayed by this plugin.</p>'; 
        $html_flashoptions[] = '<table class="form-table">'; 
        $html_flashoptions[] = '<tr valign="top">'; 
        $html_flashoptions[] = '<th scope="row">Width (width)</th>'; 
        $html_flashoptions[] = '<td><input name="simpleflickr_width" id="simpleflickr_width" type="text" value="'. $simpleflickr_width .'" size="4" maxlength="4" />'; 
        $html_flashoptions[] = '<br />'; 
        $html_flashoptions[] = 'Specifies the width of the movie in either pixels or percentage of browser window. Default is \'480\'. '; 
        $html_flashoptions[] = '</td>'; 
        $html_flashoptions[] = '</tr>'; 
        $html_flashoptions[] = '<tr valign="top">'; 
        $html_flashoptions[] = '<th scope="row">Height (height)</th>'; 
        $html_flashoptions[] = '<td><input name="simpleflickr_height" id="simpleflickr_height" type="text" value="'. $simpleflickr_height .'" size="4" maxlength="4" />'; 
        $html_flashoptions[] = '<br />'; 
        $html_flashoptions[] = 'Specifies the height of the movie in either pixels or percentage of browser window. Default is \'680\'.';  
        $html_flashoptions[] = '</td>'; 
        $html_flashoptions[] = '</tr>'; 
        $html_flashoptions[] = '<tr valign="top">'; 
        $html_flashoptions[] = '<th scope="row">Quality (quality)</th>'; 
        $html_flashoptions[] = '<td><select name="simpleflickr_quality" id="simpleflickr_quality">'; 
        $option = '    <option value="best"'; 
        if($simpleflickr_quality=='best')	$option .= ' selected="selected"';
        $html_flashoptions[] = $option .'>Best</option>'; 
        $option = '    <option value="high"'; 
        if($simpleflickr_quality=='high')	$option .= ' selected="selected"';
        $html_flashoptions[] = $option .'>High</option>'; 
        $option = '    <option value="medium"'; 
        if($simpleflickr_quality=='medium')	$option .= ' selected="selected"';
        $html_flashoptions[] = $option .'>Medium</option>'; 
        $option = '    <option value="autohigh"'; 
        if($simpleflickr_quality=='autohigh')	$option .= ' selected="selected"';
        $html_flashoptions[] = $option .'>Autohigh</option>'; 
        $option = '    <option value="autolow"'; 
        if($simpleflickr_quality=='autolow')	$option .= ' selected="selected"';
        $html_flashoptions[] = $option .'>Autolow</option>'; 
        $option = '    <option value="low"'; 
        if($simpleflickr_quality=='low')	$option .= ' selected="selected"';
        $html_flashoptions[] = $option .'>Low</option>'; 
        $html_flashoptions[] = '</select>'; 
        $html_flashoptions[] = '<br />'; 
        $html_flashoptions[] = 'Specifies the quality of the simpleviewer flash object. Can be \'low\', \'high\', \'autolow\', \'autohigh\', \'best\'. Default is \'best\'. '; 
        $html_flashoptions[] = '</td>'; 
        $html_flashoptions[] = '</tr>'; 
        $html_flashoptions[] = '<tr valign="top">'; 
        $html_flashoptions[] = '<th scope="row">Background Color (bgcolor)</th>'; 
        $html_flashoptions[] = '<td><input name="simpleflickr_bgcolor" id="simpleflickr_bgcolor" type="text" value="'. $simpleflickr_bgcolor .'" size="7" maxlength="7" />'; 
        $html_flashoptions[] = '<br />'; 
        $html_flashoptions[] = 'Specifies the background color (hexidecimal color value e.g #FF00FF) of the movie. This attribute does not affect the background color of the HTML page. Default is #FFFFFF. Is ignored if <strong>Window Mode</strong> is set to \'transparent\'. '; 
        $html_flashoptions[] = '</td>'; 
        $html_flashoptions[] = '</tr>'; 
        $html_flashoptions[] = '<tr valign="top">'; 
        $html_flashoptions[] = '<th scope="row">Window Mode (wmode)</th>'; 
        $html_flashoptions[] = '<td><select name="simpleflickr_wmode" id="simpleflickr_wmode">'; 
        $option = '    <option value="window"';
        if($simpleflickr_wmode=='window')	$option .= ' selected="selected"';
        $html_flashoptions[] = $option .'>Window</option>'; 
        $option = '    <option value="opaque"';
        if($simpleflickr_wmode=='opaque')	$option .= ' selected="selected"';
        $html_flashoptions[] = $option .'>Opaque</option>'; 
        $option = '    <option value="transparent"';
        if($simpleflickr_wmode=='transparent')	$option .= ' selected="selected"';
        $html_flashoptions[] = $option .'>Transparent</option>'; 
        $html_flashoptions[] = '</select>'; 
        $html_flashoptions[] = '<br />'; 
        $html_flashoptions[] = 'Sets the Window Mode property of the Flash movie for transparency, layering, and positioning in the browser. Can be \'window\', \'opaque\', \'transparent\'. Default is \'window\'. Overrides <strong>Background Color</strong> if set to \'transparent\'.'; 
        $html_flashoptions[] = '</td>'; 
        $html_flashoptions[] = '</tr>'; 
        $html_flashoptions[] = '</table>'; 

        // Create HTML for simpleviewer options
        $html_simplevieweroptions = array();
        $html_simplevieweroptions[] = '<h3>SimpleViewer Options</h3>'; 
        $html_simplevieweroptions[] = '<p>This plugin uses the SimpleViewer flash application for displaying images from Flickr.  SimpleViewer can be customized by setting the following options.  Please visit the <a href="http://www.airtightinteractive.com/simpleviewer/options.html" target="_blank">SimpleViewer Options Page</a> for more details.</p>'; 
        $html_simplevieweroptions[] = '<table class="form-table">'; 
        $html_simplevieweroptions[] = '<tr valign="top">'; 
        $html_simplevieweroptions[] = '<th scope="row">Max Image Width (maximagewidth)</th>'; 
        $html_simplevieweroptions[] = '<td><input name="simpleflickr_maximagewidth" id="simpleflickr_maximagewidth" type="text" value="'. $simpleflickr_maximagewidth .'" size="4" maxlength="4" />'; 
        $html_simplevieweroptions[] = '<br />'; 
        $html_simplevieweroptions[] = 'Width of the widest image in the gallery. Used to determine the best layout for your gallery (pixels). Default is \'480\'.'; 
        $html_simplevieweroptions[] = '</td>'; 
        $html_simplevieweroptions[] = '</tr>'; 
        $html_simplevieweroptions[] = '<tr valign="top">'; 
        $html_simplevieweroptions[] = '<th scope="row">Max Image Height (maximageheight)</th>'; 
        $html_simplevieweroptions[] = '<td><input name="simpleflickr_maximageheight" id="simpleflickr_maximageheight" type="text" value="'. $simpleflickr_maximageheight .'" size="4" maxlength="4" />'; 
        $html_simplevieweroptions[] = '<br />'; 
        $html_simplevieweroptions[] = 'Height of tallest image in the gallery. Used to determine the best layout for your gallery (pixels).  Default is \'680\'.'; 
        $html_simplevieweroptions[] = '</td>'; 
        $html_simplevieweroptions[] = '</tr>'; 
        $html_simplevieweroptions[] = '<tr valign="top">'; 
        $html_simplevieweroptions[] = '<th scope="row">Text Color (textcolor)</th>'; 
        $html_simplevieweroptions[] = '<td><input name="simpleflickr_textcolor" id="simpleflickr_textcolor" type="text" value="'. $simpleflickr_textcolor .'" size="8" maxlength="8" />'; 
        $html_simplevieweroptions[] = '<br />'; 
        $html_simplevieweroptions[] = 'Color of title and caption text (hexidecimal color e.g. 0xFF00FF).  Default is \'0xFFFFFF\'.'; 
        $html_simplevieweroptions[] = '</td>'; 
        $html_simplevieweroptions[] = '</tr>'; 
        $html_simplevieweroptions[] = '<tr valign="top">'; 
        $html_simplevieweroptions[] = '<th scope="row">Frame Color (framecolor)</th>'; 
        $html_simplevieweroptions[] = '<td><input name="simpleflickr_framecolor" id="simpleflickr_framecolor" type="text" value="'. $simpleflickr_framecolor .'" size="8" maxlength="8" />'; 
        $html_simplevieweroptions[] = '<br />'; 
        $html_simplevieweroptions[] = 'Color of image frame, navigation buttons and thumbnail frame (hexidecimal color value e.g. 0xFF00FF).  Default is \'0xFFFFFF\'.'; 
        $html_simplevieweroptions[] = '</td>'; 
        $html_simplevieweroptions[] = '</tr>'; 
        $html_simplevieweroptions[] = '<tr valign="top">'; 
        $html_simplevieweroptions[] = '<th scope="row">Frame Width (framewidth)</th>'; 
        $html_simplevieweroptions[] = '<td><input name="simpleflickr_framewidth" id="simpleflickr_framewidth" type="text" value="'. $simpleflickr_framewidth .'" size="4" maxlength="4" />'; 
        $html_simplevieweroptions[] = '<br />'; 
        $html_simplevieweroptions[] = 'Width of image frame (pixels). Default is \'20\'.'; 
        $html_simplevieweroptions[] = '</td>'; 
        $html_simplevieweroptions[] = '</tr>'; 
        $html_simplevieweroptions[] = '<tr valign="top">'; 
        $html_simplevieweroptions[] = '<th scope="row">Stage Padding (stagepadding)</th>'; 
        $html_simplevieweroptions[] = '<td><input name="simpleflickr_stagepadding" id="simpleflickr_stagepadding" type="text" value="'. $simpleflickr_stagepadding .'" size="4" maxlength="4" />'; 
        $html_simplevieweroptions[] = '<br />'; 
        $html_simplevieweroptions[] = 'Width of padding around gallery edge (pixels). To have the image flush to the edge of the swf, set this to \'0\'.  Default is \'40\'. '; 
        $html_simplevieweroptions[] = '</td>'; 
        $html_simplevieweroptions[] = '</tr>'; 
        $html_simplevieweroptions[] = '<tr valign="top">'; 
        $html_simplevieweroptions[] = '<th scope="row">Nav Padding (navpadding)</th>'; 
        $html_simplevieweroptions[] = '<td><input name="simpleflickr_navpadding" id="simpleflickr_navpadding" type="text" value="'. $simpleflickr_navpadding .'" size="4" maxlength="4" />'; 
        $html_simplevieweroptions[] = '<br />'; 
        $html_simplevieweroptions[] = 'Distance between image and thumbnails (pixels).  Default is \'40\'.'; 
        $html_simplevieweroptions[] = '</td>'; 
        $html_simplevieweroptions[] = '</tr>'; 
        $html_simplevieweroptions[] = '<tr valign="top">'; 
        $html_simplevieweroptions[] = '<th scope="row">Thumbnail Columns (thumbnailcolumns)</th>'; 
        $html_simplevieweroptions[] = '<td><input name="simpleflickr_thumbnailcolumns" id="simpleflickr_thumbnailcolumns" type="text" value="'. $simpleflickr_thumbnailcolumns .'" size="4" maxlength="3" />'; 
        $html_simplevieweroptions[] = '<br />'; 
        $html_simplevieweroptions[] = 'Number of thumbnail columns. To disable thumbnails completely set this value to \'0\'.  Default is \'3\'.'; 
        $html_simplevieweroptions[] = '</td>'; 
        $html_simplevieweroptions[] = '</tr>'; 
        $html_simplevieweroptions[] = '<tr valign="top">'; 
        $html_simplevieweroptions[] = '<th scope="row">Thumbnail Rows (thumbnailrows)</th>'; 
        $html_simplevieweroptions[] = '<td><input name="simpleflickr_thumbnailrows" id="simpleflickr_thumbnailrows" type="text" value="'. $simpleflickr_thumbnailrows .'" size="4" maxlength="3" />'; 
        $html_simplevieweroptions[] = '<br />'; 
        $html_simplevieweroptions[] = 'Number of thumbnail rows. To disable thumbnails completely set this value to \'0\'.  Default is \'3\'.'; 
        $html_simplevieweroptions[] = '</td>'; 
        $html_simplevieweroptions[] = '</tr>'; 
        $html_simplevieweroptions[] = '<tr valign="top">'; 
        $html_simplevieweroptions[] = '<th scope="row">Nav Position (navposition)</th>'; 
        $html_simplevieweroptions[] = '<td><select name="simpleflickr_navposition" id="simpleflickr_navposition">'; 
        $option = '    <option value="left"';
        if($simpleflickr_navposition=='left')	$option .= ' selected="selected"';
        $html_simplevieweroptions[] = $option .'>Left</option>'; 
        $option = '    <option value="right"';
        if($simpleflickr_navposition=='right')	$option .= ' selected="selected"';
        $html_simplevieweroptions[] = $option .'>Right</option>'; 
        $option = '    <option value="bottom"';
        if($simpleflickr_navposition=='bottom')	$option .= ' selected="selected"';
        $html_simplevieweroptions[] = $option .'>Bottom</option>'; 
        $option = '    <option value="top"';
        if($simpleflickr_navposition=='top')	$option .= ' selected="selected"';
        $html_simplevieweroptions[] = $option .'>Top</option>'; 
        $html_simplevieweroptions[] = '</select>'; 
        $html_simplevieweroptions[] = '<br />'; 
        $html_simplevieweroptions[] = 'Position of thumbnails relative to image. Can be \'top\', \'bottom\', \'left\' or \'right\'.  Default is \'left\'. '; 
        $html_simplevieweroptions[] = '</td>'; 
        $html_simplevieweroptions[] = '</tr>';
        $html_simplevieweroptions[] = '<tr valign="top">';
        $html_simplevieweroptions[] = '<th scope="row">Vertical Alignment (valign)</th>';
        $html_simplevieweroptions[] = '<td><select name="simpleflickr_valign" id="simpleflickr_valign">';
        $option = '    <option value="center"';
        if($simpleflickr_valign=='center')	$option .= ' selected="selected"';
        $html_simplevieweroptions[] = $option .'>Center</option>';
        $option = '    <option value="top"';
        if($simpleflickr_valign=='top')	$option .= ' selected="selected"';
        $html_simplevieweroptions[] = $option .'>Top</option>';
        $option = '    <option value="bottom"';
        if($simpleflickr_valign=='bottom')	$option .= ' selected="selected"';
        $html_simplevieweroptions[] = $option .'>Bottom</option>';
        $html_simplevieweroptions[] = '</select>';
        $html_simplevieweroptions[] = '<br />';
        $html_simplevieweroptions[] = 'Vertical placment of the image and thumbnails within the SWF. Can be \'center\', \'top\' or \'bottom\'.  Default is \'center\'. ';
        $html_simplevieweroptions[] = '<br />';
        $html_simplevieweroptions[] = 'For large format galleries this is best set to \'center\'. For small format galleries setting this to \'top\' or \'bottom\' can help get the image flush to the edge of the swf.';
        $html_simplevieweroptions[] = '</td>';
        $html_simplevieweroptions[] = '</tr>';
        $html_simplevieweroptions[] = '<tr valign="top">';
        $html_simplevieweroptions[] = '<th scope="row">Horizontal Alignment (halign)</th>';
        $html_simplevieweroptions[] = '<td><select name="simpleflickr_halign" id="simpleflickr_halign">';
        $option = '    <option value="center"';
        if($simpleflickr_halign=='center')	$option .= ' selected="selected"';
        $html_simplevieweroptions[] = $option .'>Center</option>';
        $option = '    <option value="left"';
        if($simpleflickr_halign=='left')	$option .= ' selected="selected"';
        $html_simplevieweroptions[] = $option .'>Left</option>';
        $option = '    <option value="right"';
        if($simpleflickr_halign=='right')	$option .= ' selected="selected"';
        $html_simplevieweroptions[] = $option .'>Right</option>';
        $html_simplevieweroptions[] = '</select>';
        $html_simplevieweroptions[] = '<br />';
        $html_simplevieweroptions[] = 'Horizontal placement of the image and thumbnails within the SWF. Can be \'center\', \'left\' or \'right\'.  Default is \'center\'.'; 
        $html_simplevieweroptions[] = '<br />';
        $html_simplevieweroptions[] = 'For large format galleries this is best set to \'center\'. For small format galleries setting this to \'left\' or \'right\' can help get the image flush to the edge of the swf.';
        $html_simplevieweroptions[] = '</td>';
        $html_simplevieweroptions[] = '</tr>';
        $html_simplevieweroptions[] = '<tr valign="top">';
        $html_simplevieweroptions[] = '<th scope="row">Title (title)</th>';
        $html_simplevieweroptions[] = '<td><input name="simpleflickr_title" id="simpleflickr_title" style="width: 95%" type="text" value="'. $simpleflickr_title .'" size="45" />';
        $html_simplevieweroptions[] = '<br />';
        $html_simplevieweroptions[] = 'Text to display as gallery Title.  Default is blank.';
        $html_simplevieweroptions[] = '</td>';
        $html_simplevieweroptions[] = '</tr>';
        $html_simplevieweroptions[] = '<tr valign="top">';
        $html_simplevieweroptions[] = '<th scope="row">Enable Right Click Open (enablerightclickopen)</th>';
        $html_simplevieweroptions[] = '<td><select name="simpleflickr_enablerightclickopen" id="simpleflickr_enablerightclickopen">';
        $option = '    <option value="true"';
        if($simpleflickr_enablerightclickopen=='true')	$option .= ' selected="selected"';
        $html_simplevieweroptions[] = $option .'>True</option>';
        $option = '    <option value="false"';
        if($simpleflickr_enablerightclickopen=='false')	$option .= ' selected="selected"';
        $html_simplevieweroptions[] = $option .'>False</option>';
        $html_simplevieweroptions[] = '</select>';
        $html_simplevieweroptions[] = '<br />';
        $html_simplevieweroptions[] = 'Whether to display a \'Open In new Window...\' dialog when right-clicking on an image. Can be \'true\' or \'false\'.  Default is \'true\'.'; 
        $html_simplevieweroptions[] = '</td>';
        $html_simplevieweroptions[] = '</tr>';
        $html_simplevieweroptions[] = '<tr valign="top">';
        $html_simplevieweroptions[] = '<th scope="row">Background Image Path (backgroundimagepath)</th>';
        $html_simplevieweroptions[] = '<td><input name="simpleflickr_backgroundimagepath" id="simpleflickr_backgroundimagepath" style="width: 95%" type="text" value="'. $simpleflickr_backgroundimagepath .'" size="45" />';
        $html_simplevieweroptions[] = '<br />';
        $html_simplevieweroptions[] = 'Relative or absolute path to a JPG or SWF to load as the gallery background.  Default is blank.';
        $html_simplevieweroptions[] = '<br />';
        $html_simplevieweroptions[] = 'Relative paths are relative to the HTML document that contains SimpleViewer. For example: \'images/bkgnd.jpg\'.'; 
        $html_simplevieweroptions[] = '</td>';
        $html_simplevieweroptions[] = '</tr>';
        $html_simplevieweroptions[] = '<tr valign="top">';
        $html_simplevieweroptions[] = '<th scope="row">First Image Index (firstimageindex)</th>';
        $html_simplevieweroptions[] = '<td><input name="simpleflickr_firstImageIndex" id="simpleflickr_firstImageIndex" type="text" value="'. $simpleflickr_firstimageindex .'" size="4" maxlength="3" />';
        $html_simplevieweroptions[] = '<br />';
        $html_simplevieweroptions[] = 'Index of image to display when gallery loads. Images are numbered beginning at zero. You can use this option to display a specific number based on the URL. Default is \'0\'.';
        $html_simplevieweroptions[] = '</td>';
        $html_simplevieweroptions[] = '</tr>';
        $html_simplevieweroptions[] = '<tr valign="top">';
        $html_simplevieweroptions[] = '<th scope="row">Open Image Text (langopenimage)</th>';
        $html_simplevieweroptions[] = '<td><input name="simpleflickr_langopenimage" id="simpleflickr_langopenimage" style="width: 95%" type="text" value="'. $simpleflickr_langopenimage .'" size="45" />';
        $html_simplevieweroptions[] = '<br />';
        $html_simplevieweroptions[] = 'The text displayed for the right-click \'Open Image in New Window\' menu option. Can be used to translate SimpleViewer into a non-English language.  Default is \'Open Image in New Window\'.';
        $html_simplevieweroptions[] = '</td>';
        $html_simplevieweroptions[] = '</tr>';
        $html_simplevieweroptions[] = '<tr valign="top">';
        $html_simplevieweroptions[] = '<th scope="row">About Text (langabout)</th>';
        $html_simplevieweroptions[] = '<td><input name="simpleflickr_langabout" id="simpleflickr_langabout" style="width: 95%" type="text" value="'. $simpleflickr_langabout .'" size="45" />';
        $html_simplevieweroptions[] = '<br />';
        $html_simplevieweroptions[] = 'The text displayed for the right-click \'About\' menu option. Can be used to translate SimpleViewer into a non-English language.  Default is \'About\'.';
        $html_simplevieweroptions[] = '</td>';
        $html_simplevieweroptions[] = '</tr>';
        $html_simplevieweroptions[] = '<tr valign="top">';
        $html_simplevieweroptions[] = '<th scope="row">Preloader Color (preloadercolor)</th>';
        $html_simplevieweroptions[] = '<td><input name="simpleflickr_preloadercolor" id="simpleflickr_preloadercolor" type="text" value="'. $simpleflickr_preloadercolor .'" size="8" maxlength="8" />';
        $html_simplevieweroptions[] = '<br />';
        $html_simplevieweroptions[] = 'Preloader color (hexidecimal color value).  Default is \'0xFFFFFF\'.';
        $html_simplevieweroptions[] = '</td>';
        $html_simplevieweroptions[] = '</tr>';
        $html_simplevieweroptions[] = '</table>';

        // Create HTML for Additional options
        $html_additionaloptions = array();
        $html_additionaloptions[] = '<h3>Additional Options</h3>';
        $html_additionaloptions[] = '<p>The following are additional options to control how SimpleViewer displays images within this plugin.</p>';
        $html_additionaloptions[] = '<table class="form-table">';
        $html_additionaloptions[] = '<tr valign="top">';
        $html_additionaloptions[] = '<th scope="row">Show Image Caption (showimagecaption)</th>';
        $html_additionaloptions[] = '<td><select name="simpleflickr_showimagecaption" id="simpleflickr_showimagecaption">';
        $option = '    <option value="true"';
        if($simpleflickr_showimagecaption=='true')	$option .= ' selected="selected"';
        $html_additionaloptions[] = $option .'>True</option>';
        $option = '    <option value="false"';
        if($simpleflickr_showimagecaption=='false')	$option .= ' selected="selected"';
        $html_additionaloptions[] = $option .'>False</option>';
        $html_additionaloptions[] = '</select>';
        $html_additionaloptions[] = '<br />';
        $html_additionaloptions[] = 'Specifies if the image caption is displayed. Can be \'true\' or \'false\'. Default is \'true\'. ';
        $html_additionaloptions[] = '</td>';
        $html_additionaloptions[] = '</tr>';
        $html_additionaloptions[] = '<tr valign="top">';
        $html_additionaloptions[] = '<th scope="row">Image Caption Link (imagecaptionlink)</th>';
        $html_additionaloptions[] = '<td><select name="simpleflickr_imagecaptionlink" id="simpleflickr_imagecaptionlink">';
        $option = '    <option value="true"';
        if($simpleflickr_imagecaptionlink=='true')	$option .= ' selected="selected"';
        $html_additionaloptions[] = $option .'>True</option>';
        $option = '    <option value="false"';
        if($simpleflickr_imagecaptionlink=='false')	$option .= ' selected="selected"';
        $html_additionaloptions[] = $option .'>False</option>';
        $html_additionaloptions[] = '</select>';
        $html_additionaloptions[] = '<br />';
        $html_additionaloptions[] = 'Specifies if the image caption text is a link to the flickr image page.  Can be \'true\' or \'false\'. Default is \'true\'. ';
        $html_additionaloptions[] = '</td>';
        $html_additionaloptions[] = '</tr>';
        $html_additionaloptions[] = '<tr valign="top">';
        $html_additionaloptions[] = '<th scope="row">Image Caption Style (imagecaptionstyle)</th>';
        $html_additionaloptions[] = '<td><select name="simpleflickr_imagecaptionstyle" id="simpleflickr_imagecaptionstyle">';
        $option = '    <option value="none"';
        if($simpleflickr_imagecaptionstyle=='none')	$option .= ' selected="selected"';
        $html_additionaloptions[] = $option .'>None</option>';
        $option = '    <option value="bold"';
        if($simpleflickr_imagecaptionstyle=='bold')	$option .= ' selected="selected"';
        $html_additionaloptions[] = $option .'>Bold</option>';
        $option = '    <option value="italic"';
        if($simpleflickr_imagecaptionstyle=='italic')	$option .= ' selected="selected"';
        $html_additionaloptions[] = $option .'>Italic</option>';
        $option = '    <option value="underline"';
        if($simpleflickr_imagecaptionstyle=='underline')	$option .= ' selected="selected"';
        $html_additionaloptions[] = $option .'>Underline</option>';
        $html_additionaloptions[] = '</select>';
        $html_additionaloptions[] = '<br />';
        $html_additionaloptions[] = 'Specifies the font style for the image caption text if displayed. Can be \'bold\', \'italic\', \'underline\', or \'none\'. Default is \'none\'. ';
        $html_additionaloptions[] = '</td>';
        $html_additionaloptions[] = '</tr>';
        $html_additionaloptions[] = '</table>';

        // Create HTML for alternate simpleviewer options
        $html_alternateoptions = array();
        $html_alternateoptions[] = '<h3>Alternate SimpleViewer Options</h3>';
        $html_alternateoptions[] = '<p>The following is an alternate option for using SimpleViewer within this plugin.  If you provide a path to a standard SimpleViewer XML configuration file then this plugin will display the gallery and all settings specified in that file.  This option overrides all other Simpleviewer options set for this plugin.</p>';
        $html_alternateoptions[] = '<table class="form-table">';
        $html_alternateoptions[] = '<tr valign="top">';
        $html_alternateoptions[] = '<th scope="row">XML Configuration File Path (xmldatapath)</th>';
        $html_alternateoptions[] = '<td><input name="simpleflickr_xmldatapath" id="simpleflickr_xmldatapath" style="width: 95%" type="text" value="'. $simpleflickr_xmldatapath .'" size="45" />';
        $html_alternateoptions[] = '<br />';
        $html_alternateoptions[] = 'Relative or absolute URL of the gallery XML file. Relative paths are relative to the HTML page that contains the swf.  Default is blank. ';
        $html_alternateoptions[] = '</td>';
        $html_alternateoptions[] = '</tr>';
        $html_alternateoptions[] = '</table>';
        
        $html_submitbutton = '<p class="submit"><input type="submit" name="info_update" value="Update Options &raquo;" /></p>';
        
		// Check if we passed authentication
		if ($flickrAuth) {
            
            // Add flickr options
            $html_output .= join(PHP_EOL, $html_flickroptions);
            
            // Add flash options
            $html_output .= join(PHP_EOL, $html_flashoptions);
            
            // Add simpleviewer options
            $html_output .= join(PHP_EOL, $html_simplevieweroptions);
            
            // Add additional options
            $html_output .= join(PHP_EOL, $html_additionaloptions);
            
            // Add alternate  options
            $html_output .= join(PHP_EOL, $html_alternateoptions);
            
            // Add button
            $html_output .= $html_submitbutton . PHP_EOL;
        }
		else {
		
			// Setup authentication url
			$link = 'http://flickr.com/services/auth/?api_key=' . SIMPLEFLICKR_FLICKR_API_KEY . '&amp;frob=' . $frob . '&amp;perms=read';
			$parms = 'api_key' . SIMPLEFLICKR_FLICKR_API_KEY . 'frob' . $frob . 'permsread';
			$link .= '&amp;api_sig=' . md5(SIMPLEFLICKR_FLICKR_API_SECRET . $parms);
			
            // Create HTML for flickr authorization
            $html_flickrauth = array();
            $html_flickrauth[] = '<h3>Flickr Authorization</h3>';
            $html_flickrauth[] = '<p>Please complete the following steps to allow SimpleFlickr access to your Flickr photos.</p>';
			$html_flickrauth[] = '<input type="hidden" name="frob" value="' . $frob . '" />';
            $html_flickrauth[] = '<table class="form-table">';
            $html_flickrauth[] = '<tr valign="top">';
            $html_flickrauth[] = '<th scope="row">Step 1</th>';
            $html_flickrauth[] = '<td><a href="' . $link . '" target="_blank">Authorize SimpleFlickr to access your Flickr account</a>';
            $html_flickrauth[] = '<br />';
            $html_flickrauth[] = 'A new browser window or tab will open asking you to login to your flickr account and authorize this application.  Click \'Ok, I\'ll Allow it\' then leave that window (or tab) open and return to this options page to complete the authorization.';
            $html_flickrauth[] = '</td>';
            $html_flickrauth[] = '</tr>';
            $html_flickrauth[] = '<tr valign="top">';
            $html_flickrauth[] = '<th scope="row">Step 2</th>';
            $html_flickrauth[] = '<td><input type="submit" name="Authenticate" value="Get Authentication Token" />';
            $html_flickrauth[] = '<br />';
            $html_flickrauth[] = 'Once this button has been clicked then your flickr account should be authenticated with this plugin and you may close the previously opened window (or tab).';
            $html_flickrauth[] = '</td>';
            $html_flickrauth[] = '</tr>';
            $html_flickrauth[] = '</table>';
            
            // Add flickr authorization
            $html_output .= join(PHP_EOL, $html_flickrauth);
            
            // Add flash options
            $html_output .= join(PHP_EOL, $html_flashoptions);
            
            // Add alternate  options
            $html_output .= join(PHP_EOL, $html_alternateoptions);
            
            // Add button
            $html_output .= $html_submitbutton . PHP_EOL;
		}
		
		// Add footer html
        $html_output .= '</form>'. PHP_EOL;
		$html_output .= '</div>'. PHP_EOL;
        
        // Write the HTML to screen
        echo($html_output);
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

		// If we're not serving up a feed, generate the script tags
		if (is_feed()) {
            $ret = $this->build_feed($atts);
		} else {            
			$ret = $this->build_script($atts);
		}

		return $ret;
	}
	
	function build_script($atts) {
		$result = "";
		
		// Create a random number to make the flash object unique
		$rand = mt_rand();
		
		// Extract out all of the option variables
		if (is_array($atts)) extract($atts);

		// Get the saved values for some of the tags
		$simpleFlickrOptionsDB = get_option(SIMPLEFLICKR_OPTIONS_NAME);
        if(empty($xmldatapath)) $xmldatapath = $simpleFlickrOptionsDB['XML_DATA_PATH'];
        if(empty($width))       $width = $simpleFlickrOptionsDB['WIDTH'];
        if(empty($height))      $height = $simpleFlickrOptionsDB['HEIGHT'];
        if(empty($quality))     $quality = $simpleFlickrOptionsDB['QUALITY'];
        if(empty($bgcolor))     $bgcolor = $simpleFlickrOptionsDB['BGCOLOR'];
        if(empty($wmode))       $wmode = $simpleFlickrOptionsDB['WMODE'];

		// Load some defaults if value not given
		if(empty($width))	    $width = SIMPLEFLICKR_DEFAULT_WIDTH; 
		if(empty($height))	    $height = SIMPLEFLICKR_DEFAULT_HEIGHT;
		if(empty($quality))	    $quality = SIMPLEFLICKR_DEFAULT_QUALITY;
		if(empty($bgcolor))	    $bgcolor = SIMPLEFLICKR_DEFAULT_BGCOLOR;
		if(empty($wmode))	    $wmode = SIMPLEFLICKR_DEFAULT_WMODE;
		
        if(empty($xmldatapath))
        {
    		// Combine the simpleviewer parameters
            
            // Tag only parameters
            $params[] = $set;
    		$params[] = $group;
            
            // Flickr Options
            $params[] = $count;
            $params[] = $showrecent;
            $params[] = $imagesize;
            $params[] = $privacyfilter;
            
            // SimpleViewer Options
            $params[] = $maximagewidth;
            $params[] = $maximageheight;
            $params[] = $textcolor;
            $params[] = $framecolor;
            $params[] = $framewidth;
            $params[] = $stagepadding;
            $params[] = $navpadding;
            $params[] = $thumbnailcolumns;
            $params[] = $thumbnailrows;
            $params[] = $navposition;
            $params[] = $valign;
            $params[] = $halign;
            $params[] = $title;
            $params[] = $enablerightclickopen;
            $params[] = $backgroundimagepath;
            
            // Additional Options
            $params[] = $showimagecaption;
            $params[] = $imagecaptionlink;
            $params[] = $imagecaptionstyle;
    		
    		$parameters = join(",", $params);

            // Clear bgcolor if wmode is transparent
    		if($wmode == 'transparent') 
                $bgcolor = null;
            
            // Create the URL for the xmldatapath
            $xmldatapath = $this->get_plugin_uri() .'simpleFlickr.php?parameters='. $parameters;
        }
        
        // Generate the unique id
        $prefix = 'SF_'. $rand . '_';
        
		// Create the script
		$output	= array();
		$output[] = '<div id="'. $prefix .'Viewer" class="flashmovie">';
		$output[] = '<a href="http://www.macromedia.com/go/getflashplayer"><img src="http://www.adobe.com/images/shared/download_buttons/get_flash_player.gif" alt="Get macromedia Flash Player" style="border:none;" /></a>';
		$output[] = '</div>';
		$output[] = '<script type="text/javascript">';
        $output[] = 'var '. $prefix .'flashvars = {};';
        $output[] = $prefix .'flashvars.xmlDataPath = "'. $xmldatapath .'";';
        if(!empty($firstimageindex))
            $output[] = $prefix .'flashvars.firstImageIndex = "'. $firstimageindex .'";';
        if(!empty($langopenimage))
            $output[] = $prefix .'flashvars.langOpenImage = "'. $langopenimage .'";';
        if(!empty($langabout))
            $output[] = $prefix .'flashvars.langAbout = "'. $langabout .'";';
        if(!empty($preloadercolor))
            $output[] = $prefix .'flashvars.preloaderColor = "'. $preloadercolor .'";';
        $output[] = 'var '. $prefix .'params = {};';
        $output[] = $prefix .'params.quality = "'. $quality .'";';
        $output[] = $prefix .'params.wmode = "'. $wmode .'";';
        if(!empty($bgcolor))    
            $output[] = $prefix .'params.bgcolor = "'. $bgcolor .'";';
        $output[] = $prefix .'params.base = "'. $this->get_plugin_uri() .'";';
        $output[] = 'var '. $prefix .'attributes = {};';
        $output[] = 'swfobject.embedSWF("'. $this->get_plugin_uri() .'viewer.swf", "SF_'. $rand .'_Viewer", "'. $width .'", "'. $height .'", "8.0.0", "'. $this->get_plugin_uri() .'swfobject/expressInstall.swf", '. $prefix .'flashvars, '. $prefix .'params, '. $prefix .'attributes);';
		$output[] = '</script>';
		$output[] = '';
		$result = join(PHP_EOL, $output);
	
		return $result;
	}
	
	function build_feed($atts) {
		$result = "";
		
		// Create the message to be displayed.  Had lots of issues displaying the flash content in the RSS feed so decided to just show a message.
		$output = array();
        $output[] = '<p>';
        $output[] = '<strong>-- SimpleFlickr Content --</strong><br />';
        $output[] = '(Please visit the original post page to view the details.)';
        $output[] = '</p>';

		$result = join(PHP_EOL, $output);	
	
		return $result;
	}
	
	function get_xml($parameters) {
		// Get the saved default tag values
        
        // Get values from DB
        $simpleFlickrOptionsDB = get_option(SIMPLEFLICKR_OPTIONS_NAME);
        
        // Flickr Options
        $count = $simpleFlickrOptionsDB['COUNT'];
        $showrecent = $simpleFlickrOptionsDB['SHOW_RECENT'];
        $imagesize = $simpleFlickrOptionsDB['IMAGE_SIZE'];
        $privacyfilter = $simpleFlickrOptionsDB['PRIVACY_FILTER'];
        
        // SimpleViewer Options
        $maximagewidth = $simpleFlickrOptionsDB['MAX_IMAGE_WIDTH'];
        $maximageheight = $simpleFlickrOptionsDB['MAX_IMAGE_HEIGHT'];
        $textcolor = $simpleFlickrOptionsDB['TEXT_COLOR'];
        $framecolor = $simpleFlickrOptionsDB['FRAME_COLOR'];
        $framewidth = $simpleFlickrOptionsDB['FRAME_WIDTH'];
        $stagepadding = $simpleFlickrOptionsDB['STAGE_PADDING'];
        $navpadding = $simpleFlickrOptionsDB['NAV_PADDING'];
        $thumbnailcolumns = $simpleFlickrOptionsDB['THUMBNAIL_COLUMNS'];
        $thumbnailrows = $simpleFlickrOptionsDB['THUMBNAIL_ROWS'];
        $navposition = $simpleFlickrOptionsDB['NAV_POSITION'];
        $valign = $simpleFlickrOptionsDB['VALIGN'];
        $halign = $simpleFlickrOptionsDB['HALIGN'];
        $title = $simpleFlickrOptionsDB['TITLE'];
        $enablerightclickopen = $simpleFlickrOptionsDB['ENABLE_RIGHT_CLICK_OPEN'];
        $backgroundimagepath = $simpleFlickrOptionsDB['BACKGROUND_IMAGE_PATH'];
        
        // Additional Options
        $showimagecaption = $simpleFlickrOptionsDB['SHOW_IMAGE_CAPTION'];
        $imagecaptionlink = $simpleFlickrOptionsDB['IMAGE_CAPTION_LINK'];
        $imagecaptionstyle = $simpleFlickrOptionsDB['IMAGE_CAPTION_STYLE'];
            
		
		// Get the values from the tag if given
		$array = split(",", $_GET{parameters});
        
        // Tag only parameters
        if(isset($array[0]) && strlen($array[0]))	$setid = $array[0];
        if(isset($array[1]) && strlen($array[1]))	$group = $array[1];
        
        // Flickr Options
        if(isset($array[2]) && strlen($array[2]))	$count = $array[2];
        if(isset($array[3]) && strlen($array[3]))	$showrecent = $array[3];
        if(isset($array[4]) && strlen($array[4]))	$imagesize = $array[4];
        if(isset($array[5]) && strlen($array[5]))	$privacyfilter = $array[5];
        
        // SimpleViewer Options
        if(isset($array[6]) && strlen($array[6]))	$maximagewidth = $array[6];
        if(isset($array[7]) && strlen($array[7]))	$maximageheight = $array[7];
        if(isset($array[8]) && strlen($array[8]))	$textcolor = $array[8];
        if(isset($array[9]) && strlen($array[9]))	$framecolor = $array[9];
        if(isset($array[10]) && strlen($array[10]))	$framewidth = $array[10];
        if(isset($array[11]) && strlen($array[11]))	$stagepadding = $array[11];
        if(isset($array[12]) && strlen($array[12]))	$navpadding = $array[12];
        if(isset($array[13]) && strlen($array[13]))	$thumbnailcolumns = $array[13];
        if(isset($array[14]) && strlen($array[14]))	$thumbnailrows = $array[14];
        if(isset($array[15]) && strlen($array[15]))	$navposition = $array[15];
        if(isset($array[16]) && strlen($array[16]))	$valign = $array[16];
        if(isset($array[17]) && strlen($array[17]))	$halign = $array[17];
        if(isset($array[18]) && strlen($array[18]))	$title = $array[18];
        if(isset($array[19]) && strlen($array[19]))	$enablerightclickopen = $array[19];
        if(isset($array[20]) && strlen($array[20]))	$backgroundimagepath = $array[20];
        
        // Additional Options
        if(isset($array[21]) && strlen($array[21]))	$showimagecaption = $array[21];
        if(isset($array[22]) && strlen($array[22]))	$imagecaptionlink = $array[22];
        if(isset($array[23]) && strlen($array[23]))	$imagecaptionstyle = $array[23];

        // Set count if not set
        if(empty($count))   $count = 0;
        
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
				$photos = $flickr->photos_search(array("user_id" => $nsid, "per_page" => $count, "privacy_filter" => $privacyfilter));
			}
			else {
				$photos = $flickr->photos_search(array("user_id" => $nsid, "privacy_filter" => $privacyfilter));
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
					$photos = $flickr->photosets_getPhotos($setid, $privacyfilter, NULL, $count, NULL);
				}
				else {
					$photos = $flickr->photosets_getPhotos($setid, $privacyfilter);
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
		
		// Check for special original setting
		$thumbtype = (strtolower($imagesize) == "original") ? "SquareOriginal" : "Square";
		
		// Generate xml output
		$xmlout = '<?xml version="1.0" encoding="UTF-8"?>';
		$xmlout .= '<!-- Last updated: ' . date("r") . ' -->';
		$xmlout .= '<simpleviewergallery ';
        if(!empty($maximagewidth))
            $xmlout .= ' maxImageWidth="'. $maximagewidth .'"';
        if(!empty($maximageheight))
            $xmlout .= ' maxImageHeight="'. $maximageheight .'"';
        if(!empty($textcolor))
            $xmlout .= ' textColor="'. $textcolor .'"';
        if(!empty($framecolor))
            $xmlout .= ' frameColor="'. $framecolor .'"';
        if(!empty($framewidth))
            $xmlout .= ' frameWidth="'. $framewidth .'"';
        if(!empty($stagepadding))
            $xmlout .= ' stagePadding="'. $stagepadding .'"';
        if(!empty($navpadding))
            $xmlout .= ' navPadding="'. $navpadding .'"';
        if(!empty($thumbnailcolumns))
            $xmlout .= ' thumbnailColumns="'. $thumbnailcolumns .'"';
        if(!empty($thumbnailrows))
            $xmlout .= ' thumbnailRows="'. $thumbnailrows .'"';
        if(!empty($navposition))
            $xmlout .= ' navPosition="'. $navposition .'"';
        if(!empty($valign))
            $xmlout .= ' vAlign="'. $valign .'"';
        if(!empty($halign))
            $xmlout .= ' hAlign="'. $halign .'"';
        if(!empty($title))
            $xmlout .= ' title="'. $title .'"';
        if(!empty($enablerightclickopen))
            $xmlout .= ' enableRightClickOpen="'. $enablerightclickopen .'"';
        if(!empty($backgroundimagepath))
            $xmlout .= ' backgroundImagePath="'. $backgroundimagepath .'"';
		$xmlout .= ' imagePath="simpleFlickr.php?mode=img&amp;size=' . $imagesize . '&amp;image="';
		$xmlout .= ' thumbPath="simpleFlickr.php?mode=img&amp;size=' . $thumbtype . '&amp;image=">';
		
		foreach ((array)$photos['photo'] as $photo)
		{
			//Skip sizing if using Medium (default)
			if(strtolower($imagesize) == "medium" )
			{
				$photoName = "{$photo[server]}/{$photo[id]}_{$photo[secret]}";
			}
			else
			{
				// Get original photo url
				$sizes = $flickr->photos_getSizes($photo[id]);
		
				if($sizes)
				{
					// Loop through the sizes
					foreach ($sizes as $size)
					{
						// get the source image url of the desired size
						if (strtolower($size['label']) == strtolower($imagesize))
						{
							$photoName = substr($size['source'], 7);
							$photoName = substr($photoName, strpos($photoName, "/") + 1);	
							if( substr_count($photoName, "_") > 1)
								$endPos = strrpos($photoName, "_");
							else
								$endPos = strlen($photoName) - 4;
								
							$photoName = substr($photoName, 0, $endPos);
							break;
						}
					}
					
					// If size not found then use standard
					if(empty($photoName))
						$photoName = "{$photo[server]}/{$photo[id]}_{$photo[secret]}";
					
					// Check for original and add info
					if(strtolower($imagesize) == "original")
						$photoName = "{$photo[server]}/{$photo[id]}_{$photo[secret]}__" . $photoName;
				}
			}
			
            $xmlout .= "<image><filename>{$photoName}</filename><caption><![CDATA[";
            if($showimagecaption == 'true')
            {
                // Check if caption should be a link
                if($imagecaptionlink == 'true')
                {
    				if(!empty($group)) 
    				{
    					$html_caption = "<a href=\"http://www.flickr.com/photos/{$photo[ownername]}/{$photo[id]}\" target=\"_blank\">{$photo[title]}</a>";
    				}
    				else
    				{
    					$html_caption = "<a href=\"{$photos_url}{$photo[id]}\" target=\"_blank\">{$photo[title]}</a>";
    				}
                }
                else
                {
                    $html_caption = "{$photo[title]}";
                }
                
                // Set the caption style
                switch(strtolower($imagecaptionstyle)) {
        			case "bold":
        			$tag = "b";
        			break;
        			case "italic":
        			$tag = "i";
        			break;
        			case "underline":
        			$tag = "u";
        			break;
        			case "none":
        			$tag = "";
        			break;
        			default:
        			$tag = "";
        			break;
        	   }
               
                if(empty($tag))
                    $xmlout .= $html_caption;
                else
                    $xmlout .= "<". $tag .">". $html_caption ."</". $tag .">";  
            }
            $xmlout .= "]]></caption></image>";
		}
		$xmlout .= "</simpleviewergallery>";

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
