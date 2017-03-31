<?php

//
// Функции
//
//


defined( 'ABSPATH' ) || exit;



//
// Выводит всплывающие подсказки с меню по массиву
// $arr = array(
//             array( 'href' => $exclude_url, 'descr' => __( 'Не показывать такие записи', 'mif-bp-customizer' ), 'class' => 'ajax', 'data' => array( 'exclude' => $param )  ),
//             array( 'href' => $settings_url, 'descr' => __( 'Настройка', 'mif-bp-customizer' ) ),
//         );
//

function mif_bpc_hint( $arr = NULL )
{
    if ( $arr == NULL ) return;

    $out = '';

    $out .= '<div class="mif-bpc-hint"><div>';

    foreach ( (array) $arr as $item ) {

        $param = '';
        if ( isset( $item['data'] ) && is_array( $item['data'] ) )
            foreach ( $item['data'] as $key => $value )
                $param = ' '. 'data-' . $key . '="' . $value . '"';

        $class = ( isset( $item['class'] ) ) ? ' class="' . $item['class'] . '"' : '';
        $out .= '<a href="' . $item['href'] . '"' . $class . $param . '>' . $item['descr'] . '</a>';

    };

    $out .= '</div></div>';

    return $out;
}


//
// Получить метку времени последней активности пользователя
//

function mif_bpc_get_last_activity_timestamp( $user_id )
{
    if ( ! $timestamp = wp_cache_get( 'last_activity_timestamp', $user_id ) ) {

        $last_activity = bp_get_user_last_activity( $user_id );

        if ( isset( $last_activity ) ) {

            $time_chunks = explode( ':', str_replace( ' ', ':', $last_activity ) );
            $date_chunks = explode( '-', str_replace( ' ', '-', $last_activity ) );
            $timestamp  = gmmktime( (int) $time_chunks[1], (int) $time_chunks[2], (int) $time_chunks[3], (int) $date_chunks[1], (int) $date_chunks[2], (int) $date_chunks[0] );

        } else {

            $timestamp = 0;

        }
        
        wp_cache_set( 'last_activity_timestamp', $timestamp, $user_id );

    }

    return $timestamp;
}


//
// Вычисляет разницу между меткой времени и текущим моментом
//

function timestamp_to_now( $timestamp, $mode = NULL )
{
    $now = time();
    $res = $now - $timestamp;
    if ( $mode == 'day' ) $res = floor( $res / 86400 );  // 24 * 60 * 60

    return $res;
}