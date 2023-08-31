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
        add_action( "woocommerce_thankyou", [ $this, "send_order_to_suppliers" ], 1 );
    }

    /**
     * Send order to suppliers
     *
     * @since   1.0.0
     * @param   int  $order_id
     * @return  void
     */
    public static function send_order_to_suppliers( $order_id )
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
            $product = $item->get_product();
            $sup_id  = $product->get_meta( "_dsm_supplier",     true );
            $sku     = $product->get_meta( "_dsm_supplier_sku", true );

            $products[ $sup_id ][ $sku ] = [
                "quantity" => $item->get_quantity(),
                "sale"     => $product->get_meta( "_dsm_supplier_sale_price",    true ),
                "regular"  => $product->get_meta( "_dsm_supplier_regular_price", true ),
                "sku"      => $product->get_sku(),
            ];
        }

        foreach ( $products as $supplier_id => $products )
        {
            $user     = get_user_by( "id", $supplier_id );
            $url      = get_user_meta( $user->ID, "_dsm_url"     ,   true );
            $secret   = get_user_meta( $user->ID, "_dsm_secret"  ,   true );
            $secret_s = get_user_meta( $user->ID, "_dsm_secret_s",   true );

            if ( !$url || !$secret || !$secret_s )
            {
                self::save_error( $order, $products, $user );
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
                    "order_id"        => $order_id,
                    "customer_ip"     => $customer_ip,
                    "billing"         => $billing,
                    "shipping"        => $shipping,
                    "products"        => $products,
                ] )
            ];

            $url      = "{$url}/wp-json/dsm/v1/orders";

            /**
             * Post order to supplier
             */
            $response = wp_remote_post( $url, $args );

            if ( is_wp_error( $response ) )
            {
                self::save_error( $order, $products, $user, $response->get_error_message() );
                continue;
            }

            $status   = wp_remote_retrieve_response_code( $response );
            $data     = json_decode( $response[ "body" ] );

            if ( $status != 200 )
            {
                self::save_error( $order, $products, $user, json_encode( $data ) );
                continue;
            }

            $msg      = [
                "Order sent successfully",
                "Supplier: {$user->user_login} ({$supplier_id})",
            ];

            $order->add_order_note( implode( "\n", $msg ) );

            \Dropshipping_Shopkeeper::log( "INFO - " . implode( " - ", $msg ) );
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
    public static function save_error( &$order, $products, $user, $error = null )
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
            "Order: #{$order->get_id()}",
            "Supplier: {$user->user_login} ({$user->ID})",
            "SKUs: {$skus}"
        ];

        $order->add_order_note( implode( "\n", $msg ) );

        \Dropshipping_Shopkeeper::log( "ERROR - " . implode( " - ", $msg ), true );

        if ( $error ) \Dropshipping_Shopkeeper::log( "ERROR - {$error}", true );
    }

}

new DSM_Orders();