//
// JS-помощник всплывающих сообщений
//
//

jQuery( document ).ready( function( jq ) {

    // Подключиться к эхо-серверу

    var socket = io.connect( websocket_param['url'] + ':' + websocket_param['port'] );

    // Подключиться к своему каналу

    socket.emit( 'joinToRoom', { room: websocket_param['room'] } );


    // Обновление всплывающих уведомлений

    socket.on( 'float_notification_update', function( data ) {

        if ( typeof float_notification_update == 'function') float_notification_update( jq );
        notify_alarm( data );
        
        // console.log(data);

        // var date = new Date();
        // var hour = date.getHours();

        // // Выводить звук, если это разрешено и сейчас подходящее время

        // if ( data['notify'] == 'yes' && hour >= 8 && hour < 22 ) {

        //     notufy = jq( '#notification_notify' )[0];
        //     notufy.volume = 0.1;
        //     notufy.play();

        // }

    });


    // Обновление диалогов

    socket.on( 'dialogues_update', function( data ) {

        if ( typeof dialogues_update_page == 'function') dialogues_update_page();
        notify_alarm( data );

        // console.log(data);

    });

})


//
// Звуковоле оповещение
//

function notify_alarm( data )
{

    var date = new Date();
    var hour = date.getHours();

    // Выводить звук, если это разрешено и сейчас подходящее время

    if ( data['notify'] == 'yes' && hour >= 8 && hour < 22 ) {

        notufy = jq( '#notification_notify' )[0];
        notufy.volume = 0.1;
        notufy.play();

    }

}

