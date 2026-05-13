( function() {

    window.ccbillFunctions = {
        
        sleep: function ( ms ) {
            return new Promise(resolve => setTimeout(resolve, ms));
        },
        getCCBillFormElement: function ( attributeName ) {
            var querySelectorValue = '[data-ccbill="' + attributeName + '"]';            
            var targetElement = document.querySelector(querySelectorValue);
            
            if (!targetElement)
                this.ccbillDebugLog('getCCBillFormElement | Unable to locate element with query selector value: ' + querySelectorValue);
            
            return document.querySelector(querySelectorValue);
        },
        ccbillDebugLog: function ( message )
        {
            if (window.ccbillDebug == true)
                console.log(message);
        },
        setCCBillFormValue: function ( attributeName, value ) {
            var targetElement = this.getCCBillFormElement(attributeName);
            
            if (targetElement == null)
            {
                this.ccbillDebugLog('setCCBillFormValue | An error occurred while attempting to locate a field input with data attribute of ' + attributeName);
                
                return;
            }
            
            targetElement.value = value;
            
            var elementValue = this.getCCBillFormValue(attributeName);
        },
        
        getCCBillFormValue: function ( attributeName ) {            
            var targetElement = this.getCCBillFormElement(attributeName);
            
            if (targetElement == null)
                return null;
            
            return targetElement.value;
        },
        
        getPaymentToken : function(customSuccessCallback, customFailureCallback) {
            
            var registerPaymentMethod = window.wc && wc.wcBlocksRegistry && wc.wcBlocksRegistry.registerPaymentMethod;
            
            if (!registerPaymentMethod) { 
                this.ccbillDebugLog('Failure: registerPaymentMethod is null');
                return; 
            }
            
            var getSetting = window.wc && wc.wcSettings && wc.wcSettings.getSetting;
            
            if (getSetting == null)
                this.ccbillDebugLog('getSetting is null');
            else
                this.ccbillDebugLog('getSetting is not null');
            
            var settings = getSetting ? getSetting( 'wc-gateway-ccbill_data', {} ) : {};            
            // var settings = wc.wcSettings.allSettings.paymentMethodData['wc-gateway-ccbill'];
            
            this.ccbillDebugLog('ccbillFunctions.js | oauth token: ' + JSON.stringify(settings.oauthToken));
            
            if (window.gettingToken || window.getPaymentTokenDisabled)
                return;
            else
                window.gettingToken = true;
            
            // Set CCBill form fields from billing fields
            try {
                
                this.ccbillDebugLog('Setting CCBill form fields...');            
                
                var billingFieldPrefix = 'billing_';
                
                var testField = document.getElementById(billingFieldPrefix + 'first_name');
                
                // Use a different field prefix if this is blocks checkout
                if (testField == null) {
                    this.ccbillDebugLog(billingFieldPrefix + 'first_name is null. ');
                    billingFieldPrefix = 'billing-';
                    testField = document.getElementById(billingFieldPrefix + 'first_name');
                }
                
                // If billing fields are not present (box checked to use shipping address for billing), try shipping
                if (testField == null) {
                    this.ccbillDebugLog(billingFieldPrefix + 'first_name is null.');
                    billingFieldPrefix = 'shipping-';
                    testField = document.getElementById(billingFieldPrefix + 'first_name');
                }
                
                var checkoutData = wc.wcSettings.allSettings.checkoutData;
                
                if (testField == null) {
                    this.ccbillDebugLog("testField is still null and we've tried everything.  Filling form fields from checkout data: " + JSON.stringify(checkoutData));   
                    this.ccbillDebugLog('checkoutData.first_name = ' + checkoutData.first_name);   
                    
                    // Set field data
                    this.setCCBillFormValue('firstName', checkoutData.first_name);
                    this.setCCBillFormValue('lastName', checkoutData.last_name);
                    this.setCCBillFormValue('address1', checkoutData.address_1);
                    this.setCCBillFormValue('address2', checkoutData.address_2);
                    this.setCCBillFormValue('city', checkoutData.city);
                    this.setCCBillFormValue('country', checkoutData.country);
                    this.setCCBillFormValue('state', checkoutData.state);
                    this.setCCBillFormValue('postalCode', checkoutData.postcode);
                    this.setCCBillFormValue('phoneNumber', checkoutData.phone);
                    this.setCCBillFormValue('ipAdress', settings.userIpAddress);
                    this.setCCBillFormValue('currencyCode', settings.currencyCode);
                    
                    this.ccbillDebugLog('Form fields set.');
                      
                }
                else {
                    this.ccbillDebugLog('billing field prefix determined: ' + billingFieldPrefix);
                    
                    if (document.getElementById(billingFieldPrefix + 'email') != null)
                    {
                        document.getElementById('email').value = document.getElementById(billingFieldPrefix + 'email').value;
                    }
                    
                    var ccbillFirstNameElement = this.getCCBillFormElement('firstName');
                    
                    if (!ccbillFirstNameElement) {
                        this.ccbillDebugLog('getPaymentToken | ccbillFirstNameElement is null');
                    }
                    
                    if (ccbillFirstNameElement) {
                        this.ccbillDebugLog('getPaymentToken | ccbillFirstNameElement is found.  Setting form values...');
                    
                        var t = {};
                        
                        t.firstName = document.getElementById(billingFieldPrefix + 'first_name').value;
                        t.lastName = document.getElementById(billingFieldPrefix + 'last_name').value;
                        t.address1 = document.getElementById(billingFieldPrefix + 'address_1').value;
                        t.address2 = document.getElementById(billingFieldPrefix + 'address_2').value;
                        t.city = document.getElementById(billingFieldPrefix + 'city').value;
                        t.country = document.getElementById(billingFieldPrefix + 'country').value;
                        t.state = document.getElementById(billingFieldPrefix + 'state').value;
                        t.postalCode = document.getElementById(billingFieldPrefix + 'postcode').value;
                        t.phoneNumber = document.getElementById(billingFieldPrefix + 'phone').value;
                        t.email = document.getElementById('email').value;
                        t.ipAddress = settings.userIpAddress;
                        t.currencyCode = settings.currencyCode;
                        
                        this.ccbillDebugLog('getPaymentToken | setting form values: ' + JSON.stringify(t));
                    
                        // Set field data
                        this.setCCBillFormValue('firstName', t.firstName);
                        this.setCCBillFormValue('lastName', t.lastName);
                        this.setCCBillFormValue('address1', t.address1);
                        this.setCCBillFormValue('address2', t.address2);
                        this.setCCBillFormValue('city', t.city);
                        this.setCCBillFormValue('country', t.country);
                        this.setCCBillFormValue('state', t.state);
                        this.setCCBillFormValue('postalCode', t.postalCode);
                        this.setCCBillFormValue('phoneNumber', t.phoneNumber);
                        this.setCCBillFormValue('email', t.email);
                        this.setCCBillFormValue('ipAddress', t.ipAddress);
                        this.setCCBillFormValue('currencyCode', t.currencyCode);
                    }
                    
                    this.ccbillDebugLog('getPaymentToken | Form fields set.');
                    
                    this.ccbillDebugLog('getPaymentToken | firstName: ' + this.getCCBillFormValue('firstName'));
                    this.ccbillDebugLog('getPaymentToken | lastName: ' + this.getCCBillFormValue('lastName'));
                    this.ccbillDebugLog('getPaymentToken | address1: ' + this.getCCBillFormValue('address1'));
                    this.ccbillDebugLog('getPaymentToken | address2: ' + this.getCCBillFormValue('address2'));
                    this.ccbillDebugLog('getPaymentToken | city: ' + this.getCCBillFormValue('city'));
                    this.ccbillDebugLog('getPaymentToken | country: ' + this.getCCBillFormValue('country'));
                    this.ccbillDebugLog('getPaymentToken | state: ' + this.getCCBillFormValue('state'));
                    this.ccbillDebugLog('getPaymentToken | postalCode: ' + this.getCCBillFormValue('postalCode'));
                    this.ccbillDebugLog('getPaymentToken | phoneNumber: ' + this.getCCBillFormValue('phoneNumber'));
                    this.ccbillDebugLog('getPaymentToken | email: ' + this.getCCBillFormValue('email'));
                    this.ccbillDebugLog('getPaymentToken | ipAddress: ' + this.getCCBillFormValue('ipAddress'));
                    this.ccbillDebugLog('getPaymentToken | currencyCode: ' + this.getCCBillFormValue('currencyCode'));
                }
            }
            catch(ex){
                this.ccbillDebugLog('An error occurred while setting the form fields: ' + ex);
            }
            
            try {
                this.ccbillDebugLog('Retrieving a payment token...');
                this.ccbillDebugLog('Creating an instance of the CCBill widget with applicationId ' + settings.applicationId);
                
                var widget = new ccbill.CCBillAdvancedWidget(settings.applicationId);
                
                if (widget)
                    this.ccbillDebugLog("widget is not null");
                else
                    this.ccbillDebugLog("widget is null");
                
                const result = widget.createPaymentToken(settings.oauthToken, settings.clientAccnum, settings.clientSubacc);
                result.then(
                    (data) => {
                        return data.json();
                    },
                    (error) => {
                        this.ccbillDebugLog("An error occurred while retrieving the payment token");
                        return error.json();
                    }).then(json => {   
                            
                        this.ccbillDebugLog("Payment token received successfully");
                        
                        // Assign the token value to the form field
                        this.ccbillDebugLog("Assigning payment token value to form field: " + json.paymentTokenId);
                        
                        // Assign the token value to the form field
                        this.setCCBillFormValue('ccbillToken', json.paymentTokenId);
                        
                        if (customSuccessCallback)
                            customSuccessCallback(json);
                        else
                            this.successCallback(json);
                    }).catch((error) => {
                        console.error("An error occurred while retrieving the payment token (2): [" + error + "]");
                        
                        if (customFailureCallback)
                            customFailureCallback(error);
                    });
                this.ccbillDebugLog(`Payment token generation complete`);
            } catch (error) {
                const errors = [];
                
                if (error?.forEach)
                {
                    error.forEach(function(item) {
                        const msg = item.message.split(".");
                        errors.push(msg[1]);
                    });
                }
                
                //console.error(`ERROR_RAW ` + error);
                console.error(`An error occurred while retrieving the payment token ` + JSON.stringify(errors));
                alert("ERROR: Unable to generate Payment Token: " + JSON.stringify(errors));
            }
            
            var paymentProcessingElement = document.getElementById('payment-processing')
            
            if (paymentProcessingElement)
                paymentProcessingElement.classList.remove('active');
            
            // TODO: remove this after troubleshooting
            window.gettingToken = false;
            return false;
            
        },
        successCallback : function( data ) {
          
          this.ccbillDebugLog("Success callback.  Data: " + JSON.stringify(data));
          
          window.getPaymentTokenDisabled = true;
        },
    
    }
} )();