<?php
/**
 * Users table class.
 *
 * @author 		Mateus Costa <mateus@costamateus.com.br>
 * @package 	Dropshipping_Shopkeeper\Classes\Tables
 * @version 	1.0.0
 */

namespace Dropshipping_Shopkeeper\Classes\Tables;

class DSM_Users_table extends \WP_List_Table
{
    /**
     * Define the columns that are going to be used in the table
     *
     * @since   1.0.0
     * @return  array   $columns    The array of columns to use with the table
     */
    public function get_columns()
    {
        return $columns = [
            // "cb"       => "",
            "username" => __( "Username", "woo_ds_shopkeeper" ),
            "name"     => __( "Name",     "woo_ds_shopkeeper" ),
            "email"    => __( "Email",    "woo_ds_shopkeeper" ),
            "products" => __( "Products", "woo_ds_shopkeeper" ),
            "keys"     => __( "Keys",     "woo_ds_shopkeeper" ),
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
     * @param   array $a
     * @param   array $b
     * @return  int
     */
    public function usort_reorder( $a, $b )
    {
        $orderby = ( !empty( $_GET[ "orderby" ] ) ) ? $_GET[ "orderby" ] : "username";
        $order   = ( !empty( $_GET[ "order"   ] ) ) ? $_GET[ "order"   ] : "asc";
        $result  = strcmp( $a[ $orderby ], $b[ $orderby ] );

        return ( $order === "asc" ) ? $result : -$result;
    }

    /**
     *
     * @since   1.0.0
     * @return  void
     */
    public function get_bulk_actions()
    {
        // $actions = [
        //     "delete" => "Delete"
        // ];
        //
        // return $actions;
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
		$users = get_users( [
			"role"  => "supplier",
			"order" => "ASC"
		] );

        array_map( function( $user ) {
            $user->usermeta = array_map( function ($data ) {
                return reset( $data );
            }, get_user_meta( $user->ID ) );

            return $user;
        }, $users );

        $hidden                = [];
        $columns               = $this->get_columns();
        $sortable              = $this->get_sortable_columns();

        $this->_column_headers = [ $columns, $hidden, $sortable ];

        usort( $users, [ &$this, "usort_reorder" ] );

        $per_page              = 10;
        $current_page          = $this->get_pagenum();
        $total_items           = count( $users );
        $total_pages           = ceil( $total_items / $per_page );

        $users                 = array_chunk( $users, $per_page )[ $current_page - 1 ];

        if ( empty( $paged ) || !is_numeric( $paged ) || $paged<=0 )
            $paged = 1;

        $this->set_pagination_args( [
            "total_items" => $total_items,
            "total_pages" => $total_pages,
            "per_page"    => $per_page,
        ] );

        $this->items           = $users;
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
        // return sprintf( "<input type='checkbox' name='users[]' value='%s' />", $item->ID );
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
        $actions = [];

        if ( current_user_can( "edit_user", $item->ID ) )
        {
            $edit_link = esc_url(
                add_query_arg(
                    "wp_http_referer",
                    urlencode( wp_unslash( $_SERVER[ "REQUEST_URI" ] ) ),
                    get_edit_user_link( $item->ID )
                )
            );

            $actions[ "edit"   ] = "<a href='{$edit_link}'>" . __( "Edit",   "woo_ds_shopkeeper" ) . "</a>";
        }

        if ( get_current_user_id() !== $item->ID && current_user_can( "delete_user", $item->ID ) )
        {
            $actions[ "delete" ] = "<a class='submitdelete' href='" . wp_nonce_url( "users.php?action=delete&amp;user=$item->ID", "bulk-users" ) . "'>" . __( "Delete", "woo_ds_shopkeeper" ) . "</a>";
        }

        return sprintf( "%1s %2s", $item->user_login, $this->row_actions( $actions ) );
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
        $count    = 0;

        $products = wc_get_products( [
            "limit"        => -1,
            "meta_key"     => "_dsm_supplier",
            "meta_compare" => "EXISTS",
        ] );

        foreach ( $products as $product )
        {
            $meta = $product->get_meta( "_dsm_supplier" );

            if ( !empty( $meta ) && $meta == $item->ID )
                $count++;
        }

        return $count;
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
}
