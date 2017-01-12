<?php

namespace Oro\Bundle\PayPalBundle\Tests\Unit\Method\Config;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\IntegrationBundle\Entity\Transport;
use Oro\Bundle\PayPalBundle\Method\Config\PayPalCreditCardConfig;
use Oro\Bundle\SecurityBundle\Encoder\SymmetricCrypterInterface;
use Oro\Component\Testing\Unit\EntityTrait;
use Symfony\Component\HttpFoundation\ParameterBag;

class PayPalCreditCardConfigTest extends AbstractPayPalCreditCardConfigTest
{
    use EntityTrait;

    /**
     * {@inheritdoc}
     */
    protected function getPaymentConfig(ConfigManager $configManager)
    {
        $this->encoder = $this->createMock(SymmetricCrypterInterface::class);

        $bag = [
            $this->getConfigPrefix() . 'short_label' => 'test short label',
            $this->getConfigPrefix() . 'proxy_port' => '8099',
            $this->getConfigPrefix() . 'proxy_host' => 'proxy host',
            $this->getConfigPrefix() . 'use_proxy' => true,
            $this->getConfigPrefix() . 'test_mode' => true,
            $this->getConfigPrefix() . 'label' => 'test label',
            $this->getConfigPrefix() . 'require_cvv' => true,
            $this->getConfigPrefix() . 'enable_ssl_verification' => true,
            $this->getConfigPrefix() . 'debug_mode' => true,
            $this->getConfigPrefix() . 'authorization_for_required_amount' => true,
            $this->getConfigPrefix() . 'zero_amount_authorization' => true,
            $this->getConfigPrefix() . 'allowed_cc_types' => ['Master Card', 'Visa'],
            $this->getConfigPrefix() . 'payment_action' => 'string',
            $this->getConfigPrefix() . 'vendor' => 'string',
            $this->getConfigPrefix() . 'user' => 'string',
            $this->getConfigPrefix() . 'password' => 'string',
            $this->getConfigPrefix() . 'partner' => 'string',
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
            $this->encoder
        );
    }

    /**
     * @return string
     */
    protected function getConfigPrefix()
    {
        return 'payflow_gateway_';
    }
}
