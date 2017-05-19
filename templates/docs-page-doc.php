<?php get_header( 'buddypress' ); ?>

    <div id="page-header" class="clearfix">

        <h1><?php _e( 'Документы', 'mif-bp-customizer' ); ?></h1>

    </div>
      
    <div id="item-body" class="docs-page-doc clearfix">

        <div class="content">

            <?php mif_bpc_docs_the_doc(); ?>

        </div>

        <div class="meta">

            <?php // mif_bpc_docs_the_name(); ?>
            <?php mif_bpc_docs_the_folder(); ?>
            <?php mif_bpc_docs_the_group(); ?>
            <?php mif_bpc_docs_the_date(); ?>
            <?php mif_bpc_docs_the_owner(); ?>
            <?php mif_bpc_docs_the_prev(); ?>
            <?php mif_bpc_docs_the_next(); ?>

        </div>

	</div>

<?php get_sidebar( 'buddypress' ); ?>
<?php get_footer( 'buddypress' ); ?>
   