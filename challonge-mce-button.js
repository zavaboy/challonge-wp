(function() {
	var parseUrlString = function( url ) {
		var re = /^(?:(?:https?\:\/\/)?(?:www\.|([\w\-]+)\.)?challonge\.com\/?)?(\w*|^\w+$)?/i;
		if ( re.test( url ) ) {
			var match = re.exec( url );
			var ret = {};
			if (match[2]) ret.url       = match[2];
			if (match[1]) ret.subdomain = match[1];
			return ret;
		}
		return {};
	};
	tinymce.PluginManager.add('challonge_mce_button', function( editor, url ) {
		editor.addButton('challonge_mce_button', {
			icon: 'challonge-mce-icon',
			onclick: function() {
				editor.windowManager.open( {
					title: 'Insert Challonge Bracket or Listing',
					body: [
						{
							id: 'challonge_mce_url',
							type: 'textbox',
							name: 'url',
							label: 'URL',
							size: 40
						}
					],
					onsubmit: function( e ) {
						var part = parseUrlString( e.data.url );
						var url       = part.url ?       ' url="'       + part.url       + '"' : '';
						var subdomain = part.subdomain ? ' subdomain="' + part.subdomain + '"' : '';
						editor.insertContent( '[challonge' + url + subdomain + ']' );
					}
				});
			}
		});
	});
})();
