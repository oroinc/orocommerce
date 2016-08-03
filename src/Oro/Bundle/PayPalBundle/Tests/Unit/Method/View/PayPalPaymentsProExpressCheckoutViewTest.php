<?php

namespace Oro\Bundle\PayPalBundle\Tests\Unit\Method\View;

use Oro\Bundle\PayPalBundle\Method\View\PayPalPaymentsProExpressCheckoutView;

class PayPalPaymentsProExpressCheckoutViewTest extends PayflowExpressCheckoutViewTest
{
    /**
     * {@inheritdoc}
     */
    protected function createMethodView()
    {
        return new PayPalPaymentsProExpressCheckoutView($this->paymentConfig);
    }

    public function testGetPaymentMethodType()
    {
        $this->assertEquals('paypal_payments_pro_express_checkout', $this->methodView->getPaymentMethodType());
    }
}
