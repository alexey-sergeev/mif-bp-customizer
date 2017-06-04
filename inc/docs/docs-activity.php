<?php

//
// Документы (лента активности)
// 
//


defined( 'ABSPATH' ) || exit;



class mif_bpc_docs_activity extends mif_bpc_docs_screen {

    function __construct()
    {
      
        parent::__construct();

        add_action( 'bp_after_activity_post_form', array( $this, 'repost_doc_helper' ) );
        add_action( 'bp_after_activity_post_form', array( $this, 'docs_form' ) );
        add_filter( 'bp_get_activity_content_body', array( $this, 'content_body' ), 5 );
        add_filter( 'bp_get_activity_latest_update_excerpt', array( $this, 'latest_update' ), 10, 2 );

        add_action( 'wp_ajax_mif-bpc-docs-upload-files-activity', array( $this, 'ajax_upload_activity_helper' ) );

    }



    //
    // Форма загрузки документов в ленте активности
    //

    function docs_form()
    {
        $out = '';
        
        $out .= '<span class="hidden">';

        $out .= '<div id="docs-form" class="docs-form">
        <div class="response-box attach clearfix hidden"></div>
        <div class="template">' . $this->get_doc_item_activity() . '</div>
        <div class="drop-box"><p>' . __( 'Перетащите сюда фотографии или файлы', 'mif-bp-customizer' ) . '</p>
        <input type="file" name="files[]" multiple="multiple" class="docs-upload-form"></div>
        <input name="MAX_FILE_SIZE" value="' . $this->get_max_upload_size() . '" type="hidden">
        <input name="max_file_error" value="' . __( 'Слишком большой файл', 'mif-bp-customizer' ) . '" type="hidden">
        <input type="hidden" name="upload_nonce" value="' . wp_create_nonce( 'mif-bpc-docs-file-upload-nonce' ) . '">
        <input type="hidden" name="action" value="mif-bpc-docs-upload-files-activity">
        
        <a href="#" class="button file-form-toggle"><i class="fa fa-camera"></i></a>
        </div>';

        $out .= '</span>';

        $out = apply_filters( 'mif_bpc_docs_activity_docs_form', $out );

        echo $out;
    }




    // 
    // Ajax-помощник загрузки файлов
    // 

    function ajax_upload_activity_helper()
    {
        check_ajax_referer( 'mif-bpc-docs-file-upload-nonce' );

        $user_id = bp_loggedin_user_id();
        if ( empty( $user_id ) ) wp_die();

        if ( isset( $_FILES['file']['tmp_name'] ) ) {

            $filename = basename( $_FILES['file']['name'] );
            $path = trailingslashit( $this->get_docs_path() ) . md5( uniqid( rand(), true ) ); 
            $upload_dir = (object) wp_upload_dir();

            // Проверить размер
            if ( $_FILES['file']['size'] > $this->get_max_upload_size() ) wp_die();

            if ( move_uploaded_file( $_FILES['file']['tmp_name'], $upload_dir->basedir . $path ) ) {

                // Файл успешно загружен

                $post_id = $this->doc_save( $filename, $path, $user_id, 'activity_stream_folder', $_FILES['file']['type'], $_POST['order'] );

                echo $this->get_doc_item_activity( $post_id );
                echo '<input type="hidden" name="attachments[]" value="' . $post_id . '">';

            } 

        }

        wp_die();
    }



    //
    // Оформление документов в ленте активности
    //

    function latest_update( $content, $user_id )
    {

		if ( ! $update = bp_get_user_meta( $user_id, 'bp_latest_update', true ) ) return false;

        $content = $this->content_body( $update['content'] );
        $content = preg_replace( '/span><span/', 'span> <span', $content );

        $content = wp_strip_all_tags( bp_create_excerpt( $content, 358 ) );

        return apply_filters( 'mif_bpc_docs_activity_latest_update', $content, $user_id );
    }



    //
    // Оформление документов в ленте активности
    //

    function content_body( $content )
    {
        $content_copy = $content;

        // Регулярные выражения для поиска опубликованных документов
        // Идентификатор папки или документа должен быть в последней группе поиска
        // Можно уточнить внешним плагином, если планируется обращатся к документам по иным адресам (сокращение ссылок или др.)

        $regexp_arr = apply_filters( 'mif_bpc_docs_activity_content_body_reg_arr', array( 
                                    preg_replace( '/\//', '\/', trailingslashit( bp_get_root_domain() ) . '(' . bp_get_members_root_slug() . '/)?' . '(' . bp_get_groups_root_slug() . '/)?' . '[^/]+/' . $this->slug . '/(folder/)?(\d+)/?' ),
                                    '\[\[(\d+)\]\]', 
                                ) );

        // foreach ( $regexp_arr as $regexp => $num ) $content = preg_replace( '/' . $regexp . '/', $this->get_item( '\1' ), $content );
        foreach ( $regexp_arr as $regexp ) $content = preg_replace_callback( '/' . $regexp . '/', array( $this, 'get_item' ), $content );

        $content = preg_replace( '/span>\s+<span/', 'span><span', $content );
        $content = preg_replace( '/(<span.+span>)/', '<span class="attach clearfix">\1</span>', $content );

        return apply_filters( 'mif_bpc_docs_activity_content_body', $content, $content_copy );
    }



    //
    // Функция-помощник замены в регулярном выражении
    //

    function get_item( $matches )
    {
        $item_id = (int) array_pop( $matches );
        $item_activity = $this->get_doc_item_activity( $item_id );
        return apply_filters( 'mif_bpc_docs_activity_get_item', $item_activity, $item_id, $matches );
    }



    //
    // Оформление документа или папки в ленте активности
    //

    function get_doc_item_activity( $item_id = NULL )
    {
        if ( $this->is_doc( $item_id ) ) {

            if ( ! $this->is_access( $itemr_id, 'read' ) ) return;
    
            $doc = get_post( $item_id );

            $name = $this->get_doc_name( $doc );
            $logo = $this->get_file_logo( $doc, 1 );
            $url = $this->get_doc_url( $doc->ID );

            $out = '<span class="docs-item file clearfix"><a href="' . $url . '"><span class="icon">' . $logo . '</span><span class="name">' . $name . '</span></a></span>';

        } elseif ( $this->is_folder( $item_id ) ) {

            if ( ! $this->is_access( $itemr_id, 'read' ) ) return;
    
            $folder = get_post( $item_id );

            $name = $folder->post_title;
            $url = $this->get_folder_url( $folder->ID );
            $data = $this->get_folder_size( $folder->ID );

            $out = '<span class="docs-item folder clearfix"><a href="' . $url . '"><span class="icon"><i class="fa fa-folder-open-o"></i></span><span class="name">' . $name . '</span></a></span>';

        } else {

            $out = '<span class="docs-item file clearfix"><span class="icon"><i class="fa fa-spinner fa-spin fa-fw"></i></span><span class="name"></span></span>';

        }


        return apply_filters( 'mif_bpc_docs_activity_get_doc_item_activity', $out, $item_id );
    }




    //
    // Помощник публикации документа или папки в ленте активности
    //

    function repost_doc_helper()
    {
        if( ! wp_verify_nonce( $_GET['_wpnonce'], 'mif_bpc_docs_repost_button' ) ) return;

        if ( $this->is_doc( $_GET['doc'] ) || $this->is_folder( $_GET['doc'] ) ) {

            echo '<input type="hidden" id="doc-repost-id" value="' . $_GET['doc'] . '">';

        }
        
        //
        // Примечание. Наличие этого поля анализирует js-сценарий и выводит данные в форму
        //

    }


}






?>