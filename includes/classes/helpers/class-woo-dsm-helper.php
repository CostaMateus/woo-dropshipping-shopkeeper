<?php
/**
 * Helper class.
 *
 * This class contains various helper functions for handling products and variations in WooCommerce.
 *
 * @author 		Mateus Costa <mateus@costamateus.com.br>
 * @package 	Dropshipping_Shopkeeper\Classes\Helpers
 * @version 	1.0.0
 */

namespace Dropshipping_Shopkeeper\Classes\Helpers;

class DSM_Helper
{
    /**
     * Translate product data into a format suitable for WooCommerce API.
     *
     * This function takes an array of product data and transforms it into a format that can be sent to the WooCommerce API.
     * It removes unnecessary fields, creates categories, sets the product status to "draft", and formats attributes and metadata.
     *
     * @since   1.0.0
     * @param   array   $product    The product data array.
     * @return  array               The transformed product data array.
     */
    public static function translate_product( array $product ) : array
    {
        $categories = [];

        // Remove the "id" field from the product data..
        unset( $product[ "id" ] );

        // Loop through the product images and remove unnecessary fields.
        foreach( $product[ "images" ] as &$image )
            foreach ( $image as $key => $value )
                if ( !in_array( $key, [ "alt", "src", "name" ] ) )
                    unset( $image[ $key ] );

        // Check if general categories should be created.
        // And loop through the product categories and create or find them.
        if ( get_option( "_dsm_categories_general" ) )
            foreach ( $product[ "categories" ] as $category )
                $categories[] = self::find_or_create_category( $category[ "name" ], $category[ "slug" ] );

        // Set the product categories and status.
        $product[ "categories" ] = $categories;
        $product[ "status"     ] = "draft";

        // Format the product attributes.
        foreach ( $product[ "attributes" ] as $key => $attribute )
        {
            $product[ "attributes" ][ $key ] = [
                "name"      => ucfirst( strtolower( $attribute[ "name" ] ) ),
                "position"  => $attribute[ "position"  ],
                "visible"   => $attribute[ "visible"   ],
                "variation" => $attribute[ "variation" ],
                "options"   => $attribute[ "options"   ],
            ];
        }

        // Set the product metadata.
        $product[ "meta_data" ] = [
            [
                "key"   => "_dsm_supplier_regular_price",
                "value" => $product[ "regular_price" ],
            ], [
                "key"   => "_dsm_supplier_sale_price",
                "value" => $product[ "sale_price"    ],
            ], [
                "key"   => "_dsm_supplier_sku",
                "value" => $product[ "sku"           ],
            ], [
                "key"   => "_dsm_supplier_status_regular",
                "value" => "even",
            ], [
                "key"   => "_dsm_supplier_status_sale",
                "value" => "even",
            ],
        ];

        return $product;
    }

    /**
     * Translate variation data into a format suitable for WooCommerce API.
     *
     * This function takes an array of variation data, along with images, SKU, SKU prefix, and product ID.
     * It formats the variations to be sent to the WooCommerce API, including assigning images, formatting attributes, and metadata.
     *
     * @since   1.0.0
     * @param   array   $variations     The array of variation data.
     * @param   array   $images         The array of product images.
     * @param   string  $sku            The product SKU.
     * @param   string  $sku_prefix     The SKU prefix.
     * @param   int     $product_id     The product ID.
     * @return  array                   The transformed variation data array.
     */
    public static function translate_variations( array $variations, array $images, string $sku, string $sku_prefix, int $product_id ) : array
    {
        $counter = 1;

        foreach ( $variations as &$variation )
        {
            // Set the parent ID, description, and regular price for the variation.
            $variation[ "parent_id"     ] = $product_id;
            $variation[ "description"   ] = $variation[ "variation_description" ];
            $variation[ "regular_price" ] = ( string ) $variation[ "display_regular_price" ];

            // Find the image for the variation or set a new one.
            $index = array_search( $variation[ "image" ][ "title" ], array_column( $images, "name" ) );

            if ( $index !== false )
            {
                $variation[ "image" ] = [
                    "id" => $images[ $index ][ "id" ]
                ];
            }
            else
            {
                $variation[ "image" ] = [
                    "alt"  => $variation[ "image" ][ "alt"   ],
                    "name" => $variation[ "image" ][ "title" ],
                    "src"  => $variation[ "image" ][ "url"   ],
                ];
            }

            // Remove unnecessary fields from the variation data.
            unset(
                $variation[ "variation_id"          ],
                $variation[ "variation_description" ],
                $variation[ "display_regular_price" ]
            );

            // Format the variation attributes.
            foreach ( $variation[ "attributes" ] as $key => $attribute )
            {
                unset( $variation[ "attributes" ][ $key ] );

                $new_key = explode( "attribute_", $key     )[ 1 ];
                $new_key = explode( "pa_",        $new_key );
                $new_key = ( count( $new_key ) > 1 ) ? $new_key[ 1 ] : $new_key[ 0 ];

                $variation[ "attributes" ][] = [
                    "name"   => ucfirst( strtolower( $new_key   ) ),
                    "option" => ucfirst( strtolower( $attribute ) )
                ];
            }

            // Set the variation metadata.
            $variation[ "meta_data" ] = [
                [
                    "key"   => "_dsm_supplier_regular_price",
                    "value" => $variation[ "regular_price" ],
                ], [
                    "key"   => "_dsm_supplier_sale_price",
                    "value" => $variation[ "sale_price"    ],
                ], [
                    "key"   => "_dsm_supplier_sku",
                    "value" => $variation[ "sku"           ],
                ], [
                    "key"   => "_dsm_supplier_status_regular",
                    "value" => "even",
                ], [
                    "key"   => "_dsm_supplier_status_sale",
                    "value" => "even",
                ],
            ];

            // Update the variation SKU if it matches the product SKU or SKU prefix.
            if ( in_array( $variation[ "sku" ], [ $sku, "{$sku_prefix}{$sku}" ] ) )
            {
                $variation[ "sku" ] = "{$sku_prefix}{$sku}{$counter}";
                $counter++;
            }
        }

        return $variations;
    }

    /**
     * Find or create a product category based on the given name and slug.
     *
     * This function checks if a product category already exists with the given slug.
     * If it doesn't exist, it creates a new category with the provided name and slug.
     *
     * @param   string  $name   The category name.
     * @param   string  $slug   The category slug.
     * @return  array           An array containing the category ID.
     */
    public static function find_or_create_category( string $name, string $slug ) : array
    {
        $result = [];

        // Get the term object for the given slug and taxonomy 'product_cat'.
        $cat    = get_term_by( "slug", $slug, "product_cat" );

        // If the term object exists, set the result with the term ID.
        if ( $cat instanceof \WP_Term )
        {
            $result = [ "id" => $cat->term_id ];
        }
        else
        {
            // If the term doesn't exist, create a new term with the given name and slug.
            $cat = wp_insert_term( $name, "product_cat", [ "slug" => $slug ] );

            // If the term creation was successful, set the result with the new term ID.
            if ( !is_wp_error( $cat ) )
                $result = [ "id" => $cat[ "term_id" ] ];
        }

        return $result;
    }

    /**
     * Process regular price synchronization for a product.
     *
     * This function compares the current regular price with the new price and updates the product metadata accordingly.
     *
     * @since   1.0.0
     * @param   string|float    $regular    The current regular price.
     * @param   string|float    $new_price  The new regular price.
     * @param   string          $status     The current status of the regular price.
     * @param   object          $product    The WooCommerce product object.
     * @return  bool                        True if the regular price was changed, false otherwise.
     */
    public static function process_regular_price( string|float $regular, string|float $new_price, string $status, object &$product ) : bool
    {
        $change = false;

        // Call the process_price function to handle the regular price update.
        self::process_price( $change, $status, $product, $regular, $new_price, "_dsm_supplier_regular_price" );

        // If there was a change in the regular price or status.
        if ( $change )
        {
            // Update the product metadata with the new status and timestamp.
            $product->update_meta_data( "_dsm_supplier_status_regular", $status               );
            $product->update_meta_data( "_dsm_supplier_price_upd",      date( "Y-m-d H:i:s" ) );
        }

        return $change;
    }

    /**
     * Process sale price synchronization for a product.
     *
     * This function compares the current sale price with the new price and updates the product metadata accordingly.
     *
     * @since   1.0.0
     * @param   string|float    $regular    The current regular price.
     * @param   string|float    $sale       The current sale price.
     * @param   string          $new_price  The new sale price.
     * @param   string          $status     The current status of the sale price.
     * @param   object          $product    The WooCommerce product object.
     * @return  bool                        True if the sale price was changed, false otherwise.
     */
    public static function process_sale_price( string|float $regular, string|float $sale, string $new_price, string $status, object &$product ) : bool
    {
        $change = false;

        // If both the current sale price and the new price are present.
        if ( $sale && $new_price )
        {
            // Call the process_price function to handle the sale price update.
            self::process_price( $change, $status, $product, $sale, $new_price, "_dsm_supplier_sale_price" );
        }
        elseif ( $sale && !$new_price )
        {
            // If the current sale price is present but the new price is not.
            $change = true;
            $status = "even";

            // Update the product metadata to remove the sale price.
            $product->update_meta_data( "_dsm_supplier_sale_price", null );
        }
        elseif ( !$sale && $new_price )
        {
            // If the current sale price is not present but the new price is.
            $change = true;

            // Set the new status based on whether the new price is higher or lower than the regular price.
            $status = ( $regular > $new_price ) ? "down" : "up";

            // Update the product metadata with the new sale price.
            $product->update_meta_data( "_dsm_supplier_sale_price", $new_price );
        }

        // If there was a change in the sale price or status.
        if ( $change )
        {
            // Update the product metadata with the new status and timestamp.
            $product->update_meta_data( "_dsm_supplier_status_sale", $status               );
            $product->update_meta_data( "_dsm_supplier_price_upd",   date( "Y-m-d H:i:s" ) );
        }

        return $change;
    }

    /**
     * Helper function to process price updates.
     *
     * This function compares the current price with the new price and updates the product metadata accordingly.
     *
     * @since   1.0.0
     * @param   bool            $has_change     A reference to a boolean indicating if a change occurred.
     * @param   string          $status         A reference to a string to store the new status.
     * @param   object          $product        The WooCommerce product object.
     * @param   string|float    $current        The current price.
     * @param   string|float    $new            The new price.
     * @param   string          $meta_key       The meta key for the price.
     * @return  void
     */
    public static function process_price( bool &$has_change, string &$status, object &$product, string|float $current, string|float $new, string $meta_key ) : void
    {
        // If the current price is different from the new price.
        if ( $current != $new )
        {
            $has_change = true;

            // Set the new status based on whether the new price is higher or lower.
            $status     = ( $current > $new ) ? "down" : "up";

            // Update the product metadata with the new price.
            $product->update_meta_data( $meta_key, $new );
        }
        else
        {
            // If the current price is the same as the new price.
            if ( $status != "even" )
            {
                $has_change = true;
                $status     = "even";
            }
        }
    }

    /**
     * Process product metadata.
     *
     * This function updates the metadata of a product with the provided metadata array.
     *
     * @param   int     $product_id     The product ID.
     * @param   array   $metadata       The array of metadata.
     * @return  void
     */
    public static function process_product_metadata( int $product_id, array $metadata ) : void
    {
        // Get the WooCommerce product object.
        $product = wc_get_product( $product_id );

        // Loop through the metadata array.
        // And update the product metadata with the key and value from the array.
        foreach ( $metadata as $meta )
            $product->update_meta_data( $meta[ "key" ], $meta[ "value" ] );

        // Save the product with the updated metadata.
        $product->save();
    }

    /**
     * Inform the supplier about imported products.
     *
     * This function sends a POST request to a specific URL, informing the supplier about the imported product SKUs.
     *
     * @since   1.0.0
     * @param   int     $user_id    The supplier user ID.
     * @param   array   $skus       The array of product SKUs.
     * @param   array   $args       The array of arguments for the remote request.
     * @param   string  $url        The URL to send the request to.
     * @return  void
     */
    public static function send_imported_products( int $user_id, array $skus, array $args, string $url ) : void
    {
        // Prepare the request body with the SKUs in JSON format.
        $args[ "body"    ]                   = wp_json_encode( [ "skus" => $skus ] );
        $args[ "headers" ][ "Content-Type" ] = "application/json";

        // Send the POST request to the specified URL.
        $response = wp_remote_post( $url, $args );

        // Check if there was an error with the request.
        if ( is_wp_error( $response ) )
        {
            // Log the error message.
            \Dropshipping_Shopkeeper::log( "error", json_encode( [
                "ERROR",
                "Supplier ID {$user_id}",
                "api_query_error POST",
                json_encode( [ "url" => $url ] ),
                $response->get_error_message(),
            ] ), true );

            return;
        }

        // Get the response status code.
        $status   = wp_remote_retrieve_response_code( $response );
        $data     = json_decode( $response[ "body" ], true );

        // Check if the response status code is not 200 (OK).
        if ( $status != 200 )
        {
            // Log the error message with the response data.
            \Dropshipping_Shopkeeper::log( "error", json_encode( [
                "ERROR",
                "Supplier ID {$user_id}",
                "api_query_error POST",
                json_encode( [ "url" => $url ] ),
                json_encode( $data ),
            ] ), true );

            return;
        }

        // Log the response data.
        \Dropshipping_Shopkeeper::log( "info", json_encode( $data ) );
    }

    /**
     * Redirect the user to a specific URL with data.
     *
     * This function redirects the user to a specific URL with the provided type and data.
     *
     * @since   1.0.0
     * @param   string  $type   The type of the message.
     * @param   array   $data   The data to be passed in the URL.
     * @return  void
     */
    public static function redirect( string $type, array $data ) : void
    {
        // Prepare the data to be passed in the URL.
        $data = http_build_query( [ "data" => $data ] );

        // Construct the URL with the provided type and data.
        $url  = admin_url( "admin.php?page=woo_ds_shopkeeper&tab=suppliers&{$type}_message=DRPWE&{$data}" );

        // Redirect the user to the constructed URL
        wp_redirect( $url );
        return;
    }

    /**
     * Create a new product in WooCommerce.
     *
     * This function creates a new product in WooCommerce based on the provided product data, prefix, and user ID.
     * It translates the product data, creates the product using the WooCommerce API, and creates variations if the product is variable.
     *
     * @param   array   $product    The product data array.
     * @param   string  $prefix     The prefix for the product SKU.
     * @param   int     $user_id    The supplier user ID.
     * @return  bool    True if the product was created successfully, false otherwise.
     */
    public static function create_product( array $product, string $prefix, int $user_id ) : bool
    {
        // Translate the product data.
        $t_product                  = self::translate_product( $product );

        // Prepare the product data for creating the product.
        $t_product[ "sku"       ]   = $prefix . $t_product[ "sku" ];
        $t_product[ "meta_data" ][] = [
            "key"   => "_dsm_supplier",
            "value" => $user_id,
        ];

        // Create a new WP_REST_Request for creating the product.
        $product_request            = new \WP_REST_Request( "POST", "/wc/v3/products" );
        $product_request->set_body_params( $t_product );

        // Send the request to create the product.
        $product_response           = rest_do_request( $product_request )->get_data();

        // Check if there was an error creating the product.
        if ( is_wp_error( $product_response ) )
        {
            // Log the error message.
            \Dropshipping_Shopkeeper::log( "error", "Supplier ID {$user_id} - error_create_product - " . $product_response->get_error_message(), true );
            \Dropshipping_Shopkeeper::log( "info", print_r( $product_response, true ), true );

            return false;
        }

        // Process the product metadata.
        self::process_product_metadata( $product_response[ "id" ], $t_product[ "meta_data" ] );

        // Log the product response.
        \Dropshipping_Shopkeeper::log( "info", print_r( $product_response, true ) );

        // If the product is a variable product, create variations.
        if ( $product[ "type" ] == "variable" )
        {
            $product_id         = $product_response[ "id"     ];
            $images             = $product_response[ "images" ];
            $sku                = $t_product[ "sku" ];
            $t_variations       = self::translate_variations( $t_product[ "variations" ], $images, $sku, $prefix, $product_id );

            // Create a new WP_REST_Request for creating variations.
            $variation_request  = new \WP_REST_Request( "POST", "/wc/v3/products/{$product_id}/variations/batch" );
            $variation_request->set_body_params( [ "create" => $t_variations ] );

            // Send the request to create variations.
            $variation_response = rest_do_request( $variation_request )->get_data();

            // Log the variation response.
            \Dropshipping_Shopkeeper::log( "info", print_r( $variation_response, true ) );
        }

        return true;
    }
}
