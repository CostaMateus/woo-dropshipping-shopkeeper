<?php
/**
 * Products table class.
 *
 * @author 		Mateus Costa <mateus@costamateus.com.br>
 * @package 	Dropshipping_Shopkeeper\Classes\Tables
 * @version 	1.0.0
 */

namespace Dropshipping_Shopkeeper\Classes\Tables;

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
     * Constructor.
     *
     * @since   1.0.0
     * @return  void
     */
	public function __construct()
    {
        parent::__construct();

        $this->load_users();
        $this->load_products();
    }

    /**
     * Load the products data.
     *
     * This method retrieves the product data from the database and associates each product with its corresponding supplier.
     *
     * @since   1.0.0
     * @return  void
     */
    private function load_products() : void
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
     * Load the users data.
     *
     * This method retrieves the user data (suppliers) from the database.
     *
     * @since   1.0.0
     * @return  void
     */
    private function load_users() : void
    {
		$this->users = get_users( [
			"role"  => "supplier",
			"order" => "ASC"
		] );
    }

    /**
     * Define the columns that are going to be used in the table.
     *
     * This method defines the columns that will be displayed in the table.
     *
     * @since   1.0.0
     * @return  array   $columns    The array of columns to use with the table.
     */
    public function get_columns() : array
    {
        $columns = [
            // "cb"         => "<input type='checkbox' name='users[]' />",
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

        return $columns;
    }

    /**
     * Decide which columns to activate the sorting functionality on.
     *
     * This method defines the columns that can be sorted by the user in the table.
     *
     * @since   1.0.0
     * @return  array   $sortable     The array of columns that can be sorted by the user.
     */
    public function get_sortable_columns() : array
    {
        $sortable = [
            "name"     => [ "name",     false ],
            "supplier" => [ "supplier", false ],
            "sku"      => [ "sku",      false ],
            "price"    => [ "price",    false ],
            "s_price"  => [ "s_price",  false ],
            "date"     => [ "date",     false ],
        ];

        return $sortable;
    }

    /**
     * Sort users by name, supplier, sku, price or date.
     *
     * This method is a custom sorting function used to sort the users (products) based on the selected column and order.
     *
     * @since   1.0.0
     * @param   object $a
     * @param   object $b
     * @return  int|bool|null
     */
    public function usort_reorder( $a, $b ) : mixed
    {
        $order   = ( !empty( $_GET[ "order"   ] ) ) ? $_GET[ "order"   ] : "desc";
        $orderby = "date";

        if ( isset( $_GET[ "orderby" ] ) )
        {
            if ( $_GET[ "orderby" ] === "name" )
                $orderby = "get_name";

            else if ( $_GET[ "orderby" ] === "sku"  )
                $orderby = "get_sku";

            else
                $orderby = $_GET[ "orderby" ];
        }

        if ( $orderby == "date" )
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
            $pa = ( float ) $a->get_price();
            $pb = ( float ) $b->get_price();

            if ( strcasecmp( $pa, $pb ) !== 0 )
            {
                if ( $order === "asc" )
                    return $pa > $pb;
                else
                    return $pa < $pb;
            }

            $orderby = "supplier";
        }

        if ( $orderby == "supplier" )
        {
            $s1     = $a->get_meta( "_dsm_supplier" );
            $s2     = $a->get_meta( "_dsm_supplier" );

            $result = strcasecmp( $s1, $s2 );

            if ( $result !== 0 )
                return ( $order === "asc" ) ? $result : -$result;
        }

        $columns = [ "get_name", "get_sku" ];

        foreach ( $columns as $column )
        {
            $result = strcasecmp( $a->$column, $b->$column );

            if ( $result !== 0 )
                return ( $order === "asc" ) ? $result : -$result;
        }

        return null;
    }

    /**
     * Bulk actions options.
     *
     * This method is intended to define the bulk actions that can be performed on the selected items in the table.
     * However, it is currently empty and does not provide any bulk action options.
     *
     * @since   1.0.0
     * @return  void
     */
    public function get_bulk_actions() : void
    {
        //
    }

    /**
     * No items to show.
     *
     * This function is called when there are no products to display in the table.
     * It simply outputs the message "No products found." using the WordPress translation function.
     *
     * @since   1.0.0
     * @return  void
     */
    public function no_items() : void
    {
        _e( "No products found.", "woo_ds_shopkeeper" );
    }

    /**
     * Prepare the table with different parameters, pagination, columns and table elements.
     *
     * This function prepares the table by setting up various parameters such as columns, sorting,
     * filtering, pagination, and chunking the data into pages.
     *
     * @since   1.0.0
     * @return  void
     */
    public function prepare_items() : void
    {
        $hidden                = [];
        $columns               = $this->get_columns();
        $sortable              = $this->get_sortable_columns();

        $this->_column_headers = [ $columns, $hidden, $sortable ];

        $this->filter_products();

        usort( $this->products, [ &$this, "usort_reorder" ] );

        $per_page              = 50;
        $current_page          = $this->get_pagenum();
        $total_items           = count( $this->products );
        $total_pages           = ceil( $total_items / $per_page );

        if ( $total_items >= 1 )
        {
            $this->products = array_chunk( $this->products, $per_page )[ $current_page - 1 ];

            if ( empty( $paged ) || !is_numeric( $paged ) || $paged<=0 )
                $paged = 1;

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
     * Unmapped columns.
     *
     * This function handles the display of unmapped columns in the table.
     * If the column is not recognized, it will print the entire item array for troubleshooting purposes.
     *
     * @since   1.0.0
     * @param   array|object    $item
     * @param   string          $column_name
     * @return  mixed
     */
    public function column_default( $item, $column_name ) : mixed
    {
        switch( $column_name )
        {
            default:
                //Show the whole array for troubleshooting purposes
                return print_r( $item, true ) ;
            break;
        }
    }

    /**
     * Checkbox column.
     *
     * This function is responsible for rendering the checkbox column in the table.
     * It is currently commented out, but it would typically return an HTML checkbox input
     * with the product ID as the value, allowing for bulk actions on selected products.
     *
     * @since   1.0.0
     * @param   array|object $item   The product item being displayed in the table row.
     * @return  string               The HTML markup for the checkbox column.
     */
    public function column_cb( $item ) : void
    {
        // if ( $this->preview )
        //     return sprintf( "<input type='checkbox' name='products[]' value='%s' />", $item->ID );
    }

    /**
     * Thumbnail column.
     *
     * This function renders the thumbnail column in the table.
     *
     * @since   1.0.0
     * @param   array|object $item   The product item being displayed in the table row.
     * @return  string               The HTML markup for the thumbnail column.
     */
    public function column_thumbnail( $item ) : string
    {
        if ( $item->get_image_id() )
            return wp_get_attachment_image( $item->get_image_id(), [ 40, 40 ], true );

        return "<img width='40' height='40' src='/wp-content/uploads/woocommerce-placeholder-150x150.png' class='attachment-40x40 size-40x40'
            decoding='async' loading='lazy' sizes='(max-width: 40px) 100vw, 40px' >";
    }

    /**
     * Name column.
     *
     * This function renders the name column in the table.
     *
     * @since   1.0.0
     * @param   array|object    $item   The product item being displayed in the table row.
     * @return  string                  The HTML markup for the name column.
     */
    public function column_name( $item ) : string
    {
        $status          = $item->get_status();
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

        $product_name    = $item->get_name() . " - {$status}";

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

            $actions[ "edit" ] = "<a href='{$edit_link}' target='_blank' >" . __( "Edit",   "woo_ds_shopkeeper" ) . "</a>";
            $product_name      = "<strong><a href='{$edit_link}' target='_blank' >{$item->get_name()}</a></strong>";
            $product_name     .= " - {$status}";
        }

        return sprintf( "%1s %2s", $product_name, $this->row_actions( $actions ) );
    }

    /**
     * Supplier column.
     *
     * This function renders the supplier column in the table.
     * It displays the supplier name and an "Edit" link if the user has the "edit_posts" capability.
     * The supplier ID is also displayed as part of the row actions.
     *
     * @since   1.0.0
     * @param   array|object    $item   The product item being displayed in the table row.
     * @return  string                  The HTML markup for the supplier column.
     */
    public function column_supplier( $item ) : string
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

            $actions[ "edit" ] = "<a href='{$edit_link}' target='_blank' >" . __( "Edit",   "woo_ds_shopkeeper" ) . "</a>";
            $name              = "<strong><a href='{$edit_link}' target='_blank' >{$item->supplier}</a></strong>";
        }

        return sprintf( "%1s %2s", $name, $this->row_actions( $actions ) );
    }

    /**
     * SKU column.
     *
     * This function renders the SKU (Stock Keeping Unit) column in the table.
     * It simply displays the product's SKU.
     *
     * @since   1.0.0
     * @param   array|object    item    The product item being displayed in the table row.
     * @return  string                  The product's SKU.
     */
    public function column_sku( $item ) : string
    {
        return $item->get_sku();
    }

    /**
     * Stock column.
     *
     * This function renders the stock column in the table.
     *
     * @since   1.0.0
     * @param   array|object    $item   The product item being displayed in the table row.
     * @return  string                  The HTML markup for the stock column.
     */
    public function column_stock( $item ) : string
    {
        $stock = "";
        $class = "";

        switch ( $item->get_stock_status() )
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

        $text  = "<mark class='{$class}'>{$stock}</mark>";

        if ( $item->get_manage_stock() )
        {
            $qtty = ( $this->preview ) ? $item->stock_quantity : $item->get_stock_quantity();
            $text = "<mark class='{$class}'>{$stock}</mark> ({$qtty})";
        }

        return $text;
    }

    /**
     * Price column.
     *
     * This function renders the price column in the table.
     *
     * @since   1.0.0
     * @param   array|object    $item   The product item being displayed in the table row.
     * @return  string                  The HTML markup for the price column.
     */
    public function column_price( $item ) : string
    {
        $r = wc_price( $item->get_regular_price() );
        $s = wc_price( $item->get_sale_price()    );

        return ( $item->get_sale_price() ) ? "{$s} <del>{$r}</del>" : $r;
    }

    /**
     * Supplier Price column.
     *
     * This function renders the supplier price column in the table.
     *
     * @since   1.0.0
     * @param   array|object    $item   The product item being displayed in the table row.
     * @return  string                  The HTML markup for the supplier price column.
     */
    public function column_s_price( $item ) : string
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
     * Categories column.
     *
     * This function renders the categories column in the table.
     * It displays the product categories associated with the product, separated by commas.
     *
     * @since   1.0.0
     * @param   array|object    $item   The product item being displayed in the table row.
     * @return  string                  The product categories.
     */
    public function column_categories( $item ) : string
    {
        return $item->get_categories( ", ", " " . _n( " ", " ", sizeof( get_the_terms( $item->get_id(), "product_cat" ) ), "woocommerce" ) . " ", " " );
    }

    /**
     * Date column.
     *
     * This function renders the date column in the table.
     *
     * @since   1.0.0
     * @param   array|object    $item   The product item being displayed in the table row.
     * @return  string                  The formatted date and time of the last modification.
     */
    public function column_date( $item ) : string
    {
        $date = $item->get_date_modified();
        $date = wc_format_datetime( $date, "Y/m/d H:i:s" );

        return $date;
    }

    /**
     * Extra table navigation.
     *
     * This function is a placeholder for adding extra content before or after the table.
     *
     * @since   1.0.0
     * @return  void
     */
    public function extra_tablenav( $which ) : void
    {
        // if ( $which == "top"    )
        // if ( $which == "bottom" )
    }

    /**
     * Filter products.
     *
     * This function filters the products based on the "supplier" query parameter.
     *
     * @since   1.0.0
     * @return  void
     */
    public function filter_products() : void
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
}
