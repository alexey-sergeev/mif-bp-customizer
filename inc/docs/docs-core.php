<?php

//
// Документы (параметры и методы ядра)
// 
//



defined( 'ABSPATH' ) || exit;


abstract class mif_bpc_docs_core {

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
    // Название папки с документами в uploads
    //

    public $path = 'docs';

    // //
    // // Название папки по умолчанию
    // //

    // public $default_folder_name = 'New folder';




    function __construct()
    {

        // Настройка типа записи
        add_action( 'bp_init', array( $this, 'create_post_type' ) );

        // Скачивание файла
        add_action( 'bp_init', array( $this, 'force_download' ) );

        // $this->default_folder_name = __( 'Новая папка', 'mif-bp-customizer' );
    }



    // 
    // Создание типов записей
    // 

    function create_post_type()
    {
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
        // Вернёт в $ret2:
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

        $docs = get_posts( $args );

        return apply_filters( 'mif_bpc_docs_get_docs_collection_data', $docs, $folder_id, $page, $posts_per_page );
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
        foreach ( (array) $arr as $doc_id ) $ret2[] = $this->untrash_doc( $doc_id );

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
        foreach ( (array) $docs as $doc ) $ret[] = wp_delete_post( $doc->ID );

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
		$y = substr( $time, 0, 4 ); // год
		$m = substr( $time, 5, 2 ); // месяц

        $path = '/' . $this->path . '/' . $y . '/' . $user_id;
        $path = apply_filters( 'mif_bpc_docs_get_path', $path, $user_id );

        $ret = ( wp_mkdir_p( $upload_dir . $path ) ) ? $path : false;

        return apply_filters( 'mif_bpc_docs_get_docs_path', $ret, $user_id, $upload_dir, $y, $m );
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



    //
    // Адрес конкретной папки
    //

    function get_folder_url( $folder_id = NULL )
    {
        if ( $folder_id == NULL ) return;
        $folder_url = $this->get_docs_url() . '/folder/' . $folder_id . '/';

        return apply_filters( 'mif_bpc_docs_get_folder_url', $folder_url, $folder_id );
    }



    //
    // Адрес конкретного документа
    //

    function get_doc_url( $doc_id = NULL )
    {
        if ( $doc_id == NULL ) return;
        $doc_url = $this->get_docs_url() . '/' . $doc_id . '/';

        return apply_filters( 'mif_bpc_docs_get_doc_url', $doc_url, $doc_id );
    }



    //
    // Имя документа
    //

    function get_doc_name( $doc = NULL )
    {
        if ( $doc == NULL ) return;
        if ( ! is_object( $doc ) ) $doc = get_post( $doc ); 

        $name = $doc->post_title;
        
        $icon = mif_bpc_get_file_icon( $name );

        if ( in_array( $this->get_doc_type( $doc ), array( 'file', 'image' ) ) && preg_match( '/noext/', $icon ) ) $name = preg_replace( '/\.\w+$/', '', $name );

        return apply_filters( 'mif_bpc_docs_get_doc_name', $name, $doc );
    }



    // 
    // Инициирует скачивание документа
    // 

    function force_download()
    {
        if ( bp_current_component() != 'docs' || ! is_numeric( bp_current_action() ) ) return false;
        if ( bp_action_variable( 0 ) != 'download' ) return false;
        
        $this->download( bp_current_action() );
    }



    // 
    // Скачивание документа
    // 

    function download( $doc_id = NULL )
    {
        if ( ! $this->is_doc( $doc_id ) ) return false;
        
        $doc = get_post( $doc_id );

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
    // Возвращает тип документа (image, file, link или html)
    // 

    function get_doc_type( $doc )
    {
        if ( empty( $doc ) ) return false;

        if ( preg_match( '/^\/' . $this->path . '\//', $doc->post_content ) ) {

            // Если содержимое начинается с /docs/

            $ext = end( explode( ".", $doc->post_title ) );
            $img_types = apply_filters( 'mif_bpc_docs_img_types', array( 'png', 'jpg', 'jpeg', 'gif' ) );

            $ret = ( in_array( $ext, $img_types ) ) ? 'image' : 'file';
            
        } elseif ( preg_match( '/^https?:\/\//', $doc->post_content ) ) {

            // Если содержимое начинается с http

            $ret = 'link';

        } else {

            // Если содержимое начинается с http

            $ret = 'html';

        }

        return apply_filters( 'mif_bpc_docs_get_doc_type', $ret, $doc );
    }



    //
    // Получает данные документа, отображаемого на экране
    //

    function get_doc_data()
    {
        if ( bp_current_component() != 'docs' || ! is_numeric( bp_current_action() ) ) return false;

        $doc_id = bp_current_action();
        $doc_data = get_post( $doc_id );

        return apply_filters( 'mif_bpc_docs_get_doc_data', $doc_data, $doc_id );
    }



    //
    // Есть ли доступ к объекту?
    // режимы - read, write, delete
    //

    function is_access( $folder_id, $mode = 'write' ) 
    {

        return true;
    }


}



?>