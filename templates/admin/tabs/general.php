<?php
/**
 * General tab view
 *
 * @author 		Mateus Costa <mateus@costamateus.com.br>
 * @package 	Dropshipping_Shopkeeper\Templates\Admin\Tabs
 * @version 	1.0.0
 */

defined( "ABSPATH" ) || exit;

?>

<section>
	<div class="wrap-dsm" id="" >
		<h2 class="wp-heading-inline" style="display:inline;" >
			<?php _e( "General", "woo_ds_shopkeeper" ); ?>
		</h2>

		<form id="dsm-general-form" class="dsm-form" method="post" action="<?php echo admin_url( "admin-post.php" ); ?>" >
			<input type="hidden" name="action" value="dsm_save_general" />
			<?php wp_nonce_field( "dsm_save_general" ); ?>

			<table class="form-table" >
				<tbody>
					<tr valign="top" >
						<th scope="row" class="titledesc" >
							<label for="">
								<?php _e( "E-mail account", "woo_ds_shopkeeper" ); ?>
							</label>
						</th>
						<td class="forminp" >
							<input class="input-text regular-input" type="email" name="dsm_email_general" value="<?php echo $email_general; ?>" >
							<p class="description" ><?php _e( "The supplier will create an account with this email. And it will be used to communicate with the supplier. If you leave it blank, the WP default will be used, inform the suppplier.", "woo_ds_shopkeeper" ); ?></p>
							<p class="description" ><?php echo __( "Default: ", "woo_ds_shopkeeper" ) . get_bloginfo( "admin_email" ); ?></p>
						</td>
					</tr>
					<!-- <tr valign="top" style="display:none;" >
						<th scope="row" class="titledesc" >
							<label for="">
								<?php _e( "", "woo_ds_shopkeeper" ); ?>
							</label>
						</th>
						<td class="forminp" >
							<input class="input-text regular-input" type="email" >
							<p class="description" ><?php _e( "", "woo_ds_shopkeeper" ); ?></p>
						</td>
					</tr> -->
				</tbody>
			</table>

			<p class="submit" style="clear: both;" >
				<input type="submit" class="button-primary" value="<?php _e( "Update settings", "woo_ds_shopkeeper" );?>" ></input>
			</p>
		</form>
	</div>
</section>
