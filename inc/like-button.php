<?php

//
// Кнопка "Нравится"
// 
//

defined( 'ABSPATH' ) || exit;

if ( mif_bpc_options( 'like-button' ) ) {

    global $mif_bpc_like_button;
    $mif_bpc_like_button = new mif_bpc_like_button();

}


class mif_bpc_like_button {

    //
    // Механизм "Нравится" - у элементов активности есть мета-поле 'mif_bpc_likes' со списком id пользовавтелей, 
    // нажавших "Нравится". Это используется при выводе кнопки "Нравится"
    //
    // Есть мета-поле 'mif_bpc_likes', где указывается время последнего нажатия "Нравится"
    //
    // "Нравится" для записей блога связывается с данными элемента ленты активности о публикации этой записи.
    //

    //
    // Ключ мета-поля
    //

    public $meta_key = 'mif_bpc_likes';
    
    // 
    // Количество аватар, выводимых в подсказке
    // 

    public $number = 5;

    //
    // Элементы активности, которые нельзя отмечать кнопкой "Нравится"
    //

    public $unlikes_activity = array( 'activity_update' );
    

    function __construct()
    {

        add_action( 'bp_activity_entry_meta', array( $this, 'like_button' ), 10 );
        add_action( 'wp_print_scripts', array( $this, 'load_js_helper' ) );            				
        add_action( 'wp_ajax_like-button-press', array( $this, 'like_button_ajax_helper' ) );

        $this->number = apply_filters( 'mif_bpc_like_buttons_avatar_number', $this->number );


        // Расскоментируйте на один раз эту строку для конвертации старых данных
        // add_action( 'init', array( $this, 'likes_old_to_new_converted' ) );

    }


    //
    // Показать кнопку "Нравится"
    //

    function like_button()
    {

        global $bp;

        $likes = $this->get_likes();
        $count = count( $likes );

		$url = wp_nonce_url( home_url( bp_get_activity_root_slug() . '/like/' . bp_get_activity_id() . '/' ), 'mif_bpc_like_button_press' );

        // $span = ( $count ) ? ' <span>' . $count . '</span>' : '';
        // $span = ' <span>' . $count . '</span>';
        $active = ( $this->is_liked() ) ? ' active' : '';

        $avatar_hint = $this->avatar_hint();
// echo $avatar_hint;
        // echo '<a href="" class="button bp-secondary-action like">' . __( 'Нравится', 'mif-bp-customizer' ) . $span . '</a>';
        echo '<div class="like"><a href="' . $url . '" class="button bp-primary-action like' . $active . '"><i class="fa fa-heart" aria-hidden="true"></i> <span>' . $count . '</span></a>' . $avatar_hint . '</div>';
        // echo '<a href="' . $url . '" class="button bp-primary-action like' . $active . '"><i class="fa fa-heart" aria-hidden="true"></i> <span>' . $count . '</span>' . $avatar_hint . '</a>';

    }

    public function load_js_helper()
    {
        wp_register_script( 'mif_bpc_ilke-button', plugins_url( '../js/like-button.js', __FILE__ ) );  
        wp_enqueue_script( 'mif_bpc_ilke-button' );
    }


    public function like_button_ajax_helper()
    {
        check_ajax_referer( 'mif_bpc_like_button_press' );

        if ( ! mif_bpc_options( 'like-button' ) ) wp_die();

        $activity_id = (int) $_POST['activity_id'];
        $user_id = bp_loggedin_user_id();
        
        if ( $this->is_liked( $activity_id, $user_id ) ) {

            if ( $this->unliked( $activity_id, $user_id ) ) echo 'unliked';

        } else {

            if ( $this->liked( $activity_id, $user_id ) ) echo 'liked';

        }

        $this->get_cache_avatar_data( $activity_id, 'create_new_cache' );

        wp_die();
    }


    // 
    // Выводит аватары во всплывающей подсказке
    // 

    function avatar_hint()
    {
        // if ( $arr == NULL ) return;

        $out = '';

        $out .= '<div class="mif-bpc-hint"><div>';

        $out .= $this->get_avatars();
        // $out .= '<img src="dd">';
        // p($this->get_avatars());

        // foreach ( (array) $arr as $item ) {

        //     $param = '';
        //     if ( is_array( $item['data'] ) )
        //         foreach ( $item['data'] as $key => $value )
        //             $param = ' '. 'data-' . $key . '="' . $value . '"';

        //     $class = ( isset( $item['class'] ) ) ? ' class="' . $item['class'] . '"' : '';
        //     $out .= '<a href="' . $item['href'] . '"' . $class . $param . '>' . $item['descr'] . '</a>';

        // };

        $out .= '</div></div>';

        return $out;
    }


    // 
    // Получить аватарки тех, кто нажимал "Нравится"
    // 

    function get_avatars( $activity_id = NULL )
    {

        if ( $activity_id == NULL ) $activity_id = bp_get_activity_id();

        $user_avatars = $this->get_cache_avatar_data( $activity_id );

        $current_user_id = bp_loggedin_user_id();

        unset( $user_avatars[$current_user_id] );
        shuffle( $user_avatars );

        $friends_ids = friends_get_friend_user_ids( $current_user_id );

        $arr = array();

        // Сначала выбрать друзей с аватарками

        foreach ( (array) $user_avatars as $key => $item ) {

            if ( count( $arr ) >= $this->number ) break;

            if ( $item['avatar'] == 'default' ) continue;
            if ( ! in_array( $key, $friends_ids ) ) continue;

            $arr[] = $item['html'];
            unset( $user_avatars[$key] );

        }

        // Далее, если не хватает, добавить не друзей с аватарками

        foreach ( (array) $user_avatars as $key => $item ) {

            if ( count( $arr ) >= $this->number ) break;

            if ( $item['avatar'] == 'default' ) continue;

            $arr[] = $item['html'];
            unset( $user_avatars[$key] );

        }

        // Теперь, если опять не хватает, добавить друзей без аватарок

        foreach ( (array) $user_avatars as $key => $item ) {

            if ( count( $arr ) >= $this->number ) break;

            if ( ! in_array( $key, $friends_ids ) ) continue;

            $arr[] = $item['html'];
            unset( $user_avatars[$key] );

        }

        // Ну и наконец, если надо, добавить всех остальных

        foreach ( (array) $user_avatars as $key => $item ) {

            if ( count( $arr ) >= $this->number ) break;

            $arr[] = $item['html'];
            unset( $user_avatars[$key] );

        }

        shuffle( $arr );
        
        $current_user_avatar = $this->get_item_avatar( array(   'ID' => $current_user_id, 
                                                                'url' => bp_loggedin_user_domain(),
                                                                'name' => bp_core_get_user_displayname( $current_user_id ) ), 'current_user' );

        // $out = $current_user_avatar;
        // $out = $current_user_avatar . implode( '', $arr );
        $out = implode( '', $arr );
        
        return apply_filters( 'mif_bpc_like_button_get_avatars', $out, $activity_id, $current_user_id );

    }


    //
    // Выбирает данные аватарок из кэша или формирует этот кэш
    //

    function get_cache_avatar_data( $activity_id = NULL, $nocache = false )
    {

        $cache_data = bp_activity_get_meta( $activity_id, 'cache_liked_avatar', true );
        if ( empty( $cache_data ) || $cache_data['expires'] < time() || $nocache != false ) {

            $user_ids = $this->get_likes( $activity_id );

            $args = array(
                    'max' => $this->number * 10,
                    'per_page' => $this->number * 10,
                    'include' => $user_ids,
                    // 'orderby' => 'rand',
                    'type' => 'random',
            );
            
            if ( bp_has_members( $args ) ) {

                while ( bp_members() ) {

                    bp_the_member(); 

                    $user_data[] = array(
                                    'ID' => bp_get_member_user_id(),
                                    'url' => bp_get_member_link(),
                                    'name' => bp_get_member_name(),
                                );

                }; 

            }

            $avatar_dir = trailingslashit( bp_core_avatar_upload_path() ) . trailingslashit( 'avatars' ); 

            $user_data_clean = array();

            // Выберем сначала те аватарки, которые с картинками

            foreach ( (array) $user_data as $key => $item ) {

                if ( file_exists( $avatar_dir . $item['ID'] ) ) {

                    $item['avatar'] = 'img';
                    $user_data_clean[] = $item;
                    unset( $user_data[$key] );

                }
                
                if ( count( $user_data_clean ) >= $this->number * 5 ) break;

            }

            // Добавим и аватарки без картинок, если их не хватает

            foreach ( (array) $user_data as $key => $item ) {

                if ( count( $user_data ) >= $this->number * 5 ) break;
                $item['avatar'] = 'default';
                $user_data_clean[] = $item;

            }

            // Массив с HTML аватарок

            $user_avatars = array();

            foreach ( (array) $user_data_clean as $item ) {
                
                $user_avatars[$item['ID']] = array( 'avatar' => $item['avatar'],
                                                    'html' => $this->get_item_avatar( $item ) );

            }
            
            // Здесь можно изменить время жизни аватарок в кеше. По умолчанию 1 час = 3600 секунд.
            // Кеш сбрасывается, когда обновляется список лайков
            
            $ttl = apply_filters( 'mif_bpc_like_buttons_avatar_cache_ttl', 3600 );

            $expires = time() + $ttl;

            bp_activity_update_meta( $activity_id, 'cache_liked_avatar', array( 'expires' => $expires, 'user_avatars' => $user_avatars ) );

        } else {

            $user_avatars = $cache_data['user_avatars'];

        }

        return apply_filters( 'mif_bpc_like_cache_avatar_data', $user_avatars );

    }

    //
    // Получить HTML-блок одной аватарки
    //

    function get_item_avatar( $item, $class = '' )
    {

        // Размер аватар, выводимых в подсказке
        $avatar_size  = apply_filters( 'mif_bpc_like_buttons_avatar_size', 30 );

        $before = ( $item['url'] ) ? '<a href="' . $item['url'] . '">' : '';
        $after = ( $item['url'] ) ? '</a>' : '';

        if ( $class ) $class = ' ' . $class;

        $ret = '<span class="avatar' . $class . '" title="' . $item['name'] . '">' . $before . get_avatar( $item['ID'], $avatar_size ) . $after . '</span>';

        return $ret;

    }



    //
    // Добавить новую отметку "Нравится"
    //

    function liked( $activity_id = NULL, $user_id = NULL )
    {

        if ( $activity_id == NULL ) return;
        if ( $user_id == NULL ) return;

        $likes_ids = bp_activity_get_meta( $activity_id, $this->meta_key, true );
        $likes_arr = explode( ',', $likes_ids );
        $likes_arr[] = (int) $user_id;

        $likes_arr = array_unique( $likes_arr );
        $likes_arr = array_diff( $likes_arr, array( '' ) );

        $likes_ids = implode( ',', $likes_arr );

        $ret = bp_activity_update_meta( $activity_id, $this->meta_key, $likes_ids );
        bp_activity_update_meta( $activity_id, $this->meta_key . '_timestamp', time() );
        
        wp_cache_delete( 'likes_arr', $activity_id );

        return $ret;

    }


    //
    // Убрать отметку "Нравится"
    //

    function unliked( $activity_id = NULL, $user_id = NULL )
    {

        if ( $activity_id == NULL ) return;
        if ( $user_id == NULL ) return;

        $likes_ids = bp_activity_get_meta( $activity_id, $this->meta_key, true );
        $likes_arr = explode( ',', $likes_ids );
        $likes_arr = array_diff( $likes_arr, array( $user_id ) );

        $likes_arr = array_unique( $likes_arr );
        $likes_arr = array_diff( $likes_arr, array( '' ) );

        $likes_ids = implode( ',', $likes_arr );

        $ret = bp_activity_update_meta( $activity_id, $this->meta_key, $likes_ids );
        
        wp_cache_delete( 'likes_arr', $activity_id );

        return $ret;

    }


    //
    // Проверить, есть ли пользователь в числе тех, кому понравилось
    //

    function is_liked( $activity_id = NULL, $user_id = NULL )
    {

        if ( $activity_id == NULL ) $activity_id = bp_get_activity_id();
        if ( $user_id == NULL ) $user_id = bp_loggedin_user_id();

        $likes_arr = $this->get_likes( $activity_id );

        $ret = ( isset( $likes_arr ) && in_array( $user_id, $likes_arr ) ) ? true : false;

        return apply_filters( 'mif_bpc_like_button_get_likes', $ret, $activity_id, $user_id );

    }


    //
    // Получить массив ID пользователей, которым нравится элемент активности
    //

    function get_likes( $activity_id = NULL )
    {

        if ( $activity_id == NULL ) $activity_id = bp_get_activity_id();
        // if ( $activity_id == NULL ) return;

        if ( ! $likes_arr = wp_cache_get( 'likes_arr', $activity_id ) ) {

            $likes_ids = bp_activity_get_meta( $activity_id, $this->meta_key, true );
            $likes_arr = ( $likes_ids ) ? explode( ',', $likes_ids ) : NULL;

            $likes_arr = array_unique( (array) $likes_arr );
            $likes_arr = array_diff( (array) $likes_arr, array( '' ) );

            // Здесь можно уточнить список пользователей. Например, удалить тех, кто заблокирован
            
            $likes_arr = apply_filters( 'mif_bpc_like_button_get_likes', $likes_arr, $activity_id );

            wp_cache_set( 'likes_arr', $likes_arr, $activity_id );

        }

        return $likes_arr;

    }


    //
    // Получить список типов активности, котрая не может нравиться
    //

    function get_unlikes_activity()
    {
        return apply_filters( 'mif_bpc_like_button_get_unlikes_activity', $this->unlikes_activity );
    }



    //
    // Конвертация данных (от плагина BuddyPress Like)
    //

    function likes_old_to_new_converted( $activity_id = NULL )
    {
        global $wpdb;

        $table = _get_meta_table( 'activity' );
        $arr = $wpdb->get_results( "SELECT activity_id, meta_value FROM $table WHERE meta_key='liked_count'", ARRAY_A );

        foreach ( (array) $arr as $item ) {
            $likes_ids = implode( ',', array_keys( unserialize( $item['meta_value'] ) ) );

		    bp_activity_update_meta( $item['activity_id'], $this->meta_key, $likes_ids );
            bp_activity_delete_meta( $item['activity_id'], 'liked_count' );

        }

        $table = _get_meta_table( 'user' );
        $arr = $wpdb->get_results( "SELECT user_id FROM $table WHERE meta_key='bp_liked_activities'", ARRAY_A );
        
        foreach ( (array) $arr as $item ) {

            delete_user_meta( $item['user_id'], 'bp_liked_activities' );

        }

    }





}



?>
