<?php

namespace Oro\Bundle\PayPalBundle\Tests\Unit\Method\Config\Factory;

use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\IntegrationBundle\Generator\IntegrationIdentifierGeneratorInterface;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\LocaleBundle\Helper\LocalizationHelper;
use Oro\Bundle\PayPalBundle\Entity\CreditCardPaymentAction;
use Oro\Bundle\PayPalBundle\Entity\CreditCardType;
use Oro\Bundle\PayPalBundle\Entity\PayPalSettings;
use Oro\Bundle\PayPalBundle\Method\Config\Factory\PayPalCreditCardConfigFactory;
use Oro\Bundle\PayPalBundle\Method\Config\PayPalCreditCardConfig;
use Oro\Bundle\PayPalBundle\PayPal\Payflow\Option;
use Oro\Bundle\SecurityBundle\Encoder\SymmetricCrypterInterface;
use Oro\Component\Testing\Unit\EntityTrait;

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
     * @var IntegrationIdentifierGeneratorInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $identifierGenerator;

    /**
     * @var PayPalCreditCardConfigFactory
     */
    protected $payPalCreditCardConfigFactory;

    protected function setUp()
    {
        $this->encoder = $this->createMock(SymmetricCrypterInterface::class);
        $this->localizationHelper = $this->createMock(LocalizationHelper::class);
        $this->identifierGenerator = $this->createMock(IntegrationIdentifierGeneratorInterface::class);
        $this->payPalCreditCardConfigFactory = new PayPalCreditCardConfigFactory(
            $this->encoder,
            $this->localizationHelper,
            $this->identifierGenerator
        );
    }

    public function testCreateConfig()
    {
        $this->encoder->expects(static::once())
            ->method('decryptData')
            ->with('string')
            ->willReturn('string');

        /** @var Channel $channel */
        $channel = $this->getEntity(
            Channel::class,
            ['id' => 1, 'name' => 'PayFlow Gateway']
        );

        $bag = [
            'channel' => $channel,
            'creditCardLabels' => [new LocalizedFallbackValue()],
            'creditCardShortLabels' => [new LocalizedFallbackValue()],
            'proxyPort' => '8099',
            'proxyHost' => 'proxy host',
            'useProxy' => true,
            'testMode' => true,
            'requireCvvEntry' => true,
            'enableSslVerification' => true,
            'debugMode' => true,
            'authorizationForRequiredAmount' => true,
            'zeroAmountAuthorization' => true,
            'allowedCreditCardTypes' => [$this->createMock(CreditCardType::class)],
            'creditCardPaymentAction' => $this->createMock(CreditCardPaymentAction::class),
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
                [$paypalSettings->getCreditCardLabels(), null, 'test label'],
                [$paypalSettings->getCreditCardShortLabels(), null, 'test short label'],
            ]);

        $this->identifierGenerator->expects($this->once())
            ->method('generateIdentifier')
            ->with($channel)
            ->willReturn('paypal_payflow_gateway_credit_card_1');

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
            'label' => 'test label',
            'short_label' => 'test short label',
            'admin_label' => 'PayFlow Gateway',
            'payment_method_identifier' => 'paypal_payflow_gateway_credit_card_1',
            'proxy_port' => '8099',
            'proxy_host' => 'proxy host',
            'use_proxy' => true,
            'test_mode' => true,
            'require_cvv_entry' => true,
            'enable_ssl_verification' => true,
            'debug_mode' => true,
            'authorization_for_required_amount' => true,
            'zero_amount_authorization' => true,
            'allowed_credit_card_types' => [$this->createMock(CreditCardType::class)],
            'purchase_action' => $this->createMock(CreditCardPaymentAction::class),
            'credentials' => [
                Option\Vendor::VENDOR => 'string',
                Option\User::USER => 'string',
                Option\Password::PASSWORD => 'string',
                Option\Partner::PARTNER => 'string',
            ],
        ];

        return new PayPalCreditCardConfig($params);
    }
}
