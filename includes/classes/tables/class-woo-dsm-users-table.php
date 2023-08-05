<?php
/**
 * Users table class.
 *
 * @author 		Mateus Costa <mateus@costamateus.com.br>
 * @package 	Dropshipping_Shopkeeper\Classes\Tables
 * @version 	1.0.0
 */

namespace Dropshipping_Shopkeeper\Classes\Tables;

use Dropshipping_Shopkeeper\Classes\Helpers\DSM_Helper;

class DSM_Users_table extends \WP_List_Table
{
    /**
     * Products data
     *
     * @since   1.0.0
     * @var     array|stdClass
     */
    protected $products;

    /**
     * Users data
     *
     * @since   1.0.0
     * @var     array|stdClass
     */
    protected $users;

    /**
     * Constructor
     *
     * @since   1.0.0
     * @return  void
     */
    public function __construct()
    {
        parent::__construct();

        $this->load_products();
		$this->load_users();
    }

    /**
     * Load the products data
     *
     * @since   1.0.0
     * @return  void
     */
    private function load_products()
    {
        $this->products = wc_get_products( [
            "limit"        => -1,
            "meta_key"     => "_dsm_supplier",
            "meta_compare" => "EXISTS",
        ] );
    }

    /**
     * Load the users data
     *
     * @since   1.0.0
     * @return  void
     */
    private function load_users()
    {
		$this->users = get_users( [
			"role"  => "supplier",
			"order" => "ASC"
		] );

        array_map( function( &$user ) {
            $user->usermeta = array_map( function ($data ) {
                return reset( $data );
            }, get_user_meta( $user->ID ) );
            return $user;
        }, $this->users );

        foreach ( $this->users as &$user )
        {
            $count = 0;

            foreach ( $this->products as $product )
            {
                $id = $product->get_meta( "_dsm_supplier" );

                if ( !empty( $id ) && $id == $user->ID )
                    $count++;
            }

            $user->qty_products = $count;
        }
    }

    /**
     * Define the columns that are going to be used in the table
     *
     * @since   1.0.0
     * @return  array   $columns    The array of columns to use with the table
     */
    public function get_columns()
    {
        return $columns = [
            "cb"       => "",
            "username" => __( "Username",   "woo_ds_shopkeeper" ),
            "name"     => __( "Name",       "woo_ds_shopkeeper" ),
            "prefix"   => __( "SKU Prefix", "woo_ds_shopkeeper" ),
            "email"    => __( "Email",      "woo_ds_shopkeeper" ),
            "products" => __( "Products",   "woo_ds_shopkeeper" ),
            "keys"     => __( "Keys",       "woo_ds_shopkeeper" ),
        ];
    }

    /**
     * Decide which columns to activate the sorting functionality on
     *
     * @since   1.0.0
     * @return  array   $sortable     The array of columns that can be sorted by the user
     */
    public function get_sortable_columns()
    {
        return $sortable = [
            "username"  => [ "username", false ],
            "email"     => [ "email",    false ],
            "products"  => [ "products", false ],
        ];
    }

    /**
     * Sort users by username, email or quantity of products.
     *
     * @since   1.0.0
     * @param   object $a
     * @param   object $b
     * @return  int
     */
    public function usort_reorder( $a, $b )
    {
        $orderby = "username";

        if ( !empty( $_GET[ "orderby" ] ) )
        {
            if ( $_GET[ "orderby" ] === "email"    ) $orderby = "user_email";
            if ( $_GET[ "orderby" ] === "products" ) $orderby = "qty_products";
        }

        $order   = ( !empty( $_GET[ "order" ] ) ) ? $_GET[ "order" ] : "asc";
        $result  = strcasecmp( $a->$orderby, $b->$orderby );

        return ( $order === "asc" ) ? $result : -$result;
    }

    /**
     * Get bulk actions
     *
     * @since   1.0.0
     * @return  array
     */
    public function get_bulk_actions()
    {
        $actions = [
            "sync"         => __( "Sync stock",       "woo_ds_shopkeeper" ),
            "import"       => __( "Import products",  "woo_ds_shopkeeper" ),
            "del_products" => __( "Delete products",  "woo_ds_shopkeeper" ),
            "del_users"    => __( "Delete suppliers", "woo_ds_shopkeeper" )
        ];

        return $actions;
    }

    /**
     * No items to show
     *
     * @since   1.0.0
     * @return  void
     */
    public function no_items()
    {
        _e( "No users found.", "woo_ds_shopkeeper" );
    }

    /**
     * Prepare the table with different parameters, pagination, columns and table elements
     *
     * @since   1.0.0
     * @return  void
     */
    public function prepare_items()
    {
        $this->process_bulk_action();

        if ( $this->current_action() != "sync" )
        {
            $this->load_products();
            $this->load_users();
        }

        $hidden                = [];
        $columns               = $this->get_columns();
        $sortable              = $this->get_sortable_columns();

        $this->_column_headers = [ $columns, $hidden, $sortable ];

        usort( $this->users, [ &$this, "usort_reorder" ] );

        $per_page              = 25;
        $current_page          = $this->get_pagenum();
        $total_items           = count( $this->users );
        $total_pages           = ceil( $total_items / $per_page );

        $this->users           = array_chunk( $this->users, $per_page )[ $current_page - 1 ];

        if ( empty( $paged ) || !is_numeric( $paged ) || $paged <= 0 )
            $paged = 1;

        $this->set_pagination_args( [
            "total_items" => $total_items,
            "total_pages" => $total_pages,
            "per_page"    => $per_page,
        ] );

        $this->items           = $this->users;
    }

    /**
     * Process the bulk actions
     *
     * @since   1.0.0
     * @return  void
     */
    public function process_bulk_action()
    {
        if ( isset( $_POST[ "_wpnonce" ] ) && !empty( $_POST[ "_wpnonce" ] ) )
        {
            $nonce  = htmlspecialchars( $_POST[ "_wpnonce" ] );
            $action = "bulk-" . $this->_args[ "plural" ];

            if ( !wp_verify_nonce( $nonce, $action ) ) wp_die( "Nope! Security check failed!" );
        }

        $action  = $this->current_action();
        $actions = array_keys( $this->get_bulk_actions() );

        if ( !in_array( $action, $actions ) ) return;

        switch ( $action )
        {
            case "sync":
                $this->process_b_a_sync();
            break;

            case "import":
                $this->process_b_a_import();
            break;

            case "del_products":
                $this->process_b_a_del_products();
            break;

            case "del_users":
                $this->process_b_a_del_users();
            break;
        }
    }

    /**
     * Unmapped columns
     *
     * @since   1.0.0
     * @param   array|object    $item
     * @param   string          $column_name
     * @return  mixed
     */
    public function column_default( $item, $column_name )
    {
        switch( $column_name )
        {
            default:
                return print_r( $item, true ) ; //Show the whole array for troubleshooting purposes
            break;
        }
    }

    /**
     * Checkbox column
     *
     * @since   1.0.0
     * @param   array|object $item
     * @return  string
     */
    public function column_cb( $item )
    {
        $meta   = $item->usermeta;
        $prefix = $meta[ "_dsm_sku_prefix" ] ? "" : "disabled";

        return sprintf( "<input type='checkbox' name='users[]' value='%s' $prefix />", $item->ID );
    }

    /**
     * Username column
     *
     * @since   1.0.0
     * @param   array|object $item
     * @return  string
     */
    public function column_username( $item )
    {
        $actions         = [];
        $actions[ "id" ] = "ID: {$item->ID}";

        $username        = $item->user_login;

        if ( current_user_can( "edit_user", $item->ID ) )
        {
            $edit_link = esc_url(
                add_query_arg(
                    "wp_http_referer",
                    urlencode( wp_unslash( $_SERVER[ "REQUEST_URI" ] ) ),
                    get_edit_user_link( $item->ID )
                )
            );

            $actions[ "edit" ] = "<a href='{$edit_link}'>" . __( "Edit", "woo_ds_shopkeeper" ) . "</a>";
            $username          = "<strong><a href='{$edit_link}'>{$username}</a></strong>";
        }

        return sprintf( "%1s %2s", $username, $this->row_actions( $actions ) );
    }

    /**
     * Name column
     *
     * @since   1.0.0
     * @param   array|object $item
     * @return  string
     */
    public function column_name( $item )
    {
        return $item->user_firstname . " " . $item->user_lastname;
    }

    /**
     * SKU Prefix column
     *
     * @since   1.0.0
     * @param   array|object $item
     * @return  string
     */
    public function column_prefix( $item )
    {
        $meta   = $item->usermeta;
        $prefix = $meta[ "_dsm_sku_prefix" ];

        if ( !$prefix )
            return "<mark class='outofstock'>" . __( "Missing SKU Prefix", "woo_ds_shopkeeper" ) . "</mark>";

        return $prefix;
    }

    /**
     * Email column
     *
     * @since   1.0.0
     * @param   array|object $item
     * @return  string
     */
    public function column_email( $item )
    {
        return $item->user_email;
    }

    /**
     * Keys column
     *
     * @since   1.0.0
     * @param   array|object $item
     * @return  string
     */
    public function column_keys( $item )
    {
        $meta = $item->usermeta;

        $k1   = $meta[ "_dsm_secret"   ];
        $k2   = $meta[ "_dsm_secret_s" ];

        if ( $k1 && $k2 )
        {
            $text  = translate( "Keys setted", "woo_ds_shopkeeper" );
            $class = "status-processing";
        }
        elseif ( ( $k1 && !$k2 ) || ( !$k1 && $k2 ) )
        {
            $text  = translate( "Missing one key", "woo_ds_shopkeeper" );
            $class = "status-on-hold";
        }
        else
        {
            $text  = translate( "Both keys missing", "woo_ds_shopkeeper" );
            $class = "status-failed";
        }

        return "<mark class='order-status {$class}'><span>{$text}</span></mark>";
    }

    /**
     * Products column
     *
     * @since   1.0.0
     * @param   array|object $item
     * @return  string
     */
    public function column_products( $item )
    {
        $link = $item->qty_products;

        if ( $item->qty_products > 0 )
        {
            $url  = esc_url( admin_url( "admin.php?page=woo_ds_shopkeeper&tab=products&supplier={$item->ID}" ) );
            $link = "<a href='{$url}' >{$item->qty_products}</a>";
        }

        return $link;
    }

    /**
     * Extra infos on top or bottom of the table
     *
     * @since   1.0.0
     * @return  void
     */
    public function extra_tablenav( $which )
    {
        // if ( $which == "top"    )
        // if ( $which == "bottom" )

        require( dirname( \Dropshipping_Shopkeeper::FILE ) . "/templates/notices/message.php" );
    }

    /**
     * Sync products
     *
     * @since   1.0.0
     * @return  void
     */
    public function process_b_a_sync()
    {
        return $this->import_sync_products( $_POST[ "users" ], true );
    }

    /**
     * Import products
     *
     * @since   1.0.0
     * @return  void
     */
    public function process_b_a_import()
    {
        return $this->import_sync_products( $_POST[ "users" ] );
    }

    /**
     * Delete products of supplier
     *
     * @since   1.0.0
     * @return  void
     */
    public function process_b_a_del_products()
    {
        $users = $_POST[ "users" ];

        if ( !empty( $users ) && !empty( $this->products ) )
        {
            foreach ( $users as $user_id )
            {
                $count = 0;

                foreach ( $this->products as $product )
                {
                    $meta_id = $product->get_meta( "_dsm_supplier" );

                    if ( !empty( $meta_id ) && $meta_id == $user_id )
                    {
                        if ( $product->is_type( "variable" ) )
                        {
                            foreach ( $product->get_children() as $child_id )
                            {
                                $child = wc_get_product( $child_id );
                                $child->delete( true );
                            }
                        }
                        elseif ( $product->is_type( "grouped" ) )
                        {
                            foreach ( $product->get_children() as $child_id )
                            {
                                $child = wc_get_product( $child_id );
                                $child->set_parent_id( 0 );
                                $child->save();
                            }
                        }

                        $deleted = $product->delete( true );

                        if ( $deleted ) $count++;
                    }
                }

                $log   = "INFO - Supplier ID {$user_id} - deleted {$count} products";
                \Dropshipping_Shopkeeper::log( $log );
            }
        }
    }

    /**
     * Delete products and the supplier.
     *
     * @since   1.0.0
     * @return  void
     */
    public function process_b_a_del_users()
    {
        $this->process_b_a_del_products();

        $users = $_POST[ "users" ];

        if ( !empty( $users ) )
        {
            foreach ( $users as $user_id )
            {
                wp_delete_user( $user_id );

                $log = "INFO - Deleted supplier ID {$user_id}";
                \Dropshipping_Shopkeeper::log( $log );
            }
        }
    }

    /**
     * Response for process
     *
     * @since   1.0.0
     * @param   string  $type
     * @param   array   $errors
     * @return  void
     */
    private function redirect( $type, $errors )
    {
        $data = http_build_query( [ "data" => $errors ] );

        $url  = admin_url( "admin.php?page=woo_ds_shopkeeper&tab=suppliers&{$type}_message=DRPWE&{$data}" );
        wp_redirect( $url );
        return;
    }

    /**
     * Import or sync products
     *
     * @since   1.0.0
     * @param   array  $users
     * @param   bool   $sync
     * @return  void
     */
    private function import_sync_products( $users, $sync = false )
    {
        if ( !empty( $users ) )
        {
            $errors = [];

            foreach ( $users as $user_id )
            {
                $user     = get_user_by( "id", $user_id );

                $errors[ $user_id ] = [
                    "errors"   => 0,
                    "warnings" => 0,
                    "code"     => 0,
                    "count"    => 0,
                    "user"     => $user->user_login,
                ];

                $url      = get_user_meta( $user->ID, "_dsm_url"     , true );
                $secret   = get_user_meta( $user->ID, "_dsm_secret"  , true );
                $secret_s = get_user_meta( $user->ID, "_dsm_secret_s", true );

                if ( !$url || !$secret || !$secret_s )
                {
                    $errors[ $user_id ][ "errors" ]++;
                    $errors[ $user_id ][ "code"   ] = "MISS";
                    continue;
                }

                $email    = get_option( "_dsm_email_general", true );
                $email    = ( !empty( $email ) && is_email( $email ) ) ? $email : get_bloginfo( "admin_email" );

                $args     = [
                    "headers" => [
                        "_dsm_email"      => $email,
                        "_dsm_supplier"   => $secret_s,
                        "_dsm_shopkeeper" => $secret,
                    ]
                ];

                $url      = "{$url}/wp-json/dsm/v1/products";

                /**
                 * Get supplier products
                 */
                $response = wp_remote_get( $url, $args );

                if ( is_wp_error( $response ) )
                {
                    $log  = "ERROR - Supplier ID {$user_id} - api_query_error - " . $response->get_error_message();
                    $log .= " - " . json_encode( [ "url" => $url ] );
                    \Dropshipping_Shopkeeper::log( $log, true );

                    $errors[ $user_id ][ "errors" ]++;
                    $errors[ $user_id ][ "code"   ] = "WRG";
                    continue;
                }
                else
                {
                    $data = json_decode( $response[ "body" ] );

                    if ( $data->code == "dsm_rest_cannot_view" )
                    {
                        // call error / no post
                        $errors[ $user_id ][ "errors" ]++;
                        $errors[ $user_id ][ "code"   ] = "DRCV";
                        continue;
                    }
                    else if ( $data->code == "dsm_rest_products_v1_invalid_email" )
                    {
                        // config error / account not created
                        $errors[ $user_id ][ "errors" ]++;
                        $errors[ $user_id ][ "code"   ] = "DRPIE";
                        continue;
                    }
                    else if ( $data->code == "dsm_rest_products_v1_invalid_secret" )
                    {
                        // config error / invalid access key
                        $errors[ $user_id ][ "errors" ]++;
                        $errors[ $user_id ][ "code"   ] = "DRPIS";
                        continue;
                    }
                }

                $products = [];

                try
                {
                    foreach ( $data->data as $product )
                    {
                        if ( empty( $product->sku ) ) continue;

                        /**
                         * Translate products
                         */
                        $products[] = DSM_Helper::translate_product( $product );
                    }
                }
                catch ( \Exception $e )
                {
                    $log = "ERROR - Supplier ID {$user_id} - translate_product - " . $e->getMessage();
                    \Dropshipping_Shopkeeper::log( $log, true );

                    $errors[ $user_id ][ "errors" ]++;
                    $errors[ $user_id ][ "code"   ] = "EXCP";
                    continue;
                }

                /**
                 * Disable permissions check locally
                 */
                add_filter( "woocommerce_rest_check_permissions", "__return_true" );

                $request    = new \WP_REST_Request( "POST", "/wc/v3/products" );
                $controller = new \WC_REST_Products_Controller;

                $prefix     = get_user_meta( $user->ID, "_dsm_sku_prefix" , true );

                foreach ( $products as $aux_product )
                {
                    $prd_id   = wc_get_product_id_by_sku( $prefix . $aux_product[ "sku" ] );

                    /**
                     * Verify if product exists
                     */
                    if ( $prd_id )
                    {
                        /**
                         * Check if is for sync
                         */
                        if ( $sync )
                        {
                            $change   = false;
                            $product  = wc_get_product( $prd_id );

                            $sttPrice = $product->get_meta( "_dsm_supplier_status_price" );
                            $regular  = $product->get_meta( "_dsm_supplier_regular_price" );
                            $sale     = $product->get_meta( "_dsm_supplier_sale_price"    );

                            /**
                             * Check if has sale price
                             */
                            if ( $sale )
                            {
                                if ( $sale != $aux_product[ "sale_price" ] )
                                {
                                    $change = true;

                                    if ( $sale < $aux_product[ "sale_price" ] )
                                        $value = "down";
                                    else
                                        $value = "up";

                                    $product->update_meta_data( "_dsm_supplier_sale_price",   $aux_product[ "sale_price" ] );
                                    $product->update_meta_data( "_dsm_supplier_status_price", $value                       );
                                    $product->update_meta_data( "_dsm_supplier_price_upd",    date( "Y-m-d H:i:s" )        );
                                }
                                else
                                {
                                    if ( $sttPrice != "even" )
                                    {
                                        $change = true;
                                        $value  = "even";

                                        $product->update_meta_data( "_dsm_supplier_status_price", $value                );
                                        $product->update_meta_data( "_dsm_supplier_price_upd",    date( "Y-m-d H:i:s" ) );
                                    }
                                }
                            }

                            /**
                             * Check if has regular price
                             */
                            if ( $regular )
                            {
                                if ( $regular != $aux_product[ "regular_price" ] )
                                {
                                    $change = true;

                                    if ( $regular < $aux_product[ "regular_price" ] )
                                        $value = "down";
                                    else
                                        $value = "up";

                                    $product->update_meta_data( "_dsm_supplier_regular_price", $aux_product[ "regular_price" ] );
                                    $product->update_meta_data( "_dsm_supplier_status_price",  $value                          );
                                    $product->update_meta_data( "_dsm_supplier_price_upd",     date( "Y-m-d H:i:s" )           );
                                }
                                else
                                {
                                    if ( $sttPrice != "even" )
                                    {
                                        $change = true;
                                        $value  = "even";

                                        $product->update_meta_data( "_dsm_supplier_status_price", $value                );
                                        $product->update_meta_data( "_dsm_supplier_price_upd",    date( "Y-m-d H:i:s" ) );
                                    }
                                }
                            }

                            /**
                             * Check if stock status has changed
                             */
                            if ( $aux_product[ "stock_status" ] != $product->get_stock_status() )
                            {
                                $change = true;
                                $product->set_stock_status( $aux_product[ "stock_status" ] );

                                if ( $aux_product[ "stock_status" ] == "outofstock" )
                                    $product->set_status( "draft" );
                            }

                            /**
                             * Check if stock is manageable
                             * Check if stock quantity has changed
                             */
                            if ( $aux_product[ "manage_stock" ] && $aux_product[ "stock_quantity" ] != $product->get_stock_quantity() )
                            {
                                $change = true;
                                $product->set_stock_quantity( $aux_product[ "stock_quantity" ] );
                            }

                            /**
                             * Check if has any change and save it
                             */
                            if ( $change ) $product->save();
                        }

                        continue;
                    }

                    /**
                     * Product is for import
                     */
                    if ( !$sync )
                    {
                        $request->set_body_params( $aux_product );
                        $response = $controller->create_item( $request );

                        if ( is_wp_error( $response ) )
                        {
                            $log = "ERROR - Supplier ID {$user_id} - error_create_product - " . $response->get_error_message();
                            \Dropshipping_Shopkeeper::log( $log, true );

                            $errors[ $user_id ][ "warnings" ]++;
                            continue;
                        }

                        $product = wc_get_product( $response->data[ "id" ] );

                        $product->update_meta_data( "_dsm_supplier",               $user_id                );
                        $product->update_meta_data( "_dsm_supplier_regular_price", $product->regular_price );
                        $product->update_meta_data( "_dsm_supplier_sale_price",    $product->sale_price    );
                        $product->update_meta_data( "_dsm_supplier_sku",           $product->sku           );
                        $product->update_meta_data( "_dsm_supplier_status_price",  "even"                  );

                        try
                        {
                            if ( $prefix )
                                $product->set_sku( $prefix . $product->sku );
                        }
                        catch ( \Exception $e )
                        {
                            $log = "ERROR - Supplier ID {$user_id} - error_set_sku - " . $e->getMessage();
                            \Dropshipping_Shopkeeper::log( $log, true );

                            $product->delete( true );
                            $errors[ $user_id ][ "warnings" ]++;
                            continue;
                        }

                        $product->save();
                    }
                }

                $count      = count( $products ) - $errors[ $user_id ][ "warnings" ];
                $log        = "INFO - Supplier ID {$user_id} - imported {$count} products";
                \Dropshipping_Shopkeeper::log( $log );

                $errors[ $user_id ][ "count" ] = $count;
            }

            $warning = max( array_column( $errors, "warnings" ) ) == 0;
            $error   = max( array_column( $errors, "errors"   ) ) == 0;

            if ( $warning && $error )
                return $this->redirect( "success", $errors );

            else if ( !$warning )
                return $this->redirect( "warning", $errors );

            return $this->redirect( "error", $errors );
        }
    }
}
