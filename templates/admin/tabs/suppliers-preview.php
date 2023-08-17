<?php

/**
 * Suppliers tab view
 *
 * @author 		Mateus Costa <mateus@costamateus.com.br>
 * @package 	Dropshipping_Shopkeeper\Templates\Admin\Tabs
 * @version 	1.0.0
 */

defined("ABSPATH") || exit;

use Dropshipping_Shopkeeper\Classes\Tables\DSM_Products_table;

$pv_table = new DSM_Products_table();
?>

<section>
	<div class="wrap-dsm" id="dsm-users-list-table" >
		<h2 class="wp-heading-inline" style="display:inline;" >
			<?php
				$name  = $pv_table->get_user()->user_login;
				$title = __( "Supplier", "woo_ds_shopkeeper" );

				echo "{$title} - {$name}";
			?>
		</h2>
		<h3>
			<?php _e( "Preview", "woo_ds_shopkeeper" ); ?>
		</h3>

		<form id="dsm-users-list-table-form" method="post" >
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

	<!-- <div id="dsm-modal-load" class="woocommerce dsm-modal" >
		<div class="dsm-bg-modal-content" >
			<table class="wp-list-table widefat fixed striped table-view-list toplevel_page_woo_ds_shopkeeper" >
				<thead>
					<tr>
						<td id="cb" class="manage-column column-cb check-column" ></td>
						<th scope="col" id="username" class="manage-column column-username column-primary sortable desc" ><a href="http://localhost:9000/wp-admin/admin.php?page=woo_ds_shopkeeper&amp;tab=suppliers&amp;orderby=username&amp;order=asc" ><span>Username</span><span class="sorting-indicators" ><span class="sorting-indicator asc" aria-hidden="true" ></span><span class="sorting-indicator desc" aria-hidden="true" ></span></span> <span class="screen-reader-text" >Sort ascending.</span></a></th>
						<th scope="col" id="name" class="manage-column column-name" >Name</th>
						<th scope="col" id="prefix" class="manage-column column-prefix" >SKU Prefix</th>
						<th scope="col" id="email" class="manage-column column-email sortable desc" ><a href="http://localhost:9000/wp-admin/admin.php?page=woo_ds_shopkeeper&amp;tab=suppliers&amp;orderby=email&amp;order=asc" ><span>Email</span><span class="sorting-indicators" ><span class="sorting-indicator asc" aria-hidden="true" ></span><span class="sorting-indicator desc" aria-hidden="true" ></span></span> <span class="screen-reader-text" >Sort ascending.</span></a></th>
						<th scope="col" id="products" class="manage-column column-products sortable desc" ><a href="http://localhost:9000/wp-admin/admin.php?page=woo_ds_shopkeeper&amp;tab=suppliers&amp;orderby=products&amp;order=asc" ><span>Products</span><span class="sorting-indicators" ><span class="sorting-indicator asc" aria-hidden="true" ></span><span class="sorting-indicator desc" aria-hidden="true" ></span></span> <span class="screen-reader-text" >Sort ascending.</span></a></th>
						<th scope="col" id="keys" class="manage-column column-keys" >Keys</th>
					</tr>
				</thead>

				<tbody id="the-list" >
					<tr>
						<th scope="row" class="check-column" ><input type="checkbox" name="users[]" value="171" ></th>
						<td class="username column-username has-row-actions column-primary" data-colname="Username" ><strong><a href="http://localhost:9000/wp-admin/user-edit.php?user_id=171&amp;wp_http_referer=%2Fwp-admin%2Fadmin.php%3Fpage%3Dwoo_ds_shopkeeper%26tab%3Dsuppliers" >fonercedor1</a></strong>
							<div class="row-actions" ><span class="id" >ID: 171 | </span><span class="edit" ><a href="http://localhost:9000/wp-admin/user-edit.php?user_id=171&amp;wp_http_referer=%2Fwp-admin%2Fadmin.php%3Fpage%3Dwoo_ds_shopkeeper%26tab%3Dsuppliers" >Edit</a></span></div><button type="button" class="toggle-row" ><span class="screen-reader-text" >Show more details</span></button><button type="button" class="toggle-row" ><span class="screen-reader-text" >Show more details</span></button>
						</td>
						<td class="name column-name" data-colname="Name" >Supplier 1 </td>
						<td class="prefix column-prefix" data-colname="SKU Prefix" >f1-</td>
						<td class="email column-email" data-colname="Email" >fornecedor@exemplo.com</td>
						<td class="products column-products" data-colname="Products" ><a href="http://localhost:9000/wp-admin/admin.php?page=woo_ds_shopkeeper&amp;tab=products&amp;supplier=171" >15</a></td>
						<td class="keys column-keys" data-colname="Keys" ><mark class="order-status status-processing" ><span>Keys setted</span></mark></td>
					</tr>
					<tr>
						<th scope="row" class="check-column" ><input type="checkbox" name="users[]" value="172" ></th>
						<td class="username column-username has-row-actions column-primary" data-colname="Username" ><strong><a href="http://localhost:9000/wp-admin/user-edit.php?user_id=172&amp;wp_http_referer=%2Fwp-admin%2Fadmin.php%3Fpage%3Dwoo_ds_shopkeeper%26tab%3Dsuppliers" >supplier2</a></strong>
							<div class="row-actions" ><span class="id" >ID: 172 | </span><span class="edit" ><a href="http://localhost:9000/wp-admin/user-edit.php?user_id=172&amp;wp_http_referer=%2Fwp-admin%2Fadmin.php%3Fpage%3Dwoo_ds_shopkeeper%26tab%3Dsuppliers" >Edit</a></span></div><button type="button" class="toggle-row" ><span class="screen-reader-text" >Show more details</span></button><button type="button" class="toggle-row" ><span class="screen-reader-text" >Show more details</span></button>
						</td>
						<td class="name column-name" data-colname="Name" >supplier 2</td>
						<td class="prefix column-prefix" data-colname="SKU Prefix" >f2-</td>
						<td class="email column-email" data-colname="Email" >supplier2@example.com</td>
						<td class="products column-products" data-colname="Products" >0</td>
						<td class="keys column-keys" data-colname="Keys" ><mark class="order-status status-processing" ><span>Keys setted</span></mark></td>
					</tr>
				</tbody>

				<tfoot>
					<tr>
						<td class="manage-column column-cb check-column" ></td>
						<th scope="col" class="manage-column column-username column-primary sortable desc" ><a href="http://localhost:9000/wp-admin/admin.php?page=woo_ds_shopkeeper&amp;tab=suppliers&amp;orderby=username&amp;order=asc" ><span>Username</span><span class="sorting-indicators" ><span class="sorting-indicator asc" aria-hidden="true" ></span><span class="sorting-indicator desc" aria-hidden="true" ></span></span> <span class="screen-reader-text" >Sort ascending.</span></a></th>
						<th scope="col" class="manage-column column-name" >Name</th>
						<th scope="col" class="manage-column column-prefix" >SKU Prefix</th>
						<th scope="col" class="manage-column column-email sortable desc" ><a href="http://localhost:9000/wp-admin/admin.php?page=woo_ds_shopkeeper&amp;tab=suppliers&amp;orderby=email&amp;order=asc" ><span>Email</span><span class="sorting-indicators" ><span class="sorting-indicator asc" aria-hidden="true" ></span><span class="sorting-indicator desc" aria-hidden="true" ></span></span> <span class="screen-reader-text" >Sort ascending.</span></a></th>
						<th scope="col" class="manage-column column-products sortable desc" ><a href="http://localhost:9000/wp-admin/admin.php?page=woo_ds_shopkeeper&amp;tab=suppliers&amp;orderby=products&amp;order=asc" ><span>Products</span><span class="sorting-indicators" ><span class="sorting-indicator asc" aria-hidden="true" ></span><span class="sorting-indicator desc" aria-hidden="true" ></span></span> <span class="screen-reader-text" >Sort ascending.</span></a></th>
						<th scope="col" class="manage-column column-keys" >Keys</th>
					</tr>
				</tfoot>
			</table>

		</div>
	</div> -->

</section>