<?php

namespace Oro\Bundle\ApruveBundle\Tests\Unit\Method\Config;

use Oro\Bundle\ApruveBundle\Method\Config\ApruveConfig;

class ApruveConfigTest extends \PHPUnit_Framework_TestCase
{
    public function testGetters()
    {
        $adminLabel = 'Apruve';
        $label = 'Apruve';
        $shortLabel = 'Apruve (short)';
        $paymentMethodIdentifier = 'apruve_1';
        $testMode = false;
        $apiKey = '213a9079914f3b5163c6190f31444528';
        $merchantId = '7b97ea0172e18cbd4d3bf21e2b525b2d';

        $parameterBag = new ApruveConfig(
            [
                ApruveConfig::ADMIN_LABEL_KEY => $adminLabel,
                ApruveConfig::LABEL_KEY => $label,
                ApruveConfig::SHORT_LABEL_KEY => $shortLabel,
                ApruveConfig::PAYMENT_METHOD_IDENTIFIER_KEY => $paymentMethodIdentifier,
                ApruveConfig::API_KEY_KEY => $apiKey,
                ApruveConfig::MERCHANT_ID_KEY => $merchantId,
                ApruveConfig::TEST_MODE_KEY => $testMode,
            ]
        );

        static::assertEquals($adminLabel, $parameterBag->getAdminLabel());
        static::assertEquals($label, $parameterBag->getLabel());
        static::assertEquals($shortLabel, $parameterBag->getShortLabel());
        static::assertEquals($paymentMethodIdentifier, $parameterBag->getPaymentMethodIdentifier());
        static::assertEquals($apiKey, $parameterBag->getApiKey());
        static::assertEquals($merchantId, $parameterBag->getMerchantId());
        static::assertEquals($testMode, $parameterBag->isTestMode());
    }
}
