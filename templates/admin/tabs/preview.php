<?php

/**
 * Preview products tab view
 *
 * @author 		Mateus Costa <mateus@costamateus.com.br>
 * @package 	Dropshipping_Shopkeeper\Templates\Admin\Tabs
 * @version 	1.0.0
 */

defined("ABSPATH") || exit;

use \Dropshipping_Shopkeeper\Classes\Tables\DSM_Preview_table;

$pv_table = new DSM_Preview_table();

$import_cat = ( get_option( "_dsm_categories_general", true ) );
$cat_notice = ( $import_cat ) ? __( "Category import is enabled.", "woo_ds_shopkeeper" ) : __( "Category import is <u>not</u> enabled.", "woo_ds_shopkeeper" );
$url_config = admin_url( "admin.php?page=woo_ds_shopkeeper&tab=general" );
$link       = "<a href='{$url_config}' >" . __( "click here", "woo_ds_shopkeeper" ) . "</a>";
$compl      = __( "To change this setting,", "woo_ds_shopkeeper" );
$cat_msg    = "{$compl} {$link}.";
?>

<section>
	<div class="wrap-dsm" id="dsm-users-list-table" >
		<h2 class="wp-heading-inline" style="display:inline;" >
			<?php
				$name  = $pv_table->get_user_login();
				$title = __( "Supplier preview:", "woo_ds_shopkeeper" );

				echo "{$title} {$name}";
			?>
		</h2>

		<p class="notice-cat" ><?php echo $cat_notice; ?></p>
		<p><?php echo $cat_msg; ?></p>

		<form id="dsm-preview_products-list-table-form" method="post" >
			<?php
				$page  = htmlspecialchars( $_POST[ "page" ] );
				$paged = filter_input( INPUT_GET, 'paged', FILTER_SANITIZE_NUMBER_INT );

				printf( "<input type='hidden' name='page'  value='%s' />", $page  );
				printf( "<input type='hidden' name='paged' value='%d' />", $paged );

				$pv_table->prepare_items();
				$pv_table->display();
			?>
		</form>
	</div>
</section>