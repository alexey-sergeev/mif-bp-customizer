<?php

//
// Страница настроек плагина
//
//


defined( 'ABSPATH' ) || exit;


class mif_bpc_console_settings_page {
    
    function __construct() 
    {
        add_action( 'admin_menu', array( $this, 'register_menu_page' ) );
    }

    function register_menu_page()
    {
        add_options_page( __( 'Настройка плагина BP Customizer', 'mif-bp-customizer' ), __( 'BP Customizer', 'mif-bp-customizer' ), 'manage_options', 'mif-bp-customizer', array( $this, 'page' ) );
        wp_register_style( 'mif-bp-customizer-styles', plugins_url( '../mif-bp-customizer-styles.css', __FILE__ ) );
        wp_enqueue_style( 'mif-bp-customizer-styles' );
    }

    function page()
    {
        $out = '<h1>' . __( 'Настройка плагина BP Customizer', 'mif-bp-customizer' ) . '</h1>';
        $out .= '<p>' . __( 'Плагин MIF BP Customizer добавляет новые возможности к BuddyPress. Здесь вы можете указать, что именно надо применить в вашей социальной сети.', 'mif-bp-customizer' );
        $out .= '<p>&nbsp;';
      
        $out .= $this->update_mif_bpc_options();

        $args = get_mif_bpc_options();
        foreach ( $args as $key => $value ) {
            $chk[$key] = ( $value ) ? ' checked' : '';
        }

        // $chk_jtm_mm[$args['join-to-multisite-mode']] = ' checked';

        // $select_user_role = mif_wpc_wp_dropdown_roles( $args['join-to-multisite-default-role'] );

        $out .= '<form method="POST">';
        $out .= '<table class="form-table">';
        $out .= '<tr><td colspan="3">';
        // $out .= '<h2>' . __( 'Визуальные элементы', 'mif-wp-customizer' ) . '</h2>';
        // $out .= '</td></tr>';
        // $out .= '<tr>
        //         <th class="one">' . __( 'Меню «Войти/Выйти»', 'mif-wp-customizer' ) . '</th>
        //         <td class="two"><input type="checkbox"' . $chk['login-logout-menu'] . ' value = "yes" name="login-logout-menu" id="login-logout-menu"></td>
        //         <td class="three"><label for="login-logout-menu">' . __( 'Разрешить использовать элемент меню «Войти/Выйти». В меню отображается ссылка «Войти» или «Выйти» в зависимости от текущего статуса авторизации пользователя.', 'mif-wp-customizer' ) . '</label></td>
        //         </tr>';
        // $out .= '<tr>
        //         <th>' . __( 'Виджет авторизации', 'mif-wp-customizer' ) . '</th>
        //         <td><input type="checkbox"' . $chk['login-logout-widget'] . ' value = "yes" name="login-logout-widget" id="login-logout-widget"></td>
        //         <td><label for="login-logout-widget">' . __( 'Разрешить использовать виджет авторизации. В зависимости от текущего статуса авторизации пользователя виджет отображает форму авторизации, либо аватар и имя пользователя.', 'mif-wp-customizer' ) . '</label></td>
        //         </tr>';
        // // $out .= '<tr>
        // //         <th>' . __( 'Виджет участников сайта', 'mif-wp-customizer' ) . '</th>
        // //         <td><input type="checkbox"' . $chk['members-widget'] . ' value = "yes" name="members-widget" id="members-widget"></td>
        // //         <td><label for="members-widget">' . __( 'Разрешить использовать виджет участников сайта. Показывает аватрки участников в области виджетов.', 'mif-wp-customizer' ) . '</label></td>
        // //         </tr>';
        // $out .= '<tr>
        //         <th>' . __( 'Кнопка «Наверх»', 'mif-wp-customizer' ) . '</th>
        //         <td><input type="checkbox"' . $chk['button-to-top'] . ' value = "yes" name="button-to-top" id="button-to-top"></td>
        //         <td><label for="button-to-top">' . __( 'Показывать кнопку «Наверх». Кнопка включается при пролистывании страницы вниз и позволяет быстро вернуться на начало.', 'mif-wp-customizer' ) . '</label></td>
        //         </tr>';
        // $out .= '<tr>
        //         <th>' . __( 'Верхняя панель', 'mif-wp-customizer' ) . '</th>
        //         <td><input type="checkbox"' . $chk['disable-admin-bar'] . ' value = "yes" name="disable-admin-bar" id="disable-admin-bar"></td>
        //         <td><label for="disable-admin-bar">' . __( 'Убрать верхнюю панель (админ-бар) для всех пользователей сайта.', 'mif-wp-customizer' ) . '</label></td>
        //         </tr>';
        $out .= '<tr><td colspan="3">';
        $out .= '<h2>' . __( 'Поведение сайта', 'mif-bp-customizer' ) . '</h2>';
        $out .= '</td></tr>';
        $out .= '<tr>
                <th>' . __( 'Профиль как домашняя страница', 'mif-bp-customizer' ) . '</th>
                <td><input type="checkbox"' . $chk['profile-as-homepage'] . ' value = "yes" name="profile-as-homepage" id="profile-as-homepage"></td>
                <td><label for="profile-as-homepage">' . __( 'Назначить профиль пользователя его домашней страницей.', 'profile-as-homepage' ) . '</label></td>
                </tr>';
        // $out .= '<tr>
        //         <th>' . __( 'MIME типы', 'mif-wp-customizer' ) . '</th>
        //         <td><input type="checkbox"' . $chk['mif-wpc-mime-types'] . ' value = "yes" name="mif-wpc-mime-types" id="mif-wpc-mime-types"></td>
        //         <td><label for="mif-wpc-mime-types">' . __( 'Разрешить добавление пользовательских MIME типов.', 'mif-wp-customizer' ) . '</label></td>
        //         </tr>';


        $out .= '<tr><td colspan="3">';
        $out .= wp_nonce_field( "mif-bpc-admin-settings-page-nonce", "_wpnonce", true, false );
        $out .= '<p><input type="submit" class="button button-primary" name="update-mif-bpc-settings" value="' . __( 'Сохранить изменения', 'mif-bp-customizer' ) . '">';
        $out .= '</td></tr>';

        $out .= '</table>';
        $out .= '</form>';

        echo $out;
    }

    function update_mif_bpc_options()
    {
        if ( ! $_POST['update-mif-bpc-settings'] ) return;
        if ( ! wp_verify_nonce( $_POST['_wpnonce'], "mif-bpc-admin-settings-page-nonce" ) ) return '<div class="err">' . __( 'Ошибка авторизации', 'mif-bp-customizer' ) . '</div>';

        $args = get_mif_bpc_options();
        foreach ( $args as $key => $value ) {
            
            if ( isset($_POST[$key]) ) {
                $new_value = ( $_POST[$key] == 'yes' ) ? 1 : $_POST[$key];
            } else {
                $new_value = 0;    
            }
            
            update_option( $key, $new_value );
        }

        return '<div class="note">' . __( 'Изменения сохранены', 'mif-bp-customizer' ) . '</div>';
    }



}

new mif_bpc_console_settings_page();

?>
