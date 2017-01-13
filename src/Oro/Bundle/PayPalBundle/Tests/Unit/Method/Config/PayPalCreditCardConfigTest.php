<?php

namespace Oro\Bundle\PayPalBundle\Tests\Unit\Method\Config;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\IntegrationBundle\Entity\Transport;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\PayPalBundle\Entity\PayPalSettings;
use Oro\Bundle\PayPalBundle\Method\Config\PayPalCreditCardConfig;
use Oro\Component\Testing\Unit\EntityTrait;
use Symfony\Component\HttpFoundation\ParameterBag;

class PayPalCreditCardConfigTest extends AbstractPayPalCreditCardConfigTest
{
    use EntityTrait;

    /**
     * {@inheritdoc}
     */
    protected function getPaymentConfig()
    {
        $label = (new LocalizedFallbackValue())->setString('test label');
        $labels = new ArrayCollection();
        $labels->add($label);

        $short_label = (new LocalizedFallbackValue())->setString('test short label');
        $short_labels = new ArrayCollection();
        $short_labels->add($short_label);

        $bag = [
            PayPalSettings::CREDIT_CARD_LABELS_KEY => $labels,
            PayPalSettings::CREDIT_CARD_SHORT_LABELS_KEY => $short_labels,
            PayPalSettings::PROXY_PORT_KEY => '8099',
            PayPalSettings::PROXY_HOST_KEY => 'proxy host',
            PayPalSettings::USE_PROXY_KEY => true,
            PayPalSettings::TEST_MODE_KEY => true,
            PayPalSettings::REQUIRE_CVV_ENTRY_KEY => true,
            PayPalSettings::ENABLE_SSL_VERIFICATION_KEY => true,
            PayPalSettings::DEBUG_MODE_KEY => true,
            PayPalSettings::AUTHORIZATION_FOR_REQUIRED_AMOUNT_KEY => true,
            PayPalSettings::ZERO_AMOUNT_AUTHORIZATION_KEY => true,
            PayPalSettings::ALLOWED_CREDIT_CARD_TYPES_KEY => ['Master Card', 'Visa'],
            PayPalSettings::CREDIT_CARD_PAYMENT_ACTION_KEY => 'string',
            PayPalSettings::VENDOR_KEY => 'string',
            PayPalSettings::USER_KEY => 'string',
            PayPalSettings::PASSWORD_KEY => 'string',
            PayPalSettings::PARTNER_KEY => 'string',
        ];
        $settingsBag = $this->createMock(ParameterBag::class);
        $settingsBag->expects(static::any())->method('get')->willReturnCallback(
            function () use ($bag) {
                $args = func_get_args();
                return $bag[$args[0]];
            }
        );

        $transport = $this->createMock(Transport::class);
        $transport->expects(static::any())->method('getSettingsBag')->willReturn($settingsBag);

        /** @var Channel $channel */
        $channel = $this->getEntity(
            Channel::class,
            ['id' => 1, 'type' => 'paypal_payflow_gateway', 'transport' => $transport]
        );

        return new PayPalCreditCardConfig(
            $channel,
            $this->encoder,
            $this->localizationHelper
        );
    }
}
