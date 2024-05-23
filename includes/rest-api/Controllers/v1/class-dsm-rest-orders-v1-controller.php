<?php
/**
 * REST API Orders controller
 *
 * Handles requests to the /orders endpoint.
 *
 * @author 		Mateus Costa <mateus@costamateus.com.br>
 * @package 	Dropshipping_Shopkeeper\RestApi\v1\Controllers
 * @version 	1.0.0
 */

class DSM_REST_Orders_V1_Controller extends DSM_REST_Main_Controller
{
	/**
	 * Route base.
	 *
	 * @since   1.0.0
	 * @var 	string
	 */
	protected $rest_base = "orders";

	/**
	 * Register the routes for orders.
	 *
	 * @since   1.0.0
	 * @return  void
	 */
	public function register_routes() : void
	{
		register_rest_route(
			$this->namespace,
			"/{$this->rest_base}/update",
			[
				[
					"methods"             => WP_REST_Server::CREATABLE,
					"callback"            => [ $this, "update_item"       ],
					"permission_callback" => [ $this, "permissions_check" ],
				],
			]
		);
	}

	/**
	 * Saves in the product which shopkeeper imported it
	 *
	 * @since   1.0.0
	 * @param  	WP_REST_Request 	$request Full details about the request.
	 * @return 	WP_Error|WP_REST_Response
	 */
	public function update_item( $request ) : mixed
	{
		$checkAuth  = $this->checkAuth( $request );

		if ( !$checkAuth[ "success" ] )
		{
			$error = $checkAuth[ "error" ];
			return new WP_Error( $error[ "code" ], $error[ "message" ], $error[ "status" ] );
		}

		$user       = $checkAuth[ "user" ];
		$data       = json_decode( $request->get_body(), true );

		$checkData  = [ "order_id", "status", "skus" ];

		foreach ( $checkData as $check )
			if ( !isset( $data[ $check ] ) )
				return new WP_Error( "dsm_rest_orders_v1_missing_data", __( "Missing data body.", "woo_ds_shopkeeper" ), 400 );

		$order = wc_get_order( $data[ "order_id" ] );

		if ( !$order )
			return new WP_Error( "dsm_rest_orders_v1_not_found", __( "Order not found.", "woo_ds_shopkeeper" ), 404 );

		if ( !in_array( $data[ "status" ], [ "shipped", "completed" ] ) )
		{
			$msg = [
				"ORDER FAILED",
				"Supplier: {$user->user_login} ({$user->ID})",
				"Status: {$data[ 'status' ]}",
				"SKUs: " . implode( ", ", $data[ "skus" ] )
			];

			$order->add_order_note( implode( "\n", $msg ) );
		}
		else
		{
			if ( $data[ "status" ] == "shipped" )
			{
				$msg = [
					"ORDER SHIPPED",
					"Supplier: {$user->user_login} ({$user->ID})",
					"Tracking code: {$data[ 'tracking_code' ]}"
				];

				$order->add_order_note( implode( "\n", $msg ) );
			}

			if ( !in_array( $order->get_status(), [ "shipped", "completed" ] ) )
				$order->update_status( $data[ "status" ] );
		}

		$order->save();

		return new WP_REST_Response( [
			"code" => "dsm_rest_products_v1_success",
		], 200 );
	}
}
