<?php
/**
 * Tools tab view
 *
 * @author 		Mateus Costa <mateus@costamateus.com.br>
 * @package 	Dropshipping_Shopkeeper\Templates\Admin\Tabs
 * @version 	1.0.0
 */

defined( "ABSPATH" ) || exit;

use Dropshipping_Shopkeeper\Classes\Tables\DSM_Orders_table;

$o_table = new DSM_Orders_table();
?>

<section>
	<div class="wrap-dsm" id="" >
		<h2 class="wp-heading-inline" style="display:inline;" >
			<?php _e( "Orders", "woo_ds_shopkeeper" ); ?>
		</h2>

		<h3>
			<?php _e( "Instructions:", "woo_ds_shopkeeper" ); ?>
			<a id="dsm-instructions-toggle" class="" href="#" >
				<small>
					<?php _e( "Show/Hide", "woo_ds_shopkeeper" ); ?>
				</small>
			</a>
		</h3>
		<div id="dsm-instructions" class="dsm-d-none" >
			<p>
				<?php _e( "- Only orders that contain at least one product, which is from a supplier registered through this plugin, will be listed here.", "woo_ds_shopkeeper" ); ?>
				<br>
				<?php _e( "- In the 'Suppliers' column it shows the list of suppliers that have products in that order and, in parentheses, the number of different products. Example <strong>Supplier_name (2)</strong>.", "woo_ds_shopkeeper" ); ?>
			</p>
			<p>
				<?php _e( "- <strong>Set status shipped</strong>:", "woo_ds_shopkeeper" ); ?>
				<br>
				<?php _e( "(1) Will update all orders with status 'processing' to 'shipped'.", "woo_ds_shopkeeper" ); ?>
			</p>
		</div>

		<form id="dsm-orders-list-table-form" method="post" >
			<?php
				$page  = htmlspecialchars( $_POST[ "page" ] ?? "" );
				$paged = filter_input( INPUT_GET, 'paged', FILTER_SANITIZE_NUMBER_INT );

				printf( "<input type='hidden' name='page'  value='%s' />", $page  );
				printf( "<input type='hidden' name='paged' value='%d' />", $paged );

				$o_table->prepare_items();
				$o_table->display();
			?>
		</form>
	</div>
</section>
