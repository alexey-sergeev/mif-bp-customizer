<?php
/*
 * Plugin Name: MIF BP Customizer
 * Plugin URI:  https://github.com/alexey-sergeev/mif-bp-customizer
 * Author:      Alexey Sergeev
 * Author URI:  https://github.com/alexey-sergeev
 * License:     GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Description: Плагин BuddyPress для тонкой настройки социальной сети.
 * Version:     0.0.1
 * Text Domain: mif-bp-customizer
 * Domain Path: /lang/
 */

defined( 'ABSPATH' ) || exit;

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


//
// Настройка профиля как домашней страницы
//
//

add_action( 'wp', 'mif_bp_profile_as_homepage' );

function mif_bp_profile_as_homepage()
{
	global $bp;

    if ( is_user_logged_in() && bp_is_front_page() ) {
        wp_redirect( $bp->loggedin_user->domain );
    }

}

add_action( 'wp_logout', 'mif_logout_redirection' );

function mif_logout_redirection()
{
	global $bp;
	$redirect = $bp->root_domain;
	wp_logout_url( $redirect );	
}



?>