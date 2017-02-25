<?php
/*
 * Plugin Name: MIF BP Customizer
 * Plugin URI:  https://github.com/alexey-sergeev/mif-bp-customizer
 * Author:      Alexey Sergeev
 * Author URI:  https://github.com/alexey-sergeev
 * License:     MIT License
 * Description: Плагин BuddyPress для тонкой настройки социальной сети.
 * Version:     0.0.1
 * Text Domain: mif-bp-customizer
 * Domain Path: /lang/
 */

defined( 'ABSPATH' ) || exit;

include_once dirname( __FILE__ ) . '/classes/members-page.php';

include_once dirname( __FILE__ ) . '/inc/profile-as-homepage.php';
include_once dirname( __FILE__ ) . '/inc/admin-settings-page.php';
include_once dirname( __FILE__ ) . '/inc/custom-background.php';
include_once dirname( __FILE__ ) . '/inc/edit-group-slug.php';
include_once dirname( __FILE__ ) . '/inc/groups-widget.php';
include_once dirname( __FILE__ ) . '/inc/members-widget.php';
// include_once dirname( __FILE__ ) . '/inc/group-tags.php';
include_once dirname( __FILE__ ) . '/inc/activity-stream.php';





 
// Проверка опций
// 
// 

function mif_bpc_options( $key )
{
    $ret = false;
    $args = get_mif_bpc_options();

    if ( isset( $args[$key] ) ) $ret = $args[$key];

    return $ret;
}  

// 
// Получить опции
// 
// 

function get_mif_bpc_options()
{
    $default = array(
                'profile-as-homepage' => true,
                'custom-background' => false,
                'edit-group-slug' => true,
                'groups-widget' => true,
                'members-widget' => true,
                'group-tags' => true,
                'activity-stream' => true,
                'activity-exclude' => true,
                'banned-users' => true,
            );

    foreach ( $default as $key => $value ) $args[$key] = get_option( $key, $default[$key] );

    return $args;
}





//
// Подключаем свой файл CSS
//
//

add_action( 'wp_enqueue_scripts', 'mif_bp_customizer_styles' );

function mif_bp_customizer_styles() 
{
	wp_register_style( 'mif-bp-customizer-styles', plugins_url( 'mif-bp-customizer-styles.css', __FILE__ ) );
	wp_enqueue_style( 'mif-bp-customizer-styles' );
}










if ( ! function_exists( 'p' ) ) {

    function p( $data )
    {
        print_r( '<pre>' );
        print_r( $data );
        print_r( '</pre>' );
    }

}



//
// Удаляем лишние вкладки профиля (сайты, уведомления)
// Корректируем названия некоторых вкладок
//

// add_action( 'bp_init', 'mif_bp_nav_customize' );

function mif_bp_nav_customize()
{
	bp_core_remove_nav_item( 'blogs' );
}

add_filter( 'bp_get_options_nav_change-avatar', 'mif_bp_nav_change_avatar_customize', 10, 3 );

function mif_bp_nav_change_avatar_customize( $link, $subnav_item, $selected_item )
{
	$txt = __( 'Аватар', 'mif-bp-customizer' );
	return preg_replace('/(<li.+><a.+>).+(<\/a><\/li>)/isU', "$1" . $txt . "$2", $link );
}

add_filter( 'bp_get_options_nav_change-cover-image', 'mif_bp_nav_change_cover_image_customize', 10, 3 );

function mif_bp_nav_change_cover_image_customize( $link, $subnav_item, $selected_item )
{
	$txt = __( 'Обложка', 'mif-bp-customizer' );
	return preg_replace('/(<li.+><a.+>).+(<\/a><\/li>)/isU', "$1" . $txt . "$2", $link );
}

?>