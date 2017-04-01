//
// JS-помощник кнопки "Нравится"
//
//


jQuery( document ).ready( function( jq ) {

	time = 200;
    
    //
	// Отправляем данные о подтверждении дружбы
	//

	jq( '.custom-friendship-button.awaiting_response_friend' ).on( 'click', '.friendship-button', function() {

		// var button = jq( this ).parent().parent();
		var button = jq( this ).parent();
		var user_id = jq( this ).attr( 'id' );
        var nonce = jq( this ).attr( 'href' );
        var link = jq( this );

		user_id = user_id.split( '-' );
		user_id = user_id[1];

		nonce = nonce.split( '?_wpnonce=' );
		nonce = nonce[1].split( '&' );
		nonce = nonce[0];

        // alert(nonce);

		button.addClass( 'loading' );
		
		jq.post( ajaxurl, {
			action: 'awaiting-response',
			_wpnonce: nonce,
            user_id: user_id,
		},
		function( response ) {
            
            if ( response ) {

                button.fadeOut( time, function() { button.fadeIn( time ).html( response ) } );

            }
            
            // alert(response);

        //     if ( response == 'liked' ) {
        //         wrap.addClass( 'active' );
        //         n = target.find( "span" ).html();
        //         n++;
        //         target.find( "span" ).html( n )
        //     }

        //     if ( response == 'unliked' ) {
        //         wrap.removeClass( 'active' );
        //         n = target.find( "span" ).html();
        //         n--;
        //         target.find( "span" ).html( n )
        //     }

		});

		return false;

	} );

});
