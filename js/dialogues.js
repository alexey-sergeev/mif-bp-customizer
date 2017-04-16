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

                    // Увеличить начало списка сообщений, если список слишком короткий

                    var h1 = jq( '.messages-scroller-container' ).height();
                    var h2 = jq( '.messages-scroller' ).height();
                    var delta = h2 - h1;
                    if ( delta > 0 ) jq( '.message-item.loader' ).height( delta );

                    // Пролистать в самый низ

                    jq( '.messages-scroller' ).scrollTop( jq( '.messages-scroller-container' ).height() );

                    // Показать

                    jq( '.messages-items').animate( { 'opacity': 1 } );

                    // Активировать кастомный скроллинг

                    show_message_scroller();

                    // Показывать новые сообщения при прокрутке диалога

                    show_load_more_message();

                })
           
            }

        });



    })


    //
	// Вывести продолжение списка сообщений
	//

// 	jq( '.messages-wrap' ).on( 'scroll', '.messages-scroller', function() {

// alert('11');
//         var loader = jq( this ).find( '.loader' );
//         var container = jq( this ).find( '.scroller-container' );
//         var loader_top = loader.offset().top;
//         var scroller_bottom = jq( '.scroller' ).offset().top + ( jq( '.scroller' ).height() * 2 );

//         console.log(loader);

//         if ( loader_top < scroller_bottom && loader.hasClass( 'noajax' ) ) {

//             // loader.removeClass( 'noajax' );

//             // var page = loader.attr( 'data-page' );
//             // var nonce = loader.attr( 'data-nonce' );

//             // jq.post( ajaxurl, {
//             //     action: 'mif-bpc-dialogues-thread-items-more',
//             //     page: page,
//             //     _wpnonce: nonce,
//             // },
//             // function( response ) {
                
//             //     if ( response ) {

//             //         loader.remove();
//             //         container.append( response );
                    
//             //     // console.log(container.html());
                
//             //     }

//             // });
            


//             // console.log(page);
//             // console.log(nonce);
//         }
        
//     });


    //
	// Вывести продолжение списка диалогов
	//

	jq( '.thread-scroller' ).on( 'scroll',  function() {

        // var loader_top = jq( '.loader' ).offset().top;
        var loader = jq( this ).find( '.loader' );
        var container = jq( this ).find( '.scroller-container' );
        var loader_top = loader.offset().top;
        var scroller_bottom = jq( '.scroller' ).offset().top + ( jq( '.scroller' ).height() * 2 );
        
        if ( loader_top < scroller_bottom && loader.hasClass( 'ajax-ready' ) ) {

            loader.removeClass( 'ajax-ready' );

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


    // baron( jq( '.message-scroller-wrap' ), {
    //                 scroller: '.message-scroller',
    //                 container: '.message-scroller-container',
    //                 bar: '.message-scroller__bar',
    //                 barTop: 0,
    //                 barOnCls: 'message-scroller__bar_state_on',
    //                 drag: 50,
    //             } );


});



// 
// Показывает кастомный скроллинг для сообщений
// 

function show_message_scroller()
{
    baron( jq( '.messages-scroller-wrap' ), {
                    scroller: '.messages-scroller',
                    container: '.messages-scroller-container',
                    bar: '.messages-scroller__bar',
                    barTop: 0,
                    barOnCls: 'messages-scroller__bar_state_on',
                    drag: 50,
                } );

}

// 
// Показывать новые сообщения при прокрутке диалога
// 

function show_load_more_message() {

	jq( '.messages-scroller' ).on( 'scroll', function() {  

        var loader = jq( this ).find( '.loader' );
        var container = jq( this );
        var loader_top = loader.offset().top;
        var container_top = container.offset().top;
        var container_height = container.height();

        // console.log(container_top);

        if ( loader_top > container_top -  container_height && loader.hasClass( 'ajax-ready' ) ) {

            loader.removeClass( 'ajax-ready' );

            var page = loader.attr( 'data-page' );
            var nonce = loader.attr( 'data-nonce' );
            var tid = loader.attr( 'data-tid' );

            jq.post( ajaxurl, {
                action: 'mif-bpc-dialogues-messages-items-more',
                page: page,
                tid: tid,
                _wpnonce: nonce,
            },
            function( response ) {
                
                if ( response ) {

                    loader.remove();
                    container.prepend( response );

                }

            })

        }
    
    });
}
