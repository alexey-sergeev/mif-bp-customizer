//
// JS-помощник диалогов
//
//

jQuery( document ).ready( function( jq ) {

    var time = 200;

    //
	// Показать диалог
	//

	jq( '.thread-wrap' ).on( 'click', '.thread-item', function() {

        var thread_id = jq( this ).attr( 'data-thread-id' );
        var nonce = jq( this ).attr( 'data-nonce' );

        jq.post( ajaxurl, {
            action: 'mif-bpc-dialogues-messages',
            thread_id: thread_id,
            _wpnonce: nonce,
        },
        function( response ) {
            
            if ( response ) {

                jq( '.messages-items').animate( { 'opacity': 0 }, function() {


                jq( '.messages-items').html( response );

                var h1 = jq( '.message-scroller-container' ).height();
                var h2 = jq( '.message-scroller' ).height();
                var delta = h2 - h1;
                // console.log(h1);
                // console.log(h2);
                // console.log(delta);

                if ( delta > 0 ) jq( '.message-item.loader' ).height( delta );

                jq( '.message-scroller' ).scrollTop( jq( '.message-scroller-container' ).height() );
                // var msg_scroller = jq( '.message-scroller' );
                // console.log(msg_scroller.height());

                // msg_scroller.scrollTop( msg_scroller.height() );
                // msg_scroller.scrollTop( 9999 );

                jq( '.messages-items').animate( { 'opacity': 1 } );
            })


                // jq( '.messages-items').fadeIn( time, function() {
                
                //         var h1 = jq( '.message-scroller-container' ).height();
                //         var h2 = jq( '.message-scroller' ).height();
                //         var delta = h2 - h1;

                //         if ( delta > 0 ) jq( '.message-item.loader' ).height( delta );

                //         var msg_scroller = jq( '.message-scroller' );
                //         msg_scroller.scrollTop( msg_scroller.height() )

                //     } ).html( response );


                // } );


                // jq( '.messages-items').html( response );

                // var h1 = jq( '.message-scroller-container' ).height();
                // var h2 = jq( '.message-scroller' ).height();
                // var delta = h2 - h1;

                // if ( delta > 0 ) jq( '.message-item.loader' ).height( delta );

                // var msg_scroller = jq( '.message-scroller' );
                // msg_scroller.scrollTop( msg_scroller.height() )



                // loader.remove();
                // container.append( response );
                
            // console.log(response);
            
            }

        });



    })


    //
	// Вывести продолжение списка диалогов
	//

	jq( '.thread-scroller' ).on( 'scroll',  function() {

        // var loader_top = jq( '.loader' ).offset().top;
        var loader = jq( this ).find( '.loader' );
        var container = jq( this ).find( '.scroller-container' );
        var loader_top = loader.offset().top;
        var scroller_bottom = jq( '.scroller' ).offset().top + ( jq( '.scroller' ).height() * 2 );
        
        if ( loader_top < scroller_bottom && loader.hasClass( 'noajax' ) ) {

            loader.removeClass( 'noajax' );

            var page = loader.attr( 'data-page' );
            var nonce = loader.attr( 'data-nonce' );

            jq.post( ajaxurl, {
                action: 'mif-bpc-dialogues-thread-items-more',
                page: page,
                _wpnonce: nonce,
            },
            function( response ) {
                
                if ( response ) {

                    loader.remove();
                    container.append( response );
                    
                // console.log(container.html());
                
                }

            });
            


            // console.log(page);
            // console.log(nonce);
        }
        
    });


    //
	// Закрепить форму диалогов на странице
	//

	jq( '.dialogues-page' ).on( 'click', 'a.dialogues-fix', function() {
        jq( 'body' ).animate( { scrollTop: jq( '.dialogues-page' ).offset().top }, time * 2, function() { jq( 'body' ).toggleClass( 'fix ') } );
        return false;
    });


    //
    // Пролистать сообщения в самый низ
    //

    var h1 = jq( '.message-scroller-container' ).height();
    var h2 = jq( '.message-scroller' ).height();
    var delta = h2 - h1;

    if ( delta > 0 ) jq( '.message-item.loader' ).height( delta );

    var msg_scroller = jq( '.message-scroller' );
    msg_scroller.scrollTop( msg_scroller.height() )


    // 
    // Включить кастомный скроллинг
    // 

    baron( jq( '.thread-scroller-wrap' ), {
                    scroller: '.thread-scroller',
                    container: '.thread-scroller-container',
                    bar: '.thread-scroller__bar',
                    barTop: 0,
                    barOnCls: 'thread-scroller__bar_state_on',
                } );


    baron( jq( '.message-scroller-wrap' ), {
                    scroller: '.message-scroller',
                    container: '.message-scroller-container',
                    bar: '.message-scroller__bar',
                    barTop: 0,
                    barOnCls: 'message-scroller__bar_state_on',
                    drag: 50,
                } );


});

