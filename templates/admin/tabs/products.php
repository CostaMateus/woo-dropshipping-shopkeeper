<?php
/**
 * Products tab view
 *
 * @author 		Mateus Costa <mateus@costamateus.com.br>
 * @package 	Dropshipping_Shopkeeper\Templates\Admin\Tabs
 * @version 	1.0.0
 */

defined( "ABSPATH" ) || exit;

use Dropshipping_Shopkeeper\Classes\Tables\DSM_Products_table;

$products_table = new DSM_Products_table();
?>

<section>
	<h2><?php _e( "Products", "woo_ds_shopkeeper" ); ?></h2>

	<?php
		$products_table->prepare_items();
		$products_table->display();
	?>
</section>