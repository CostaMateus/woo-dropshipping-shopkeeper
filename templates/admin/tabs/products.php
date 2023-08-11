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
	<div class="wrap-dsm" id="dsm-users-list-table" >
		<h2 class="wp-heading-inline" style="display:inline;" >
			<?php _e( "Products", "woo_ds_shopkeeper" ); ?>
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
				<?php _e( "- <strong>Column Price</strong>:", "woo_ds_shopkeeper" ); ?>
				<br>
				<?php _e( "(1) Displays the price your customer will see on the website.", "woo_ds_shopkeeper" ); ?>
				<br>
				<?php _e( "(2) This price will be equal to the 'Supplier Price' column when a new product is imported.", "woo_ds_shopkeeper" ); ?>
				<br>
				<?php _e( "(3) This price will not be changed by the 'Sync stock' action from the Supplier tab.", "woo_ds_shopkeeper" ); ?>
			</p>
			<p>
				<?php _e( "- <strong>Column Supplier Price</strong>:", "woo_ds_shopkeeper" ); ?>
				<br>
				<?php _e( "(1) Displays the price you will pay to the supplier.", "woo_ds_shopkeeper" ); ?>
				<br>
				<?php _e( "(2) When there are changes in the supplier's price, the values will be highlighted.", "woo_ds_shopkeeper" ); ?>
				<br>
				<?php _e( "(3) If the value drops, it will be marked in green (<small class='downprice' >" . wc_price( 99.99 ) . "</small>).", "woo_ds_shopkeeper" ); ?>
				<br>
				<?php _e( "(4) If the value does not change, it will be marked in blue (<small class='evenprice' >" . wc_price( 149.99 ) . "</small>).", "woo_ds_shopkeeper" ); ?>
				<br>
				<?php _e( "(5) And if the value goes up, it will be marked in red (<small class='upprice' >" . wc_price( 199.99 ) . "</small>).", "woo_ds_shopkeeper" ); ?>
			</p>
		</div>

		<form id="dsm-product-list-table-form" method="post" >
			<?php
				$page  = htmlspecialchars( $_POST[ "page" ] );
				$paged = filter_input( INPUT_GET, 'paged', FILTER_SANITIZE_NUMBER_INT );

				printf( "<input type='hidden' name='page'  value='%s' />", $page  );
				printf( "<input type='hidden' name='paged' value='%d' />", $paged );

				$products_table->prepare_items();
				$products_table->display();
			?>
		</form>
	</div>
</section>