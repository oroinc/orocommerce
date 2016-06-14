<?php

namespace OroB2B\Bundle\PaymentBundle\Tests\Unit\Method\View;

use OroB2B\Bundle\PaymentBundle\Method\View\PayPalPaymentsProView;
use OroB2B\Bundle\PaymentBundle\DependencyInjection\Configuration;

class PayPalPaymentsProViewTest extends AbstractPayflowGatewayViewTest
{
    /**
     * @return PayPalPaymentsProView
     */
    protected function getMethodView()
    {
        return new PayPalPaymentsProView(
            $this->formFactory,
            $this->configManager,
            $this->paymentTransactionProvider
        );
    }

    /** {@inheritdoc} */
    protected function getZeroAmountKey()
    {
        return Configuration::PAYPAL_PAYMENTS_PRO_ZERO_AMOUNT_AUTHORIZATION_KEY;
    }

    /** {@inheritdoc} */
    protected function getAllowedCCTypesKey()
    {
        return Configuration::PAYPAL_PAYMENTS_PRO_ALLOWED_CC_TYPES_KEY;
    }

    /** {@inheritdoc} */

    protected function getAuthForRequiredAmountKey()
    {
        return Configuration::PAYPAL_PAYMENTS_PRO_AUTHORIZATION_FOR_REQUIRED_AMOUNT_KEY;
    }

    public function testGetOrder()
    {
        $order = '100';
        $this->setConfig($this->once(), Configuration::PAYPAL_PAYMENTS_PRO_SORT_ORDER_KEY, $order);

        $this->assertSame((int)$order, $this->methodView->getOrder());
    }

    public function testGetPaymentMethodType()
    {
        $this->assertEquals('paypal_payments_pro', $this->methodView->getPaymentMethodType());
    }

    public function testGetLabel()
    {
        $this->setConfig($this->once(), Configuration::PAYPAL_PAYMENTS_PRO_LABEL_KEY, 'testValue');
        $this->assertEquals('testValue', $this->methodView->getLabel());
    }

    public function testGetShortLabel()
    {
        $this->setConfig($this->once(), Configuration::PAYPAL_PAYMENTS_PRO_SHORT_LABEL_KEY, 'testValue');
        $this->assertEquals('testValue', $this->methodView->getShortLabel());
    }
}
