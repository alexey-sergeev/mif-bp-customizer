<?php do_action( 'mif_bpc_before_dialogues-page' );  ?>

<div class="dialogues-page">

    <div class="dialogues-header clearfix">

        <div class="custom-button">

            <a href="<?php mif_bpc_the_dialogues_url() ?>" class="button dialogues-fix" title="<?php echo __( 'Зафиксировать', 'mif-bp-customizer' ) ?>"><i class="fa fa-anchor" aria-hidden="true"></i></a>
            
        </div>

        <div class="custom-button">

            <a href="<?php mif_bpc_the_dialogues_url() ?>" class="button dialogues-compose" title="<?php echo __( 'Новое сообщение', 'mif-bp-customizer' ) ?>"><i class="fa fa-pencil" aria-hidden="true"></i></a>
            <a href="<?php mif_bpc_the_dialogues_url() ?>" class="button dialogues-refresh" title="<?php echo __( 'Обновить', 'mif-bp-customizer' ) ?>"><i class="fa fa-refresh" aria-hidden="true"></i></a>
            <a href="<?php mif_bpc_the_dialogues_url() ?>" class="button dialogues-join" title="<?php echo __( 'Группировать диалоги', 'mif-bp-customizer' ) ?>"><i class="fa fa-compress" aria-hidden="true"></i></a>

        </div>

    </div>

    <div class="dialogues-body clearfix">
        <div class="members">

            <div class="search"><input type="text" id="dialogues_thread_search" placeholder="<?php echo __( 'Поиск', 'mif-bp-customizer' ) ?>"></div>

            <div class="thread-wrap">

                <?php mif_bpc_the_dialogues_threads(); ?>

            </div>

        </div>

        <div class="messages">

            <?php mif_bpc_the_dialogues_dialog(); ?>

            <div class="messages-header">
                <div class="messages-header-content">
                <!-- ajaxed -->
                </div>
            </div>

            <div class="messages-wrap">

                    <div class="messages-items">

                        <?php mif_bpc_the_dialogues_default_page(); ?>

                    </div>

            </div>

            <div class="messages-form">
                <div class="messages-form-content">
                <div class="form-empty"></div>
                <?php // mif_bpc_the_dialogues_form(); ?>
                </div>
            </div>


        </div>
    </div>

</div>

<?php do_action( 'mif_bpc_after_dialogues-page' );
