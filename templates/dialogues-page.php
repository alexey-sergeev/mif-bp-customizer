<?php do_action( 'mif_bpc_before_dialogues-page' );  ?>

<div class="dialogues-page">

    <div class="dialogues-header clearfix"><div class="custom-button"><a href="<?php mif_bpc_the_dialogues_url() ?>" class="button dialogues-fix" title="<?php echo __( 'Зафиксировать', 'mif-bp-customizer' ) ?>"><i class="fa fa-chevron-down" aria-hidden="true"></i></a></div></div>

    <div class="dialogues-body clearfix">
        <div class="members">

            <div class="search"><input type="text" placeholder="<?php echo __( 'Поиск', 'mif-bp-customizer' ) ?>"></div>

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

                        <div class="messages-empty">
                            <div>
                                <i class="fa fa-5x fa-comments-o" aria-hidden="true"></i>
                                <p><?php echo __( 'Выберите диалог или', 'mif-bp-customizer' ) ?></br />
                                <a href=""><?php echo __( 'начните новый', 'mif-bp-customizer' ) ?></a></p>
                            </div>
                        </div>

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
