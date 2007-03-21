<?php
/*
Plugin Name: simpleFlickr
Plugin URI: http://wordpress.org/#
Description: This plugin allows you to embed a Simpleviewer Flash Object integrated with a Flickr account.
Author: Josh Gerdes
Version: 1.1
Author URI: http://www.joshgerdes.com

Copyright (c) 2007
Released under the GPL license
http://www.gnu.org/licenses/gpl.txt
*/ 

// Global Variables
define('SIMPLEFLICKR_OPTIONS_NAME', "simpleflickr_options");
define('SIMPLEFLICKR_VERSION', "1.1");
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

	// Get the default values for some of the tags
	$simpleFlickrOptionsDB = get_option(SIMPLEFLICKR_OPTIONS_NAME);
	$default_width = $simpleFlickrOptionsDB['WIDTH'];
	$default_height = $simpleFlickrOptionsDB['HEIGHT'];
	$default_quality = $simpleFlickrOptionsDB['QUALITY'];
	$default_bgcolor = $simpleFlickrOptionsDB['BGCOLOR'];

	// Fill with defaults if no DB value was given
	if(empty($default_width)) 
		$default_width = '100%';
	if(empty($default_width)) 
		$default_height = '800';
	if(empty($default_width)) 
		$default_quality = 'best';
	if(empty($default_width)) 
		$default_bgcolor = '#FFFFFF';
		
	// Load some defualts if value not given
	if(empty($width))	$width = $default_width; 
	if(empty($height))	$height = $default_height;
	if(empty($quality))	$quality = $default_quality;
	if(empty($bgcolor))	$bgcolor = $default_bgcolor;

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

	// Get the default values for some of the tags
	$simpleFlickrOptionsDB = get_option(SIMPLEFLICKR_OPTIONS_NAME);
	$default_width = $simpleFlickrOptionsDB['WIDTH'];
	$default_height = $simpleFlickrOptionsDB['HEIGHT'];

	// Fill with defaults if no DB value was given
	if(empty($default_width)) 
		$default_width = '100%';
	if(empty($default_width)) 
		$default_height = '800';

	// Load some defualts if value not given
	if(empty($width))	$width = $default_width; 
	if(empty($height))	$height = $default_height;
	
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
		$simpleFlickr_title = trim($_POST['simpleFlickr_title']);
		$simpleFlickr_width = trim($_POST['simpleFlickr_width']);
		$simpleFlickr_height = trim($_POST['simpleFlickr_height']);
		$simpleFlickr_quality = trim($_POST['simpleFlickr_quality']);
		$simpleFlickr_bgcolor = trim($_POST['simpleFlickr_bgcolor']);
		
		// Get array from DB
		$simpleFlickrOptionsDB = get_option(SIMPLEFLICKR_OPTIONS_NAME);

		// Check if empty and fill
		if(empty($simpleFlickr_apikey)) 
			$simpleFlickr_apikey = $simpleFlickrOptionsDB['API_KEY'];
		if(empty($simpleFlickr_name)) 
			$simpleFlickr_name = $simpleFlickrOptionsDB['SCREEN_NAME'];
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
		if(empty($simpleFlickr_thumbnailcolumns)) 
			$simpleFlickr_thumbnailcolumns = $simpleFlickrOptionsDB['THUMBNAIL_COLUMNS'];
		if(empty($simpleFlickr_thumbnailrows)) 
			$simpleFlickr_thumbnailrows = $simpleFlickrOptionsDB['THUMBNAIL_ROWS'];
		if(empty($simpleFlickr_enablerightclickopen)) 
			$simpleFlickr_enablerightclickopen = $simpleFlickrOptionsDB['ENABLE_RIGHT_CLICK_OPEN'];
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
		$simpleFlickrOptionsNewArr['API_KEY'] = $simpleFlickr_apikey;
		$simpleFlickrOptionsNewArr['SCREEN_NAME'] = $simpleFlickr_name;
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
		$simpleFlickrOptionsNewArr['TITLE'] = $simpleFlickr_title;
		$simpleFlickrOptionsNewArr['WIDTH'] = $simpleFlickr_width;
		$simpleFlickrOptionsNewArr['HEIGHT'] = $simpleFlickr_height;
		$simpleFlickrOptionsNewArr['QUALITY'] = $simpleFlickr_quality;
		$simpleFlickrOptionsNewArr['BGCOLOR'] = $simpleFlickr_bgcolor;
	
		// Save new array to DB
		update_option(SIMPLEFLICKR_OPTIONS_NAME, $simpleFlickrOptionsNewArr);
	}

	// Get values from DB
	$simpleFlickrOptionsDB = get_option(SIMPLEFLICKR_OPTIONS_NAME);
	$simpleFlickr_apikey = $simpleFlickrOptionsDB['API_KEY'];
	$simpleFlickr_name = $simpleFlickrOptionsDB['SCREEN_NAME'];
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
	$simpleFlickr_title = $simpleFlickrOptionsDB['TITLE'];
	$simpleFlickr_width = $simpleFlickrOptionsDB['WIDTH'];
	$simpleFlickr_height = $simpleFlickrOptionsDB['HEIGHT'];
	$simpleFlickr_quality = $simpleFlickrOptionsDB['QUALITY'];
	$simpleFlickr_bgcolor = $simpleFlickrOptionsDB['BGCOLOR'];
	
	// Fill with defaults if no DB value was given
	if(empty($simpleFlickr_navposition)) 
		$simpleFlickr_navposition = 'bottom';
	if(empty($simpleFlickr_maximagewidth)) 
		$simpleFlickr_maximagewidth = '500';
	if(empty($simpleFlickr_maximageheight)) 
		$simpleFlickr_maximageheight = '300';
	if(empty($simpleFlickr_textcolor)) 
		$simpleFlickr_textcolor = '0x000000';
	if(empty($simpleFlickr_framecolor)) 
		$simpleFlickr_framecolor = '0xBBBBBB';
	if(empty($simpleFlickr_framewidth)) 
		$simpleFlickr_framewidth = '15';
	if(empty($simpleFlickr_stagepadding)) 
		$simpleFlickr_stagepadding = '40';
	if(empty($simpleFlickr_thumbnailcolumns)) 
		$simpleFlickr_thumbnailcolumns = '3';
	if(empty($simpleFlickr_thumbnailrows)) 
		$simpleFlickr_thumbnailrows = '3';
	if(empty($simpleFlickr_enablerightclickopen)) 
		$simpleFlickr_enablerightclickopen = 'true';
	if(empty($simpleFlickr_width)) 
		$simpleFlickr_width = '100%';
	if(empty($simpleFlickr_height)) 
		$simpleFlickr_height = '800';
	if(empty($simpleFlickr_quality)) 
		$simpleFlickr_quality = 'best';
	if(empty($simpleFlickr_bgcolor)) 
		$simpleFlickr_bgcolor = '#FFFFFF';
			
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
	<legend><?php _e('Default Tag Settings'); ?></legend>
	<ul style="list-style:none;">
		<li>
			<label for="simpleFlickr_title"><strong>Title:</strong></label>&nbsp;&nbsp;
			<input type="text" name="simpleFlickr_title" id="simpleFlickr_title" size="30" value="<?php _e($simpleFlickr_title); ?>" /><br />
					<strong>{</strong> <em>This is the text to display as a gallery title.  Default is blank.</em> <strong>}</strong><br /><br />
		</li>
		<li>
			<label for="simpleFlickr_width"><strong>Width:</strong></label>&nbsp;&nbsp;
			<input type="text" name="simpleFlickr_width" id="simpleFlickr_width" size="4" value="<?php _e($simpleFlickr_width); ?>" /><br />
					<strong>{</strong> <em>You must provide the width of the simpleviewer flash object.  Percentages and Pixel values can be givne.  Default is 100%.</em> <strong>}</strong><br /><br />
		</li>
		<li>
			<label for="simpleFlickr_height"><strong>Height:</strong></label>&nbsp;&nbsp;
			<input type="text" name="simpleFlickr_height" id="simpleFlickr_height" size="4" value="<?php _e($simpleFlickr_height); ?>" /><br />
					<strong>{</strong> <em>You must provide the height of the simpleviewer flash object.  Percentages and Pixel values can be givne.  Default is 100%.</em> <strong>}</strong><br /><br />
		</li>
		<li>
			<label for="simpleFlickr_quality"><strong>Quality:</strong></label>&nbsp;&nbsp;
			<select name="simpleFlickr_quality" id="simpleFlickr_quality">
				<option value="best"<?php if(($simpleFlickr_quality=="best")) { _e(" selected"); } ?>>Best</option>
				<option value="high"<?php if(($simpleFlickr_quality=="high")) { _e(" selected"); } ?>>High</option>
				<option value="medium"<?php if(($simpleFlickr_quality=="medium")) { _e(" selected"); } ?>>Medium</option>
				<option value="autohigh"<?php if(($simpleFlickr_quality=="autohigh")) { _e(" selected"); } ?>>Autohigh</option>
				<option value="autolow"<?php if(($simpleFlickr_quality=="autolow")) { _e(" selected"); } ?>>Autolow</option>
				<option value="low"<?php if(($simpleFlickr_quality=="low")) { _e(" selected"); } ?>>Low</option>
			</select><br />
					<strong>{</strong> <em>You must provide the quality of the simpleviewer flash object.  Can be 'low', 'high', 'autolow', 'autohigh', 'best'.  Default is 'best'.</em> <strong>}</strong><br /><br />
		</li>
		<li>
			<label for="simpleFlickr_bgcolor"><strong>Background Color:</strong></label>&nbsp;&nbsp;
			<input type="text" name="simpleFlickr_bgcolor" id="simpleFlickr_bgcolor" size="8" maxlength="7" value="<?php _e($simpleFlickr_bgcolor); ?>" /><br />
					<strong>{</strong> <em>You must provide the background color of the simpleviewer flash object (hexidecimal color value e.g #FF00FF).  Default is #FFFFFF.</em> <strong>}</strong><br /><br />
		</li>
		<li>
			<label for="simpleFlickr_navposition"><strong>Nav Position:</strong></label>&nbsp;&nbsp;
			<select name="simpleFlickr_navposition" id="simpleFlickr_navposition">
				<option value="bottom"<?php if(($simpleFlickr_navposition=="bottom")) { _e(" selected"); } ?>>Bottom</option>
				<option value="top"<?php if(($simpleFlickr_navposition=="top")) { _e(" selected"); } ?>>Top</option>
				<option value="left"<?php if(($simpleFlickr_navposition=="left")) { _e(" selected"); } ?>>Left</option>
				<option value="right"<?php if(($simpleFlickr_navposition=="right")) { _e(" selected"); } ?>>Right</option>
			</select><br />
					<strong>{</strong> <em>You must provide the position of the simpleviewer navigation menu relative to the image.  Can be 'top', 'bottom', 'left' or 'right'.  Default is 'bottom'.</em> <strong>}</strong><br /><br />
		</li>
		<li>
			<label for="simpleFlickr_thumbnailcolumns"><strong>Thumbnail Columns:</strong></label>&nbsp;&nbsp;
			<input type="text" name="simpleFlickr_thumbnailcolumns" id="simpleFlickr_thumbnailcolumns" size="4" value="<?php _e($simpleFlickr_thumbnailcolumns); ?>" /><br />
					<strong>{</strong> <em>You must provide the number of thumbnail columns. (To disable thumbnails completely set this value to 0.)  Default is '3'.</em> <strong>}</strong><br /><br />
		</li>
		<li>
			<label for="simpleFlickr_thumbnailrows"><strong>Thumbnail Rows:</strong></label>&nbsp;&nbsp;
			<input type="text" name="simpleFlickr_thumbnailrows" id="simpleFlickr_thumbnailrows" size="4" value="<?php _e($simpleFlickr_thumbnailrows); ?>" /><br />
					<strong>{</strong> <em>You must provide the number of thumbnail rows. (To disable thumbnails completely set this value to 0.)  Default is '3'.</em> <strong>}</strong><br /><br />
		</li>
		<li>
			<label for="simpleFlickr_maximagewidth"><strong>Max Image Width:</strong></label>&nbsp;&nbsp;
			<input type="text" name="simpleFlickr_maximagewidth" id="simpleFlickr_maximagewidth" size="4" value="<?php _e($simpleFlickr_maximagewidth); ?>" /><br />
					<strong>{</strong> <em>You must provide the width of your largest image in pixels. Used to determine the best layout for your gallery.  Default is '500'.</em> <strong>}</strong><br /><br />
		</li>
		<li>
			<label for="simpleFlickr_maximageheight"><strong>Max Image Height:</strong></label>&nbsp;&nbsp;
			<input type="text" name="simpleFlickr_maximageheight" id="simpleFlickr_maximageheight" size="4" value="<?php _e($simpleFlickr_maximageheight); ?>" /><br />
					<strong>{</strong> <em>You must provide height of your largest image in pixels. Used to determine the best layout for your gallery.  Default is '300'.</em> <strong>}</strong><br /><br />
		</li>
		<li>
			<label for="simpleFlickr_textcolor"><strong>Text Color:</strong></label>&nbsp;&nbsp;
			<input type="text" name="simpleFlickr_textcolor" id="simpleFlickr_textcolor" size="8" maxlength="8" value="<?php _e($simpleFlickr_textcolor); ?>" /><br />
					<strong>{</strong> <em>You must provide the color of title and caption text (hexidecimal color value e.g 0xff00ff).  Default is '0x000000'.</em> <strong>}</strong><br /><br />
		</li>
		<li>
			<label for="simpleFlickr_framecolor"><strong>Frame Color:</strong></label>&nbsp;&nbsp;
			<input type="text" name="simpleFlickr_framecolor" id="simpleFlickr_framecolor" size="8" maxlength="8" value="<?php _e($simpleFlickr_framecolor); ?>" /><br />
					<strong>{</strong> <em>You must provide the color of the image frame, navigation buttons and thumbnail frame (hexidecimal color value e.g 0xff00ff).  Default is '0xBBBBBB'.</em> <strong>}</strong><br /><br />
		</li>
		<li>
			<label for="simpleFlickr_framewidth"><strong>Frame Width:</strong></label>&nbsp;&nbsp;
			<input type="text" name="simpleFlickr_framewidth" id="simpleFlickr_framewidth" size="4" value="<?php _e($simpleFlickr_framewidth); ?>" /><br />
					<strong>{</strong> <em>You must provide the width of image frame in pixels.  Default is '15'.</em> <strong>}</strong><br /><br />
		</li>
		<li>
			<label for="simpleFlickr_stagepadding"><strong>Stage Padding:</strong></label>&nbsp;&nbsp;
			<input type="text" name="simpleFlickr_stagepadding" id="simpleFlickr_stagepadding" size="4" value="<?php _e($simpleFlickr_stagepadding); ?>" /><br />
					<strong>{</strong> <em>You must provide the distance between image and thumbnails and around gallery edge in pixels.  Default is '40'.</em> <strong>}</strong><br /><br />
		</li>
		<li>
			<label for="simpleFlickr_enablerightclickopen"><strong>Enable Right Click Open:</strong></label>&nbsp;&nbsp;
			<select name="simpleFlickr_enablerightclickopen" id="simpleFlickr_enablerightclickopen">
				<option value="true"<?php if(($simpleFlickr_enablerightclickopen==true)) { _e(" selected"); } ?>>True</option>
				<option value="false"<?php if(($simpleFlickr_enablerightclickopen==false)) { _e(" selected"); } ?>>False</option>
			</select><br />
					<strong>{</strong> <em>You must provide whether to display a 'Open In new Window...' dialog when right-clicking on an image. Can be 'true' or 'false'.  Default is 'true'. </em> <strong>}</strong><br /><br />
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