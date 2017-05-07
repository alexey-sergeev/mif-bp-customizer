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

    //
    // Размер аватарки
    //

    public $avatar_thread_size = 50;
    public $avatar_message_size = 40;
    public $avatar_member_size = 25;

    //
    // Диалогов на одной странице в списке диалогов
    //

    public $threads_on_page = 10;

    //
    // Сообщений на одной странице сообщений
    //

    public $messages_on_page = 10;

    //
    // Пользователей на одной странице сообщений
    //

    public $members_on_page = 20;

    //
    // Время устаревания сообщения (секунд). Используется для определения - обновлять текущее сообщение, или создавать новое
    //

    public $message_outdate_time = 60;



    function __construct()
    {
       
        // Страница диалогов
        add_action( 'bp_activity_setup_nav', array( $this, 'dialogues_nav' ) );
        add_action( 'wp_enqueue_scripts', array( $this, 'load_js_helper' ) );            				

        add_action( 'wp_ajax_mif-bpc-dialogues-thread-items-more', array( $this, 'ajax_thread_more_helper' ) );
        add_action( 'wp_ajax_mif-bpc-dialogues-thread-search', array( $this, 'ajax_thread_search_helper' ) );
        add_action( 'wp_ajax_mif-bpc-dialogues-member-items-more', array( $this, 'ajax_member_more_helper' ) );
        add_action( 'wp_ajax_mif-bpc-dialogues-member-search', array( $this, 'ajax_member_search_helper' ) );
        add_action( 'wp_ajax_mif-bpc-dialogues-messages', array( $this, 'ajax_messages_helper' ) );
        add_action( 'wp_ajax_mif-bpc-dialogues-messages-items-more', array( $this, 'ajax_messages_more_helper' ) );
        add_action( 'wp_ajax_mif-bpc-dialogues-messages-send', array( $this, 'ajax_messages_send_helper' ) );
        add_action( 'wp_ajax_mif-bpc-dialogues-compose-send', array( $this, 'ajax_compose_send_helper' ) );
        add_action( 'wp_ajax_mif-bpc-dialogues-refresh', array( $this, 'ajax_dialogues_refresh' ) );
        add_action( 'wp_ajax_mif-bpc-dialogues-join', array( $this, 'ajax_dialogues_join' ) );
        add_action( 'wp_ajax_mif-bpc-dialogues-compose-form', array( $this, 'ajax_dialogues_compose_form' ) );
        add_action( 'wp_ajax_mif-bpc-message-remove', array( $this, 'ajax_message_remove' ) );
        add_action( 'wp_ajax_mif-bpc-thread-remove-window', array( $this, 'ajax_thread_remove_window' ) );
        add_action( 'wp_ajax_mif-bpc-thread-remove', array( $this, 'ajax_thread_remove' ) );

        // Обработка текста сообщений
        add_filter( 'mif_bpc_dialogues_message_item_message', array( $this, 'autop' ) );
        add_filter( 'mif_bpc_dialogues_message_item_message', 'stripslashes_deep' );

        // Стандартные фильтры обработки текста сообщений
        // add_filter( 'mif_bpc_dialogues_message_item_message', 'wp_filter_kses', 1 );
        // add_filter( 'mif_bpc_dialogues_message_item_message', 'force_balance_tags', 1 );
        // add_filter( 'mif_bpc_dialogues_message_item_message', 'wptexturize' );
        // add_filter( 'mif_bpc_dialogues_message_item_message', 'convert_chars' );
        // add_filter( 'mif_bpc_dialogues_message_item_message', 'wpautop' );

    }


    // 
    // Страница диалогов
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
        // Плагин красивого скроллинга        
        wp_enqueue_script( 'mif_bpc_baron_core', plugins_url( '../js/mif-bpc-baron.js', __FILE__ ) );
        wp_enqueue_script( 'mif_bpc_autosize', plugins_url( '../js/plugins/autosize.js', __FILE__ ) );

        wp_enqueue_script( 'mif_bpc_dialogues_helper', plugins_url( '../js/dialogues.js', __FILE__ ) );
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
        $subject = $thread['subject'];

        $arr = array();

        if ( count( $sender_ids ) > 3 ) {

                $arr[] = $this->get_username( $thread['sender_id'], $links );
                $sender_ids_without_sender_id = array_merge( $sender_ids, array( $thread['sender_id'] ) );
                $arr[] = $this->get_username( $sender_ids_without_sender_id[0], $links );

                $title = implode( ', ', $arr );
                $title .= ' ' . sprintf( __( 'и другие (всего %s)', 'mif-bp-customizer' ), number_format_i18n( count( $sender_ids ) ) );

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


    //
    // Начало последней фразы сообщения
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

        $avatar = $this->get_sender_avatar( $thread );
        $title = $this->get_thread_title( $thread );
        $time_since = apply_filters( 'mif_bpc_dialogues_thread_item_time_since', $this->time_since( $thread['date_sent'] ) );
        $message_excerpt = $this->get_message_excerpt( $thread['message'] );
        $unread_count = $thread['unread_count'];
        $class = ( $unread_count ) ? ' unread' : '';

        $out = '';

        $out .= '<div class="thread-item' . $class . '" id="thread-item-' . $thread['thread_id'] . '" data-thread-id="' . $thread['thread_id'] . '">';
        $out .= '<div>';
        $out .= '<span class="avatar">' . $avatar . '</span>';
        $out .= '<span class="content">';
        if ( $unread_count ) $out .= '<span class="unread_count">' . $unread_count . '</span>';
        $out .= '<div class="remove"><div class="custom-button"><a href="' . $this->get_dialogues_url() . '" class="button thread-remove" title="' . __( 'Удалить', 'mif-bp-customizer' ) . '"><i class="fa fa-times" aria-hidden="true"></i></a></div></div>';
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
        $arr[] = '<div class="thread-item loader ajax-ready" data-mode="threads" data-page="' . $page . '" data-nonce="' . $nonce . '"></div>';

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

        return apply_filters( 'mif_bpc_dialogues_get_threads_update', $arr, $user_id );
    }


    //
    // Загрузка продолжения списка диалогов
    //

    function ajax_thread_more_helper()
    {
        check_ajax_referer( 'mif-bpc-dialogues-thread-items-more-nonce' );

        $page = (int) $_POST['page'];
        
        $arr = array( 'threads_more' => $this->get_threads_items( $page ) );
        $arr = apply_filters( 'mif_bpc_dialogues_ajax_thread_more_helper', $arr, $page );

        echo json_encode( $arr );

        wp_die();
    }


    //
    // Поиск диалогов
    //

    function ajax_thread_search_helper()
    {
        check_ajax_referer( 'mif-bpc-dialogues-search-nonce' );

        $arr = array( 'threads_window_update' => $this->get_threads_items() );
        $arr = apply_filters( 'mif_bpc_dialogues_ajax_thread_search_helper', $arr );

        echo json_encode( $arr );

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

        // Условие для обновления
        
        if ( isset( $last_updated ) ) {

            $sql = array();
            $sql['select'] = 'SELECT DISTINCT m.thread_id';
            $sql['from']   = "FROM {$bp->messages->table_name_messages} m INNER JOIN {$bp->messages->table_name_meta} t ON m.id=t.message_id";
            $sql['where']  = $wpdb->prepare( "WHERE t.meta_key = 'last_updated' AND t.meta_value >= %d", $last_updated );
            // $sql['where']  = $wpdb->prepare( "WHERE t.meta_key='last_updated' AND t.meta_value >= %d AND m.sender_id=%d", $last_updated, $user_id );
            $new_ids = $wpdb->get_col( implode( ' ', $sql ) );

            if ( ! empty( $new_ids ) ) {

                $only_news_sql = 'AND m.thread_id IN (' . implode( ',', $new_ids) . ')';
                $pag_sql = '';

            }
        }

        // Условие для поиска

        if ( isset( $_POST['s'] ) ) {

            // Выбрать собеседников всех диалогов

            $recipients = $wpdb->get_col( $wpdb->prepare( "SELECT DISTINCT r2.user_id FROM {$bp->messages->table_name_recipients} AS r1 INNER JOIN {$bp->messages->table_name_recipients} AS r2 ON r1.thread_id = r2.thread_id WHERE r1.user_id=%d AND r1.is_deleted=0 AND r2.user_id<>%d AND r2.is_deleted=0 ORDER BY r2.user_id", $user_id, $user_id ) );

            // Отобрать только тех, кто подходит по поиску

            $recipients_search_result = new BP_User_Query( array( 'include' => $recipients, 'search_terms' => $_POST['s'] ));

            if ( ! empty( $recipients_search_result->user_ids ) ) {
                
                // Выбрать номера диалогов пользователя и тех людей, которые подошли по поиску

                $resipients_ids = implode( ',', $recipients_search_result->user_ids );
                $threads_arr = $wpdb->get_col( $wpdb->prepare( "SELECT DISTINCT r1.thread_id FROM {$bp->messages->table_name_recipients} AS r1 INNER JOIN {$bp->messages->table_name_recipients} AS r2 ON r1.thread_id = r2.thread_id WHERE r1.user_id=%d AND r1.is_deleted=0 AND r2.user_id IN ({$resipients_ids}) AND r2.is_deleted=0", $user_id ) );

                // Сформировать требование для поиска диалога

                $search_sql = ( $threads_arr ) ? "AND r.thread_id IN (" . implode( ',', $threads_arr ) . ")" : "AND 1=0";

            } else {

                $search_sql = "AND 1=0";

            }

        }


   		$sql = array();
		$sql['select'] = 'SELECT m.thread_id, MAX(m.date_sent) AS date_sent, MAX(m.id) AS message_id, r.unread_count';
		$sql['from']   = "FROM {$bp->messages->table_name_recipients} r INNER JOIN {$bp->messages->table_name_messages} m ON m.thread_id = r.thread_id";
		$sql['where']  = "WHERE r.is_deleted = 0 AND {$user_id_sql} {$only_news_sql} {$search_sql} AND m.id NOT IN (SELECT message_id FROM {$bp->messages->table_name_meta} WHERE meta_key = 'deleted' AND meta_value={$user_id})";
		$sql['misc']   = "GROUP BY m.thread_id ORDER BY date_sent DESC {$pag_sql}";

        $threads = $wpdb->get_results( implode( ' ', $sql ) );

        if ( empty( $threads) ) return array();

        $arr = array();
        $thread_ids = array();
        $message_ids = array();
        foreach ( (array) $threads as $thread ) {

            $thread_ids[] = (int) $thread->thread_id;
            $message_ids[] = (int) $thread->message_id;
            $arr[(int) $thread->thread_id]['date_sent'] = $thread->date_sent;
            $arr[(int) $thread->thread_id]['thread_id'] = $thread->thread_id;
            $arr[(int) $thread->thread_id]['unread_count'] = $thread->unread_count;

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
        $user_id = bp_loggedin_user_id();

        global $bp, $wpdb;

        // Выбрать страницу сообщений или всё с последнего обновления?

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
		$sql['where']  = "WHERE {$where_sql} AND id NOT IN (SELECT message_id FROM {$bp->messages->table_name_meta} AS mt INNER JOIN {$bp->messages->table_name_messages} AS ms ON mt.message_id = ms.id WHERE meta_key = 'deleted' AND meta_value={$user_id}  AND thread_id={$thread_id})";
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
        $avatar = '<a href="' . $url . '" target="blank">' . get_avatar( $message->sender_id, $avatar_size ) . '</a>';
        $title = '<a href="' . $url . '" target="blank">' . $this->get_username( $message->sender_id ) . '</a>';
        $time_since = apply_filters( 'mif_bpc_dialogues_message_item_time_since', $this->time_since( $message->date_sent ) );
        $message_message = apply_filters( 'mif_bpc_dialogues_message_item_message', $message->message );
        $new = ( $message->new ) ? ' new' : '';
        $attach = bp_messages_get_meta( $message->id, 'attach' );

        $out = '';

        $out .= '<div class="message-item' . $new . '" id="message-' . $message->id . '" data-message-id="' . $message->id . '" data-sent="' . $message->date_sent . '">';
        $out .= '<div class="avatar">' . $avatar . '</div>';
        $out .= '<div class="remove"><div class="custom-button"><a href="' . $this->get_dialogues_url() . '" class="button message-remove" title="' . __( 'Удалить', 'mif-bp-customizer' ) . '"><i class="fa fa-times" aria-hidden="true"></i></a></div></div>';
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

        $arr = explode( '/', $attach );
        $name = array_pop( $arr );

        $arr = explode( '.', $attach );
        $type = array_pop( $arr );

        $icon = get_file_icon( $type );

        $out = '';
        $out .= '<span class="clearfix attach ' .  $type . '">';
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

        if ( ! $this->is_access( $thread_id ) ) return false;

        // Получить нужную страницу сообщений

        $messages = $this->get_messages_data( $thread_id, $page );
        if ( $page === 0 ) $this->mark_as_read( $thread_id );

        if ( empty( $messages ) ) return false;

        // Оформить сообщения в виде HTML-блоков 

        $arr = array();
        foreach ( (array) $messages as $message ) $arr[] = $this->message_item( $message );

        $page ++;
        $nonce = wp_create_nonce( 'mif-bpc-dialogues-messages-items-more-nonce' );
        // $arr[] = '<div class="message-item loader ajax-ready" data-page="' . $page . '" data-nonce="' . $nonce . '" data-tid="' . $thread_id . '"><i class="fa fa-spinner fa-spin fa-fw"></i></div>';
        $arr[] = '<div class="message-item loader ajax-ready" data-page="' . $page . '" data-nonce="' . $nonce . '" data-tid="' . $thread_id . '"></div>';

        $arr = array_reverse( $arr );

        if ( $msg = $this->is_alone( $thread_id ) ) $arr[] = '<div class="message-item alone"><span>' . $msg . '</span></div>';

        return apply_filters( 'mif_bpc_dialogues_get_messages_page', implode( "\n", $arr ), $arr, $page, $thread_id );
    }


    //
    // Проверить, что пользователь имеет право просматривать сообщения
    //

    function is_access( $thread_id = NULL, $user_id = NULL )
    {
        global $bp, $wpdb;

        if ( $thread_id == NULL ) return false;
        if ( $user_id == NULL ) $user_id = bp_loggedin_user_id();

        $sql = $wpdb->prepare( "SELECT user_id FROM {$bp->messages->table_name_recipients} WHERE thread_id = %d AND user_id = %d AND is_deleted=0", $thread_id, $user_id );
        $user_id = $wpdb->get_var( $sql );

        $res = ( isset( $user_id ) ) ? true : false; 

        return apply_filters( 'mif_bpc_dialogues_is_alone', $res, $thread_id, $user_id );
    }


    //
    // Проверить, что пользователь одинок
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
		$sql['select'] = 'SELECT m.sender_id, GROUP_CONCAT(DISTINCT r.user_id) AS user_ids';
		$sql['from']   = "FROM {$bp->messages->table_name_recipients} r INNER JOIN {$bp->messages->table_name_messages} m ON m.thread_id = r.thread_id";
		$sql['where']  = "WHERE {$where_sql}";
		$sql['misc']   = "GROUP BY m.thread_id ORDER BY date_sent DESC";

        $thread_objects = $wpdb->get_results( implode( ' ', $sql ) );

        $thread['sender_id'] = $thread_objects[0]->sender_id;
        $thread['user_ids'] = explode( ',', $thread_objects[0]->user_ids );

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
        $url = $this->get_dialogues_url();

        $out = '';
        $out .= '<form>';
        $out .= '<table><tr>';
        $out .= '<td class="clip"><a href="' . $url . '" class="clip"><i class="fa fa-2x fa-paperclip" aria-hidden="true"></i></a></td>';
        $out .= '<td class="message"><textarea name="message" id="message" placeholder="' . __( 'Напишите сообщение...', 'mif-bp-customizer' ) . '" rows="1"></textarea></td>';
        $out .= '<td class="send"><div class="custom-button"><a href="' . $url . '" class="send button"><i class="fa fa-chevron-right" aria-hidden="true"></i></a></div></td>';
        $out .= '</tr></table>';
        $out .= wp_nonce_field( 'mif-bpc-dialogues-messages-send-nonce', 'nonce', true, false );
        $out .= '<input type="hidden" name="thread_id" id="thread_id" value="' . $thread_id . '">';
        $out .= '<input type="hidden" name="last_message_id" id="last_message_id" value="' . $last_message_id . '">';
        $out .= '</form>';

        return apply_filters( 'mif_bpc_dialogues_get_messages_form', $out, $thread_id );
    }


    // 
    // Выводит форму создания нового сообщения
    // 

    function get_compose_form()
    {
        $out = '';
        $out .= '<div>';
        $out .= '<div class="compose-wrap">';
        $out .= '<form>';
        $out .= '<div>' . __( 'Кому:', 'mif-bp-customizer' ) . '</div>';
        $out .= '<div class="recipients"></div>';
        $out .= '<div>' . __( 'Сообщение:', 'mif-bp-customizer' ) . '</div>';
        $out .= '<div class="textarea"><textarea name="message" id="message"></textarea></div>';
        $out .= '<div><label><input type="checkbox" value="on" name="email" id="email"> ' . __( 'Оповестить по почте', 'mif-bp-customizer' ) . '</label></div>';
        $out .= '<div><input type="submit" value="' . __( 'Отправить', 'mif-bp-customizer' ) . '"></div>';
        $out .= '<input type="hidden" name="nonce" id="nonce" value="' . wp_create_nonce( 'mif-bpc-dialogues-compose-send-nonce' ) . '">';
        $out .= '</form>';
        $out .= '</div>';
        $out .= '</div>';

        return apply_filters( 'mif_bpc_dialogues_get_compose_form', $out );
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

        $arr = array( 
                    'messages_page' => $out,
                    'messages_header' => $this->get_messages_header( $thread_id ),
                    'messages_form' => $this->get_messages_form( $thread_id ),
                    );
        $arr = apply_filters( 'mif_bpc_dialogues_ajax_messages_helper', $arr, $thread_id, $page );

        echo json_encode( $arr );


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

        $arr = array( 'messages_more' => $this->get_messages_page( $thread_id, $page ) );
        $arr = apply_filters( 'mif_bpc_dialogues_ajax_messages_more_helper', $arr, $thread_id, $page );

        echo json_encode( $arr );

        wp_die();
    }


    //
    // Отправка сообщения (форма диалога)
    //

    function ajax_messages_send_helper()
    {
        check_ajax_referer( 'mif-bpc-dialogues-messages-send-nonce' );

        $thread_id = (int) $_POST['thread_id'];
        $last_message_id = (int) $_POST['last_message_id'];
        $threads_update_timestamp = (int) $_POST['threads_update_timestamp'];
        $message = esc_html( $_POST['message'] );

        $res = $this->send( $message, $thread_id );

        if ( $res ) {

            $messages = $this->get_messages_items( $thread_id, $last_message_id );

            $arr = array( 
                        'messages_header_update' => $this->get_messages_header( $thread_id ),
                        'messages_update' => $messages,
                        'threads_update' => $this->get_threads_update( $threads_update_timestamp ),
                        'threads_update_timestamp' => time(),
                        );
            $arr = apply_filters( 'mif_bpc_dialogues_ajax_messages_send_helper', $arr, $thread_id, $message, $last_message_id, $threads_update_timestamp );

            echo json_encode( $arr );

        }

        wp_die();
    }


    //
    // Отправка сообщения (форма нового сообщения)
    //

    function ajax_compose_send_helper()
    {
        check_ajax_referer( 'mif-bpc-dialogues-compose-send-nonce' );

        $email_status = (int) $_POST['email'];
        $message = esc_html( $_POST['message'] );
        $subject = esc_html( $_POST['subject'] );
        $recipient_ids = (array) $_POST['recipient_ids'];

        // Получить чистый список получателей

        $recipient_clean_ids = array();
        foreach ( $recipient_ids as $recipient_id ) { 
            
            $recipient = get_user_by( 'ID', $recipient_id );
            if ( $recipient ) $recipient_clean_ids[] = $recipient->ID;
            
        }

        // Если получателей нет, то ничего и не делать
       
        if ( count( $recipient_clean_ids ) == 0 ) {

            $arr = array( 
                        'messages_header' => '<!-- empty -->',
                        'messages_page' => __( 'Ошибка. Указанные вами пользователи не существуют', 'mif-bp-customizer' ),
                        'threads_window' => $this->get_threads_items(),
                        );
            $arr = apply_filters( 'mif_bpc_dialogues_ajax_compose_send_helper_no_send', $arr, $message, $recipient_ids, $subject, $email_status );

            echo json_encode( $arr );

            wp_die();

        } 

        // Сохранить сообщение

        $thread_id = $this->get_thread_id( $recipient_clean_ids );
        $res = $this->send( $message, $thread_id, NULL, $subject, $email_status );

        if ( $res ) {

            $out .= '<div class="messages-scroller-wrap scroller-wrap"><div></div><div class="messages-scroller scroller"><div class="messages-scroller-container scroller-container">';
            $out .= $this->get_messages_page( $thread_id );
            $out .= '</div><div class="messages-scroller__bar scroller__bar"></div></div></div>';

            $arr = array( 
                        'messages_page' => $out,
                        'messages_header' => $this->get_messages_header( $thread_id ),
                        'messages_form' => $this->get_messages_form( $thread_id ),
                        'threads_window' => $this->get_threads_items(),
                        );
            $arr = apply_filters( 'mif_bpc_dialogues_ajax_compose_send_helper', $arr, $thread_id, $message, $recipient_ids, $subject, $email_status );

            echo json_encode( $arr );

        }

        // Действие после отправки сообщения (учитывать, что есть аналогичное в send).

        do_action( 'mif_bpc_dialogues_after_compose_send', $message, $recipient_clean_ids, $subject, $email_status );

        wp_die();
    }


    //
    // Получить идентификатор диалога для нового сообщения
    //

    function get_thread_id( $recipient_ids = array(), $sender_id = NULL )
    {
        global $bp, $wpdb;
        
        if ( $recipient_ids === array() ) return false;
        if ( $sender_id == NULL ) $sender_id = bp_loggedin_user_id();

        // Если получатель только один, то пытаться найти с ним диалог
        
        $thread_id = false;

        if ( count( $recipient_ids ) == 1 ) {

            $recipient_id = $recipient_ids[0];
            if ( $recipient_id == $sender_id ) return false;

            // Получить идентификатор активного диалога

            $sql = $wpdb->prepare( "SELECT thread_id FROM {$bp->messages->table_name_recipients} WHERE thread_id IN (SELECT DISTINCT r1.thread_id FROM {$bp->messages->table_name_recipients} AS r1 INNER JOIN `wp_bp_messages_recipients` AS r2 ON r1.thread_id = r2.thread_id WHERE r1.user_id=%d AND r2.user_id=%d AND r1.is_deleted=0 AND r2.is_deleted=0) GROUP BY thread_id HAVING count(DISTINCT user_id)=2 ORDER BY thread_id DESC LIMIT 1", $sender_id, $recipient_id );

            $thread_id = $wpdb->get_var( $sql );

        } 
        
        // Если активного диалога нет, то создать новый

        if ( empty( $thread_id ) ) {

            $thread_id = (int) $wpdb->get_var( "SELECT MAX(thread_id) FROM {$bp->messages->table_name_recipients}" ) + 1;

            $sql = $wpdb->prepare( "INSERT INTO {$bp->messages->table_name_recipients} ( thread_id, user_id, sender_only ) VALUES ( %d, %d, 1 )", $thread_id, $sender_id );
            if ( ! $wpdb->query( $sql ) ) return false;
            
            foreach ( (array) $recipient_ids as $recipient_id ) {

                $sql = $wpdb->prepare( "INSERT INTO {$bp->messages->table_name_recipients} ( thread_id, user_id, unread_count ) VALUES ( %d, %d, 0 )", $thread_id, $recipient_id );
                $wpdb->query( $sql );
            
            }

        }

        return apply_filters( 'mif_bpc_dialogues_get_get_thread_id', $thread_id, $recipient_ids, $sender_id );
    }


    //
    // Удаление сообщения
    //

    function ajax_message_remove()
    {
        check_ajax_referer( 'mif-bpc-dialogues-message-remove-nonce' );
        
        $message_id = (int) $_POST['message_id'];
        $threads_update_timestamp = (int) $_POST['threads_update_timestamp'];
        $user_id = bp_loggedin_user_id();

        if ( bp_messages_add_meta( $message_id, 'deleted', $user_id ) ) {

            $arr['threads_update'] = $this->get_threads_update( $threads_update_timestamp );
            $arr['threads_update_timestamp'] = time();

            $arr = apply_filters( 'mif_bpc_dialogues_ajax_message_remove', $arr, $message_id, $user_id, $threads_update_timestamp );

            echo json_encode( $arr );

        };

        wp_die();
    }


    //
    // Вывод окна удаления диалога
    //

    function ajax_thread_remove_window()
    {
        check_ajax_referer( 'mif-bpc-dialogues-thread-remove-window-nonce' );

        $thread_id = (int) $_POST['thread_id'];
        $url = $this->get_dialogues_url();

        $out = '';
        $out .= '<div class="remove-window">';
        $out .= '<i class="fa fa-5x  fa-exclamation-circle " aria-hidden="true"></i>';
        $out .= '<p>';
        $out .=  __( 'Вы хотите <strong>удалить все сообщения</strong> этого диалога.', 'mif-bp-customizer' );
        $out .= '<br />';
        $out .=  __( 'Будьте внимательны, эту операцию <strong>нельзя отменить</strong>.', 'mif-bp-customizer' );
        $out .=  '<p><div class="generic-button"><a href="' . $url . '" class="thread-remove">' . __( 'Удалить', 'mif-bp-customizer' ) . '</a></div>';
        $out .=  '<div class="generic-button"><a href="' . $url . '" class="thread-no-remove">' . __( 'Не удалять', 'mif-bp-customizer' ) . '</a></div>';
        $out .= '</div>';

        $arr = array( 
                    'messages_window' => $out,
                    'messages_header' => $this->get_messages_header( $thread_id ),
                    'messages_form' => $this->get_messages_form( $thread_id ),
                    );
        $arr = apply_filters( 'mif_bpc_dialogues_ajax_thread_remove_window', $arr, $thread_id );

        echo json_encode( $arr );


        wp_die();
    }



    //
    // Удаление диалога
    //

    function ajax_thread_remove()
    {
        check_ajax_referer( 'mif-bpc-dialogues-thread-remove-nonce' );

        $thread_id = (int) $_POST['thread_id'];

        $this->delete_thread( $thread_id );

        $arr = array( 
                    'messages_window' => $this->get_dialogues_default_page(),
                    'messages_header' => '<!-- ajaxed -->',
                    'messages_form' => '<div class="form-empty"></div>',
                    );
        $arr = apply_filters( 'mif_bpc_dialogues_ajax_thread_remove', $arr, $thread_id );

        echo json_encode( $arr );

        wp_die();
    }


    //
    // Форма создания нового сообщения
    //

    function ajax_dialogues_compose_form()
    {
        check_ajax_referer( 'mif-bpc-dialogues-compose-form-nonce' );

        $arr = array( 
                    'compose_members' => $this->get_members_items(),
                    'compose_form' => $this->get_compose_form(),
                    'messages_header' => $this->get_compose_header(),
                    'messages_form' => '<div class="form-empty"></div>',
                    );
        $arr = apply_filters( 'mif_bpc_dialogues_ajax_dialogues_compose_form', $arr );

        echo json_encode( $arr );

        wp_die();
    }


    //
    // Заголовок формы нового сообщения
    //

    function get_compose_header()
    {
        $out = '';

        $out .= '<div class="custom-button"><a href="' . $this->get_dialogues_url() . '" class="button dialogues-refresh" title="' . __( 'Отменить', 'mif-bp-customizer' ) . '"><i class="fa fa-times" aria-hidden="true"></i></a></div>';
        $out .= '<span class="title">' . __( 'Новое сообщение', 'mif-bp-customizer' ) . '</span>';

        return apply_filters( 'mif_bpc_dialogues_get_compose_header', $out );
    }



    //
    // Загрузка продолжения списка пользователей
    //

    function ajax_member_more_helper()
    {
        check_ajax_referer( 'mif-bpc-dialogues-member-items-more-nonce' );

        $page = (int) $_POST['page'];

        $arr = array( 'threads_more' => $this->get_members_items( $page ) );
        $arr = apply_filters( 'mif_bpc_dialogues_ajax_member_more_helper', $arr, $page );

        echo json_encode( $arr );

        wp_die();
    }



    //
    // Поиск пользователей 
    //

    function ajax_member_search_helper()
    {
        check_ajax_referer( 'mif-bpc-dialogues-search-nonce' );

        $arr = array( 'compose_members_update' => $this->get_members_items() );
        $arr = apply_filters( 'mif_bpc_dialogues_ajax_member_search_helper', $arr );

        echo json_encode( $arr );

        wp_die();
    }



    //
    // Получить список пользователей
    //

    function get_members_items( $page = 1 )
    {

        $user_id = bp_loggedin_user_id();

        $args = array(
                'per_page' => $this->members_on_page,
                'page' => $page,
                'exclude' => $user_id,
        );

        $args = apply_filters( 'mif_bpc_dialogues_get_members_list_args', $args );

        $arr = array();

        if ( bp_has_members( $args ) ) {

            while ( bp_members() ) {

                bp_the_member(); 
                $arr[] = $this->member_item( bp_get_member_user_id(), bp_get_member_link(), bp_get_member_name() );

            }; 

        }

        $page ++;
        $nonce = wp_create_nonce( 'mif-bpc-dialogues-member-items-more-nonce' );
        $arr[] = '<div class="member-item loader ajax-ready" data-mode="compose" data-page="' . $page . '" data-nonce="' . $nonce . '"></div>';

        return apply_filters( 'mif_bpc_dialogues_get_members_items', implode( "\n", $arr ), $arr, $page );
    }



    //
    // Получить блок пользователя
    //

    function member_item( $user_id, $user_url, $name )
    {
        $out = '';

        $avatar_size = apply_filters( 'mif_bpc_dialogues_avatar_member_size', $this->avatar_member_size );
        $avatar = get_avatar( $user_id, $avatar_size );
        $url = $this->get_dialogues_url();

        $out .= '<div class="member-item member-' . $user_id . '" data-uid="' . $user_id . '">';
        $out .= '<div class="m-check checked"><a href="' . $url . '" class="member-add" title="' . __( 'Добавить', 'mif-bp-customizer' ) . '"><i class="fa fa-circle" aria-hidden="true"></i></a></div>';
        $out .= '<div class="m-check unchecked"><a href="' . $url . '" class="member-add" title="' . __( 'Добавить', 'mif-bp-customizer' ) . '"><i class="fa fa-circle-thin" aria-hidden="true"></i></a></div>';
        $out .= '<span class="avatar"><a href="' . $user_url . '" target="blank">' . $avatar . '</a></span>';
        $out .= '<span class="name"><a href="' . $user_url . '" target="blank">' . $name . '</a></span>';
        $out .= '<span class="m-remove"><div class="custom-button"><a href="' . $url . '" class="button member-remove" title="' . __( 'Удалить', 'mif-bp-customizer' ) . '"><i class="fa fa-times" aria-hidden="true"></i></a></div></span>';
        $out .= '</div>';

        return $out;
    }



    //
    // Ajax-помощник группировки диалогов
    //

    function ajax_dialogues_join()
    {
        check_ajax_referer( 'mif-bpc-dialogues-join-nonce' );


        if ( $this->threads_joining() ) {

            $arr = array();
            $arr['threads_update'] = $this->get_threads_update();
            $arr['threads_update_timestamp'] = time();
            $arr['messages_window'] = $this->dialogues_join_success_page();

            $arr = apply_filters( 'mif_bpc_dialogues_ajax_dialogues_join', $arr );

            echo json_encode( $arr );
    
        }

        wp_die();
    }


    // 
    // Страницу с сообщением о успешной группировке диалогов
    // 

    function dialogues_join_success_page()
    {
        $out = '';

        $out .= '<div class="messages-empty"><div>';
        $out .= '<i class="fa fa-5x fa-compress" aria-hidden="true"></i>';
        $out .= '<p><strong>' . __( 'Группировка выполнена успешно', 'mif-bp-customizer' ) . '</strong>';
        $out .= '<p>' . __( 'Выберите диалог или', 'mif-bp-customizer' ) . '<br />';
        $out .= '<a href="' . $this->get_dialogues_url() . '" class="dialogues-compose">' . __( 'начните новый', 'mif-bp-customizer' ) . '</a></p>';
        $out .= '</div></div>';

        return $out;
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
        $threads_mode = $_POST['threads_mode'];

        $arr = array();

        // Что показывается - диалоги или пользователи?

        if ( $threads_mode == 'threads' ) {

            $threads_update = $this->get_threads_update( $threads_update_timestamp );
            if ( $threads_update ) $arr['threads_update'] = $threads_update;
            $arr['threads_update_timestamp'] = time();

        } elseif ( $threads_mode == 'compose' ) {

            $arr['threads_window'] = $this->get_threads_items();

        }

        $messages_update = $this->get_messages_items( $thread_id, $last_message_id );

        if ( $messages_update ) { 
            
            $arr['messages_update'] = $messages_update; 
            $messages_header_update = $this->get_messages_header( $thread_id );
            if ( $messages_header_update ) $arr['messages_header_update'] = $messages_header_update;
            
        } else {

            $arr['messages_header'] = '<!-- empty -->'; 
            $arr['messages_window'] = $this->get_dialogues_default_page(); 

        }

        $arr = apply_filters( 'mif_bpc_dialogues_ajax_dialogues_refresh', $arr, $thread_id, $last_message_id, $threads_update_timestamp, $threads_mode );

        echo json_encode( $arr );

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
        foreach ( (array) $messages as $message ) $arr[$message->id] = $this->message_item( $message );

        $arr = array_reverse( $arr, true );

        return apply_filters( 'mif_bpc_dialogues_get_messages_items', $arr, $thread_id, $last_message_id );
    }


    //
    // Отправить сообщение
    //

    function send( $message, $thread_id = NULL, $sender_id = NULL, $subject = 'default', $email_status = 'no' )
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
            $deleted = bp_messages_get_meta( $message_id, 'deleted' );
            $outdate_time = apply_filters( 'mif_bpc_dialogues_outdate_time', $this->message_outdate_time );
        
            if ( isset( $last_updated ) && empty( $deleted ) && timestamp_to_now( $last_updated ) < $outdate_time ) $update_flag = true;

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

        // Обновить для других пользователей информацию о непрочитанных

        if ( $update_flag ) {

            $sql = $wpdb->prepare( "UPDATE {$bp->messages->table_name_recipients} SET unread_count = 1 WHERE unread_count = 0 AND thread_id = %d AND user_id <> %d", $thread_id, $sender_id );
            $wpdb->query( $sql );

        } else {

            $sql = $wpdb->prepare( "UPDATE {$bp->messages->table_name_recipients} SET unread_count = unread_count + 1 WHERE thread_id = %d AND user_id <> %d", $thread_id, $sender_id );
            $wpdb->query( $sql );

        }

        // Отметить для себя, что всё прочитано

        $ret = $this->mark_as_read( $thread_id, $sender_id );

        // Узнать id получателей сообщения и отправить им уведомление (локальное уведомление, эхо-сервер, почта или др.)

        $sql = $wpdb->prepare( "SELECT user_id FROM {$bp->messages->table_name_recipients} WHERE thread_id = %d AND user_id <> %d", $thread_id, $sender_id );
        $recipients = $wpdb->get_col( $sql );

        do_action( 'mif_bpc_dialogues_after_send', $recipients, $thread_id, $sender_id, $message, $email_status );

        return apply_filters( 'mif_bpc_dialogues_send', true, $recipients, $thread_id, $sender_id, $message, $email_status, $ret );
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

        return apply_filters( 'mif_bpc_dialogues_mark_as_read', $ret );
    }


    //
    // Удалить диалог
    //

    function delete_thread( $thread_id = NULL, $user_id = NULL )
    {
        global $bp, $wpdb;

        if ( $thread_id == NULL ) return;
        if ( $user_id == NULL ) $user_id = bp_loggedin_user_id();

        // Отметить как удаленный
       
        $sql = $wpdb->prepare( "UPDATE {$bp->messages->table_name_recipients} SET is_deleted=1 WHERE thread_id=%d AND user_id=%d", $thread_id, $user_id );
        $ret = $wpdb->query( $sql );

        // Получить список всех активных пользователей диалога

        $sql = $wpdb->prepare( "SELECT user_id FROM {$bp->messages->table_name_recipients} WHERE thread_id=%d AND is_deleted=0", $thread_id );
        $user_ids = $wpdb->get_col( $sql );

        // Группировать личные диалоги после удаления пользователя (склеивание двух новых с удаленным пользователем)
        // Это действие логично выполнять только тогда, когда у всех пользователей диалоги группированы всегда и по умолчанию

        // $sql = $wpdb->prepare( "SELECT user_id FROM {$bp->messages->table_name_recipients} WHERE thread_id=%d", $thread_id );
        // $all_user_ids = $wpdb->get_col( $sql );
        // if ( count( $all_user_ids ) == 2 && count( $user_ids ) == 1 ) $this->threads_joining( $user_ids[0] );

        // Удалять совсем, если активных пользователей у диалога не осталось

        if ( count( $user_ids ) === 0 ) {

            $sql = $wpdb->prepare( "DELETE FROM {$bp->messages->table_name_recipients} WHERE thread_id=%d", $thread_id );
            $ret2 = $wpdb->query( $sql );

            $sql = $wpdb->prepare( "DELETE FROM {$bp->messages->table_name_messages} WHERE thread_id=%d", $thread_id );
            $ret3 = $wpdb->query( $sql );

        }

        return apply_filters( 'mif_bpc_dialogues_delete_thread', $ret, $user_ids, $all_user_ids, $ret2, $ret3 );
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
            
            $sql = $wpdb->prepare( "SELECT DISTINCT user_id, is_deleted FROM {$bp->messages->table_name_recipients} WHERE thread_id = %d AND user_id <> %d", $thread_id, $user_id );
            $user_ids = $wpdb->get_results( $sql );
            
            // Если собеседник только один, то запомнить номер диалога

            if ( count( $user_ids ) == 1 ) {
                
                $key = $user_ids[0]->user_id . ':' . $user_ids[0]->is_deleted;
                $arr[$key][] = $thread_id;

            }
            
        }

        $ret = true;

        foreach ( (array) $arr as $threads_arr ) {

            // Если с собеседником диалог только один, то идти дальше
            if ( count( $threads_arr ) == 1 ) continue;

            $thread_id = array_pop( $threads_arr );
            $threads_list = implode( ',', $threads_arr );

            // Обновить номера диалогов в таблице сообщений

            $sql = $wpdb->prepare( "UPDATE {$bp->messages->table_name_messages} SET thread_id = %d WHERE thread_id IN ({$threads_list})", $thread_id );
            if ( $wpdb->query( $sql ) ) {

                // Если обновление прошло успешно, то удалить старые номера диалогов в таблице диалогов
                $sql = "DELETE FROM {$bp->messages->table_name_recipients} WHERE thread_id IN ({$threads_list})";
                $ret2 = $wpdb->query( $sql );

                
            } else {

                $ret = false;

            }

        }

        return apply_filters( 'mif_bpc_dialogues_threads_joining', $ret, $user_id, $user_ids, $ret2 );
    }


    //
    // Адрес страницы диалогов
    //

    function get_dialogues_url()
    {
        global $bp;
        $url = $bp->displayed_user->domain . $bp->messages->slug . '/dialogues/';

        return apply_filters( 'mif_bpc_dialogues_get_dialogues_url', $url );
    }


    //
    // Скрытые поля для AJAX-запросов
    //

    function get_hidden_fields()
    {
        $out = '';

        $threads_update_timestamp = time();
        $out .= '<input type="hidden" id="threads_update_timestamp" value="' . $threads_update_timestamp . '">';
        $out .= '<input type="hidden" id="threads_mode" value="threads">';

        $out .= '<input type="hidden" id="dialogues_refresh_nonce" value="' . wp_create_nonce( 'mif-bpc-dialogues-refresh-nonce' ) . '">';
        $out .= '<input type="hidden" id="dialogues_join_nonce" value="' . wp_create_nonce( 'mif-bpc-dialogues-join-nonce' ) . '">';
        $out .= '<input type="hidden" id="dialogues_message_remove_nonce" value="' . wp_create_nonce( 'mif-bpc-dialogues-message-remove-nonce' ) . '">';
        $out .= '<input type="hidden" id="dialogues_thread_remove_window_nonce" value="' . wp_create_nonce( 'mif-bpc-dialogues-thread-remove-window-nonce' ) . '">';
        $out .= '<input type="hidden" id="dialogues_thread_remove_nonce" value="' . wp_create_nonce( 'mif-bpc-dialogues-thread-remove-nonce' ) . '">';
        $out .= '<input type="hidden" id="dialogues_thread_nonce" value="' . wp_create_nonce( 'mif-bpc-dialogues-thread-nonce' ) . '">';
        $out .= '<input type="hidden" id="dialogues_compose_form_nonce" value="' . wp_create_nonce( 'mif-bpc-dialogues-compose-form-nonce' ) . '">';
        $out .= '<input type="hidden" id="dialogues_compose_nonce" value="' . wp_create_nonce( 'mif-bpc-dialogues-compose-nonce' ) . '">';
        $out .= '<input type="hidden" id="dialogues_search_nonce" value="' . wp_create_nonce( 'mif-bpc-dialogues-search-nonce' ) . '">';

        return apply_filters( 'mif_bpc_dialogues_get_hidden_fields', $out );
    }


    function get_dialogues_default_page()
    {
        $out = '';

        $out .= '<div class="messages-empty"><div>';
        $out .= '<i class="fa fa-5x fa-comments-o" aria-hidden="true"></i>';
        $out .= '<p>' . __( 'Выберите диалог или', 'mif-bp-customizer' ) . '<br />';
        $out .= '<a href="' . $this->get_dialogues_url() . '" class="dialogues-compose">' . __( 'начните новый', 'mif-bp-customizer' ) . '</a></p>';
        $out .= '</div></div>';

        return apply_filters( 'mif_bpc_dialogues_get_dialogues_default_page', $out );;
    }



}



// 
// Выводит адрес страницы диалогов пользователя
// 

function mif_bpc_the_dialogues_url()
{
    global $mif_bpc_dialogues;
    echo $mif_bpc_dialogues->get_dialogues_url();
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

    echo $mif_bpc_dialogues->get_hidden_fields();

    // $threads_update_timestamp = time();
    // echo '<input type="hidden" name="threads_update_timestamp" id="threads_update_timestamp" value="' . $threads_update_timestamp . '">';
    // echo '<input type="hidden" name="threads_mode" id="threads_mode" value="threads">';

    // $nonce = wp_create_nonce( 'mif-bpc-dialogues-refresh-nonce' );
    // echo '<input type="hidden" name="dialogues_refresh_nonce" id="dialogues_refresh_nonce" value="' . $nonce . '">';

    // $nonce = wp_create_nonce( 'mif-bpc-dialogues-join-nonce' );
    // echo '<input type="hidden" name="dialogues_join_nonce" id="dialogues_join_nonce" value="' . $nonce . '">';

    // $nonce = wp_create_nonce( 'mif-bpc-dialogues-message-remove-nonce' );
    // echo '<input type="hidden" name="dialogues_message_remove_nonce" id="dialogues_message_remove_nonce" value="' . $nonce . '">';

    // $nonce = wp_create_nonce( 'mif-bpc-dialogues-thread-remove-window-nonce' );
    // echo '<input type="hidden" name="dialogues_thread_remove_window_nonce" id="dialogues_thread_remove_window_nonce" value="' . $nonce . '">';

    // $nonce = wp_create_nonce( 'mif-bpc-dialogues-thread-remove-nonce' );
    // echo '<input type="hidden" name="dialogues_thread_remove_nonce" id="dialogues_thread_remove_nonce" value="' . $nonce . '">';

    // $nonce = wp_create_nonce( 'mif-bpc-dialogues-thread-nonce' );
    // echo '<input type="hidden" name="dialogues_thread_nonce" id="dialogues_thread_nonce" value="' . $nonce . '">';

    // $nonce = wp_create_nonce( 'mif-bpc-dialogues-compose-form-nonce' );
    // echo '<input type="hidden" name="dialogues_compose_form_nonce" id="dialogues_compose_form_nonce" value="' . $nonce . '">';

    // $nonce = wp_create_nonce( 'mif-bpc-dialogues-compose-nonce' );
    // echo '<input type="hidden" name="dialogues_compose_nonce" id="dialogues_compose_nonce" value="' . $nonce . '">';

    // $nonce = wp_create_nonce( 'mif-bpc-dialogues-search-nonce' );
    // echo '<input type="hidden" name="dialogues_search_nonce" id="dialogues_search_nonce" value="' . $nonce . '">';

}


// 
// Выводит страницу по умолчанию
// 

function mif_bpc_the_dialogues_default_page()
{
    global $mif_bpc_dialogues;
    echo $mif_bpc_dialogues->get_dialogues_default_page();
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



?>