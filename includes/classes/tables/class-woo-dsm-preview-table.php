<?php
/**
 * Preview table class.
 *
 * @author 		Mateus Costa <mateus@costamateus.com.br>
 * @package 	Dropshipping_Shopkeeper\Classes\Tables
 * @version 	1.0.0
 */

namespace Dropshipping_Shopkeeper\Classes\Tables;

use Dropshipping_Shopkeeper\Classes\Helpers\DSM_Helper;

class DSM_Preview_table extends \WP_List_Table
{
    /**
     * Products data
     *
     * @since   1.0.0
     * @var     array|stdClass
     */
    protected $products;

    /**
     * User data
     *
     * @since   1.0.0
     * @var     array|stdClass
     */
    protected $supplier;

    /**
     * Constructor
     *
     * @since   1.0.0
     * @return  void
     */
	public function __construct()
    {
        parent::__construct();

        if ( !isset( $_GET[ "uid" ] ) )
        {
            $url = admin_url( "admin.php?page=woo_ds_shopkeeper&tab=suppliers" );
            wp_redirect( $url );
            exit();
        }

        $this->supplier = get_user_by( "id", $_GET[ "uid" ] );
        $this->products = $this->get_preview_products();
        $this->products = $this->filter_products();
    }

    public function get_user_login()
    {
        return $this->supplier->user_login;
    }

    /**
     * Define the columns that are going to be used in the table
     *
     * @since   1.0.0
     * @return  array   $columns    The array of columns to use with the table
     */
    public function get_columns()
    {
        $columns = [
            "cb"         => "<input type='checkbox' />",
            "thumbnail"  => "<span class='wc-image'></span>",
            "name"       => __( "Name",              "woo_ds_shopkeeper" ),
            "sku"        => __( "SKU",               "woo_ds_shopkeeper" ),
            "stock"      => __( "Stock",             "woo_ds_shopkeeper" ),
            "price"      => __( "Price",             "woo_ds_shopkeeper" ),
            "categories" => __( "Categories",        "woo_ds_shopkeeper" ),
        ];

        return $columns;
    }

    /**
     * Decide which columns to activate the sorting functionality on
     *
     * @since   1.0.0
     * @return  array   $sortable     The array of columns that can be sorted by the user
     */
    public function get_sortable_columns()
    {
        $sortable = [
            "name"     => [ "name",     false ],
            "sku"      => [ "sku",      false ],
            "price"    => [ "price",    false ],
        ];

        return $sortable;
    }

    /**
     * Sort users by name, supplier, sku, price or date.
     *
     * @since   1.0.0
     * @param   object $a
     * @param   object $b
     * @return  int
     */
    public function usort_reorder( $a, $b )
    {
        $order   = ( !empty( $_GET[ "order"   ] ) ) ? $_GET[ "order"   ] : "desc";
        $orderby = ( !empty( $_GET[ "orderby" ] ) ) ? $_GET[ "orderby" ] : "date";

        if ( $_GET[ "orderby" ] === "name" ) $orderby = "get_name";
        if ( $_GET[ "orderby" ] === "sku"  ) $orderby = "get_sku";

        if ( $orderby == "price" )
        {
            $price_a = ( $a->sale_price ) ? $a->sale_price : $a->regular_price;
            $price_b = ( $a->sale_price ) ? $a->sale_price : $a->regular_price;

            $pa = ( float ) $price_a;
            $pb = ( float ) $price_b;

            if ( strcasecmp( $pa, $pb ) !== 0 )
            {
                if ( $order === "asc" )
                    return $pa > $pb;
                else
                    return $pa < $pb;
            }
        }

        $columns = [ "name", "sku" ];

        foreach ( $columns as $column )
        {
            $result = strcasecmp( $a->$column, $b->$column );

            if ( $result !== 0 )
                return ( $order === "asc" ) ? $result : -$result;
        }
    }

    /**
     * Bulk actions options
     *
     * @since   1.0.0
     * @return  array|void
     */
    public function get_bulk_actions()
    {
        $actions = [
            "import" => __( "Import products",  "woo_ds_shopkeeper" ),
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
        _e( "No products found.", "woo_ds_shopkeeper" );
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

        $hidden                = [];
        $columns               = $this->get_columns();
        $sortable              = $this->get_sortable_columns();

        $this->_column_headers = [ $columns, $hidden, $sortable ];

        usort( $this->products, [ &$this, "usort_reorder" ] );

        $per_page              = count( $this->products );
        $current_page          = $this->get_pagenum();
        $total_items           = count( $this->products );
        $total_pages           = ceil( $total_items / $per_page );

        $this->products        = array_chunk( $this->products, $per_page )[ $current_page - 1 ];

        if ( empty( $paged ) || !is_numeric( $paged ) || $paged<=0 ) $paged = 1;

        $this->set_pagination_args( [
            "total_items" => $total_items,
            "total_pages" => $total_pages,
            "per_page"    => $per_page,
        ] );

        $this->items           = $this->products;
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

        $action   = $this->current_action();
        $actions  = array_keys( $this->get_bulk_actions() );

        if ( !in_array( $action, $actions ) ) return;

        $products = array_filter( $this->products, function( $p ) { return in_array( $p->ID, $_POST[ "products" ] ); } );

        foreach ( $products as &$product )
        {
            if ( get_option( "_dsm_categories_general", true ) )
                foreach ( $product->categories as &$category )
                    $category = DSM_Helper::find_category( $category[ "name" ], $category[ "slug" ] );

            $product = ( array ) $product;
        }

        $this->process_import_products( $products );
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
        return sprintf( "<input type='checkbox' name='products[]' value='%s' />", $item->ID );
    }

    /**
     * Thumbnail column
     *
     * @since   1.0.0
     * @param   array|object $item
     * @return  string
     */
    public function column_thumbnail( $item )
    {
        $src = "/wp-content/uploads/woocommerce-placeholder-150x150.png";

        if ( $item->images )
            $src = $item->images[ 0 ][ "src" ];

        return "<img width='40' height='40' src='{$src}' class='attachment-40x40 size-40x40'
            decoding='async' loading='lazy' sizes='(max-width: 40px) 100vw, 40px' >";
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
        return "<strong>{$item->name}</strong>";
    }

    /**
     * SKU column
     *
     * @since   1.0.0
     * @param   array|object $item
     * @return  string
     */
    public function column_sku( $item )
    {
        return $item->sku;
    }

    /**
     * Stock column
     *
     * @since   1.0.0
     * @param   array|object $item
     * @return  string
     */
    public function column_stock( $item )
    {
        $stock = "";
        $class = "";

        switch ( $item->stock_status )
        {
            case "instock":
                $stock = __( "In stock",     "woo_ds_shopkeeper" );
                $class = "instock";
            break;

            case "outofstock":
                $stock = __( "Out of stock", "woo_ds_shopkeeper" );
                $class = "outofstock";
            break;

            case "onbackorder":
                $stock = __( "On backorder", "woo_ds_shopkeeper" );
                $class = "onbackorder";
            break;
        }

        $text = "<mark class='{$class}'>{$stock}</mark>";

        if ( $item->manage_stock )
            $text .= " ({$item->stock_quantity})";

        return $text;
    }

    /**
     * Price column
     *
     * @since   1.0.0
     * @param   array|object $item
     * @return  string
     */
    public function column_price( $item )
    {
        $r = wc_price( $item->regular_price );
        $s = wc_price( $item->sale_price    );

        return ( $item->sale_price ) ? "{$s} <del>{$r}</del>" : $r;
    }

    /**
     * Categories column
     *
     * @since   1.0.0
     * @param   array|object $item
     * @return  string
     */
    public function column_categories( $item )
    {
        $cats = "";

        foreach ( $item->categories as $cat )
            $cats .= $cat[ "name" ] . ( $cat[ "name" ] != end( $item->categories )[ "name" ] ? ", " : "" );

        return $cats;
    }

    /**
     *
     * @since   1.0.0
     * @return  void
     */
    public function extra_tablenav( $which )
    {
        // if ( $which == "top" )
        //     echo"Hello, I'm before the table";

        // if ( $which == "bottom" )
        //     echo"Hi, I'm after the table";
    }

    /**
     * Get products from supplier to preview
     *
     * @since   1.0.0
     * @return  array
     */
    public function get_preview_products()
    {
        $url      = get_user_meta( $this->supplier->ID, "_dsm_url"     , true );
        $secret   = get_user_meta( $this->supplier->ID, "_dsm_secret"  , true );
        $secret_s = get_user_meta( $this->supplier->ID, "_dsm_secret_s", true );

        if ( !$url || !$secret || !$secret_s ) return [];

        $email    = get_option( "_dsm_email_general", true );
        $email    = ( !empty( $email ) && is_email( $email ) ) ? $email : get_bloginfo( "admin_email" );

        $args     = [
            "timeout"   => 60,
            "sslverify" => false,
            "headers"   => [
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
            $log  = "ERROR - Supplier ID {$this->supplier->ID} - api_query_error - " . $response->get_error_message();
            $log .= " - " . json_encode( [ "url" => $url ] );
            \Dropshipping_Shopkeeper::log( $log, true );

            return [];
        }
        else
        {
            $data = json_decode( $response[ "body" ] );

            if ( $data->code == "dsm_rest_cannot_view" )
            {
                // call error / no post
                return [];
            }
            else if ( $data->code == "dsm_rest_products_v1_invalid_email" )
            {
                // config error / account not created
                return [];
            }
            else if ( $data->code == "dsm_rest_products_v1_invalid_secret" )
            {
                // config error / invalid access key
                return [];
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
                $products[] = ( object ) DSM_Helper::translate_product( $product, true );
            }
        }
        catch ( \Exception $e )
        {
            $log = "ERROR - Supplier ID {$this->supplier->ID} - translate_product - " . $e->getMessage();
            \Dropshipping_Shopkeeper::log( $log, true );

            return [];
        }

        return $products;
    }

    /**
     * Filters the products that have already been imported
     *
     * @since   1.0.0
     * @return  array
     */
    public function filter_products()
    {
        $products = $this->products;
        $prefix   = get_user_meta( $this->supplier->ID, "_dsm_sku_prefix", true );

        foreach ( $products as $key => $product )
        {
            $product_id = wc_get_product_id_by_sku( $prefix . $product->sku );

            if ( $product_id ) unset( $products[ $key ] );
        }

        return $products;
    }

    /**
     * Import products
     *
     * @since   1.0.0
     * @param   array   $products
     * @return  void
     */
    public function process_import_products( $products )
    {
        $errors[ $this->supplier->ID ] = [
            "errors"   => 0,
            "warnings" => 0,
            "code"     => 0,
            "count"    => 0,
            "user"     => $this->supplier->user_login,
        ];

        /**
         * Disable permissions check locally
         */
        add_filter( "woocommerce_rest_check_permissions", "__return_true" );

        $request    = new \WP_REST_Request( "POST", "/wc/v3/products" );
        $controller = new \WC_REST_Products_Controller;

        $prefix     = get_user_meta( $this->supplier->ID, "_dsm_sku_prefix" , true );

        foreach ( $products as $aux_product )
        {
            $prd_id   = wc_get_product_id_by_sku( $prefix . $aux_product[ "sku" ] );

            if ( $prd_id ) continue;

            $request->set_body_params( $aux_product );
            $response = $controller->create_item( $request );

            if ( is_wp_error( $response ) )
            {
                $log = "ERROR - Supplier ID {$this->supplier->ID} - error_create_product - " . $response->get_error_message();
                \Dropshipping_Shopkeeper::log( $log, true );
                \Dropshipping_Shopkeeper::log( print_r( $response, true ), true );

                $errors[ $this->supplier->ID ][ "warnings" ]++;
                continue;
            }

            $product = wc_get_product( $response->data[ "id" ] );

            $product->update_meta_data( "_dsm_supplier",                $this->supplier->ID     );
            $product->update_meta_data( "_dsm_supplier_regular_price",  $product->regular_price );
            $product->update_meta_data( "_dsm_supplier_sale_price",     $product->sale_price    );
            $product->update_meta_data( "_dsm_supplier_sku",            $product->sku           );
            $product->update_meta_data( "_dsm_supplier_status_regular", "even"                  );
            $product->update_meta_data( "_dsm_supplier_status_sale",    "even"                  );

            try
            {
                $product->set_sku( $prefix . $product->sku );
            }
            catch ( \Exception $e )
            {
                $log = "ERROR - Supplier ID {$this->supplier->ID} - error_set_sku - " . $e->getMessage();
                \Dropshipping_Shopkeeper::log( $log, true );

                $product->delete( true );
                $errors[ $this->supplier->ID ][ "warnings" ]++;
                continue;
            }

            $product->save();
        }

        $count      = count( $products ) - $errors[ $this->supplier->ID ][ "warnings" ];
        $log        = "INFO - Supplier ID {$this->supplier->ID} - imported {$count} products";
        \Dropshipping_Shopkeeper::log( $log );

        $errors[ $this->supplier->ID ][ "count" ] = $count;

        $warning    = max( array_column( $errors, "warnings" ) ) == 0;
        $error      = max( array_column( $errors, "errors"   ) ) == 0;

        if ( $warning && $error )
            return $this->redirect( "success", $errors );

        else if ( !$warning )
            return $this->redirect( "warning", $errors );

        return $this->redirect( "error", $errors );
    }

    /**
     * Response for process
     *
     * @since   1.0.0
     * @param   string  $type
     * @param   array   $data
     * @return  void
     */
    private function redirect( $type, $data )
    {
        $data = http_build_query( [ "data" => $data ] );

        $url  = admin_url( "admin.php?page=woo_ds_shopkeeper&tab=suppliers&{$type}_message=DRPWE&{$data}" );
        wp_redirect( $url );
        return;
    }
}
