<?php

namespace Oro\Bundle\PaymentTermBundle\Tests\Unit\Method\Config\ParameterBag;

use Oro\Bundle\PaymentTermBundle\Method\Config\ParameterBag\ParameterBagPaymentTermConfig;

class ParameterBagPaymentTermConfigTest extends \PHPUnit\Framework\TestCase
{
    public function testGetters()
    {
        $adminLabel = 'someAdminLabel';
        $label = 'someLabel';
        $shortLabel = 'someShortLabel';
        $paymentMethodIdentifier = 'someMethodIdentifier';

        $parameterBag = new ParameterBagPaymentTermConfig(
            [
                ParameterBagPaymentTermConfig::FIELD_ADMIN_LABEL => $adminLabel,
                ParameterBagPaymentTermConfig::FIELD_LABEL => $label,
                ParameterBagPaymentTermConfig::FIELD_SHORT_LABEL => $shortLabel,
                ParameterBagPaymentTermConfig::FIELD_PAYMENT_METHOD_IDENTIFIER => $paymentMethodIdentifier
            ]
        );

        $this->assertEquals($adminLabel, $parameterBag->getAdminLabel());
        $this->assertEquals($label, $parameterBag->getLabel());
        $this->assertEquals($shortLabel, $parameterBag->getShortLabel());
        $this->assertEquals($paymentMethodIdentifier, $parameterBag->getPaymentMethodIdentifier());
    }
}
