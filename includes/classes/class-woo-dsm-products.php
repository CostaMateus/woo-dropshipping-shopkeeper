<?php
/**
 * Products class.
 *
 * @author 		Mateus Costa <mateus@costamateus.com.br>
 * @package 	Dropshipping_Shopkeeper\Classes
 * @version 	1.0.0
 */

namespace Dropshipping_Shopkeeper\Classes;

class DSM_Products
{
    /**
     * Constructor
     *
     * @since   1.0.0
     * @return  void
     */
    public function __construct()
    {
		add_filter( "manage_edit-product_columns", [ $this, "add_column_title_supplier"   ]        );
		add_filter( "manage_posts_custom_column",  [ $this, "add_column_content_supplier" ], 10, 3 );
    }

	/**
	 * Adds 'Suppler' column header to 'All Products' page immediately after 'Categories' column.
	 *
	 * @since 	1.0.0
	 * @param 	array 	$columns
	 * @return 	array	$new_columns
	 */
	public function add_column_title_supplier( $columns )
	{
		$new_columns = [];

		foreach ( $columns as $column_name => $column_info )
		{
			$new_columns[ $column_name ] = $column_info;

			if ( "product_cat" === $column_name )
				$new_columns[ \Dropshipping_Shopkeeper::DOMAIN . "_supplier" ] = __( "Supplier", "woo_ds_shopkeeper" );
		}

		return $new_columns;
	}

	/**
	 * Adds 'Supplier' column content to 'All Products' page immediately after 'Categories' column.
	 *
	 * @since 	1.0.0
	 * @param 	mixed 	$val
	 * @param 	string 	$column
	 * @param 	string 	$product_id
	 * @return 	mixed 	$val
	 */
	public function add_column_content_supplier( $column, $product_id )
	{
		if ( $column === \Dropshipping_Shopkeeper::DOMAIN . "_supplier" )
		{
			$product     = wc_get_product( $product_id );
			$supplier_id = $product->get_meta( "_dsm_supplier" );

			if ( $supplier_id )
			{
				$supplier = get_user_by( "id", $supplier_id );

				echo $supplier->user_login;
			}
		}
	}
}

new DSM_Products();