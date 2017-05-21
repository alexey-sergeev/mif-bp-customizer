<?php

//
// Документы
// 
//


defined( 'ABSPATH' ) || exit;

if ( mif_bpc_options( 'docs' ) ) {

    global $mif_bpc_docs;
    $mif_bpc_docs = new mif_bpc_docs();

}


class mif_bpc_docs {

    //
    // Загрузка документов
    //

    //
    // Папок на одной странице
    //

    public $folders_on_page = 12;

    //
    // Документов на одной странице
    //

    public $docs_on_page = 18;

    //
    // Ярлык системы документов
    //

    public $slug = 'docs';

    //
    // Название папки с документами
    //

    public $path = 'docs';

    //
    // Название папки по умолчанию
    //

    public $default_folder_name = 'New folder';

    //
    // Размер аватарки пользователя
    //

    public $avatar_size = 50;



    function __construct()
    {
       
        // Настройка типа записи
        add_action( 'bp_init', array( $this, 'create_post_type' ) );

        // Настройка страницы документов
        add_action( 'bp_activity_setup_nav', array( $this, 'nav' ) );
        add_action( 'bp_screens', array( $this, 'doc_page' ) );

        // Скачивание файла
        add_action( 'bp_init', array( $this, 'force_download' ) );

        // Ajax-события
        add_action( 'wp_ajax_mif-bpc-docs-upload-files', array( $this, 'ajax_upload_helper' ) );
        add_action( 'wp_ajax_mif-bpc-docs-network-link-files', array( $this, 'ajax_network_link_helper' ) );
        // add_action( 'wp_ajax_mif-bpc-docs-collection-more', array( $this, 'ajax_collection_more_helper' ) );
        add_action( 'wp_ajax_mif-bpc-docs-collection-show', array( $this, 'ajax_collection_helper' ) );
        add_action( 'wp_ajax_mif-bpc-docs-new-folder', array( $this, 'ajax_new_folder_helper' ) );
        add_action( 'wp_ajax_mif-bpc-docs-remove', array( $this, 'ajax_remove_helper' ) );
        add_action( 'wp_ajax_mif-bpc-docs-folder-statusbar-info', array( $this, 'ajax_folder_statusbar_info_helper' ) );
        add_action( 'wp_ajax_mif-bpc-docs-folder-settings', array( $this, 'ajax_folder_settings_helper' ) );
        add_action( 'wp_ajax_mif-bpc-docs-folder-settings-save', array( $this, 'ajax_folder_settings_save_helper' ) );

        // add_action( 'bp_init', array( $this, 'dialogues_nav' ) );
        // add_action( 'bp_screens', array( $this, 'compose_screen' ) );
        // add_filter( 'messages_template_view_message', array( $this, 'view_screen' ) );
        // add_filter( 'bp_get_total_unread_messages_count', array( $this, 'total_unread_messages_count' ) );
        // add_filter( 'bp_get_send_private_message_link', array( $this, 'message_link' ) );

        add_action( 'wp_enqueue_scripts', array( $this, 'load_js_helper' ) );   

        $this->default_folder_name = __( 'Новая папка', 'mif-bp-customizer' );
    }



    // 
    // Страница документов
    // 

    function create_post_type()
    {
        // f($_POST);

        // Тип записей - документ

        register_post_type( 'mif-bpc-doc',
            array(
                'labels' => array(
                    'name' => __( 'Документы', 'mif-bp-customizer' ),
                    'singular_name' => __( 'Документ', 'mif-bp-customizer' ),
                    'add_new' => __( 'Добавить новый', 'mif-bp-customizer' ),
                    'add_new_item' => __( 'Новый документ', 'mif-bp-customizer' ),
                    'edit' => __( 'Редактировать', 'mif-bp-customizer' ),
                    'edit_item' => __( 'Редактировать документ', 'mif-bp-customizer' ),
                    'new_item' => __( 'Новый документ', 'mif-bp-customizer' ),
                    'view' => __( 'Просмотр', 'mif-bp-customizer' ),
                    'view_item' => __( 'Просмотр документа', 'mif-bp-customizer' ),
                    'search_items' => __( 'Найти документ', 'mif-bp-customizer' ),
                    'not_found' => __( 'Документы не найдены', 'mif-bp-customizer' ),
                    'not_found_in_trash' => __( 'В корзине не найдено', 'mif-bp-customizer' ),
                    'parent' => __( 'Папка', 'mif-bp-customizer' ),
                ),
                'public' => true,
                'menu_position' => 15,
                'supports' => array( 'title', 'editor', 'thumbnail', 'custom-fields' ),
                'taxonomies' => array( 'mif-bpc-doc-folder-tax' ),
                'menu_icon' => 'dashicons-paperclip',
                'has_archive' => true,
                'rewrite' => array( 'slug' => $this->slug, 'with_front' => false ),                
            )
        );

        // Тип записей - папка

        register_post_type( 'mif-bpc-folder',
            array(
                'labels' => array(
                    'name' => __( 'Папки', 'mif-bp-customizer' ),
                    'singular_name' => __( 'Папка', 'mif-bp-customizer' ),
                    'add_new' => __( 'Добавить новую', 'mif-bp-customizer' ),
                    'add_new_item' => __( 'Новая папка', 'mif-bp-customizer' ),
                    'edit' => __( 'Редактировать', 'mif-bp-customizer' ),
                    'edit_item' => __( 'Редактировать папку', 'mif-bp-customizer' ),
                    'new_item' => __( 'Новая папка', 'mif-bp-customizer' ),
                    'view' => __( 'Просмотр', 'mif-bp-customizer' ),
                    'view_item' => __( 'Просмотр папки', 'mif-bp-customizer' ),
                    'search_items' => __( 'Найти папку', 'mif-bp-customizer' ),
                    'not_found' => __( 'Папки не найдены', 'mif-bp-customizer' ),
                    'not_found_in_trash' => __( 'В корзине не найдено', 'mif-bp-customizer' ),
                    'parent' => __( 'Папка', 'mif-bp-customizer' ),
                ),
                'public' => true,
                'menu_position' => 15,
                'supports' => array( 'title', 'editor', 'thumbnail', 'custom-fields' ),
                'taxonomies' => array( 'mif-bpc-doc-folder-tax' ),
                'menu_icon' => 'dashicons-paperclip',
                'has_archive' => true,
                'rewrite' => array( 'slug' => $this->slug, 'with_front' => false ),                
            )
        );

        // Таксономия для документов и папок

        register_taxonomy( 'mif-bpc-doc-folder-tax', 
            array( 'mif-bpc-doc', 'mif-bpc-folder' ), 
            array(
                'hierarchical' => false,
                'show_ui' => true,
                'query_var' => true,
                'rewrite' => array( 'slug' => 'olympic-tax' ),
            )
        );

        // add_rewrite_tag('%item%', '([^&]+)');
    }


    // 
    // Страница документов
    // 

    function nav()
    {
        global $bp;

        $url = $bp->displayed_user->domain . $this->slug . '/';
        // $parent_slug = $bp->messages->slug;

        bp_core_new_nav_item( array(  
                'name' => __( 'Документы', 'mif-bp-customizer' ),
                'slug' => $this->slug,
                'position' => 90,
                'show_for_displayed_user' => true,
                // 'screen_function' => array( $this, 'screen' ), 
                'default_subnav_slug' => 'folder',
                // 'item_css_id' => $this->slug
            ) );

        bp_core_new_subnav_item( array(  
                'name' => __( 'Папки', 'mif-bp-customizer' ),
                'slug' => 'folder',
                'parent_url' => $url, 
                'parent_slug' => $this->slug, 
                'screen_function' => array( $this, 'screen' ), 
                'position' => 10,
                // 'user_has_access'=>  bp_is_my_profile() 
            ) );

        bp_core_new_subnav_item( array(  
                'name' => __( 'Создать папку', 'mif-bp-customizer' ),
                'slug' => 'new-folder',
                'parent_url' => $url, 
                'parent_slug' => $this->slug, 
                'screen_function' => array( $this, 'screen' ), 
                'position' => 20,
                // 'user_has_access'=>  bp_is_my_profile() 
            ) );

       
    }



    //
    // Содержимое страниц
    //

    function screen()
    {
        add_action( 'bp_template_content', array( $this, 'body' ) );
        bp_core_load_template( apply_filters( 'bp_core_template_plugin', 'members/single/plugins' ) );
    }

    function body()
    {
        $tpl_file = 'docs-page.php';

        if ( $template = locate_template( $tpl_file ) ) {
            load_template( $template, false );
        } else {
            load_template( dirname( __FILE__ ) . '/../templates/' . $tpl_file, false );
        }
    }



    // 
    // Страница просмотра отдельного документа
    // 

    function doc_page()
    {
        if ( bp_current_component() != 'docs' || ! is_numeric( bp_current_action() ) ) return false;

        // bp_core_load_template( 'members/docs-page-doc' );
    	global $wp_query;

        $tpl_file = 'docs-page-doc.php';

        status_header( 200 );
		$wp_query->is_page     = true;
		$wp_query->is_singular = true;
		$wp_query->is_404      = false;

        if ( $template = locate_template( $tpl_file ) ) {
            load_template( $template, false );
        } else {
            load_template( dirname( __FILE__ ) . '/../templates/' . $tpl_file, false );
        }

        exit();
    }



    // 
    // JS-помощник
    // 

    function load_js_helper()
    {
        wp_enqueue_script( 'mif_bpc_docs_helper', plugins_url( '../js/docs.js', __FILE__ ) );
    }



    // 
    // Ajax-помощник удаления или восстановления папки или документа
    // 

    function ajax_remove_helper()
    {
        check_ajax_referer( 'mif-bpc-docs-collection-nonce' );

        // $user_id = bp_loggedin_user_id();
        // if ( empty( $user_id ) ) wp_die();

        $item_id = (int) $_POST['item_id'];

        // $doc_id = $item_id;

        if ( ! $this->is_access( $item_id, 'write' ) ) wp_die();

        $is_doc = ( $this->is_doc( $item_id ) ) ? true : false;
        $is_folder = ( $this->is_folder( $item_id ) ) ? true : false;
        $mode = ( $_POST['mode'] == 'page' ) ? 'page' : 'item';

        $item = get_post( $item_id );

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
                if ( $is_doc ) if ( $this->delete_doc( $item_id ) ) echo $this->show_response( $item_id, 'doc-empty', $mode, $item->post_title );
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

    function show_response( $item_id = NULL, $item_type = 'doc', $mode = 'item', $name )
    {
        if ( $item_id == NULL ) return;

        // Если запрос пришел с кнопки на элементе в каталоге элементов

        if ( $mode == 'item' ) {

            if( $item_type == 'doc' ) $out = $this->get_doc_item( $item_id );
            if( $item_type == 'doc-empty' ) $out = '<!-- empty -->';
            if( $item_type == 'folder' ) $out = $this->get_folder_item( $item_id );
            if( $item_type == 'folder-empty' ) $out = '<!-- empty -->';

        }

        // Если запрос пришел со страницы элемента

        if ( $mode == 'page' ) {

            // if( $item_type == 'doc' ) $out = $this->get_doc_item( $item_id );
            // if( $item_type == 'doc-empty' ) $out = '<!-- empty -->';
            if( $item_type == 'folder' ) $out = $this->get_folder_content( $item_id, __( 'Папка и все удалённые вместе с ней документы восстановлены', 'mif-bp-customizer' ) );
            if( $item_type == 'folder-empty' ) {
                
                $msg = sprintf( __( 'Папка «%s» окончательно удалена', 'mif-bp-customizer' ), '<strong>' . $name . '</strong>' );
                $msg .= '<p>' . __( 'Вернуться', 'mif-bp-customizer' ) . ': <strong><a href="' . $this->get_docs_url() . '">' . __( 'документы', 'mif-bp-customizer' ) . '</a></strong>';
                
                $out = mif_bpc_message( $msg );

            }
                

        }

        return apply_filters( 'mif_bpc_docs_show_response', $out, $item_id, $item_type, $mode );
    }


    // 
    // Удалить документ в корзину
    // 

    function trash_doc( $doc_id = NULL )
    {
        if ( ! $this->is_doc( $doc_id ) ) return false;

        $doc = get_post( $doc_id );
        $this->clean_folder_size( $doc->post_parent );

        $ret = wp_trash_post( $doc_id );

        return apply_filters( 'mif_bpc_docs_trash_doc', $ret, $doc_id );
    }



    // 
    // Восстановить документ из корзины
    // 

    function untrash_doc( $doc_id = NULL )
    {
        if ( ! $this->is_doc( $doc_id ) ) return false;

        $doc = get_post( $doc_id );
        $this->clean_folder_size( $doc->post_parent );
        
        // Восстановить папку (вдруг она тоже в корзине?)
        //
        // Вернет в $ret2:
        //          true - если это не папка
        //          false - если папка, но не в корзине
        //          $post (array) - если папка была в корзине и она восстановлена

        $ret2 = true;
        if ( $this->is_folder( $doc->post_parent ) ) {

            $ret2 = wp_untrash_post( $doc->post_parent );
            $ret3 = delete_post_meta( $doc->post_parent, 'mif-bpc-trashed-docs' );

        }

        $ret = wp_untrash_post( $doc_id );


        return apply_filters( 'mif_bpc_docs_untrash_doc', $ret, $ret2, $ret3, $doc_id );
    }



    // 
    // Удалить документ навсегда
    // 

    function delete_doc( $doc_id = NULL )
    {
        if ( ! $this->is_doc( $doc_id ) ) return false;

        $ret = wp_delete_post( $doc_id );

        return apply_filters( 'mif_bpc_docs_untrash_doc', $ret, $doc_id );
    }


    // 
    // Ajax-помощник создания папки
    // 

    function ajax_new_folder_helper()
    {
        check_ajax_referer( 'mif-bpc-docs-new-folder-nonce' );

        $user_id = bp_loggedin_user_id();
        if ( empty( $user_id ) ) wp_die();

        $publish = ( $_POST['publish'] == 'on' ) ? 'publish' : 'private';
        $name = ( trim( $_POST['name'] ) == '' ) ? $this->default_folder_name : trim( $_POST['name'] );

        $folder_data = array(
            'post_type' => 'mif-bpc-folder',
            'post_title' => $name,
            'post_content' => trim( $_POST['desc'] ),
            'post_status' => $publish,
            // 'post_parent' => $group_id,
            'post_author' => $user_id,
            'comment_status' => 'closed',
            'ping_status' => 'closed'

        );

        $post_id = wp_insert_post( wp_slash( $folder_data ) );

        if ( $post_id ) {

            $folder_url = $this->get_docs_url() . '/folder/' . $post_id . '/';
            echo $folder_url;

        }

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
            
            $post_id = $this->doc_save( $name, $path, $user_id, $_POST['folder_id'] );
            echo $this->get_doc_item( $post_id );

        } else {

            echo __( 'Ошибка', 'mif-bp-customizer' );

        }

        wp_die();
    }



    // 
    // Ajax-помощник загрузки страниц коллекции документов
    // 

    function ajax_collection_helper()
    {
        check_ajax_referer( 'mif-bpc-docs-collection-nonce' );
        $page = ( isset( $_POST['page'] ) ) ? (int) $_POST['page'] : 1;
        $trashed = (int) $_POST['trashed'];

        if ( isset( $_POST['folder_id'] ) ) {

            $folder_id = (int) $_POST['folder_id'];
            echo $this->get_docs_collection( $folder_id, $page, $trashed );

        } else {

            $mode = false;
            
            if ( bp_is_user() ) {

                $mode = 'member';
                $item_id = bp_displayed_user_id();

            } elseif ( bp_is_user() ) {

                $mode = 'group';
                $item_id = bp_get_current_group_id();

            }

            // $item_id = (int) $_POST['item_id'];
            // $mode = $_POST['mode'];

            if ( $mode ) echo $this->get_folders( $page, $item_id, $mode, $trashed );
            
        }

        wp_die();
    }



    // // 
    // // Ajax-помощник загрузки продолжения коллекции документов
    // // 

    // function ajax_collection_more_helper()
    // {
    //     check_ajax_referer( 'mif-bpc-docs-collection-more-nonce' );

    //     $page = (int) $_POST['page'];

    //     if ( isset( $_POST['folder_id'] ) ) {

    //         $folder_id = (int) $_POST['folder_id'];
    //         $trashed = (int) $_POST['trashed'];
    //         echo $this->get_docs_collection( $folder_id, $page, $trashed );

    //     } else {

    //         $item_id = (int) $_POST['item_id'];
    //         $mode = $_POST['mode'];
    //         echo $this->get_folders( $page, $item_id, $mode );
    //         f($_POST);
    //     }
        

    //     wp_die();
    // }



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

                // Добавить его в базу данных

                // $docs_data = array(
                //     'post_type' => 'mif-bpc-doc',
                //     'post_title' => $filename,
                //     'post_content' => $path,
                //     'post_status' => 'publish',
                //     'post_author' => (int) $user_id,
                //     'comment_status' => 'closed',
                //     'ping_status' => 'closed'
                // );

                // if ( isset( $_POST['folder_id'] ) ) $docs_data['post_parent'] = (int) $_POST['folder_id'];

                // $post_id = wp_insert_post( wp_slash( $docs_data ) );

                $post_id = $this->doc_save( $filename, $path, $user_id, $_POST['folder_id'], $_FILES['file']['type'] );
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
    // Сохранить документ
    // 

    function doc_save( $name, $path, $user_id = NULL, $folder_id = NULL, $file_type = NULL )
    {
        if ( $user_id == NULL ) $user_id = bp_loggedin_user_id();

        $docs_data = array(
            'post_type' => 'mif-bpc-doc',
            'post_title' => $name,
            'post_content' => $path,
            'post_status' => 'publish',
            'post_author' => (int) $user_id,
            'comment_status' => 'closed',
            'ping_status' => 'closed'
        );

        if ( isset( $folder_id ) ) $docs_data['post_parent'] = (int) $folder_id;
        if ( isset( $file_type ) ) $docs_data['post_mime_type'] = $file_type;

        $post_id = wp_insert_post( wp_slash( $docs_data ) );
        
        $this->clean_folder_size( $folder_id );

        return apply_filters( 'mif_bpc_docs_doc_save', $post_id, $title, $location, $user_id, $folder_id );
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
        // $out .= '<form>
        // <input type="file" name="files[]" multiple="multiple">
        // <div class="button">' . __( 'Перетащите файлы сюда', 'mif-bp-customizer' ) . '<br />
        // ' . __( 'или', 'mif-bp-customizer' ) . '
        // <p><button>' . __( 'Выберите файлы', 'mif-bp-customizer' ) . '</button></div>
        // <input type="hidden" name="nonce" value="' . wp_create_nonce( 'mif-bpc-docs-file-upload-nonce' ) . '">';
        
        // if ( isset( $folder_id ) ) $out .= '<input type="hidden" name="folder_id" value="' . $folder_id . '">';
        $out .= '<input type="hidden" name="folder_id" value="' . $folder_id . '">';
        
        $out .= '</form>';
        $out .= '</div>';

        return apply_filters( 'mif_bpc_docs_get_upload_form', $out, $folder_id );
    }


    
    // 
    // Получить данные коллекции папок
    // 

    function get_folders_data( $item_id, $mode = 'member', $page = NULL, $trashed = false, $posts_per_page = NULL )
    {
        $item_id = bp_displayed_user_id();

        $arr = array( 'publish', 'private' );
        if ( $trashed ) $arr[] = 'trash';

        if ( $posts_per_page == NULL ) $posts_per_page = $this->folders_on_page;

        $args = array(
            // 'numberposts' => $this->folders_on_page,
            'posts_per_page' => $posts_per_page,
            'paged' => $page,
            'author' => $item_id,
            // 'category'    => 0,
            'orderby'     => 'date',
            'order'       => 'DESC',
            // 'include'     => array(),
            // 'exclude'     => array(),
            // 'meta_key'    => '',
            // 'meta_value'  =>'',
            'post_type'   => 'mif-bpc-folder',
            'post_status' => implode( ',', $arr ),
        );

        $folders = get_posts( $args );

        return apply_filters( 'mif_bpc_docs_get_folders_data', $folders, $item_id, $mode, $page, $posts_per_page );
    }


    // 
    // Все папки пользователя или группы
    // 

    function get_folders( $page = 1, $item_id = NULL, $mode = 'member', $trashed = false )
    {
        if ( ! in_array( $mode, array( 'member', 'group' ) ) ) return;

        // $item_id = bp_displayed_user_id();

        // $arr = array( 'publish', 'private' );
        // if ( $trashed ) $arr[] = 'trash';

        // $args = array(
        //     // 'numberposts' => $this->folders_on_page,
        //     'posts_per_page' => $this->folders_on_page,
        //     'paged' => $page,
        //     'author' => $item_id,
        //     // 'category'    => 0,
        //     'orderby'     => 'date',
        //     'order'       => 'DESC',
        //     // 'include'     => array(),
        //     // 'exclude'     => array(),
        //     // 'meta_key'    => '',
        //     // 'meta_value'  =>'',
        //     'post_type'   => 'mif-bpc-folder',
        //     'post_status' => implode( ',', $arr ),
        // );

        $out = '';
        if ( $page === 1 ) $out .= '<div class="collection clearfix">';

        // $folders = get_posts( $args );
        $folders = $this->get_folders_data( $item_id, $mode, $page, $trashed );

        if ( $folders ) {

            $arr = array();
            foreach( $folders as $folder ) $arr[] = $this->get_folder_item( $folder );

            $out .= implode( "\n", $arr );

            if ( count( $folders ) == $this->folders_on_page ) {

                // $out .= '<form><div class="more">
                // <button>' . __( 'Показать ещё', 'mif-bp-customizer' ) . '</button>
                // <i class="fa fa-spinner fa-spin fa-3x fa-fw"></i></div>';
                // $out .= '<input type="hidden" name="nonce" value="' . wp_create_nonce( 'mif-bpc-docs-folders-more-nonce' ) . '">';
                // $out .= '<input type="hidden" name="item_id" value="' . $item_id . '">';
                // $out .= '<input type="hidden" name="mode" value="' . $mode . '">';
                // $next_page = (int) $page + 1;
                // $out .= '<input type="hidden" name="page" value="' . $next_page . '">';
                // $out .= '</form>';

                // $out .= $this->get_more_button( $page, array( 'mode' => $mode, 'item_id' => $item_id ) );
                $out .= $this->get_more_button( $page );

            }

            // $out .= '111';
        
        } else {

            if ( $page === 1 ) $out = __( 'Папки не обнаружены', 'mif-bp-customizer' );

        }

        if ( $page === 1 ) {
            
            $out .= '</div>';
            // $out .= '<input type="hidden" id="docs-collection-nonce" value="' . wp_create_nonce( 'mif-bpc-docs-collection-nonce' ) . '">';
            // $out .= $this->get_folder_statusbar();

        }

        return apply_filters( 'mif_bpc_docs_get_folders', $out, $page, $item_id, $mode, $arr );
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
    // Получить данные коллекции документов
    // 

    function get_docs_collection_data( $folder_id, $page = NULL, $trashed = false, $posts_per_page = NULL )
    {

        if ( $posts_per_page == NULL ) $posts_per_page = $this->docs_on_page;

        $arr = array( 'publish', 'private' );
        if ( $trashed ) $arr[] = 'trash';

        $args = array(
            // 'numberposts' => $this->docs_on_page,
            'posts_per_page' => $posts_per_page,
            // 'author' => bp_displayed_user_id(),
            // 'category'    => 0,
            'orderby'     => 'date',
            'order'       => 'DESC',
            // 'include'     => array(),
            // 'exclude'     => array(),
            // 'meta_key'    => '',
            // 'meta_value'  =>'',
            'post_type'   => 'mif-bpc-doc',
            'post_parent' => $folder_id,
            'post_status' => implode( ',', $arr ),
            'paged' => $page,
        );

        // if ( isset( $page ) ) $args['paged'] = $page;

        $docs = get_posts( $args );

        return apply_filters( 'mif_bpc_docs_get_docs_collection_data', $docs, $folder_id, $page, $posts_per_page );
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
        // $out .= '<input type="hidden" name="_wpnonce" value="' . wp_create_nonce( 'mif-bpc-docs-collection-more-nonce' ) . '">';
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
            $url = $this->get_docs_url() . '/' . $doc->ID;
            $a1 = '<a href="' . $url . '/">';
            $a2 = '</a>';
            $left = '<a href="' . $url . '/remove/" data-item-id="' . $doc->ID . '" class="button item-remove left" title="' . __( 'Удалить', 'mif-bp-customizer' ) . '"><i class="fa fa-times"></i></a>';

            $doc_type = $this->get_doc_type( $doc );

            if ( $doc_type == 'file' || $doc_type == 'image' ) {

                $right = '<a href="' . $url . '/download/" class="button doc-download right" title="' . __( 'Скачать', 'mif-bp-customizer' ) . '"><i class="fa fa-download"></i></a>';
            
            } elseif ( $doc_type == 'link' ) {

                $right = '<a href="' . $doc->post_content . '" target="blank" class="button doc-download right" title="' . __( 'Открыть', 'mif-bp-customizer' ) . '"><i class="fa fa-arrow-up"></i></a>';

            } else {

                $right = '';

            }

            if ( $doc->post_status == 'trash' ) {

                $left = '<a href="' . $url . '/restore/" data-item-id="' . $doc->ID . '" class="button item-remove restore left" title="' . __( 'Восстановить', 'mif-bp-customizer' ) . '"><i class="fa fa-undo"></i></a>';
                $right = '<a href="' . $url . '/remove/" data-item-id="' . $doc->ID . '" class="button item-remove right" title="' . __( 'Удалить совсем', 'mif-bp-customizer' ) . '"><i class="fa fa-times"></i></a>';

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
    // Выводит заголовок на странице папки
    // 

    function get_folder_header( $folder = NULL )
    {
        if ( $folder == NULL ) return;

        $out = '<h2><a href="' . $this->get_docs_url() . '/">' . __( 'Папки', 'mif-bp-customizer' ) . '</a> /  
        <a href="' . $this->get_docs_url() . '/folder/' . $folder->ID . '/">' . $folder->post_title . '</a></h2>';

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
            
            // $out .= '<h2>' . __( 'Папки', 'mif-bp-customizer' ) . ' / ' . $folder->post_title . '</h2>';
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

        // if ( bp_is_current_action( 'new-folder' ) ) $tpl_file = 'docs-folder-settings.php';

        return apply_filters( 'mif_bpc_docs_get_docs_content', $out, $ca );
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
        // if ( $folder_id == NULL ) {

        //     if ( ! ( bp_current_action() == 'folder' && is_numeric( bp_action_variable( 0 ) ) ) ) return;
        //     $folder_id = bp_action_variable( 0 );

        // }

        $data = $this->get_all_folders_size();

        $out = '<span class="one">' . __( 'Папок', 'mif-bp-customizer' ) . ':</span> <span class="two">' . $data['count'] . '</span>
        <span class="one">' . __( 'Общий объем', 'mif-bp-customizer' ) . ':</span> <span class="two">' . mif_bpc_format_file_size( $data['size'] ) . '</span>';

        return apply_filters( 'mif_bpc_docs_get_folder_statusbar_info', $out, $folder_id, $data );
    }



    // 
    // Возвращает размер папки (количество и общий объем файлов)
    // 

    function get_all_folders_size()
    {
        $item_id = bp_displayed_user_id();
        $mode = 'member';

        $folders = $this->get_folders_data( $item_id, $mode, 0, false, -1 );

        $count = count( $folders );
        
        $size = 0;
        foreach ( (array) $folders as $folder ) {
            
            $folder_size = $this->get_folder_size( $folder );
            $size += $folder_size['size'];

        }

        $data = array( 'count' => $count, 'size' => $size );
     
        return apply_filters( 'mif_bpc_docs_get_all_folders_size', $data );
    }


    // 
    // Возвращает размер документа (байты на диске)
    // 

    function get_doc_size( $doc = NULL )
    {
        if ( $doc == NULL ) return 0;

        $doc_type = $this->get_doc_type( $doc );

        if ( ! ( $doc_type == 'file' || $doc_type == 'image' ) ) return 0;

        $ret = get_post_meta( $doc->ID, 'mif-bpc-doc-size', true );

        if ( $ret === '' ) {

            $upload_dir = (object) wp_upload_dir();
            $file = $upload_dir->basedir . $doc->post_content; 

            $ret = filesize ( $file );

            if ( $ret ) update_post_meta( $doc->ID, 'mif-bpc-doc-size', $ret );
        }


        return apply_filters( 'mif_bpc_docs_get_doc_size', $ret, $doc );
    }



    // 
    // Возвращает размер папки (количество и общий объем файлов)
    // 

    function get_folder_size( $folder = NULL )
    {
        if ( is_numeric( $folder ) ) $folder = get_post( $folder );
        if ( ! $this->is_folder( $folder->ID ) ) return false;

        $data = get_post_meta( $folder->ID, 'mif-bpc-folder-size', true );

        if ( $data === '' ) {

            $trashed = ( $folder->post_status == 'trash' ) ? true : false;
            $docs = $this->get_docs_collection_data( $folder->ID, 0, $trashed, -1 );

            $count = count( $docs );
           
            $size = 0;
            foreach ( (array) $docs as $doc ) $size += $this->get_doc_size( $doc );

            $data = array( 'count' => $count, 'size' => $size );

            update_post_meta( $folder->ID, 'mif-bpc-folder-size', $data );

        }

        return apply_filters( 'mif_bpc_docs_get_folder_size', $data, $folder );
    }



    // 
    // Очищает данные о размере папки
    // 

    function clean_folder_size( $folder = NULL )
    {
        if ( is_numeric( $folder ) ) $folder = get_post( $folder );
        if ( ! $this->is_folder( $folder->ID ) ) return false;

        $ret = delete_post_meta( $folder->ID, 'mif-bpc-folder-size' );

        return apply_filters( 'mif_bpc_docs_clean_folder_size', $ret, $folder );
    }



    // 
    // Ajax-помощник окна настройки папки
    // 

    function ajax_folder_settings_helper()
    {
        check_ajax_referer( 'mif-bpc-docs-collection-nonce' );

        $folder_id = (int) $_POST['folder_id'];

        echo $this->get_folder_settings( $folder_id );
        echo $this->get_docs_collection_nonce();


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
    // Удалить папку в корзину
    // 

    function trash_folder( $folder_id = NULL )
    {
        if ( ! $this->is_folder( $folder_id ) ) return false;

        $docs = $this->get_docs_collection_data( $folder_id, 0, 0, -1 );

        // Удалить в корзину все документы папки, запомнив их номера

        $arr = array();
        $ret = array();
        foreach ( (array) $docs as $doc ) {

            $arr[] = $doc->ID;
            $ret[] = $this->trash_doc( $doc->ID );

        }

        // Сохранить в мета-поле папки номера удаленных документов

        $ret2 = update_post_meta( $folder_id, 'mif-bpc-trashed-docs', implode( ',', $arr ) );

        // Удалить папку

        // $this->clean_folder_size( $folder_id );
        $ret3 = wp_trash_post( $folder_id );

        return apply_filters( 'mif_bpc_docs_trash_folder', $ret3, $ret2, $ret, $folder_id );
    }



    // 
    // Восстановить папку из корзины
    // 

    function untrash_folder( $folder_id = NULL )
    {
        if ( ! $this->is_folder( $folder_id ) ) return false;

        // Восстановить папку

        $ret = wp_untrash_post( $folder_id );

        // Восстановить все документы

        $docs_ids = get_post_meta( $folder_id, 'mif-bpc-trashed-docs', true );
        $arr = explode( ',', $docs_ids );

        $ret2 = array();
        foreach ( (array) $arr as $doc_id ) {

            $ret2[] = $this->untrash_doc( $doc_id );

        }

        // Очистить информацию о ранее удаленных документах

        $ret3 = delete_post_meta( $folder_id, 'mif-bpc-trashed-docs' );

        return apply_filters( 'mif_bpc_docs_untrash_folder', $ret, $ret2, $ret3, $folder_id );
    }



    // 
    // Удалить папку навсегда
    // 

    function delete_folder( $folder_id = NULL )
    {
        if ( ! $this->is_folder( $folder_id ) ) return false;

        $docs = $this->get_docs_collection_data( $folder_id, 0, 1, -1 );

        // Удалить навсегда все документы папки

        $ret = array();
        foreach ( (array) $docs as $doc ) {

            $ret[] = wp_delete_post( $doc->ID );

        }

        // Удалить папку

        $ret3 = wp_delete_post( $folder_id );

        return apply_filters( 'mif_bpc_docs_delete_folder', $ret3, $ret2, $ret, $folder_id );
    }



    // 
    // Проверяет, является ли объект документом
    // 

    function is_doc( $doc_id = NULL )
    {
        if ( $doc_id == NULL ) return false;

        $doc = get_post( $doc_id );

        $ret = false;
        if ( isset( $doc->post_type ) && $doc->post_type == 'mif-bpc-doc' ) $ret = true;

        return apply_filters( 'mif_bpc_docs_is_doc', $ret, $doc_id );
    }



    // 
    // Проверяет, является ли объект папкой
    // 

    function is_folder( $folder_id = NULL )
    {
        if ( $folder_id == NULL ) return false;

        $folder = get_post( $folder_id );

        $ret = false;
        if ( isset( $folder->post_type ) && $folder->post_type == 'mif-bpc-folder' ) $ret = true;

        return apply_filters( 'mif_bpc_docs_is_folder', $ret, $folder_id );
    }



    // 
    // Выводит сообщение об ошибке
    // 

    function error_msg( $s = '000' )
    {
        $out = mif_bpc_message( sprintf( __( 'Ошибка %s. Что-то пошло не так', 'mif-bp-customizer' ), $s ), 'error' );
        return apply_filters( 'mif_bpc_docs_error_msg', $out, $s );
    }

    // // 
    // // Окно настройки папки
    // // 

    // function get_folder_settings_page( $folder_id = NULL )
    // {
    //     $folder = get_post( $folder_id );
    //     if ( ( isset( $folder->post_type ) && $folder->post_type == 'folder' ) ) return;

    //     $out = '';

    //     $out .= $folder->post_title;
    //     $out .= $folder->post_content;
    //     $out .= $folder->post_status;

    //     return apply_filters( 'mif_bpc_docs_get_folder_settings_page', $out, $folder_id );
    // }



    // 
    // Ajax-помощник информации статусной строки
    // 

    function ajax_folder_statusbar_info_helper()
    {
        check_ajax_referer( 'mif-bpc-docs-collection-nonce' );
        
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



    // 
    // Адрес папки для файлов пользователя
    // 
    // /docs/2017/<user_id>
    // Используется как продолжение /wp-content/uploads
    //

    function get_docs_path( $user_id = NULL )
    {
        if ( $user_id == NULL ) $user_id = bp_loggedin_user_id();

        $upload = wp_upload_dir();
        $upload_dir = $upload['basedir'];

        $time = current_time( 'mysql' );
		$y = substr( $time, 0, 4 );
		// $m = substr( $time, 5, 2 );

        // $path = $upload_dir . '/' . $this->path . '/' . $y . '/' . $user_id;
        $path = '/' . $this->path . '/' . $y . '/' . $user_id;
        $path = apply_filters( 'mif_bpc_docs_get_path', $path, $user_id );

        $ret = ( wp_mkdir_p( $upload_dir . $path ) ) ? $path : false;

        return $ret;
    }



    // 
    // Инициирует загрузку документа
    // 

    function force_download()
    {
        if ( bp_current_component() != 'docs' || ! is_numeric( bp_current_action() ) ) return false;
        if ( bp_action_variable( 0 ) != 'download' ) return false;
        
        $doc = get_post( bp_current_action() );

        if ( empty( $doc ) ) return false;

        $folder_id = $doc->post_parent;
        if ( ! $this->is_access( $folder_id, 'read' ) ) return false;

        $upload_dir = (object) wp_upload_dir();
        $file = $upload_dir->basedir . $doc->post_content; 
        $filename = str_replace( array( '*', '|', '\\', ':', '"', '<', '>', '?', '/' ), '_', $doc->post_title );

        if ( file_exists( $file ) ) {

            if ( ob_get_level() ) ob_end_clean();

            header( 'Content-Description: File Transfer' );
            header( 'Content-Type: ' . $doc->post_mime_type );
            header('Content-Type: application/octet-stream');
            header( 'Content-Disposition: attachment; filename=' . $filename );
            header( 'Content-Transfer-Encoding: binary');
            header( 'Expires: 0');
            header( 'Cache-Control: must-revalidate');
            header( 'Pragma: public');
            header( 'Content-Length: ' . filesize( $file ) );

            if ( $fd = fopen( $file, 'rb' ) ) {

                while ( ! feof( $fd ) ) print fread( $fd, 1024 );
                fclose( $fd );

            }

        }

        exit;
    }



    // 
    // Логотип файла
    // 

    function get_file_logo( $doc, $size = 3 )
    {
        $type = ( preg_match( '/^http/', $doc->post_content ) ) ? $doc->post_content : $doc->post_title;
        return apply_filters( 'mif_bpc_docs_get_file_logo', get_file_icon( $type, 'fa-' . $size . 'x' ), $doc );
    }



    // 
    // Возвращает тип документа (image, file, link или html)
    // 

    function get_doc_type( $doc )
    {
        if ( empty( $doc ) ) return false;

        if ( preg_match( '/^\/' . $this->path . '\//', $doc->post_content ) ) {

            // Если содержимое начинается с /docs/

            $ext = end( explode( ".", $doc->post_title ) );
            $img_types = apply_filters( 'mif_bpc_docs_img_types', array( 'png', 'jpg', 'jpeg', 'gif' ) );

            if ( in_array( $ext, $img_types ) ) {

                $ret = 'image';

            } else {

                $ret = 'file';

            }

        } elseif ( preg_match( '/^https?:\/\//', $doc->post_content ) ) {

            // Если содержимое начинается с http

            $ret = 'link';

        } else {

            // Если содержимое начинается с http

            $ret = 'html';

        }

        // p($ret);

        return apply_filters( 'mif_bpc_docs_get_doc_type', $ret, $doc );
    }


    //
    // Возвращает путь к папке
    //

    function get_folder_url( $folder_id = NULL )
    {
        if ( $folder_id == NULL ) return;
        $folder_url = $this->get_docs_url() . '/folder/' . $folder_id . '/';

        return apply_filters( 'mif_bpc_docs_get_folder_url', $folder_url, $folder_id );
    }



    //
    // Получает данные документа, отображаемого на экране
    //

    function get_doc_data()
    {
        if ( bp_current_component() != 'docs' || ! is_numeric( bp_current_action() ) ) return false;
        // if ( ! $this->is_access( $folder_id, 'read' ) ) return false;

        $doc_id = bp_current_action();
        $doc_data = get_post( $doc_id );

        return apply_filters( 'mif_bpc_docs_get_doc_data', $doc_data, $doc_id );
    }



    //
    // Выводит имя документа
    //

    function get_name()
    {
        $out = '';

        $doc = $this->get_doc_data();
        if ( empty( $doc ) ) return;

        $out .= $doc->post_title;
        // $out .= '<div class="name">' . $doc->post_title . '</div>';

        return apply_filters( 'mif_bpc_docs_get_name', $out, $doc );
    }



    //
    // Выводит документ на страницу документа
    //

    function get_doc()
    {
        $out = '<div class="doc">';

        $doc = $this->get_doc_data();
        if ( empty( $doc ) ) return;

        $doc_type = $this->get_doc_type( $doc );
        $url = $this->get_docs_url() . '/' . $doc->ID . '/download';
        $html = $doc->the_content;

        // Если ссылка, то решить, отображать ее как HTML или как простую ссылку (оформляется как файл)

        if ( $doc_type == 'link' ) {

            $html = wp_oembed_get( $doc->post_content );

            if ( $html  ) {

                $doc_type = 'html';

            } else {

                $doc_type = 'file';
                $url = $doc->post_content;

            }

        }

        // Показать HTML (из базы данных, или сформироанную выше через oembed)

        if ( $doc_type == 'html' ) {

            $name = ( preg_match( '/^https?:\/\//', $doc->post_title ) ) ? '' : '<div class="name">' . $doc->post_title . '</div>';

            $out .= '
            <div class="html">' . $html . '</div>
            <div>
                ' . $name . '
                <div class="description">' . $doc->post_excerpt . '</div>
            </div>';

        }

        // Показать файл (или простую ссылку)

        if ( $doc_type == 'file' ) {

            $item = $this->get_file_logo( $doc );

            $out .= '
            <div class="file">
                <a href="' . $url . '"><span class="item">' . $item . '</span></a>
            </div>
            <div>
                <div class="name"><a href="' . $url . '">' . $doc->post_title . '</a></div>
                <div class="description">' . $doc->post_excerpt . '</div>
            </div>';

        } 
        
        // Показать картинку (целиком)

        if ( $doc_type == 'image' ) {

            // $url = $this->get_docs_url() . '/' . $doc->ID . '/download';

            $out .= '
            <div class="image">
                <a href="' . $url . '"><img src="' . $url . '"></a>
            </div>
            <div>
                <div class="name"><span class="one">' . __( 'Файл', 'mif-bp-customizer' ) . ':</span> <span class="two"><a href="' . $url . '">' . $doc->post_title . '</a></span></div>
                <div class="description">' . $doc->post_excerpt . '</div>
            </div>';

        } 
        
        $out .= '</div>';
        
        // $out .= '<p>' . $logo;
        // $out .= '<p>' . $doc->post_title;
        // $out .= '<p>' . $doc->post_content;

        return apply_filters( 'mif_bpc_docs_get_doc', $out, $doc );
    }



    //
    // Выводит владельца документа
    //

    function get_owner()
    {
        $out = '';

        $doc = $this->get_doc_data();
        if ( empty( $doc ) ) return;

        $avatar = get_avatar( $doc->post_author, apply_filters( 'mif_bpc_docs_avatar_size', $this->avatar_size ) );
        // $title = bp_core_get_user_displayname( $doc->post_author );
        $author = mif_bpc_get_member_name( $doc->post_author );

        $out .= '<div class="owner clearfix"><a href="' . bp_core_get_user_domain( $doc->post_author ) . '" target="blank"><span class="one">' . $avatar . '</span><span class="two">' . $author . '</span></a></div>';

        return apply_filters( 'mif_bpc_docs_get_owner', $out, $doc );
    }


    //
    // Выводит папку документа
    //

    function get_folder()
    {
        $out = '';

        $doc = $this->get_doc_data();
        if ( empty( $doc ) ) return;

        $folder = get_post( $doc->post_parent );
        if ( empty( $folder ) ) return;

        $folder_url = $this->get_docs_url() . '/folder/' . $folder->ID . '/';

        $out .= '<div class="folder"><span class="one">' . __( 'Папка', 'mif-bp-customizer' ) . ':</span> <span class="two"><a href="' . $folder_url . '">' . $folder->post_title . '</a></span></div>';

        return apply_filters( 'mif_bpc_docs_get_folder', $out, $doc, $folder );
    }



    //
    // Выводит группу документа
    //

    function get_group()
    {
        return apply_filters( 'mif_bpc_docs_get_group', $out, $doc );
    }



    //
    // Выводит время размещения документа
    //

    function get_date()
    {
        $out = '';

        $doc = $this->get_doc_data();
        if ( empty( $doc ) ) return;

        $txt = ( $doc->post_date_gmt == $doc->post_modified_gmt ) ? __( 'Опубликовано', 'mif-bp-customizer' ) : __( 'Изменено', 'mif-bp-customizer' );

        $out .= '<div class="date"><span class="one">' . $txt . ':</span> <span class="two">' . mif_bpc_time_since( $doc->post_modified_gmt ) . '</span></div>';

        return apply_filters( 'mif_bpc_docs_get_date', $out, $doc );
    }



    //
    // Выводит ссылку на следующий документ
    //

    function get_next()
    {
        $out = '';

        $out .= '<div class="next"><a href="11"><span>' . __( 'туда', 'mif-bp-customizer' ) . '</span> <i class="fa fa-arrow-right"></i></a></div>';

        return apply_filters( 'mif_bpc_docs_get_next', $out, $doc );
    }



    //
    // Выводит ссылку на предыдущий документ
    //

    function get_prev()
    {
        $out = '';

        $out .= '<div class="prev"><a href="22"><i class="fa fa-arrow-left"></i> <span>' . __( 'сюда', 'mif-bp-customizer' ) . '</span></a></div>';

        return apply_filters( 'mif_bpc_docs_get_prev', $out, $doc );
    }





    //
    // Есть ли доступ к объекту?
    // режимы - read, write, delete
    //

    function is_access( $folder_id, $mode = 'write' ) 
    {

        return true;
    }



    //
    // Адрес страницы документов
    //

    function get_docs_url()
    {
        global $bp;
        $url = trailingslashit( $bp->displayed_user->domain ) . $this->slug;

        return apply_filters( 'mif_bpc_dialogues_get_docs_url', $url );
    }


}



//
// Выводит форму загрузки
//

function mif_bpc_the_docs_upload_form()
{
    global $mif_bpc_docs;
    echo $mif_bpc_docs->get_upload_form();
}



//
// Выводит список папок
//

function mif_bpc_the_folders()
{
    global $mif_bpc_docs;
    echo $mif_bpc_docs->get_folders();
}



//
// Выводит форму создания или настройки папки
//

function mif_bpc_the_folder_settings()
{
    global $mif_bpc_docs;
    echo $mif_bpc_docs->get_folder_settings();
}



//
// Выводит содержимое страницы документов
//

function mif_bpc_the_docs_content()
{
    global $mif_bpc_docs;
    echo $mif_bpc_docs->get_docs_content();
}



//
// Выводит статусную строку папки
//

function mif_bpc_the_folder_statusbar()
{
    global $mif_bpc_docs;
    echo $mif_bpc_docs->get_folder_statusbar();
}



//
// Выводит имя документа
//

function mif_bpc_docs_the_name()
{
    global $mif_bpc_docs;
    echo $mif_bpc_docs->get_name();
}



//
// Выводит документ на страницу документа
//

function mif_bpc_docs_the_doc()
{
    global $mif_bpc_docs;
    echo $mif_bpc_docs->get_doc();
}



//
// Выводит владельца документа
//

function mif_bpc_docs_the_owner()
{
    global $mif_bpc_docs;
    echo $mif_bpc_docs->get_owner();
}



//
// Выводит папку документа
//

function mif_bpc_docs_the_folder()
{
    global $mif_bpc_docs;
    echo $mif_bpc_docs->get_folder();
}



//
// Выводит группу документа
//

function mif_bpc_docs_the_group()
{
    global $mif_bpc_docs;
    echo $mif_bpc_docs->get_group();
}



//
// Выводит время размещения документа
//

function mif_bpc_docs_the_date()
{
    global $mif_bpc_docs;
    echo $mif_bpc_docs->get_date();
}



//
// Выводит ссылку на следующий документ
//

function mif_bpc_docs_the_next()
{
    global $mif_bpc_docs;
    echo $mif_bpc_docs->get_next();
}



//
// Выводит ссылку на предыдущий документ
//

function mif_bpc_docs_the_prev()
{
    global $mif_bpc_docs;
    echo $mif_bpc_docs->get_prev();
}




?>