<?php

namespace Oro\Bundle\PayPalBundle\Tests\Unit\Method\Config;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\IntegrationBundle\Entity\Transport;
use Oro\Bundle\PayPalBundle\Method\Config\PayPalCreditCardConfig;
use Oro\Bundle\SecurityBundle\Encoder\SymmetricCrypterInterface;
use Oro\Component\Testing\Unit\EntityTrait;
use Symfony\Component\HttpFoundation\ParameterBag;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 */
class PayPalCreditCardConfigTest extends AbstractPayPalCreditCardConfigTest
{
    use EntityTrait;

    /**
     * {@inheritdoc}
     */
    protected function getPaymentConfig(ConfigManager $configManager)
    {
        $this->encoder = $this->createMock(SymmetricCrypterInterface::class);

        $settingsBag = $this->createMock(ParameterBag::class);
        $settingsBag->expects(static::any())->method('get')->willReturnCallback(
            function () {
                $args = func_get_args();
                return $args[0];
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
