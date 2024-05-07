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
     * Supplier url
     *
     * @since   1.0.0
     * @var     strings
     */
    protected $_url;

    /**
     * Supplier secret
     *
     * @since   1.0.0
     * @var     string
     */
    protected $_secret_s;

    /**
     * Supplier prefix
     *
     * @since   1.0.0
     * @var     string
     */
    protected $_prefix;

    /**
     * Shopkeepers secret
     *
     * @since   1.0.0
     * @var     string
     */
    protected $_secret;

    /**
     * Shopkeeper email
     *
     * @since   1.0.0
     * @var     string
     */
    protected $_email;

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

        $this->set_props();

        $this->products = $this->get_preview_products();
    }

    /**
     * Set global props
     *
     * @since   1.0.0
     * @return  void
     */
    private function set_props()
    {
        $this->_url      = get_user_meta( $this->supplier->ID, "_dsm_url",        true );
        $this->_secret   = get_user_meta( $this->supplier->ID, "_dsm_secret",     true );
        $this->_secret_s = get_user_meta( $this->supplier->ID, "_dsm_secret_s",   true );
        $this->_prefix   = get_user_meta( $this->supplier->ID, "_dsm_sku_prefix", true );

        $this->_email    = get_option( "_dsm_email_general" );

        if ( empty( $this->_email ) || !is_email( $this->_email ) )
            $this->_email = get_bloginfo( "admin_email" );

        $this->_args     = [
            "timeout"   => 60,
            "sslverify" => false,
            "headers"   => [
                "_dsm_email"      => $this->_email,
                "_dsm_supplier"   => $this->_secret_s,
                "_dsm_shopkeeper" => $this->_secret,
            ]
        ];
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
        return $columns = [
            "cb"         => "<input type='checkbox' />",
            "thumbnail"  => "<span class='wc-image'></span>",
            "name"       => __( "Name",              "woo_ds_shopkeeper" ),
            "sku"        => __( "SKU",               "woo_ds_shopkeeper" ),
            "stock"      => __( "Stock",             "woo_ds_shopkeeper" ),
            "price"      => __( "Price",             "woo_ds_shopkeeper" ),
            "categories" => __( "Categories",        "woo_ds_shopkeeper" ),
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
            "name"     => [ "name",     false ],
            "sku"      => [ "sku",      false ],
            "price"    => [ "price",    false ],
        ];
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
        return $actions = [ "import" => __( "Import products", "woo_ds_shopkeeper" ) ];
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

        if ( $per_page > 0 )
        {
            $current_page   = $this->get_pagenum();
            $total_items    = count( $this->products );
            $total_pages    = ceil( $total_items / $per_page );

            $this->products = array_chunk( $this->products, $per_page )[ $current_page - 1 ];

            if ( empty( $paged ) || !is_numeric( $paged ) || $paged<=0 ) $paged = 1;

            $this->set_pagination_args( [
                "total_items" => $total_items,
                "total_pages" => $total_pages,
                "per_page"    => $per_page,
            ] );

            $this->items    = $this->products;
        }
        else
        {
            $this->items    = [];
        }
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

        $products = array_filter( $this->products, function( $p )
        {
            return in_array( $p->id, $_POST[ "products" ] );
        } );

        $products = json_decode( json_encode( $products ), true );

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
        return sprintf( "<input type='checkbox' name='products[]' value='%s' />", $item->id );
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
            $src = $item->images[ 0 ]->src;

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
            $cats .= $cat->name . ( $cat->name != end( $item->categories )->name ? ", " : "" );

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
        if ( !$this->_url || !$this->_secret || !$this->_secret_s ) return [];

        $url      = "{$this->_url}/wp-json/dsm/v1/products";

        // Get supplier products
        $response = wp_remote_get( $url, $this->_args );

        if ( is_wp_error( $response ) )
        {
            \Dropshipping_Shopkeeper::log(
                "error",
                "Supplier ID {$this->supplier->ID} - api_query_error GET - " .
                $response->get_error_message() . " - " .
                json_encode( [ "url" => $url ] ),
                true
            );

            return [];
        }

        $data     = json_decode( $response[ "body" ] );

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

        $products = $data->data;

        return $this->filter_products( $products );
    }

    /**
     * Filters the products that have already been imported
     *
     * @since   1.0.0
     * @return  array
     */
    public function filter_products( $products )
    {
        foreach ( $products as $key => $product )
        {
            $product_id = wc_get_product_id_by_sku( $this->_prefix . $product->sku );

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
    private function process_import_products( $products )
    {
        $errors[ $this->supplier->ID ] = [
            "errors"   => 0,
            "warnings" => 0,
            "code"     => 0,
            "count"    => 0,
            "user"     => $this->supplier->user_login,
        ];

		// Disable permissions check locally
		add_filter( "woocommerce_rest_check_permissions", "__return_true" );

        $imported = [];

        foreach ( $products as $aux_product )
        {
            if ( wc_get_product_id_by_sku( $this->_prefix . $aux_product[ "sku" ] ) ) continue;

            $success    = DSM_Helper::create_product( $aux_product, $this->_prefix, $this->supplier->ID );

            if ( !$success )
            {
                $errors[ $this->supplier->ID ][ "warnings" ]++;
                continue;
            }

            $imported[] = $aux_product[ "sku" ];
        }

        // Inform supplier about imported products
        if ( !empty( $imported ) ) $this->prepare_to_send_imported_products( $imported );

        $count   = count( $products ) - $errors[ $this->supplier->ID ][ "warnings" ];

        \Dropshipping_Shopkeeper::log( "info", "Supplier ID {$this->supplier->ID} - imported {$count} products" );

		// Remove the filter.
		remove_filter( "woocommerce_rest_check_permissions", "__return_true" );

        $errors[ $this->supplier->ID ][ "count" ] = $count;

        $warning = max( array_column( $errors, "warnings" ) ) == 0;
        $error   = max( array_column( $errors, "errors"   ) ) == 0;

        if ( $warning && $error )
            return DSM_Helper::redirect( "success", $errors );

        else if ( !$warning )
            return DSM_Helper::redirect( "warning", $errors );

        return DSM_Helper::redirect( "error", $errors );
    }

    /**
     * Inform supplier about imported products
     *
     * @since   1.0.0
     * @param   array   $skus
     * @return  void
     */
    private function prepare_to_send_imported_products( $skus )
    {
        $url = "{$this->_url}/wp-json/dsm/v1/products/imported";

        DSM_Helper::send_imported_products( $this->supplier->ID, $skus, $this->_args, $url );
    }
}
