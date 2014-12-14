jQuery(document).ready(function ( $ ) {
	$(document).on( 'click', '.challonge-lnkconfirm .challonge-button', function ( e ) {
		var $this, $lnkConfirm, lnkAction, lnkTourny, $form, formData, $metaHtml, i;
		e.preventDefault();
		$this = $( this );
		$lnkConfirm = $this.parent();
		lnkAction = $this.data('lnkaction');
		lnkTourny = $this.data('lnktourny');
		params = {
			action:     'challonge_widget',
			lnk_action: lnkAction,
			lnk_tourny: lnkTourny
		};
		if ( 'report' == lnkAction ) {
			$form = $('#challonge-report');
			formData = $form.serializeArray();
			for ( i in formData ) {
				switch ( formData[i].name ) {
					case 'challonge_report_wl':
						params.report_wl = formData[i].value;
						break;
					case 'challonge_report_score':
						params.report_score = formData[i].value;
						break;
					case 'challonge_report_opponent_score':
						params.report_opponent_score = formData[i].value;
						break;
					default:
						// do nothing
						break;
				}
			}
			if ( 'undefined' == typeof params.report_wl ) {
				alert( challongeVar.wltMsg );
				return;
			}
			$form.find('input').prop( 'disabled', true );
		} else if ( 'join' == lnkAction ) {
			params.playername_input = [];
			$('.challonge-playername').find('input').each( function () {
				params.playername_input.push( $(this).val() );
			} ).prop( 'disabled', true );
		}
		$lnkConfirm.hide().html( '<img src="' + challongeVar.spinUrl + '" />' ).fadeIn('slow');
		$.post(
			challongeVar.ajaxUrl,
			params,
			function ( data, status ) {
				$lnkConfirm.finish().hide().html( data ).fadeIn('slow', function () {
					$metaHtml = $(this).find('.challonge-metahtml');
					if ( $metaHtml.length )
						$('.challonge-tournyid-'+lnkTourny).filter(':first').replaceWith( $metaHtml.html() );
				} );
			}
		).fail( function () {
			$lnkConfirm.finish().hide().html(
				'<p class="challonge-error">' + challongeVar.errorMsg
					+ ' -- <a href="#close" onclick="tb_remove();return false;">'
					+ challongeVar.closeMsg + '</a></p>'
			).fadeIn('slow');
		} );
	} ).on( 'submit', '#challonge-loginform', function ( e ) {
		var $redirect = $( this ).find('input[name="redirect_to"]');
		var idx = $redirect.val().indexOf('challonge_signup=');
		$redirect.val( window.location.href + ( window.location.href.indexOf('?') == -1 ? '?' : '&' ) + $redirect.val().substr( idx ) );
	} );
	if ( window.location.search.indexOf('challonge_signup=') != -1 ) {
		var idx = window.location.search.indexOf('challonge_signup=');
		var tournyid = window.location.search.substr( idx + 17 );
		if ( tournyid.indexOf('&') != -1 )
			tournyid = tournyid.substr( 0, tournyid.indexOf('&') );
		setTimeout( "Challonge_jQuery('.challonge-button.thickbox.challonge-tournyid-" + tournyid + "').get(0).click();", 1000 );
	}
	var onResize = function () {
		var $win = $(this);
		var winW = $win.width()-80;
		var winH = $win.height()-80;
		var rex = /(\?action=challonge_widget&width)=\d+(&height)=\d+\b(.*&lnk_action=view)$/;
		$('a.thickbox').each( function () {
			var $this = $(this);
			var href = $this.attr('href');
			var newHref = href.replace( rex, '$1=' + winW + '$2=' + winH + '$3' );
			if ( href != newHref )
				$this.attr( 'href', newHref );
		} );
	};
	$(window).resize( onResize );
	setTimeout( onResize, 10 );
} );

// Our own jQuery variable that points to WP's own copy of jQuery. We shouldn't lose this. ;)
// If a theme or plugin loads its own copy without compatibility mode, we can still use the copy we loaded the Challonge jQuery plugin into.
var Challonge_jQuery = jQuery;