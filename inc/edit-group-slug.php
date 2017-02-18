<?php

//
// Настройка короткого адреса для группы
// 
//


defined( 'ABSPATH' ) || exit;



if ( mif_bpc_options( 'edit-group-slug' ) ) 
    add_action( 'bp_init', 'mif_bpc_edit_group_slug_init' );


function mif_bpc_edit_group_slug_init() {

	class mif_bpc_edit_group_slug extends BP_Group_Extension {

		var $visibility = 'private';

		var $enable_nav_item = false;
		var $enable_create_step = true;
		var $enable_edit_item = true;

		function __construct() 
        {

            $this->name = __( 'Адрес', 'mif-bp-customizer' );
            $this->slug = 'group-slug';

			$this->create_step_position = 11;
			$this->nav_item_position = 11;

		}


        function settings_screen( $group_id = NULL ) 
        {
            global $bp;

            $group_url = $bp->root_domain . '/' . BP_GROUPS_SLUG . '/';
            $slug = $bp->groups->current_group->slug;

            $out = '';

            $out .= '<h3>' . __( 'Адрес', 'mif-bp-customizer' ) . '</h3>';
            $out .= '<p>' . __( 'Настройка имени группы в адресной строке', 'mif-bp-customizer' ) . '</p>';
            $out .= '<p>' . __( 'Имя группы в адресной строке задается автоматически на основе названия группы, указанного при её создании. Вы можете оставить существующее имя или указать другое.', 'mif-bp-customizer' ) . '</p>';

            $out .= '<div class="slug-edit">';
            $out .= '<div>' . $group_url . '</div>';
            $out .= '<input type="text" name="slug" value="' . $slug . '">';
            $out .= '</div>';

            $out .= '<p>' . __( '** Придумайте адрес, который будет коротким и запоминающимся. Вы можете использовать строчные латинские буквы, цифры, подчёркивание и тире.', 'mif-bp-customizer' ) . '</p>';
            $out .= '<p>&nbsp;';

            echo $out;

        }


		function create_screen_save( $group_id = NULL ) 
        {
			$this->save( $group_id, 'create' );
		}



        function settings_screen_save( $group_id = NULL ) 
        {
            $this->save( $group_id, 'screen' );
        }


        function save( $group_id = NULL, $mode = 'screen' ) 
        {
            global $bp;

            $msg = array(   0 => __( 'Адрес группы успешно изменён.', 'mif-bp-customizer' ), 
                            1 => __( 'Адрес группы не изменился.', 'mif-bp-customizer' ),
                            2 => __( 'Указанный адрес уже используется. Пожалуйста, придумайте другой.', 'mif-bp-customizer' ),
                            3 => __( 'Такой адрес не допускается. Пожалуйста, придумайте другой.', 'mif-bp-customizer' ),
                            4 => __( 'Адрес содержит недопустимые символы. Используйте только строчные латинские буквы, цифры, подчёркивание и тире.', 'mif-bp-customizer' )
                        );


            $slug = sanitize_text_field( $_POST['slug'] );
            $error_code = $this->slug_check( $slug );

            if ( $mode == 'screen' ) {

                if ( $error_code == 0 ) {

                    bp_core_add_message( $msg[$error_code] );
    
                    if ( $this->save_slug( $slug, $group_id ) ) {

                        $bp->groups->current_group->slug = $slug;
                        $redirect = bp_get_group_permalink( $bp->groups->current_group ) . 'admin/group-slug/';
                        bp_core_redirect( $redirect );

                    } else {

                        bp_core_add_message( __( 'При изменении адреса произошла ошибка.', 'mif-bp-customizer' ), 'error' );

                    }

                } else {

                    bp_core_add_message( $msg[$error_code], 'error' );

                }

            } elseif ( $mode == 'create' ) {

                if ( $error_code == 0 ) {
                    
                    bp_core_add_message( $msg[$error_code] );
                    if ( ! $this->save_slug( $slug, $group_id ) ) bp_core_add_message( __( 'При изменении адреса произошла ошибка.', 'mif-bp-customizer' ), 'error' );

                }

                if ( in_array( $error_code, array( 2, 3, 4 ) ) ) bp_core_add_message( $msg[$error_code], $error );

                if ( $error_code != 1 ) {

                    $redirect = apply_filters( 'bp_get_group_creation_form_action', trailingslashit( bp_get_groups_directory_permalink() . 'create/step/group-slug' ) );
                    bp_core_redirect( $redirect );

                }

            }

        }



        function save_slug( $slug, $group_id )
        {
			global $bp, $wpdb;

			if ( $slug && $group_id ) {
				$sql = $wpdb->prepare( "UPDATE {$bp->groups->table_name} SET slug = %s WHERE id = %d", $slug, $group_id );
				return $wpdb->query( $sql );
			}

			return false;
        }


        function slug_check( $slug ) 
        {
			global $bp;

            // совпадает со старым
			if ( $slug == $bp->groups->current_group->slug ) return 1;

            // уже используется для другой группы
			if ( BP_Groups_Group::check_slug( $slug ) ) return 2;

            // попадает в запрещенные имена
			if ( in_array( $slug, (array) $bp->groups->forbidden_names ) ) return 3;

            // содержит запрещенные буквы
            $clean_slug = preg_replace( "/[^a-z0-9_\-]/", '', $slug );
            if ( $slug != $clean_slug )  return 4;

			return 0;
		}



    }

	bp_register_group_extension( 'mif_bpc_edit_group_slug' );

}




?>
