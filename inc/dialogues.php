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
        add_action( 'wp_ajax_mif-bpc-dialogues-refresh', array( $this, 'ajax_dialogues_refresh' ) );


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
        $message = array_pop( explode( "\n", $message ) );
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
        $time_since = apply_filters( 'mif_bpc_dialogues_thread_item_time_since', $this->time_since( $thread['date_sent'] ) );
        $message_excerpt = $this->get_message_excerpt( $thread['message'] );
        $nonce = wp_create_nonce( 'mif-bpc-dialogues-thread-nonce' );
        $unread_count = $thread['unread_count'];
        // p($avatar);

        $out = '';

        $out .= '<div class="thread-item" id="thread-item-' . $thread['thread_id'] . '" data-thread-id="' . $thread['thread_id'] . '" data-nonce="' . $nonce . '">';
        $out .= '<div>';
        $out .= '<span class="avatar">' . $avatar . '</span>';
        $out .= '<span class="content">';
        // $out .= '<span class="title">' . $title . '</span><br />';
        // $out .= '<span class="time-since">' . $time_since . '</span><br />';
        // $out .= $thread['unread_count'];
        if ($unread_count) $out .= '<span class="unread_count">' . $unread_count . '</span>';
        // $out .= '<span class="unread_count">' . $unread_count . '</span>';
        $out .= '<span class="title">' . $title . '</span>';
        $out .= '<div><span class="time-since">' . $time_since . '</span></div>';
        $out .= '<div><span class="message-excerpt">' . $message_excerpt . '</span></div>';
        $out .= '</span>';
        $out .= '</div>';
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

        // Получить сведения о диалогах (номер, дата, id последнего сообщения, количество непрочитанных сообщений)

        $search_sql = '';
        $user_id_sql = $wpdb->prepare( 'r.user_id = %d', $user_id );
        $pag_sql = $wpdb->prepare( "LIMIT %d, %d", intval( ( $page ) * $this->threads_on_page ), intval( $this->threads_on_page ) );

        if ( isset( $last_updated ) ) {

            $sql = array();
            $sql['select'] = 'SELECT DISTINCT m.thread_id';
            $sql['from']   = "FROM {$bp->messages->table_name_messages} m INNER JOIN {$bp->messages->table_name_meta} t ON m.id = t.message_id";
            $sql['where']  = $wpdb->prepare( "WHERE t.meta_key = 'last_updated' AND t.meta_value >= %d", $last_updated );
            $new_ids = $wpdb->get_col( implode( ' ', $sql ) );

            $only_news_sql = 'AND m.thread_id IN (' . implode( ',', $new_ids) . ')';
            $pag_sql = '';

        }

   		$sql = array();
		$sql['select'] = 'SELECT m.thread_id, MAX(m.date_sent) AS date_sent, MAX(m.id) AS message_id, r.unread_count';
		$sql['from']   = "FROM {$bp->messages->table_name_recipients} r INNER JOIN {$bp->messages->table_name_messages} m ON m.thread_id = r.thread_id";
		$sql['where']  = "WHERE r.is_deleted = 0 AND {$user_id_sql} {$only_news_sql} {$search_sql}";
		$sql['misc']   = "GROUP BY m.thread_id ORDER BY date_sent DESC {$pag_sql}";

        $threads = $wpdb->get_results( implode( ' ', $sql ) );

        $arr = array();
        $thread_ids = array();
        $message_ids = array();
        foreach ( (array) $threads as $thread ) {

            $thread_ids[] = (int) $thread->thread_id;
            $message_ids[] = (int) $thread->message_id;
            // $arr[(int) $thread->thread_id]['date_sent'] = strtotime( $thread->date_sent );
            $arr[(int) $thread->thread_id]['date_sent'] = $thread->date_sent;
            $arr[(int) $thread->thread_id]['thread_id'] = $thread->thread_id;
            $arr[(int) $thread->thread_id]['unread_count'] = $thread->unread_count;

        }


        // Для каждого диалога из списка узнать получателей (кроме текущего пользователя)

        $where_sql = $wpdb->prepare( 'r.user_id <> (%d)', $user_id );

		$sql = array();
		$sql['select'] = 'SELECT r.thread_id, GROUP_CONCAT(DISTINCT r.user_id) AS user_ids';
		$sql['from']   = "FROM {$bp->messages->table_name_recipients} r";
		// $sql['where']  = 'WHERE r.thread_id IN (' . implode( ',', $thread_ids ) . ') AND is_deleted = 0 AND ' . $where_sql;
		$sql['where']  = 'WHERE r.thread_id IN (' . implode( ',', $thread_ids ) . ') AND ' . $where_sql;
		$sql['misc']   = "GROUP BY r.thread_id";

        $threads = $wpdb->get_results( implode( ' ', $sql ) );

        foreach ( (array) $threads as $thread ) $arr[(int) $thread->thread_id]['user_ids'] = explode( ',', $thread->user_ids );
        
        // Для каждого диалога из списка узнать автора, тему и начало последнего сообщения

		$sql = array();
		// $sql['select'] = 'SELECT m.thread_id, sender_id, subject, LEFT(m.message,100) AS message';
		$sql['select'] = 'SELECT thread_id, sender_id, subject, message';
		$sql['from']   = "FROM {$bp->messages->table_name_messages}";
		$sql['where']  = 'WHERE id IN (' . implode( ',', $message_ids ) . ')';

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

        $new_message_ids = $this->get_new_message_ids( $thread_id );
        foreach ( (array) $messages as $key => $message ) if ( in_array( $message->id, $new_message_ids) ) $messages[$key]->new = true;

        return apply_filters( 'mif_bpc_dialogues_get_messages_data', $messages, $thread_id, $page, $last_message_id );
    }


    // 
    // Получить номера новых для пользователя сообщений
    // 
    
    function get_new_message_ids( $thread_id = NULL, $user_id = NULL )
    {
        global $bp, $wpdb;
        
        if ( $thread_id == NULL ) return false;
        if ( $user_id == NULL ) $user_id = bp_loggedin_user_id();

        $sql = $wpdb->prepare( "SELECT unread_count FROM {$bp->messages->table_name_recipients} WHERE thread_id = %d AND user_id = %d", $thread_id, $user_id );
        $unread_count = (int) $wpdb->get_var( $sql );

        $arr = array();
        
        if ( $unread_count ) {

            $sql = $wpdb->prepare( "SELECT id FROM {$bp->messages->table_name_messages} WHERE thread_id = %d ORDER BY date_sent DESC LIMIT %d", $thread_id, $unread_count );
            $arr = $wpdb->get_col( $sql );

        }


// file_put_contents('/tmp/log.txt', print_r($arr, true));

        return apply_filters( 'mif_bpc_dialogues_get_new_message_ids', $arr, $thread_id, $user_id );

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
    // Форматирует время сообщений
    // 
    
    function time_since( $time )
    {

        $month = array( 
            '01' => __( 'января', 'mif-bp-customizer' ),
            '02' => __( 'февраля', 'mif-bp-customizer' ),
            '03' => __( 'марта', 'mif-bp-customizer' ),
            '04' => __( 'апреля', 'mif-bp-customizer' ),
            '05' => __( 'мая', 'mif-bp-customizer' ),
            '06' => __( 'июня', 'mif-bp-customizer' ),
            '07' => __( 'июля', 'mif-bp-customizer' ),
            '08' => __( 'августа', 'mif-bp-customizer' ),
            '09' => __( 'сентября', 'mif-bp-customizer' ),
            '10' => __( 'октября', 'mif-bp-customizer' ),
            '11' => __( 'ноября', 'mif-bp-customizer' ),
            '12' => __( 'декабря', 'mif-bp-customizer' ),
        );

        $out = '';
        $now = date( 'Y-m-d H:i:s' );
        $yesterday = date( 'Y-m-d H:i:s', time() - 86400 );

        if ( get_date_from_gmt( $time, 'Y-m-d' ) == get_date_from_gmt( $now, 'Y-m-d' ) ) {

            // Если сегодня, то вывести время и минуты
            $out = get_date_from_gmt( $time, 'H:i' );

        } elseif ( get_date_from_gmt( $time, 'Y-m-d' ) == get_date_from_gmt( $yesterday, 'Y-m-d' ) ) {

            // Если вчера, то вывести время, минуты и сообщение, что это вчера
            $out = get_date_from_gmt( $time, 'H:i' ) . ', ' . __( 'вчера', 'mif-bp-customizer' );

        } elseif ( get_date_from_gmt( $time, 'Y' ) == get_date_from_gmt( $now, 'Y' ) ) {

            // Если этом году, то вывести время, минуты, день и месяц
            $out = get_date_from_gmt( $time, 'H:i, j ' );
            $out .= $month[get_date_from_gmt( $time, 'm' )];
            
        } else {

            // В остальных случаях вывести время, минуты, день с ведущими нулями, номер месяца и год
            $out = get_date_from_gmt( $time, 'H:i, j ' );
            $out .= $month[get_date_from_gmt( $time, 'm' )];
            $out .= get_date_from_gmt( $time, ' Y ' ) . __( 'года', 'mif-bp-customizer' );
            $out = get_date_from_gmt( $time, 'H:i, d.m.Y' );
            
        }
            



        // return date( 'H:i', $timestamp );
        // return $now - $timestamp;
        return $out;
    }


    //
    // Получить HTML-блок сообщения
    //

    function message_item( $message = NULL )
    {
        if ( $message == NULL ) return;

        $url = bp_core_get_user_domain( $message->sender_id );

        $avatar_size = apply_filters( 'mif_bpc_dialogues_avatar_message_size', $this->avatar_message_size );
        $avatar = '<a href="' . $url . '">' . get_avatar( $message->sender_id, $avatar_size ) . '</a>';
        $title = '<a href="' . $url . '">' . $this->get_username( $message->sender_id ) . '</a>';
        $time_since = apply_filters( 'mif_bpc_dialogues_message_item_time_since', $this->time_since( $message->date_sent ) );
        $message_message = apply_filters( 'mif_bpc_dialogues_message_item_message', $message->message );
        $new = ( $message->new ) ? ' new' : '';
        $attach = bp_messages_get_meta( $message->id, 'attach' );

        $out = '';

        $out .= '<div class="message-item' . $new . '" id="message-' . $message->id . '" data-message-id="' . $message->id . '" data-sent="' . $message->date_sent . '">';
        $out .= '<div class="avatar">' . $avatar . '</div>';
        $out .= '<div class="content">';
        $out .= '<span class="title">' . $title . '</span> ';
        $out .= '<span class="time-since">' . $time_since . '</span>';
        $out .= '<span class="message">' . $message_message . '</span>';
        $out .= $this->attach( $attach );
        $out .= '</div>';
        $out .= '</div>';

        return apply_filters( 'mif_bpc_dialogues_message_item', $out, $message );
    }



    //
    // Сформировать ссылку на прикрепленный файл
    //

    function attach( $attach )
    {
        if ( empty( $attach ) ) return;

        // $folder = wp_upload_dir();
        // $url = $folder['baseurl'] . $attach;

        $arr = explode( '/', $attach );
        $name = array_pop( $arr );

        $arr = explode( '.', $attach );
        $type = array_pop( $arr );

        $icon = get_file_icon( $type );

        $out = '';
        $out .= '<span class="clearfix attach ' .  $type . '">';
        // $out .= '<a href="' . $url . '" class="icon">' . $icon . '</a>';
        // $out .= '<a href="' . $url . '" class="name">' . $name . '</a>';
        $out .= '<a href="' . $attach . '" target="blank"><span class="icon">' . $icon . '</span><span class="name">' . $name . '</span></a>';
        $out .= '</span>';

        return apply_filters( 'mif_bpc_dialogues_attach', $out, $attach );
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

        if ( $page === 0 ) $this->mark_as_read( $thread_id );

        if ( empty( $messages ) ) return false;

        // Оформить сообщения в виде HTML-блоков 
        $arr = array();
        foreach ( (array) $messages as $message ) $arr[] = $this->message_item( $message );

        $page ++;
        $nonce = wp_create_nonce( 'mif-bpc-dialogues-messages-items-more-nonce' );
        $arr[] = '<div class="message-item loader ajax-ready" data-page="' . $page . '" data-nonce="' . $nonce . '" data-tid="' . $thread_id . '"></div>';

        $arr = array_reverse( $arr );

        if ( $msg = $this->is_alone( $thread_id ) ) $arr[] = '<div class="message-item alone"><span>' . $msg . '</span></div>';

        return apply_filters( 'mif_bpc_dialogues_get_messages_page', implode( "\n", $arr ), $arr, $page, $thread_id );
    }


    //
    // Получить заголовок диалога
    //

    function is_alone( $thread_id = NULL, $user_id = NULL )
    {
        global $bp, $wpdb;

        if ( $thread_id == NULL ) return false;
        if ( $user_id == NULL ) $user_id = bp_loggedin_user_id();

        $sql = $wpdb->prepare( "SELECT user_id, is_deleted FROM {$bp->messages->table_name_recipients} WHERE thread_id = %d AND user_id <> %d", $thread_id, $user_id );
        $results = $wpdb->get_results( $sql );

        $present = false;
        foreach ( (array) $results as $result ) if ( $result->is_deleted == 0 ) $present = true;

        $msg = '';
        if ( ! $present && count( $results ) == 0 ) $msg = __( 'Собеседники не найдены', 'mif-bp-customizer' );
        if ( ! $present && count( $results ) == 1 ) $msg = __( 'Пользователь покинул диалог', 'mif-bp-customizer' );
        if ( ! $present && count( $results ) > 1 ) $msg = __( 'Все пользователи покинули диалог', 'mif-bp-customizer' );

        return apply_filters( 'mif_bpc_dialogues_is_alone', $msg, $thread_id, $user_id );
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
            if ( $user_id ) {

                $last_activity = bp_get_last_activity( $user_id );
                $header .= ' <span class="time-since">' . $last_activity . '</span>';

            }

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

            $arr = $this->get_messages_items( $thread_id, $last_message_id );

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
    // Обновление страницы диалогов
    //

    function ajax_dialogues_refresh()
    {
        check_ajax_referer( 'mif-bpc-dialogues-refresh-nonce' );

        $thread_id = (int) $_POST['thread_id'];
        $last_message_id = (int) $_POST['last_message_id'];
        $threads_update_timestamp = (int) $_POST['threads_update_timestamp'];

        // // Получить сообщения, начная с $last_message_id
        // $messages = $this->get_messages_data( $thread_id, 0, $last_message_id );

        // // Оформить сообщения в виде HTML-блоков 
        // $arr = array();
        // foreach ( (array) $messages as $message ) {
        //     $arr[$message->id] = $this->message_item( $message );
        // }

        // $arr = array_reverse( $arr, true );

        $messages_header_update = $this->get_messages_header( $thread_id );
        $messages_update = $this->get_messages_items( $thread_id, $last_message_id );
        $threads_update = $this->get_threads_update( $threads_update_timestamp );

        $arr = array();

        if ( $messages_header_update ) $arr['messages_header_update'] = $messages_header_update;
        if ( $messages_update ) $arr['messages_update'] = $messages_update;
        if ( $threads_update ) $arr['threads_update'] = $threads_update;

        $arr['threads_update_timestamp'] = time();

        echo json_encode( $arr );

        // echo json_encode( array( 
        //                         'messages_header_update' => $this->get_messages_header( $thread_id ),
        //                         'messages_update' => $this->get_messages_items( $thread_id, $last_message_id ),
        //                         'threads_update' => $this->get_threads_update( $threads_update_timestamp ),
        //                         'threads_update_timestamp' => time(),
        //                         ) );

        // echo json_encode( $threads_update );

        wp_die();
    }



    //
    // Получить массив HTML-блоков сообщений
    //

    function get_messages_items( $thread_id, $last_message_id )
    {

        // Получить сообщения, начная с $last_message_id
        $messages = $this->get_messages_data( $thread_id, 0, $last_message_id );

        if ( empty( $messages ) ) return false;

        // Оформить сообщения в виде HTML-блоков 
        $arr = array();
        foreach ( (array) $messages as $message ) {
            $arr[$message->id] = $this->message_item( $message );
        }

        $arr = array_reverse( $arr, true );

        return apply_filters( 'mif_bpc_dialogues_get_messages_items', $arr, $thread_id, $last_message_id );
    }


    //
    // Отправить сообщение
    //

    function send( $message, $thread_id = NULL, $sender_id = NULL, $subject = 'default' )
    {
        global $bp, $wpdb;
        
        if ( $thread_id == NULL ) return false;
        if ( $sender_id == NULL ) $sender_id = bp_loggedin_user_id();

        // Получить последнее сообщение в диалоге

        $sql = $wpdb->prepare( "SELECT * FROM {$bp->messages->table_name_messages} WHERE thread_id = %d ORDER BY date_sent DESC LIMIT 1", $thread_id );
        $result = $wpdb->get_row( $sql );
        $message_id = $result->id;

        // Обновлять существующую, или добавлять новую?
        
        $update_flag = false;
        
        if ( $result && $result->sender_id == $sender_id ) {

            $last_updated = bp_messages_get_meta( $message_id, 'last_updated' );
            $outdate_time = apply_filters( 'mif_bpc_dialogues_outdate_time', $this->message_outdate_time );
        
            if ( isset( $last_updated ) && timestamp_to_now( $last_updated ) < $outdate_time ) $update_flag = true;

        }

        // Сохранить в базе новое сообщение

        if ( $update_flag ) {

            // Обновить существующую
            $message = $result->message . "\n" . $message;
            $sql = $wpdb->prepare( "UPDATE {$bp->messages->table_name_messages} SET message = %s WHERE id = %d", $message, $message_id );
            if ( ! $wpdb->query( $sql ) ) return false;

        } else {

            // Добавить новую
            $date_sent = bp_core_current_time();
            $sql = $wpdb->prepare( "INSERT INTO {$bp->messages->table_name_messages} ( thread_id, sender_id, subject, message, date_sent ) VALUES ( %d, %d, %s, %s, %s )", $thread_id, $sender_id, $subject, $message, $date_sent );
            if ( ! $wpdb->query( $sql ) ) return false;

            $message_id = $wpdb->get_var( "SELECT LAST_INSERT_ID()" );

        }

        // Обновить метку последнего обновления

        $now = time();
        bp_messages_update_meta( $message_id, 'last_updated', $now );

        // Удалить старые метки, которые уже не нужны
        
        $outdate_time = apply_filters( 'mif_bpc_dialogues_outdate_time', $this->message_outdate_time );
        $sql = $wpdb->prepare( "DELETE FROM {$bp->messages->table_name_meta} WHERE meta_key = 'last_updated' AND meta_value < %d", $now - $outdate_time );
        $wpdb->query( $sql );

        // Обновить для других пользователей информацию о непрочитанных

        if ( $update_flag ) {

            $sql = $wpdb->prepare( "UPDATE {$bp->messages->table_name_recipients} SET unread_count = 1 WHERE unread_count = 0 AND thread_id = %d AND user_id <> %d", $thread_id, $sender_id );
            $wpdb->query( $sql );

        } else {

            $sql = $wpdb->prepare( "UPDATE {$bp->messages->table_name_recipients} SET unread_count = unread_count + 1 WHERE thread_id = %d AND user_id <> %d", $thread_id, $sender_id );
            $wpdb->query( $sql );

        }

        // Отметить для себя, что всё прочитано

        $this->mark_as_read( $thread_id, $sender_id );

        // $sql = $wpdb->prepare( "UPDATE {$bp->messages->table_name_recipients} SET unread_count = 0 WHERE thread_id = %d AND user_id = %d", $thread_id, $sender_id );
        // $wpdb->query( $sql );

        // Узнать id получателей сообщения и отправить им уведомление через эхо-сервер

        $sql = $wpdb->prepare( "SELECT user_id FROM {$bp->messages->table_name_recipients} WHERE thread_id = %d AND user_id <> %d", $thread_id, $sender_id );
        // $sql = $wpdb->prepare( "SELECT user_id FROM {$bp->messages->table_name_recipients} WHERE thread_id = %d", $thread_id, $sender_id );
        $recipients = $wpdb->get_col( $sql );

        do_action( 'mif_bpc_dialogues_after_send', $recipients, $thread_id, $sender_id, $message );

// file_put_contents('/tmp/log.txt', print_r( $recipients, true));        

        return true;
    }


    //
    // Отметить диалог как прочитанный
    //

    function mark_as_read( $thread_id = NULL, $user_id = NULL )
    {
        global $bp, $wpdb;

        if ( $thread_id == NULL ) return;
        if ( $user_id == NULL ) $user_id = bp_loggedin_user_id();

        $sql = $wpdb->prepare( "UPDATE {$bp->messages->table_name_recipients} SET unread_count = 0 WHERE thread_id = %d AND user_id = %d", $thread_id, $user_id );
        $ret = $wpdb->query( $sql );

        return $ret;
    }


    //
    // Склеивание диалогов с одинаковыми пользователями в один диалог
    //

    function threads_joining( $user_id = NULL )
    {
        global $bp, $wpdb;

        if ( $user_id == NULL ) $user_id = bp_loggedin_user_id();

        // Выбрать ID всех диалогов пользователя
        
        $sql = $wpdb->prepare( "SELECT thread_id FROM {$bp->messages->table_name_recipients} WHERE user_id = %d AND is_deleted = 0", $user_id );
        $threads_ids = $wpdb->get_col( $sql );

        $arr = array();
        foreach ( (array) $threads_ids as $thread_id ) {

            // Для каждого диалога - получить список собеседников пользователя
            
            // $sql = $wpdb->prepare( "SELECT DISTINCT user_id FROM {$bp->messages->table_name_recipients} WHERE thread_id = %d AND is_deleted = 0 AND user_id <> %d", $thread_id, $user_id );
            $sql = $wpdb->prepare( "SELECT DISTINCT user_id, is_deleted FROM {$bp->messages->table_name_recipients} WHERE thread_id = %d AND user_id <> %d", $thread_id, $user_id );
            $user_ids = $wpdb->get_results( $sql );
            
            // Если собеседник только один, то запомнить номер диалога

            if ( count( $user_ids ) == 1 ) {
                
                $key = $user_ids[0]->user_id . ':' . $user_ids[0]->is_deleted;
                $arr[$key][] = $thread_id;

            }
            
        }

        foreach ( (array) $arr as $threads_arr ) {

            // Если с собеседником диалог только один, то идти дальше
            if ( count( $threads_arr ) == 1 ) continue;

            $thread_id = array_pop( $threads_arr );
            $threads_list = implode( ',', $threads_arr );

            // Обновить номера диалогов в таблице сообщений
            $sql = $wpdb->prepare( "UPDATE {$bp->messages->table_name_messages} SET thread_id = %d WHERE thread_id IN ({$threads_list})", $thread_id );
            if ( $wpdb->query( $sql ) ) {

                // // Если обновление прошло успешно, то удалить старые номера диалогов в таблице диалогов
                $sql = $wpdb->prepare( "DELETE FROM {$bp->messages->table_name_recipients} WHERE thread_id IN ({$threads_list})", $thread_id );
                $wpdb->query( $sql );
                
            }

        }

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

    $nonce = wp_create_nonce( 'mif-bpc-dialogues-refresh-nonce' );
    echo '<input type="hidden" name="dialogues_refresh_nonce" id="dialogues_refresh_nonce" value="' . $nonce . '">';

}


// 
// Выводит конкретный диалог
// 

function mif_bpc_the_dialogues_dialog()
{
    global $bp, $mif_bpc_dialogues;

    // mif_bpc_msgat_convert();

// p($bp->messages);

    // echo $mif_bpc_dialogues->get_messages_page( 7590, 0 );
    // $mif_bpc_dialogues->get_messages_header( 7682 );

    // $mif_bpc_dialogues->threads_joining();
    // echo $mif_bpc_dialogues->get_last_message_id( 7689 );

    // echo '2';
}


// 
// Корректировка прикрепленных файлов (конвертация данных плагина BuddyPress Message Attachment)
// Запустить несколько раз при настройке плагина
// 

function mif_bpc_msgat_convert()
{
    global $bp, $wpdb;

    $posts = get_posts( array(
            'numberposts' => 250,
        	'post_type'   => 'messageattachements',
    ) );

    foreach ( $posts as $post ) {

        $meta = get_post_meta( $post->ID, 'bp_msgat_message_id', true );
        $arr = explode( '=', $meta );
        // $message_id = $arr[0];

        $sql = $wpdb->prepare( "SELECT id FROM {$bp->messages->table_name_messages} WHERE thread_id = %d AND date_sent = %s", $arr[0], $arr[1] );
        $message_id = $wpdb->get_var( $sql );

        if ( $message_id ) {
            
            if ( bp_messages_update_meta( $message_id, 'attach', $post->post_excerpt ) ) {

                wp_delete_post( $post->ID );
                echo $post->ID . ', ';

            };
        }
    }
}


// //
// // Склеивание диалогов с одинаковыми пользователями в один диалог
// //

// function mif_bpc_dialogues_joining()
// {


// }

?>