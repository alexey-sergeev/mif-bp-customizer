<?php

//
// Документы (функции ajax-запросов)
// 
//


defined( 'ABSPATH' ) || exit;



class mif_bpc_docs_ajax extends mif_bpc_docs_screen {

    function __construct()
    {
       
        parent::__construct();

        // Ajax-события
        add_action( 'wp_ajax_mif-bpc-docs-upload-files', array( $this, 'ajax_upload_helper' ) );
        add_action( 'wp_ajax_mif-bpc-docs-network-link-files', array( $this, 'ajax_network_link_helper' ) );
        add_action( 'wp_ajax_mif-bpc-docs-collection-show', array( $this, 'ajax_collection_helper' ) );
        add_action( 'wp_ajax_mif-bpc-collection-reorder', array( $this, 'ajax_collection_reorder_helper' ) );

        add_action( 'wp_ajax_mif-bpc-docs-new-folder', array( $this, 'ajax_new_folder_helper' ) );
        add_action( 'wp_ajax_mif-bpc-docs-remove', array( $this, 'ajax_remove_helper' ) );
        add_action( 'wp_ajax_mif-bpc-docs-folder-publisher', array( $this, 'ajax_publisher_folder_helper' ) );
        add_action( 'wp_ajax_mif-bpc-docs-folder-statusbar-info', array( $this, 'ajax_folder_statusbar_info_helper' ) );
        add_action( 'wp_ajax_mif-bpc-docs-folder-settings', array( $this, 'ajax_folder_settings_helper' ) );
        add_action( 'wp_ajax_mif-bpc-docs-folder-settings-save', array( $this, 'ajax_folder_settings_save_helper' ) );
        add_action( 'wp_ajax_mif-bpc-docs-doc-publisher', array( $this, 'ajax_publisher_doc_helper' ) );
        add_action( 'wp_ajax_mif-bpc-docs-doc-settings', array( $this, 'ajax_doc_settings_helper' ) );
        add_action( 'wp_ajax_mif-bpc-docs-doc-settings-save', array( $this, 'ajax_doc_settings_save_helper' ) );
        add_action( 'wp_ajax_mif-bpc-docs-doc-statusbar-info', array( $this, 'ajax_doc_statusbar_info_helper' ) );
        add_action( 'wp_ajax_mif-bpc-docs-doc-meta', array( $this, 'ajax_doc_meta_helper' ) );

        add_action( 'wp_enqueue_scripts', array( $this, 'load_js_helper' ) );   

    }



    // 
    // JS-помощник
    // 

    function load_js_helper()
    {
        wp_enqueue_script( 'mif_bpc_docs_helper', plugins_url( '../../js/docs.js', __FILE__ ) );
        wp_enqueue_script( 'mif_bpc_docs_jquery-ui', plugins_url( '../../js/jquery-ui/jquery-ui.min.js', __FILE__ ) );
    }



    // 
    // Ajax-помощник публикации приватного документа
    // 

    function ajax_publisher_doc_helper()
    {
        check_ajax_referer( 'mif-bpc-docs-nonce' );

        $item_id = (int) $_POST['item_id'];

        if ( ! $this->is_access( $item_id, 'write' ) ) wp_die();

        wp_publish_post( $item_id );
        
        echo mif_bpc_message( __( 'Документ опубликован', 'mif-bp-customizer' ) );

        wp_die();
    }



    // 
    // Ajax-помощник публикации приватной папки
    // 

    function ajax_publisher_folder_helper()
    {
        check_ajax_referer( 'mif-bpc-docs-nonce' );

        // $user_id = bp_loggedin_user_id();
        // if ( empty( $user_id ) ) wp_die();

        $item_id = (int) $_POST['item_id'];

        // $doc_id = $item_id;

        if ( ! $this->is_access( $item_id, 'write' ) ) wp_die();

        wp_publish_post( $item_id );
        
        echo mif_bpc_message( __( 'Папка опубликована', 'mif-bp-customizer' ) );

        wp_die();
    }



    // 
    // Ajax-помощник удаления или восстановления папки или документа
    // 

    function ajax_remove_helper()
    {
        check_ajax_referer( 'mif-bpc-docs-nonce' );

        // $user_id = bp_loggedin_user_id();
        // if ( empty( $user_id ) ) wp_die();

        $item_id = (int) $_POST['item_id'];

        // $doc_id = $item_id;

        if ( ! $this->is_access( $item_id, 'write' ) ) wp_die();

        $is_doc = ( $this->is_doc( $item_id ) ) ? true : false;
        $is_folder = ( $this->is_folder( $item_id ) ) ? true : false;
        $mode = ( $_POST['mode'] == 'page' ) ? 'page' : 'item';

        $item = get_post( $item_id );
        
        if ( $is_doc ) $folder_id = $item->post_parent;

        if ( $item->post_status == 'trash' ) {

            if ( isset( $_POST['restore'] ) && $_POST['restore'] == 1) {

                // Восстановить документ или папку
                // if ( $is_doc ) if ( $this->untrash_doc( $item_id ) ) echo $this->get_doc_item( $item_id );
                // if ( $is_folder ) if ( $this->untrash_folder( $item_id ) ) echo $this->get_folder_item( $item_id );
                if ( $is_doc ) if ( $this->untrash_doc( $item_id ) ) echo $this->show_response( $item_id, 'doc', $mode );
                if ( $is_folder ) if ( $this->untrash_folder( $item_id ) ) echo $this->show_response( $item_id, 'folder', $mode );

            } else {

                // Удалить документ или папку навсегда
                // if ( $is_doc ) if ( $this->delete_doc( $item_id ) ) echo '<!-- empty -->';
                // if ( $is_folder ) if ( $this->delete_folder( $item_id ) ) echo '<!-- empty -->';
                if ( $is_doc ) if ( $this->delete_doc( $item_id ) ) echo $this->show_response( $folder_id, 'doc-empty', $mode, $item->post_title );
                if ( $is_folder ) if ( $this->delete_folder( $item_id ) ) echo $this->show_response( $item_id, 'folder-empty', $mode, $item->post_title );

            }

        } else {

            // Поместить документ или папку в корзину
            // if ( $is_doc ) if ( $this->trash_doc( $item_id ) ) echo $this->get_doc_item( $item_id );
            // if ( $is_folder ) if ( $this->trash_folder( $item_id ) ) echo $this->get_folder_item( $item_id );
            if ( $is_doc ) if ( $this->trash_doc( $item_id ) ) echo $this->show_response( $item_id, 'doc', $mode );
            if ( $is_folder ) if ( $this->trash_folder( $item_id ) ) echo $this->show_response( $item_id, 'folder', $mode );

        }

        wp_die();
    }



    // 
    // Показать данные ответа
    // 

    function show_response( $item_id = NULL, $item_type = 'doc', $mode = 'item', $name = '' )
    {
        if ( $item_id == NULL ) return;

        // Если запрос пришел с кнопки на элементе в каталоге элементов

        if ( $mode == 'item' ) {

            if ( $item_type == 'doc' ) $out = $this->get_doc_item( $item_id );
            if ( $item_type == 'doc-empty' ) $out = '<!-- empty -->';
            if ( $item_type == 'folder' ) $out = $this->get_folder_item( $item_id );
            if ( $item_type == 'folder-empty' ) $out = '<!-- empty -->';

        }

        // Если запрос пришел со страницы элемента

        if ( $mode == 'page' ) {

            if( $item_type == 'doc' ) $out = $this->get_doc_content( $item_id, __( 'Документ восстановлен', 'mif-bp-customizer' ) );
            if( $item_type == 'doc-empty' ) {
                
                $msg = sprintf( __( 'Документ «%s» окончательно удален', 'mif-bp-customizer' ), '<strong>' . $name . '</strong>' );

                $folder = get_post( $item_id );

                $msg .= '<p>' . __( 'Вернуться', 'mif-bp-customizer' ) . ': <strong><a href="' . $this->get_folder_url( $folder->ID ) . '">' . $folder->post_title . '</a></strong>';
                
                $out = mif_bpc_message( $msg );

            }           

            if ( $item_type == 'folder' ) $out = $this->get_folder_content( $item_id, __( 'Папка и все удалённые вместе с ней документы восстановлены', 'mif-bp-customizer' ) );
            if ( $item_type == 'folder-empty' ) {
                
                $msg = sprintf( __( 'Папка «%s» окончательно удалена', 'mif-bp-customizer' ), '<strong>' . $name . '</strong>' );
                $msg .= '<p>' . __( 'Вернуться', 'mif-bp-customizer' ) . ': <strong><a href="' . $this->get_docs_url() . '">' . __( 'документы', 'mif-bp-customizer' ) . '</a></strong>';
                
                $out = mif_bpc_message( $msg );

            }
                

        }

        return apply_filters( 'mif_bpc_docs_show_response', $out, $item_id, $item_type, $mode );
    }



    // 
    // Ajax-помощник создания папки
    // 

    function ajax_new_folder_helper()
    {
        check_ajax_referer( 'mif-bpc-docs-new-folder-nonce' );

        // if ( empty( $user_id ) ) wp_die();

        // $publish = ( $_POST['publish'] == 'on' ) ? 'publish' : 'private';
        // $name = ( trim( $_POST['name'] ) == '' ) ? $this->default_folder_name : trim( $_POST['name'] );

        // $folder_data = array(
        //     'post_type' => 'mif-bpc-folder',
        //     'post_title' => $name,
        //     'post_content' => trim( $_POST['desc'] ),
        //     'post_status' => $publish,
        //     // 'post_parent' => $group_id,
        //     'post_author' => $user_id,
        //     'comment_status' => 'closed',
        //     'ping_status' => 'closed'

        // );

        // $post_id = wp_insert_post( wp_slash( $folder_data ) );



        $item_id = bp_loggedin_user_id();
        $mode = 'user';

        $post_id = $this->folder_save( $item_id, $mode, $_POST['name'], $_POST['desc'], $_POST['publish'] );
        if ( $post_id ) echo $this->get_folder_url( $post_id );

        wp_die();
    }



    // 
    // Ajax-помощник загрузки сетевого документа
    // 

    function ajax_network_link_helper()
    {
        check_ajax_referer( 'mif-bpc-docs-file-upload-nonce' );

        $user_id = bp_loggedin_user_id();
        if ( empty( $user_id ) ) wp_die();

        $name = trim( $_POST['descr'] );
        $path = trim( $_POST['link'] );

        if ( empty( $name ) ) $name = $path;

        if ( ! empty( $path ) ) {
            
            $post_id = $this->doc_save( $name, $path, $user_id, $_POST['folder_id'], '', $_POST['order'] );
            echo $this->get_doc_item( $post_id );

        } else {

            echo __( 'Ошибка', 'mif-bp-customizer' );

        }

        wp_die();
    }



    // 
    // Ajax-помощник загрузки страниц коллекции документов
    // 

    function ajax_collection_reorder_helper()
    {
        check_ajax_referer( 'mif-bpc-docs-nonce' );

        if ( isset( $_POST['folder_id'] ) ) {

            // Папка (сортируем документы)

            $folder_id = (int) $_POST['folder_id'];
            $order = json_decode( stripcslashes( $_POST['order'] ), true );
            $this->docs_reorder( $folder_id, $order );

            echo 1;

        } elseif ( isset( $_POST['all_folders'] ) ) {

            // Список папок (сортируем папки)

            $item_id = bp_displayed_user_id(); //!!! как быть для групп?
            $mode = 'user';
            $order = json_decode( stripcslashes( $_POST['order'] ), true );
            $this->folders_reorder( $item_id, $mode, $order );

            echo 1;
            
        } else {

            // Не ясно, это папки или документы?

            echo 0;

        }

        wp_die();
    }

    // 
    // Ajax-помощник загрузки страниц коллекции документов
    // 

    function ajax_collection_helper()
    {
        check_ajax_referer( 'mif-bpc-docs-nonce' );

        $page = ( isset( $_POST['page'] ) ) ? (int) $_POST['page'] : 1;
        $trashed = (int) $_POST['trashed'];

        if ( isset( $_POST['folder_id'] ) ) {

            $folder_id = (int) $_POST['folder_id'];
            echo $this->get_docs_collection( $folder_id, $page, $trashed );

        } else {

            $mode = false;
            
            if ( bp_is_user() ) {

                $mode = 'user';
                $item_id = bp_displayed_user_id();

            } elseif ( bp_is_group() ) {

                $mode = 'group';
                $item_id = bp_get_current_group_id();

            }

            // $item_id = (int) $_POST['item_id'];
            // $mode = $_POST['mode'];

            if ( $mode ) echo $this->get_folders( $page, $item_id, $mode, $trashed );
            
        }

        wp_die();
    }



    // 
    // Ajax-помощник загрузки файлов
    // 

    function ajax_upload_helper()
    {
        check_ajax_referer( 'mif-bpc-docs-file-upload-nonce' );

        $user_id = bp_loggedin_user_id();
        if ( empty( $user_id ) ) wp_die();

        // f($_FILES);
        // f($_FILES['file']['name']);

        // f( $this->get_file_logo( $_FILES['file']['name'] ) );

        // echo '12';

        // $attachment = new BP_Attachment( array( 'base_dir' => 'docs' ) );
        // $file = $attachment->upload( $_FILES );


        if ( isset( $_FILES['file']['tmp_name'] ) ) {

            $filename = basename( $_FILES['file']['name'] );
            $path = trailingslashit( $this->get_docs_path() ) . md5( uniqid( rand(), true ) ); 
            $upload_dir = (object) wp_upload_dir();

            // Здесь проверять размер и тип файла

            if ( move_uploaded_file( $_FILES['file']['tmp_name'], $upload_dir->basedir . $path ) ) {

                // Файл успешно загружен

                $post_id = $this->doc_save( $filename, $path, $user_id, $_POST['folder_id'], $_FILES['file']['type'], $_POST['order'] );
                echo $this->get_doc_item( $post_id );

                // // Отправить ответ клиенту

                // $logo = $this->get_file_logo( $_FILES['file']['name'] );
                // $before = '<a href="123">';
                // $after = '</a>';
                
                // echo $before . $logo . $after;

            } else {

                echo __( 'Ошибка', 'mif-bp-customizer' );

            }

        }

        wp_die();
    }



    // 
    // Ajax-помощник окна настройки документа
    // 

    function ajax_doc_settings_helper()
    {
        check_ajax_referer( 'mif-bpc-docs-nonce' );

        $doc_id = (int) $_POST['doc_id'];

        echo $this->get_doc_settings( $doc_id );
        echo $this->get_doc_nonce();

        wp_die();
    }



    // 
    // Ajax-помощник сохранения настроек документа
    // 

    function ajax_doc_settings_save_helper()
    {
        check_ajax_referer( 'mif-bpc-docs-doc-settings-nonce' );

        $doc_id = (int) $_POST['doc_id'];

        if ( ! $this->is_access( $doc_id, 'write' ) ) wp_die();

        if ( isset( $_POST['do'] ) ) {

            if ( $_POST['do'] == 'cancel' ) {

                // Нажали "Отмена" - просто показать папки

                echo $this->get_doc_content( $doc_id );

            } elseif ( $_POST['do'] == 'to-trash' ) {

                // Удалить в корзину

                $ret = ( $this->trash_doc( $doc_id ) ) ? $this->get_doc_content( $doc_id ) : $this->error_msg( '005' );
                echo $ret;

            } else {

                echo $this->error_msg( '006' );

            }

        } else {

            // Сохраняем новые настройки документа

            $doc = get_post( $doc_id );
            
            if ( isset( $doc->post_status ) && $doc->post_status != 'trash' ) {

                $publish = ( $_POST['publish'] == 'on' ) ? 'publish' : 'private';

                $doc_data = array(
                                    'ID' => (int) $_POST['doc_id'],
                                    'post_status' => $publish,
                                    'post_excerpt' => trim( $_POST['desc'] ),
                                );

                $name = trim( $_POST['name'] );
                
                if ( $name != '' ) $doc_data['post_title'] = $this->ext_safety( $name, $doc->post_title );

                $ret = ( wp_update_post( wp_slash( $doc_data ) ) ) ? $this->get_doc_content( $doc_id ) : $this->error_msg( '008' );
                echo $ret;

            } else {

                echo $this->error_msg( '007' );

            }
        }

        wp_die();
    }


    // 
    // Ajax-помощник окна настройки папки
    // 

    function ajax_folder_settings_helper()
    {
        check_ajax_referer( 'mif-bpc-docs-nonce' );

        $folder_id = (int) $_POST['folder_id'];

        echo $this->get_folder_settings( $folder_id );
        echo $this->get_folder_nonce();

        wp_die();
    }



    // 
    // Ajax-помощник сохранения настроек папки
    // 

    function ajax_folder_settings_save_helper()
    {
        check_ajax_referer( 'mif-bpc-docs-folder-settings-nonce' );

        $folder_id = (int) $_POST['folder_id'];

        if ( ! $this->is_access( $folder_id, 'write' ) ) wp_die();

        if ( isset( $_POST['do'] ) ) {

            if ( $_POST['do'] == 'cancel' ) {

                // Нажали "Отмена" - просто показать папки

                echo $this->get_docs_content();

            } elseif ( $_POST['do'] == 'to-trash' ) {

                // Удалить в корзину

                $ret = ( $this->trash_folder( $folder_id ) ) ? $this->get_docs_content() : $this->error_msg( '004' );
                echo $ret;

            } else {

                echo $this->error_msg( '003' );

            }

        } else {

            // Сохраняем новые настройки папки

            $folder = get_post( $folder_id );
            
            if ( isset( $folder->post_status) && $folder->post_status != 'trash' ) {

                $publish = ( $_POST['publish'] == 'on' ) ? 'publish' : 'private';

                $folder_data = array(
                                    'ID' => (int) $_POST['folder_id'],
                                    'post_status' => $publish,
                                    'post_content' => trim( $_POST['desc'] ),
                                );

                if ( trim( $_POST['name'] ) != '' ) $folder_data['post_title'] = trim( $_POST['name'] );

                $ret = ( wp_update_post( wp_slash( $folder_data ) ) ) ? $this->get_docs_content() : $this->error_msg( '001' );
                echo $ret;

            } else {

                echo $this->error_msg( '002' );

            }
        }

        wp_die();
    }




    // 
    // Ajax-помощник обновления мета-информации документа
    // 

    function ajax_doc_meta_helper()
    {
        check_ajax_referer( 'mif-bpc-docs-nonce' );
        
        if ( isset( $_POST['doc_id'] ) ) {
            
            $doc_id = (int) $_POST['doc_id'];
            echo $this->get_doc_meta( $doc_id );

        }

        wp_die();
    }




    // 
    // Ajax-помощник информации статусной строки документа
    // 

    function ajax_doc_statusbar_info_helper()
    {
        check_ajax_referer( 'mif-bpc-docs-nonce' );
        
        if ( isset( $_POST['doc_id'] ) ) {
            
            $doc_id = (int) $_POST['doc_id'];
            echo $this->get_doc_statusbar_info( $doc_id );

        }

        wp_die();
    }




    // 
    // Ajax-помощник информации статусной строки папки
    // 

    function ajax_folder_statusbar_info_helper()
    {
        check_ajax_referer( 'mif-bpc-docs-nonce' );
        
        // if ( empty( $folder_id ) ) wp_die();

        if ( isset( $_POST['folder_id'] ) ) {
            
            // Показать статистику конкретной папки
            $folder_id = (int) $_POST['folder_id'];
            echo $this->get_folder_statusbar_info( $folder_id );

        } elseif ( isset( $_POST['all_folders'] ) && $_POST['all_folders'] == 'on' ) {

            // Показать статистику всех папок папки
            echo $this->get_all_folders_statusbar_info();

        }

        wp_die();
    }

}






?>