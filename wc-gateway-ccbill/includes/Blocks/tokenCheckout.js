( function() {
    
    // *********** Global ***************
    
    var registerPaymentMethod = window.wc && wc.wcBlocksRegistry && wc.wcBlocksRegistry.registerPaymentMethod;
      
    if (!registerPaymentMethod) { 
        console.log('CCBill payment method failure: registerPaymentMethod is null');
        return; 
    }
    
    // Get server-provided settings from your PHP class `get_payment_method_data()`
    var getSetting = window.wc && wc.wcSettings && wc.wcSettings.getSetting;
    var settings = getSetting ? getSetting( 'wc-gateway-ccbill_data', {} ) : {};
    
    var debug = settings.debug;
    
    window.ccbillDebug = debug;
    
    // React shim provided by WordPress (no JSX needed)
    var el = window.wp && window.wp.element;
    if (!el) { return; }
    
    var useState = el.useState;
    var useMemo = el.useMemo;
    var useEffect = el.useEffect;
    
    // *********** Helpers ***************
    
    function ccbillDebugLog(message)
    {
        if (window.ccbillDebug == true)
            console.log(message);
    }
    
    window.ccbillDebugLog = ccbillDebugLog;
    
    function pad2(n) {
        return n < 10 ? '0' + n : '' + n;
    }

    function luhnOk(num) {
        // very light, optional Luhn — ignores spaces/dashes
        var s = (num || '').replace(/\D/g, '');
        var sum = 0, alt = false;
        for (var i = s.length - 1; i >= 0; i--) {
        var n = parseInt(s.charAt(i), 10);
        if (alt) {
            n *= 2;
            if (n > 9) n -= 9;
        }
        sum += n;
        alt = !alt;
        }
        return s.length >= 12 && sum % 10 === 0;
    }
    
    function yearsList() {
        var start = new Date().getFullYear();
        var list = [];
        for (var i = 0; i <= 10; i++) list.push(start + i);
        return list;
    }
    
    function onlyDigits(s) {
        return (s || '').replace(/\D/g, '');
    }
    
    // *********** UI Components ***************
    
    function Label( props ) {
        // console.log('Label hit');
        // Blocks expects an element; return a simple text label
        return el.createElement( props.components.PaymentMethodLabel, {
        text: (settings && settings.title) || 'Pay with your credit card via CCBill'
        } );
    }
    
    function FieldWrapper(children, style) {
        return el.createElement(
        'div',
        { style: Object.assign({ marginBottom: '12px' }, style || {}) },
        children
        );
    }
    
    function TextLabel(text, htmlFor) {
        return el.createElement(
        'label',
        {
            htmlFor: htmlFor,
            style: { display: 'block', marginBottom: 4, fontWeight: 600 },
        },
        text
        );
    }
    
    function Input(props) {
        // Lightweight unstyled input
        return el.createElement(
        'input',
        Object.assign(
            {
            style: {
                width: '100%',
                padding: '10px',
                border: '1px solid #ddd',
                borderRadius: 6,
                fontSize: '14px',
            },
            },
            props
        )
        );
    }
      
    function Select(props) {
        return el.createElement(
            'select',
            Object.assign(
            {
                style: {
                width: '100%',
                padding: '10px',
                border: '1px solid #ddd',
                borderRadius: 6,
                fontSize: '14px',
                background: '#fff',
                },
            },
            props
            ),
            props.children
        );
    }
    
    // We’ll subscribe once to the checkout “onPaymentSetup” event.
    var subscribedToPaymentSetup = false;
    
    // The renderless -> rendering Content component
    function Content(props) {
        
        // console.log('Content hit');
        
        var { eventRegistration, emitResponse } = props;
        var { onPaymentProcessing } = eventRegistration;
        
        var tempYear = (new Date()).getFullYear();
        
        if (!subscribedToPaymentSetup) {
            subscribedToPaymentSetup = true;
            
            var onPaymentSetup = props.eventRegistration.onPaymentSetup;
            var emitResponse = props.emitResponse;
            
            var [number, setNumber] = useState('');
            var [expMonth, setExpMonth] = useState('');
            var [expYear, setExpYear] = useState('');
            var [cvv, setCvv] = useState('');
            var [error, setError] = useState('');
            
            var months = useMemo(function () {
                
                var m = [];
                
                for (var i = 1; i <= 12; i++) 
                    m.push(pad2(i));
                    
                return m;
            }, []);
            
            // console.log('months set: ' + JSON.stringify(months));
            
            var years = useMemo(yearsList, []);
            
            useEffect(function () {
                
                ccbillDebugLog('useEffect hit');
                
                var unsubscribe = onPaymentSetup( async () => {
                    try {
                        
                        ccbillDebugLog('onPaymentSetup2 hit');
                        
                        var nameOnCard = document.getElementById('wc-gateway-ccbill-cc-name-on-card').value;
                        var number = document.getElementById('wc-gateway-ccbill-cc-number').value;
                        var m = parseInt(document.getElementById('wc-gateway-ccbill-cc-exp-month').value, 10);
                        var y = parseInt(document.getElementById('wc-gateway-ccbill-cc-exp-year').value, 10);
                        var cvv = document.getElementById('wc-gateway-ccbill-cc-cvv').value;
                        
                        ccbillDebugLog('number: ' + number + '; m: ' + m + '; y: ' + y + '; cvv = ' + cvv);
                        
                        // Basic validation
                        var cc = onlyDigits(number);
                        
                        if (!luhnOk(cc)) {
                            setError('Please enter a valid card number.');
                            return {
                                type: emitResponse.responseTypes.ERROR,
                                message: 'Invalid card number.',
                            };
                        }                    
                        
                        if (!m || m < 1 || m > 12) {
                            setError('Please select a valid expiration month.');
                            return {
                                type: emitResponse.responseTypes.ERROR,
                                message: 'Invalid expiration month.',
                            };
                        }
                        if (!y) {
                            setError('Please select a valid expiration year.');
                            return {
                                type: emitResponse.responseTypes.ERROR,
                                message: 'Invalid expiration year.',
                            };
                        }
                        // Expiry in the past?
                        var now = new Date();
                        var thisMonth = now.getMonth() + 1;
                        var thisYear = now.getFullYear();
                        if (y < thisYear || (y === thisYear && m < thisMonth)) {
                            setError('Expiration date is in the past.');
                            return {
                                type: emitResponse.responseTypes.ERROR,
                                message: 'Card is expired.',
                            };
                        }
                        var cv = onlyDigits(cvv);
                        if (cv.length < 3 || cv.length > 4) {
                            setError('Please enter a valid CVV.');
                            return {
                                type: emitResponse.responseTypes.ERROR,
                                message: 'Invalid CVV.',
                            };
                        }
                        
                        setError('');
                        
                        // Create the payment token --------------------
                        
                        
                        window.ccbillTokenRequestProcessing = true;
                        
                        await window.ccbillFunctions.getPaymentToken(
                        (data) => {
                            ccbillDebugLog("onPaymentSetup2 | Payment token received successfully");
                            ccbillDebugLog("onPaymentSetup2 | Payment token received successfully 2");
                            
                            if (data)
                                ccbillDebugLog("onPaymentSetup2 | data is not null");
                            else
                                ccbillDebugLog("onPaymentSetup2 | data is null");
                            
                            ccbillDebugLog("onPaymentSetup2 | data: " + JSON.stringify(data));
                            window.ccbillTokenRequestProcessing = false;
                            window.ccbillTokenResponseSuccess = true;
                            window.ccbillTokenResponseData = data;
                            
                        },
                        (error) => {
                            ccbillDebugLog("onPaymentSetup2 | An error occurred while retrieving the payment token");
                            window.ccbillTokenRequestProcessing = false;
                            window.ccbillTokenResponseSuccess = false;
                            window.ccbillTokenResponseError = error;
                        });
                        
                        for (var i = 0; i < 500 && window.ccbillTokenRequestProcessing == true; i++) {
                            ccbillDebugLog('Waiting for token creation to complete ' + i);
                            await window.ccbillFunctions.sleep (100);
                        }
                        
                        ccbillDebugLog("onPaymentSetup2 token request complete.  success: " + window.ccbillTokenResponseSuccess);
                        ccbillDebugLog("onPaymentSetup2 token request complete.  response: " + JSON.stringify(window.ccbillTokenResponseData));
                        
                        if (window.ccbillTokenResponseSuccess) {
                            
                            var tokenResponse = window.ccbillTokenResponseData;
                            var token = tokenResponse.paymentTokenId;
                            var subscriptionId = tokenResponse.subscriptionId;
                            
                            ccbillDebugLog('preparing to emit response with token: ' + token + '; subscriptionId: ' + subscriptionId);
                            
                            return {
                                type: emitResponse.responseTypes.SUCCESS,
                                meta: { paymentMethodData: { token, subscriptionId } },
                            };
                        }
                        else {
                            return { type: emitResponse.responseTypes.ERROR, message: 'Authorization failed.' };
                        }
                    } catch (e) {
                        return { type: emitResponse.responseTypes.ERROR, message: e?.message || 'Tokenization error.' };
                    }
                } );
                
                return () => unsubscribe();
                
            }, [number, expMonth, expYear, cvv, onPaymentSetup, emitResponse, props.shouldSavePayment]);            
        }
    
        // Layout
        var row = {
        display: 'grid',
        gridTemplateColumns: '1fr 1fr 1fr',
        gap: '12px',
        };
    
        return el.createElement(
        'div',
        null,
        settings && settings.description
            ? el.createElement('div', {
                dangerouslySetInnerHTML: { __html: settings.description },
                style: { marginBottom: '12px' },
            })
            : null,
    
        FieldWrapper([
            TextLabel('Name on Card', 'wc-gateway-ccbill-cc-name-on-card'),
            Input({
                id: 'wc-gateway-ccbill-cc-name-on-card',
                'data-ccbill': 'nameOnCard',
                value: number,
                inputMode: 'numeric',
                autoComplete: 'cardholder-name',
                placeholder: '',
                /* -- No formatting
                onInput: function (e) {
                    
                },
                */
                style: { 'width': '80%', 'padding': '10px', 'border': '1px solid rgb(221, 221, 221)', 'border-radius': '6px', 'font-size': '14px' },
            }),
        ]),
        
        FieldWrapper([
            TextLabel('Card Number', 'wc-gateway-ccbill-cc-number'),
            Input({
                id: 'wc-gateway-ccbill-cc-number',
                'data-ccbill': 'cardNumber',
                value: number,
                inputMode: 'numeric',
                autoComplete: 'cc-number',
                placeholder: '',
                onInput: function (e) {
                    // simple formatting: keep digits, group by 4 visually
                    var digits = onlyDigits(e.target.value).slice(0, 19);
                    var groups = digits.match(/.{1,4}/g);
                    e.target.value = groups ? groups.join('') : ''; //.join(' ') : '';
                    /* setNumber(e.target.value); */
                },
                style: { 'width': '80%', 'padding': '10px', 'border': '1px solid rgb(221, 221, 221)', 'border-radius': '6px', 'font-size': '14px' },
            }),
        ]),
        FieldWrapper([
            Input({
                id: 'wc-gateway-ccbill-ccbill-token',
                type: 'hidden',
                'data-ccbill': 'ccbillToken',
                value: '',
            }),
        ]),
        FieldWrapper([
            Input({
                id: 'wc-gateway-ccbill-first-name',
                type: 'hidden',
                'data-ccbill': 'firstName',
                value: '',
            }),
        ]),
        FieldWrapper([
            Input({
                id: 'wc-gateway-ccbill-last-name',
                type: 'hidden',
                'data-ccbill': 'lastName',
                value: '',
            }),
        ]),
        FieldWrapper([
            Input({
                id: 'wc-gateway-ccbill-address1',
                type: 'hidden',
                'data-ccbill': 'address1',
                value: '',
            }),
        ]),
        FieldWrapper([
            Input({
                id: 'wc-gateway-ccbill-address2',
                type: 'hidden',
                'data-ccbill': 'address2',
                value: '',
            }),
        ]),
        FieldWrapper([
            Input({
                id: 'wc-gateway-ccbill-city',
                type: 'hidden',
                'data-ccbill': 'city',
                value: '',
            }),
        ]),
        FieldWrapper([
            Input({
                id: 'wc-gateway-ccbill-country',
                type: 'hidden',
                'data-ccbill': 'country',
                value: '',
            }),
        ]),
        FieldWrapper([
            Input({
                id: 'wc-gateway-ccbill-state',
                type: 'hidden',
                'data-ccbill': 'state',
                value: '',
            }),
        ]),
        FieldWrapper([
            Input({
                id: 'wc-gateway-ccbill-postcode',
                type: 'hidden',
                'data-ccbill': 'postalCode',
                value: '',
            }),
        ]),
        FieldWrapper([
            Input({
                id: 'wc-gateway-ccbill-phone',
                type: 'hidden',
                'data-ccbill': 'phoneNumber',
                value: '',
            }),
        ]),
        FieldWrapper([
            Input({
                id: 'wc-gateway-ccbill-email',
                type: 'hidden',
                'data-ccbill': 'email',
                value: '',
            }),
        ]),
        FieldWrapper([
            Input({
                id: 'wc-gateway-ccbill-ip-address',
                type: 'hidden',
                'data-ccbill': 'ipAddress',
                value: '',
            }),
        ]),
        FieldWrapper([
            Input({
                id: 'wc-gateway-ccbill-currency-code',
                type: 'hidden',
                'data-ccbill': 'currencyCode',
                value: '',
            }),
        ]),
    
        el.createElement(
            'div',
            { style: row },
            FieldWrapper([
               TextLabel('Exp. Month', 'wc-gateway-ccbill-cc-exp-month'),
                 el.createElement(
                   Select,
                   {
                     id: 'wc-gateway-ccbill-cc-exp-month',
                     'data-ccbill': 'expMonth',
                     value: expMonth,
                     autoComplete: 'cc-exp-month',
                     onChange: function (e) { /* setExpMonth(e.target.value); */ },
                     style: { 'width': '80%', 'padding': '10px', 'border': '1px solid rgb(221, 221, 221)', 'border-radius': '6px', 'font-size': '14px' },
                   },
                   el.createElement('option', { key: '', value: '', default: true, disabled: true }, 'MM'),
                   el.createElement('option', { key: 1, value: '01' }, '01 - January'),
                   el.createElement('option', { key: 2, value: '02' }, '02 - February'),
                   el.createElement('option', { key: 3, value: '03' }, '03 - March'),
                   el.createElement('option', { key: 4, value: '04' }, '04 - April'),
                   el.createElement('option', { key: 5, value: '05' }, '05 - May'),
                   el.createElement('option', { key: 6, value: '06' }, '06 - June'),
                   el.createElement('option', { key: 7, value: '07' }, '07 - July'),
                   el.createElement('option', { key: 8, value: '08' }, '08 - August'),
                   el.createElement('option', { key: 9, value: '09' }, '09 - September'),
                   el.createElement('option', { key: 10, value: '10' }, '10 - October'),
                   el.createElement('option', { key: 11, value: '11' }, '11 - November'),
                   el.createElement('option', { key: 12, value: '12' }, '12 - December')
                   
                 ),
            ]),
            FieldWrapper([
              TextLabel('Exp. Year', 'wc-gateway-ccbill-cc-exp-year'),
              el.createElement(
                Select,
                {
                  id: 'wc-gateway-ccbill-cc-exp-year',
                  'data-ccbill': 'expYear',
                  value: expYear,
                  autoComplete: 'cc-exp-year',
                  onChange: function (e) { /* setExpYear(e.target.value); */ },
                   style: { 'width': '80%', 'padding': '10px', 'border': '1px solid rgb(221, 221, 221)', 'border-radius': '6px', 'font-size': '14px' },
                },
                el.createElement('option', { key: '', value: '', default: true, disabled: true }, 'YYYY'),
                el.createElement('option', { key: '' + tempYear, value: '' + tempYear }, '' + tempYear++),
                el.createElement('option', { key: '' + tempYear, value: '' + tempYear }, '' + tempYear++),
                el.createElement('option', { key: '' + tempYear, value: '' + tempYear }, '' + tempYear++),
                el.createElement('option', { key: '' + tempYear, value: '' + tempYear }, '' + tempYear++),
                el.createElement('option', { key: '' + tempYear, value: '' + tempYear }, '' + tempYear++),
                el.createElement('option', { key: '' + tempYear, value: '' + tempYear }, '' + tempYear++),
                el.createElement('option', { key: '' + tempYear, value: '' + tempYear }, '' + tempYear++),
                el.createElement('option', { key: '' + tempYear, value: '' + tempYear }, '' + tempYear++),
                el.createElement('option', { key: '' + tempYear, value: '' + tempYear }, '' + tempYear++),
                el.createElement('option', { key: '' + tempYear, value: '' + tempYear }, '' + tempYear++),
                el.createElement('option', { key: '' + tempYear, value: '' + tempYear }, '' + tempYear++),
              ),
            ]),
            FieldWrapper([
                TextLabel('CVV', 'wc-gateway-ccbill-cc-cvv'),
                Input({
                id: 'wc-gateway-ccbill-cc-cvv',
                /* type: 'password', */
                'data-ccbill': 'cvv2',
                value: cvv,
                inputMode: 'numeric',
                autoComplete: 'cc-csc',
                placeholder: '•••',
                maxLength: 4,
                onInput: function (e) {
                    var d = onlyDigits(e.target.value).slice(0, 4);
                    e.target.value = d;
                    /*setCvv(d); */
                },
                 style: { 'width': '40%', 'padding': '10px', 'border': '1px solid rgb(221, 221, 221)', 'border-radius': '6px', 'font-size': '14px' },
                }),
            ]),
        ),
    
        error
            ? el.createElement(
                'div',
                { style: { color: '#b00020', fontSize: 13, marginTop: 4 } },
                error
            )
            : null
        );
    }
    
    ccbillDebugLog('integration method: ' + settings.integration_method);
    
    registerPaymentMethod({
        name: 'wc_gateway_ccbill', // MUST match your PHP integration `$name`
        label: el.createElement(Label, null),
        ariaLabel: window.wp.htmlEntities.decodeEntities( settings.description || ''),
        content: el.createElement(Content, null),
        edit: el.createElement(Content, null),
        canMakePayment: function () {
          return true; // add currency/country logic if needed
        },
        supports: {
          features: (settings && settings.supports) || ['products'],
          showSavedCards: false,
          showSaveOption: false, // TODO
        },
      });
    
    ccbillDebugLog('settings: ' + JSON.stringify(wcSettings));
        
    var ccbillFunctions = window.ccbillFunctions;
    
    if (ccbillFunctions == null)
    {
        ccbillDebugLog('ccbill functions is null');
    }
    else
    {
        ccbillDebugLog('ccbill functions is loaded.');
    }

} )();