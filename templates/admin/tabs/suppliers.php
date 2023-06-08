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
	<div class="wrap" id="dsm-users-list-table" >
		<h2><?php _e( "Suppliers", "woo_ds_shopkeeper" ); ?></h2>
		<form id="dsm-users-list-table-form" method="post" >
			<?php
				$page  = filter_input( INPUT_GET, 'page',  FILTER_SANITIZE_STRIPPED   );
				$paged = filter_input( INPUT_GET, 'paged', FILTER_SANITIZE_NUMBER_INT );

				printf( "<input type='hidden' name='page'  value='%s' />", $page  );
				printf( "<input type='hidden' name='paged' value='%d' />", $paged );

				$users_table->prepare_items();
				$users_table->display();
			?>
		</form>
	</div>
</section>