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

        // Показывать при создании группы

        var $enable_create_step = false;
        
        // Показывать в меню группы
        
        var $enable_nav_item = true;

        // Показывать в настройках группы

        var $enable_edit_item = true;

       

        function __construct() 
        {
            global $bp, $mif_bpc_docs;

            $access_mode = groups_get_groupmeta( $bp->groups->current_group->id, $mif_bpc_docs->group_access_mode_meta_key );

            if ( isset( $access_mode ) && empty( $access_mode['docs_allowed'] ) ) $this->enable_nav_item = false;

            $data = $mif_bpc_docs->get_all_folders_size();

            $this->name = __( 'Документы', 'mif-bp-customizer' );
            $this->nav_item_name = __( 'Документы', 'mif-bp-customizer' ) . ' <span>' . $data['count'] . '</span>';
            $this->slug = 'docs';
            // $this->create_step_position = 10;
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

                $mif_bpc_docs->doc_page();

            } else {

                $mif_bpc_docs->body();

            }

        }


        //
        // Панель внутренней навигации
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
            if ( $mif_bpc_docs->is_access( 'all-folders', 'write' ) ) $out .= '<li' . $current2 . '><a href="' . $url . 'new-folder/">' . __( 'Создать папку', 'mif-bp-customizer' ) . '</a></li>';

            $out .= '</ul></div>';

            return apply_filters( 'mif_bpc_docs_group_subnav', $out );
        }



        //
        // Страница настройки документов в группе
        //        

        function settings_screen( $group_id = NULL ) 
        {
            global $bp, $mif_bpc_docs;

            $access_mode = groups_get_groupmeta( $group_id, $mif_bpc_docs->group_access_mode_meta_key );
            
            if ( empty( $access_mode ) ) {
            
                $docs_allowed = ' checked';
                $everyone_create = '';
                $everyone_delete = '';

            } else {

                $docs_allowed = ( $access_mode['docs_allowed'] ) ? ' checked' : '';
                $everyone_create = ( $access_mode['everyone_create'] ) ? ' checked' : '';
                $everyone_delete = ( $access_mode['everyone_delete'] ) ? ' checked' : '';

            }

            $out = '';

            $out .= '<h3>' . __( 'Документы', 'mif-bp-customizer' ) . '</h3>';
            $out .= '<p>' . __( 'Настройка системы документов в группе. Параметры по созданию и удалению можно переопределить для каждой конкретной папки.', 'mif-bp-customizer' ) . '</p>';
            $out .= '<hr>';

            $out .= '<div class="checkbox"><label><input type="checkbox" name="docs_allowed"' . $docs_allowed . '>' . __( 'Разрешить документы в группе', 'mif-bp-customizer' ) . '
            <ul>
            <li>' . __( 'Создаёт в группе раздел "Документы"', 'mif-bp-customizer' ) . '</li>
            <li>' . __( 'Можно создавать папки, загружать файлы, размещать ссылки на ресурсы Интернета', 'mif-bp-customizer' ) . '</li>
            </ul>
            </label></div>';

            $out .= '<div class="checkbox"><label><input type="checkbox" name="everyone_create"' . $everyone_create . '>' . __( 'Каждый участник группы может создавать папки и размещать документы', 'mif-bp-customizer' ) . '
            <ul>
            <li>' . __( 'По умолчанию создавать папки и размещать документы могут только администраторы группы', 'mif-bp-customizer' ) . '</li>
            <li>' . __( 'При выборе данной опции создавать папки и размещать документы смогут все участники группы', 'mif-bp-customizer' ) . '</li>
            </ul>
            </label></div>';

            $out .= '<div class="checkbox"><label><input type="checkbox" name="everyone_delete"' . $everyone_delete . '>' . __( 'Участники группы могут удалять чужие папки и документы', 'mif-bp-customizer' ) . '
            <ul>
            <li>' . __( 'По умолчанию удалять любые папки и документы могут только администраторы группы', 'mif-bp-customizer' ) . '</li>
            <li>' . __( 'Обычные пользователи могут удалять только те папки и документы, которые создали сами', 'mif-bp-customizer' ) . '</li>
            <li>' . __( 'При выборе данной опции пользователи смогут удалять и чужие папки и документы', 'mif-bp-customizer' ) . '</li>
            <li>' . __( 'Также, всем будет разрешено создавать папки и размещать документы', 'mif-bp-customizer' ) . '</li>
            </ul>
            </label></div>';

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



        //
        // Сохранение настроек
        //

        function save( $group_id = NULL, $mode = 'screen' ) 
        {
            global $mif_bpc_docs;

            $access_mode = array();

            $access_mode['docs_allowed'] = ( isset( $_POST['docs_allowed'] ) ) ? true : false;
            $access_mode['everyone_create'] = ( isset( $_POST['everyone_create'] ) ) ? true : false;
            $access_mode['everyone_delete'] = ( isset( $_POST['everyone_delete'] ) ) ? true : false;

            if ( $access_mode['everyone_delete'] ) $access_mode['everyone_create'] = true;

            groups_update_groupmeta( $group_id, $mif_bpc_docs->group_access_mode_meta_key, $access_mode );
            groups_update_last_activity();
        }



    }

    bp_register_group_extension( 'mif_bpc_docs_group' );

}

?>