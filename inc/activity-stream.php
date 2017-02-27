<?php

//
// Настройка режима "вся лента" ленты активности
// Основан на Facebook Like User Activity Stream For BuddyPress (Brajesh Singh)
//

defined( 'ABSPATH' ) || exit;


if ( mif_bpc_options( 'activity-stream' ) ) 
    new mif_bpc_activity_stream();


class mif_bpc_activity_stream {

    //
    // Пользователи, которых нельзя блокировать
    //

    public $unbanned_users = array( 'admin' );
    
    //
    // Типы активности, которые нельзя блокировать
    //

    public $unexcluded_types = array( 'activity_update' );


    function __construct()
    {
        // Включить особый вид ленты активности        
        add_action( 'bp_activity_setup_nav', array( $this, 'activity_nav' ) );
        add_filter( 'bp_activity_get_where_conditions', array( $this, 'where_conditions' ), 2, 2 );
        add_action( 'bp_before_member_activity_post_form', array( $this,'show_post_form' ) );

        // Включить настройку типов записей ленты активности
        if ( mif_bpc_options( 'activity-exclude' ) ) {

            add_action( 'bp_activity_setup_nav', array( $this, 'activity_exclude_nav' ) );
            add_action( 'bp_init', array( $this, 'activity_exclude_helper' ) );

            add_action( 'bp_activity_entry_meta', array( $this, 'exclude_button' ) );
            add_action( 'wp_print_scripts', array( $this, 'load_js_helper' ) );            				
            add_action( 'wp_ajax_disable-activity-type-button', array( $this, 'exclude_button_ajax_helper' ) );

        };

        // Включить блокировку пользователей
        if ( mif_bpc_options( 'banned-users' ) ) {

            add_action( 'bp_activity_setup_nav', array( $this, 'banned_users_nav' ) );

            add_action( 'bp_member_header_actions', array( $this, 'banned_user_button' ), 100 );
            add_action( 'wp_print_scripts', array( $this, 'load_js_helper' ) );            				
            add_action( 'wp_ajax_banned-user-button', array( $this, 'banned_user_button_ajax_helper' ) );

            add_action( 'bp_get_add_friend_button', array( $this, 'remove_friendship_button' ) );
            add_action( 'bp_activity_can_comment', array( $this, 'remove_comment_button' ) );
            

        }
    }
    
    
    //
    // Настройка вкладок активности на странице пользователя
    //
    //

    function activity_nav()
    {
        global $bp;

        $activity_link = bp_core_get_user_domain( bp_displayed_user_id() ) . $bp->activity->slug . '/';

        if ( bp_is_my_profile() ) {

            // Вся лента

            $sub_nav = array(  
                    'name' => __( 'Вся лента', 'mif-bp-customizer' ), 
                    'slug' => 'all-stream', 
                    'subnav_slug' => 'all-stream',
                    'parent_url' => $activity_link, 
                    'parent_slug' => $bp->activity->slug, 
                    'screen_function' => array( $this, 'activity_screen' ), 
                    'position' => 0,
                    'user_has_access'=>  bp_is_my_profile() 
                );

            bp_core_new_subnav_item( $sub_nav );
            bp_core_new_nav_default( $sub_nav );


            // Личное - сделать второй вкладкой

            $aaa = $bp->members->nav->get_secondary( array( 'parent_slug' => 'activity', 'slug' => 'just-me' ), false );
            $name = $aaa['activity/just-me']['name'];

            bp_core_remove_subnav_item( 'activity', 'just-me' );

            $sub_nav = array(
                    'name'            => $name,
                    'slug'            => 'personal',
                    'parent_url'      => $activity_link,
                    'parent_slug'     => $bp->activity->slug,
                    'screen_function' => 'bp_activity_screen_my_activity',
                    'position'        => 10
                );

            bp_core_new_subnav_item( $sub_nav );
            
        }

        // Убрать упоминания

        bp_core_remove_subnav_item( 'activity', 'mentions' );

        // Добавить сайты

        if ( is_multisite() ) {

            $sub_nav = array(  
                    'name' => __( 'Сайты', 'mif-bp-customizer' ), 
                    'slug' => 'sites', 
                    'parent_url' => $activity_link, 
                    'parent_slug' => $bp->activity->slug, 
                    'screen_function' => array( $this, 'activity_screen' ), 
                    'position' => 60,
                    // 'user_has_access'=>  bp_is_my_profile() 
                );

            bp_core_new_subnav_item( $sub_nav );

        }

        // Добавить курсы

        if ( function_exists( 'lms_get_mycourses' ) ) {

            $sub_nav = array(  
                    'name' => __( 'Курсы', 'mif-bp-customizer' ), 
                    'slug' => 'courses', 
                    'parent_url' => $activity_link, 
                    'parent_slug' => $bp->activity->slug, 
                    'screen_function' => array( $this, 'activity_screen' ), 
                    'position' => 60,
                    // 'user_has_access'=>  bp_is_my_profile() 
                );

            bp_core_new_subnav_item( $sub_nav );

        }

    }


    //  
    // Показывает ленту активности в новых вкладках
    //  

    public function activity_screen()
    {
        bp_core_load_template( apply_filters( 'bp_activity_template_mystream_activity', 'members/single/home' ) ); 
    }



    //  
    // Показывает форму публикации статуса на странице профиля пользователя
    //  
  
    public function show_post_form()
    {
        if ( is_user_logged_in() && bp_is_my_profile() &&  bp_is_activity_component() && bp_is_current_action( 'all-stream' ) ) {

            locate_template( array( 'activity/post-form.php'), true ) ;

        } 
    }



    //  
    // Настройка правил отображения элементов активности в лентах пользователей
    //  

    public function where_conditions( $where, $r )
    {
        global $bp;
        
        $current_user_id = bp_displayed_user_id();
        $filter_sql = '';

        if ( bp_is_my_profile() ) {

            // Вся лента (моя страница)
            
            if ( $r['scope'] == 'all-stream' ) {

                $filter_sql = '(';

                // Я и мои друзья

                $friends = (array) friends_get_friend_user_ids( $current_user_id );
                $friends[] = $current_user_id;
                $filter_sql .= '(a.user_id IN (' . implode( ',', $friends ) . ') AND a.hide_sitewide = 0)';

                // Мои группы

                $groups = groups_get_user_groups( $current_user_id );
                $component = $bp->groups->id;
                if ( isset( $groups['groups'] ) ) $filter_sql .= ' OR ((a.component=\'' . $component . '\') AND (a.item_id IN (' . implode( ',', $groups['groups'] ) . ')))';

                // Мои курсы

                if ( function_exists( 'lms_get_mycourses' ) ) {
                    $courses = lms_get_mycourses( $current_user_id );
                    if ( $courses ) $filter_sql .= ' OR ((a.component=\'course\') AND (a.item_id IN (' . $courses . ')))';
                } 

                // Мои сайты

                if ( is_multisite() ) {
                    $blogs = get_blogs_of_user( $current_user_id );
                    if ( $blogs ) $filter_sql .= ' OR ((a.component=\'blogs\') AND (a.item_id IN (' . implode( ',', array_keys( $blogs ) ) . ')))';
                } 

                // Мои упоминания

                $nicename = bp_core_get_username( $current_user_id );
                $filter_sql .= ' OR (a.content LIKE \'%@' . $nicename . '<%\')';

                // Избранное

                $favorites = bp_get_user_meta( $current_user_id, 'bp_favorite_activities', true );
                if ($favorites ) $filter_sql .= ' OR (a.id IN (' . implode( ',', array_keys( $favorites ) ) . '))';

                $filter_sql .= ')';

                $where['filter_sql'] = $filter_sql;
                unset( $where['hidden_sql'] );

            }

            // Сайты (моя страница)

            if ( $r['scope'] == 'sites' ) {

                $blogs = get_blogs_of_user( $current_user_id );
                if ( $blogs ) $filter_sql = '(a.component=\'blogs\') AND (a.item_id IN (' . implode( ',', array_keys( $blogs ) ) . '))';
                $where['filter_sql'] = $filter_sql;
                unset( $where['hidden_sql'] );

            }

            // Курсы (моя страница)

            if ( $r['scope'] == 'courses' ) {

                if ( function_exists( 'lms_get_mycourses' ) ) {

                    $courses = lms_get_mycourses( $current_user_id );
                    if ( $courses ) $filter_sql = '(a.component=\'course\') AND (a.item_id IN (' . $courses . '))';
                    $where['filter_sql'] = $filter_sql;
                    unset( $where['hidden_sql'] );

                } 

            }

            
            // Убрать на странице "Вся лена" лишние типы активности, если такая возможность включена
            
            if ( mif_bpc_options( 'activity-exclude' ) && bp_is_current_action( 'all-stream' ) ) {
            // if ( mif_bpc_options( 'activity-exclude' ) ) {

                $activity_exclude = $this->get_activity_exclude();
                foreach ( $activity_exclude as $key => $item ) $activity_exclude[$key] = '\'' . trim( $item ) . '\'';

                if ( $activity_exclude ) $where['activity_exclude'] = 'a.type NOT IN (' . implode( ',', $activity_exclude ) . ')';

            }
            
            // Убрать заблокированных пользователей, если такая возможность включена

            if ( mif_bpc_options( 'banned-users' ) ) {
                
                $banned_users = $this->get_banned_users();
                if ( $banned_users ) $where['banned_users'] = 'a.user_id NOT IN (' . $banned_users . ')';

            }

        } else {

            // Избранное (чужая страница)

            if ( $r['scope'] == 'favorites' ) {

            	
                $favs = bp_activity_get_user_favorites( $current_user_id );
            	$fav_ids = implode( ',', (array) $favs );

                $or = '';
              
                $groups = groups_get_user_groups( bp_loggedin_user_id() );
                $component = $bp->groups->id;
                if ( $groups ) $or .= ' OR (a.component=\'' . $component . '\' AND a.item_id IN (' . implode( ',', $groups['groups'] ) . '))';

                if ( function_exists( 'lms_get_mycourses' ) ) {
                    $courses = lms_get_mycourses( bp_loggedin_user_id() );
                    if ( $courses ) $or .= ' OR (a.component=\'course\' AND a.item_id IN (' . $courses . '))';
                }

                $filter_sql = '(a.id IN (' . $fav_ids . ')) AND (a.hide_sitewide = 0' . $or . ')';

                $where['filter_sql'] = $filter_sql;
                unset( $where['scope_query_sql'] );

            }

            // Группы (чужая страница)

            if ( $r['scope'] == 'groups' ) {

                $groups = groups_get_user_groups( bp_loggedin_user_id() );
                $component = $bp->groups->id;
                if ( bp_loggedin_user_id() && $groups ) {
                    $filter_sql = '(a.component=\'' . $component . '\') AND (a.user_id=\'' . $current_user_id . '\') AND ((a.hide_sitewide = 0) OR (a.item_id IN (' . implode( ',', $groups['groups'] ) . ')))';
                } else {
                    $filter_sql = '(a.component=\'' . $component . '\') AND (a.user_id=\'' . $current_user_id . '\') AND (a.hide_sitewide = 0)';
                }

                $where['filter_sql'] = $filter_sql;
                unset( $where['scope_query_sql'] );

            }

            // Сайты (чужая страница)

            if ( $r['scope'] == 'sites' ) {

                $filter_sql = '(a.component=\'blogs\') AND (a.user_id=\'' . $current_user_id . '\')';
                $where['filter_sql'] = $filter_sql;

            }

            // Курсы (чужая страница)

            if ( $r['scope'] == 'courses' ) {

                if ( function_exists( 'lms_get_mycourses' ) ) {

                    $courses = lms_get_mycourses( bp_loggedin_user_id() );
                    if ( bp_loggedin_user_id() && $courses ) {
                        $filter_sql = '(a.component=\'course\') AND (a.user_id=\'' . $current_user_id . '\') AND ((a.hide_sitewide = 0) OR (a.item_id IN (' . $courses . ')))';
                    } else {
                        $filter_sql = '(a.component=\'course\') AND (a.user_id=\'' . $current_user_id . '\') AND (a.hide_sitewide = 0)';
                    }

                    $where['filter_sql'] = $filter_sql;
                    unset( $where['hidden_sql'] );

                } 

            }

        }

        //
        // Здесь можно уточнить правила отображения элементов
        //

        return apply_filters( 'mif_bpc_activity_stream_where_conditions', $where, $r );
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

        echo '<div class="right disable-activity-type"><a href="" class="button bp-secondary-action disable-activity-type"><strong>&middot;&middot;&middot;</strong></a>' . mif_bpc_hint( $arr ) . '</div>';

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
        
        if ( update_user_meta( bp_loggedin_user_id(), 'activity_exclude', implode( ', ', $activity_exclude ) ) ) {

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

        if ( update_user_meta( bp_loggedin_user_id(), 'activity_exclude', implode( ', ', $exclude_types ) ) ) {
            
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
    // Страница блокировки пользователей
    // 
    // 

    function banned_users_nav()
    {
        $args = array(
                    'name' => __( 'Блокировки', 'mif-bp-customizer' ),
                    'slug' => 'banned-members',
                    'position' => 60,
                    'title' => __( 'Блокировка пользователей', 'mif-bp-customizer' ),
                    'body_comment' => __( 'Список пользователей, для которых ограничены контакты с вами. Эти пользователи не могут предлагать дружбу, оставлять комментарии и нажимать «Нравится» для ваших записей. Их информация не отображается в ленте активности вашей страницы. Изменить статус блокировки вы можете здесь или на странице самих пользователей.', 'mif-bp-customizer' ),
                    'can_edit' => true,
                    'members_usermeta' => 'banned_users',
                    'exclude_users' => $this->get_unbanned_users(),
                );

        new mif_bpc_members_page( $args );
    }
 

    // 
    // Кнопка блокировки пользователей на странице пользователей
    // 
    // 

    function banned_user_button()
    {

        if ( bp_is_my_profile() ) return;

        $user_id = bp_displayed_user_id();
        $unbanned_users = $this->get_unbanned_users( $mode = 'ids_arr' );

        if ( in_array( $user_id, $unbanned_users ) ) return;


        global $bp;

        $banned_url = $bp->loggedin_user->domain . $bp->profile->slug . '/banned-members';
        $banned_url_request = wp_nonce_url( $settings_url . '/banned-members/requests/' . $user_id . '/', 'mif_bpc_banned_user_button' );

        $caption = $this->get_caption();

        $arr = array(
                    array( 'href' => $banned_url_request, 'descr' => $caption, 'class' => 'ajax', 'data' => array( 'userid' => $user_id ) ),
                    array( 'href' => $banned_url, 'descr' => __( 'Настройка', 'mif-bp-customizer' ) ),
                );

        $none = ( $this->is_banned() ) ? '' : ' none';

        echo '<div class="right"><div class="right generic-button banned-users"><a href="" class="gray banned-users"><strong>&middot;&middot;&middot;</strong></a>' . mif_bpc_hint( $arr ) . '</div><i class="fa fa-ban fa-2x right banned-users icon' . $none . '"></i></div>';

    }
 

    public function banned_user_button_ajax_helper()
    {
        check_ajax_referer( 'mif_bpc_banned_user_button' );

        if ( ! mif_bpc_options( 'banned-users' ) ) wp_die();

        $user_id = (int) $_POST['userid'];
        $current_user_id = bp_loggedin_user_id();
        $banned_users = $this->get_banned_users( $current_user_id, 'arr' );
        
        if ( in_array( $user_id, $banned_users ) ) {

            $banned_users = array_diff( $banned_users, array( $user_id ) );

        } else {

            $banned_users[] = $user_id;
            sort( $banned_users );

        }

        update_user_meta( $current_user_id, 'banned_users', implode( ',', $banned_users ) );
        $caption = $this->get_caption();

        echo $caption;


        // $exclude = sanitize_text_field( $_POST['exclude'] );
        // $unexcluded_types = $this->get_unexcluded_types();
        // $activity_exclude = $this->get_activity_exclude();


        // if ( in_array( $exclude, $unexcluded_types ) ) wp_die();
        // if ( in_array( $exclude, $activity_exclude ) ) wp_die();

        // $activity_exclude[] = $exclude;
        
        // if ( update_user_meta( bp_loggedin_user_id(), 'activity_exclude', implode( ', ', $activity_exclude ) ) ) {

        //     echo $exclude;
        
        // }
        
        wp_die();
    }


    function get_caption()
    {
        $caption = ( $this->is_banned() ) ? __( 'Снять ограничения', 'mif-bp-customizer' ) : __( 'Ограничить контакты', 'mif-bp-customizer' );

        return $caption;
    }


    // 
    // Удалить кнопку "Добавить в друзья", если пользователь тебя заблокировал
    // 

    public function remove_friendship_button( $button )
    {
        $target_user_id = bp_get_potential_friend_id();
        $user_id = bp_loggedin_user_id();
        
        if ( $this->is_banned( $target_user_id, $user_id ) ) $button = array();

        return $button;
    }


    // 
    // Удалить кнопку "Оставить комментарий", если пользователь тебя заблокировал
    // 

    public function remove_comment_button( $can_comment )
    {
        $target_user_id = bp_get_activity_user_id();
        $user_id = bp_loggedin_user_id();
        
        if ( $this->is_banned( $target_user_id, $user_id ) ) $can_comment = false;

        return $can_comment;
    }





    // 
    // Получить список исключенной активности для пользователя
    // 

    public function get_activity_exclude( $user_id = NULL )
    {
        // возвращает массив типов активности

        if ( $user_id === NULL ) $user_id = bp_loggedin_user_id();

        $ret = get_user_meta( $user_id, 'activity_exclude', true );
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



    // 
    // Получить список заблокированных пользователей для пользователя
    // 

    public function get_banned_users( $user_id = NULL, $mode = 'str' )
    {
        // возвращает строку id через запятую

        if ( $user_id === NULL ) $user_id = bp_loggedin_user_id();
        $ret = get_user_meta( $user_id, 'banned_users', true );

        $ret_arr = explode( ',', $ret );
        foreach ( (array) $ret_arr as $key => $item ) $ret_arr[$key] = (int) $item;

        $unbanned_users = $this->get_unbanned_users( 'ids_arr' );

        $ret_arr = array_diff( $ret_arr, $unbanned_users );

        // Здесь можно поменять id заблокированных пользователей в массиве
        $ret_arr = apply_filters( 'mif_bpc_activity_stream_get_banned_users_arr', $ret_arr, $user_id );

        if ( $mode == 'arr' ) return $ret_arr;

        $ret = implode( ',', $ret_arr );

        return apply_filters( 'mif_bpc_activity_stream_get_banned_users', $ret, $user_id );
    }


    // 
    // Проверяет, является ли user2 заблокироанным пользователем у пользователя user
    // 

    public function is_banned( $user_id = NULL, $user2_id = NULL )
    {
        if ( $user_id == NULL ) $user_id = bp_loggedin_user_id();
        if ( $user2_id == NULL ) $user2_id = bp_displayed_user_id();

        $banned_users = $this->get_banned_users( $user_id, 'arr' );

        $ret = ( in_array( $user2_id, $banned_users ) ) ? true : false;

        return apply_filters( 'mif_bpc_activity_stream_get_banned_users', $ret, $user_id, $user2_id );
    }


    // 
    // Получить пользователей, которых нельзя блокировать
    // 

    public function get_unbanned_users( $mode = 'ids' )
    {
        // возвращает массив или строку пользователей, которых нельзя блокировать
        
        // Здесь можно менять список неблокируемых пользователей (массив nicenames)
        $unbanned_users = apply_filters( 'mif_bpc_activity_stream_get_unbanned_users', $this->unbanned_users );
        $unbanned_users = array_unique( $unbanned_users ); // массив nicenames

        if ( $mode == 'ids' || $mode = 'ids_arr' ) {

            if ( ! $unbanned_users_ids_arr = wp_cache_get( 'unbanned_users' ) ) {

                $unbanned_users_ids_arr = array();

                foreach ( (array) $unbanned_users as $item ) {

                    if ( trim( $item ) == '' ) continue;

                    $user = get_user_by( 'slug', $item ); 
                    if ( is_object( $user ) ) $unbanned_users_ids_arr[] = $user->ID;

                }
            
                // Здесь можно менять список неблокируемых пользователей (массив id)
                $unbanned_users_ids_arr = apply_filters( 'mif_bpc_activity_stream_get_unbanned_users_ids', $unbanned_users_ids_arr );

                wp_cache_set( 'unbanned_users', $unbanned_users_ids_arr );

            }

            // вернуть id в массиве
            if ( $mode == 'ids_arr' ) return $unbanned_users_ids_arr;

            // вернуть id в строке через запятую
            if ( $mode == 'ids' ) return implode( ',', $unbanned_users_ids_arr );

        }

        // вернуть массив nicenames
        return $unbanned_users;
    }




}



?>