jQuery(document).ready( function ( $ ) {
	$('#challonge-apikey').keyup( function () {
		var apiKey = $( this ).val();
		$('#challonge-apikey-check, #challonge-apikey-ok, #challonge-apikey-fail, #challonge-apikey-error').stop( true, true ).hide();
		$('#challonge-apikey-errmsg').html('');
		if ( apiKey.match( /^[a-z0-9]{40}$/i ) ) {
			$('#challonge-apikey-check').fadeIn('fast');
			$.getJSON(
				ajaxurl,
				{
					action:  'challonge_verify_apikey',
					api_key: apiKey
				},
				function ( data, status ) {
					$('#challonge-apikey-check').fadeOut( 'fast', function () {
						if ( data.errors && data.errors[0][0] == 'Invalid API key' ) {
							$('#challonge-apikey-fail').fadeIn('fast');
						} else if ( data.errors && data.errors[0] == 'Result set empty' ) {
							$('#challonge-apikey-ok').fadeIn('fast');
						} else {
							$('#challonge-apikey-errmsg').html( data.errors[0] );
							$('#challonge-apikey-error').fadeIn('fast');
						}
					} );
				}
			);
		} else if ( apiKey ) {
			$('#challonge-apikey-fail').fadeIn('fast');
		}
	} ).keyup();
} );