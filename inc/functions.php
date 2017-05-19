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

function get_file_icon( $file, $class = '' )
{
    $default = 'file-o';
    $icon = $default;

    $ext = end( explode( ".", $file ) );

    if ( in_array( $ext, array( 'doc', 'docx', 'odt', 'rtf' ) ) ) $icon = 'file-word-o';
    if ( in_array( $ext, array( 'xls', 'xlsx', 'ods' ) ) ) $icon = 'file-excel-o';
    if ( in_array( $ext, array( 'ppt', 'pptx', 'odp' ) ) ) $icon = 'file-powerpoint-o';
    if ( in_array( $ext, array( 'pdf' ) ) ) $icon = 'file-pdf-o';
    if ( in_array( $ext, array( 'txt' ) ) ) $icon = 'file-text-o';
    if ( in_array( $ext, array( 'zip', 'rar', '7z' ) ) ) $icon = 'file-archive-o';
    if ( in_array( $ext, array( 'png', 'gif', 'jpg', 'jpeg' ) ) ) $icon = 'file-image-o';
    if ( in_array( $ext, array( 'mp3', 'ogg', 'wma' ) ) ) $icon = 'file-audio-o';
    if ( in_array( $ext, array( 'html', 'htm', 'css', 'cpp', 'pas', 'js' ) ) ) $icon = 'file-code-o';

    if ( $icon == $default ) {

        $arr = explode( ':', $file );
        if ( in_array( $arr[0], array( 'http', 'https' ) ) ) $icon = 'globe';

    }

    if ( $class ) $class = ' ' . $class;

    return '<i class="fa fa-' . $icon . $class . '"></i>';
}



//
// Возвращает имя пользователя так, как это делается в цикле BP
//

function mif_bpc_get_member_name( $user_id ) 
{
    if ( empty( $user_id ) ) return false;

    $user = get_user_by( 'id', $user_id );

    $name_stack = array(
                    'display_name' => $user->display_name,
                    'user_nicename' => $user->user_nicename,
                    'user_login' => $user->user_login
                    );

    $name = '';

    foreach ( $name_stack as $source ) {

        if ( ! empty( $source ) ) {

            $name = $source;
            break;

        }

    }

    return apply_filters( 'bp_get_member_name', $name );
}



//
// Возвращает короткую и понятную метку времени
// 
// $time - время в формате MySQL по GMT (2017-05-17 23:02:50)
//

function mif_bpc_time_since( $time, $reverse = false )
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
        // $out = get_date_from_gmt( $time, 'H:i' );
        $arr[0] = get_date_from_gmt( $time, 'H:i' );

    } elseif ( get_date_from_gmt( $time, 'Y-m-d' ) == get_date_from_gmt( $yesterday, 'Y-m-d' ) ) {

        // Если вчера, то вывести время, минуты и сообщение, что это вчера
        // $out = get_date_from_gmt( $time, 'H:i' ) . ', ' . __( 'вчера', 'mif-bp-customizer' );
        $arr[0] = __( 'вчера', 'mif-bp-customizer' );
        $arr[1] = get_date_from_gmt( $time, 'H:i' );

    } elseif ( get_date_from_gmt( $time, 'Y' ) == get_date_from_gmt( $now, 'Y' ) ) {

        // Если этом году, то вывести время, минуты, день и месяц
        $arr[0] = get_date_from_gmt( $time, 'j ' ) . $month[get_date_from_gmt( $time, 'm' )];
        $arr[1] = get_date_from_gmt( $time, 'H:i' );
        // $out = get_date_from_gmt( $time, 'H:i, j ' );
        // $out .= $month[get_date_from_gmt( $time, 'm' )];
        
    } else {

        // В остальных случаях вывести время, минуты, день с ведущими нулями, номер месяца и год
        // // $out = get_date_from_gmt( $time, 'H:i, j ' );
        // // $out .= $month[get_date_from_gmt( $time, 'm' )];
        // // $out .= get_date_from_gmt( $time, ' Y ' ) . __( 'года', 'mif-bp-customizer' );
        // $out = get_date_from_gmt( $time, 'H:i, d.m.Y' );
        $arr[0] = get_date_from_gmt( $time, 'd.m.Y' );
        $arr[1] = get_date_from_gmt( $time, 'H:i' );
        
    }

    if ( $reverse ) $arr = array_reverse( $arr );

    return apply_filters( 'bp_get_member_name', implode( ', ', $arr ), $time );
}


//
// Красивое оформление размера файла
//


function mif_bpc_format_file_size( $size = 0 ) 
{
    if ( $size == 0 ) return 0;

    $arr = array(
        __( 'Б', 'mif-bp-customizer' ),
        __( 'Кб', 'mif-bp-customizer' ),
        __( 'Мб', 'mif-bp-customizer' ),
        __( 'Гб', 'mif-bp-customizer' ),
        __( 'Тб', 'mif-bp-customizer' ),
        __( 'Пб', 'mif-bp-customizer' ),
        );

    $i = 0;

    while ( $size >= 1024 ) {

        $size = $size / 1024;
        $i++;

    }

    $ret = round( $size, 2 ) . ' ' . $arr[$i];

    return apply_filters( 'mif_bpc_format_file_size', $ret, $size );
}