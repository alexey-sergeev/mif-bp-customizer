<?php

//
// Настройка механизма подписчиков
// 
//


defined( 'ABSPATH' ) || exit;

if ( mif_bpc_options( 'followers' ) ) {

    global $mif_bpc_followers;
    $mif_bpc_followers = new mif_bpc_followers();

}


class mif_bpc_followers {

    //
    // Механизм подписчиков - подписчиками считаются те, кто имеет статус неподтвержденной дружбы 
    //

    function __construct()
    {

        // Добавить записи читаемых пользователей в ленту активности
        add_filter( 'mif_bpc_activity_stream_friends', array( $this, 'activity_stream' ), 10, 2 );

        // Страницы подписчиков и читаемых
        add_action( 'bp_activity_setup_nav', array( $this, 'followers_nav' ) );
        add_action( 'bp_activity_setup_nav', array( $this, 'subscriptions_nav' ) );

        // Кнопки в списке пользователей
        add_action( 'bp_get_add_friend_button', array( $this, 'friend_button' ) );
        add_action( 'wp_print_scripts', array( $this, 'load_js_helper' ) );            				
        add_action( 'wp_ajax_awaiting-response', array( $this, 'awaiting_response_ajax_helper' ) );
        add_action( 'friends_friendship_post_delete', array( $this, 'friendship_delete' ), 10, 2 );



        // add_action( 'bp_get_add_friend_button', array( $this, 'remove_old_friend_button' ) );
        // add_action( 'bp_directory_members_actions', array( $this, 'add_new_friend_button' ) );
    
    }



    function friend_button( $button )
    {

		switch ( $button['id'] ) {

			case 'awaiting_response' :
                $button['link_text'] = __( 'Принять дружбу', 'mif-bp-customizer' );
                $button['wrapper_class'] = 'custom-friendship-button awaiting_response_friend';
                $button['link_href'] = bp_get_friend_accept_request_link();
				break;

			// case 'is_friend' :
            //     $button['wrapper_class'] = 'custom-friendship-button is_friend';
			// 	break;

			case 'pending' :
                $button['link_text'] = __( 'Отписаться', 'mif-bp-customizer' );
                // $button['link_href'] = bp_get_friend_accept_request_link();
				break;

        }
        
                // p($button);

        return apply_filters( 'mif_bpc_followers_friend_button', $button );
    }


    function awaiting_response_ajax_helper()
    {
        check_ajax_referer( 'friends_accept_friendship' );
        
        $user_id = (int) $_POST['user_id'];

        $friendship_id = friends_get_friendship_id( $user_id, bp_loggedin_user_id() );
        if ( friends_accept_friendship( $friendship_id ) ) bp_add_friend_button( $user_id );
        
        wp_die();
    }


    function load_js_helper()
    {
        wp_register_script( 'mif_bpc_followers', plugins_url( '../js/followers.js', __FILE__ ) );  
        wp_enqueue_script( 'mif_bpc_followers' );
    }


    //
    // При удалении - переводить пользователей в статус фолловеров
    //

    function friendship_delete( $initiator_userid, $friend_userid )
    {
        friends_add_friend( $friend_userid, $initiator_userid );
    }


    // // 
    // // Добавить новые кнопки в списке пользователей
    // // 

    // function add_new_friend_button()
    // {
    //     $out = '';

    //     $is_friend = bp_is_friend( bp_get_member_user_id() );
	// 	if ( empty( $is_friend ) ) return false;

	// 	switch ( $is_friend ) {
	// 		case 'awaiting_response' :
    //             $out .= '<a class="button accept" href="' . bp_get_friend_accept_request_link() . '">' . __( 'Принять дружбу', 'mif-bp-customizer' ) . '</a>';
	// 			break;
    //     }

    //     echo $out;
    // }


    // // 
    // // Удалить старые кнопки в списке пользователей
    // // 

    // function remove_old_friend_button( $button )
    // {

	// 	switch ( $button['id'] ) {
	// 		case 'awaiting_response' :
    //             $button = false;
	// 			break;

    //     }
        

    //     return apply_filters( 'mif_bpc_followers_friend_button', $button );
    // }


    // 
    // Добавить записи читаемых пользователей в ленту активности
    // 

    function activity_stream( $friends, $user_id )
    {
        $followers = $this->get_subscriptions_ids( $user_id );
        $friends = array_merge( $friends, $followers );

        return apply_filters( 'mif_bpc_followers_activity_stream', $friends, $user_id );
    }


    // 
    // Получить массив ID подписчиков
    // 

    function get_subscriptions_ids( $user_id = NULL )
    {
        if ( $user_id == NULL ) $user_id = bp_displayed_user_id();

        $subscriptions = (array) BP_Friends_Friendship::get_friendships( $user_id, array( 'initiator_user_id' => $user_id, 'is_confirmed' => 0 ) );

        $arr = array();
        foreach ( $subscriptions as $item ) $arr[] = $item->friend_user_id;

        return $arr;
    }


    // 
    // Получить массив ID фолловеров
    // 

    function get_followers_ids( $user_id = NULL )
    {
        if ( $user_id == NULL ) $user_id = bp_displayed_user_id();

        $followers = (array) BP_Friends_Friendship::get_friendships( $user_id, array( 'friend_user_id' => $user_id, 'is_confirmed' => 0 ) );

        $arr = array();
        foreach ( $followers as $follower ) $arr[] = $follower->initiator_user_id;

        return $arr;
    }



    // 
    // Страница подписчиков
    // 

    function followers_nav()
    {
        global $bp;

        $parent_url = $bp->displayed_user->domain . $bp->friends->slug . '/';
        $parent_slug = $bp->friends->slug;

        $sub_nav = array(  
                'name' => __( 'Подписчики', 'mif-bp-customizer' ), 
                'slug' => 'followers', 
                'parent_url' => $parent_url, 
                'parent_slug' => $parent_slug, 
                'screen_function' => array( $this, 'screen' ), 
                'position' => 50,
                // 'user_has_access'=>  bp_is_my_profile() 
            );

        bp_core_new_subnav_item( $sub_nav );
       
    }



    // 
    // Страница тех, на кого подписан
    // 

    function subscriptions_nav()
    {
        global $bp;

        $parent_url = $bp->displayed_user->domain . $bp->friends->slug . '/';
        $parent_slug = $bp->friends->slug;

        $sub_nav = array(  
                'name' => __( 'Читаю', 'mif-bp-customizer' ), 
                'slug' => 'subscriptions', 
                'parent_url' => $parent_url, 
                'parent_slug' => $parent_slug, 
                'screen_function' => array( $this, 'screen' ), 
                'position' => 60,
                // 'user_has_access'=>  bp_is_my_profile() 
            );

        bp_core_new_subnav_item( $sub_nav );
       
    }

    //
    // Содержимое страниц
    //

    function screen()
    {
        global $bp;
        // add_action( 'bp_template_title', array( $this, 'title' ) );
        add_action( 'bp_template_content', array( $this, 'body' ) );
        bp_core_load_template( apply_filters( 'bp_core_template_plugin', 'members/single/plugins' ) );
    }

    function body()
    {
        // $out = '';
        global $mif_bpc_followers;

        add_filter( 'bp_ajax_querystring', array( $mif_bpc_followers, 'members_param' ), 100, 2 );
        bp_get_template_part( 'members/members-loop' ) ;
        remove_filter( 'bp_ajax_querystring', array( $mif_bpc_followers, 'members_param' ) );
    }

    function members_param( $members_param, $object )
    {
        global $bp;

        if ( $bp->current_action == 'subscriptions' ) {

            $members_param = array( 'include' => implode( ',', $this->get_subscriptions_ids() ) );

        } elseif ( $bp->current_action == 'followers' ) {

            $members_param = array( 'include' => implode( ',', $this->get_followers_ids() ) );

        }

        add_filter( 'bp_is_current_component', 'no_friends_page', 10, 2 );

        return apply_filters( 'mif_bpc_followers_members_param', $members_param, $bp->current_action ) ;
    }    



}

?>