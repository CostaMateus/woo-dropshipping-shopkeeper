<?php
/**
 * REST API Products controller
 *
 * Handles requests to the /products endpoint.
 *
 * @author 		Mateus Costa <mateus@costamateus.com.br>
 * @package 	Dropshipping_Shopkeeper\RestApi\v1\Controllers
 * @version 	1.0.0
 */

use Dropshipping_Shopkeeper\Classes\Helpers\DSM_Helper;

class DSM_REST_Products_V1_Controller extends DSM_REST_Main_Controller
{
	/**
	 * Route base.
	 *
	 * @since   1.0.0
	 * @var 	string
	 */
	protected $rest_base = "products";

	/**
	 * Register the routes for products.
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

		$checkData  = [
			"stock_quantity",
			"stock_status",
			"sku",
			"managed",
			"regular_price",
			"sale_price"
		];

		foreach ( $checkData as $check )
			if ( !isset( $data[ $check ] ) )
				return new WP_Error( "dsm_rest_products_v1_missing_data", __( "Missing data body.", "woo_ds_shopkeeper" ), 400 );

		$prefix     = $user->data->usermeta[ "_dsm_sku_prefix" ];
		$product_id = wc_get_product_id_by_sku( $prefix . $data[ "sku" ] );

		if ( !$product_id )
			return new WP_Error( "dsm_rest_products_v1_not_found", __( "Product not found.", "woo_ds_shopkeeper" ), 404 );

		$product    = wc_get_product( $product_id );

		// Check if stock quantity has changed
		if ( $data[ "stock_quantity" ] != $product->get_stock_quantity() )
			$product->set_stock_quantity( $data[ "stock_quantity" ] );

		// Check if stock status has changed
		if ( $data[ "stock_status" ] != $product->get_stock_status() )
		{
			$product->set_stock_status( $data[ "stock_status" ] );

			if ( $data[ "stock_status" ] == "outofstock" )
				$product->set_status( "draft" );
		}

		// Check if stock is manageable
		if ( $data[ "managed" ] != $product->get_manage_stock() )
			$product->set_manage_stock( $data[ "managed" ] );

		$sttRegular = $product->get_meta( "_dsm_supplier_status_regular" );
		$regular    = $product->get_meta( "_dsm_supplier_regular_price"  );
		$newRegular = $data[ "regular_price" ];

		$sttSale    = $product->get_meta( "_dsm_supplier_status_sale"    );
		$sale       = $product->get_meta( "_dsm_supplier_sale_price"     );
		$newSale    = $data[ "sale_price"    ];

		// Process regular and sale price
		DSM_Helper::process_regular_price( $regular, $newRegular, $sttRegular, $product );
		DSM_Helper::process_sale_price( $regular, $sale, $newSale, $sttSale, $product );

		$product->save();

		return new WP_REST_Response( [
			"code" => "dsm_rest_products_v1_success",
		], 200 );
	}
}
