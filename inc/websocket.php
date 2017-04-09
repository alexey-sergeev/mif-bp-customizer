<?php

//
// Настройка всплывающих уведомлений
// 
//


defined( 'ABSPATH' ) || exit;

// use ElephantIO\Client;
// use ElephantIO\Engine\SocketIO\Version1X;

if ( mif_bpc_options( 'websocket' ) ) {

    global $mif_bpc_websocket;
    $mif_bpc_websocket = new mif_bpc_websocket();

}


class mif_bpc_websocket {

    //
    // Стандартный механизм уведомлений, но с улучшенной интерфейсной частью
    //


    //
    // Порт эхо-сервера
    //

    public $port = 8080;

    //
    // Секретный ключ эхо-сервера
    //

    public $secure_key = 'A4nYoRiq0dispfmCkFzfUAtAnV6wBglC';



    function __construct()
    {
       
        // Отправка уведомлений клиентам

        add_action( 'bp_notification_before_update', array( $this, 'notification_before_update' ), 10, 2 );
        add_action( 'bp_notification_before_delete', array( $this, 'notification_before_delete' ) );
        add_action( 'bp_notification_before_save', array( $this, 'notification_before_save' ) );


        // JS-скрипты для связки "браузер - эхо-сервер"

        add_action( 'wp_print_scripts', array( $this, 'load_js_helper' ) );            				
        add_action( 'wp_head', array( $this, 'add_js_param' ) );

    }


    // 
    // Выяснить ID пользователя при изменении состояния уведомлений и отправить уведомление клиенту по поводу обновления уведомлений
    // 

    function notification_before_update( $data1, $data2 )
    {
        $notification = bp_notifications_get_notification( $data2['id'] );
        $this->send_notification( $notification->user_id );
    }

    function notification_before_delete( $data )
    {
        $notification = bp_notifications_get_notification( $data['id'] );
        $this->send_notification( $notification->user_id );
    }

    function notification_before_save( $data )
    {
        $this->send_notification( $data->user_id );
    }



    // 
    // Отправляет уведомление клиенту
    // 

    function send_notification( $user_id )
    {
        $url = $this->get_local_url();
        $port = $this->get_port();
        $room = $this->get_user_room( $user_id );
        $key = $this->get_secure_key();

        // require __DIR__ . '/../vendor/autoload.php';
        // include_once dirname( __FILE__ ) . '/../classes/elephant.io/Client.php';

        $event = 'float_notification_update';
        $data = '1';

        try {

            $conn = curl_init();
        	curl_setopt( $conn, CURLOPT_URL, $url . ':' . $port . '?' . 'key=' . $key . '&room=' . $room . '&event=' . $event . '&data=' . $data );
        	curl_setopt( $conn, CURLOPT_NOBODY, 1 );
        	curl_exec( $conn );
        	curl_close( $conn );            

        } catch ( Exception $e ) {

            // Сообщение о том, что эхо-сервер не работает

            do_action( 'mif_bpc_echo_server_not_worked' );

        };


    }


    //
    // Параметры подключения клиента к эхо-серверу
    //

    function add_js_param()
    {
        if ( ! is_user_logged_in() ) return;

        $url = $this->get_url();
        $port = $this->get_port();
        $room = $this->get_user_room();

        $out = '<script>websocket_param = { url: "' . $url . '", port: ' . $port . ', room: "' . $room . '" }</script>';
        
        echo $out;
    }


    //
    // JS-помощник и библиотека для связки "браузер - эхо-сервер"
    //

    function load_js_helper()
    {
        if ( ! is_user_logged_in() ) return;

        wp_enqueue_script( 'mif_bpc_websocket', plugins_url( '../js/websocket.js', __FILE__ ), array( 'mif_bpc_socket_io' ) );
        wp_enqueue_script( 'mif_bpc_socket_io', plugins_url( '../js/socket.io/socket.io.min.js', __FILE__ ) );

    }


    //
    // Получить адрес эхо-сервера для клиента
    //

    function get_url()
    {
        return apply_filters( 'mif_bpc_websocket_url', get_option( 'mif_bpc_websocket_url', home_url() ) );
    }


    //
    // Получить адрес эхо-сервера для локальной машины
    //

    function get_local_url()
    {
        return apply_filters( 'mif_bpc_websocket_url', get_option( 'mif_bpc_websocket_local_url', 'http://localhost' ) );
    }


    //
    // Получить порт, на котором работает эхо-сервер
    //

    function get_port()
    {
        return apply_filters( 'mif_bpc_websocket_port', get_option( 'mif_bpc_websocket_url', $this->port ) );
    }


    //
    // Получить секретный ключ для эхо-сервера
    //

    function get_secure_key()
    {
        return apply_filters( 'mif_bpc_websocket_port', get_option( 'mif_bpc_websocket_url', $this->secure_key ) );
    }


    //
    // Получить имя комнаты (канала) для пользователя
    //

    function get_user_room( $user_id = NULL )
    {
        if ( $user_id == NULL ) $user_id = bp_loggedin_user_id();
        $user = get_user_by( 'id', $user_id );
        return apply_filters( 'mif_bpc_websocket_room', wp_hash( $user->user_login . '-' . $user->user_pass ) );
    }


}


?>