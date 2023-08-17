<?php
/**
 * Products table class.
 *
 * @author 		Mateus Costa <mateus@costamateus.com.br>
 * @package 	Dropshipping_Shopkeeper\Classes\Tables
 * @version 	1.0.0
 */

namespace Dropshipping_Shopkeeper\Classes\Tables;

use Dropshipping_Shopkeeper\Classes\Helpers\DSM_Helper;

class DSM_Products_table extends \WP_List_Table
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
     * User data
     *
     * @since   1.0.0
     * @var     array|stdClass
     */
    protected $preview = null;

    /**
     * Undocumented function
     *
     * @since   1.0.0
     * @return  void
     */
	public function __construct()
    {
        parent::__construct();

        if ( !isset( $_GET[ "uid" ] ) )
        {
            $this->load_users();
            $this->load_products();
        }
        else
        {
            $this->preview  = get_user_by( "id", $_GET[ "uid" ] );
            $this->products = $this->get_products();
        }
    }

    public function get_user()
    {
        return $this->preview;
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

        foreach ( $this->products as &$product )
        {
            foreach ( $this->users as $user )
            {
                if ( $user->ID == $product->get_meta( "_dsm_supplier" ) )
                {
                    $product->supplier = $user->user_login;
                    break;
                }
            }
        }
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
            "cb"         => "<input type='checkbox' name='users[]' />",
            "thumbnail"  => "<span class='wc-image tips'></span>",
            "name"       => __( "Name",              "woo_ds_shopkeeper" ),
            "supplier"   => __( "Username Supplier", "woo_ds_shopkeeper" ),
            "sku"        => __( "SKU",               "woo_ds_shopkeeper" ),
            "stock"      => __( "Stock",             "woo_ds_shopkeeper" ),
            "price"      => __( "Price",             "woo_ds_shopkeeper" ),
            "s_price"    => __( "Supplier Price",    "woo_ds_shopkeeper" ),
            "categories" => __( "Categories",        "woo_ds_shopkeeper" ),
            "date"       => __( "Last change",       "woo_ds_shopkeeper" ),
        ];

        if ( empty( $this->preview ) ) unset( $columns[ "cb" ] );

        if ( $this->preview )
        {
            unset( $columns[ "supplier" ] );
            unset( $columns[ "s_price"  ] );
            unset( $columns[ "date"     ] );
        }

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
            "supplier" => [ "supplier", false ],
            "sku"      => [ "sku",      false ],
            "price"    => [ "price",    false ],
            "s_price"  => [ "s_price",  false ],
            "date"     => [ "date",     false ],
        ];

        if ( $this->preview )
        {
            unset( $columns[ "supplier" ] );
            unset( $columns[ "s_price"  ] );
            unset( $columns[ "date"     ] );
        }

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

        if ( $orderby == "date" && !$this->preview )
        {
            $ta = strtotime( $a->get_date_modified() );
            $tb = strtotime( $b->get_date_modified() );

            if ( strcasecmp( $ta, $tb ) !== 0 )
            {
                if ( $order === "asc" )
                    return $ta - $tb;
                else
                    return $tb - $ta;
            }

            $orderby = "price";
        }

        if ( $orderby == "price" )
        {
            if ( $this->preview )
            {
                $price_a = ( $a->sale_price ) ? $a->sale_price : $a->regular_price;
                $price_b = ( $a->sale_price ) ? $a->sale_price : $a->regular_price;

                $pa = ( float ) $price_a;
                $pb = ( float ) $price_b;
            }
            else
            {
                $pa = ( float ) $a->get_price();
                $pb = ( float ) $b->get_price();
            }

            if ( strcasecmp( $pa, $pb ) !== 0 )
            {
                if ( $order === "asc" )
                    return $pa > $pb;
                else
                    return $pa < $pb;
            }

            $orderby = "supplier";
        }

        if ( $orderby == "supplier" && !$this->preview )
        {
            $s1     = $a->get_meta( "_dsm_supplier" );
            $s2     = $a->get_meta( "_dsm_supplier" );

            $result = strcasecmp( $s1, $s2 );

            if ( $result !== 0 )
                return ( $order === "asc" ) ? $result : -$result;
        }

        $columns = ( $this->preview ) ? [ "name", "sku" ] : [ "get_name", "get_sku" ];

        foreach ( $columns as $column )
        {
            $result = strcasecmp( $a->$column, $b->$column );

            if ( $result !== 0 )
                return ( $order === "asc" ) ? $result : -$result;
        }
    }

    /**
     *
     * @since   1.0.0
     * @return  void
     */
    public function get_bulk_actions()
    {
        if ( $this->preview )
        {
            $actions = [
                "import" => __( "Import products",  "woo_ds_shopkeeper" ),
            ];

            return $actions;
        }
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
        $hidden                = [];
        $columns               = $this->get_columns();
        $sortable              = $this->get_sortable_columns();

        $this->_column_headers = [ $columns, $hidden, $sortable ];

        $this->filter_products();

        usort( $this->products, [ &$this, "usort_reorder" ] );

        $per_page              = 25;
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
        if ( $this->preview )
            return sprintf( "<input type='checkbox' name='users[]' value='%s' />", $item->ID );
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
        $default = "<img width='40' height='40' src='/wp-content/uploads/woocommerce-placeholder-150x150.png' class='attachment-40x40 size-40x40'
            decoding='async' loading='lazy' sizes='(max-width: 40px) 100vw, 40px' >";

        if ( $this->preview )
        {
            if ( $item->images )
            {
                $src = $item->images[ 0 ][ "src" ];

                return "<img width='40' height='40' src='{$src}' class='attachment-40x40 size-40x40'
                    decoding='async' loading='lazy' sizes='(max-width: 40px) 100vw, 40px' >";
            }

            return $default;
        }

        if ( $item->get_image_id() )
            return wp_get_attachment_image( $item->get_image_id(), [ 40, 40 ], true );

        return $default;
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
        $product_name    = ( $this->preview ) ? $item->name   : $item->get_name();

        if ( $this->preview ) return "<strong>{$product_name}</strong>";

        $status          = ( $this->preview ) ? $item->status : $item->get_status();
        $class           = "";
        $arr_status      = [
            "publish" => __( "Published",      "woo_ds_shopkeeper" ),
            "pending" => __( "Pending review", "woo_ds_shopkeeper" ),
            "draft"   => __( "Draft",          "woo_ds_shopkeeper" ),
            "private" => __( "Private",        "woo_ds_shopkeeper" ),
        ];

        if ( $status == "publish" )
            $class = "ispublish";

        if ( $status == "draft"   )
            $class = "isdraft";

        $status          = isset( $arr_status[ $status ] ) ? $arr_status[ $status ] : ucfirst( $status ) ;
        $status          = "<small class='{$class}' >{$status}</small>";

        $product_name   .= " - {$status}";

        $actions         = [];
        $actions[ "id" ] = "ID: {$item->get_id()}";

        if ( current_user_can( "edit_posts" ) )
        {
            $edit_link = esc_url(
                add_query_arg(
                    "wp_http_referer",
                    urlencode( wp_unslash( $_SERVER[ "REQUEST_URI" ] ) ),
                    get_edit_post_link( $item->get_id() )
                )
            );

            $actions[ "edit" ] = "<a href='{$edit_link}' >" . __( "Edit",   "woo_ds_shopkeeper" ) . "</a>";
            $product_name      = "<strong><a href='{$edit_link}' >{$item->get_name()}</a></strong>";
            $product_name     .= " - {$status}";
        }

        return sprintf( "%1s %2s", $product_name, $this->row_actions( $actions ) );
    }

    /**
     * Supplier column
     *
     * @since   1.0.0
     * @param   array|object $item
     * @return  string
     */
    public function column_supplier( $item )
    {
        $id              = $item->get_meta( "_dsm_supplier" );
        $actions         = [];
        $actions[ "id" ] = "ID: {$id}";
        $name            = $item->supplier;

        if ( current_user_can( "edit_posts" ) )
        {
            $edit_link = esc_url(
                add_query_arg(
                    "wp_http_referer",
                    urlencode( wp_unslash( $_SERVER[ "REQUEST_URI" ] ) ),
                    get_edit_user_link( $id )
                )
            );

            $actions[ "edit" ] = "<a href='{$edit_link}'>" . __( "Edit",   "woo_ds_shopkeeper" ) . "</a>";
            $name              = "<strong><a href='{$edit_link}'>{$item->supplier}</a></strong>";
        }

        return sprintf( "%1s %2s", $name, $this->row_actions( $actions ) );
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
        if ( $this->preview )
            return $item->sku;

        return $item->get_sku();
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
        $stock        = "";
        $class        = "";

        $stock_status = ( $this->preview ) ? $item->stock_status : $item->get_stock_status();
        $can_manage   = ( $this->preview ) ? $item->manage_stock : $item->get_manage_stock();

        switch ( $stock_status )
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

        if ( $can_manage )
        {
            $qtty = ( $this->preview ) ? $item->stock_quantity : $item->get_stock_quantity();
            $text = "<mark class='{$class}'>{$stock}</mark> ({$qtty})";
        }

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
        if ( $this->preview )
        {
            $sp = $item->sale_price;
            $r  = wc_price( $item->regular_price );
            $s  = wc_price( $item->sale_price    );
        }
        else
        {
            $sp = $item->get_sale_price();
            $r  = wc_price( $item->get_regular_price() );
            $s  = wc_price( $item->get_sale_price()    );
        }

        return ( $sp ) ? "{$s} <del>{$r}</del>" : $r;
    }

    /**
     * Supplier Price column
     *
     * @since   1.0.0
     * @param   array|object $item
     * @return  string
     */
    public function column_s_price( $item )
    {
        $class       = "";
        $lastUpdate  = $item->get_meta( "_dsm_supplier_price_upd"      );

        $htmlRegular = "";
        $sttRegular  = $item->get_meta( "_dsm_supplier_status_regular" );
        $regular     = $item->get_meta( "_dsm_supplier_regular_price"  );

        $htmlSale    = "";
        $sttSale     = $item->get_meta( "_dsm_supplier_status_sale"    );
        $sale        = $item->get_meta( "_dsm_supplier_sale_price"     );

        // Last update is more than two days old
        if ( $lastUpdate && strtotime( $lastUpdate ) < strtotime( "-2 days" ) )
        {
            $s = wc_price( $sale    );
            $r = wc_price( $regular );

            return ( $sale ) ? "{$s} <del>{$r}</del>" : $r;
        }

        if ( $regular )
        {
            $class       = ( $sttRegular == "up" ) ? "upprice" : ( ( $sttRegular == "down" ) ? "downprice" : "evenprice" );
            $price       = wc_price( $regular );
            $htmlRegular = "<mark class='{$class}'>{$price}</mark>";
        }

        if ( $sale )
        {
            $class       = ( $sttSale    == "up" ) ? "upprice" : ( ( $sttSale    == "down" ) ? "downprice" : "evenprice" );
            $price       = wc_price( $sale );
            $htmlSale    = "<mark class='{$class}'>{$price}</mark>";
        }

        return ( $htmlSale ) ? "{$htmlSale} <del>{$htmlRegular}</del>": $htmlRegular;
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
        if ( $this->preview )
        {
            $cats = "";

            foreach ( $item->categories as $cat )
                $cats .= $cat[ "name" ] . ( $cat[ "name" ] != end( $item->categories )[ "name" ] ? ", " : "" );

            return $cats;
        }

        return $item->get_categories( ", ", " " . _n( " ", " ", sizeof( get_the_terms( $item->get_id(), "product_cat" ) ), "woocommerce" ) . " ", " " );
    }

    /**
     * Date column
     *
     * @since   1.0.0
     * @param   array|object $item
     * @return  string
     */
    public function column_date( $item )
    {
        $date = $item->get_date_modified();
        $date = wc_format_datetime( $date, "Y/m/d H:i:s" );

        return $date;
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
     * Filter products
     *
     * @since   1.0.0
     * @return  void
     */
    public function filter_products()
    {
        if ( isset( $_GET[ "supplier" ] ) )
        {
            $products = [];

            foreach ( $this->products as $product )
                if ( $_GET[ "supplier" ] == $product->get_meta( "_dsm_supplier" ) )
                    $products[] = $product;

            $this->products = $products;
        }
    }

    public function get_products()
    {
        $url      = get_user_meta( $this->preview->ID, "_dsm_url"     , true );
        $secret   = get_user_meta( $this->preview->ID, "_dsm_secret"  , true );
        $secret_s = get_user_meta( $this->preview->ID, "_dsm_secret_s", true );

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
            $log  = "ERROR - Supplier ID {$this->user->ID} - api_query_error - " . $response->get_error_message();
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
            $log = "ERROR - Supplier ID {$this->user->ID} - translate_product - " . $e->getMessage();
            \Dropshipping_Shopkeeper::log( $log, true );

            return [];
        }

        return $products;
    }
}
