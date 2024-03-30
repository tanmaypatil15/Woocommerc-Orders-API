<?php
/*
Plugin Name: Woocommerce Orders API
Description: Create and retrieve the list of orders for the particular customer.
Version: 1.0
Author: Tanmay Patil
*/

// Define the path to the WooCommerce Points and Rewards plugin file
//define('WC_POINTS_REWARDS_PLUGIN_PATH', ABSPATH . 'wp-content/plugins/woocommerce-points-and-rewards/');

// Include the WC_Points_Rewards_Order class from the plugin file
//require_once( WC_POINTS_REWARDS_PLUGIN_PATH . '/includes/class-wc-points-rewards-order.php');

require_once WP_PLUGIN_DIR . '/woocommerce-points-and-rewards/includes/class-wc-points-rewards-order.php';


// Define a custom endpoint to create & retrieve customer orders
add_action('rest_api_init', function(){
    // Endpoint for retrieving customer orders.
    register_rest_route(
        'wc/v3', 
        '/orders/retrieve', 
        array(
            'methods' => 'GET', 
            'callback' => 'get_orders_data',
            'args' => array(
                'customer_id' => array(
                    'description' => 'Customer ID for whom to retrieve orders.',
                    'type' => 'string',
                    'required' => false,
                ),
            ),
            'permission_callback' => '__return_true', // Add permission callback
        )
    );

    // Endpoint for creating a new customer order.
    register_rest_route(
        'wc/v3', 
        '/orders/create', 
        array(
            'methods' => 'POST',
			'callback' => 'create_orders_data',
            'permission_callback' => '__return_true', // Add permission callback
        )
    );
});
	

function get_orders_data($request) {
    $customer_id = $request->get_param('customer_id');

    $params = $request->get_params();

    // Check if customer_id parameter is provided
    //$customer_id = isset($params['customer_id']) ? $params['customer_id'] : '';

    // Query orders based on customer ID
    $orders = wc_get_orders(array(
        'customer' => $customer_id,
        'status' => 'any', // Include orders with any status
        'orderby' => 'date',
        'order' => 'DESC',
    ));
	
   // $order_data = array();
	
    foreach ($orders as $order) {
		$points_earned = $order->get_meta( '_wc_points_earned', true );
		$points_redeemed = $order->get_meta( '_wc_points_redeemed', true );
		
		
        $order_data[] = array(
            'id' => $order->get_id(),
            'parent_id' => $order->get_parent_id(),
            'number' => $order->get_order_number(),
            'order_key' => $order->get_order_key(),
            'created_via' => $order->get_created_via(),
            'version' => $order->get_version(),
            'status' => $order->get_status(),
            'currency' => $order->get_currency(),
			'currency_symbol' => get_woocommerce_currency_symbol(),
			'coins_earned' => $points_earned,
			'coins_redeemed' => $points_redeemed,
            'date_created' => is_a($order->get_date_created(), 'WC_DateTime') ? $order->get_date_created()->format('Y-m-d H:i:s') : null,
// 			'date_created_gmt' => is_a($order->get_date_created_gmt(), 'WC_DateTime') ? $order->get_date_created_gmt()->format('Y-m-d H:i:s') : null,
			'date_modified' => is_a($order->get_date_modified(), 'WC_DateTime') ? $order->get_date_modified()->format('Y-m-d H:i:s') : null,
// 			'date_modified_gmt' => $order->get_date_modified_gmt() ? $order->get_date_modified_gmt()->format('Y-m-d H:i:s') : null,
            'discount_total' => $order->get_discount_total(),
            'discount_tax' => $order->get_discount_tax(),
            'shipping_total' => $order->get_shipping_total(),
            'shipping_tax' => $order->get_shipping_tax(),
            'cart_tax' => $order->get_cart_tax(),
            'total' => $order->get_total(),
            'total_tax' => $order->get_total_tax(),
            'prices_include_tax' => $order->get_prices_include_tax(),
            'customer_id' => $order->get_customer_id(),
            'customer_ip_address' => $order->get_customer_ip_address(),
            'customer_user_agent' => $order->get_customer_user_agent(),
            'customer_note' => $order->get_customer_note(),
			'billing' => get_billing_info($order),
			'shipping' => get_shipping_info($order),
            'payment_method' => $order->get_payment_method(),
            'payment_method_title' => $order->get_payment_method_title(),
            'transaction_id' => $order->get_transaction_id(),
            'date_paid' => is_a($order->get_date_paid(), 'WC_DateTime') ? $order->get_date_paid()->format('Y-m-d H:i:s') : null,
//             'date_paid_gmt' => strip_tags($order->get_date_paid_gmt()->format('Y-m-d H:i:s')),
            'date_completed' => is_a($order->get_date_completed(), 'WC_DateTime') ? $order->get_date_completed()->format('Y-m-d H:i:s') : null,
//             'date_completed_gmt' => $order->get_date_completed_gmt() ? strip_tags($order->get_date_completed_gmt()->format('Y-m-d H:i:s')) : null,
            'cart_hash' => $order->get_cart_hash(),
			'payment_url' => $order->get_checkout_payment_url(),
            'is_editable' => $order->is_editable(),
            'needs_payment' => $order->needs_payment(),
            'needs_processing' => $order->needs_processing(),
			'line_items' => get_line_items_info($order),
            'tax_lines' => get_tax_lines_info($order),
			'meta_data' => $order->get_meta_data(),
            'shipping_lines' => get_shipping_lines_info($order),
            'fee_lines' => get_fee_lines_info($order),
            'coupon_lines' => get_coupon_lines_info($order),
            'refunds' => get_refunds_info($order),
        );
    }

    return new WP_REST_Response($order_data, 200);
}


function get_billing_info($order) {
    $billing_info = array(
        'first_name' => $order->get_billing_first_name(),
        'last_name' => $order->get_billing_last_name(),
        'company' => $order->get_billing_company(),
        'address_1' => $order->get_billing_address_1(),
        'address_2' => $order->get_billing_address_2(),
        'city' => $order->get_billing_city(),
        'state' => $order->get_billing_state(),
        'postcode' => $order->get_billing_postcode(),
        'country' => $order->get_billing_country(),
        'email' => $order->get_billing_email(),
        'phone' => $order->get_billing_phone(),
    );
	
	return $billing_info;
}
	
	
function get_shipping_info($order) {
	
    $shipping_info = array(
        'first_name' => $order->get_shipping_first_name(),
        'last_name' => $order->get_shipping_last_name(),
        'company' => $order->get_shipping_company(),
        'address_1' => $order->get_shipping_address_1(),
        'address_2' => $order->get_shipping_address_2(),
        'city' => $order->get_shipping_city(),
        'state' => $order->get_shipping_state(),
        'postcode' => $order->get_shipping_postcode(),
        'country' => $order->get_shipping_country(),
		'email' => $order->get_billing_email(),
        'phone' => $order->get_shipping_phone(),
    );
	return $shipping_info;
}


function get_line_items_info($order) {
    $line_items = array();
	

    foreach ($order->get_items() as $item_id => $item) {
        $product = $item->get_product();
		$image_id = $product ? $product->get_image_id() : '';
    	$image_src = wp_get_attachment_image_src($image_id, 'full');
		$short_description = $product ? $product->get_short_description() : '';
		
        $line_items[] = array(
            'id' => $item_id,
            'name' => $item->get_name(),
			'short_description' => $short_description,
            'product_id' => $product ? $product->get_id() : null,
            'variation_id' => $item->get_variation_id(),
            'quantity' => $item->get_quantity(),
            'tax_class' => $item->get_tax_class(),
            'subtotal' => $item->get_subtotal(),
            'subtotal_tax' => $item->get_subtotal_tax(),
            'total' => $item->get_total(),
            'total_tax' => $item->get_total_tax(),
            'taxes' => $item->get_taxes(),
			'image' => array(
                'id' => $image_id,
                'src' => isset($image_src[0]) ? $image_src[0] : ''
            ),
            'meta_data' => $item->get_meta_data(),
            'sku' => $product ? $product->get_sku() : '',
            'price' => $product ? $product->get_price() : '',
        );
    }

    return $line_items;
}


function get_tax_lines_info($order) {
    $tax_lines = array();

    foreach ($order->get_tax_totals() as $tax_total) {
        $tax_lines[] = array(
            'id' => $tax_total->id,
            'rate_code' => $tax_total->rate_code,
            'rate_id' => $tax_total->rate_id,
            'label' => $tax_total->label,
            'compound' => $tax_total->compound,
            'tax_total' => $tax_total->tax_total,
            'shipping_tax_total' => $tax_total->shipping_tax_total,
            'meta_data' => $tax_total->meta_data,
        );
    }

    return $tax_lines;
}


function get_shipping_lines_info($order) {
    $shipping_lines = array();

    foreach ($order->get_shipping_methods() as $shipping_item) {
        $shipping_lines[] = array(
            'id' => $shipping_item->get_id(),
            'method_title' => $shipping_item->get_method_title(),
            'method_id' => $shipping_item->get_method_id(),
            'total' => $shipping_item->get_total(),
            'total_tax' => $shipping_item->get_total_tax(),
            'taxes' => $shipping_item->get_taxes(),
            'meta_data' => $shipping_item->get_meta_data(),
        );
    }

    return $shipping_lines;
}


function get_fee_lines_info($order) {
    $fee_lines = array();

    foreach ($order->get_fees() as $fee) {
        $fee_lines[] = array(
            'id' => $fee->get_id(),
            'name' => $fee->get_name(),
            'amount' => $fee->get_total(),
            'tax_class' => $fee->get_tax_class(),
            'tax_status' => $fee->get_tax_status(),
            'tax_total' => $fee->get_total_tax(),
        );
    }

    return $fee_lines;
}


function get_coupon_lines_info($order) {
    $coupon_lines = array();

    foreach ($order->get_items('coupon') as $item_id => $item) {
        $coupon = new WC_Coupon($item->get_code());

        $coupon_lines[] = array(
            'id' => $coupon->get_id(),
            'code' => $item->get_code(),
            'discount' => $item->get_discount(),
            'discount_tax' => $item->get_discount_tax(),
        );
    }

    return $coupon_lines;
}


function get_refunds_info($order) {
    $refunds = array();

    foreach ($order->get_refunds() as $refund) {
        $refunds[] = array(
            'id' => $refund->get_id(),
            'date_created' => strip_tags($refund->get_date_created()->format('Y-m-d H:i:s')),
            'date_created_gmt' => strip_tags($refund->get_date_created_gmt()->format('Y-m-d H:i:s')),
            // Add more refund related information if needed
        );
    }

    return $refunds;
}


function create_orders_data($request) {
	$parameters = $request->get_json_params();
	
	// Check if required parameters are present
    if (empty($parameters['customer_id']) || empty($parameters['payment_method']) || empty($parameters['set_paid']) || empty($parameters['payment_method_title']) || empty($parameters['line_items']) || empty($parameters['billing']) || empty($parameters['shipping'])) {
        return new WP_Error('error', 'Required parameters are missing', array('status' => 400));
    }
	
	// Initialize WC_Points_Rewards_Order class
    //$points_rewards_order = new WC_Points_Rewards_Order();
	
 	// Extract necessary parameters from $params
	$customer_id = $parameters['customer_id'];
	$product_id = $parameters['product_id'];
	$wc_points_redeemed = null;
	$wc_points_logged_redemption = null;
	$payment_method = $parameters['payment_method'];
    $set_paid = $parameters['set_paid'];
    $payment_method_title = $parameters['payment_method_title'];
	// Retrieve line items from the request
    $line_items = $parameters['line_items'];
	
	// Extract meta data if present
	if (isset($parameters['meta_data'])) {
		foreach ($parameters['meta_data'] as $meta) {
			if ($meta['key'] === '_wc_points_redeemed') {
				$wc_points_redeemed = $meta['value'];
			} elseif ($meta['key'] === '_wc_points_logged_redemption') {
				$wc_points_logged_redemption = $meta['value'];
			}
		}
	}
		

    // Validate product quantities
    if (!validate_product_quantity($line_items)) {
        return new WP_Error('error', 'Product quantity from store is less than or equal to 1', array('status' => 400));
    }
	
	// Create order
    $order = wc_create_order(array(
        'customer_id' => $customer_id,
    ));
	

	foreach ($line_items as $item) {
		$product_id = $item['product_id'];
		$quantity = $item['quantity'];
		

		if ($product_id) {
			// Add product to the order
			$product = wc_get_product($product_id);
			$order->add_product($product, $quantity);
		} else {
			// Handle the case where the product ID is invalid
			return new WP_Error('error', 'Invalid product ID', array('status' => 400));
		}
	}
	
	
	// 	Add meta data if available
// 	if ($wc_points_redeemed !== null) {
// 		$order->add_meta_data('_wc_points_redeemed', $wc_points_redeemed);
// 	}
// 	if ($wc_points_logged_redemption !== null) {
// 		$order->add_meta_data('_wc_points_logged_redemption', $wc_points_logged_redemption);
// 	}
	
	

	
	// Set billing address
    $order->set_billing_first_name($parameters['billing']['billing_first_name']);
    $order->set_billing_last_name($parameters['billing']['billing_last_name']);
    $order->set_billing_address_1($parameters['billing']['billing_address_1']);
    $order->set_billing_city($parameters['billing']['billing_city']);
    $order->set_billing_state($parameters['billing']['billing_state']);
    $order->set_billing_postcode($parameters['billing']['billing_postcode']);
    $order->set_billing_country($parameters['billing']['billing_country']);
    $order->set_billing_email($parameters['billing']['billing_email']);
    $order->set_billing_phone($parameters['billing']['billing_phone']);
	
	// Set shipping address
    $order->set_shipping_first_name($parameters['shipping']['shipping_first_name']);
    $order->set_shipping_last_name($parameters['shipping']['shipping_last_name']);
    $order->set_shipping_address_1($parameters['shipping']['shipping_address_1']);
    $order->set_shipping_city($parameters['shipping']['shipping_city']);
    $order->set_shipping_state($parameters['shipping']['shipping_state']);
    $order->set_shipping_postcode($parameters['shipping']['shipping_postcode']);
    $order->set_shipping_country($parameters['shipping']['shipping_country']);
//     $order->set_billing_email($parameters['shipping']['shipping_email']);
    $order->set_shipping_phone($parameters['shipping']['shipping_phone']);
	
	// Set payment method and title
    $order->set_payment_method($payment_method);
    $order->set_payment_method_title($payment_method_title);
	
	// Set paid status
    if ($set_paid) {
        $order->payment_complete();
	}
	
    // Calculate totals and save the order
    $order->calculate_totals();
    $order->save();
	

	// Update points and redeem them if applicable
    $order_id = $order->get_id();
    $points_redeemed = $wc_points_redeemed;
	
	// Create an instance of WC_Points_Rewards_Order class
	$order_handler = new WC_Points_Rewards_Order();

	// Call the methods as needed
	$order_handler->maybe_update_points($order_id);
	$order_handler->maybe_deduct_redeemed_points($order_id);
	
// 	var_dump($points_redeemed);
// 	exit;

//     if ($points_redeemed !== null) {
//         // Update points and redeem them
//         $points_update = $points_rewards_order->maybe_update_points($order_id);
//         $update_result = update_order_meta_data_and_redeem_points($order_id, $points_redeemed, $logged_redemption);

//         // Handle the update result as needed
//         if (is_wp_error($update_result)) {
//             print "Error";
//         } else {
//             // Update successful
//         }
//     }
	    
    // Return response based on order creation status
    if (is_wp_error($order)) {
        return new WP_REST_Response(array('message' => $order->get_error_message()), 400);
    } else {
		// Get order details for response
		$order_data = $order->get_data();

		// Prepare response data
		$response_data = array(
			'order_id' => $order_data['id'],
			'parent_id' => $order_data['parent_id'],
			'number' => $order_data['number'],
			'order_key' => $order_data['order_key'],
			'created_via' => $order_data['created_via'],
			'version' => $order_data['version'],
			'status' => $order_data['status'],
			'currency' => $order_data['currency'],
			'status' => $order_data['status'],
			'currency' => $order_data['currency'],
			'date_created' => $order_data['date_created'],
            'date_modified' => $order_data['date_modified'],
            'total' => $order_data['total'],
            'customer_ip_address' => $order_data['customer_ip_address'],
            'customer_user_agent' => $order_data['customer_user_agent'],
            'customer_note' => $order_data['customer_note'],
			'date_created' => $order_data['date_created'],
			'customer_id' => $order_data['customer_id'],
			'billing_address' => $order_data['billing'],
			'shipping_address' => $order_data['shipping'],
			'payment_method' => $order_data['payment_method'],
            'payment_method_title' => $order_data['payment_method_title'],
            'transaction_id' => $order_data['transaction_id'],
            'date_paid' => $order_data['date_paid']->date('Y-m-d\TH:i:s'),
            'date_completed' => $order_data['date_completed'],
            'meta_data' => $order->get_meta_data(),
            'coins_redeemed' => $points_redeemed,
			'message' => 'Order created successfully',
		);

		return new WP_REST_Response($response_data, 200);
	}
}



// Validate Product Quantity
function validate_product_quantity($line_items) {
    foreach ($line_items as $item) {
        $product_id = $item['product_id'];
        $product = wc_get_product($product_id);
        if (!$product || $product->get_stock_quantity() <= 1) {
            return false; // Product quantity is less than or equal to 1
        }
    }
    return true; // All product quantities are valid
}


function update_order_meta_data_and_redeem_points($order_id, $points_redeemed, $logged_redemption) {
    // Update metadata in the order
    update_post_meta($order_id, '_wc_points_redeemed', $points_redeemed);
    // You can update other metadata as needed

    // Deduct redeemed points from the user's points balance
    $user_id = $customer_id; // Assuming you have user ID available
    $current_points = wc_points_rewards_get_points_balance($user_id);
    $updated_points = $current_points - $points_redeemed;
    wc_points_rewards_update_points($user_id, $updated_points);

    // Log redemption event or update records
    // Example: Log redemption event in a custom table
    $log_data = array(
        'order_id' => $order_id,
        'user_id' => $user_id,
        'points_redeemed' => $points_redeemed,
        'logged_redemption' => $logged_redemption,
        'redeemed_at' => current_time('mysql'),
    );
    $log_id = insert_custom_redemption_log($log_data); // Your custom function to insert log data

    if (!$log_id) {
        return new WP_Error('error', 'Failed to log redemption event', array('status' => 500));
    }

    // Return success if everything is processed correctly
    return true;
}

