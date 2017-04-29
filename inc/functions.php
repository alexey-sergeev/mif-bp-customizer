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


//
// Корректирует ответ о нахождении на странице друзей
//

function no_friends_page( $is_current_component, $component )
{
    if ( $component == 'friends' ) $is_current_component = false;

    remove_filter( 'bp_is_current_component', 'no_friends_page' );
    return $is_current_component;
}


//
// Возвращает fa-иконку для файла указанного типа
//

function get_file_icon( $type )
{
    $icon = 'file-o';
    if ( in_array( $type, array( 'doc', 'docx', 'odt', 'rtf' ) ) ) $icon = 'file-word-o';
    if ( in_array( $type, array( 'xls', 'xlsx', 'ods' ) ) ) $icon = 'file-excel-o';
    if ( in_array( $type, array( 'ppt', 'pptx', 'odp' ) ) ) $icon = 'file-powerpoint-o';
    if ( in_array( $type, array( 'pdf' ) ) ) $icon = 'file-pdf-o';
    if ( in_array( $type, array( 'txt' ) ) ) $icon = 'file-text-o';
    if ( in_array( $type, array( 'zip', 'rar', '7z' ) ) ) $icon = 'file-archive-o';
    if ( in_array( $type, array( 'png', 'gif', 'jpg', 'jpeg' ) ) ) $icon = 'file-image-o';
    if ( in_array( $type, array( 'mp3', 'ogg', 'wma' ) ) ) $icon = 'file-audio-o';

    return '<i class="fa fa-' . $icon . '"></i>';
}