<?php

/**
 * Provide a admin area view for the plugin
 * This file is used to markup the admin-facing aspects of the plugin.
 *
 * @author 		Mateus Costa <mateus@costamateus.com.br>
 * @package 	Dropshipping_Shopkeeper\Templates\Admin
 * @version 	1.0.0
 */

defined( "ABSPATH" ) || exit;

// Modulo Supplier
// Id spk | Email spk | URL spk | URL supplier

// Modulo Shopkeeper
// ID supplier | Email supplier | URL supplier | URL skp

?>

<h3><?php _e( "DSM Settings", "woo_ds_shopkeeper" ); ?></h3>

<table class="form-table" >
    <tr>
        <th>
            <label for="woo_ds_sku_prefix" ><?php _e( "SKU Prefix", "woo_ds_shopkeeper" ); ?></label>
        </th>
        <td>
            <input type="text" name="woo_ds_sku_prefix" id="woo_ds_sku_prefix" value="<?php echo esc_attr( get_the_author_meta( "_dsm_sku_prefix", $user->ID ) ); ?>" class="regular-text" />
            <br>
            <p class="description" ><?php _e( "Enter a prefix to add to the SKU of that supplier's products for better identification. Leave it blank to not add any prefixes.", "woo_ds_shopkeeper" ); ?></p>
            <p class="description" ><?php _e( "Example, prefix set 'sku-', supplier SKU 'ABC123', final SKU: 'sku-ABC123'", "woo_ds_shopkeeper" ); ?></p>
        </td>
    </tr>
    <tr>
        <th>
            <label for="woo_ds_website" ><?php _e( "Website", "woo_ds_shopkeeper" ); ?></label>
        </th>
        <td>
            <input type="text" name="woo_ds_website" id="woo_ds_website" value="<?php echo esc_attr( get_the_author_meta( "_dsm_url", $user->ID ) ); ?>" class="regular-text" />
            <br>
            <p class="description" ><?php _e( "Enter the full URL of the supplier's website.", "woo_ds_shopkeeper" ); ?></p>
        </td>
    </tr>
    <tr>
        <th><label for="woo_ds_external"><?php _e( "Secret key from supplier", "woo_ds_shopkeeper" ); ?></label></th>
        <td>
            <input type="password" name="woo_ds_external" id="woo_ds_external" value="<?php echo esc_attr( get_the_author_meta( "_dsm_secret_s", $user->ID ) ); ?>" class="regular-text" />
            <br>
            <p class="description" ><?php _e( "Enter the secret key provided by the supplier.", "woo_ds_shopkeeper" ); ?></p>
        </td>
    </tr>
    <tr>
        <th><label for="woo_ds_key" ><?php _e( "Secret key" ); ?></label></th>
        <td>
            <button id="set_new_key" type="button" class="button" aria-expanded="true" >
                <?php _e( "Set new secret key", "woo_ds_shopkeeper" ); ?>
                <span class="dashicons dashicons-update spin" ></span>
            </button>
            <br>
            <p class="description" ><?php _e( "Make sure to copy and sent to the supplier new secret key now as will be hidden once you leave this page.", "woo_ds_shopkeeper" ); ?></p>
            <p id="set_key_error" class="description" ></p>

            <div id="new_key" style="padding-top:10px; display:none;" >
                <span class="password-input-wrapper" >
                    <input type="text" name="woo_ds_new_key" id="woo_ds_new_key" class="regular-text empty strong" value="" spellcheck="false" >
                </span>
                <button id="woo_ds_copy" type="button" class="button" data-toggle="0" aria-label="Hide password" >
                    <span class="text" ><?php _e( "Copy", "woo_ds_shopkeeper" ); ?></span>
                </button>
            </div>
            <div id="key_copied" style="display:none;" >
                <p class="description" ><?php _e( "Secret key copied to clipboard.", "woo_ds_shopkeeper" ); ?></p>
            </div>

            <?php if ( get_the_author_meta( "_dsm_secret", $user->ID ) ) : ?>
                <p id="key_set" class="description" ><?php _e( "This user already have a secret key set.", "woo_ds_shopkeeper" ); ?></p>
            <?php endif; ?>
        </td>
    </tr>
</table>