//
// JS-помощник документов
//
//

jQuery( document ).ready( function( jq ) {

    var time = 200;

    // var files;

    // jq( '.upload-form input[type=file]' ).change( function() {

    //     files = this.files;
    //     console.log( files );

    // });

    //
    // Меняем стиль бокса загрузки файла при наведении новых файлов
    //

    jq( '.upload-form input[type=file]' ).on( 'dragenter', function() {

        jq( '.drop-box' ).addClass( 'active');

    } );

    jq( '.upload-form input[type=file]' ).on( 'dragleave', function() {

        jq( '.drop-box' ).removeClass( 'active');

    } );


    //
    // Показать форму для ввода ссылки
    //

    jq( '.upload-form .show-link-box' ).on( 'click', function() {

        jq( '.link-box' ).fadeToggle();
        return false;

    } );

    

	//
	// Отправляем файлы на сервер
	//

	// jq( '.upload-form button' ).on( 'click', function() {
    jq( '.upload-form input[type=file]' ).change( function() {

        var form = jq( this ).closest( 'form' );
        var inputFiles = jq( 'input[type=file]', form );
        var nonce = jq( 'input[name="nonce"]', form ).val();
        var folder_id = jq( 'input[name="folder_id"]', form ).val();
        // var action = jq( 'input[name="action"]', form ).val();

        var files = inputFiles.get(0).files;

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

            // console.log( data );

            jq.ajax( {
                url: ajaxurl,
                type: 'POST',
                contentType: false,
                processData: false,
                data: data,
                success: function( response ) {

                    item.removeClass( 'loading' );
                    // jq( '.logo', item ).html( response );
                    item.replaceWith( response );
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

	jq( '.upload-form' ).on( 'submit', 'form', function() {

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
	// Удалить или восстановить документ
	//

	jq( '.collection' ).on( 'click', '.doc-remove', function() {

        var item = jq( this ).closest( '.file' );
        var doc_id = jq( this ).attr( 'data-doc-id' );
        var nonce = jq( '#docs-collection-nonce' ).val();
        var restore = ( jq( this ).hasClass( 'restore' ) ) ? 1 : 0;

        jq.post( ajaxurl, {
            action: 'mif-bpc-docs-remove-doc',
            doc_id: doc_id,
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
	// Продолжить список документов или папок
	//

	jq( '.docs-content' ).on( 'click', '.collection button', function() {

        var form = jq( this ).closest( 'form' );
        var data = new FormData( form.get( 0 ) );
        var trashed = ( jq( '#show-remove-docs' ).prop( 'checked' ) ) ? 1 : 0;
        data.append( 'trashed', trashed );
        // data.append( 'action', 'mif-bpc-docs-collection-more' );
        data.append( 'action', 'mif-bpc-docs-doc-collection-show' );

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

	jq( '.statusbar' ).on( 'change', '#show-remove-docs', function() {

        var nonce = jq( '#docs-collection-nonce' ).val();
        var folder_id = jq( '#docs-folder-id' ).val();
        var trashed = ( jq( '#show-remove-docs' ).prop( 'checked' ) ) ? 1 : 0;
        

        jq.post( ajaxurl, {
            action: 'mif-bpc-docs-doc-collection-show',
            folder_id: folder_id,
            trashed: trashed,
            _wpnonce: nonce,
        },
        function( response ) { 

            var elements = jq( response ).hide();
            // var elements = jq( response );
            jq( '.collection' ).replaceWith( elements );
            elements.fadeIn();
            // jq( '.collection' ).html( response );

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

                jq( '.docs-folder-settings').animate( { 'opacity': 0 }, function() {

                    jq( '.docs-folder-settings').html( response );
                    jq( '.docs-folder-settings').animate( { 'opacity': 1 } );

                } )
                
                // console.log( response );

            }
        } );

		return false;

	} );

   


    //
    // Обновляет статусную строку папки
    //

    function folder_statusbar_info_update()
    {
        var nonce = jq( '#docs-collection-nonce' ).val();

        jq.post( ajaxurl, {
            action: 'mif-bpc-docs-folder-statusbar-info',
            _wpnonce: nonce,
        },
        function( response ) { 

            jq( '.statusbar .info' ).html( response );
            // console.log( response );

        });

    }

    // Запустить обновление срузу после загрузки страницы

    folder_statusbar_info_update();

});

