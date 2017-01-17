<?php

namespace Oro\Bundle\PayPalBundle\Tests\Unit\Method\Config\Builder;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\IntegrationBundle\Entity\Transport;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\LocaleBundle\Helper\LocalizationHelper;
use Oro\Bundle\PayPalBundle\Entity\PayPalSettings;
use Oro\Bundle\PayPalBundle\Method\Config\Builder\PayPalExpressCheckoutConfigBuilder;
use Oro\Bundle\PayPalBundle\Method\Config\PayPalExpressCheckoutConfig;
use Oro\Bundle\SecurityBundle\Encoder\SymmetricCrypterInterface;
use Oro\Component\Testing\Unit\EntityTrait;
use Symfony\Component\HttpFoundation\ParameterBag;

class PayPalExpressCheckoutConfigBuilderTest extends \PHPUnit_Framework_TestCase
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
     * @var PayPalExpressCheckoutConfigBuilder
     */
    protected $payPalExpressCheckoutConfigBuilder;

    protected function setUp()
    {
        $this->encoder = $this->createMock(SymmetricCrypterInterface::class);
        $this->localizationHelper = $this->getMockBuilder(LocalizationHelper::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->payPalExpressCheckoutConfigBuilder = new PayPalExpressCheckoutConfigBuilder(
            $this->encoder,
            $this->localizationHelper
        );
    }

    public function testGetResult()
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
            PayPalSettings::EXPRESS_CHECKOUT_LABELS_KEY => $labels,
            PayPalSettings::EXPRESS_CHECKOUT_SHORT_LABELS_KEY => $short_labels,
            PayPalSettings::EXPRESS_CHECKOUT_NAME_KEY => 'Express Checkout Name',
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

        $transport = $this->createMock(Transport::class);
        $transport->expects(static::any())->method('getSettingsBag')->willReturn($settingsBag);

        /** @var Channel $channel */
        $channel = $this->getEntity(
            Channel::class,
            ['id' => 1, 'name' => 'PayFlow Gateway', 'type' => 'paypal_payflow_gateway', 'transport' => $transport]
        );

        $this->payPalExpressCheckoutConfigBuilder->setChannel($channel);
        $config = $this->payPalExpressCheckoutConfigBuilder->getResult();
        $expectedConfig = $this->getExpectedConfig();
        static::assertEquals($expectedConfig, $config);
    }

    protected function getExpectedConfig()
    {
        $bag = [
            PayPalSettings::EXPRESS_CHECKOUT_LABELS_KEY => 'test label',
            PayPalSettings::EXPRESS_CHECKOUT_SHORT_LABELS_KEY => 'test short label',
            PayPalSettings::EXPRESS_CHECKOUT_NAME_KEY => 'Express Checkout Name',
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
            PayPalExpressCheckoutConfig::PAYMENT_METHOD_IDENTIFIER_KEY => 'paypal_payflow_gateway_express_checkout_1',
            PayPalExpressCheckoutConfig::ADMIN_LABEL_KEY => 'Express Checkout Name'
        ];

        return new PayPalExpressCheckoutConfig(new ParameterBag($bag));
    }
}
