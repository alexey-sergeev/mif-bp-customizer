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
        // $out .= '<tr>
        //         <th class="one">' . __( 'Меню «Войти/Выйти»', 'mif-wp-customizer' ) . '</th>
        //         <td class="two"><input type="checkbox"' . $chk['login-logout-menu'] . ' value="yes" name="login-logout-menu" id="login-logout-menu"></td>
        //         <td class="three"><label for="login-logout-menu">' . __( 'Разрешить использовать элемент меню «Войти/Выйти». В меню отображается ссылка «Войти» или «Выйти» в зависимости от текущего статуса авторизации пользователя.', 'mif-wp-customizer' ) . '</label></td>
        //         </tr>';
        // $out .= '<tr>
        //         <th>' . __( 'Виджет авторизации', 'mif-wp-customizer' ) . '</th>
        //         <td><input type="checkbox"' . $chk['login-logout-widget'] . ' value="yes" name="login-logout-widget" id="login-logout-widget"></td>
        //         <td><label for="login-logout-widget">' . __( 'Разрешить использовать виджет авторизации. В зависимости от текущего статуса авторизации пользователя виджет отображает форму авторизации, либо аватар и имя пользователя.', 'mif-wp-customizer' ) . '</label></td>
        //         </tr>';

        if ( is_main_site() ) {

            $out .= '<tr><td colspan="3">
                    <h2>' . __( 'Лента активности', 'mif-bp-customizer' ) . '</h2>
                    </td></tr>';

            $out .= '<tr>
                    <th>' . __( 'Особая лента активности', 'mif-bp-customizer' ) . '</th>
                    <td><input type="checkbox"' . $chk['activity-stream'] . ' value="yes" name="activity-stream" id="activity-stream"></td>
                    <td><label for="activity-stream">' . __( 'Меняет вид и поведение ленты активности на страницах пользователей (на личной старнице - "Вся лента", на страницах других пользователей - только их активность). Позволяет использовать инструменты блокировки контента.', 'mif-bp-customizer' ) . '</label></td>
                    </tr>';

            $out .= '<tr>
                    <th>' . __( 'Типы записей ленты активности', 'mif-bp-customizer' ) . '</th>
                    <td><input type="checkbox"' . $chk['activity-exclude'] . ' value="yes" name="activity-exclude" id="activity-exclude"></td>
                    <td><label for="activity-exclude">' . __( 'Позволяет указывать типы активности, которые должны отображаться в ленте пользователя (требуется установка опции "Особая лента активности").', 'mif-bp-customizer' ) . '</label></td>
                    </tr>';

            $out .= '<tr>
                    <th>' . __( 'Блокировка пользователей', 'mif-bp-customizer' ) . '</th>
                    <td><input type="checkbox"' . $chk['banned-users'] . ' value="yes" name="banned-users" id="banned-users"></td>
                    <td><label for="banned-users">' . __( 'Позволяет вести списки пользователей, информация которых блокируется в вашей ленте активности (требуется установка опции "Особая лента активности").', 'mif-bp-customizer' ) . '</label></td>
                    </tr>';

            $out .= '<tr><td colspan="3">
                    <h2>' . __( 'Поведение сайта', 'mif-bp-customizer' ) . '</h2>
                    </td></tr>';

            $out .= '<tr>
                    <th>' . __( 'Профиль как домашняя страница', 'mif-bp-customizer' ) . '</th>
                    <td><input type="checkbox"' . $chk['profile-as-homepage'] . ' value="yes" name="profile-as-homepage" id="profile-as-homepage"></td>
                    <td><label for="profile-as-homepage">' . __( 'Назначить профиль пользователя его домашней страницей.', 'mif-bp-customizer' ) . '</label></td>
                    </tr>';

            $out .= '<tr>
                    <th>' . __( 'Приватность профиля', 'mif-bp-customizer' ) . '</th>
                    <td><input type="checkbox"' . $chk['profile-privacy'] . ' value="yes" name="profile-privacy" id="profile-privacy"></td>
                    <td><label for="profile-privacy">' . __( 'Разрешить пользователям ограничивать доступ к своему профилю.', 'mif-bp-customizer' ) . '</label></td>
                    </tr>';

            $out .= '<tr>
                    <th>' . __( 'Подписчики', 'mif-bp-customizer' ) . '</th>
                    <td><input type="checkbox"' . $chk['followers'] . ' value="yes" name="followers" id="followers"></td>
                    <td><label for="followers">' . __( 'Включить возможность подписки на обновления пользователей (подписка = односторонняя дружба).', 'mif-bp-customizer' ) . '</label></td>
                    </tr>';

            $out .= '<tr>
                    <th>' . __( 'Уведомления', 'mif-bp-customizer' ) . '</th>
                    <td><input type="checkbox"' . $chk['notifications'] . ' value="yes" name="notifications" id="notifications"></td>
                    <td><label for="notifications">' . __( 'Включить продвинутый режим уведомлений.', 'mif-bp-customizer' ) . '</label></td>
                    </tr>';

            $out .= '<tr>
                    <th>' . __( 'Всплывающие сообщения', 'mif-bp-customizer' ) . '</th>
                    <td><input type="checkbox"' . $chk['websocket'] . ' value="yes" name="websocket" id="websocket"></td>
                    <td><label for="websocket">' . __( 'Включить механизм всплывающих сообщений. Требуется настройка эхо-сервера.', 'mif-bp-customizer' ) . '</label></td>
                    </tr>';

            $out .= '<tr>
                    <th>' . __( 'Фоновое изображение', 'mif-bp-customizer' ) . '</th>
                    <td><input type="checkbox"' . $chk['custom-background'] . ' value="yes" name="custom-background" id="custom-background"></td>
                    <td><label for="custom-background">' . __( 'Разрешить использовать пользовательское изображение в качестве фона для профиля пользователя или группы.', 'mif-bp-customizer' ) . '</label></td>
                    </tr>';

            $out .= '<tr>
                    <th>' . __( 'Адрес группы', 'mif-bp-customizer' ) . '</th>
                    <td><input type="checkbox"' . $chk['edit-group-slug'] . ' value="yes" name="edit-group-slug" id="edit-group-slug"></td>
                    <td><label for="edit-group-slug">' . __( 'Разрешить изменять адрес группы в её настройках и при создании.', 'mif-bp-customizer' ) . '</label></td>
                    </tr>';
            
            $out .= '<tr>
                    <th>' . __( 'Кнопка «Нравится»', 'mif-bp-customizer' ) . '</th>
                    <td><input type="checkbox"' . $chk['like-button'] . ' value="yes" name="like-button" id="like-button"></td>
                    <td><label for="like-button">' . __( 'Разрешить использовать кнопку «Нравится».', 'mif-bp-customizer' ) . '</label></td>
                    </tr>';
            
            $out .= '<tr>
                    <th>' . __( 'Кнопка «Репост»', 'mif-bp-customizer' ) . '</th>
                    <td><input type="checkbox"' . $chk['repost-button'] . ' value="yes" name="repost-button" id="repost-button"></td>
                    <td><label for="repost-button">' . __( 'Разрешить использовать кнопку «Репост».', 'mif-bp-customizer' ) . '</label></td>
                    </tr>';
            
            $out .= '<tr>
                    <th>' . __( 'Кнопки «Избранное», «Удалить»', 'mif-bp-customizer' ) . '</th>
                    <td><input type="checkbox"' . $chk['activity-button-customize'] . ' value="yes" name="activity-button-customize" id="activity-button-customize"></td>
                    <td><label for="activity-button-customize">' . __( 'Использовать особые кнопки  «Избранное», «Удалить».', 'mif-bp-customizer' ) . '</label></td>
                    </tr>';
            


        }

        $out .= '<tr><td colspan="3">';
        $out .= '<h2>' . __( 'Визуальные элементы', 'mif-wp-customizer' ) . '</h2>';
        $out .= '</td></tr>';

        $out .= '<tr>
                <th>' . __( 'Виджет участников сайта', 'mif-bp-customizer' ) . '</th>
                <td><input type="checkbox"' . $chk['members-widget'] . ' value="yes" name="members-widget" id="members-widget"></td>
                <td><label for="members-widget">' . __( 'Разрешить использовать виджет участников сайта. Показывает аватары участников в области виджетов.', 'mif-bp-customizer' ) . '</label></td>
                </tr>';

        $out .= '<tr>
                <th>' . __( 'Виджет групп', 'mif-bp-customizer' ) . '</th>
                <td><input type="checkbox"' . $chk['groups-widget'] . ' value="yes" name="groups-widget" id="groups-widget"></td>
                <td><label for="groups-widget">' . __( 'Разрешить использовать виджет групп. Показывает аватры групп в области виджетов.', 'mif-bp-customizer' ) . '</label></td>
                </tr>';
            
        // $out .= '<tr>
        //         <th>' . __( 'Кнопка «Наверх»', 'mif-wp-customizer' ) . '</th>
        //         <td><input type="checkbox"' . $chk['button-to-top'] . ' value="yes" name="button-to-top" id="button-to-top"></td>
        //         <td><label for="button-to-top">' . __( 'Показывать кнопку «Наверх». Кнопка включается при пролистывании страницы вниз и позволяет быстро вернуться на начало.', 'mif-wp-customizer' ) . '</label></td>
        //         </tr>';
        // $out .= '<tr>
        //         <th>' . __( 'Верхняя панель', 'mif-wp-customizer' ) . '</th>
        //         <td><input type="checkbox"' . $chk['disable-admin-bar'] . ' value="yes" name="disable-admin-bar" id="disable-admin-bar"></td>
        //         <td><label for="disable-admin-bar">' . __( 'Убрать верхнюю панель (админ-бар) для всех пользователей сайта.', 'mif-wp-customizer' ) . '</label></td>
        //         </tr>';


        // $out .= '<tr>
        //         <th>' . __( 'MIME типы', 'mif-wp-customizer' ) . '</th>
        //         <td><input type="checkbox"' . $chk['mif-wpc-mime-types'] . ' value="yes" name="mif-wpc-mime-types" id="mif-wpc-mime-types"></td>
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
