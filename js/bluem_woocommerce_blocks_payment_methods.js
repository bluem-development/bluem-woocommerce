(function () {
    if (!window.wc || !window.wc.wcBlocksRegistry || !window.wc.wcSettings || !window.wp || !window.wp.element) {
        return;
    }

    var registerPaymentMethod = window.wc.wcBlocksRegistry.registerPaymentMethod;
    var getSetting = window.wc.wcSettings.getSetting;
    var element = window.wp.element;
    var createElement = element.createElement;
    var useEffect = element.useEffect;
    var useState = element.useState;

    var gatewayIds = [
        'bluem_payments_ideal',
        'bluem_payments_paypal',
        'bluem_payments_creditcard',
        'bluem_payments_sofort',
        'bluem_payments_cartebancaire',
        'bluem_mandates'
    ];

    function PaymentMethodLabel(props) {
        var components = props.components || {};
        var settings = props.settings || {};
        var label = settings.title || props.name;

        if (components.PaymentMethodLabel) {
            return createElement(components.PaymentMethodLabel, { text: label });
        }

        return createElement('span', null, label);
    }

    function Content(props) {
        var settings = props.settings || {};
        var gatewayId = props.gatewayId || '';
        var bics = settings.bics || [];
        var useDebtorWallet = !!settings.use_debtor_wallet;
        var state = useState('');
        var bic = state[0];
        var setBic = state[1];

        useEffect(function () {
            if (!props.eventRegistration || !props.eventRegistration.onPaymentProcessing) {
                return undefined;
            }

            return props.eventRegistration.onPaymentProcessing(function () {
                if (useDebtorWallet && !bic) {
                    return {
                        type: props.emitResponse.responseTypes.ERROR,
                        message: settings.bic_required_message || 'Please select a bank.'
                    };
                }

                var paymentMethodData = {};
                if (bic) {
                    if (gatewayId === 'bluem_mandates') {
                        paymentMethodData.bluem_mandates_bic = bic;
                    } else if (gatewayId === 'bluem_payments_ideal') {
                        paymentMethodData.bluem_payments_ideal_bic = bic;
                    }
                }

                return {
                    type: props.emitResponse.responseTypes.SUCCESS,
                    meta: { paymentMethodData: paymentMethodData }
                };
            });
        }, [bic, props.emitResponse, props.eventRegistration, settings.bic_required_message, useDebtorWallet]);

        var children = [];
        if (settings.description) {
            children.push(createElement('p', { key: 'description' }, settings.description));
        }

        if (bics.length && useDebtorWallet) {
            children.push(createElement(
                'label',
                { key: 'bic-label', className: 'bluem-blocks-bic-field' },
                createElement('span', null, settings.bic_label || 'Select a bank:'),
                createElement(
                    'select',
                    {
                        value: bic,
                        onChange: function (event) { setBic(event.target.value); },
                        required: true
                    },
                    createElement('option', { value: '' }, settings.bic_placeholder || 'Select a bank'),
                    bics.map(function (bank) {
                        return createElement('option', { key: bank.id, value: bank.id }, bank.name);
                    })
                )
            ));
        }

        return createElement('div', { className: 'bluem-blocks-payment-method' }, children);
    }

    gatewayIds.forEach(function (gatewayId) {
        var settings = getSetting(gatewayId + '_data', {});
        if (!settings || !settings.title) {
            return;
        }

        registerPaymentMethod({
            name: gatewayId,
            label: createElement(PaymentMethodLabel, {
                name: gatewayId,
                settings: settings
            }),
            content: createElement(Content, { settings: settings, gatewayId: gatewayId }),
            edit: createElement(Content, { settings: settings, gatewayId: gatewayId }),
            ariaLabel: settings.title,
            canMakePayment: function () { return true; },
            supports: { features: settings.supports || ['products'] }
        });
    });
}());
