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


class mif_bpc_docs extends mif_bpc_docs_screen {



    function __construct()
    {

        parent::__construct();

        // Настройка страницы документов
        add_action( 'bp_activity_setup_nav', array( $this, 'nav' ) );
        add_action( 'bp_screens', array( $this, 'doc_page' ) );

        // // Экранные функции
        // global $mif_bpc_docs_templates;
        // $mif_bpc_docs_templates = new mif_bpc_docs_templates();

        // Функции ajax-запросов
        global $mif_bpc_docs_ajax;
        $mif_bpc_docs_ajax = new mif_bpc_docs_ajax();

    }



    // 
    // Страница документов
    // 

    function nav()
    {
        // f($_POST);
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
                'user_has_access'=> $this->is_access( 'all-folders', 'write' ), 
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
    // Инициализация страницы просмотра отдельного документа
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


}






?>