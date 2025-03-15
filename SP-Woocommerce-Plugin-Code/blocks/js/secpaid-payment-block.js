/**
 * SecPaid Payment Block Integration
 */
(function() {
    const { registerPaymentMethod } = window.wc.wcBlocksRegistry;
    const { createElement } = window.wp.element;
    const { __ } = window.wp.i18n;
    
    const settings = window.wc.wcSettings.getSetting('secpaid_payment_data', {});
    
    const defaultLabel = __('SecPaid Secure Payment', 'woocommerce-secpaid-payment-gateway');
    const defaultDescription = __('Pay securely with SecPaid', 'woocommerce-secpaid-payment-gateway');
    
    const Label = () => {
        return createElement('span', {}, settings.title || defaultLabel);
    };
    
    const Content = () => {
        return createElement('div', {}, [
            createElement('p', { key: 'description' }, settings.description || defaultDescription),
            createElement('div', { 
                key: 'icons',
                className: 'secpaid-payment-method-icons'
            }, Object.entries(settings.icons || {}).map(([name, url]) => 
                createElement('img', { 
                    key: name,
                    src: url,
                    alt: name + ' ' + __('Logo', 'woocommerce-secpaid-payment-gateway'),
                    style: { maxWidth: '50px', marginRight: '5px' }
                })
            ))
        ]);
    };
    
    const secpaidPaymentMethod = {
        name: 'secpaid_payment',
        label: createElement(Label),
        content: createElement(Content),
        edit: createElement(Content),
        canMakePayment: () => true,
        ariaLabel: settings.title || defaultLabel,
        supports: {
            features: settings.supports || ['products'],
        },
    };
    
    registerPaymentMethod(secpaidPaymentMethod);
})();