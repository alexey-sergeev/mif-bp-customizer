//
// JS-помощник кнопки "Нравится"
//
//


jQuery( document ).ready( function( jq ) {

    time = 200;

	// //
	// // Отключить действие кнопки "три точки"
	// //

    // jq( 'a.disable-activity-type' ).on( 'click', function() { return false; });
    // jq( 'a.banned-users' ).on( 'click', function() { return false; });

	//
	// Отправляем данные о новом исключенном типе активности
	//

	jq( '#activity-stream' ).on( 'click', '.button.like', function() {

		var target = jq( this ),
        parent = target.closest('.activity-item');
        activity_id = parent.attr('id').substr( 9, parent.attr( 'id' ).length );

        var nonce = jq( this ).attr( 'href' );
		nonce = nonce.split('?_wpnonce=');
		nonce = nonce[1].split('&');
		nonce = nonce[0];

        // alert(activity_id);
        // alert(nonce);
        
		jq.post( ajaxurl, {
			action: 'like-button-press',
			_wpnonce: nonce,
            activity_id: activity_id,
		},
		function( response ) {
            
            if ( response == 'liked' ) {
                target.addClass( 'active' );
                n = target.find( "span" ).html();
                n++;
                target.find( "span" ).html( n )
            }

            if ( response == 'unliked' ) {
                target.removeClass( 'active' );
                n = target.find( "span" ).html();
                n--;
                target.find( "span" ).html( n )
            }

            // alert(response);
            // if ( response ) jq( 'li.' + response ).slideUp();
		});

		return false;

	} );

	// //
	// // Отправляем данные о новом заблокироанном пользователе
	// //

	// jq( '#item-buttons' ).on( 'click', '.banned-users a.ajax', function() {

    //     var nonce = jq( this ).attr( 'href' );
	// 	nonce = nonce.split('?_wpnonce=');
	// 	nonce = nonce[1].split('&');
	// 	nonce = nonce[0];

    //     // alert(nonce);
	// 	var userid = jq( this ).attr( 'data-userid' );
    //     var button = jq( this );

	// 	jq.post( ajaxurl, {
	// 		action: 'banned-user-button',
	// 		_wpnonce: nonce,
    //         userid: userid,
	// 	},
	// 	function( response ) {
    //         // alert(response);
    //         if ( response ) {
    //             button.fadeOut( time, function() { button.fadeIn( time ).html( response ) } );

    //             jq( '.acomment-user-' + userid ).toggleClass( 'none' );
    //             jq( '.friendship-button.add' ).toggleClass( 'none' );
    //             jq( 'i.banned-users.icon' ).toggleClass( 'none' );
    //     }
	// 	});

	// 	return false;

	// } );





});
