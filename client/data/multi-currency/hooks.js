/** @format */

/**
 * External dependencies
 */
import { useSelect } from '@wordpress/data';
import { STORE_NAME } from '../constants';

export const useAvailableCurrencies = () =>
	useSelect( ( select ) => {
		const { getAvailableCurrencies, isResolving } = select( STORE_NAME );

		return {
			available: getAvailableCurrencies(),
			isLoading: isResolving( 'getAvailableCurrencies', [] ),
		};
	}, [] );

export const useEnabledCurrencies = () =>
	useSelect( ( select ) => {
		const { getEnabledCurrencies, isResolving } = select( STORE_NAME );

		return {
			enabled: getEnabledCurrencies(),
			isLoading: isResolving( 'getEnabledCurrencies', [] ),
		};
	}, [] );