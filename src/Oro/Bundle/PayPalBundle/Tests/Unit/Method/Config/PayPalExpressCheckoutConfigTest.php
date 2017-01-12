<?php

namespace Oro\Bundle\PayPalBundle\Tests\Unit\Method\Config;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\IntegrationBundle\Entity\Transport;
use Oro\Bundle\PayPalBundle\DependencyInjection\OroPayPalExtension;
use Oro\Bundle\PayPalBundle\Method\Config\PayPalExpressCheckoutConfig;
use Oro\Bundle\PayPalBundle\Method\Config\PayPalExpressCheckoutConfigInterface;
use Oro\Bundle\PaymentBundle\Tests\Unit\Method\Config\AbstractPaymentConfigTestCase;
use Oro\Bundle\SecurityBundle\Encoder\SymmetricCrypterInterface;
use Oro\Component\Testing\Unit\EntityTrait;
use Symfony\Component\HttpFoundation\ParameterBag;

class PayPalExpressCheckoutConfigTest extends AbstractPaymentConfigTestCase
{
    use EntityTrait;

    /** @var PayPalExpressCheckoutConfigInterface */
    protected $config;

    /**
     * {@inheritdoc}
     */
    protected function getPaymentConfig(ConfigManager $configManager)
    {
        $this->encoder = $this->createMock(SymmetricCrypterInterface::class);

        $bag = [
            $this->getConfigPrefix() . 'short_label' => 'test short label',
            $this->getConfigPrefix() . 'proxy_port' => '8099',
            $this->getConfigPrefix() . 'checkout_label' => 'test label',
            $this->getConfigPrefix() . 'checkout_short_label' => 'test short label',
            $this->getConfigPrefix() . 'checkout_payment_action' => 'paypal_payments_pro_express_payment_action',
            $this->getConfigPrefix() . 'test_mode' => true,
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

        return new PayPalExpressCheckoutConfig(
            $channel,
            $this->encoder
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function getConfigPrefix()
    {
        return 'paypal_payments_pro_express_';
    }

    public function testIsTestMode()
    {
        $this->assertTrue($this->config->isTestMode());
    }

    public function testGetPurchaseAction()
    {
        $this->assertSame($this->getConfigPrefix() . 'payment_action', $this->config->getPurchaseAction());
    }

    /**
     * {@inheritdoc}
     */
    protected function getExtensionAlias()
    {
        return OroPayPalExtension::ALIAS;
    }
}
