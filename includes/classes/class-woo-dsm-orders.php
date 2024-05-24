<?php
/**
 * Orders class.
 *
 * @author 		Mateus Costa <mateus@costamateus.com.br>
 * @package 	Dropshipping_Shopkeeper\Classes
 * @version 	1.0.0
 */

namespace Dropshipping_Shopkeeper\Classes;

class DSM_Orders
{
    /**
     * Constructor
     *
     * @since   1.0.0
     * @return  void
     */
    public function __construct()
    {
        add_action( "woocommerce_thankyou",                      [ $this, "send_order_to_suppliers"       ], 20, 1 );

		add_filter( "bulk_actions-edit-shop_order",              [ $this, "add_bulk_actions_shop_order"   ], 20, 1 );
        add_filter( "wc_order_statuses",                         [ $this, "add_shipped_to_order_statuses" ], 10, 1 );
		add_filter( "manage_edit-shop_order_columns",            [ $this, "add_column_title_shopkeeper"   ], 10, 1 );
		add_filter( "manage_shop_order_posts_custom_column",     [ $this, "add_column_content_shopkeeper" ], 10, 3 );

        // Order Box Actions
        add_action( "woocommerce_order_actions",                 [ $this, "add_order_box_action"          ], 10, 1 );
        add_action( "woocommerce_order_action_dsm_order_action", [ $this, "process_order_box_action"      ], 10, 1 );
    }

    /**
     * Send order to suppliers
     *
     * @since   1.0.0
     * @param   int     $order_id
     * @return  void
     */
    public function send_order_to_suppliers( $order_id ) : void
    {
		$order       = wc_get_order( $order_id );
        $products    = [];
        $customer_ip = $order->get_customer_ip_address();

        $billing     = [
            "first_name" => $order->get_billing_first_name(),
            "last_name"  => $order->get_billing_last_name(),
            "company"    => $order->get_billing_company(),
            "address_1"  => $order->get_billing_address_1(),
            "address_2"  => $order->get_billing_address_2(),
            "city"       => $order->get_billing_city(),
            "state"      => $order->get_billing_state(),
            "postcode"   => $order->get_billing_postcode(),
            "country"    => $order->get_billing_country(),
            "email"      => $order->get_billing_email(),
            "phone"      => $order->get_billing_phone(),
        ];

        $shipping    = [
            "first_name" => $order->get_shipping_first_name(),
            "last_name"  => $order->get_shipping_last_name(),
            "company"    => $order->get_shipping_company(),
            "address_1"  => $order->get_shipping_address_1(),
            "address_2"  => $order->get_shipping_address_2(),
            "city"       => $order->get_shipping_city(),
            "state"      => $order->get_shipping_state(),
            "postcode"   => $order->get_shipping_postcode(),
            "country"    => $order->get_shipping_country(),
        ];

        foreach ( $order->get_items() as $item )
        {
            $product     = $item->get_product();
            $supplier_id = $product->get_meta( "_dsm_supplier",     true );
            $sku         = $product->get_meta( "_dsm_supplier_sku", true );

            if ( !$supplier_id ) continue;

            $products[ $supplier_id ][ $sku ] = [
                "quantity" => $item->get_quantity(),
                "sale"     => $product->get_meta( "_dsm_supplier_sale_price",    true ),
                "regular"  => $product->get_meta( "_dsm_supplier_regular_price", true ),
                "sku"      => $product->get_sku(),
            ];
        }

        if ( empty( $products ) ) return;

        $order->update_meta_data( "_has_dsm_order", true );

        foreach ( $products as $supplier_id => $s_products )
        {
            $sent     = $order->get_meta( "_dsm_order_sent_{$order_id}_{$supplier_id}" );

            if ( $sent ) continue;

            $user     = get_user_by( "id", $supplier_id );
            $url      = get_user_meta( $user->ID, "_dsm_url"     ,   true );
            $secret   = get_user_meta( $user->ID, "_dsm_secret"  ,   true );
            $secret_s = get_user_meta( $user->ID, "_dsm_secret_s",   true );

            if ( !$url || !$secret || !$secret_s )
            {
                self::save_error( $order, $s_products, $user );
                continue;
            }

            $email    = get_option( "_dsm_email_general", true );
            $email    = ( !empty( $email ) && is_email( $email ) ) ? $email : get_bloginfo( "admin_email" );

            $args     = [
                "timeout"     => 60,
                "sslverify"   => false,
                "data_format" => "body",
                "headers"     => [
                    "_dsm_email"      => $email,
                    "_dsm_supplier"   => $secret_s,
                    "_dsm_shopkeeper" => $secret,
                    "Content-Type"    => "application/json",
                ],
                "body"        => wp_json_encode( [
                    "action"          => "create",
                    "order_id"        => $order_id,
                    "customer_ip"     => $customer_ip,
                    "billing"         => $billing,
                    "shipping"        => $shipping,
                    "products"        => $s_products,
                ] )
            ];

            $url      = "{$url}/wp-json/dsm/v1/orders";

            // Post order to supplier
            $response = wp_remote_post( $url, $args );

            if ( is_wp_error( $response ) )
            {
                self::save_error( $order, $s_products, $user, $response->get_error_message() );
                continue;
            }

            $status   = wp_remote_retrieve_response_code( $response );
            $data     = json_decode( $response[ "body" ], true );

            if ( $status != 200 )
            {
                self::save_error( $order, $s_products, $user, json_encode( $data ) );
                continue;
            }

            $msg      = [
                "Order sent successfully",
                "Supplier: {$user->user_login} ({$supplier_id})",
                "Order ID: #{$data[ "order_id" ]}",
                // "Pay link: {$data[ "link" ]}",
            ];

            $order->add_order_note( implode( "\n", $msg ) );
            $order->add_meta_data( "_dsm_order_sent_{$order_id}_{$supplier_id}", true );
            $order->add_meta_data( "_dsm_order_id_{$supplier_id}", $data[ "order_id" ] );

            \Dropshipping_Shopkeeper::log( "info" , implode( " - ", $msg ) );
        }

        $order->save();
    }

    /**
     * create error note and log
     *
     * @since   1.0.0
     * @param   object  $order
     * @param   array   $products
     * @param   object  $user
     * @param   string  $error
     * @return  void
     */
    public function save_error( &$order, $products, $user, $error = null ) : void
    {
        $last = array_key_last( $products );
        $skus = "";

        foreach ( $products as $key => $p )
        {
            $skus .= $p[ "sku" ];

            if ( $key != $last )
                $skus .= " | ";
        }

        $msg  = [
            "ORDER NOT SENT",
            "Supplier: {$user->user_login} ({$user->ID})",
            "SKUs: {$skus}"
        ];

        $order->add_order_note( implode( "\n", $msg ) );

        \Dropshipping_Shopkeeper::log( "error", implode( " - ", $msg ), true );

        if ( $error ) \Dropshipping_Shopkeeper::log( "critical", $error, true );
    }

	/**
	 * Add option to bulk actions on shop order page, to change status to 'shipped'
	 *
	 * @since 	1.0.0
	 * @param   array   $actions
	 * @return  array
	 */
	public function add_bulk_actions_shop_order( $actions ) : array
	{
		$new_actions = [];

		foreach ( $actions as $key => $action )
		{
			$new_actions[ $key ] = $action;

			if ( "mark_on-hold" === $key )
				$new_actions[ "mark_shipped" ] = __( "Change status to shipped", "woo_dsm_supplier" );
		}

		return $new_actions;
	}

	/**
	 * Add 'shipped' status after 'on-hold' status.
	 *
	 * @since 	1.0.0
     * @param   array   $order_statuses
	 * @return  array
	 */
    public function add_shipped_to_order_statuses( $order_statuses ) : array
    {
        $new_order_statuses = [];

        foreach ( $order_statuses as $key => $status )
        {
            $new_order_statuses[ $key ] = $status;

            if ( $key === "wc-on-hold" )
                $new_order_statuses[ "wc-shipped" ] = "Shipped";
        }

        return $new_order_statuses;
    }

	/**
	 * Adds 'Shopkeeper' column header to 'All Orders' page immediately after 'Status' column.
	 *
	 * @since 	1.0.0
	 * @param   array   $columns
	 * @return  array
	 */
	public function add_column_title_shopkeeper( $columns ) : array
	{
		$new_columns = [];

		foreach ( $columns as $column_name => $column_info )
		{
			$new_columns[ $column_name ] = $column_info;

			if ( "order_status" === $column_name )
            {
				$new_columns[ \Dropshipping_Shopkeeper::DOMAIN . "_supplier"     ] = __( "Supplier", "woo_ds_shopkeeper" );
				$new_columns[ \Dropshipping_Shopkeeper::DOMAIN . "_supplier_tag" ] = __( "Tag",      "woo_ds_shopkeeper" );
            }
		}

		return $new_columns;
	}

	/**
	 * Adds 'Supplier' column content to 'All Products' page immediately after 'Categories' column.
	 *
	 * @since 	1.0.0
	 * @param 	mixed 	$val
	 * @param 	string 	$column
	 * @param 	string 	$order_id
	 * @return  void
	 */
	public function add_column_content_shopkeeper( $val, $column, $order_id ) : void
	{
        $columns = [
            \Dropshipping_Shopkeeper::DOMAIN . "_supplier",
            \Dropshipping_Shopkeeper::DOMAIN . "_supplier_tag"
        ];

        if ( in_array( $column, $columns ) )
        {
            $msg   = "";
            $ids   = [];
            $order = wc_get_order( $order_id );

            if ( $order->get_meta( "_has_dsm_order" ) )
            {
                foreach ( $order->get_items() as $item )
                {
                    $product     = $item->get_product();
                    $supplier_id = $product->get_meta( "_dsm_supplier", true );

                    if ( !$supplier_id ) continue;
                    if ( isset( $ids[ $supplier_id ] ) ) continue;

                    $ids[] = $supplier_id;
                }

                if ( $column === \Dropshipping_Shopkeeper::DOMAIN . "_supplier" )
                {
                    $last_id = end( $ids );

                    foreach ( $ids as $id )
                    {
                        $supplier  = get_user_by( "id", $id );
                        $msg      .= "{$supplier->user_login}";

                        if ( count( $ids ) > 1 && $last_id != $id )
                            $msg .= "<br>";
                    }

                    echo $msg;
                }

                if ( $column === \Dropshipping_Shopkeeper::DOMAIN . "_supplier_tag" )
                {
                    $pending = false;

                    foreach ( $ids as $id )
                    {
                        $sent_tag = $order->get_meta( "_dsm_order_sent_tag_{$order_id}_{$id}" );

                        if ( !$sent_tag )
                        {
                            echo "<mark class='outofstock'>" . __( "Supplier waiting for tag.", "woo_ds_shopkeeper" ) . "</mark>";
                            $pending = true;
                            break;
                        }
                    }

                    if ( !$pending )
                        echo "<mark class='instock'>" . __( "All tags sent.", "woo_ds_shopkeeper" ) . "</mark>";
                }
            }
        }
	}

	/**
	 * Add custom action to order box.
	 *
     * @since   1.0.0
	 * @param 	array   $actions
     * @return  array
	 */
	public function add_order_box_action( $actions ) : array
	{
		global $theorder;

        $metas = [];

        foreach ( $theorder->get_items() as $item )
        {
            $product     = $item->get_product();
            $supplier_id = $product->get_meta( "_dsm_supplier", true );

            if ( !$supplier_id ) continue;
            if ( isset( $metas[ $supplier_id ] ) ) continue;

            $sent        = $theorder->get_meta( "_dsm_order_sent_{$theorder->id}_{$supplier_id}" );

            if ( !$sent ) $metas[] = $supplier_id;
        }

		if ( !empty( $metas ) )
		{
			$actions[ "dsm_order_action" ] = __( "Send order to supplier", "woo_ds_shopkeeper" );

			return $actions;
		}

		return $actions;
	}

	/**
	 * Process custom action order box.
	 *
     * @since   1.0.0
     * @param   object  $order
     * @return  void
	 */
	public function process_order_box_action( $order ) : void
	{
        $this->send_order_to_suppliers( $order->get_id() );
	}
}
