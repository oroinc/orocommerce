<?php

namespace Oro\Bundle\PayPalBundle\Tests\Unit\Method\Config\Factory;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\IntegrationBundle\Generator\IntegrationIdentifierGeneratorInterface;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\LocaleBundle\Helper\LocalizationHelper;
use Oro\Bundle\PayPalBundle\Entity\PayPalSettings;
use Oro\Bundle\PayPalBundle\Method\Config\Factory\PayPalExpressCheckoutConfigFactory;
use Oro\Bundle\PayPalBundle\Method\Config\PayPalExpressCheckoutConfig;
use Oro\Bundle\PayPalBundle\PayPal\Payflow\Option;
use Oro\Bundle\SecurityBundle\Encoder\SymmetricCrypterInterface;
use Oro\Component\Testing\Unit\EntityTrait;

class PayPalExpressCheckoutConfigFactoryTest extends \PHPUnit_Framework_TestCase
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
     * @var IntegrationIdentifierGeneratorInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $identifierGenerator;

    /**
     * @var PayPalExpressCheckoutConfigFactory
     */
    protected $payPalExpressCheckoutConfigFactory;

    protected function setUp()
    {
        $this->encoder = $this->createMock(SymmetricCrypterInterface::class);
        $this->localizationHelper = $this->createMock(LocalizationHelper::class);
        $this->identifierGenerator = $this->createMock(IntegrationIdentifierGeneratorInterface::class);
        $this->payPalExpressCheckoutConfigFactory = new PayPalExpressCheckoutConfigFactory(
            $this->encoder,
            $this->localizationHelper,
            $this->identifierGenerator
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

        $this->encoder->expects(static::any())
            ->method('decryptData')
            ->willReturnMap([
                ['string', 'string'],
                ['proxy host', 'proxy host'],
                ['8099', '8099']
            ]);

        /** @var Channel $channel */
        $channel = $this->getEntity(
            Channel::class,
            ['id' => 1]
        );

        $bag = [
            'channel' => $channel,
            'expressCheckoutLabels' => [new LocalizedFallbackValue()],
            'expressCheckoutShortLabels' => [new LocalizedFallbackValue()],
            'expressCheckoutName' => 'Express Checkout Name',
            'proxyPort' => '8099',
            'proxyHost' => 'proxy host',
            'useProxy' => true,
            'testMode' => true,
            'requireCvvEntry' => true,
            'enableSslVerification' => true,
            'debugMode' => true,
            'authorizationForRequiredAmount' => true,
            'zeroAmountAuthorization' => true,
            'allowedCreditCardTypes' => ['visa'],
            'expressCheckoutPaymentAction' => 'charge',
            'vendor' => 'string',
            'user' => 'string',
            'password' => 'string',
            'partner' => 'string',
        ];
        /** @var PayPalSettings $paypalSettings */
        $paypalSettings = $this->getEntity(PayPalSettings::class, $bag);

        $this->localizationHelper->expects(static::exactly(2))
            ->method('getLocalizedValue')
            ->willReturnMap([
                [$paypalSettings->getExpressCheckoutLabels(), null, 'test label'],
                [$paypalSettings->getExpressCheckoutShortLabels(), null, 'test short label'],
            ]);

        $this->identifierGenerator->expects(static::once())
            ->method('generateIdentifier')
            ->with($channel)
            ->willReturn('paypal_payflow_gateway_express_checkout_1');

        $config = $this->payPalExpressCheckoutConfigFactory->createConfig($paypalSettings);
        $expectedConfig = $this->getExpectedConfig();
        static::assertEquals($expectedConfig, $config);
    }

    /**
     * @return PayPalExpressCheckoutConfig
     */
    protected function getExpectedConfig()
    {
        $params = [
            'label' => 'test label',
            'short_label' => 'test short label',
            'admin_label' => 'Express Checkout Name',
            'payment_method_identifier' => 'paypal_payflow_gateway_express_checkout_1',
            'test_mode' => true,
            'purchase_action' => 'charge',
            'credentials' => [
                Option\Vendor::VENDOR => 'string',
                Option\User::USER => 'string',
                Option\Password::PASSWORD => 'string',
                Option\Partner::PARTNER => 'string',
            ],
        ];

        return new PayPalExpressCheckoutConfig($params);
    }
}
