<?php

//
// Документы (функции шаблона текущего документа)
// 
//


defined( 'ABSPATH' ) || exit;



class mif_bpc_docs_templates extends mif_bpc_docs_screen {


    //
    // Размер аватарки пользователя
    //

    public $avatar_size = 50;



    function __construct()
    {
        parent::__construct();
    }



    //
    // Выводит имя документа
    //

    function get_name()
    {
        $out = '';

        $doc = $this->get_doc_data();
        if ( empty( $doc ) ) return;

        $out .= $this->get_doc_name( $doc );

        return apply_filters( 'mif_bpc_docs_get_name', $out, $doc );
    }



    //
    // Выводит документ на страницу документа
    //

    function get_doc()
    {
        $out = '';

        $doc = $this->get_doc_data();
        if ( empty( $doc ) ) return;

        $out .= $this->get_doc_content( $doc );

        return apply_filters( 'mif_bpc_docs_get_doc', $out, $doc );
    }



    //
    // Выводит мета-информацию на страницу документа
    //

    function get_meta()
    {
        $out = '';

        $doc = $this->get_doc_data();
        if ( empty( $doc ) ) return;

        $out .= $this->get_folder( $doc );
        $out .= $this->get_group( $doc );
        $out .= $this->get_date( $doc );
        $out .= $this->get_owner( $doc );
        $out .= $this->get_prev( $doc );
        $out .= $this->get_next( $doc );
        
        return apply_filters( 'mif_bpc_docs_get_meta', $out, $doc );
    }



    //
    // Выводит владельца документа
    //

    function get_owner( $doc = NULL )
    {
        $out = '';

        if ( $doc == NULL ) $doc = $this->get_doc_data();
        if ( empty( $doc ) ) return;

        $avatar = get_avatar( $doc->post_author, apply_filters( 'mif_bpc_docs_avatar_size', $this->avatar_size ) );
        $author = mif_bpc_get_member_name( $doc->post_author );

        $out .= '<div class="owner clearfix"><a href="' . bp_core_get_user_domain( $doc->post_author ) . '" target="blank"><span class="one">' . $avatar . '</span><span class="two">' . $author . '</span></a></div>';

        return apply_filters( 'mif_bpc_docs_get_owner', $out, $doc );
    }


    //
    // Выводит папку документа
    //

    function get_folder( $doc = NULL )
    {
        $out = '';

        if ( $doc == NULL ) $doc = $this->get_doc_data();
        if ( empty( $doc ) ) return;

        $folder = get_post( $doc->post_parent );
        if ( empty( $folder ) ) return;

        $folder_url = $this->get_folder_url( $folder->ID );

        $out .= '<div class="folder"><span class="one">' . __( 'Папка', 'mif-bp-customizer' ) . ':</span> <span class="two"><a href="' . $folder_url . '">' . $folder->post_title . '</a></span></div>';

        return apply_filters( 'mif_bpc_docs_get_folder', $out, $doc, $folder );
    }



    //
    // Выводит группу документа
    //

    function get_group( $doc = NULL )
    {
        return apply_filters( 'mif_bpc_docs_get_group', $out, $doc );
    }



    //
    // Выводит время размещения документа
    //

    function get_date( $doc = NULL )
    {
        $out = '';

        if ( $doc == NULL ) $doc = $this->get_doc_data();
        if ( empty( $doc ) ) return;

        $txt = ( $doc->post_date_gmt == $doc->post_modified_gmt ) ? __( 'Опубликовано', 'mif-bp-customizer' ) : __( 'Изменено', 'mif-bp-customizer' );

        $out .= '<div class="date"><span class="one">' . $txt . ':</span> <span class="two">' . mif_bpc_time_since( $doc->post_modified_gmt ) . '</span></div>';

        return apply_filters( 'mif_bpc_docs_get_date', $out, $doc );
    }



    //
    // Выводит ссылку на следующий документ
    //

    function get_next( $doc = NULL )
    {
        $out = '';

        $out .= '<div class="next"><a href="11"><span>' . __( 'туда', 'mif-bp-customizer' ) . '</span> <i class="fa fa-arrow-right"></i></a></div>';

        return apply_filters( 'mif_bpc_docs_get_next', $out, $doc );
    }



    //
    // Выводит ссылку на предыдущий документ
    //

    function get_prev( $doc = NULL )
    {
        $out = '';

        $out .= '<div class="prev"><a href="22"><i class="fa fa-arrow-left"></i> <span>' . __( 'сюда', 'mif-bp-customizer' ) . '</span></a></div>';

        return apply_filters( 'mif_bpc_docs_get_prev', $out, $doc );
    }



}




//
// Выводит форму загрузки
//

function mif_bpc_the_docs_upload_form()
{
    global $mif_bpc_docs_templates;
    echo $mif_bpc_docs_templates->get_upload_form();
}



//
// Выводит список папок
//

function mif_bpc_the_folders()
{
    global $mif_bpc_docs_templates;
    echo $mif_bpc_docs_templates->get_folders();
}



//
// Выводит форму создания или настройки папки
//

function mif_bpc_the_folder_settings()
{
    global $mif_bpc_docs_templates;
    echo $mif_bpc_docs_templates->get_folder_settings();
}



//
// Выводит содержимое страницы документов
//

function mif_bpc_the_docs_content()
{
    global $mif_bpc_docs_templates;
    echo $mif_bpc_docs_templates->get_docs_content();
}



//
// Выводит статусную строку документа
//

function mif_bpc_the_doc_statusbar()
{
    global $mif_bpc_docs_templates;
    echo $mif_bpc_docs_templates->get_doc_statusbar();
}



//
// Выводит статусную строку папки
//

function mif_bpc_the_folder_statusbar()
{
    global $mif_bpc_docs_templates;
    echo $mif_bpc_docs_templates->get_folder_statusbar();
}



//
// Выводит документ на страницу документа
//

function mif_bpc_docs_the_doc()
{
    global $mif_bpc_docs_templates;
    echo $mif_bpc_docs_templates->get_doc();
}



//
// Выводит мета-информацию на страницу документа
//

function mif_bpc_docs_the_meta()
{
    global $mif_bpc_docs_templates;
    echo $mif_bpc_docs_templates->get_meta();
}



//
// Выводит имя документа
//

function mif_bpc_docs_the_name()
{
    global $mif_bpc_docs_templates;
    echo $mif_bpc_docs_templates->get_name();
}



//
// Выводит владельца документа
//

function mif_bpc_docs_the_owner()
{
    global $mif_bpc_docs_templates;
    echo $mif_bpc_docs_templates->get_owner();
}



//
// Выводит папку документа
//

function mif_bpc_docs_the_folder()
{
    global $mif_bpc_docs_templates;
    echo $mif_bpc_docs_templates->get_folder();
}



//
// Выводит группу документа
//

function mif_bpc_docs_the_group()
{
    global $mif_bpc_docs_templates;
    echo $mif_bpc_docs_templates->get_group();
}



//
// Выводит время размещения документа
//

function mif_bpc_docs_the_date()
{
    global $mif_bpc_docs_templates;
    echo $mif_bpc_docs_templates->get_date();
}



//
// Выводит ссылку на следующий документ
//

function mif_bpc_docs_the_next()
{
    global $mif_bpc_docs_templates;
    echo $mif_bpc_docs_templates->get_next();
}



//
// Выводит ссылку на предыдущий документ
//

function mif_bpc_docs_the_prev()
{
    global $mif_bpc_docs_templates;
    echo $mif_bpc_docs_templates->get_prev();
}


?>