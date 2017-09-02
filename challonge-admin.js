/**
 * @package Challonge
 */
/*
Plugin Name: Challonge
Plugin URI: http://wordpress.org/plugins/challonge
License: MIT
*/
jQuery(document).ready( function( $ ) {

	// Sends AJAX request to validate API Key field as it is changed
	var lastApiKey = null;
	$( '#challonge-apikey' ).on( 'keyup mouseup change', function() {
		var apiKey = $( this ).val();
		if ( apiKey === lastApiKey ) {
			return;
		}
		lastApiKey = apiKey;
		$( '#challonge-apikey-check, #challonge-apikey-ok, #challonge-apikey-fail, #challonge-apikey-error' ).stop( true, true ).hide();
		$( '#challonge-apikey-errmsg' ).html( '' );
		if ( apiKey.match( /^[a-z0-9]{40}$/i ) ) {
			$( '#challonge-apikey-check' ).fadeIn( 'fast' );
			$.getJSON(
				ajaxurl,
				{
					action  : 'challonge_verify_apikey',
					api_key : apiKey
				},
				function( data, status ) {
					$( '#challonge-apikey-check' ).fadeOut( 'fast', function() {
						if ( data.errors && 'Invalid API key' == data.errors[0][0] ) {
							$( '#challonge-apikey-fail' ).fadeIn( 'fast' );
						} else if ( data.errors && 'Result set empty' == data.errors[0] ) {
							$('#challonge-apikey-ok').fadeIn( 'fast' );
						} else {
							$( '#challonge-apikey-errmsg' ).html( data.errors[0] );
							$( '#challonge-apikey-error' ).fadeIn( 'fast' );
						}
					} );
				}
			).fail( function() {
				$( '#challonge-apikey-check' ).fadeOut( 'fast', function() {
					$( '#challonge-apikey-errmsg' ).html( challongeVar.errorMsg );
					$( '#challonge-apikey-error' ).fadeIn( 'fast' );
				} );
			} );
		} else if ( apiKey ) {
			$( '#challonge-apikey-fail' ).fadeIn( 'fast' );
		}
	}).change();

	// Shows available paticipant name tokens
	$( '#challonge-participant_name-showtokens' ).one( 'click', function( e ) {
		$( this ).slideUp( 'fast' );
		$( '#challonge-participant_name-tokens' ).slideDown( 'slow' );
		e.preventDefault();
	});
	$( '#challonge-participant_name-showusermetatokens' ).one( 'click', function( e ) {
		$( this ).parent().fadeOut( 'fast' );
		$( '#challonge-participant_name-usermetatokens' ).slideDown( 'slow' );
		e.preventDefault();
	});

	// Displays examples of each participant name token based on selected user
	$( '#challonge-participant_name-tokens-users' ).change( function() {
		var userdata = $( this ).find( 'option:selected' ).data( 'userdata' );
		$( '#challonge-participant_name-tokens table tr td.token' ).each( function() {
			var key = $( this ).text().split( '%' ).join( '' );
			$( this ).nextAll( 'td.userdata' ).text( userdata[ key ] );
		});
	}).change();

	// Hides the author info and donate box in the upper right corner
	$( '#challonge-donate-hide' ).one( 'click', function( e ) {
		$( '#challonge-donate' ).fadeOut();
		e.preventDefault();
	});

	// Manages the events (both mouse and keyboard) on the shortcode colum header list
	var $headShortList = $( '#challonge-headers_shortcode-list' );
	$headShortList.sortable({
		handle : '.challonge-handle'
	});
	var updateListItem = function () {};
	// $headShortList.disableSelection();
	$headShortList.on( 'focus', '.challonge-headers_shortcode-handle', function() {
		var $this = $( this ),
			$li = $( this ).closest( 'li' );
		$this.one( 'keydown', function( e ) {
			var code = ( e.keyCode ? e.keyCode : e.which );
			if ( 40 == code ) { // down
				e.preventDefault();
				$li.next().after( $li );
				$this.focus();
			} else if ( 38 == code ) { // up
				e.preventDefault();
				$li.prev().before( $li );
				$this.focus();
			}
		});
	}).on( 'blur', '.challonge-headers_shortcode-handle', function() {
		$( this ).unbind( 'keydown' );
	}).on( 'click', '.challonge-headers_shortcode-togglevis', function() {
		var $li = $( this ).closest( 'li' ),
			$dat = $li.find( 'input[type=hidden]' ),
			data = JSON.parse( $dat.val() );
		if ( data.hasOwnProperty( 'show' ) ) {
			data.show = $li.hasClass( 'challonge-hide' );
			$li.addClass( data.show ? 'challonge-show' : 'challonge-hide' ).removeClass( data.show ? 'challonge-hide' : 'challonge-show' );
			$dat.val(JSON.stringify(data));
		}
	});
	// Dialog
	$headShortList.find( 'li' ).each(function(){
		var $dialog = $( this ).find( '.challonge-headers_shortcode-editform' ).dialog({
			autoOpen:false,
			width:350,
			height:250,
			modal:true,
			buttons: {
				// TODO: "Done" and "Cancel" should be translatable
				Done: function () {
					// Set stored JSON data values to new values from input values
					var $fs = $dialog.find('fieldset'),
						$dat = $( '#challonge-headers_shortcode-' + $dialog.data( 'prop' ) ),
						data = JSON.parse($dat.val());
					$dat.val(JSON.stringify($.extend(data,{
						alias : $fs.find('input[id$="-alias"]'  ).val(),
						format: $fs.find('select[id$="-format"]').val(),
					})));
					$dat.closest('li').find('.challonge-headers_shortcode-label').text(data.alias?data.alias:data.name);
					// Close modal
					$dialog.dialog( 'close' );
				},
				Cancel: function() {
					// Reset input values back to previous values from JSON data
					var $fs = $dialog.find('fieldset'),
						$dat = $( '#challonge-headers_shortcode-' + $dialog.data( 'prop' ) ),
						data = JSON.parse($dat.val());
					$fs.find('input[id$="-alias"]'  ).val(data.alias );
					$fs.find('select[id$="-format"]').val(data.format);
					// Close modal
					$dialog.dialog( 'close' );
				}
			},
			close: function() {
				// nothing
			},
		});
		$( this ).find( '.challonge-headers_shortcode-edit' ).click( function( e ) {
			$dialog.dialog( 'open' );
		});
	});
});
