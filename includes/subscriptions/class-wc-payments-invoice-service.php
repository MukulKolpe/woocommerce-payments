<?php
/**
 * Class WC_Payments_Invoice_Service
 *
 * @package WooCommerce\Payments
 */

use WCPay\Exceptions\API_Exception;
use WCPay\Exceptions\Rest_Request_Exception;
use WCPay\Logger;

defined( 'ABSPATH' ) || exit;

/**
 * Class handling any subscription invoice functionality.
 */
class WC_Payments_Invoice_Service {

	/**
	 * Subscription meta key used to store subscription's last invoice ID.
	 *
	 * @const string
	 */
	const PENDING_INVOICE_ID_KEY = '_wcpay_pending_invoice_id';

	/**
	 * Meta key used to store invoice IDs on orders.
	 *
	 * @const
	 */
	const ORDER_INVOICE_ID_KEY = '_wcpay_billing_invoice_id';

	/**
	 * Client for making requests to the WooCommerce Payments API.
	 *
	 * @var WC_Payments_API_Client
	 */
	private $payments_api_client;

	/**
	 * Product Service
	 *
	 * @var WC_Payments_Product_Service
	 */
	private $product_service;

	/**
	 * Constructor.
	 *
	 * @param WC_Payments_API_Client      $payments_api_client  WooCommerce Payments API client.
	 * @param WC_Payments_Product_Service $product_service      Product Service.
	 */
	public function __construct( WC_Payments_API_Client $payments_api_client, WC_Payments_Product_Service $product_service ) {
		$this->payments_api_client = $payments_api_client;
		$this->product_service     = $product_service;

		add_action( 'woocommerce_order_status_changed', [ $this, 'maybe_record_first_invoice_payment' ], 10, 3 );
	}

	/**
	 * Gets the subscription last invoice ID from WC subscription.
	 *
	 * @param WC_Subscription $subscription The subscription.
	 *
	 * @return string Invoice ID.
	 */
	public static function get_pending_invoice_id( $subscription ) : string {
		return $subscription->get_meta( self::PENDING_INVOICE_ID_KEY, true );
	}

	/**
	 * Gets the invoice ID from a WC order.
	 *
	 * @param WC_Order $order The order.
	 * @return string Invoice ID.
	 */
	public static function get_order_invoice_id( WC_Order $order ) : string {
		return $order->get_meta( self::ORDER_INVOICE_ID_KEY, true );
	}

	/**
	 * Gets the invoice ID from a WC subscription.
	 *
	 * @param WC_Subscription $subscription The subscription.
	 *
	 * @return string Invoice ID.
	 */
	public static function get_subscription_invoice_id( $subscription ) {
		return $subscription->get_meta( self::ORDER_INVOICE_ID_KEY, true );
	}

	/**
	 * Gets the WC order ID from the invoice ID.
	 *
	 * @param string $invoice_id The invoice ID.
	 * @return int The order ID.
	 */
	public static function get_order_id_by_invoice_id( string $invoice_id ) {
		global $wpdb;

		return (int) $wpdb->get_var(
			$wpdb->prepare(
				"
				SELECT pm.post_id
				FROM {$wpdb->prefix}postmeta AS pm
				INNER JOIN {$wpdb->prefix}posts AS p ON pm.post_id = p.ID
				WHERE pm.meta_key = %s AND pm.meta_value = %s
				",
				self::ORDER_INVOICE_ID_KEY,
				$invoice_id
			)
		);
	}

	/**
	 * Sets a pending invoice ID meta for a subscription.
	 *
	 * @param WC_Subscription $subscription The subscription to set the invoice on.
	 * @param string          $invoice_id   The invoice ID.
	 */
	public function mark_pending_invoice_for_subscription( WC_Subscription $subscription, string $invoice_id ) {
		$this->set_pending_invoice_id( $subscription, $invoice_id );
	}

	/**
	 * Removes pending invoice id meta from subscription.
	 *
	 * @param WC_Subscription $subscription The Subscription.
	 */
	public function mark_pending_invoice_paid_for_subscription( WC_Subscription $subscription ) {
		$this->set_pending_invoice_id( $subscription, '' );
	}

	/**
	 * Marks the initial subscription invoice as paid after a parent order is completed.
	 *
	 * When a subscription's parent order goes from a pending payment status to a payment completed status,
	 * make sure the invoice is marked as paid (without charging the customer since it was charged on checkout).
	 *
	 * @param int    $order_id   The order which is updating status.
	 * @param string $old_status The order's old status.
	 * @param string $new_status The order's new status.
	 */
	public function maybe_record_first_invoice_payment( int $order_id, string $old_status, string $new_status ) {
		$order = wc_get_order( $order_id );

		if ( ! $order ) {
			return;
		}

		$needed_payment  = in_array( $old_status, apply_filters( 'woocommerce_valid_order_statuses_for_payment', [ 'pending', 'on-hold', 'failed' ], $order ), true );
		$order_completed = in_array( $new_status, [ apply_filters( 'woocommerce_payment_complete_order_status', 'processing', $order_id, $order ), 'processing', 'completed' ], true );

		if ( $needed_payment && $order_completed ) {
			foreach ( wcs_get_subscriptions_for_order( $order, [ 'order_type' => 'parent' ] ) as $subscription ) {
				$invoice_id = self::get_subscription_invoice_id( $subscription );

				if ( ! $invoice_id ) {
					continue;
				}

				// Update the status of the invoice to paid but don't charge the customer by using paid_out_of_band parameter.
				$this->payments_api_client->charge_invoice( $invoice_id, [ 'paid_out_of_band' => 'true' ] );
			}
		}
	}

	/**
	 * Validates a WCPay invoice.
	 *
	 * @param array           $wcpay_items     The WCPay invoice items.
	 * @param array           $wcpay_discounts The WCPay invoice discounts.
	 * @param WC_Subscription $subscription    The WC Subscription object.
	 *
	 * @throws Rest_Request_Exception WCPay invoice items do not match WC subscription items.
	 */
	public function validate_invoice_items( array $wcpay_items, array $wcpay_discounts, WC_Subscription $subscription ) {
		$wcpay_item_data = [];

		foreach ( $wcpay_items as $item ) {
			$wcpay_subscription_item_id = $item['subscription_item'];

			$wcpay_item_data[ $wcpay_subscription_item_id ] = [
				'amount'    => $item['amount'],
				'quantity'  => $item['quantity'],
				'tax_rates' => array_column( $item['tax_rates'], 'percentage' ),
			];
		}

		foreach ( $subscription->get_items( [ 'line_item', 'fee', 'shipping' ] ) as $item ) {
			$subscription_item_id = WC_Payments_Subscription_Service::get_wcpay_subscription_item_id( $item );

			if ( ! $subscription_item_id ) {
				continue;
			}

			if ( ! in_array( $subscription_item_id, array_keys( $wcpay_item_data ), true ) ) {
				$message = __( 'The WCPay invoice items do not match WC subscription items', 'woocommerce-payments' );
				Logger::error( $message );
				throw new Rest_Request_Exception( $message );
			}

			$item_data   = $wcpay_item_data[ $subscription_item_id ];
			$repair_data = [];

			if ( (int) $item->get_total() * 100 !== $item_data['amount'] ) {
				if ( $item->is_type( 'line_item' ) ) {
					$product              = $item->get_product();
					$repair_data['price'] = $this->product_service->get_wcpay_price_id( $product );
				} else {
					$repair_data['price_data'] = WC_Payments_Subscription_Service::format_item_price_data(
						$subscription->get_currency(),
						$this->product_service->get_stripe_product_id_for_item( $item->get_type() ),
						$item->get_total(),
						$subscription->get_billing_period(),
						$subscription->get_billing_interval()
					);
				}
			}

			if ( $item->get_quantity() !== $item_data['quantity'] ) {
				$repair_data['quantity'] = $item->get_quantity();
			}

			if ( ! empty( $item->get_taxes() ) ) {
				$tax_rate_ids = array_keys( $item->get_taxes()['total'] );

				if ( count( $tax_rate_ids ) !== count( $item_data['tax_rates'] ) ) {
					$repair_data['tax_rates'] = WC_Payments_Subscription_Service::get_tax_rates_for_item( $item, $subscription );
				} else {
					foreach ( $subscription->get_taxes() as $tax ) {
						if ( in_array( $tax->get_rate_id(), $tax_rate_ids, true ) && ! in_array( (int) $tax->get_rate_percent(), $item_data['tax_rates'], true ) ) {
							$repair_data['tax_rates'] = WC_Payments_Subscription_Service::get_tax_rates_for_item( $item, $subscription );
							break;
						}
					}
				}
			}

			if ( ! empty( $repair_data ) ) {
				$this->payments_api_client->update_subscription_item( $subscription_item_id, $repair_data );
			}
		}

		// TODO: Handle discounts.
	}

	/**
	 * Sets the subscription's last invoice ID meta for WC subscription.
	 *
	 * @param WC_Order $order      The order.
	 * @param string   $invoice_id The invoice ID.
	 */
	public function set_order_invoice_id( WC_Order $order, string $invoice_id ) {
		$order->update_meta_data( self::ORDER_INVOICE_ID_KEY, $invoice_id );
		$order->save();
	}

	/**
	 * Sets the subscription's last invoice ID meta for WC subscription.
	 *
	 * @param WC_Subscription $subscription      The subscription.
	 * @param string          $parent_invoice_id The parent order invoice ID.
	 */
	public function set_subscription_invoice_id( WC_Subscription $subscription, string $parent_invoice_id ) {
		$subscription->update_meta_data( self::ORDER_INVOICE_ID_KEY, $parent_invoice_id );
		$subscription->save();
	}

	/**
	 * Sets the subscription last invoice ID meta for WC subscription.
	 *
	 * @param WC_Subscription $subscription The subscription.
	 * @param string          $invoice_id   The invoice ID.
	 */
	private function set_pending_invoice_id( $subscription, string $invoice_id ) {
		$subscription->update_meta_data( self::PENDING_INVOICE_ID_KEY, $invoice_id );
		$subscription->save();
	}
}
