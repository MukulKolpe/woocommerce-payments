/** @format */

/**
 * External dependencies
 */
import { React, useState, useEffect } from 'react';
import { __, sprintf } from '@wordpress/i18n';
import {
	PaymentRequestButtonElement,
	useStripe,
} from '@stripe/react-stripe-js';

/**
 * Internal dependencies
 */
import { shouldUseGooglePayBrand } from 'payment-request/utils';
import InlineNotice from 'components/inline-notice';
import {
	usePaymentRequestButtonSize,
	usePaymentRequestButtonTheme,
	usePaymentRequestButtonType,
} from '../../data';

/**
 * stripePromise is used to pass into <Elements>'s stripe props.
 * The stripe prop in <Elements> can't be change once passed in.
 * Keeping this outside of <PaymentRequestButtonPreview> so that
 * re-rendering does not change it.
 */

const BrowserHelpText = () => {
	let browser = 'Google Chrome';
	let paymentMethodName = 'Google Pay';

	if ( shouldUseGooglePayBrand() ) {
		browser = 'Safari';
		paymentMethodName = 'Apple Pay';
	}

	return (
		<p className="payment-method-settings__preview-help-text">
			{ sprintf(
				__(
					/* translators: %1: Payment method name %2: Browser name. */
					'To preview the %1$s button, view this page in the %2$s browser.',
					'woocommerce-payments'
				),
				paymentMethodName,
				browser
			) }
		</p>
	);
};

const buttonSizeToPxMap = {
	default: 40,
	medium: 48,
	large: 56,
};

const PaymentRequestButtonPreview = () => {
	const stripe = useStripe();
	const [ paymentRequest, setPaymentRequest ] = useState();
	const [ isLoading, setIsLoading ] = useState( true );
	const [ buttonType ] = usePaymentRequestButtonType();
	const [ size ] = usePaymentRequestButtonSize();
	const [ theme ] = usePaymentRequestButtonTheme();

	useEffect( () => {
		if ( ! stripe ) {
			return;
		}

		// Create a preview for payment button. The label and its total are placeholders.
		const stripePaymentRequest = stripe.paymentRequest( {
			country: 'US',
			currency: 'usd',
			total: {
				label: __( 'Total', 'woocommerce-payments' ),
				amount: 99,
			},
			requestPayerName: true,
			requestPayerEmail: true,
		} );

		// Check the availability of the Payment Request API.
		stripePaymentRequest.canMakePayment().then( ( result ) => {
			if ( result ) {
				setPaymentRequest( stripePaymentRequest );
			}
			setIsLoading( false );
		} );
	}, [ stripe, setPaymentRequest, setIsLoading ] );

	/**
	 * If stripe is loading, then display nothing.
	 * If stripe finished loading but payment request button failed to load (null), display info section.
	 * If stripe finished loading and payment request button loads, display the button.
	 */
	if ( isLoading ) {
		return null;
	}

	if ( ! paymentRequest ) {
		return (
			<InlineNotice status="info" isDismissible={ false }>
				{ __(
					'To preview the buttons, ' +
						'ensure your device is configured to accept Apple Pay or Google Pay, ' +
						'and view this page using the Safari or Chrome browsers.',
					'woocommerce-payments'
				) }
			</InlineNotice>
		);
	}

	return (
		<>
			<div className="payment-method-settings__preview">
				<PaymentRequestButtonElement
					key={ `${ buttonType }-${ theme }-${ size }` }
					onClick={ ( e ) => {
						e.preventDefault();
					} }
					options={ {
						paymentRequest: paymentRequest,
						style: {
							paymentRequestButton: {
								type: buttonType,
								theme: theme,
								height: `${
									buttonSizeToPxMap[ size ] ||
									buttonSizeToPxMap.default
								}px`,
							},
						},
					} }
				/>
			</div>
			<BrowserHelpText />
		</>
	);
};

export default PaymentRequestButtonPreview;
