// Function for video websites
function SimpleFlickrInsertSet(example, tag) {

	var set = prompt("Please enter a set number. Can be found at the end of a flickr set URL.\n\nExample :" + example);
	
	if( !set ) { return; }
		
	buttonsnap_settext('<' + tag + ' set="' + set + '" />');
}