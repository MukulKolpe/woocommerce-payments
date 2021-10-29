<?php
/**
 * Class Compatibility
 *
 * @package WooCommerce\Payments\Compatibility
 */

namespace WCPay\MultiCurrency;

use WC_Order;
use WC_Order_Refund;
use WC_Product;
use WCPay\MultiCurrency\Compatibility\WooCommerceBookings;
use WCPay\MultiCurrency\Compatibility\WooCommerceFedEx;
use WCPay\MultiCurrency\Compatibility\WooCommerceProductAddOns;
use WCPay\MultiCurrency\Compatibility\WooCommerceSubscriptions;
use WCPay\MultiCurrency\Compatibility\WooCommerceUPS;

defined( 'ABSPATH' ) || exit;

/**
 * Class that controls Multi-Currency Compatibility.
 */
class Compatibility {

	const FILTER_PREFIX = 'wcpay_multi_currency_';

	/**
	 * MultiCurrency class.
	 *
	 * @var MultiCurrency
	 */
	private $multi_currency;

	/**
	 * Utils class.
	 *
	 * @var Utils
	 */
	private $utils;

	/**
	 * Constructor.
	 *
	 * @param MultiCurrency $multi_currency MultiCurrency class.
	 * @param Utils         $utils Utils class.
	 */
	public function __construct( MultiCurrency $multi_currency, Utils $utils ) {
		$this->multi_currency = $multi_currency;
		$this->utils          = $utils;
		$this->init_filters();

		add_action( 'init', [ $this, 'init_compatibility_classes' ], 11 );
	}

	/**
	 * Initializes our compatibility classes.
	 *
	 * @return void
	 */
	public function init_compatibility_classes() {
		$compatibility_classes[] = new WooCommerceBookings( $this->multi_currency, $this->utils, $this->multi_currency->get_frontend_currencies() );
		$compatibility_classes[] = new WooCommerceFedEx( $this->multi_currency, $this->utils );
		$compatibility_classes[] = new WooCommerceProductAddOns( $this->multi_currency, $this->utils );
		$compatibility_classes[] = new WooCommerceSubscriptions( $this->multi_currency, $this->utils );
		$compatibility_classes[] = new WooCommerceUPS( $this->multi_currency, $this->utils );
	}

	/**
	 * Checks to see if the if the selected currency needs to be overridden.
	 *
	 * @return mixed Three letter currency code or false if not.
	 */
	public function override_selected_currency() {
		return apply_filters( self::FILTER_PREFIX . 'override_selected_currency', false );
	}

	/**
	 * Checks to see if the widgets should be hidden.
	 *
	 * @return bool False if it shouldn't be hidden, true if it should.
	 */
	public function should_hide_widgets(): bool {
		return apply_filters( self::FILTER_PREFIX . 'should_hide_widgets', false );
	}

	/**
	 * Checks to see if the coupon's amount should be converted.
	 *
	 * @param object $coupon Coupon object to test.
	 *
	 * @return bool True if it should be converted.
	 */
	public function should_convert_coupon_amount( $coupon = null ): bool {
		if ( ! $coupon ) {
			return true;
		}

		return apply_filters( self::FILTER_PREFIX . 'should_convert_coupon_amount', true, $coupon );
	}

	/**
	 * Checks to see if the product's price should be converted.
	 *
	 * @param object $product Product object to test.
	 *
	 * @return bool True if it should be converted.
	 */
	public function should_convert_product_price( $product = null ): bool {
		if ( ! $product ) {
			return true;
		}

		return apply_filters( self::FILTER_PREFIX . 'should_convert_product_price', true, $product );
	}

	/**
	 * Determines if the store currency should be returned or not.
	 *
	 * @return bool
	 */
	public function should_return_store_currency(): bool {
		return apply_filters( self::FILTER_PREFIX . 'should_return_store_currency', false );
	}

	/**
	 * This filter is called when the best sales day logic is called. We use it to add another filter which will
	 * convert the order prices used in this inbox notification.
	 *
	 * @param bool $arg Whether or not the best sales day logic should execute. We will just return this as is to
	 * respect the existing behaviour.
	 *
	 * @return bool
	 */
	public function attach_order_modifier( $arg ) {
		// Attach our filter to modify the order prices.
		add_filter( 'woocommerce_order_query', [ $this, 'convert_order_prices' ] );

		// This will be a bool value indication whether the best day logic should be run. Let's just return it as is.
		return $arg;
	}

	/**
	 * When a request is made by the "Best Sales Day" Inbox notification, we want to hook into this and convert
	 * the order totals to the store default currency.
	 *
	 * @param WC_Order[]|WC_Order_Refund[] $results The results returned by the orders query.
	 *
	 * @return array
	 */
	public function convert_order_prices( $results ): array {
		$backtrace_calls = [
			'Automattic\WooCommerce\Admin\Notes\NewSalesRecord::sum_sales_for_date',
			'Automattic\WooCommerce\Admin\Notes\NewSalesRecord::possibly_add_note',
		];

		// If the call we're expecting isn't in the backtrace, then just do nothing and return the results.
		if ( ! $this->utils->is_call_in_backtrace( $backtrace_calls ) ) {
			return $results;
		}

		$default_currency = $this->multi_currency->get_default_currency();
		if ( ! $default_currency ) {
			return $results;
		}

		foreach ( $results as $order ) {
			if ( ! $order ||
				$order->get_currency() === $default_currency->get_code() ||
				! $order->get_meta( '_wcpay_multi_currency_order_exchange_rate', true ) ||
				$order->get_meta( '_wcpay_multi_currency_order_default_currency', true ) !== $default_currency->get_code()
			) {
				continue;
			}

			$exchange_rate = $order->get_meta( '_wcpay_multi_currency_order_exchange_rate', true );
			$order->set_total( number_format( $order->get_total() * ( 1 / $exchange_rate ), wc_get_price_decimals() ) );
		}

		remove_filter( 'woocommerce_order_query', [ $this, 'convert_order_prices' ] );

		return $results;
	}

	/**
	 * Initializes our filters for compatibility.
	 *
	 * @return void
	 */
	private function init_filters() {
		if ( defined( 'DOING_CRON' ) ) {
			add_filter( 'woocommerce_admin_sales_record_milestone_enabled', [ $this, 'attach_order_modifier' ] );
		}
	}
}
