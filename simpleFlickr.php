<?php
/*
Plugin Name: simpleFlickr
Plugin URI: http://wordpress.org/#
Description: This plugin allows you to embed a Simpleviewer Flash Object integrated with a Flickr account.
Author: Josh Gerdes
Version: 1.0
Author URI: http://www.joshgerdes.com

Copyright (c) 2007
Released under the GPL license
http://www.gnu.org/licenses/gpl.txt
*/ 

// Global Variables
define('SIMPLEFLICKR_OPTIONS_NAME', "simpleflickr_options");
define('SIMPLEFLICKR_VERSION', "1.0");
define('SIMPLEFLICKR_DIR', "simpleFlickr");
$simpleflickr_request_type = "";

// Main function
function simpleflickr($content) {
	$pattern = '/(<p>[\s\n\r]*)??(([\[<]SIMPLEFLICKR.*\/[\]>])|([\[<]SIMPLEFLICKR.*[\]>][\[<]\/SIMPLEFLICKR[\]>]))([\s\n\r]*<\/p>)??/Umi'; 
	return preg_replace_callback($pattern,'simpleflickr_parse_tags',$content);
}

function simpleflickr_parse_tags ($match) {
	$ret = "";
	
	// Remove some of the unwanted tags
	$strip		= array('[SIMPLEFLICKR',
						'][/SIMPLEFLICKR]',
						'[simpleflickr',
						'][/simpleflickr]',
						'/]',
						'<SIMPLEFLICKR',
						'></SIMPLEFLICKR>',
						'<simpleflickr',
						'></simpleflickr>',
						'/>',
						'\n',
						'<br>',
						'<br />',
						'<p>',
						'</p>'
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
	if ($GLOBALS['simpleflickr_request_type'] != "feed") {
		$ret = simpleflickr_build_script($atts);
	} else {
		$ret = simpleflickr_build_feed($atts);
	}

	return $ret;
}

// Build the javascript for the page
function simpleflickr_build_script($atts) {
	$result = "";
	
	// Create a random number to make the flash object unique
	$rand = mt_rand();
	
	// Extract out all of the option variables
	if (is_array($atts)) extract($atts);

	// Load some defualts if value not given
	if(empty($width))	$width = "100%"; 
	if(empty($height))	$height = "800";
	if(empty($quality))	$quality = "best";
	if(empty($bgcolor))	$bgcolor = "#FFFFFF";
	
	if(empty($navposition)) 			$navposition = "bottom";
	if(empty($maximagewidth))			$maximagewidth = "500";
	if(empty($maximageheight))			$maximageheight = "300";
	if(empty($textcolor))				$textcolor = "0xFFFFFF";
	if(empty($framecolor))				$framecolor = "0xFFFFFF";
	if(empty($framewidth))				$framewidth = "15";
	if(empty($stagepadding))			$stagepadding = "40";
	if(empty($thumbnailcolumns))		$thumbnailcolumns = "3";
	if(empty($thumbnailrows))			$thumbnailrows = "3";
	if(empty($enablerightclickopen))	$enablerightclickopen = "true";
	if(empty($title)) 					$title = "";
	
	// Combine the simpleviewer parameters
	$params[] = $set;
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
	
	$parameters = join(",", $params);
	
	// Create the script
	$output	= array();
	$output[] = '<div id="fo_targ_viewer' . $rand . '" class="flashmovie"></div>';
	$output[] = '';
	$output[] = '<script type="text/javascript">';
	$output[] = '';
	$output[] = '// <![CDATA[';
	$output[] = '';
	$output[] = 'var so_' . $rand . ' = new SWFObject("'. get_plugin_uri() .'viewer.swf","fm_viewer","' . $width . '","' . $height . '","6","' . $bgcolor . '","","' . $quality . '","","");';
	$output[] = 'so_' . $rand . '.addParam("base", "' . get_plugin_uri() . '");';
	$output[] = 'so_' . $rand . '.addVariable("xmlDataPath","' . get_plugin_uri() . 'flickrViewer.php?parameters=' . $parameters . '");';
	$output[] = 'so_' . $rand . '.write("fo_targ_viewer' . $rand . '");';
	$output[] = '';
	$output[] = '// ]]>';
	$output[] = '';
	$output[] = '</script>';
	$output[] = '';
	$result = join("\n", $output);

	return $result;
}

// Build an object for the RSS feed
function simpleflickr_build_feed($atts) {
	$result = "";

		// Extract out all of the option variables
	if (is_array($atts)) extract($atts);

	// Load some defualts if value not given
	if(empty($width))	$width = "100%"; 
	if(empty($height))	$height = "800";
	
	$output[] = '';    
	$output[] = '<object	type="application/x-shockwave-flash"';
	$output[] = '			data="' . get_plugin_uri() . 'viewer.swf"'; 
	$output[] = '			base="' . get_plugin_uri() . '"';
	$output[] = '			width="' . $width . '"';
	$output[] = '			height="' . $height . '">';
	$output[] = '	<param name="movie" value="' . get_plugin_uri() . 'viewer.swf" />';
	$output[] = '	<param name=base" value="' . get_plugin_uri() . '" />';
	$output[] = '</object>';     

	$result .= join("\n", $output);	
	return $result;
}

function get_plugin_uri() {
	$uri = get_settings('siteurl') . '/wp-content/plugins/'. SIMPLEFLICKR_DIR .'/';
	return $uri;
}

//	Add the call to flashobject.js
function simpleflickr_add_flashobject_js() {
	echo '
	<!-- Added by SimpleFlickr - Version '. SIMPLEFLICKR_VERSION . ' -->
	<script src="' . get_plugin_uri() . 'swfobject.js" type="text/javascript"></script>
	';
}

// Apply the filter 
if (preg_match("/(\/\?feed=|\/feed)/i",$_SERVER['REQUEST_URI'])) {
	// RSS Feeds
	$simpleflickr_request_type	= "feed";
} else {
	// Everything else
	$simpleflickr_request_type	= "nonfeed";
	add_action('wp_head', 'simpleflickr_add_flashobject_js');
}

// Apply all over except the admin section
if (strpos($_SERVER['REQUEST_URI'], 'wp-admin') === false ) {
	add_action('template_redirect','simpleflickrDoObjectStart');
}

// Trigger Function
function simpleflickrDoObjectStart()
{
	ob_start('simpleflickr');
}

// Admin Panel Start
function simpleflickr_menu() {
    if (function_exists('add_options_page')) {
		add_options_page('simpleFlickr Options', 'simpleFlickr', 8, basename(__FILE__), 'simpleflickr_options_subpanel');
    }
 }
 
 function simpleflickr_options_subpanel() {
	global $user_level;
	get_currentuserinfo();
	// Don't show the GUI if not a level 8 admin
	if ($user_level < 8) {
?><div class="wrap">
	<h2>simpleFlickr (v<?php print(SIMPLEFLICKR_VERSION) ?>) Options</h2>
	<br /><?php _e("<div style=\"color:#770000;\">You are not logged in as a <strong>LEVEL 8</strong> or above USER so you cannot configure <strong>simpleFlickr</strong>.</div>"); ?><br />
</div><?php
		return;
	}
	
	// Get the field values
	if(!empty($_POST['simpleFlickr_apikey'])) {
		$simpleFlickr_apikey = trim($_POST['simpleFlickr_apikey']);
		$simpleFlickr_name = trim($_POST['simpleFlickr_name']);

		// Get array from DB
		$simpleFlickrOptionsDB = get_option(SIMPLEFLICKR_OPTIONS_NAME);

		// Check if empty and fill
		if(empty($simpleFlickr_apikey)) 
			$simpleFlickr_apikey = $simpleFlickrOptionsDB['API_KEY'];
		if(empty($simpleFlickr_name)) 
			$simpleFlickr_name = $simpleFlickrOptionsDB['SCREEN_NAME'];

		// Add values to a new array to save
		$simpleFlickrOptionsNewArr = array();
		$simpleFlickrOptionsNewArr['API_KEY'] = $simpleFlickr_apikey;
		$simpleFlickrOptionsNewArr['SCREEN_NAME'] = $simpleFlickr_name;

		// Save new array to DB
		update_option(SIMPLEFLICKR_OPTIONS_NAME, $simpleFlickrOptionsNewArr);
	}

	// Get values from DB
	$simpleFlickrOptionsDB = get_option(SIMPLEFLICKR_OPTIONS_NAME);
	$simpleFlickr_apikey = $simpleFlickrOptionsDB['API_KEY'];
	$simpleFlickr_name = $simpleFlickrOptionsDB['SCREEN_NAME'];

	if (isset($_POST['info_update'])) {
    ?><div class="updated"><p><strong><?php 
	_e('Successfully Saved Settings')
    ?></strong></p></div><?php
	} ?>
<div class=wrap>
  <form method="post">
    <h2>simpleFlickr (v<?php print(SIMPLEFLICKR_VERSION) ?>) Options</h2>
     <fieldset name="set1">
	<legend><?php _e('Base Settings'); ?></legend>
	<ul style="list-style:none;">
		<li>
			<label for="simpleFlickr_apikey"><strong>Flickr API Key:</strong></label>&nbsp;&nbsp;
			<input type="text" name="simpleFlickr_apikey" id="simpleFlickr_apikey" size="34" value="<?php _e($simpleFlickr_apikey); ?>" /><br />
					<strong>{</strong> <em>You must provide a Flickr API Key for this plugin to work correctly.  You can obtain an API key at <a href="http://www.flickr.com/services/api/keys/" target=_blank">http://www.flickr.com/services/api/keys/</a>.</em> <strong>}</strong><br /><br />
		</li>
		<li>
			<label for="simpleFlickr_name"><strong>Flickr Screen Name:</strong></label>&nbsp;&nbsp;
			<input type="text" name="simpleFlickr_name" id="simpleFlickr_name" size="16" value="<?php _e($simpleFlickr_name); ?>" /><br />
					<strong>{</strong> <em>You must provide your Flickr Screen Name so the plugin can create your main photo URL.  Your screen name can be found in multiple locations including the URL of your main photo page (Example: http://www.flickr.com/photos/screen_name).</em> <strong>}</strong><br /><br />
		</li>
	</ul>
     </fieldset>

<div class="submit">
  <input type="submit" name="info_update" value="<?php
    _e('Update options', 'Localization name')
	?> »" /></div>
  </form>
 </div><?php
}

// add the sub-panel under the OPTIONS panel
add_action('admin_menu', 'simpleflickr_menu');
?>