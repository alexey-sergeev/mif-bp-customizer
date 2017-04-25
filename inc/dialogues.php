<?php

//
// Диалоги
// 
//


defined( 'ABSPATH' ) || exit;

if ( mif_bpc_options( 'dialogues' ) ) {

    global $mif_bpc_dialogues;
    $mif_bpc_dialogues = new mif_bpc_dialogues();

}


class mif_bpc_dialogues {

    //
    // Простые и удобные диалоги вместо стандартной системы сообщений
    //


    // //
    // // Сколько уведомлений показывать на странице
    // //

    // public $per_page = 15;


    //
    // Размер аватарки
    //

    public $avatar_thread_size = 50;
    public $avatar_message_size = 40;

    //
    // Диалогов на одной странице в списке диалогов
    //

    public $threads_on_page = 10;

    //
    // Сообщений на одной странице сообщений
    //

    public $messages_on_page = 10;

    //
    // Время устаревания сообщения (секунд)
    //

    public $message_outdate_time = 60;



    function __construct()
    {
       
        // Страница диалогов
        add_action( 'bp_activity_setup_nav', array( $this, 'dialogues_nav' ) );
        // add_action( 'bp_init', array( $this, 'delete_notifications_nav' ) );

        add_action( 'wp_enqueue_scripts', array( $this, 'load_js_helper' ) );            				
        // add_action( 'wp_print_scripts', array( $this, 'load_js_helper' ) );            				
        // add_action( 'wp_enqueue_scripts', 'mif_bp_customizer_styles' );

        add_action( 'wp_ajax_mif-bpc-dialogues-thread-items-more', array( $this, 'ajax_thread_more_helper' ) );
        add_action( 'wp_ajax_mif-bpc-dialogues-messages', array( $this, 'ajax_messages_helper' ) );
        add_action( 'wp_ajax_mif-bpc-dialogues-messages-items-more', array( $this, 'ajax_messages_more_helper' ) );
        add_action( 'wp_ajax_mif-bpc-dialogues-messages-send', array( $this, 'ajax_messages_send_helper' ) );


        // Обработка текста сообщений
        add_filter( 'mif_bpc_dialogues_message_item_message', array( $this, 'autop' ) );
        add_filter( 'mif_bpc_dialogues_message_item_message', 'stripslashes_deep' );

        // add_filter( 'mif_bpc_dialogues_message_item_message', 'wp_filter_kses', 1 );
        // add_filter( 'mif_bpc_dialogues_message_item_message', 'force_balance_tags', 1 );
        // add_filter( 'mif_bpc_dialogues_message_item_message', 'wptexturize' );
        // add_filter( 'mif_bpc_dialogues_message_item_message', 'convert_chars' );
        // add_filter( 'mif_bpc_dialogues_message_item_message', 'wpautop' );


        // Разные корректировки
        // add_filter( 'bp_message_thread_to', array( $this, 'recipient_links' ) );

    }


    // 
    // Страница уведомлений
    // 

    function dialogues_nav()
    {
        global $bp;

        $parent_url = $bp->displayed_user->domain . $bp->messages->slug . '/';
        $parent_slug = $bp->messages->slug;

        $sub_nav = array(  
                'name' => __( 'Диалоги', 'mif-bp-customizer' ), 
                'slug' => 'dialogues', 
                'parent_url' => $parent_url, 
                'parent_slug' => $parent_slug, 
                'screen_function' => array( $this, 'screen' ), 
                'position' => 10,
                'user_has_access'=>  bp_is_my_profile() 
            );

        bp_core_new_subnav_item( $sub_nav );
       
    }


    //
    // Содержимое страниц
    //

    function screen()
    {
        global $bp;
        add_action( 'bp_template_content', array( $this, 'body' ) );
        bp_core_load_template( apply_filters( 'bp_core_template_plugin', 'members/single/plugins' ) );
    }

    function body()
    {
        if ( $template = locate_template( 'dialogues-page.php' ) ) {
           
            load_template( $template, false );

        } else {

            load_template( dirname( __FILE__ ) . '/../templates/dialogues-page.php', false );

        }
    }


    // 
    // JS-помощник
    // 

    function load_js_helper()
    {
        // Плагин скроллинга        
        wp_enqueue_script( 'mif_bpc_baron_core', plugins_url( '../js/mif-bpc-baron.js', __FILE__ ) );
        wp_enqueue_script( 'mif_bpc_autosize', plugins_url( '../js/plugins/autosize.js', __FILE__ ) );

        wp_enqueue_script( 'mif_bpc_dialogues_helper', plugins_url( '../js/dialogues.js', __FILE__ ) );

    	// wp_enqueue_style( 'mif_bpc_baron_styles_core', plugins_url( '../js/baron/baron.css', __FILE__ ) );
    	// wp_enqueue_style( 'mif_bpc_baron_styles_helper', plugins_url( '../js/baron/style.css', __FILE__ ) );

    }


    //
    // Получить аватар отправителя
    //

    function get_sender_avatar( $thread, $avatar_size = 0 )
    {
        $user_id = ( count( $thread['user_ids'] ) == 1 ) ? $thread['user_ids'][0] : $thread['sender_id'];

        if ( $avatar_size == 0 ) $avatar_size = apply_filters( 'mif_bpc_dialogues_avatar_thread_size', $this->avatar_thread_size );
        $avatar = get_avatar( $user_id, $avatar_size );

        return apply_filters( 'mif_bpc_dialogues_get_sender_avatar', $avatar, $sender_id, $avatar_size );
    }


    //
    // Получить заголовок диалога
    //

    function get_thread_title( $thread, $links = false )
    {
        $sender_ids = $thread['user_ids'];
        // $subject = $thread['subject'];

        $arr = array();
        // $arr[] = count( $sender_ids );

        if ( count( $sender_ids ) > 3 ) {

            $arr[] = $this->get_username( $thread['sender_id'], $links );
            $sender_ids_without_sender_id = array_merge( $sender_ids, array( $thread['sender_id'] ) );
            $arr[] = $this->get_username( $sender_ids_without_sender_id[0], $links );

            $title = implode( ', ', $arr );
            $title .= ' ' . sprintf( __( 'и другие (всего %s)', 'mif-bp-customizer' ), number_format_i18n( count( $sender_ids ) ) );
            // $title = ( isset( $subject ) ) ? $subject : sprintf( __( 'Получателей: %s', 'mif-bp-customizer' ), number_format_i18n( count( $sender_ids ) ) );

        } else {

            foreach ( (array) $sender_ids as $sender_id ) $arr[] = $this->get_username( $sender_id, $links );
            $title = implode( ', ', $arr );

        }


        return apply_filters( 'mif_bpc_dialogues_thread_title', $title, $thread );
    }


    //
    // Сформировать имя пользователя для заголовков диалогов
    //


    function get_username( $user_id, $links = false )
    {
        $username = bp_core_get_user_displayname( $user_id );

        if ( empty( $username ) ) { 

            $username = 'deleted';

        } elseif ( $links ) {

            $url = bp_core_get_user_domain( $user_id );
            $username = '<a href="' . $url . '">' . $username . '</a>';

        }

        return apply_filters( 'mif_bpc_dialogues_get_username', $username, $user_id, $links );
    }


    // //
    // // Получить получателей сообщения, кропе самого пользователя
    // //

    // function get_recipients_ids()
    // {
    //     global $messages_template;

    //     $recipients = $messages_template->thread->recipients;
    //     $user_id = bp_loggedin_user_id();

    //     if ( isset( $recipients[$user_id] ) ) unset( $recipients[$user_id] );

    //     return array_keys( $recipients );
    // }

    //
    // Получить время последнего сообщения
    //

    // function get_last_message_date( $date )
    // {
    //     return apply_filters( 'mif_bpc_dialogues_last_message_date', bp_core_time_since( $date ) );
    // }


    //
    // Начало фразы последнего сообщения
    //

    function get_message_excerpt( $message )
    {
        $old = $message;
        $message = preg_replace( '/[\s]+/s', ' ', $message );
        $message = apply_filters( 'mif_bpc_dialogues_message_item_message', $message );
        $message = bp_create_excerpt( $message, 50, array( 'ending' => '...' ) );

        return apply_filters( 'mif_bpc_dialogues_message_excerpt', $message, $old );
    }


    // 
    // Выводит элемент списка диалогов
    // 

    function thread_item( $thread = NULL )
    {
        if ( $thread == NULL ) return;

        // p($thread);

        $avatar = $this->get_sender_avatar( $thread );
        $title = $this->get_thread_title( $thread );
        $time_since = apply_filters( 'mif_bpc_dialogues_thread_item_time_since', bp_core_time_since( $thread['date_sent'] ) );
        $message_excerpt = $this->get_message_excerpt( $thread['message'] );
        $nonce = wp_create_nonce( 'mif-bpc-dialogues-thread-nonce' );

        // p($avatar);

        $out = '';

        $out .= '<div class="thread-item" id="thread-item-' . $thread['thread_id'] . '" data-thread-id="' . $thread['thread_id'] . '" data-nonce="' . $nonce . '">';
        $out .= '<span class="avatar">' . $avatar . '</span>';
        $out .= '<span class="content">';
        // $out .= '<span class="title">' . $title . '</span><br />';
        // $out .= '<span class="time-since">' . $time_since . '</span><br />';
        $out .= '<span class="title">' . $title . '</span>';
        $out .= '<div><span class="time-since">' . $time_since . '</span></div>';
        $out .= '<div><span class="message-excerpt">' . $message_excerpt . '</span></div>';
        $out .= '</span>';
        $out .= '</div>';

        return $out;
    }


    //
    // Получить элементы списка диалогов
    //

    function get_threads_items( $page = 0, $user_id = NULL )
    {
        if ( $user_id == NULL ) $user_id = bp_loggedin_user_id();

        $threads = $this->get_threads_data( $page, $user_id );

        $arr = array();
        foreach ( $threads as $thread ) $arr[] = $this->thread_item( $thread );

        $page ++;
        $nonce = wp_create_nonce( 'mif-bpc-dialogues-thread-items-more-nonce' );
        // $arr[] = '<div class="thread-item loader ajax-ready" data-page="' . $page . '" data-nonce="' . $nonce . '"><i class="fa fa-spinner fa-spin fa-2x fa-fw"></i></div>';
        $arr[] = '<div class="thread-item loader ajax-ready" data-page="' . $page . '" data-nonce="' . $nonce . '"></div>';

        return apply_filters( 'mif_bpc_dialogues_get_threads_items', implode( "\n", $arr ), $arr, $page, $user_id );
    }


    //
    // Получить новые элементы списка диалогов
    //

    function get_threads_update( $last_updated = NULL, $user_id = NULL )
    {
        if ( $user_id == NULL ) $user_id = bp_loggedin_user_id();

        $threads = $this->get_threads_data( 0, $user_id, $last_updated );

        $arr = array();
        foreach ( $threads as $key => $thread ) $arr[$key] = $this->thread_item( $thread );

        // $arr = array_reverse( $arr, true );

        return apply_filters( 'mif_bpc_dialogues_get_threads_update', $arr, $user_id );
    }


    //
    // Загрузка продолжения списка диалогов
    //

    function ajax_thread_more_helper()
    {
        check_ajax_referer( 'mif-bpc-dialogues-thread-items-more-nonce' );

        $page = (int) $_POST['page'];
        echo json_encode( array( 'threads_more' => $this->get_threads_items( $page ) ) );

        wp_die();
    }



    //
    // Получить данные списка диалогов
    //

    function get_threads_data( $page = 0, $user_id = NULL, $last_updated = NULL )
    {
        global $bp, $wpdb;

        if ( $user_id == NULL ) $user_id = bp_loggedin_user_id();


        // Получить сведения о диалогах (номер, дата, id последнего сообщения)

        $search_sql = '';
        $user_id_sql = $wpdb->prepare( 'r.user_id = %d', $user_id );
        $pag_sql = $wpdb->prepare( "LIMIT %d, %d", intval( ( $page ) * $this->threads_on_page ), intval( $this->threads_on_page ) );

        // $last_updated = 1493100583;

        if ( isset( $last_updated ) ) {

            $sql = array();
            $sql['select'] = 'SELECT DISTINCT m.thread_id';
            $sql['from']   = "FROM {$bp->messages->table_name_messages} m INNER JOIN {$bp->messages->table_name_meta} t ON m.id = t.message_id";
            $sql['where']  = $wpdb->prepare( "WHERE t.meta_key = 'last_updated' AND t.meta_value >= %d", $last_updated );
            $new_ids = $wpdb->get_col( implode( ' ', $sql ) );

            $only_news_sql = 'AND m.thread_id IN (' . implode( ',', $new_ids) . ')';
            $pag_sql = '';

        }

        // p($news_ids);
        // p(implode( ' ', $sql ));


        // if ( $only_news ) {

        //     $last_threads_update = get_user_meta( $user_id, 'mif_bpc_last_threads_update', true );
        //     $only_news_sql = $wpdb->prepare( "AND date_sent >= %s", $last_threads_update );
        //     $pag_sql = '';

        // }

        // if ( $page == 0 ) update_user_meta( $user_id, 'mif_bpc_last_threads_update', bp_core_current_time() );

// $results = print_r($last_updated, true);
// file_put_contents('/tmp/log.txt', $results);



		$sql = array();
		$sql['select'] = 'SELECT m.thread_id, MAX(m.date_sent) AS date_sent, MAX(m.id) AS message_id';
		$sql['from']   = "FROM {$bp->messages->table_name_recipients} r INNER JOIN {$bp->messages->table_name_messages} m ON m.thread_id = r.thread_id";
		$sql['where']  = "WHERE r.is_deleted = 0 AND {$user_id_sql} {$only_news_sql} {$search_sql}";
		$sql['misc']   = "GROUP BY m.thread_id ORDER BY date_sent DESC {$pag_sql}";

        $threads = $wpdb->get_results( implode( ' ', $sql ) );

// p($threads);

        // if ( $page == 0 ) update_user_meta( $user_id, 'mif_bpc_last_threads_update', bp_core_current_time() );

// $results = print_r($threads, true);
// file_put_contents('/tmp/log.txt', $results);


        $arr = array();
        $thread_ids = array();
        $message_ids = array();
        foreach ( (array) $threads as $thread ) {

            $thread_ids[] = (int) $thread->thread_id;
            $message_ids[] = (int) $thread->message_id;
            // $arr[(int) $thread->thread_id]['date_sent'] = strtotime( $thread->date_sent );
            $arr[(int) $thread->thread_id]['date_sent'] = $thread->date_sent;
            $arr[(int) $thread->thread_id]['thread_id'] = $thread->thread_id;

        }


        // Для каждого диалога из списка узнать получателей (кроме текущего пользователя)

        $where_sql = $wpdb->prepare( 'r.user_id <> (%d)', $user_id );

		$sql = array();
		$sql['select'] = 'SELECT r.thread_id, GROUP_CONCAT(DISTINCT r.user_id) AS user_ids';
		$sql['from']   = "FROM {$bp->messages->table_name_recipients} r";
		$sql['where']  = 'WHERE r.thread_id IN (' . implode( ',', $thread_ids ) . ') AND ' . $where_sql;
		$sql['misc']   = "GROUP BY r.thread_id";

        $threads = $wpdb->get_results( implode( ' ', $sql ) );

        foreach ( (array) $threads as $thread ) $arr[(int) $thread->thread_id]['user_ids'] = explode( ',', $thread->user_ids );
        

        // Для каждого диалога из списка узнать автора, тему и начало последнего сообщения

		$sql = array();
		$sql['select'] = 'SELECT m.thread_id, sender_id, subject, LEFT(m.message,100) AS message';
		$sql['from']   = "FROM {$bp->messages->table_name_messages} m";
		$sql['where']  = 'WHERE m.id IN (' . implode( ',', $message_ids ) . ')';

        $threads = $wpdb->get_results( implode( ' ', $sql ) );

        foreach ( (array) $threads as $thread ) {
            
            $arr[(int) $thread->thread_id]['message'] = $thread->message;
            $arr[(int) $thread->thread_id]['subject'] = $thread->subject;
            $arr[(int) $thread->thread_id]['sender_id'] = $thread->sender_id;

        }

        return apply_filters( 'mif_bpc_dialogues_get_threads_data', $arr, $page, $user_id );
    }



    //
    // Получить ID последнего сообщения в диалоге
    //

    function get_last_message_id( $thread_id )
    {
        global $bp, $wpdb;

        $sql = $wpdb->prepare( "SELECT MAX(id) AS message_id FROM {$bp->messages->table_name_messages} WHERE thread_id = %d", $thread_id );
        $message_id = $wpdb->get_var( $sql );

        return apply_filters( 'mif_bpc_dialogues_get_last_message_id', $message_id, $thread_id );
    }



    //
    // Получить данные сообщений из диалога
    //

    function get_messages_data( $thread_id = NULL, $page = 0, $last_message_id = NULL )
    {
        if ( $thread_id == NULL ) return false;
                
        global $bp, $wpdb;

        if ( $last_message_id == NULL ) {

            $where_sql = $wpdb->prepare( 'thread_id = %d', $thread_id );
            $pag_sql = $wpdb->prepare( "LIMIT %d, %d", intval( ( $page ) * $this->messages_on_page ), intval( $this->messages_on_page ) );

        } else {

            $where_sql = $wpdb->prepare( 'thread_id = %d AND id >= %d', $thread_id, $last_message_id );
            $pag_sql = '';

        }

		$sql = array();
		$sql['select'] = 'SELECT id, sender_id, subject, message, date_sent';
		$sql['from']   = "FROM {$bp->messages->table_name_messages}";
		$sql['where']  = 'WHERE ' . $where_sql;
        $sql['misc']   = "ORDER BY date_sent DESC {$pag_sql}";

        $messages = $wpdb->get_results( implode( ' ', $sql ) );

        return apply_filters( 'mif_bpc_dialogues_get_messages_data', $messages, $thread_id, $page, $last_message_id );
    }


    // 
    // Преобразует переводы строк в знаки абзаца
    // 
    
    function autop( $text )
    {
        $text = preg_replace( '/[\r|\n]+/', "\n", trim( $text ) );
        $text = preg_replace( '/\n/', '<p>', $text );
        return $text;
    }


    //
    // Получить HTML-блок сообщения
    //

    function message_item( $message = NULL )
    {
        if ( $message == NULL ) return;

// p($message);

        $url = bp_core_get_user_domain( $message->sender_id );

        $avatar_size = apply_filters( 'mif_bpc_dialogues_avatar_message_size', $this->avatar_message_size );
        $avatar = '<a href="' . $url . '">' . get_avatar( $message->sender_id, $avatar_size ) . '</a>';
        $title = '<a href="' . $url . '">' . $this->get_username( $message->sender_id ) . '</a>';
        $time_since = apply_filters( 'mif_bpc_dialogues_message_item_time_since', bp_core_time_since( $message->date_sent ) );

        // $message_message = preg_replace( '/\n/', '<p>', trim( $message->message ) );

        // $message_message = apply_filters( 'bp_get_the_thread_message_content', $message->message );

        // $message_message = apply_filters( 'mif_bpc_dialogues_message_item_message', $message_message );

        $message_message = apply_filters( 'mif_bpc_dialogues_message_item_message', $message->message );
        $out = '';

        // $out .= '<div class="">';

        $out .= '<div class="message-item" id="message-' . $message->id . '" data-message-id="' . $message->id . '" data-sent="' . $message->date_sent . '">';
        $out .= '<div class="avatar">' . $avatar . '</div>';
        $out .= '<div class="content">';
        $out .= '<span class="title">' . $title . '</span> ';
        $out .= '<span class="time-since">' . $time_since . '</span>';
        $out .= '<span class="message">' . $message_message . '</span>';
        $out .= '</div>';
        $out .= '</div>';

        return $out;
    }


    //
    // Получить страницу cообщений из диалога
    //

    function get_messages_page( $thread_id = NULL, $page = 0 )
    {
        global $bp, $wpdb;

        if ( $thread_id == NULL ) return false;

        // Проверка прав пользователя на просмотр этих сообщений

        //
        // return false;

        // // Выбрать сообщения из базы данных

        // $where_sql = $wpdb->prepare( 'thread_id = %d', $thread_id );
        // $pag_sql = $wpdb->prepare( "LIMIT %d, %d", intval( ( $page ) * $this->messages_on_page ), intval( $this->messages_on_page ) );

		// $sql = array();
		// $sql['select'] = 'SELECT id, sender_id, subject, message, date_sent';
		// $sql['from']   = "FROM {$bp->messages->table_name_messages}";
		// $sql['where']  = 'WHERE ' . $where_sql;
        // $sql['misc']   = "ORDER BY date_sent DESC {$pag_sql}";

        // $threads = $wpdb->get_results( implode( ' ', $sql ) );

        // Получить нужную страницу сообщений
        $messages = $this->get_messages_data( $thread_id, $page );

        if ( empty( $messages ) ) return false;

        // Оформить сообщения в виде HTML-блоков 
        $arr = array();
        foreach ( (array) $messages as $message ) $arr[] = $this->message_item( $message );

        $page ++;
        $nonce = wp_create_nonce( 'mif-bpc-dialogues-messages-items-more-nonce' );
        $arr[] = '<div class="message-item loader ajax-ready" data-page="' . $page . '" data-nonce="' . $nonce . '" data-tid="' . $thread_id . '"></div>';

        $arr = array_reverse( $arr );

        return apply_filters( 'mif_bpc_dialogues_get_messages_page', implode( "\n", $arr ), $arr, $page, $thread_id );
    }



    //
    // Получить заголовок диалога
    //

    function get_messages_header( $thread_id = NULL, $user_id = NULL )
    {
        global $bp, $wpdb;

        if ( $thread_id == NULL ) return false;

        if ( $user_id == NULL ) $user_id = bp_loggedin_user_id();

        // Проверка прав пользователя на просмотр этих сообщений

        //
        // return false;

        $where_sql = $wpdb->prepare( 'm.thread_id = %d AND r.user_id <> (%d)', $thread_id, $user_id );

		$sql = array();
		// $sql['select'] = 'SELECT m.thread_id, MAX(m.date_sent) AS date_sent, MAX(m.id) AS message_id';
		$sql['select'] = 'SELECT m.sender_id, GROUP_CONCAT(DISTINCT r.user_id) AS user_ids';
		$sql['from']   = "FROM {$bp->messages->table_name_recipients} r INNER JOIN {$bp->messages->table_name_messages} m ON m.thread_id = r.thread_id";
		$sql['where']  = "WHERE {$where_sql}";
		$sql['misc']   = "GROUP BY m.thread_id ORDER BY date_sent DESC";

        $thread_objects = $wpdb->get_results( implode( ' ', $sql ) );

        $thread['sender_id'] = $thread_objects[0]->sender_id;
        $thread['user_ids'] = explode( ',', $thread_objects[0]->user_ids );

        // p(implode( ' ', $sql ));
        // p($threads);
        // $thread = array( $sender_id => )

        $thread_title = $this->get_thread_title( $thread, true );
        $header = '<span class="title">' . $thread_title . '</span>';

        if ( count( $thread['user_ids'] ) == 1 ) {

            $user_id = $thread['user_ids'][0];
            $last_activity = bp_get_last_activity( $user_id );
            $header .= ' <span class="time-since">' . $last_activity . '</span>';

        }

        return apply_filters( 'mif_bpc_dialogues_get_messages_header', $header, $thread_title, $avatar, $thread_id, $user_id );
    }


    // 
    // Выводит форму написания сообщения
    // 

    function get_messages_form( $thread_id )
    {
        $last_message_id = $this->get_last_message_id( $thread_id );
        
        $out = '';
        $out .= '<form>';
        $out .= '<table><tr>';
        $out .= '<td class="clip"><a href="11" class="clip"><i class="fa fa-2x fa-paperclip" aria-hidden="true"></i></a></td>';
        $out .= '<td class="message"><textarea name="message" id="message" placeholder="' . __( 'Напишите сообщение...', 'mif-bp-customizer' ) . '" rows="1"></textarea></td>';
        $out .= '<td class="send"><div class="custom-button"><a href="11" class="send button"><i class="fa fa-chevron-right" aria-hidden="true"></i></a></div></td>';
        $out .= '</tr></table>';
        $out .= wp_nonce_field( 'mif-bpc-dialogues-messages-send-nonce', 'nonce', true, false );
        $out .= '<input type="hidden" name="thread_id" id="thread_id" value="' . $thread_id . '">';
        $out .= '<input type="hidden" name="last_message_id" id="last_message_id" value="' . $last_message_id . '">';
        $out .= '</form>';

        return $out;
    }


    //
    // Загрузка диалога
    //

    function ajax_messages_helper()
    {
        check_ajax_referer( 'mif-bpc-dialogues-thread-nonce' );

        $thread_id = (int) $_POST['thread_id'];
        $page = (int) $_POST['page'];

        $out = '';

        $out .= '<div class="messages-scroller-wrap scroller-wrap"><div></div><div class="messages-scroller scroller"><div class="messages-scroller-container scroller-container">';
        $out .= $this->get_messages_page( $thread_id, $page );
        $out .= '</div><div class="messages-scroller__bar scroller__bar"></div></div></div>';

        echo json_encode( array( 
                                'messages_page' => $out,
                                'messages_header' => $this->get_messages_header( $thread_id ),
                                'messages_form' => $this->get_messages_form( $thread_id ),
                                ) );
        // echo $page;
        // echo $thread_id;


// function messages_check_thread_access( $thread_id, $user_id = 0 ) {
    // function messages_mark_thread_read( $thread_id ) {


        wp_die();
    }


    //
    // Загрузка продолжения списка сообщений
    //

    function ajax_messages_more_helper()
    {
        check_ajax_referer( 'mif-bpc-dialogues-messages-items-more-nonce' );

        $thread_id = (int) $_POST['tid'];
        $page = (int) $_POST['page'];
        echo json_encode( array( 'messages_more' => $this->get_messages_page( $thread_id, $page ) ) );

        wp_die();
    }


    //
    // Отправка сообщения
    //

    function ajax_messages_send_helper()
    {
        check_ajax_referer( 'mif-bpc-dialogues-messages-send-nonce' );

        $thread_id = (int) $_POST['thread_id'];
        $last_message_id = (int) $_POST['last_message_id'];
        $threads_update_timestamp = (int) $_POST['threads_update_timestamp'];
        $message = esc_html( $_POST['message'] );
        // $page = (int) $_POST['page'];
        // echo json_encode( array( 'messages_more' => $this->get_messages_page( $thread_id, $page ) ) );

        $res = $this->send( $message, $thread_id );

        if ( $res ) {

            // Получить сообщения, начная с $last_message_id
            $messages = $this->get_messages_data( $thread_id, 0, $last_message_id );

            // Оформить сообщения в виде HTML-блоков 
            $arr = array();
            foreach ( (array) $messages as $message ) {
                $arr[$message->id] = $this->message_item( $message );
            }

            $arr = array_reverse( $arr, true );

            echo json_encode( array( 
                                    'messages_header_update' => $this->get_messages_header( $thread_id ),
                                    'messages_update' => $arr,
                                    'threads_update' => $this->get_threads_update( $threads_update_timestamp ),
                                    // 'threads_update' => $threads_update_timestamp,
                                    'threads_update_timestamp' => time(),
                                    ) );

        }

        wp_die();
    }



    //
    // Отправить сообщение
    //

    function send( $message, $thread_id = NULL, $sender_id = NULL, $subject = '' )
    {
        global $bp, $wpdb;
        
        if ( $sender_id == NULL ) $sender_id = bp_loggedin_user_id();

        // Получить последнее сообщение в диалоге

        $sql = $wpdb->prepare( "SELECT * FROM {$bp->messages->table_name_messages} WHERE thread_id = %d ORDER BY date_sent DESC LIMIT 1", $thread_id );
        $result = $wpdb->get_row( $sql );
        $message_id = $result->id;

        // Обновлять существующую, или добавлять новую?
        
        $update_flag = false;
        
        if ( $result && $result->sender_id == $sender_id ) {

            // $last_message_data = get_user_meta( $sender_id, 'mif_bpc_last_message_data', true );
            $last_updated = bp_messages_get_meta( $message_id, 'last_updated' );
            $outdate_time = apply_filters( 'mif_bpc_dialogues_outdate_time', $this->message_outdate_time );

            // if ( isset( $last_message_data ) && 
            //         $last_message_data['message_id'] == $result->id && 
            //         timestamp_to_now( $last_message_data['timestamp'] ) < $outdate_time ) $update_flag = true;
        
            if ( isset( $last_updated ) && timestamp_to_now( $last_updated ) < $outdate_time ) $update_flag = true;

        }


        if ( $update_flag ) {

            // Обновить существующую
            $message = $result->message . "\n" . $message;
            // $sql = $wpdb->prepare( "UPDATE {$bp->messages->table_name_messages} SET message = %s WHERE id = %d", $message, $result->id );
            $sql = $wpdb->prepare( "UPDATE {$bp->messages->table_name_messages} SET message = %s WHERE id = %d", $message, $message_id );
            if ( ! $wpdb->query( $sql ) ) return false;

            // update_user_meta( $sender_id, 'mif_bpc_last_message_data', array( 'message_id' => $result->id, 'timestamp' => time() ) );

        } else {

            // Добавить новую
            $date_sent = bp_core_current_time();
            $sql = $wpdb->prepare( "INSERT INTO {$bp->messages->table_name_messages} ( thread_id, sender_id, subject, message, date_sent ) VALUES ( %d, %d, %s, %s, %s )", $thread_id, $sender_id, $subject, $message, $date_sent );
            if ( ! $wpdb->query( $sql ) ) return false;

            $message_id = $wpdb->get_var( "SELECT LAST_INSERT_ID()" );
            // update_user_meta( $sender_id, 'mif_bpc_last_message_data', array( 'message_id' => $message_id, 'timestamp' => time() ) );

        }

        bp_messages_update_meta( $message_id, 'last_updated', time() );

        return true;
    }








}



// 
// Выводит адрес страницы диалогов пользователя
// 

function mif_bpc_the_dialogues_url()
{
    global $bp;
    echo $bp->displayed_user->domain . $bp->messages->slug . '/dialogues/';
}


// 
// Выводит список диалогов
// 

function mif_bpc_the_dialogues_threads()
{
    global $mif_bpc_dialogues;

    echo '<div class="thread-scroller-wrap scroller-wrap"><div class="thread-scroller scroller"><div class="thread-scroller-container scroller-container">';
    echo $mif_bpc_dialogues->get_threads_items();
    echo '</div><div class="thread-scroller__bar scroller__bar"></div></div></div>';

    $threads_update_timestamp = time();
    echo '<input type="hidden" name="threads_update_timestamp" id="threads_update_timestamp" value="' . $threads_update_timestamp . '">';

}


// 
// Выводит конкретный диалог
// 

function mif_bpc_the_dialogues_dialog()
{
    global $mif_bpc_dialogues;

    // echo $mif_bpc_dialogues->get_messages_page( 7590, 0 );
    // $mif_bpc_dialogues->get_messages_header( 7682 );

    // $mif_bpc_dialogues->send( 'Текcт', 7690 );
    // echo $mif_bpc_dialogues->get_last_message_id( 7689 );

    // echo '2';
}





?>