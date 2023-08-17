<?php
/**
 * Suppliers tab view
 *
 * @author 		Mateus Costa <mateus@costamateus.com.br>
 * @package 	Dropshipping_Shopkeeper\Templates\Admin\Tabs
 * @version 	1.0.0
 */

defined( "ABSPATH" ) || exit;

if ( !isset( $_GET[ "uid" ] ) )
	require_once plugin_dir_path( Dropshipping_Shopkeeper::FILE ) . "templates/admin/tabs/suppliers-list.php";
else
	require_once plugin_dir_path( Dropshipping_Shopkeeper::FILE ) . "templates/admin/tabs/suppliers-preview.php";
?>
