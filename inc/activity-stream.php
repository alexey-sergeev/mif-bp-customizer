<?php

//
// Настройка режима "вся лента" ленты активности
// Основан на Facebook Like User Activity Stream For BuddyPress (Brajesh Singh)
//

defined( 'ABSPATH' ) || exit;


if ( mif_bpc_options( 'activity-stream' ) ) 
    new mif_bpc_activity_stream();


class mif_bpc_activity_stream {
  
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

        };

        // Включить блокировку пользователей
        if ( mif_bpc_options( 'banned-users' ) ) {

            add_action( 'bp_activity_setup_nav', array( $this, 'banned_users_nav' ) );

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

            
            // Убрать лишние типы активности, если такая возможность включена
            
            if ( mif_bpc_options( 'activity-exclude' ) ) {

                $activity_exclude = explode( ', ', $this->get_activity_exclude() );
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
        $activity_exclude = explode( ', ', $this->get_activity_exclude() );
        
        $out = '';

        $out .= '<p>' . __( 'Укажите элементы ленты активности, которые должны отображаться на вашей странице. Блокировка этих элементов также доступна и в самой ленте.', 'mif-bp-customizer' ) . '</p>';
        
        $out .= '<form class="nav-settings-form" method="POST">';

        $activity_types_data = $this->get_activity_types( 'table' );

        foreach ( (array) $activity_types_data as $activity_types_data_group ) {

            $out .= '<h4>' . $activity_types_data_group['descr'] . '</h4>';

            foreach ( (array) $activity_types_data_group['items'] as $key => $item ) {
                $checked = ( ! in_array( $key, $activity_exclude ) ) ? ' checked' : '';
                $out .= '<label><input type="checkbox" name="items[' . $key . ']"' . $checked . ' /> <span>' . $item . '</span></label>';
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
        $exclude_types = array_diff( $all_types, $form_types );

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

            $sql = $wpdb->prepare( "SELECT DISTINCT type FROM {$bp->activity->table_name}" );
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
                    'body_comment' => __( 'Список пользователей, для которых ограничены контакты с вами. Эти пользователи не могут оставлять комментарии и нажимать «Нравится» для ваших записей. Их информация не отображается в ленте активности вашей страницы. Изменить статус блокировки вы можете здесь или на странице самих пользователей.', 'mif-bp-customizer' ),
                    'can_edit' => true,
                    'members_usermeta' => 'banned_users',
                );

        new mif_bpc_members_page( $args );
    }
 


    // 
    // Получить список исключенной активности для пользователя
    // 

    public function get_activity_exclude( $user_id = NULL )
    {
        if ( $user_id === NULL ) $user_id = bp_loggedin_user_id();

        $ret = get_user_meta( $user_id, 'activity_exclude', true );

        return apply_filters( 'mif_bpc_activity_stream_get_activity_exclude', $ret, $user_id );
    }



    // 
    // Получить список заблокированных пользователей  для пользователя
    // 

    public function get_banned_users( $user_id = NULL )
    {
        if ( $user_id === NULL ) $user_id = bp_loggedin_user_id();

        $ret = get_user_meta( $user_id, 'banned_users', true );

        return apply_filters( 'mif_bpc_activity_stream_get_banned_users', $ret, $user_id );
    }




}



?>
