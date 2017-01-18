<?php

namespace Oro\Bundle\PayPalBundle\Tests\Unit\Method\Config\Factory;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\LocaleBundle\Helper\LocalizationHelper;
use Oro\Bundle\PayPalBundle\Entity\PayPalSettings;
use Oro\Bundle\PayPalBundle\Method\Config\Factory\PayPalCreditCardConfigFactory;
use Oro\Bundle\PayPalBundle\Method\Config\PayPalCreditCardConfig;
use Oro\Bundle\PayPalBundle\PayPal\Payflow\Option;
use Oro\Bundle\SecurityBundle\Encoder\SymmetricCrypterInterface;
use Oro\Component\Testing\Unit\EntityTrait;
use Symfony\Component\HttpFoundation\ParameterBag;

class PayPalCreditCardConfigFactoryTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    /**
     * @var SymmetricCrypterInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $encoder;

    /**
     * @var LocalizationHelper|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $localizationHelper;

    /**
     * @var PayPalCreditCardConfigFactory
     */
    protected $payPalCreditCardConfigFactory;

    protected function setUp()
    {
        $this->encoder = $this->createMock(SymmetricCrypterInterface::class);
        $this->localizationHelper = $this->getMockBuilder(LocalizationHelper::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->payPalCreditCardConfigFactory = new PayPalCreditCardConfigFactory(
            $this->encoder,
            $this->localizationHelper
        );
    }

    public function testCreateConfig()
    {
        $label = (new LocalizedFallbackValue())->setString('test label');
        $labels = new ArrayCollection();
        $labels->add($label);

        $short_label = (new LocalizedFallbackValue())->setString('test short label');
        $short_labels = new ArrayCollection();
        $short_labels->add($short_label);

        $this->localizationHelper->expects(static::exactly(2))
            ->method('getLocalizedValue')
            ->willReturnMap(
                [
                    [$labels, null, 'test label'],
                    [$short_labels, null, 'test short label']
                ]
            );

        $this->encoder->expects(static::once())
            ->method('decryptData')
            ->with('string')
            ->willReturn('string');

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
        $settingsBag = new ParameterBag($bag);

        /** @var PayPalSettings|\PHPUnit_Framework_MockObject_MockObject $paypalSettings */
        $paypalSettings = $this->createMock(PayPalSettings::class);
        $paypalSettings->expects(static::any())->method('getSettingsBag')->willReturn($settingsBag);

        /** @var Channel $channel */
        $channel = $this->getEntity(
            Channel::class,
            ['id' => 1, 'name' => 'PayFlow Gateway', 'type' => 'paypal_payflow_gateway', 'transport' => $paypalSettings]
        );
        $paypalSettings->expects(static::once())->method('getChannel')->willReturn($channel);

        $config = $this->payPalCreditCardConfigFactory->createConfig($paypalSettings);
        $expectedConfig = $this->getExpectedConfig();
        static::assertEquals($expectedConfig, $config);
    }

    /**
     * @return PayPalCreditCardConfig
     */
    protected function getExpectedConfig()
    {
        $params = [
            PayPalCreditCardConfig::LABEL_KEY => 'test label',
            PayPalCreditCardConfig::SHORT_LABEL_KEY => 'test short label',
            PayPalCreditCardConfig::ADMIN_LABEL_KEY => 'PayFlow Gateway',
            PayPalCreditCardConfig::PAYMENT_METHOD_IDENTIFIER_KEY => 'paypal_payflow_gateway_credit_card_1',
            PayPalCreditCardConfig::PROXY_PORT_KEY => '8099',
            PayPalCreditCardConfig::PROXY_HOST_KEY => 'proxy host',
            PayPalCreditCardConfig::USE_PROXY_KEY => true,
            PayPalCreditCardConfig::TEST_MODE_KEY => true,
            PayPalCreditCardConfig::REQUIRE_CVV_ENTRY_KEY => true,
            PayPalCreditCardConfig::ENABLE_SSL_VERIFICATION_KEY => true,
            PayPalCreditCardConfig::DEBUG_MODE_KEY => true,
            PayPalCreditCardConfig::AUTHORIZATION_FOR_REQUIRED_AMOUNT_KEY => true,
            PayPalCreditCardConfig::ZERO_AMOUNT_AUTHORIZATION_KEY => true,
            PayPalCreditCardConfig::ALLOWED_CREDIT_CARD_TYPES_KEY => ['Master Card', 'Visa'],
            PayPalCreditCardConfig::PURCHASE_ACTION_KEY => 'string',
            PayPalCreditCardConfig::CREDENTIALS_KEY => [
                Option\Vendor::VENDOR => 'string',
                Option\User::USER => 'string',
                Option\Password::PASSWORD => 'string',
                Option\Partner::PARTNER => 'string'
            ],
        ];

        return new PayPalCreditCardConfig($params);
    }
}
