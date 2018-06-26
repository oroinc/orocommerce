<?php

namespace Oro\Bundle\MoneyOrderBundle\Tests\Unit\Method\Config;

use Oro\Bundle\MoneyOrderBundle\Method\Config\MoneyOrderConfig;

class MoneyOrderConfigTest extends \PHPUnit\Framework\TestCase
{
    public function testGetters()
    {
        $adminLabel = 'someAdminLabel';
        $label = 'someLabel';
        $shortLabel = 'someShortLabel';
        $paymentMethodIdentifier = 'someMethodIdentifier';
        $payTo = 'payTo';
        $sendTo = 'sendTo';

        $parameterBag = new MoneyOrderConfig(
            [
                MoneyOrderConfig::ADMIN_LABEL_KEY => $adminLabel,
                MoneyOrderConfig::LABEL_KEY => $label,
                MoneyOrderConfig::SHORT_LABEL_KEY => $shortLabel,
                MoneyOrderConfig::PAYMENT_METHOD_IDENTIFIER_KEY => $paymentMethodIdentifier,
                MoneyOrderConfig::PAY_TO_KEY => $payTo,
                MoneyOrderConfig::SEND_TO_KEY => $sendTo
            ]
        );

        static::assertEquals($adminLabel, $parameterBag->getAdminLabel());
        static::assertEquals($label, $parameterBag->getLabel());
        static::assertEquals($shortLabel, $parameterBag->getShortLabel());
        static::assertEquals($paymentMethodIdentifier, $parameterBag->getPaymentMethodIdentifier());
        static::assertEquals($payTo, $parameterBag->getPayTo());
        static::assertEquals($sendTo, $parameterBag->getSendTo());
    }
}
