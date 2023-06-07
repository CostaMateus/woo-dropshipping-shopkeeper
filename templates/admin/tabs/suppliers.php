<?php
/**
 * Suppliers tab view
 *
 * @author 		Mateus Costa <mateus@costamateus.com.br>
 * @package 	Dropshipping_Shopkeeper\Templates\Admin\Tabs
 * @version 	1.0.0
 */

defined( "ABSPATH" ) || exit;

use Dropshipping_Shopkeeper\Classes\Tables\DSM_Users_table;

$users_table = new DSM_Users_table();
?>

<section>
	<h2><?php _e( "Suppliers", "woo_ds_shopkeeper" ); ?></h2>

	<?php
		$users_table->prepare_items();
		$users_table->display();
	?>
</section>