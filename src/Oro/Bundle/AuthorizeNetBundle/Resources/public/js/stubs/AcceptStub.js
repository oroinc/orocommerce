(function() {
    'use strict';
    window.Accept = {
        dispatchData: function(request, callback) {
            var result;
            if (request.cardData.cardNumber === '5555555555554444') {
                result = {
                    messages: {
                        message: [
                            {
                                code: 'E_WC_17',
                                text: 'User authentication failed due to invalid authentication values.'
                            }
                        ],
                        resultCode: 'Error'
                    }
                };
            } else if (request.cardData.cardNumber === '5105105105105100') {
                result = {
                    messages: {
                        message: [
                            {
                                code: 'I_WC_01',
                                text: 'Successful.'
                            }
                        ],
                        resultCode: 'Ok'
                    },
                    opaqueData: {
                        dataDescriptor: 'COMMON.ACCEPT.INAPP.PAYMENT',
                        dataValue: 'special_data_value_for_api_error_emulation'
                    }
                };
            } else {
                result = {
                    messages: {
                        message: [
                            {
                                code: 'I_WC_01',
                                text: 'Successful.'
                            }
                        ],
                        resultCode: 'Ok'
                    },
                    opaqueData: {
                        dataDescriptor: 'COMMON.ACCEPT.INAPP.PAYMENT',
                        dataValue: 'eyJ0b2tlbiI6Ijk0OTIxNzMxMTc4ODIwODQ2MDQ2MDMiLCJ2IjoiMS4xIn0='
                    }
                };
            }
            callback(result);
        }
    };
})();
