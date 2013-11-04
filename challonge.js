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
						params['report_wl'] = formData[i].value;
						break;
					case 'challonge_report_score':
						params['report_score'] = formData[i].value;
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
		}
		$lnkConfirm.hide().html( '<img src="' + challongeVar.spinUrl + '" />' ).fadeIn('slow');
		$.post(
			challongeVar.ajaxUrl,
			params,
			function ( data, status ) {
				$lnkConfirm.finish().hide().html( data ).fadeIn('slow', function () {
					$metaHtml = $(this).find('.challonge-metahtml');
					if ( $metaHtml.length )
						$('.challonge-tournyid-'+lnkTourny).replaceWith( $metaHtml.html() );
				} );
			}
		);
	} );
} );