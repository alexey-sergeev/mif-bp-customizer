<?php

//
// Документы (экранные функции)
// 
//


defined( 'ABSPATH' ) || exit;



class mif_bpc_docs_screen extends mif_bpc_docs_core {


    function __construct()
    {
        parent::__construct();
    }


    // 
    // Форма загрузки
    // 

    function get_upload_form( $folder_id = NULL )
    {
        if ( $folder_id == NULL ) return;
        if ( ! $this->is_access( $folder_id, 'write' ) ) return;
        
        $out = '';

        $out .= '<div class="upload-form">';
        $out .= '<form>';
        $out .= '<div class="drop-box">';
        // $out .= '<div class="response-box clearfix"></div>';
        $out .= '<div class="template">' . $this->get_doc_item() . '</div>
        <p>' . __( 'Перетащите файлы сюда', 'mif-bp-customizer' ) . '...</p>
        <input type="file" name="files[]" multiple="multiple">';
        $out .= '</div>';
        $out .= '<p>... ' . __( 'или', 'mif-bp-customizer' ) . ' <a href="#" class="show-link-box">' . __( 'укажите ссылку Интернета', 'mif-bp-customizer' ) . '</a></p>';

        $out .= '<div class="link-box">
        <p><input type="text" name="link" placeholder="' . __( 'URL', 'mif-bp-customizer' ) . '">
        <p><input type="text" name="descr" placeholder="' . __( 'Описание', 'mif-bp-customizer' ) . '">
        <p><input type="submit" value="' . __( 'Опубликовать', 'mif-bp-customizer' ) . '">
        </div>';

        $out .= '<input type="hidden" name="nonce" value="' . wp_create_nonce( 'mif-bpc-docs-file-upload-nonce' ) . '">';
        $out .= '<input type="hidden" name="folder_id" value="' . $folder_id . '">';
        
        $out .= '</form>';
        $out .= '</div>';

        return apply_filters( 'mif_bpc_docs_get_upload_form', $out, $folder_id );
    }



    // 
    // Все папки пользователя или группы
    // 

    function get_folders( $page = 1, $item_id = NULL, $mode = 'member', $trashed = false )
    {
        if ( ! in_array( $mode, array( 'member', 'group' ) ) ) return;

        $out = '';
        if ( $page === 1 ) $out .= '<div class="collection clearfix">';

        $folders = $this->get_folders_data( $item_id, $mode, $page, $trashed );

        if ( $folders ) {

            $arr = array();
            foreach( $folders as $folder ) $arr[] = $this->get_folder_item( $folder );

            $out .= implode( "\n", $arr );
            if ( count( $folders ) == $this->folders_on_page ) $out .= $this->get_more_button( $page );

        } else {

            if ( $page === 1 ) $out = __( 'Папки не обнаружены', 'mif-bp-customizer' );

        }

        if ( $page === 1 ) $out .= '</div>';

        return apply_filters( 'mif_bpc_docs_get_folders', $out, $page, $item_id, $mode, $arr );
    }



    // 
    // Выводит страницу создания или настройки папки
    // 

    function get_folder_settings( $folder_id = NULL )
    {
        $out = '<div class="folder-settings">';

        if ( $folder_id == NULL ) {

            // Создаем новую папку

            $out .= '<h2>' . __( 'Новая папка', 'mif-bp-customizer' ) . '</h2>
            <form id="new-folder">
            <input type="hidden" name="redirect" value="' . $this->get_docs_url() . '/">
            <input type="hidden" name="_wpnonce" value="' . wp_create_nonce( 'mif-bpc-docs-new-folder-nonce' ) . '">';

            $name = $this->default_folder_name;
            $desc = '';
            $publish = ' checked';
            $remove_box = '';
            $disabled = '';

        } else {

            // Редактируем существующую папку

            $folder = get_post( $folder_id );

            if ( ! $this->is_folder( $folder ) ) return false;

            $out .= '<h2>' . __( 'Настройки папки', 'mif-bp-customizer' ) . '</h2>';
            
            $remove_box = '<p><a href="' . $this->get_folder_url( $folder_id ) . '" class="remove-box-toggle dotted">' . __( 'Удалить папку', 'mif-bp-customizer' ) . '</a></p>
            <div class="remove-box">
            <div class="message warning">
            <p><strong>' . __( 'Удалить папку и все её документы', 'mif-bp-customizer' ) . '</strong></p>
            <p>' . __( 'Папка и все её документы будут перемещены в корзину и через несколько дней окончательно удалены. Пока материалы хранятся в корзине, вы их сможете восстановить.', 'mif-bp-customizer' ) . '</p>
            <p><input type="button" class="remove to-trash" value="' . __( 'Удалить папку', 'mif-bp-customizer' ) . '"></p>
            </div>
            </div>';

            $disabled = '';
            if ( $folder->post_status == 'trash' ) {

                $out .= $this->folder_restore_delete_tool( $folder_id );
                $disabled = ' disabled';
                $remove_box = '';

            }

            $out .= '<form id="folder-settings" class="' . $folder->post_status . '">
            <input type="hidden" name="folder_id" value="' . $folder_id . '">
            <input type="hidden" name="_wpnonce" value="' . wp_create_nonce( 'mif-bpc-docs-folder-settings-nonce' ) . '">';

            $name = $folder->post_title;
            $desc = $folder->post_content;
            $publish = ( $folder->post_status == 'publish' ) ? ' checked' : '';

        }

        $out .= '<p>' . __( 'Название', 'mif-bp-customizer' ) . ':</p>
        <p><input type="text" name="name" value="' . $name .'"' . $disabled . '></p>
        <p>' . __( 'Описание', 'mif-bp-customizer' ) . ':</p>
        <p><textarea name="desc"' . $disabled . '>' . $desc . '</textarea></p>
        <p>' . __( 'Режим доступа', 'mif-bp-customizer' ) . ':</p>
        <p><label><input type="checkbox" name="publish"' . $publish  . $disabled . '> ' . __( 'Опубликована', 'mif-bp-customizer' ) . '</label></p><p>';

        if ( ! $disabled ) $out .= '<input type="submit" value="' . __( 'Сохранить', 'mif-bp-customizer' ) . '"> ';

        $out .= '<input type="button" id="cancel" value="' . __( 'Отмена', 'mif-bp-customizer' ) . '">
        </p>' . $remove_box . '</form>';

        $out .= '</div>';

        return apply_filters( 'mif_bpc_docs_get_folder_settings', $out, $folder_id );
    }



    // 
    // Выводит содержимое страницы документов
    // 

    function get_docs_content()
    {
        $out = '';

        $ca = bp_current_action();
        $param = bp_action_variable( 0 );

        if ( $ca == 'new-folder' ) {

            // Создание новой папки
            $out .= $this->get_folder_settings();

        } elseif ( $ca == 'folder' && is_numeric( $param ) ) {

            // Отобразить страницу папки
            $out .= $this->get_folder_content( $param );

        } else {

            // Главная страница документов - папки и др.
            $out .= $this->get_folders();
            $out .= $this->get_folder_statusbar();
            $out .= $this->get_docs_collection_nonce( 'all-folders' );

        }

        return apply_filters( 'mif_bpc_docs_get_docs_content', $out, $ca );
    }    




    // 
    // Выводит статусную строку папки
    // 

    function get_folder_statusbar( $folder_id = NULL )
    {
        if ( $folder_id == NULL && bp_current_action() == 'folder' && is_numeric( bp_action_variable( 0 ) ) ) $folder_id = bp_action_variable( 0 );

        $out = '';

        $show_settings = ( $this->is_folder( $folder_id ) ) ? true : false;

        $out .= '<div class="statusbar">
        <span class="info">&nbsp;</span>
        <span class="tools"> 
        <span class="item"><label title="' . __( 'Показать удалённые', 'mif-bp-customizer' ) . '"><span class="one"><input type="checkbox" id="show-remove-docs"></span><span class="two"><i class="fa fa-trash-o"></i></span></label></span>';

        if ( $show_settings ) $out .= '<span class="item"><span class="two" title="' . __( 'Настройки', 'mif-bp-customizer' ) . '"><a href="' . trailingslashit( $this->get_folder_url( $folder_id ) ) . 'settings/" id="folder-settings"><i class="fa fa-cog"></i></a></span></span></span>';

        $out .= '</div>';

        return apply_filters( 'mif_bpc_docs_get_folder_statusbar', $out, $folder_id );
    }




    // 
    // Все документы, расположенные в папке
    // 

    function get_docs_collection( $folder_id, $page = 1, $trashed = false )
    {
        if ( ! $this->is_folder( $folder_id ) ) return;
        
        if ( ! $this->is_access( $folder_id, 'read' ) ) {

            $out = __( 'Доступ ограничен', 'mif-bp-customizer' );   
            return apply_filters( 'mif_bpc_docs_get_docs_collection_access_denied', $out, $folder_id );

        }

        $out = '';
        if ( $page === 1 ) $out .= '<div class="collection response-box clearfix">';

        $folder = get_post( $folder_id );

        if ( $folder->post_status == 'trash' ) {
            
            $out .= $this->folder_restore_delete_tool( $folder_id );
            $trashed = true;

        }

        $docs = $this->get_docs_collection_data( $folder_id, $page, $trashed );

        if ( $docs ) {

            $arr = array();
            foreach( $docs as $doc ) $arr[] = $this->get_doc_item( $doc );

            $out .= implode( "\n", $arr );
            if ( count( $docs ) == $this->docs_on_page ) $out .= $this->get_more_button( $page, array( 'folder_id' => $folder_id ) );
        
        } else {

            if ( $page === 1 ) $out .= '<div class="folder-is-empty-msg">' . mif_bpc_message( __( 'Документы не обнаружены', 'mif-bp-customizer' ) ) . '</div>';
            
        }

        if ( $page === 1 ) $out .= '</div>';

        return apply_filters( 'mif_bpc_docs_get_docs_collection', $out, $page, $folder_id );
    }



    // 
    // Окно восстановления или окончательного удаления папки
    // 

    function folder_restore_delete_tool( $folder_id )
    {
        if ( ! $this->is_folder( $folder_id ) ) return;

        $out = '';

        $out .= __( 'Папка находится в корзине и через некоторое время будет окончательно удалена. Пока это не произошло, вы можете её восстановить или самостоятельно удалить из корзины.', 'mif-bp-customizer' );
        $out .= '<div class="folder-restore-delete">
        <form>
        <input type="button" name="delete" class="delete" value="' . __( 'Удалить совсем', 'mif-bp-customizer' ) . '">
        <input type="button" name="restore" class="restore" value="' . __( 'Восстановить', 'mif-bp-customizer' ) . '">
        <input type="hidden" name="item_id" value="' . $folder_id . '">
        </form>
        </div>';

        $ret = mif_bpc_message( $out, 'warning' );

        return apply_filters( 'mif_bpc_docs_folder_restore_delete_tool', $ret, $out, $folder_id );
    }

    


    // 
    // Выводит кнопку "Показать ещё"
    // 

    function get_more_button( $page, $args = array() )
    {
        $out = '';

        $out .= '<div class="more"><form>
        <button>' . __( 'Показать ещё', 'mif-bp-customizer' ) . '</button>
        <i class="fa fa-spinner fa-spin fa-3x fa-fw"></i>';
        $out .= '<input type="hidden" name="_wpnonce" value="' . wp_create_nonce( 'mif-bpc-docs-collection-nonce' ) . '">';

        foreach ( $args as $key => $value ) $out .= '<input type="hidden" name="' . $key . '" value="' . $value . '">';

        $next_page = (int) $page + 1;
        $out .= '<input type="hidden" name="page" value="' . $next_page . '">';
        $out .= '</form></div>';

        return apply_filters( 'mif_bpc_docs_get_more_button', $out, $page, $args );
    }



    // 
    // Выводит изображение документа
    // 

    function get_doc_item( $doc = NULL )
    {
        if ( $doc == NULL ) {

            $logo = '<i class="fa fa-spinner fa-spin fa-3x fa-fw"></i>';
            $name = '';
            $loading = ' loading';
            $a1 = '';
            $a2 = '';
            $remove = '';
            $download = '';

        } else {

            if ( is_numeric( $doc ) ) $doc = get_post( $doc );

            $name = $doc->post_title;
            $logo = $this->get_file_logo( $doc );
            $loading = '';
            $url = $this->get_doc_url( $doc->ID );
            $a1 = '<a href="' . $url . '/">';
            $a2 = '</a>';
            $left = '<a href="' . $url . 'remove/" data-item-id="' . $doc->ID . '" class="button item-remove left" title="' . __( 'Удалить', 'mif-bp-customizer' ) . '"><i class="fa fa-times"></i></a>';

            $doc_type = $this->get_doc_type( $doc );

            if ( $doc_type == 'file' || $doc_type == 'image' ) {

                $right = '<a href="' . $url . 'download/" class="button doc-download right" title="' . __( 'Скачать', 'mif-bp-customizer' ) . '"><i class="fa fa-download"></i></a>';
            
            } elseif ( $doc_type == 'link' ) {

                $right = '<a href="' . $doc->post_content . '" target="blank" class="button doc-download right" title="' . __( 'Открыть', 'mif-bp-customizer' ) . '"><i class="fa fa-arrow-up"></i></a>';

            } else {

                $right = '';

            }

            if ( $doc->post_status == 'trash' ) {

                $left = '<a href="' . $url . 'restore/" data-item-id="' . $doc->ID . '" class="button item-remove restore left" title="' . __( 'Восстановить', 'mif-bp-customizer' ) . '"><i class="fa fa-undo"></i></a>';
                $right = '<a href="' . $url . 'remove/" data-item-id="' . $doc->ID . '" class="button item-remove right" title="' . __( 'Удалить совсем', 'mif-bp-customizer' ) . '"><i class="fa fa-times"></i></a>';

            }

        }

        $out = '<div class="file ' . $doc->post_status . $loading . '">
        ' . $a1 . '
        <span class="logo">' . $logo . '</span>
        <span class="name">' . $name . '</span>
        ' . $a2 . '
        ' . $left . '
        ' . $right . '
        </div>';

        return apply_filters( 'mif_bpc_docs_get_doc_item', $out, $doc );
    }



    // 
    // Выводит изображение папки
    // 

    function get_folder_item( $folder = NULL )
    {
        if ( is_numeric( $folder ) ) $folder = get_post( $folder );

        if ( ! $this->is_folder( $folder->ID ) ) return;

        $data = $this->get_folder_size( $folder->ID );

        $left = '';
        $right = '';
        $url = $this->get_folder_url( $folder->ID );


        if ( $folder->post_status == 'trash' ) {

            if ( $data['count'] == 0 ) $left = '<a href="' . $url . '/restore/" data-item-id="' . $folder->ID . '" class="button item-remove restore left" title="' . __( 'Восстановить', 'mif-bp-customizer' ) . '"><i class="fa fa-undo"></i></a>';
            if ( $data['count'] == 0 ) $right = '<a href="' . $url . '/remove/" data-item-id="' . $folder->ID . '" class="button item-remove right" title="' . __( 'Удалить совсем', 'mif-bp-customizer' ) . '"><i class="fa fa-times"></i></a>';

        } else {

            if ( $data['count'] == 0 ) $left = '<a href="' . $url . '/remove/" data-item-id="' . $folder->ID . '" class="button item-remove left" title="' . __( 'Удалить', 'mif-bp-customizer' ) . '"><i class="fa fa-times"></i></a>';

        }

        $out = '<div class="file folder ' . $folder->post_status . '">
        <a href="' . $this->get_folder_url( $folder->ID ) . '">
        <span class="logo"><i class="fa fa-folder-open-o fa-3x"></i></span>
        <span class="name">' . $folder->post_title . '</span>
        <span class="count right">' . $data['count'] . '</span>
        ' . $left . '
        ' . $right . '
        </a>
        </div>';

        return apply_filters( 'mif_bpc_docs_get_folder_item', $out, $folder );
    }




    // 
    // Выводит заголовок на странице папки
    // 

    function get_folder_header( $folder = NULL )
    {
        if ( $folder == NULL ) return;

        $out = '<h2><a href="' . $this->get_docs_url() . '/">' . __( 'Папки', 'mif-bp-customizer' ) . '</a> /  
        <a href="' . $this->get_folder_url( $folder->ID ) . '">' . $folder->post_title . '</a></h2>';

        return apply_filters( 'mif_bpc_docs_ get_folder_header', $out, $folder );
    }



    // 
    // Содержимое папки
    // 

    function get_folder_content( $folder_id = NULL, $msg = false )
    {
        if ( $folder_id == NULL ) return;
        
        $out = '';
        $folder = get_post( $folder_id );

        if ( $folder->post_type == 'mif-bpc-folder' ) {
            
            $out .= $this->get_folder_header( $folder );
            $out .= $this->get_upload_form( $folder_id );

            if ( $msg ) $out .= mif_bpc_message( $msg );

            $out .= $this->get_docs_collection( $folder_id );
            $out .= $this->get_folder_statusbar( $folder_id );
            $out .= $this->get_docs_collection_nonce( $folder_id );
             
        } else {

            $out .= mif_bpc_message( __( 'Папка не обнаружена', 'mif-bp-customizer' ), 'warning' );

        }

        return apply_filters( 'mif_bpc_docs_get_folder_content', $out, $folder_id );
    }



    // 
    // Выводит nonce-поля и другую информацию для поддержки AJAX-запросов
    // 

    function get_docs_collection_nonce( $folder_id = NULL )
    {
        $out = '';
        $out .= '<input type="hidden" id="docs-collection-nonce" value="' . wp_create_nonce( 'mif-bpc-docs-collection-nonce' ) . '">';
        
        if ( is_numeric( $folder_id ) ) $out .= '<input type="hidden" name="folder_id" id="docs-folder-id" value="' . $folder_id . '">';
        if ( $folder_id == 'all-folders' ) $out .= '<input type="hidden" name="all_folders" id="docs-all-folders" value="on">';

        return apply_filters( 'mif_bpc_docs_gget_docs_collection_nonce', $out, $folder_id );
    }



    // 
    // Выводит информацию папки в статусной строке
    // 

    function get_folder_statusbar_info( $folder_id = NULL )
    {
        if ( $folder_id == NULL ) {

            if ( ! ( bp_current_action() == 'folder' && is_numeric( bp_action_variable( 0 ) ) ) ) return;
            $folder_id = bp_action_variable( 0 );

        }

        $data = $this->get_folder_size( $folder_id );

        $out = '<span class="one">' . __( 'Документов', 'mif-bp-customizer' ) . ':</span> <span class="two">' . $data['count'] . '</span>
        <span class="one">' . __( 'Объем', 'mif-bp-customizer' ) . ':</span> <span class="two">' . mif_bpc_format_file_size( $data['size'] ) . '</span>';

        return apply_filters( 'mif_bpc_docs_get_folder_statusbar_info', $out, $folder_id, $data );
    }




    // 
    // Выводит информацию всех папок в статусной строке
    // 

    function get_all_folders_statusbar_info()
    {
        $data = $this->get_all_folders_size();

        $out = '<span class="one">' . __( 'Папок', 'mif-bp-customizer' ) . ':</span> <span class="two">' . $data['count'] . '</span>
        <span class="one">' . __( 'Общий объем', 'mif-bp-customizer' ) . ':</span> <span class="two">' . mif_bpc_format_file_size( $data['size'] ) . '</span>';

        return apply_filters( 'mif_bpc_docs_get_folder_statusbar_info', $out, $folder_id, $data );
    }



    // 
    // Выводит сообщение об ошибке
    // 

    function error_msg( $s = '000' )
    {
        $out = mif_bpc_message( sprintf( __( 'Ошибка %s. Что-то пошло не так', 'mif-bp-customizer' ), $s ), 'error' );
        return apply_filters( 'mif_bpc_docs_error_msg', $out, $s );
    }



    // 
    // Логотип файла
    // 

    function get_file_logo( $doc, $size = 3 )
    {
        $type = ( preg_match( '/^http/', $doc->post_content ) ) ? $doc->post_content : $doc->post_title;
        return apply_filters( 'mif_bpc_docs_get_file_logo', mif_bpc_get_file_icon( $type, 'fa-' . $size . 'x' ), $doc );
    }




}



?>