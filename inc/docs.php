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

    public $docs_on_page = 24;

    //
    // Ярлык системы документов
    //

    public $slug = 'docs';

    //
    // Название папки с документами
    //

    public $path = 'docs';


    function __construct()
    {
       
        // Настройка типа записи
        add_action( 'bp_init', array( $this, 'create_post_type' ) );

        // Настройка страницы документов
        add_action( 'bp_activity_setup_nav', array( $this, 'nav' ) );
        add_action( 'bp_screens', array( $this, 'doc_page_init' ) );

        // Ajax-события
        add_action( 'wp_ajax_mif-bpc-docs-upload-files', array( $this, 'ajax_upload_helper' ) );
        add_action( 'wp_ajax_mif-bpc-docs-network-link-files', array( $this, 'ajax_network_link_helper' ) );
        add_action( 'wp_ajax_mif-bpc-docs-new-folder', array( $this, 'ajax_new_folder_helper' ) );

        // add_action( 'bp_init', array( $this, 'dialogues_nav' ) );
        // add_action( 'bp_screens', array( $this, 'compose_screen' ) );
        // add_filter( 'messages_template_view_message', array( $this, 'view_screen' ) );
        // add_filter( 'bp_get_total_unread_messages_count', array( $this, 'total_unread_messages_count' ) );
        // add_filter( 'bp_get_send_private_message_link', array( $this, 'message_link' ) );

        add_action( 'wp_enqueue_scripts', array( $this, 'load_js_helper' ) );   
    }



    // 
    // Страница документов
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
        // if ( bp_is_current_action( 'new-folder' ) ) $tpl_file = 'docs-folder-settings.php';

        // if ( is_int( bp_current_action() ) ) $tpl_file = 'docs-folder.php';

        if ( $template = locate_template( $tpl_file ) ) {
            load_template( $template, false );
        } else {
            load_template( dirname( __FILE__ ) . '/../templates/' . $tpl_file, false );
        }
    }



    // 
    // Настройка страницы просмотра файлов или папки
    // 

    function doc_page_init()
    {
        if ( bp_current_component() != 'docs' || ! is_numeric( bp_current_action() ) ) return false;
        
        p( 'ook' );
        
        $this->screen();
    }



    // 
    // JS-помощник
    // 

    function load_js_helper()
    {
        wp_enqueue_script( 'mif_bpc_docs_helper', plugins_url( '../js/docs.js', __FILE__ ) );
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
        $name = ( trim( $_POST['name'] ) == '' ) ? __( 'Папка', 'mif-bp-customizer' ) : $_POST['name'];


        $folder_data = array(
            'post_type' => 'mif-bpc-folder',
            'post_title' => $name,
            'post_content' => $_POST['desc'],
            'post_status' => $publish,
            // 'post_parent' => $group_id,
            'post_author' => $user_id,
            'comment_status' => 'closed',
            'ping_status' => 'closed'

        );

        $post_id = wp_insert_post( wp_slash( $folder_data ) );

        echo $this->get_folder_settings( $post_id );

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
            $upload = (object) wp_upload_dir();

            if ( move_uploaded_file( $_FILES['file']['tmp_name'], $upload->basedir . $path ) ) {

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

                $post_id = $this->doc_save( $filename, $path, $user_id, $_POST['folder_id'] );
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

    function doc_save( $name, $path, $user_id = NULL, $folder_id = NULL )
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

        $post_id = wp_insert_post( wp_slash( $docs_data ) );

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
    // Все папки пользователя или группы
    // 

    function get_folders( $page = 0, $item_id = NULL, $mode = 'member' )
    {
        $args = array(
            'numberposts' => $this->folders_on_page,
            'author' => bp_displayed_user_id(),
            // 'category'    => 0,
            'orderby'     => 'date',
            'order'       => 'DESC',
            // 'include'     => array(),
            // 'exclude'     => array(),
            // 'meta_key'    => '',
            // 'meta_value'  =>'',
            'post_type'   => 'mif-bpc-folder',
        );

        $folders = get_posts( $args );

        if ( $folders ) {

            $arr = array();
            foreach( $folders as $folder ) $arr[] = $this->get_folder_item( $folder );

            $out = implode( "\n", $arr );
        
        } else {

            $out = __( 'Папки не обнаружены', 'mif-bp-customizer' );

        }

        return apply_filters( 'mif_bpc_docs_get_folders', $out, $page, $item_id, $mode, $arr );
    }



    // 
    // Все документы, расположенные в папке
    // 

    function get_docs_collection( $folder_id, $page = 0 )
    {
        if ( ! $this->is_access( $folder_id, 'read' ) ) {

            $out = __( 'Доступ ограничен', 'mif-bp-customizer' );   
            return apply_filters( 'mif_bpc_docs_get_docs_access_denied', $out, $folder_id );

        }

        $args = array(
            'numberposts' => $this->docs_on_page,
            // 'author' => bp_displayed_user_id(),
            // 'category'    => 0,
            'orderby'     => 'date',
            'order'       => 'DESC',
            // 'include'     => array(),
            // 'exclude'     => array(),
            // 'meta_key'    => '',
            // 'meta_value'  =>'',
            'post_type'   => 'mif-bpc-doc',
            'post_parent' => $folder_id
        );

        $out = '<div class="docs-collection response-box clearfix">';

        $docs = get_posts( $args );

        if ( $docs ) {

            $arr = array();
            foreach( $docs as $doc ) $arr[] = $this->get_doc_item( $doc );

            $out .= implode( "\n", $arr );
        
        } else {

            $out .= __( 'Документы не обнаружены', 'mif-bp-customizer' );

        }

        $out .= '</div>';

        return apply_filters( 'mif_bpc_docs_get_folders', $out, $page, $item_id, $mode, $arr );
    }



    // 
    // Выводит изображение папки
    // 

    function get_doc_item( $doc = NULL )
    {
        if ( $doc == NULL ) {

            $logo = '<i class="fa fa-spinner fa-spin fa-3x fa-fw"></i>';
            $name = '';
            $loading = ' loading';
            $a1 = '';
            $a2 = '';

        } else {

            if ( is_numeric( $doc ) ) $doc = get_post( $doc );

            $name = $doc->post_title;
            $type = ( preg_match( '/^http/', $doc->post_content ) ) ? $doc->post_content : $doc->post_title;
            $logo = $this->get_file_logo( $type );
            $loading = '';
            $a1 = '<a href="' . $this->get_docs_url() . '/' . $doc->ID . '/">';
            $a2 = '</a>';

        }

        $out = '<div class="file' . $loading . '">
        ' . $a1 . '
        <span class="logo">' . $logo . '</span>
        <span class="name">' . $name . '</span>
        ' . $a2 . '
        </div>';

        return apply_filters( 'mif_bpc_docs_get_doc_item', $out, $doc );
    }



    // 
    // Выводит изображение папки
    // 

    function get_folder_item( $folder = NULL )
    {
        if ( $folder == NULL ) return;
        
        $folder_url = $this->get_docs_url() . '/folder/' . $folder->ID . '/';

        $out = '<div class="file folder">
        <a href="' . $folder_url . '">
        <span class="logo"><i class="fa fa-folder-open-o fa-3x"></i></span>
        <span class="name">' . $folder->post_title . '</span>
        </a>
        </div>';

        return apply_filters( 'mif_bpc_docs_get_folder_item', $out, $folder );
    }


    // 
    // Выводит страницу создания или настройки папки
    // 

    function get_folder_settings( $folder = NULL )
    {
        $out = '';

        if ( empty( $folder ) ) {

            $out .= '<form id="new-folder">
            <h2>' . __( 'Новая папка', 'mif-bp-customizer' ) . '</h2>
            <p>' . __( 'Название', 'mif-bp-customizer' ) . ':</p>
            <p><input type="text" name="name"></p>
            <p>' . __( 'Описание', 'mif-bp-customizer' ) . ':</p>
            <p><textarea name="desc"></textarea></p>
            <p>' . __( 'Режим доступа', 'mif-bp-customizer' ) . ':</p>
            <p><label><input type="checkbox" name="publish" checked> ' . __( 'Опубликована', 'mif-bp-customizer' ) . '</label></p>
            <p><input type="submit" value="' . __( 'Сохранить', 'mif-bp-customizer' ) . '"></p>
            <input type="hidden" name="_wpnonce" value="' . wp_create_nonce( 'mif-bpc-docs-new-folder-nonce' ) . '">
            </form>';

        } else {

            $out .= 'ok';

        }

        return apply_filters( 'mif_bpc_docs_get_folder_settings', $out, $folder );
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

    function get_folder_content( $folder_id = NULL )
    {
        if ( $folder_id == NULL ) return;
        
        $out = '';
        $folder = get_post( $folder_id );

        if ( $folder->post_type == 'mif-bpc-folder' ) {
            
            // $out .= '<h2>' . __( 'Папки', 'mif-bp-customizer' ) . ' / ' . $folder->post_title . '</h2>';
            $out .= $this->get_folder_header( $folder );
            $out .= $this->get_upload_form( $folder_id );
            $out .= $this->get_docs_collection( $folder_id );
             
        } else {

            $out .= __( 'Папка не обнаружена', 'mif-bp-customizer' );

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
            // $out .= 'folder ' . $param;
            $out .= $this->get_folder_content( $param );

        } elseif ( is_numeric( $ca ) ) {

            // Отобразить страницу документа
            $out .= 'doc ' . $ca;

            // $item = get_post( $ca );
            
            // if ( isset( $item ) && $item->post_type == 'mif-bpc-doc' ) {

            //     $out .= 'doc';

            // } elseif ( isset( $item ) && $item->post_type == 'mif-bpc-folder' ) {


            // } else {

            //     $out .= 'none';

            // }

        } else {

            // Главная страница документов - папки и др.
            $out .= $this->get_folders();

        }




        // if ( bp_is_current_action( 'new-folder' ) ) $tpl_file = 'docs-folder-settings.php';

        return apply_filters( 'mif_bpc_docs_get_docs_content', $out, $ca );
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
    // Логотип файла
    // 

    function get_file_logo( $file = 'none' )
    {
        // $ext = end( explode( ".", $file ) );
        return apply_filters( 'mif_bpc_docs_get_file_logo', get_file_icon( $file, 'fa-3x' ), $file );
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

?>