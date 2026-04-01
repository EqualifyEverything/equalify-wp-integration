(function( $ ) {
	'use strict';

	$(function() {
		var $btn    = $( '#equalify-copy-url-btn' );
		var $notice = $( '#equalify-copy-notice' );

		if ( ! $btn.length ) {
			return;
		}

		$btn.on( 'click', function() {
			var url = $btn.data( 'url' );

			if ( navigator.clipboard && navigator.clipboard.writeText ) {
				navigator.clipboard.writeText( url ).then( function() {
					showCopied();
				} );
			} else {
				// Fallback for older browsers.
				var $input = $( '#equalify-feed-url-input' );
				$input.select();
				document.execCommand( 'copy' );
				showCopied();
			}
		} );

		function showCopied() {
			$notice.show();
			setTimeout( function() {
				$notice.fadeOut();
			}, 2500 );
		}
	} );

})( jQuery );
