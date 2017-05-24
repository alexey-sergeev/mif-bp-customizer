//
// JS-помощник документов
//
//

jQuery( document ).ready( function( jq ) {

    var time = 200;


    //
    // Меняем стиль бокса загрузки файла при наведении новых файлов
    //

    jq( '.docs-page' ).on( 'dragenter', '.upload-form input[type=file]', function() {

        jq( '.drop-box' ).addClass( 'active');

    } );

    jq( '.docs-page' ).on( 'dragleave', '.upload-form input[type=file]', function() {

        jq( '.drop-box' ).removeClass( 'active');

    } );


    //
    // Показать форму для ввода ссылки
    //

    jq( '.docs-page' ).on( 'click', '.upload-form .show-link-box', function() {

        jq( '.link-box' ).fadeToggle();
        return false;

    } );


    //
    // Показать форму для удаления папки
    //

    jq( '.docs-page' ).on( 'click', '.remove-box-toggle', function() {

        jq( '.remove-box' ).fadeToggle();
        return false;

    } );

    

	//
	// Отправляем файлы или ссылки на сервер
	//

	jq( '.docs-page' ).on( 'change', '.upload-form input[type=file]', function() {

        var form = jq( this ).closest( 'form' );
        var inputFiles = jq( 'input[type=file]', form );
        var nonce = jq( 'input[name="nonce"]', form ).val();
        var folder_id = jq( 'input[name="folder_id"]', form ).val();

        var files = inputFiles.get( 0 ).files;

        jq.each( files, function( key, value ) { 
            
            var data = new FormData();
            data.append( 'file', value ); 
            data.append( 'action', 'mif-bpc-docs-upload-files' );
            data.append( '_wpnonce', nonce );
            data.append( 'folder_id', folder_id );
            // console.log( value );

            // Отобразить блок файла на экране, клонировав его из шаблона и уточнив оформление

            var item = jq( '.template .file').clone();
            item.addClass( value['name'].split( '.' ).pop() );
            jq( '.name', item ).html( value['name'] );
            item.prependTo( '.response-box' ).hide().fadeIn();
            jq( '.folder-is-empty-msg' ).remove();

            // console.log( data );

            jq.ajax( {
                url: ajaxurl,
                type: 'POST',
                contentType: false,
                processData: false,
                data: data,
                success: function( response ) {

                    item.removeClass( 'loading' );
                    item.replaceWith( response );
                    folder_statusbar_info_update();
                    // console.log( response );

                },
                error: function( response ) {

                    item.addClass( 'error' );

                },
            } );

        });

        inputFiles.val( '' );
        setTimeout( function() { jq( '.drop-box' ).removeClass( 'active'); }, time );

		return false;

	} );



	//
	// Сохраняем ссылку на сетевой документ
	//

	jq( '.docs-page' ).on( 'submit', '.upload-form form', function() {

        var form = jq( this );
        var link = jq( 'input[name="link"]', form ).val();
        var descr = jq( 'input[name="descr"]', form ).val();
        var nonce = jq( 'input[name="nonce"]', form ).val();
        var folder_id = jq( 'input[name="folder_id"]', form ).val();

        if ( link ) {

            // Отобразить блок документа на экране, клонировав его из шаблона и уточнив оформление

            var item = jq( '.template .file').clone();
            item.addClass( link.split( '.' ).pop() );
            
            var name = ( descr ) ? descr : link;
            
            jq( '.name', item ).html( link );
            item.prependTo( '.response-box' ).hide().fadeIn();

            // Отправить Ajax-запрос

            jq.post( ajaxurl, {
                action: 'mif-bpc-docs-network-link-files',
                link: link,
                descr: descr,
                folder_id: folder_id,
                _wpnonce: nonce,
            },
            function( response ) { 

                if ( response ) {

                    item.removeClass( 'loading' );
                    item.replaceWith( response );

                    jq( 'input[name="link"]', form ).val( '' );
                    jq( 'input[name="descr"]', form ).val( '' );

                } else {

                    item.addClass( 'error' );

                }

            });
        }


        return false;

    } )



	//
	// Удалить или восстановить папку или документ (через кнопку на элементе)
	//

	jq( '.docs-page' ).on( 'click', '.collection .item-remove', function() {

        var item = jq( this ).closest( '.file' );
        var item_id = jq( this ).attr( 'data-item-id' );
        var nonce = jq( '#docs-collection-nonce' ).val();
        var restore = ( jq( this ).hasClass( 'restore' ) ) ? 1 : 0;

        jq.post( ajaxurl, {
            action: 'mif-bpc-docs-remove',
            item_id: item_id,
            restore: restore,
            _wpnonce: nonce,
        },
        function( response ) { 

            if ( response == '' ) {

                item.addClass( 'error' );
                setTimeout( function() { item.removeClass( 'error' ); }, 1000 );

            } else {
                
                item.replaceWith( response );
                folder_statusbar_info_update();

            }

        });

        return false;

    } )



	//
	// Опубликовать приватную папку
	//

	jq( '.docs-page' ).on( 'click', '.folder-publisher input[type="button"]', function() {

        var form = jq( this ).closest( 'form' );
        var item_id = jq( 'input[name="item_id"]', form ).val();

        var nonce = jq( '#docs-collection-nonce' ).val();

        jq.post( ajaxurl, {
            action: 'mif-bpc-docs-publisher',
            item_id: item_id,
            _wpnonce: nonce,
        },
        function( response ) { 

            if ( response ) {

                jq( '.message.folder-publisher').animate( { 'opacity': 0 }, function() {

                    jq( '.message.folder-publisher').replaceWith( response );
                    jq( '.message.folder-publisher').animate( { 'opacity': 1 } );

                } )

        }
            // console.log( response );

        });

        return false;

    } )



	//
	// Удалить совсем или восстановить папку (через tools-панель)
	//

	jq( '.docs-page' ).on( 'click', '.folder-restore-delete input[type="button"]', function() {

        var form = jq( this ).closest( 'form' );
        var item_id = jq( 'input[name="item_id"]', form ).val();
        var restore = ( jq( this ).hasClass( 'restore' ) ) ? 1 : 0;

        var nonce = jq( '#docs-collection-nonce' ).val();

        jq.post( ajaxurl, {
            action: 'mif-bpc-docs-remove',
            item_id: item_id,
            restore: restore,
            mode: 'page',
            _wpnonce: nonce,
        },
        function( response ) { 

            docs_content_update( response );
            // console.log( response );

        });

        return false;

    } )



	//
	// Продолжить список документов или папок
	//

	jq( '.docs-page' ).on( 'click', '.docs-content .collection button', function() {

        var form = jq( this ).closest( 'form' );
        var data = new FormData( form.get( 0 ) );
        var trashed = ( jq( '#show-remove-docs' ).prop( 'checked' ) ) ? 1 : 0;
        data.append( 'trashed', trashed );
        // data.append( 'action', 'mif-bpc-docs-collection-more' );
        data.append( 'action', 'mif-bpc-docs-collection-show' );

        jq.ajax( {
            url: ajaxurl,
            type: 'POST',
            contentType: false,
            processData: false,
            data: data,
            success: function( response ) {

                jq( '.collection .more' ).remove();

                var elements = jq( response ).hide();
                jq( '.collection' ).append( elements );
                elements.fadeIn();

            }
        } );

        return false;

    } )



	//
	// Показать удаленные
	//

	jq( '.docs-page' ).on( 'change', '.statusbar #show-remove-docs', function() {

        var nonce = jq( '#docs-collection-nonce' ).val();
        var folder_id = jq( '#docs-folder-id' ).val();
        var trashed = ( jq( '#show-remove-docs' ).prop( 'checked' ) ) ? 1 : 0;
        
        jq.post( ajaxurl, {
            action: 'mif-bpc-docs-collection-show',
            folder_id: folder_id,
            trashed: trashed,
            _wpnonce: nonce,
        },
        function( response ) { 

            var elements = jq( response ).hide();
            jq( '.collection' ).replaceWith( elements );
            elements.fadeIn();

        });

        return false;

    } )



	//
	// Создаем новую папку
	//

	jq( '.docs-page' ).on( 'submit', 'form#new-folder', function() {

        var form = jq( this );
        var data = new FormData( this );
        data.append( 'action', 'mif-bpc-docs-new-folder' );

        // console.log( data );

        jq.ajax( {
            url: ajaxurl,
            type: 'POST',
            contentType: false,
            processData: false,
            data: data,
            success: function( response ) {

                window.location.href = response;

            }
        } );

		return false;

	} );



	//
	// Сохранение настроек папки
	//

	jq( '.docs-page' ).on( 'submit', 'form#folder-settings', function() {

        var form = jq( this );
        var data = new FormData( this );
        data.append( 'action', 'mif-bpc-docs-folder-settings-save' );

        // console.log( data );

        jq.ajax( {
            url: ajaxurl,
            type: 'POST',
            contentType: false,
            processData: false,
            data: data,
            success: function( response ) {

                docs_content_update( response );

            }
        } );

		return false;

	} );

   


	//
	// Редактирование папки
	//

	jq( '.docs-page' ).on( 'click', '.statusbar #folder-settings', function() {


        var nonce = jq( '#docs-collection-nonce' ).val();
        var folder_id = jq( '#docs-folder-id' ).val();

        jq.post( ajaxurl, {
            action: 'mif-bpc-docs-folder-settings',
            folder_id: folder_id,
            _wpnonce: nonce,
        },
        function( response ) { 

            docs_content_update( response );

        });

		return false;

	} );



	//
	// Кнопка "Отмена" (создание папки)
	//

	jq( '.new-folder' ).on( 'click', '#cancel', function() {

        var redirect = jq( '.new-folder input[name="redirect"]' ).val();
        window.location.href = redirect;

    } );



	//
	// Кнопка "Отмена" (редактирование папки)
	//

	jq( '.docs-page' ).on( 'click', '.folder-settings #cancel', function() {

        var form = jq( this ).closest( 'form' );
        var data = new FormData( form.get( 0 ) );
        data.append( 'action', 'mif-bpc-docs-folder-settings-save' );
        data.append( 'do', 'cancel' );

        jq.ajax( {
            url: ajaxurl,
            type: 'POST',
            contentType: false,
            processData: false,
            data: data,
            success: function( response ) {

                docs_content_update( response );

            }
        } );

    } );



	//
	// Кнопка "Удалить" (редактирование папки)
	//

	jq( '.docs-page' ).on( 'click', '.folder-settings .remove', function() {

        var form = jq( this ).closest( 'form' );
        var data = new FormData( form.get( 0 ) );
        data.append( 'action', 'mif-bpc-docs-folder-settings-save' );
        data.append( 'do', 'to-trash' );

        jq.ajax( {
            url: ajaxurl,
            type: 'POST',
            contentType: false,
            processData: false,
            data: data,
            success: function( response ) {

                docs_content_update( response );

            }
        } );

    } );



    //
    // Обработка клавиш
    //

    jq( document ).keydown( function( e ) {

        if ( e.which == 27 ) {

            if ( jq( '.folder-settings #cancel' ).length ) jq( '.folder-settings #cancel' ).trigger( 'click' );
            if ( jq( '.new-folder #cancel' ).length ) jq( '.new-folder #cancel' ).trigger( 'click' );

            return false;
        }

    });


    //
    // Обновляет содержимое страницы документов
    //

    function docs_content_update( response )
    {
        jq( '.docs-content').animate( { 'opacity': 0 }, function() {

            jq( '.docs-content').html( response );
            jq( '.docs-content').animate( { 'opacity': 1 } );
            folder_statusbar_info_update();

        } )

    }



    //
    // Обновляет статусную строку папки
    //

    function folder_statusbar_info_update()
    {
        var nonce = jq( '#docs-collection-nonce' ).val();
        var folder_id = jq( '#docs-folder-id' ).val();
        var all_folders = jq( '#docs-all-folders' ).val();

        if ( nonce ) {

            jq.post( ajaxurl, {
                action: 'mif-bpc-docs-folder-statusbar-info',
                folder_id: folder_id,
                all_folders: all_folders,
                _wpnonce: nonce,
            },
            function( response ) { 

                if ( response ) jq( '.statusbar .info' ).html( response );
                // console.log( response );

            });
            
        }

    }

    // Запустить обновление срузу после загрузки страницы

    folder_statusbar_info_update();

});

