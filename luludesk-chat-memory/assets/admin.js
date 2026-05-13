/* global ludeskAdmin, jQuery */
( function ( $ ) {
	'use strict';

	$( document ).ready( function () {
		$( '#luludesk-test-btn' ).on( 'click', function () {
			var $btn    = $( this );
			var $result = $( '#luludesk-test-result' );
			var token   = $( '#luludesk_install_token' ).val().trim();

			if ( ! token ) {
				$result.css( 'color', '#b32d2e' ).text( ludeskAdmin.i18n.no_token );
				return;
			}

			$btn.prop( 'disabled', true );
			$result.css( 'color', '#646970' ).text( ludeskAdmin.i18n.testing );

			$.post(
				ludeskAdmin.ajaxurl,
				{
					action: 'luludesk_test_connection',
					nonce:  ludeskAdmin.nonce,
					token:  token,
				},
				function ( response ) {
					if ( response.success ) {
						$result.css( 'color', '#00a32a' ).text( ludeskAdmin.i18n.connected );
					} else {
						$result.css( 'color', '#b32d2e' ).text( ludeskAdmin.i18n.failed );
					}
				}
			).fail( function () {
				$result.css( 'color', '#b32d2e' ).text( ludeskAdmin.i18n.failed );
			} ).always( function () {
				$btn.prop( 'disabled', false );
			} );
		} );
	} );
} )( jQuery );
