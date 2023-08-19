
( function( $ ) {
    "use strict";

    $( function() {
        $( "#set_new_key" ).click( function() {
            const query   = window.location.search;
            const params  = new URLSearchParams( query );
            const user_id = params.has( "user_id" ) ? params.get( "user_id" ) : 0;

            $( "#key_set" ).css( "display", "none" );

            let span      = $( this ).find( "span" );
            span.css( "display", "inline-block" );

            let error     = $( "#set_key_error" );
            error.css( "display", "none" ).html( "" );

            $.ajax( {
                url  : woo_dsm_params.ajax_url,
                type : "POST",
                data : {
                    action : "generate_secret",
                    id     : user_id,
                },
                success: function( response ) {
                    $( "#new_key" ).css( "display", "block" );
                    $( "#woo_ds_new_key" ).val( response.data );
                    span.css( "display", "none" );
                },
                error: function( err ) {
                    console.log( err );
                    error.css( "display", "block" ).html( err.responseJSON.data );
                    span.css( "display", "none" );
                },
            } );
        } );

        $( "#woo_ds_copy" ).click( function() {
            let copyText = document.getElementById( "woo_ds_new_key" );
            copyText.select();
            copyText.setSelectionRange( 0, 99999 );
            document.execCommand( "copy" );

            $( "#key_copied" ).css( "display", "block" );

            setTimeout( function() {
                $( "#key_copied" ).css( "display", "none" );
            }, 4000 );

            return false;
        } );

        // =========================================================

        $( "#doaction"  ).click( function ( e ) {
            const action = $( "#bulk-action-selector-top"    ).val();
            preventSubmitBulkActions( e, action );
        } );

        $( "#doaction2" ).click( function ( e ) {
            const action = $( "#bulk-action-selector-bottom" ).val();
            preventSubmitBulkActions( e, action );
        } );

        function preventSubmitBulkActions( e, a )
        {
            const query  = window.location.search;
            const params = new URLSearchParams( query );

            const page   = params.has( "page" ) ? params.get( "page" ) : 0;
            if ( page == 0 || page != "woo_ds_shopkeeper" ) return;

            const tab    = params.has( "tab"  ) ? params.get( "tab"  ) : 0;
            if ( tab  == 0 || tab  == "products"          ) return;

            e.preventDefault();

            if ( $( '#dsm-users-list-table-form' ).length )
            {
                const users = $( '#dsm-users-list-table-form input[name^="users"]:checked' );

                if ( a == "-1" ) return;

                if ( users.length == 0 )
                {
                    alert( woo_dsm_params.messages.select_users );
                    return;
                }

                if ( a == "del_users" )
                {
                    if ( confirm( woo_dsm_params.messages.del_users ) )
                    {
                        $( "#dsm-modal-load" ).removeClass( "dsm-d-none" );
                        $( "#dsm-users-list-table-form" ).submit();
                    }
                }
                else if ( a == "del_products" )
                {
                    if ( confirm( woo_dsm_params.messages.del_products ) )
                    {
                        $( "#dsm-modal-load" ).removeClass( "dsm-d-none" );
                        $( "#dsm-users-list-table-form" ).submit();
                    }
                }
                else if ( a == "preview" )
                {
                    if ( users.length > 1 )
                    {
                        alert( woo_dsm_params.messages.preview_pdcts );
                        return;
                    }

                    $( "#dsm-modal-load" ).removeClass( "dsm-d-none" );
                    $( "#dsm-users-list-table-form" ).submit();
                }
                else
                {
                    $( "#dsm-modal-load" ).removeClass( "dsm-d-none" );
                    $( "#dsm-users-list-table-form" ).submit();
                }

                return;
            }

            if ( $( '#dsm-preview_products-list-table-form' ).length > 0 )
            {
                console.log( "test" );

                const preview_products = $( '#dsm-preview_products-list-table-form input[name^="products"]:checked' );

                if ( a == "-1" ) return;

                if ( preview_products.length == 0 )
                {
                    alert( woo_dsm_params.messages.select_preview );
                    return;
                }

                $( "#dsm-modal-load" ).removeClass( "dsm-d-none" );
                $( "#dsm-preview_products-list-table-form" ).submit();

                return;
            }
        }

        // =========================================================

        $( "#dsm-general-form" ).on( "submit", function() {
            $( "#dsm-modal-load" ).removeClass( "dsm-d-none" );
        } );

        $( "#bulk-action-selector-top"    ).on( "change", function() {
            show_bulk_actions_message( $( this ).val() );
        } );
        $( "#bulk-action-selector-bottom" ).on( "change", function() {
            show_bulk_actions_message( $( this ).val() );
        } );

        function show_bulk_actions_message( val )
        {
            let e = "";

            $( ".dsm-bulkactions-sync"         ).addClass( "dsm-d-none" );
            $( ".dsm-bulkactions-import"       ).addClass( "dsm-d-none" );
            $( ".dsm-bulkactions-del-products" ).addClass( "dsm-d-none" );
            $( ".dsm-bulkactions-del-users"    ).addClass( "dsm-d-none" );

            switch ( val )
            {
                case "sync":
                    e = ".dsm-bulkactions-sync";
                break;

                case "import":
                    e = ".dsm-bulkactions-import";
                break;

                case "del_products":
                    e = ".dsm-bulkactions-del-products";
                break;

                case "del_users":
                    e = ".dsm-bulkactions-del-users";
                break;
            }

            $( e ).removeClass( "dsm-d-none" );
        }

        // =========================================================

        $( "#dsm-instructions-toggle" ).click( function( e ) {
            if ( $( "#dsm-instructions" ).hasClass( "dsm-d-none" ) )
                $( "#dsm-instructions" ).removeClass( "dsm-d-none" );
            else
                $( "#dsm-instructions" ).addClass( "dsm-d-none" );
        } );

    } );
}( jQuery ) );