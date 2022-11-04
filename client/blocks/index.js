/**
 * Internal dependencies
 */

import { __ } from '@wordpress/i18n';
import { registerPaymentMethod } from '@woocommerce/blocks-registry';
import { getSetting } from '@woocommerce/settings';
import { decodeEntities } from '@wordpress/html-entities';

const PAYMENT_METHOD_NAME = 'fawaterak';
const settings = getSetting('fawaterak_data', null);
/**
 * @typedef {import('@woocommerce/type-defs/registered-payment-method-props').RegisteredPaymentMethodProps} RegisteredPaymentMethodProps
 */

/**
 * Content component
 */
const Content = () => < div > { decodeEntities(settings.description || '') } < /div>;

const fawaterakPaymentMethod = {
    name: PAYMENT_METHOD_NAME,
    label: ( <
        >
        { decodeEntities(__('Fawaterak', 'woo-gutenberg-products-block')) } <
        img src = { scriptVars.imageUrl }
        alt = {
            decodeEntities(
                settings.title || __('Fawaterak', 'woo-gutenberg-products-block')
            )
        }
        /> < / >
    ),
    placeOrderButtonLabel: __(
        'Proceed to Fawatrak',
        'woo-gutenberg-products-block'
    ),
    content: < Content / > ,
    edit: < Content / > ,
    canMakePayment: () => true,
    ariaLabel: decodeEntities(
        settings.title ||
        __('Payment via Fawaterak', 'woo-gutenberg-products-block')
    ),
    supports: {
        features: settings.supports ? ? []
    }
};

registerPaymentMethod(fawaterakPaymentMethod);