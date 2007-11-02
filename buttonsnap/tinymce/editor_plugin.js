/* Import plugin specific language pack */
tinyMCE.importPluginLanguagePack('simpleflickr');

var TinyMCE_SimpleFlickrQuicktags = {
	getInfo : function() {
		return {
			longname : "SimpleFlickr",
			author : "Josh Gerdes",
			authorurl : "http://www.joshgerdes.com/",
			infourl : "http://www.joshgerdes.com/blog/projects/simpleflickr-plugin/",
			version : tinyMCE.majorVersion + "." + tinyMCE.minorVersion
		};
	},

	getControlHTML : function(cn) {
		switch (cn) {
			case 'simpleflickr':
				buttons = tinyMCE.getButtonHTML('sfq_simpleflickr', 'lang_simpleflickr', '{$pluginurl}/../images/flickr.png', 'sfq_simpleflickr');
				return buttons;
		}

		return '';
	},

	execCommand : function(editor_id, element, command, user_interface, value) {
		switch (command) {
			case 'sfq_simpleflickr':
				SimpleFlickrInsertSet('http://flickr.com/photos/joshgerdes/sets/72157594408754918/', 'simpleflickr');
				return true;
		}

		return false;
	}
};

tinyMCE.addPlugin('simpleflickr', TinyMCE_SimpleFlickrQuicktags);