<?php
/**
 * Suppliers view
 *
 * @author 		Mateus Costa <mateus@costamateus.com.br>
 * @package 	Dropshipping_Shopkeeper\Templates\Admin\Tabs
 * @version 	1.0.0
 */

defined( "ABSPATH" ) || exit;

$url = esc_url( admin_url( "users.php?role=supplier" ) );
?>

<section>
	<h2><?php _e( "List of suppliers", "woo_ds_shopkeeper" ); ?></h2>

	<p><a href="<?php echo $url; ?>" ><?php _e( "Click here", "woo_ds_shopkeeper" ); ?></a></p>
</section>