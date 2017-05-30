<?php

//
// Документы (описание раздела группы)
// 
//



defined( 'ABSPATH' ) || exit;


if ( mif_bpc_options( 'docs' ) ) 
    add_action( 'bp_init', 'mif_bpc_docs_group_init' );



function mif_bpc_docs_group_init() {

    class mif_bpc_docs_group extends BP_Group_Extension {

        var $visibility = 'private';
        var $enable_create_step = false;
        
        // Показывать в меню группы
        
        var $enable_nav_item = true;

        // Показывать в настройках группы

        var $enable_edit_item = false;

        function __construct() 
        {

            $this->name = __( 'Документы', 'mif-bp-customizer' );
            $this->slug = 'docs';
            $this->nav_item_position = 30;

        }



        // 
        // Страница документов
        // 

        function display( $group_id = NULL ) 
        {
            global $mif_bpc_docs;
            
            $action = bp_action_variable();

            echo $this->subnav( $group_id );

            if ( $action == 'folder' ) {

                $mif_bpc_docs->body();

            } elseif ( $action == 'new-folder' ) {

                $mif_bpc_docs->body();

            } elseif ( is_numeric( $action ) ) {

                // $mif_bpc_docs->body();
                $mif_bpc_docs->doc_page();

            } else {

                $mif_bpc_docs->body();

            }

        }


        //
        //
        //        

        function subnav()
        {
            global $mif_bpc_docs;

            $url = trailingslashit( $mif_bpc_docs->get_docs_url() );
            
            $out = '';
            $out .= '<div class="item-list-tabs no-ajax" id="subnav" role="navigation"><ul>';

            $current1 = ' class="current"';
            $current2 = '';

            if ( bp_action_variable() == 'new-folder' ) {

                $current1 = '';
                $current2 = ' class="current"';

            }

            $out .= '<li' . $current1 . '><a href="' . $url . '">' . __( 'Папки', 'mif-bp-customizer' ) . '</a></li>';
            $out .= '<li' . $current2 . '><a href="' . $url . 'new-folder/">' . __( 'Создать папку', 'mif-bp-customizer' ) . '</a></li>';

            $out .= '</ul></div>';

            return apply_filters( 'mif_bpc_docs_group_subnav', $out );
        }

    }

    bp_register_group_extension( 'mif_bpc_docs_group' );

}

?>