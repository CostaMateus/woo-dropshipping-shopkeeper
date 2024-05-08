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

$u_table = new DSM_Users_table();
$nu_url  = esc_url( admin_url( "user-new.php" ) );
$nu_text = __( "Add New", "woo_ds_shopkeeper" );
?>

<section>
	<div class="wrap-dsm" id="dsm-users-list-table" >
		<h2 class="wp-heading-inline" style="display:inline;" >
			<?php _e( "Suppliers", "woo_ds_shopkeeper" ); ?>
		</h2>
		<a class="page-title-action" href="<?php echo $nu_url; ?>" >
			<?php echo $nu_text; ?>
		</a>

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
				<?php _e( "- Different suppliers may have identical SKUs for different products. You <strong>need</strong> to configure a different SKU prefix for each supplier, you can do this by editing the supplier's user profile.", "woo_ds_shopkeeper" ); ?>
				<br>
				<?php _e( "- Configure access keys in the supplier profile. You will generate a unique key for each supplier and you will receive a unique key for each supplier.", "woo_ds_shopkeeper" ); ?>
			</p>
			<p>
				<?php _e( "- <strong>Sync stock</strong>:", "woo_ds_shopkeeper" ); ?>
				<br>
				<?php _e( "(1) Will sync price and stock of products already imported.", "woo_ds_shopkeeper" ); ?>
				<br>
				<?php _e( "(2) When the product is out-of-stock at the supplier, it will change to draft status.", "woo_ds_shopkeeper" ); ?>
				<br>
				<?php _e( "(3) When the product returns to the supplier's stock, it will remain in draft status, requiring manual change.", "woo_ds_shopkeeper" ); ?>
			</p>
			<p>
				<?php _e( "- <strong>Preview products</strong>:", "woo_ds_shopkeeper" ); ?>
				<br>
				<?php _e( "(1) Need to have SKU Prefix configured.", "woo_ds_shopkeeper" ); ?>
				<br>
				<?php _e( "(2) Will show only products of the simple/variable type and that have the published status.", "woo_ds_shopkeeper" ); ?>
				<br>
				<?php _e( "(3) Only one supplier can be selected.", "woo_ds_shopkeeper" ); ?>
			</p>
			<p>
				<?php _e( "- <strong>Import products</strong>:", "woo_ds_shopkeeper" ); ?>
				<br>
				<?php _e( "(1) Need to have SKU Prefix configured.", "woo_ds_shopkeeper" ); ?>
				<br>
				<?php _e( "(2) Will import only products of the simple type and that have the published status.", "woo_ds_shopkeeper" ); ?>
				<br>
				<?php _e( "(3) It is recommended that you import one supplier at a time.", "woo_ds_shopkeeper" ); ?>
				<br>
				<?php _e( "(4) By default, products are registered without category, requiring manual configuration. (To change this, access the General tab)", "woo_ds_shopkeeper" ); ?>
				<br>
				<?php _e( "(5) Products are registered as a draft and with the supplier's standard price, requiring manual configuration in Products tab.", "woo_ds_shopkeeper" ); ?>
			</p>
			<p>
				<?php _e( "- <strong>Delete products</strong>:", "woo_ds_shopkeeper" ); ?>
				<br>
				<?php _e( "(1) Will delete all products from the selected supplier.", "woo_ds_shopkeeper" ); ?>
				<br>
				<?php _e( "(2) Supplier settings will remained.", "woo_ds_shopkeeper" ); ?>
			</p>
			<p>
				<?php _e( "- <strong>Delete suppliers</strong>:", "woo_ds_shopkeeper" ); ?>
				<br>
				<?php _e( "(1) Will delete all products and all settings for the selected supplier.", "woo_ds_shopkeeper" ); ?>
			</p>
		</div>

		<form id="dsm-users-list-table-form" method="post" >
			<?php
				$page  = htmlspecialchars( $_POST[ "page" ] ?? "" );
				$paged = filter_input( INPUT_GET, 'paged', FILTER_SANITIZE_NUMBER_INT );

				printf( "<input type='hidden' name='page'  value='%s' />", $page  );
				printf( "<input type='hidden' name='paged' value='%d' />", $paged );

				$u_table->prepare_items();
				$u_table->display();
			?>
		</form>
	</div>
</section>
