<?php
/**
 * Order Data
 *
 * Functions for displaying the order data meta box.
 *
 * @author 		WooThemes
 * @category 	Admin
 * @package 	WooCommerce/Admin/WritePanels
 * @version     1.7.0
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Displays the order data meta box.
 *
 * @access public
 * @param mixed $post
 * @return void
 */
function woocommerce_order_data_meta_box($post) {
	global $post, $wpdb, $thepostid, $theorder, $order_status, $woocommerce;

	$thepostid = absint( $post->ID );
	
	if ( ! is_object( $theorder ) )
		$theorder = new WC_Order( $thepostid );

	$order = $theorder;

	wp_nonce_field( 'woocommerce_save_data', 'woocommerce_meta_nonce' );

	// Custom user
	$customer_user = absint( get_post_meta( $post->ID, '_customer_user', true ) );

	// Order status
	$order_status = wp_get_post_terms( $post->ID, 'shop_order_status' );
	if ( $order_status ) {
		$order_status = current( $order_status );
		$order_status = sanitize_title( $order_status->slug );
	} else {
		$order_status = sanitize_title( apply_filters( 'woocommerce_default_order_status', 'pending' ) );
	}

	if ( empty( $post->post_title ) )
		$order_title = 'Order';
	else
		$order_title = $post->post_title;
	?>
	<style type="text/css">
		#titlediv, #major-publishing-actions, #minor-publishing-actions, #visibility, #submitdiv { display:none }
	</style>
	<div class="panel-wrap woocommerce">
		<input name="post_title" type="hidden" value="<?php echo esc_attr( $order_title ); ?>" />
		<input name="post_status" type="hidden" value="publish" />
		<div id="order_data" class="panel">

			<div class="order_data_left">

				<h2><?php _e( 'Order Details', 'woocommerce' ); ?> &mdash; <?php echo esc_html( $order->get_order_number() ); ?></h2>

				<p class="form-field"><label for="order_status"><?php _e( 'Order status:', 'woocommerce' ) ?></label>
				<select id="order_status" name="order_status" class="chosen_select">
					<?php
						$statuses = (array) get_terms( 'shop_order_status', array( 'hide_empty' => 0, 'orderby' => 'id' ) );
						foreach ( $statuses as $status ) {
							echo '<option value="' . esc_attr( $status->slug ) . '" ' . selected( $status->slug, $order_status, false ) . '>' . esc_html__( $status->name, 'woocommerce' ) . '</option>';
						}
					?>
				</select></p>

				<p class="form-field last"><label for="order_date"><?php _e( 'Order Date:', 'woocommerce' ) ?></label>
					<input type="text" class="date-picker-field" name="order_date" id="order_date" maxlength="10" value="<?php echo date_i18n( 'Y-m-d', strtotime( $post->post_date ) ); ?>" /> @ <input type="text" class="hour" placeholder="<?php _e( 'h', 'woocommerce' ) ?>" name="order_date_hour" id="order_date_hour" maxlength="2" size="2" value="<?php echo date_i18n( 'H', strtotime( $post->post_date ) ); ?>" />:<input type="text" class="minute" placeholder="<?php _e( 'm', 'woocommerce' ) ?>" name="order_date_minute" id="order_date_minute" maxlength="2" size="2" value="<?php echo date_i18n( 'i', strtotime( $post->post_date ) ); ?>" />
				</p>

				<p class="form-field form-field-wide">
					<label for="customer_user"><?php _e( 'Customer:', 'woocommerce' ) ?></label>
					<select id="customer_user" name="customer_user" class="ajax_chosen_select_customer">
						<option value=""><?php _e( 'Guest', 'woocommerce' ) ?></option>
						<?php
							if ( $customer_user ) {
								$user = get_user_by( 'id', $customer_user );
								echo '<option value="' . esc_attr( $user->ID ) . '" ' . selected( 1, 1, false ) . '>' . esc_html( $user->display_name ) . ' (#' . absint( $user->ID ) . ' &ndash; ' . esc_html( $user->user_email ) . ')</option>';
							}
						?>
					</select>
					<?php

					// Ajax Chosen Customer Selectors JS
					$woocommerce->add_inline_js( "
						jQuery('select.ajax_chosen_select_customer').ajaxChosen({
						    method: 		'GET',
						    url: 			'" . admin_url('admin-ajax.php') . "',
						    dataType: 		'json',
						    afterTypeDelay: 100,
						    minTermLength: 	1,
						    data:		{
						    	action: 	'woocommerce_json_search_customers',
								security: 	'" . wp_create_nonce("search-customers") . "'
						    }
						}, function (data) {

							var terms = {};

						    $.each(data, function (i, val) {
						        terms[i] = val;
						    });

						    return terms;
						});
					" );

					?>
				</p>

				<?php if( get_option( 'woocommerce_enable_order_comments' ) != 'no' ) : ?>
					<p class="form-field form-field-wide"><label for="excerpt"><?php _e( 'Customer Note:', 'woocommerce' ) ?></label>
					<textarea rows="1" cols="40" name="excerpt" tabindex="6" id="excerpt" placeholder="<?php _e( 'Customer\'s notes about the order', 'woocommerce' ); ?>"><?php echo wp_kses_post( $post->post_excerpt ); ?></textarea></p>
				<?php endif; ?>

				<?php do_action( 'woocommerce_admin_order_data_after_order_details', $order ); ?>

			</div>
			<div class="order_data_right">
				<div class="order_data">
					<h2><?php _e( 'Billing Details', 'woocommerce' ); ?> <a class="edit_address" href="#">(<?php _e( 'Edit', 'woocommerce' ) ;?>)</a></h2>
					<?php
						$billing_data = apply_filters('woocommerce_admin_billing_fields', array(
							'first_name' => array(
								'label' => __( 'First Name', 'woocommerce' ),
								'show'	=> false
								),
							'last_name' => array(
								'label' => __( 'Last Name', 'woocommerce' ),
								'show'	=> false
								),
							'company' => array(
								'label' => __( 'Company', 'woocommerce' ),
								'show'	=> false
								),
							'address_1' => array(
								'label' => __( 'Address 1', 'woocommerce' ),
								'show'	=> false
								),
							'address_2' => array(
								'label' => __( 'Address 2', 'woocommerce' ),
								'show'	=> false
								),
							'city' => array(
								'label' => __( 'City', 'woocommerce' ),
								'show'	=> false
								),
							'postcode' => array(
								'label' => __( 'Postcode', 'woocommerce' ),
								'show'	=> false
								),
							'country' => array(
								'label' => __( 'Country', 'woocommerce' ),
								'show'	=> false,
								'type'	=> 'select',
								'options' => array( '' => __( 'Select a country&hellip;', 'woocommerce' ) ) + $woocommerce->countries->get_allowed_countries()
								),
							'state' => array(
								'label' => __( 'State/County', 'woocommerce' ),
								'show'	=> false
								),
							'email' => array(
								'label' => __( 'Email', 'woocommerce' ),
								),
							'phone' => array(
								'label' => __( 'Phone', 'woocommerce' ),
								),
							) );

						// Display values
						echo '<div class="address">';

							if ( $order->get_formatted_billing_address() ) 
								echo '<p><strong>' . __( 'Address', 'woocommerce' ) . ':</strong><br/> ' . $order->get_formatted_billing_address() . '</p>'; 
							else 
								echo '<p class="none_set"><strong>' . __( 'Address', 'woocommerce' ) . ':</strong> ' . __( 'No billing address set.', 'woocommerce' ) . '</p>';

							foreach ( $billing_data as $key => $field ) { 
								if ( empty( $field['show'] ) ) 
									continue;
								$field_name = 'billing_' . $key;
								if ( $order->$field_name ) 
									echo '<p><strong>' . esc_html( $field['label'] ) . ':</strong> ' . esc_html( $order->$field_name ) . '</p>';
							}

						echo '</div>';

						// Display form
						echo '<div class="edit_address"><p><button class="button load_customer_billing">'.__( 'Load billing address', 'woocommerce' ).'</button></p>';

						foreach ( $billing_data as $key => $field ) {
							if ( ! isset( $field['type'] ) ) 
								$field['type'] = 'text';
							switch ( $field['type'] ) {
								case "select" :
									woocommerce_wp_select( array( 'id' => '_billing_' . $key, 'label' => $field['label'], 'options' => $field['options'] ) );
								break;
								default :
									woocommerce_wp_text_input( array( 'id' => '_billing_' . $key, 'label' => $field['label'] ) );
								break;
							}
						}

						echo '</div>';

						do_action( 'woocommerce_admin_order_data_after_billing_address', $order );
					?>
				</div>
				<div class="order_data order_data_alt">

					<h2><?php _e( 'Shipping Details', 'woocommerce' ); ?> <a class="edit_address" href="#">(<?php _e( 'Edit', 'woocommerce' ) ;?>)</a></h2>
					<?php
						$shipping_data = apply_filters('woocommerce_admin_shipping_fields', array(
							'first_name' => array(
								'label' => __( 'First Name', 'woocommerce' ),
								'show'	=> false
								),
							'last_name' => array(
								'label' => __( 'Last Name', 'woocommerce' ),
								'show'	=> false
								),
							'company' => array(
								'label' => __( 'Company', 'woocommerce' ),
								'show'	=> false
								),
							'address_1' => array(
								'label' => __( 'Address 1', 'woocommerce' ),
								'show'	=> false
								),
							'address_2' => array(
								'label' => __( 'Address 2', 'woocommerce' ),
								'show'	=> false
								),
							'city' => array(
								'label' => __( 'City', 'woocommerce' ),
								'show'	=> false
								),
							'postcode' => array(
								'label' => __( 'Postcode', 'woocommerce' ),
								'show'	=> false
								),
							'country' => array(
								'label' => __( 'Country', 'woocommerce' ),
								'show'	=> false,
								'type'	=> 'select',
								'options' => array( '' => __( 'Select a country&hellip;', 'woocommerce' ) ) + $woocommerce->countries->get_allowed_countries()
								),
							'state' => array(
								'label' => __( 'State/County', 'woocommerce' ),
								'show'	=> false
								),
							) );

						// Display values
						echo '<div class="address">';

							if ( $order->get_formatted_shipping_address() ) 
								echo '<p><strong>' . __( 'Address', 'woocommerce' ) . ':</strong><br/> ' . $order->get_formatted_shipping_address() . '</p>'; 
							else 
								echo '<p class="none_set"><strong>' . __( 'Address', 'woocommerce' ) . ':</strong> ' . __( 'No shipping address set.', 'woocommerce' ) . '</p>';

							if ( $shipping_data ) foreach ( $shipping_data as $key => $field ) { 
								if ( empty( $field['show'] ) ) 
									continue;
								$field_name = 'shipping_' . $key;
								if ( $order->$field_name ) 
									echo '<p><strong>' . esc_html( $field['label'] ) . ':</strong> ' . esc_html( $order->$field_name ) . '</p>';
							}

						echo '</div>';

						// Display form
						echo '<div class="edit_address"><p><button class="button load_customer_shipping">' . __( 'Load shipping address', 'woocommerce' ) . '</button> <button class="button billing-same-as-shipping">'. __( 'Copy from billing', 'woocommerce' ) . '</button></p>';

						if ( $shipping_data ) foreach ( $shipping_data as $key => $field ) {
							if ( ! isset( $field['type'] ) ) 
								$field['type'] = 'text';
							switch ( $field['type'] ) {
								case "select" :
									woocommerce_wp_select( array( 'id' => '_shipping_' . $key, 'label' => $field['label'], 'options' => $field['options'] ) );
								break;
								default :
									woocommerce_wp_text_input( array( 'id' => '_shipping_' . $key, 'label' => $field['label'] ) );
								break;
							}
						}

						echo '</div>';

						do_action( 'woocommerce_admin_order_data_after_shipping_address', $order );
					?>
				</div>
			</div>
			<div class="clear"></div>
		</div>
	</div>
	<?php
}

/**
 * Order items meta box.
 *
 * Displays the order items meta box - for showing individual items in the order.
 */
function woocommerce_order_items_meta_box( $post ) {
	global $wpdb, $thepostid, $theorder, $woocommerce;

	if ( ! is_object( $theorder ) )
		$theorder = new WC_Order( $thepostid );

	$order = $theorder;
	
	$order_items = $order->get_items();
	?>
	<div class="woocommerce_order_items_wrapper">
		<table cellpadding="0" cellspacing="0" class="woocommerce_order_items">
			<thead>
				<tr>
					<th class="thumb" width="1%"><?php _e( 'Item', 'woocommerce' ); ?></th>
					<th class="sku"><?php _e( 'SKU', 'woocommerce' ); ?></th>
					<th class="name"><?php _e( 'Name', 'woocommerce' ); ?></th>
					
					<?php do_action( 'woocommerce_admin_order_item_headers' ); ?>

					<th class="tax_class"><?php _e( 'Tax Class', 'woocommerce' ); ?>&nbsp;<a class="tips" data-tip="<?php _e( 'Tax class for the line item', 'woocommerce' ); ?>." href="#">[?]</a></th>

					<th class="quantity"><?php _e( 'Qty', 'woocommerce' ); ?></th>

					<th class="line_subtotal"><?php _e( 'Line&nbsp;Subtotal', 'woocommerce' ); ?>&nbsp;<a class="tips" data-tip="<?php _e( 'Line cost and line tax before pre-tax discounts', 'woocommerce' ); ?>" href="#">[?]</a></th>

					<th class="line_total"><?php _e( 'Line&nbsp;Total', 'woocommerce' ); ?>&nbsp;<a class="tips" data-tip="<?php _e( 'Line cost and line tax after pre-tax discounts', 'woocommerce' ); ?>" href="#">[?]</a></th>
				</tr>
			</thead>
			<tbody id="order_items_list">

				<?php if ( ! empty( $order_items ) ) foreach ( $order_items as $item_id => $item ) {

					if ( ! empty( $item['variation_id'] ) )
						$_product = new WC_Product_Variation( $item['variation_id'] );
					else
						$_product = new WC_Product( $item['product_id'] );
					
					$item_meta = $order->get_item_meta( $item_id );
					
					include( 'order-item-html.php' );
				} 	
				?>
			</tbody>
		</table>
	</div>

	<p class="buttons">
		<select id="add_item_id" class="ajax_chosen_select_products_and_variations" multiple="multiple" data-placeholder="<?php _e( 'Search for a product&hellip;', 'woocommerce' ); ?>" style="width: 400px"></select>

		<button type="button" class="button add_order_item"><?php _e( 'Add item(s)', 'woocommerce' ); ?></button>
	</p>
	<p class="buttons buttons-alt">
		<button type="button" class="button calc_line_taxes"><?php _e( 'Calc line tax &uarr;', 'woocommerce' ); ?></button>
		<button type="button" class="button calc_totals"><?php _e( 'Calc totals &rarr;', 'woocommerce' ); ?></button>
	</p>
	<div class="clear"></div>
	<?php
}


/**
 * Display the order actions meta box.
 *
 * Displays the order actions meta box - buttons for managing order stock and sending the customer an invoice.
 *
 * @access public
 * @param mixed $post
 * @return void
 */
function woocommerce_order_actions_meta_box($post) {
	?>
	<ul class="order_actions submitbox">

		<li><input type="submit" class="button tips" name="reduce_stock" value="<?php _e( 'Reduce stock', 'woocommerce' ); ?>" data-tip="<?php _e( 'Reduces stock for each item in the order; useful after manually creating an order or manually marking an order as paid.', 'woocommerce' ); ?>" /></li>

		<li><input type="submit" class="button tips" name="restore_stock" value="<?php _e( 'Restore stock', 'woocommerce' ); ?>" data-tip="<?php _e( 'Restores stock for each item in the order; useful after refunding or canceling the entire order.', 'woocommerce' ); ?>" /></li>
		
		<?php do_action( 'woocommerce_order_actions', $post->ID ); ?>

		<li class="wide" id="order-emails">
			<a href="#order-emails" class="show-order-emails hide-if-no-js tips" data-tip="<?php _e( 'Lets you send or resend order emails to the admin or customer.', 'woocommerce' ); ?>"><?php _e( 'Show order emails', 'woocommerce' ); ?></a>

			<div id="order-emails-select" class="hide-if-js">
				<?php
				global $woocommerce;
				$mailer = $woocommerce->mailer();

				$available_emails = apply_filters( 'woocommerce_resend_order_emails_available', array( 'new_order', 'customer_processing_order', 'customer_completed_order', 'customer_invoice' ) );
				$mails = $mailer->get_emails();

				if ( ! empty( $mails ) ) {
					foreach ( $mails as $mail ) {
						if ( in_array( $mail->id, $available_emails ) ) {
							echo '<label><input name="order_email[]" type="checkbox" value="'. esc_attr( $mail->id ) .'" id="'. esc_attr( $mail->id ) .'_email"> ' . $mail->title. '</label>';
							echo '<img class="help_tip" data-tip="' . esc_attr( $mail->description ) . '" src="' . $woocommerce->plugin_url() . '/assets/images/help.png" /></br >';
						}
					}

					?>
					<p>
						<input type="submit" class="save-post-visibility hide-if-no-js button" value="<?php _e( 'Send selected emails', 'woocommerce' ); ?>">
						<a href="#order-emails" class="hide-order-emails hide-if-no-js"><?php _e( 'Cancel', 'woocommerce' ); ?></a>
					</p>
					<?php
				}
				?>
			</div>
		</li>

		<li class="wide">
			<div id="delete-action"><?php
				if ( current_user_can( "delete_post", $post->ID ) ) {
					if ( ! EMPTY_TRASH_DAYS )
						$delete_text = __( 'Delete Permanently', 'woocommerce' );
					else
						$delete_text = __( 'Move to Trash', 'woocommerce' );
					?><a class="submitdelete deletion" href="<?php echo esc_url( get_delete_post_link( $post->ID ) ); ?>"><?php echo $delete_text; ?></a><?php
				} 
			?></div>
			
			<input type="submit" class="button save_order button-primary tips" name="save" value="<?php _e( 'Save Order', 'woocommerce' ); ?>" data-tip="<?php _e( 'Save/update the order', 'woocommerce' ); ?>" />
		</li>
	</ul>
	<?php
}


/**
 * Displays the order totals meta box.
 *
 * @access public
 * @param mixed $post
 * @return void
 */
function woocommerce_order_totals_meta_box($post) {
	global $woocommerce;

	$data = get_post_custom( $post->ID );
	?>
	<div class="totals_group">
		<h4><span class="discount_total_display inline_total"></span><?php _e( 'Discounts', 'woocommerce' ); ?></h4>
		<ul class="totals">

			<li class="left">
				<label><?php _e( 'Cart Discount:', 'woocommerce' ); ?></label>
				<input type="text" id="_cart_discount" name="_cart_discount" placeholder="0.00" value="<?php
					if ( isset( $data['_cart_discount'][0] ) ) 
						echo esc_attr( $data['_cart_discount'][0] );
				?>" class="calculated" />
			</li>

			<li class="right">
				<label><?php _e( 'Order Discount:', 'woocommerce' ); ?></label>
				<input type="text" id="_order_discount" name="_order_discount" placeholder="0.00" value="<?php
					if ( isset( $data['_order_discount'][0] ) ) 
						echo esc_attr( $data['_order_discount'][0] );
				?>" />
			</li>

		</ul>
		<div class="clear"></div>
	</div>
	<div class="totals_group">
		<h4><?php _e( 'Shipping', 'woocommerce' ); ?></h4>
		<ul class="totals">

			<li class="left">
				<label><?php _e( 'Cost ex. tax:', 'woocommerce' ); ?></label>
				<input type="text" id="_order_shipping" name="_order_shipping" placeholder="0.00 <?php _e( '(ex. tax)', 'woocommerce' ); ?>" value="<?php 
					if ( isset( $data['_order_shipping'][0] ) ) 
						echo esc_attr( $data['_order_shipping'][0] );
				?>" class="first" />
			</li>

			<li class="right">
				<label><?php _e( 'Shipping Method:', 'woocommerce' ); ?></label>
				<select name="_shipping_method" id="_shipping_method" class="first">
					<option value=""><?php _e( 'N/A', 'woocommerce' ); ?></option>
					<?php
						$chosen_method 	= $data['_shipping_method'][0];
						$found_method 	= false;

						if ( $woocommerce->shipping ) {
							foreach ( $woocommerce->shipping->load_shipping_methods() as $method ) {
								echo '<option value="' . esc_attr( $method->id ) . '" ' . selected( ( strpos( $chosen_method, $method->id ) === 0 ), true, false ) . '>' . esc_html( $method->get_title() ) . '</option>';
								if ( strpos( $chosen_method, $method->id ) === 0 )
									$found_method = true;
							}
						}

						if ( ! $found_method && ! empty( $chosen_method ) ) {
							echo '<option value="' . esc_attr( $chosen_method ) . '" selected="selected">' . __( 'Other', 'woocommerce' ) . '</option>';
						} else {
							echo '<option value="other">' . __( 'Other', 'woocommerce' ) . '</option>';
						}
					?>
				</select>
			</li>

			<li class="wide">
				<label><?php _e( 'Shipping Title:', 'woocommerce' ); ?></label>
				<input type="text" id="_shipping_method_title" name="_shipping_method_title" placeholder="<?php _e( 'The shipping title the customer sees', 'woocommerce' ); ?>" value="<?php 
					if ( isset( $data['_shipping_method_title'][0] ) ) 
						echo esc_attr( $data['_shipping_method_title'][0] );
				?>" class="first" />
			</li>

		</ul>
		<?php do_action( 'woocommerce_admin_order_totals_after_shipping', $post->ID ) ?>
		<div class="clear"></div>
	</div>
	<div class="totals_group">
		<h4><?php _e( 'Tax Rows', 'woocommerce' ); ?></h4>
		<div id="tax_rows">
			<?php
				$loop = 0;
				$taxes = isset( $data['_order_taxes'][0] ) ? maybe_unserialize( $data['_order_taxes'][0] ) : '';
				if ( is_array( $taxes ) && sizeof( $taxes ) > 0 ) {
					foreach ( $taxes as $tax ) {
						?>
						<div class="tax_row">
							<p class="first">
								<label><?php _e( 'Tax Label:', 'woocommerce' ); ?></label>
								<input type="text" name="_order_taxes_label[<?php echo $loop; ?>]" placeholder="<?php echo $woocommerce->countries->tax_or_vat(); ?>" value="<?php echo esc_attr( $tax['label'] ); ?>" />
							</p>
							<p class="last">
								<label><?php _e( 'Compound:', 'woocommerce' ); ?>
								<input type="checkbox" name="_order_taxes_compound[<?php echo $loop; ?>]" <?php checked( $tax['compound'], 1 ); ?> /></label>
							</p>
							<p class="first">
								<label><?php _e( 'Cart Tax:', 'woocommerce' ); ?></label>
								<input type="text" name="_order_taxes_cart[<?php echo $loop; ?>]" placeholder="0.00" value="<?php echo esc_attr( $tax['cart_tax'] ); ?>" />
							</p>
							<p class="last">
								<label><?php _e( 'Shipping Tax:', 'woocommerce' ); ?></label>
								<input type="text" name="_order_taxes_shipping[<?php echo $loop; ?>]" placeholder="0.00" value="<?php echo esc_attr( $tax['shipping_tax'] ); ?>" />
							</p>
							<a href="#" class="delete_tax_row">&times;</a>
							<div class="clear"></div>
						</div>
						<?php
						$loop++;
					}
				}
			?>
		</div>
		<h4><a href="#" class="add_tax_row tips" data-tip="<?php _e( 'These rows contain taxes for this order. This allows you to display multiple or compound taxes rather than a single total.', 'woocommerce' ); ?>"><?php _e( '+ Add tax row', 'woocommerce' ); ?> [?]</a></a></h4>
		<div class="clear"></div>
	</div>
	<div class="totals_group">
		<h4><span class="tax_total_display inline_total"></span><?php _e( 'Tax Totals', 'woocommerce' ); ?></h4>
		<ul class="totals">

			<li class="left">
				<label><?php _e( 'Cart Tax:', 'woocommerce' ); ?></label>
				<input type="text" id="_order_tax" name="_order_tax" placeholder="0.00" value="<?php
					if ( isset( $data['_order_tax'][0] ) ) 
						echo esc_attr( $data['_order_tax'][0] );
				?>" class="calculated" />
			</li>

			<li class="right">
				<label><?php _e( 'Shipping Tax:', 'woocommerce' ); ?></label>
				<input type="text" id="_order_shipping_tax" name="_order_shipping_tax" placeholder="0.00" value="<?php
					if ( isset( $data['_order_shipping_tax'][0] ) ) 
						echo esc_attr( $data['_order_shipping_tax'][0] );
				?>" />
			</li>

		</ul>
		<div class="clear"></div>
	</div>
	<div class="totals_group">
		<h4><?php _e( 'Total', 'woocommerce' ); ?></h4>
		<ul class="totals">

			<li class="left">
				<label><?php _e( 'Order Total:', 'woocommerce' ); ?></label>
				<input type="text" id="_order_total" name="_order_total" placeholder="0.00" value="<?php
					if ( isset( $data['_order_total'][0] ) ) 
						echo esc_attr( $data['_order_total'][0] );
				?>" class="calculated" />
			</li>

			<li class="right">
				<label><?php _e( 'Payment Method:', 'woocommerce' ); ?></label>
				<select name="_payment_method" id="_payment_method" class="first">
					<option value=""><?php _e( 'N/A', 'woocommerce' ); ?></option>
					<?php
						$chosen_method 	= $data['_payment_method'][0];
						$found_method 	= false;

						if ( $woocommerce->payment_gateways ) {
							foreach ( $woocommerce->payment_gateways->payment_gateways() as $gateway ) {
								if ( $gateway->enabled == "yes" ) {
									echo '<option value="' . esc_attr( $gateway->id ) . '" ' . selected( $chosen_method, $gateway->id, false ) . '>' . esc_html( $gateway->get_title() ) . '</option>';
									if ( $chosen_method == $gateway->id )
										$found_method = true;
								}
							}
						}

						if ( ! $found_method && ! empty( $chosen_method ) ) {
							echo '<option value="' . esc_attr( $chosen_method ) . '" selected="selected">' . __( 'Other', 'woocommerce' ) . '</option>';
						} else {
							echo '<option value="other">' . __( 'Other', 'woocommerce' ) . '</option>';
						}
					?>
				</select>
			</li>

		</ul>
		<div class="clear"></div>
	</div>
	<?php
}


/**
 * Save the order data meta box.
 *
 * @access public
 * @param mixed $post_id
 * @param mixed $post
 * @return void
 */
function woocommerce_process_shop_order_meta( $post_id, $post ) {
	global $wpdb, $woocommerce, $woocommerce_errors;

	// Add key
	add_post_meta( $post_id, '_order_key', uniqid('order_'), true );

	// Update post data
	update_post_meta( $post_id, '_billing_first_name', woocommerce_clean( $_POST['_billing_first_name'] ) );
	update_post_meta( $post_id, '_billing_last_name', woocommerce_clean( $_POST['_billing_last_name'] ) );
	update_post_meta( $post_id, '_billing_company', woocommerce_clean( $_POST['_billing_company'] ) );
	update_post_meta( $post_id, '_billing_address_1', woocommerce_clean( $_POST['_billing_address_1'] ) );
	update_post_meta( $post_id, '_billing_address_2', woocommerce_clean( $_POST['_billing_address_2'] ) );
	update_post_meta( $post_id, '_billing_city', woocommerce_clean( $_POST['_billing_city'] ) );
	update_post_meta( $post_id, '_billing_postcode', woocommerce_clean( $_POST['_billing_postcode'] ) );
	update_post_meta( $post_id, '_billing_country', woocommerce_clean( $_POST['_billing_country'] ) );
	update_post_meta( $post_id, '_billing_state', woocommerce_clean( $_POST['_billing_state'] ) );
	update_post_meta( $post_id, '_billing_email', woocommerce_clean( $_POST['_billing_email'] ) );
	update_post_meta( $post_id, '_billing_phone', woocommerce_clean( $_POST['_billing_phone'] ) );
	update_post_meta( $post_id, '_shipping_first_name', woocommerce_clean( $_POST['_shipping_first_name'] ) );
	update_post_meta( $post_id, '_shipping_last_name', woocommerce_clean( $_POST['_shipping_last_name'] ) );
	update_post_meta( $post_id, '_shipping_company', woocommerce_clean( $_POST['_shipping_company'] ) );
	update_post_meta( $post_id, '_shipping_address_1', woocommerce_clean( $_POST['_shipping_address_1'] ) );
	update_post_meta( $post_id, '_shipping_address_2', woocommerce_clean( $_POST['_shipping_address_2'] ) );
	update_post_meta( $post_id, '_shipping_city', woocommerce_clean( $_POST['_shipping_city'] ) );
	update_post_meta( $post_id, '_shipping_postcode', woocommerce_clean( $_POST['_shipping_postcode'] ) );
	update_post_meta( $post_id, '_shipping_country', woocommerce_clean( $_POST['_shipping_country'] ) );
	update_post_meta( $post_id, '_shipping_state', woocommerce_clean( $_POST['_shipping_state'] ) );
	update_post_meta( $post_id, '_order_shipping', woocommerce_clean( $_POST['_order_shipping'] ) );
	update_post_meta( $post_id, '_cart_discount', woocommerce_clean( $_POST['_cart_discount'] ) );
	update_post_meta( $post_id, '_order_discount', woocommerce_clean( $_POST['_order_discount'] ) );
	update_post_meta( $post_id, '_order_total', woocommerce_clean( $_POST['_order_total'] ) );
	update_post_meta( $post_id, '_customer_user', absint( $_POST['customer_user'] ) );
	update_post_meta( $post_id, '_order_tax', woocommerce_clean( $_POST['_order_tax'] ) );
	update_post_meta( $post_id, '_order_shipping_tax', woocommerce_clean( $_POST['_order_shipping_tax'] ) );

	// Shipping method handling
	if ( get_post_meta( $post_id, '_shipping_method', true ) !== stripslashes( $_POST['_shipping_method'] ) ) {

		$shipping_method = woocommerce_clean( $_POST['_shipping_method'] );

		update_post_meta( $post_id, '_shipping_method', $shipping_method );
	}

	if ( get_post_meta( $post_id, '_shipping_method_title', true ) !== stripslashes( $_POST['_shipping_method_title'] ) ) {

		$shipping_method_title = woocommerce_clean( $_POST['_shipping_method_title'] );

		if ( ! $shipping_method_title ) {

			$shipping_method = esc_attr( $_POST['_shipping_method'] );
			$methods = $woocommerce->shipping->load_shipping_methods();

			if ( isset( $methods ) && isset( $methods[ $shipping_method ] ) )
				$shipping_method_title = $methods[ $shipping_method ]->get_title();
		}

		update_post_meta( $post_id, '_shipping_method_title', $shipping_method_title );
	}

	// Payment method handling
	if ( get_post_meta( $post_id, '_payment_method', true ) !== stripslashes( $_POST['_payment_method'] ) ) {

		$methods 				= $woocommerce->payment_gateways->payment_gateways();
		$payment_method 		= woocommerce_clean( $_POST['_payment_method'] );
		$payment_method_title 	= $payment_method;

		if ( isset( $methods) && isset( $methods[ $payment_method ] ) )
			$payment_method_title = $methods[ $payment_method ]->get_title();

		update_post_meta( $post_id, '_payment_method', $payment_method );
		update_post_meta( $post_id, '_payment_method_title', $payment_method_title );
	}

	// Update date
	if ( empty( $_POST['order_date'] ) ) {
		$date = current_time('timestamp');
	} else {
		$date = strtotime( $_POST['order_date'] . ' ' . (int) $_POST['order_date_hour'] . ':' . (int) $_POST['order_date_minute'] . ':00' );
	}

	$wpdb->query( $wpdb->prepare( "UPDATE $wpdb->posts SET post_date = %s WHERE ID = %s", date_i18n( 'Y-m-d H:i:s', $date ), $post_id ) );

	// Tax rows
	$order_taxes = array();

	if ( isset( $_POST['_order_taxes_label'] ) ) {

		$order_taxes_label		= $_POST['_order_taxes_label'];
		$order_taxes_compound 	= isset( $_POST['_order_taxes_compound'] ) ? $_POST['_order_taxes_compound'] : array();
		$order_taxes_cart 		= $_POST['_order_taxes_cart'];
		$order_taxes_shipping 	= $_POST['_order_taxes_shipping'];
		$order_taxes_label_count = sizeof( $order_taxes_label );

		for ( $i = 0; $i < $order_taxes_label_count; $i ++ ) {

			// Add to array if the tax amount is set
			if ( ! $order_taxes_cart[ $i ] && ! $order_taxes_shipping[ $i ] ) 
				continue;

			if ( ! $order_taxes_label[ $i ] ) 
				$order_taxes_label[ $i ] = $woocommerce->countries->tax_or_vat();

			if ( isset( $order_taxes_compound[ $i ] ) ) 
				$is_compound = 1; 
			else 
				$is_compound = 0;

			$order_taxes[] = array(
				'label' 		=> esc_attr( $order_taxes_label[ $i ] ),
				'compound' 		=> $is_compound,
				'cart_tax' 		=> esc_attr( $order_taxes_cart[ $i ] ),
				'shipping_tax' 	=> esc_attr( $order_taxes_shipping[ $i ] )
			);

		}
	}

	update_post_meta( $post_id, '_order_taxes', $order_taxes );

	// Order items
	if ( isset( $_POST['order_item_id'] ) ) {
		$order_item_id		= $_POST['order_item_id'];
		$order_item_qty 	= $_POST['order_item_qty'];
		$line_subtotal		= $_POST['line_subtotal'];
		$line_subtotal_tax	= $_POST['line_subtotal_tax'];
		$line_total 		= $_POST['line_total'];
		$line_tax		 	= $_POST['line_tax'];
		$item_tax_class		= $_POST['order_item_tax_class'];

		foreach ( $order_item_id as $item_id ) {
			
			$item_id = absint( $item_id );

		 	woocommerce_update_order_item_meta( $item_id, '_qty', absint( $order_item_qty[ $item_id ] ) );
		 	woocommerce_update_order_item_meta( $item_id, '_tax_class', woocommerce_clean( $item_tax_class[ $item_id ] ) );
		 	woocommerce_update_order_item_meta( $item_id, '_line_subtotal', woocommerce_clean( $line_subtotal[ $item_id ] ) );
		 	woocommerce_update_order_item_meta( $item_id, '_line_subtotal_tax', woocommerce_clean( $line_subtotal_tax[ $item_id ] ) );
		 	woocommerce_update_order_item_meta( $item_id, '_line_total', woocommerce_clean( $line_total[ $item_id ] ) );
		 	woocommerce_update_order_item_meta( $item_id, '_line_tax', woocommerce_clean( $line_tax[ $item_id ] ) );
		}
	}
	
	// Save meta
	$meta_keys 		= isset( $_POST['meta_key'] ) ? $_POST['meta_key'] : '';
	$meta_values 	= isset( $_POST['meta_value'] ) ? $_POST['meta_value'] : '';
	
	foreach ( $meta_keys as $id => $value ) {
		$wpdb->update( 
			$wpdb->prefix . "woocommerce_order_itemmeta", 
			array( 
				'meta_key' => $value,
				'meta_value' => empty( $meta_values[ $id ] ) ? '' : $meta_values[ $id ]
			), 
			array( 'meta_id' => $id ), 
			array( '%s', '%s' ), 
			array( '%d' ) 
		);
	}

	// Order data saved, now get it so we can manipulate status
	$order = new WC_Order( $post_id );

	// Order status
	$order->update_status( $_POST['order_status'] );

	// Handle button actions
	if ( ! empty( $_POST['reduce_stock'] ) && sizeof( $order_items ) > 0 ) {

		$order->add_order_note( __( 'Manually reducing stock.', 'woocommerce' ) );

		foreach ( $order_items as $order_item ) {

			$_product = $order->get_product_from_item( $order_item );

			if ( $_product->exists() ) {

			 	if ( $_product->managing_stock() ) {

					$old_stock = $_product->stock;

					$new_quantity = $_product->reduce_stock( $order_item['qty'] );

					$order->add_order_note( sprintf( __( 'Item #%s stock reduced from %s to %s.', 'woocommerce' ), $order_item['id'], $old_stock, $new_quantity) );

					$order->send_stock_notifications( $_product, $new_quantity, $order_item['qty'] );

				}

			} else {

				$order->add_order_note( sprintf( __( 'Item %s %s not found, skipping.', 'woocommerce' ), $order_item['id'], $order_item['name'] ) );

			}
		}

		$order->add_order_note( __( 'Manual stock reduction complete.', 'woocommerce' ) );

		do_action( 'woocommerce_reduce_order_stock', $order );

	} elseif ( ! empty( $_POST['restore_stock'] ) && sizeof( $order_items ) > 0 ) {

		$order->add_order_note( __( 'Manually restoring stock.', 'woocommerce' ) );

		foreach ( $order_items as $order_item ) {

			$_product = $order->get_product_from_item( $order_item );

			if ( $_product->exists() ) {

			 	if ( $_product->managing_stock() ) {

					$old_stock = $_product->stock;

					$new_quantity = $_product->increase_stock( $order_item['qty'] );

					$order->add_order_note( sprintf( __( 'Item #%s stock increased from %s to %s.', 'woocommerce' ), $order_item['id'], $old_stock, $new_quantity) );

				}

			} else {

				$order->add_order_note( sprintf( __( 'Item %s %s not found, skipping.', 'woocommerce' ), $order_item['id'], $order_item['name'] ) );

			}

		}

		$order->add_order_note( __( 'Manual stock restore complete.', 'woocommerce' ) );

		do_action( 'woocommerce_restore_order_stock', $order );

	} elseif ( ! empty( $_POST['order_email'] ) ) {

		do_action( 'woocommerce_before_resend_order_emails', $order );

		$mailer = $woocommerce->mailer();

		$available_emails = apply_filters( 'woocommerce_resend_order_emails_available', array( 'new_order', 'customer_processing_order', 'customer_completed_order', 'customer_invoice' ) );
		$resend_emails = array_intersect( $available_emails, $_POST['order_email'] );
		$mails = $mailer->get_emails();

		if ( ! empty( $mails ) ) {
			foreach ( $mails as $mail ) {
				if ( in_array( $mail->id, $resend_emails ) ) {
					$mail->trigger( $order->id );
				}
			}
		}

		do_action( 'woocommerce_after_resend_order_emails', $order, $resend_emails );

	}

	delete_transient( 'woocommerce_processing_order_count' );
}

add_action( 'woocommerce_process_shop_order_meta', 'woocommerce_process_shop_order_meta', 10, 2 );