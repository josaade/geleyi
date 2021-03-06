<h2><?php _e( 'Settings', 'wc_product_vendor' ); ?></h2>

<?php if ( function_exists( 'wc_print_messages' ) ) wc_print_messages(); else {
	global $woocommerce;
	$woocommerce->show_messages();
} ?>

<form method="post">
	<?php

	do_action( 'wc_product_vendor_settings_before_paypal' );

	if ( $paypal_address !== 'false' ) {
		woocommerce_get_template( 'paypal-email-form.php', array(
																'user_id' => $user_id,
														   ), 'wc-product-vendor/dashboard/settings/', pv_plugin_dir . 'views/dashboard/settings/' );
	}

	do_action( 'wc_product_vendor_settings_after_paypal' );

	woocommerce_get_template( 'shop-name.php', array(
													'user_id' => $user_id,
											   ), 'wc-product-vendor/dashboard/settings/', pv_plugin_dir . 'views/dashboard/settings/' );

	do_action( 'wc_product_vendor_settings_after_shop_name' );

	woocommerce_get_template( 'seller-info.php', array(
													  'global_html' => $global_html,
													  'has_html'    => $has_html,
													  'seller_info' => $seller_info,
												 ), 'wc-product-vendor/dashboard/settings/', pv_plugin_dir . 'views/dashboard/settings/' );

	do_action( 'wc_product_vendor_settings_after_seller_info' );

	if ( $shop_description !== 'false' ) {
		woocommerce_get_template( 'shop-description.php', array(
															   'description' => $description,
															   'global_html' => $global_html,
															   'has_html'    => $has_html,
															   'shop_page'   => $shop_page,
															   'user_id'     => $user_id,
														  ), 'wc-product-vendor/dashboard/settings/', pv_plugin_dir . 'views/dashboard/settings/' );

		do_action( 'wc_product_vendor_settings_after_shop_description' );
	}
	?>

	<?php wp_nonce_field( 'save-shop-settings', 'wc-product-vendor-nonce' ); ?>
	<input type="submit" class="btn btn-inverse btn-small" style="float:none;" name="vendor_application_submit"
		   value="<?php _e( 'Save', 'wc_product_vendor' ); ?>"/>
</form>
