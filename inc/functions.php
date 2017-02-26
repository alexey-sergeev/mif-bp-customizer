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
        if ( is_array( $item['data'] ) )
            foreach ( $item['data'] as $key => $value )
                $param = ' '. 'data-' . $key . '="' . $value . '"';

        $class = ( isset( $item['class'] ) ) ? ' class="' . $item['class'] . '"' : '';
        $out .= '<a href="' . $item['href'] . '"' . $class . $param . '>' . $item['descr'] . '</a>';

    };

    $out .= '</div></div>';

    return $out;
}
