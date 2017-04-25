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

            modify_page( response ); 

        });



    })


    //
	// Вывести продолжение списка диалогов
	//

	jq( '.thread-scroller' ).on( 'scroll',  function() {

        var container = jq( '.thread-scroller' );
        var loader = jq( '.thread-scroller .loader' );
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

                modify_page( response ); 

            });

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
	// Отправить сообщение
	//

	jq( '.messages-form' ).on( 'click', 'a.send.button', function() {

        var form = jq( this ).closest( 'form' );
        var thread_id = jq( '#thread_id', form ).val();
        var last_message_id = jq( '#last_message_id', form ).val();
        var nonce = jq( '#nonce', form ).val();
        var message = jq( '#message', form ).val();
        var threads_update_timestamp = jq( '#threads_update_timestamp' ).val();

        // Очистить форму
        jq( '#message', form ).val( '' );

        // Временно вывести новое сообщение и пролистать вниз
        var rand =  Math.floor( Math.random() * 9999 );
        jq( '.messages-scroller-container #message-' + last_message_id + ' .content .message' ).append( '<p class="new ' + rand + '"><i class="fa fa-clock-o"></i>' + message + '</p>' );
        setTimeout( function( rand ){ jq( '.messages-scroller-container .new.' + rand ).addClass( 'clock' ) }, 1000, rand );
        jq( '.messages-scroller' ).scrollTop( jq( '.messages-scroller-container' ).height() );
        


        jq.post( ajaxurl, {
            action: 'mif-bpc-dialogues-messages-send',
            thread_id: thread_id,
            last_message_id: last_message_id,
            threads_update_timestamp: threads_update_timestamp,
            message: message,
            _wpnonce: nonce,
        },
        function( response ) {

            // console.log(response);
            modify_page( response ); 

        });

        return false;

    });

    jq( '.messages-form' ).keypress( 'a.send.button', function( e ) {
        if ( e.which == 13 ) {
            // alert('You pressed enter!');
            jq( '.messages-form a.send.button' ).trigger( 'click' );
            return false;
        }
    });

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


    // 
    // Включить авторесайз textarea
    // 

    // autosize( jq( '.messages-form textarea' ) );
    // message_items_height_correct();


    // 
    // Корректировать размер страницы при изменении размера textarea
    // 

	// jq( '.messages-form textarea' ).on( 'autosize:resized', function() {

    //     message_items_height_correct();

    // } );
    


});




// 
// Корректировать высоту страниы в засивимости от высоты формы ввода
// 

function message_items_height_correct()
{
        var h_form = jq( '.dialogues-page .messages-form' ).height();
        var margin = h_form + 50;
        var padding = margin + 30;

        jq( '.dialogues-page .messages-form' ).css( 'margin-top', '-' + margin + 'px' );
        jq( '.dialogues-page .messages-wrap' ).css( 'padding-bottom', padding + 'px' );
        jq( '.messages-scroller' ).scrollTop( jq( '.messages-scroller-container' ).height() );
        // console.log(margin);
}


// 
// Инициализировать действия для страницы сообщений
// 

function messages_actions_init()
{

    // Показывает кастомный скроллинг для сообщений

    baron( jq( '.messages-scroller-wrap' ), {
                    scroller: '.messages-scroller',
                    container: '.messages-scroller-container',
                    bar: '.messages-scroller__bar',
                    barTop: 0,
                    barOnCls: 'messages-scroller__bar_state_on',
                    drag: 50,
                } );


    // Вывести продолжение списка сообщений

    jq( '.messages-scroller' ).on( 'scroll', function() {  

        var loader = jq( '.messages-scroller .loader' );
        var container = jq( '.messages-scroller' );
        var loader_top = loader.offset().top;
        var container_top = container.offset().top;
        var container_height = container.height();

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

                modify_page( response ); 

            });

        }
    
    });

}


//
// Внести изменения на страницу на основе полученных данных
//

function modify_page( response )
{

    if ( ! response ) return;

    var data = jQuery.parseJSON( response );
    
    // Загрузка продолжения заголовков диалогов

    if ( data['threads_more'] ) {

        jq( '.thread-scroller .loader' ).remove();
        jq( '.thread-scroller' ).append( data['threads_more'] );

    }

    // Загрузка продолжения списка сообщений

    if ( data['messages_more'] ) {

        jq( '.messages-scroller .loader' ).remove();
        jq( '.messages-scroller' ).prepend( data['messages_more'] );

    }

    // Загрузка заголовка списка сообщений

    if ( data['messages_header'] ) {

        jq( '.messages-header-content').animate( { 'opacity': 0 }, function() {

            jq( '.messages-header-content').html( data['messages_header'] );
            jq( '.messages-header-content').animate( { 'opacity': 1 } );

        } )
    }

    // Обновление заголовка списка сообщений

    if ( data['messages_header_update'] ) {

        jq( '.messages-header-content').html( data['messages_header_update'] );

    }

    // Обновление списка сообщений

    if ( data['threads_update'] ) {

        var arr = data['threads_update'];

        for ( var key in arr ) {

            var elem = jq( '.thread-scroller-container #thread-item-' + key );

            if ( elem.length ) {
                
                // Элемент существует - заменить его
                elem.remove();
                jq( '.thread-scroller-container' ).prepend( arr[key] );

            } else {

                // Элемент не существует - добавить новый в начало
                jq( '.thread-scroller-container' ).prepend( arr[key] );

            }

        }

    }

    // Обновление метки времени списка сообщений

    if ( data['threads_update_timestamp'] ) {

        jq( '#threads_update_timestamp' ).val( data['threads_update_timestamp'] );

    }

    // Загрузка формы списка сообщений

    if ( data['messages_form'] ) {

        jq( '.messages-form-content').animate( { 'opacity': 0 }, function() {

            jq( '.messages-form-content').html( data['messages_form'] );
            jq( '.messages-form-content').animate( { 'opacity': 1 } );

            jq( '.messages-form-content textarea').focus();

            // Уточнить высоту формы и диалога
            message_items_height_correct();

            // Увеличивать текстовое поле при появлении новых строк
            autosize( jq( '.messages-form textarea' ) );

            // Корректировать положение формы и высоту диалога при изменении размера формы
            jq( '.messages-form textarea' ).on( 'autosize:resized', function() {
                message_items_height_correct();
            });

        } )
    }

    // Загрузка страницы сообщений

    if ( data['messages_page'] ) {

        jq( '.messages-items').animate( { 'opacity': 0 }, function() {

            jq( '.messages-items').html( data['messages_page'] );

            // Увеличить начало списка сообщений, если список слишком короткий
            var h1 = jq( '.messages-scroller-container' ).height();
            var h2 = jq( '.messages-scroller' ).height();
            var delta = h2 - h1;
            if ( delta > 0 ) jq( '.message-item.loader' ).height( delta );

            // Пролистать в самый низ
            jq( '.messages-scroller' ).scrollTop( jq( '.messages-scroller-container' ).height() );

            // Показать
            jq( '.messages-items').animate( { 'opacity': 1 } );

            // Инициализировать действия со страницей сообщений
            messages_actions_init();

        })

    }

    // Отображение новых сообщений

    if ( data['messages_update'] ) {

        var arr = data['messages_update'];

        for ( var key in arr ) {

            if ( jq( '.messages-scroller-container #message-' + key ).length ) {
                
                // Элемент существует - заменить его
                jq( '.messages-scroller-container #message-' + key ).replaceWith( arr[key] );

            } else {

                // Элемент не существует - добавить новый в конец
                jq( '.messages-scroller-container' ).append( arr[key] );

            }

        }

        // Обновить информацию об ID последнего сообщения
        jq( '.messages-form #last_message_id' ).val( key );

        // Пролистать в самый низ
        jq( '.messages-scroller' ).scrollTop( jq( '.messages-scroller-container' ).height() );

    }
    




}
