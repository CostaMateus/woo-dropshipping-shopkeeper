<?php
/**
 * Tags class.
 *
 * @author 		Mateus Costa <mateus@costamateus.com.br>
 * @package 	Dropshipping_Shopkeeper\Classes
 * @version 	1.0.0
 */

namespace Dropshipping_Shopkeeper\Classes;

class DSM_Tags
{
    /**
     * Constructor
     *
     * @since   1.0.0
     * @return  void
     */
    public function __construct()
    {
        if ( is_admin() )
        {
            add_action( "load-post.php",     [ $this, "init_metabox" ], 10, 0 );
            add_action( "load-post-new.php", [ $this, "init_metabox" ], 10, 0 );
        }
    }

    /**
     * Meta box initialization
     *
     * @since   1.0.0
     * @return  void
     */
    public function init_metabox() : void
    {
        add_action( "add_meta_boxes",     [ $this, "add_metabox"      ], 10, 0 );
        add_action( "save_post",          [ $this, "save_metabox"     ], 10, 2 );
        add_action( "post_edit_form_tag", [ $this, "add_form_enctype" ], 10, 0 );
    }

    /**
     * Adds the meta box
     *
     * @since   1.0.0
     * @return  void
     */
    public function add_metabox() : void
    {
        add_meta_box(
            "dsm-tags-suppliers",
            __( "Send tag to supplier", "woo_ds_shopkeepers" ),
            [ $this, "render_metabox" ],
            "shop_order",
            "side",
            "core"
        );
    }

    /**
     * Renders the meta box
     *
     * @since   1.0.0
     * @param   mixed   $post
     * @return  void
     */
    public function render_metabox( $post ) : void
    {
		global $theorder;

		$order     = $theorder;

        if ( !$order->get_meta( "_has_dsm_order" ) ) return;

		$order_id  = $order->get_id();
        $suppliers = [];

        foreach ( $order->get_items() as $item )
        {
            $product     = $item->get_product();
            $supplier_id = $product->get_meta( "_dsm_supplier", true );
            $sent_tag    = $order->get_meta( "_dsm_order_sent_tag_{$order_id}_{$supplier_id}" );
            $s_order_id  = $order->get_meta( "_dsm_order_id_{$supplier_id}", true );

            if ( !$supplier_id                 ) continue;
            if ( $sent_tag                     ) continue;
            if ( isset( $ids[ $supplier_id ] ) ) continue;
            if ( !$s_order_id                  ) continue;

            $supplier    = get_user_by( "id", $supplier_id );

            $suppliers[ $supplier_id ] = $supplier->user_login;
        }

        if ( empty( $suppliers ) )
        {
            ?>
            <ul class="dsm-orders-tags-supplier submitbox" >
                <li class="wide dsm-fw-bold" id="dsm-tags-success" >
                    <p ><?php echo __( "All suppliers of this order have already received their tags.", "woo_ds_shopkeeper" ); ?></p>
                </li>
            </ul>
            <?php
        }
        else
        {
            wp_nonce_field( "dsm_tag_nonce_action", "dsm_tag_nonce" );
            ?>
            <ul class="dsm-orders-tags-supplier submitbox" >
                <li class="wide" id="dsm-suppliers-ids" >
                    <select id="dsm-supplier-id" name="dsm-supplier-id" style="width:100%; margin:0;"  >
                        <option value="" >
                            <?php esc_html_e( "Choose a supplier...", "woo_ds_shopkeeper" ); ?>
                        </option>
                        <?php foreach ( $suppliers as $id => $name ) { ?>
                            <option value="<?php echo $id; ?>" >
                                <?php echo esc_html( $name ); ?>
                            </option>
                        <?php } ?>
                    </select>
                </li>
                <li class="wide" >
                    <span id="dsm-tag-error-type" class="dsm-d-none dsm-text-danger dsm-fw-bold" style="padding-bottom:6px;" >
                        <?php _e( "Only these types are supported:<br>JPG, JPEG, PNG, PDF", "woo_dsm_shopkeeper" );?>
                    </span>
                    <label id="dsm-tag-supplier-label" for="dsm-tag-supplier" style="display:block; width:auto; background-color:#f5f5f5; border:1px solid #ccc; padding:5px; cursor:pointer;" >
                        Select tag file
                    </label>
                    <input id="dsm-tag-supplier" type="file" name="dsm-tag-supplier" class="dsm-d-none" >
                </li>

                <li class="wide" >
                    <button id="dsm-send-tag" type="submit" class="button save-tag-supplier button-primary" name="dsm-send-tag" value="send" >
                        <?php echo esc_html__( "Send", "woo_ds_shopkeeper" ); ?>
                    </button>
                </li>
            </ul>
            <?php
        }
    }

    /**
     * Handles saving the meta box.
     *
     * @since   1.0.0
     * @param   int     $post_id
     * @param   WP_Post $post
     * @return  void
     */
    public function save_metabox( $post_id, $post ) : void
    {
        // Security check
        if ( !$this->check_nonce( $post_id ) ) return;

        $order_id   = $post_id;
        $order      = wc_get_order( $order_id );

        $supp_types = [
            "application/pdf",
            "image/jpeg",
            "image/png",
        ];

        if ( !empty( $_FILES[ "dsm-tag-supplier" ] ) )
        {
            $file_tag      = $_FILES[ "dsm-tag-supplier" ];
            $arr_file_type = wp_check_filetype( basename( $file_tag[ "name" ] ) );
            $uploaded_type = $arr_file_type[ "type" ];

            if ( in_array( $uploaded_type, $supp_types ) )
            {
                $upload_dir = wp_upload_dir();

                if ( !empty( $upload_dir[ "basedir" ] ) )
                {
                    $supplier_id = $_POST[ "dsm-supplier-id" ];
                    $dirname     = $upload_dir[ "basedir" ] . "/dsm_tags";

                    if ( !file_exists( $dirname ) ) wp_mkdir_p( $dirname );

                    $ext         = pathinfo( $file_tag[ "name" ], PATHINFO_EXTENSION );
                    $name        = "dsm_tag_{$order_id}_{$supplier_id}.{$ext}";
                    $filename    = wp_unique_filename( $dirname, $name );
                    $path        = "{$dirname}/{$filename}";
                    $upd         = move_uploaded_file( $file_tag[ "tmp_name" ], $path );

                    if ( !$upd ) wp_die( "There was an error uploading your file." );

                    $s_order_id  = $order->get_meta( "_dsm_order_id_{$supplier_id}", true );
                    $response    = $this->send_tag_to_supplier( $supplier_id, $path, $s_order_id, $order );

                    if ( !$response[ "success" ] )
                        $order->add_order_note( $response[ "message" ] );
                    else
                        $order->add_meta_data( "_dsm_order_sent_tag_{$order_id}_{$supplier_id}", true );

                    $order->save();
                }
            }
        }
    }

    /**
     * Add to form "enctype=multipart/form-data"
     *
     * @since   1.0.0
     * @return  void
     */
    public function add_form_enctype() : void
    {
        echo " enctype='multipart/form-data'";
    }

    /**
     * Check teh nonce
     *
     * @since   1.0.0
     * @param   int     $post_id
     * @return  bool
     */
    private function check_nonce( $post_id ) : bool
    {
        // Add nonce for security and authentication.
        $nonce_name   = isset( $_POST[ "dsm_tag_nonce" ] ) ? $_POST[ "dsm_tag_nonce" ] : "";
        $nonce_action = "dsm_tag_nonce_action";

        // Check if nonce is valid.
        if ( !wp_verify_nonce( $nonce_name, $nonce_action ) ) return false;

        // Check if user has permissions to save data.
        if ( !current_user_can( "edit_post", $post_id )     ) return false;

        // Check if not an autosave.
        if ( wp_is_post_autosave( $post_id )                ) return false;

        // Check if not a revision.
        if ( wp_is_post_revision( $post_id )                ) return false;

        return true;
    }

    /**
     * Call API to send tag to supplier
     *
     * @since   1.0.0
     * @param   int     $supplier_id
     * @param   string  $path
     * @param   int     $order_id
     * @param   object  $order
     * @return  array
     */
    private function send_tag_to_supplier( $supplier_id, $path, $order_id, $order )
    {
        $user     = get_user_by( "id", $supplier_id );
        $url      = get_user_meta( $user->ID, "_dsm_url",      true );
        $secret   = get_user_meta( $user->ID, "_dsm_secret",   true );
        $secret_s = get_user_meta( $user->ID, "_dsm_secret_s", true );

        if ( !$url || !$secret || !$secret_s )
        {
            return [
                "success" => false,
                "message" => __( "Authentication with supplier failed, ID: {$supplier_id}", "woo_ds_shopkeeper" )
            ];
        }

        $email    = get_option( "_dsm_email_general" );
        $email    = ( !empty( $email ) && is_email( $email ) ) ? $email : get_bloginfo( "admin_email" );

        $args     = [
            "timeout"     => 60,
            "sslverify"   => false,
            "data_format" => "body",
            "headers"     => [
                "_dsm_email"      => $email,
                "_dsm_supplier"   => $secret_s,
                "_dsm_shopkeeper" => $secret,
                "Content-Type"    => "application/json",
            ],
            "body"        => json_encode( [
                "order_id"    => $order_id,
                "tag_name"    => basename( $path ),
                "tag_content" => base64_encode( file_get_contents( $path ) ),
            ] )
        ];

        $url      = "{$url}/wp-json/dsm/v1/tags";

        // Post tag to supplier
        $response = wp_remote_post( $url, $args );

        if ( is_wp_error( $response ) )
        {
            return [
                "success" => false,
                "message" => $response->get_error_message()
            ];
        }

        $data     = json_decode( $response[ "body" ], true );

        return $data;
    }
}
