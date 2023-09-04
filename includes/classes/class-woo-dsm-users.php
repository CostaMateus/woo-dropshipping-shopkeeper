<?php
/**
 * Users class.
 *
 * @author 		Mateus Costa <mateus@costamateus.com.br>
 * @package 	Dropshipping_Shopkeeper\Classes
 * @version 	1.0.0
 */

namespace Dropshipping_Shopkeeper\Classes;

class DSM_Users
{
    /**
     * Constructor
     *
     * @since   1.0.0
     * @return  void
     */
    public function __construct()
    {
		add_filter( "manage_users_columns",       [ $this, "add_column_title_user"   ]        );
		add_filter( "manage_users_custom_column", [ $this, "add_column_content_user" ], 10, 3 );
    }

	/**
	 * Adds 'DSM Keys' column header to 'Users' page immediately after 'Role' column.
	 *
	 * @since 	1.0.0
	 * @param 	array 	$columns
	 * @return 	array 	$new_columns
	 */
	public function add_column_title_user( $columns )
	{
		if ( isset( $_GET[ "role" ] ) && $_GET[ "role" ] === "supplier" )
		{
			$new_columns = [];

			foreach ( $columns as $column_name => $column_info )
			{
				$new_columns[ $column_name ] = $column_info;
				$name                        = self::DOMAIN . "_user_keys";

				if ( "role" === $column_name )
					$new_columns[ $name ] = "DSM Keys";
			}

			return $new_columns;
		}

		return $columns;
	}

	/**
	 * Adds 'DSM Keys' column content to 'Users' page immediately after 'Role' column.
	 *
	 * @since 	1.0.0
	 * @param 	mixed 	$val
	 * @param 	string 	$column
	 * @param 	string 	$user_id
	 * @return 	mixed 	$val
	 */
	public function add_column_content_user( $val, $column, $user_id )
	{
		if ( isset( $_GET[ "role" ] ) && $_GET[ "role" ] === "supplier" )
		{
			$name = self::DOMAIN . "_user_keys";

			if ( $name === $column )
			{
				$k1 = get_user_meta( $user_id, "_dsm_secret",   true );
				$k2 = get_user_meta( $user_id, "_dsm_secret_s", true );

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
		}

		return $val;
	}
}

new DSM_Users();