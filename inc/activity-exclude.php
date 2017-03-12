<?php

//
// Класс исключенной активности
// 
//

defined( 'ABSPATH' ) || exit;


if ( mif_bpc_options( 'activity-exclude' ) ) {

    global $mif_bpc_activity_exclude;
    $mif_bpc_activity_exclude = new mif_bpc_activity_exclude();

}


class mif_bpc_activity_exclude {

    //
    // Механизм блокировки - у пользователя есть мета-поле 'activity_exclude' со списком типов исключенной активности,
    // это учитывается при формировании главной страницы профиля пользователя.
    // 

    //
    // Ключ мета-поля
    //

    public $meta_key = 'activity_exclude';

    //
    // Типы активности, которые нельзя блокировать
    //

    public $unexcluded_types = array( 'activity_update', 'activity_repost' );


    function __construct()
    {

        add_action( 'bp_activity_setup_nav', array( $this, 'activity_exclude_nav' ) );
        add_action( 'bp_init', array( $this, 'activity_exclude_helper' ) );

        // add_action( 'bp_activity_entry_meta', array( $this, 'exclude_button' ), 20 );
        add_action( 'wp_print_scripts', array( $this, 'load_js_helper' ) );            				
        add_action( 'wp_ajax_disable-activity-type-button', array( $this, 'exclude_button_ajax_helper' ) );

        add_filter( 'mif_bpc_activity_action_menu', array( $this, 'exclude_button_menu' ), 40 );

    }
    
    




    // 
    // Кнопка удаления типов активности в своей ленте
    // 
    // 

    public function exclude_button_menu( $arr )
    {

        if ( ! bp_is_current_action( 'all-stream' ) ) return $arr;

        global $bp;

        $activity_type = bp_get_activity_type();
        $unexcluded_types = $this->get_unexcluded_types();

        if ( in_array( $activity_type, $unexcluded_types ) ) return $arr;

        $settings_url = $bp->loggedin_user->domain . $bp->profile->slug . '/activity-settings';
        $exclude_url = wp_nonce_url( $settings_url . '/request-exclude/' . $activity_type . '/', 'mif_bpc_activity_type_exclude_button' );

        // $arr = array();
        // if ( ! in_array( $at, $unexcluded_types ) ) $arr[] = array( 'href' => $exclude_url, 'descr' => __( 'Не показывать записи этого типа', 'mif-bp-customizer' ), 'class' => 'ajax', 'data' => array( 'exclude' => $at ) );
        // $arr[] = array( 'href' => $settings_url, 'descr' => __( 'Настройка', 'mif-bp-customizer' ) );

        // $arr = array(
        //             array( 'href' => $exclude_url, 'descr' => __( 'Не показывать записи этого типа', 'mif-bp-customizer' ), 'class' => 'ajax', 'data' => array( 'exclude' => $activity_type ) ),
        //             array( 'href' => $settings_url, 'descr' => __( 'Настройка', 'mif-bp-customizer' ) ),
        //         );

        $arr[] = array( 'href' => $exclude_url, 'descr' => __( 'Не показывать записи этого типа', 'mif-bp-customizer' ), 'class' => 'ajax activity-exclude', 'data' => array( 'exclude' => $activity_type ) );
        $arr[] = array( 'href' => $settings_url, 'descr' => __( 'Настройка', 'mif-bp-customizer' ) );

        // echo '<div class="right relative disable-activity-type"><a href="" class="button bp-secondary-action disable-activity-type"><strong>&middot;&middot;&middot;</strong></a>' . mif_bpc_hint( $arr ) . '</div>';

        // echo '<a href="" class="button bp-secondary-action disable-activity-type" title="' . __( 'Не показывать записи этого типа', 'mif-bp-customizer' ) . '"><strong>&middot;&middot;&middot;</strong></a>';
        // echo '<a href="" class="button bp-secondary-action disable-activity-type"><i class="fa fa-ellipsis-h" aria-hidden="true"></i></a>';

        return $arr;
    }


    // 
    // Кнопка удаления типов активности в своей ленте
    // 
    // 

    public function exclude_button()
    {

        if ( ! bp_is_current_action( 'all-stream' ) ) return;

        global $bp;

        $activity_type = bp_get_activity_type();
        $unexcluded_types = $this->get_unexcluded_types();

        if ( in_array( $activity_type, $unexcluded_types ) ) return;

        $settings_url = $bp->loggedin_user->domain . $bp->profile->slug . '/activity-settings';
        $exclude_url = wp_nonce_url( $settings_url . '/request-exclude/' . $activity_type . '/', 'mif_bpc_activity_type_exclude_button' );

        // $arr = array();
        // if ( ! in_array( $at, $unexcluded_types ) ) $arr[] = array( 'href' => $exclude_url, 'descr' => __( 'Не показывать записи этого типа', 'mif-bp-customizer' ), 'class' => 'ajax', 'data' => array( 'exclude' => $at ) );
        // $arr[] = array( 'href' => $settings_url, 'descr' => __( 'Настройка', 'mif-bp-customizer' ) );

        $arr = array(
                    array( 'href' => $exclude_url, 'descr' => __( 'Не показывать записи этого типа', 'mif-bp-customizer' ), 'class' => 'ajax', 'data' => array( 'exclude' => $activity_type ) ),
                    array( 'href' => $settings_url, 'descr' => __( 'Настройка', 'mif-bp-customizer' ) ),
                );

        echo '<div class="right relative disable-activity-type"><a href="" class="button bp-secondary-action disable-activity-type"><strong>&middot;&middot;&middot;</strong></a>' . mif_bpc_hint( $arr ) . '</div>';

        // echo '<a href="" class="button bp-secondary-action disable-activity-type" title="' . __( 'Не показывать записи этого типа', 'mif-bp-customizer' ) . '"><strong>&middot;&middot;&middot;</strong></a>';
        // echo '<a href="" class="button bp-secondary-action disable-activity-type"><i class="fa fa-ellipsis-h" aria-hidden="true"></i></a>';
    }



    public function load_js_helper()
    {
        wp_register_script( 'mif_bpc_exclude_button', plugins_url( '../js/button-hint-helper.js', __FILE__ ) );  
        wp_enqueue_script( 'mif_bpc_exclude_button' );
    }



    public function exclude_button_ajax_helper()
    {
        check_ajax_referer( 'mif_bpc_activity_type_exclude_button' );

        if ( ! mif_bpc_options( 'activity-exclude' ) ) wp_die();

        $exclude = sanitize_text_field( $_POST['exclude'] );
        $unexcluded_types = $this->get_unexcluded_types();
        $activity_exclude = $this->get_activity_exclude();


        if ( in_array( $exclude, $unexcluded_types ) ) wp_die();
        if ( in_array( $exclude, $activity_exclude ) ) wp_die();

        $activity_exclude[] = $exclude;
        
        if ( update_user_meta( bp_loggedin_user_id(), $this->meta_key, implode( ', ', $activity_exclude ) ) ) {

            echo $exclude;
        
        }
        
        wp_die();
    }





    // 
    // Страница настройки ленты активности (типы активности)
    // 
    // 

    public function activity_exclude_nav()
    {
        global $bp;

        $parent_url = $bp->loggedin_user->domain . $bp->profile->slug . '/';
        $parent_slug = $bp->profile->slug;

        $sub_nav = array(  
                'name' => __( 'Лента', 'mif-bp-customizer' ), 
                'slug' => 'activity-settings', 
                'parent_url' => $parent_url, 
                'parent_slug' => $parent_slug, 
                'screen_function' => array( $this, 'activity_exclude_screen' ), 
                'position' => 60,
                'user_has_access'=>  bp_is_my_profile() 
            );

        bp_core_new_subnav_item( $sub_nav );
        
    }


    public function activity_exclude_screen()
    {
        global $bp;
        add_action( 'bp_template_title', array( $this, 'activity_exclude_title' ) );
        add_action( 'bp_template_content', array( $this, 'activity_exclude_body' ) );
        bp_core_load_template( apply_filters( 'bp_core_template_plugin', 'members/single/plugins' ) );
    }


    public function activity_exclude_title()
    {
        echo __( 'Параметры ленты активности', 'mif-bp-customizer' );
    }


    public function activity_exclude_body()
    {
        $activity_exclude = $this->get_activity_exclude();
        $unexcluded_types = $this->get_unexcluded_types();

        $out = '';

        $out .= '<p>' . __( 'Укажите элементы ленты активности, которые должны отображаться на вашей главной странице. Блокировка этих элементов также доступна и в самой ленте главной страницы.', 'mif-bp-customizer' ) . '</p>';
        
        $out .= '<form class="nav-settings-form" method="POST">';

        $activity_types_data = $this->get_activity_types( 'table' );

        foreach ( (array) $activity_types_data as $activity_types_data_group ) {

            $out .= '<h4>' . $activity_types_data_group['descr'] . '</h4>';

            foreach ( (array) $activity_types_data_group['items'] as $key => $item ) {
                $checked = ( ! in_array( $key, $activity_exclude ) ) ? ' checked' : '';
                $disabled = ( in_array( $key, $unexcluded_types ) ) ? ' disabled' : '';
                $out .= '<label><input type="checkbox" name="items[' . $key . ']"' . $checked . $disabled . ' /> <span>' . $item . '</span></label>';
            }
        }

        $out .= '<input type="hidden" name="items[last_activity]" value="on"  />';
        $out .= wp_nonce_field( 'mif-bp-customizer-settings-activity', '_wpnonce', true, false );
        $out .= '&nbsp;<p><input type="submit" value="' . __( 'Сохранить изменения', 'mif-bp-customizer' ) . '">';
        $out .= '</form>';

        echo $out;
    }


    public function activity_exclude_helper()
    {
        if ( ! ( isset( $_POST['_wpnonce'] ) && wp_verify_nonce( $_POST['_wpnonce'], 'mif-bp-customizer-settings-activity' ) ) ) return;

        $form_types = array_keys( $_POST['items'] );
        $all_types = $this->get_activity_types();
        $unexcluded_types = $this->get_unexcluded_types();
        $exclude_types = array_diff( $all_types, $form_types, $unexcluded_types );

        if ( update_user_meta( bp_loggedin_user_id(), $this->meta_key, implode( ', ', $exclude_types ) ) ) {
            
            bp_core_add_message( __( 'Список элементов активности сохранён.', 'mif-bp-customizer' ) );

        }
    }

        
    //
    // Получает таблицу типов активности (только ключи или полная таблица с описанием)
    //

    public function get_activity_types( $mode = 'keys' )
    {

        if ( ! $data = wp_cache_get( 'activity_types' ) ) {

            $data = array(

                    'activity_update' => array( 'part' => 10, 'descr' => __( 'Сообщение в ленте активности', 'mif-bp-customizer' ) ),
                    'activity_repost' => array( 'part' => 10, 'descr' => __( 'Репост записи в ленте активности', 'mif-bp-customizer' ) ),
                    'activity_comment' => array( 'part' => 10, 'descr' => __( 'Комментарий в ленте активности', 'mif-bp-customizer' ) ),
                    'new_media_update' => array( 'part' => 10, 'descr' => __( 'Новый документ', 'mif-bp-customizer' ) ),

                    'new_forum_post' => array( 'part' => 20, 'descr' => __( 'Сообщение в форуме', 'mif-bp-customizer' ) ),
                    'new_forum_topic' => array( 'part' => 20, 'descr' => __( 'Тема форума', 'mif-bp-customizer' ) ),

                    'new_blog_post' => array( 'part' => 30, 'descr' => __( 'Запись на сайте', 'mif-bp-customizer' ) ),
                    'new_blog_comment' => array( 'part' => 30, 'descr' => __( 'Комментарий на сайте', 'mif-bp-customizer' ) ),
                    'message' => array( 'part' => 30, 'descr' => __( 'Сообщение на странице курса', 'mif-bp-customizer' ) ),

                    'new_member' => array( 'part' => 40, 'descr' => __( 'Новый пользователь', 'mif-bp-customizer' ) ),
                    'friendship_created' => array( 'part' => 40, 'descr' => __( 'Кто-то подружился друг с другом', 'mif-bp-customizer' ) ),
                    'new_avatar' => array( 'part' => 40, 'descr' => __( 'Новый аватар', 'mif-bp-customizer' ) ),
                    'created_group' => array( 'part' => 40, 'descr' => __( 'Создание группы', 'mif-bp-customizer' ) ),
                    'joined_group' => array( 'part' => 40, 'descr' => __( 'Вступление в группу', 'mif-bp-customizer' ) ),

            );

            //
            // Здесь можно менять перечень типов активности из внешних плагинов
            //

            $data = apply_filters( 'mif_bpc_activity_get_activity_types_data', $data );

            $group = array(
                    10 => __( 'Сообщения и документы', 'mif-bp-customizer' ),
                    20 => __( 'Форумы', 'mif-bp-customizer' ),
                    30 => __( 'Сайты', 'mif-bp-customizer' ),
                    40 => __( 'Действия пользователей', 'mif-bp-customizer' ),
                    1000 => __( 'Прочее', 'mif-bp-customizer' ),
            );

            //
            // Здесь можно менять перечень групп типов активности из внешних плагинов
            //

            $group = apply_filters( 'mif_bpc_activity_get_activity_types_group', $group );
            
            global $bp, $wpdb;

            $sql = "SELECT DISTINCT type FROM {$bp->activity->table_name}";
            $activity_types = $wpdb->get_col( $sql ); 
            
            //
            // Здесь можно менять фактические типы активности из базы данных для дальнейшего сопоставления
            //

            $activity_types = apply_filters( 'mif_bpc_activity_get_activity_types_activity_types', $activity_types );

            foreach ( $data as $key => $item ) 
                if ( ! in_array( $key, $activity_types ) ) unset( $data[$key] );

            wp_cache_set( 'activity_types', $data );

        }

        if ( $mode == 'keys' ) return array_keys( $data );

        $arr = array();

        foreach ( $data as $key => $item ) {
            $arr[$item['part']]['descr'] = ( isset( $group[$item['part']] ) ) ? $group[$item['part']] : $group[1000];
            $arr[$item['part']]['items'][$key] = $item['descr'];
        }
        
        return $arr;

    }


    // 
    // Получить список исключенной активности для пользователя
    // 

    public function get_activity_exclude( $user_id = NULL )
    {
        // возвращает массив типов активности

        if ( $user_id === NULL ) $user_id = bp_loggedin_user_id();

        $ret = get_user_meta( $user_id, $this->meta_key, true );
        $ret_arr = explode( ', ', $ret );

        $unexcluded_types = $this->get_unexcluded_types();

        $ret_arr = array_diff( $ret_arr, $unexcluded_types );

        return apply_filters( 'mif_bpc_activity_stream_get_activity_exclude', $ret_arr, $user_id );
    }


    // 
    // Получить активности, которые нельзя блокировать
    // 

    public function get_unexcluded_types( $mode = 'arr' )
    {
        // возвращает массив или строку неблокинуемых типов активности

        // Зднесь можно менять список неблокируемых типов
        $unexcluded_types = apply_filters( 'mif_bpc_activity_stream_get_unexcluded_types', $this->unexcluded_types );
        $unexcluded_types = array_unique( $unexcluded_types ); // массив типов активности

        // вернуть типы активности в строке через запятую
        if ( ! $mode = 'arr' ) return implode( ',', $unexcluded_types );

        // вернуть массив типов активности
        return $unexcluded_types;
    }

}



?>
