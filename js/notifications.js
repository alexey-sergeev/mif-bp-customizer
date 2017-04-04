//
// JS-помощник раздела уведомлений
//
//


jQuery( document ).ready( function( jq ) {

	time = 200;
    


    //
	// Отметить всё на странице
	//

	jq( '.custom-notifications' ).on( 'change click', '#select-all-notifications', function() {

		var input = '.custom-notifications tbody input:checkbox';
		
		jq( input ).map( function() {
			if ( jq( '#select-all-notifications' ).prop("checked") ) {
				jq( this ).attr( 'checked', 'checked' ); 
			} else {
				jq( this ).removeAttr( 'checked' ); 
			}
		} );

	} );


    //
	// Удалить отмеченное
	//

	jq( '.custom-notifications' ).on( 'click', 'a.notification-bulk-delete', function() {

        var href = jq( this ).attr( 'href' );
		var form = jq( this ).closest( 'form' );
		var input = '.custom-notifications tbody input:checkbox:checked';
		var arr = jq( input ).map( function() { return this.value; } ).get();


		var nonce = href.split( '_wpnonce=' );
		nonce = nonce[1].split( '&' );
		nonce = nonce[0];

		jq.post( ajaxurl, {
			action: 'mif-bpc-notification-bulk-delete',
            arr: arr,
			_wpnonce: nonce,
		},
		function( response ) {

			if ( response ) {
				jq( input ).map( function() { 
					jq( this ).parent().parent().fadeOut();
			} );
			}

		});

		return false;

	} );


    //
	// Отметить отмеченное как прочитанное
	//

	jq( '.custom-notifications' ).on( 'click', 'a.notification-bulk-not-is-new', function() {

        var href = jq( this ).attr( 'href' );
		var form = jq( this ).closest( 'form' );
		var input = '.custom-notifications tbody input:checkbox:checked';
		var arr = jq( input ).map( function() { return this.value; } ).get();


		var nonce = href.split( '_wpnonce=' );
		nonce = nonce[1].split( '&' );
		nonce = nonce[0];

		jq.post( ajaxurl, {
			action: 'mif-bpc-notification-bulk-not-is-new',
            arr: arr,
			_wpnonce: nonce,
		},
		function( response ) {
			
			if ( response ) {
				jq( input ).map( function() { 
					jq( this ).parent().parent().removeClass( 'new' );
					jq( this ).removeAttr( 'checked' );
					jq( '#select-all-notifications' ).removeAttr( 'checked' );
			} );
			}

		});

		return false;

	} );


    //
	// Отметить как прочитанное
	//

	jq( '.custom-notifications' ).on( 'click', 'a.notification-to-not-new', function() {

		var tr = jq( this ).parent().parent().parent();
        var href = jq( this ).attr( 'href' );
        var id = jq( this ).attr( 'id' );

		var nonce = href.split( '_wpnonce=' );
		nonce = nonce[1].split( '&' );
		nonce = nonce[0];

		id = id.split( '-' );
		id = id[2];

		jq.post( ajaxurl, {
			action: 'mif-bpc-notification-to-not-new',
            id: id,
			_wpnonce: nonce,
		},
		function( response ) {

			if ( response ) tr.removeClass( 'new' );

		});

		return false;

	} );



    //
	// Отметить как непрочитанное
	//

	jq( '.custom-notifications' ).on( 'click', 'a.notification-to-new', function() {

		var tr = jq( this ).parent().parent().parent();
        var href = jq( this ).attr( 'href' );
        var id = jq( this ).attr( 'id' );

		var nonce = href.split( '_wpnonce=' );
		nonce = nonce[1].split( '&' );
		nonce = nonce[0];

		id = id.split( '-' );
		id = id[2];

		jq.post( ajaxurl, {
			action: 'mif-bpc-notification-to-new',
            id: id,
			_wpnonce: nonce,
		},
		function( response ) {

			if ( response ) tr.addClass( 'new' );

		});

		return false;

	} );



    //
	// Удалить активность
	//

	jq( '.custom-notifications' ).on( 'click', 'a.notification-delete', function() {

		var tr = jq( this ).parent().parent().parent();
        var href = jq( this ).attr( 'href' );
        var id = jq( this ).attr( 'id' );

		var nonce = href.split( '_wpnonce=' );
		nonce = nonce[1].split( '&' );
		nonce = nonce[0];

		id = id.split( '-' );
		id = id[2];

		
		jq.post( ajaxurl, {
			action: 'mif-bpc-notification-delete',
            id: id,
			_wpnonce: nonce,
		},
		function( response ) {

			if ( response ) tr.fadeOut();
			
		});

		return false;

	} );



    //
	// Отправляем запрос "читать далее"
	//

	jq( '.custom-notifications' ).on( 'click', 'a.load-more', function() {

        // alert('12');

		var button = jq( this ).parent();
		var tr = jq( this ).parent().parent().parent();
		var tbody = jq( this ).parent().parent().parent().parent();
        var href = jq( this ).attr( 'href' );

		nonce = href.split( '_wpnonce=' );
		nonce = nonce[1].split( '&' );
		nonce = nonce[0];

		page = href.split( 'page=' );
		page = page[1].split( '&' );
		page = page[0];

		// alert(page);

		tr.addClass( 'loading' );
		
		jq.post( ajaxurl, {
			action: 'mif-bpc-notifications-load-more',
            page: page,
			_wpnonce: nonce,
		},
		function( response ) {

			var elem = jq( response );
			
			tr.remove();

			elem.hide();
			tbody.append( elem );
			elem.slideDown();

			if ( jq( '#select-all-notifications' ).prop("checked") ) {
				jq( '.custom-notifications tbody input:checkbox' ).map( function() { jq( this ).attr( 'checked', 'checked' ); } );
			}

		});

		return false;

	} );

});
