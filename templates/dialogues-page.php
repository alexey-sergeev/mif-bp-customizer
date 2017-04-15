<?php do_action( 'mif_bpc_before_dialogues-page' );  ?>

<div class="dialogues-page">

    <div class="dialogues-header clearfix"><div class="custom-button"><a href="<?php mif_bpc_the_dialogues_url() ?>" class="button dialogues-fix" title="<?php echo __( 'Зафиксировать', 'mif-bp-customizer' ) ?>"><i class="fa fa-thumb-tack" aria-hidden="true"></i></a></div></div>

    <div class="dialogues-body clearfix">
        <div class="members">

            <div class="search"><input type="text" placeholder="<?php echo __( 'Поиск', 'mif-bp-customizer' ) ?>"></div>

            <div class="thread-wrap">

                <?php mif_bpc_the_dialogues_threads(); ?>

            </div>

        </div>

        <div class="messages">

            <div class="messages-title">1</div>

            <div class="messages-wrap">

                    <div class="messages-items">

                        <div class="new-empty">
                            <div>
                                <i class="fa fa-5x fa-comments-o" aria-hidden="true"></i>
                                <p><?php echo __( 'Выберите диалог или', 'mif-bp-customizer' ) ?></br />
                                <a href=""><?php echo __( 'начните новый', 'mif-bp-customizer' ) ?></a></p>
                            </div>
                        </div>

                    </div>

            </div>

            <div class="form-wrap">
                <div class="form">
                    <?php mif_bpc_the_dialogues_form(); ?>
                </div>
            </div>


        </div>
    </div>

</div>

<?php do_action( 'mif_bpc_after_dialogues-page' );
