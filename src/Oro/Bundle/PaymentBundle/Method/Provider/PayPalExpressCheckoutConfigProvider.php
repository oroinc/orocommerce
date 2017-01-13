<?php

namespace Oro\Bundle\PaymentBundle\Method\Provider;

use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\PaymentBundle\Method\Config\PaymentConfigInterface;
use Oro\Bundle\PayPalBundle\Method\Config\PayPalExpressCheckoutConfig;

class PayPalExpressCheckoutConfigProvider extends PayPalConfigProvider implements
    PayPalExpressCheckoutConfigProviderInterface
{
    /**
     * @return PaymentConfigInterface[]
     */
    protected function fillConfigs()
    {
        $channels = $this->doctrine->getManagerForClass('OroIntegrationBundle:Channel')
            ->getRepository('OroIntegrationBundle:Channel')
            ->findBy(['type' => $this->getType(), 'enabled' => true])
        ;
        /** @var Channel $channel */
        foreach ($channels as $channel) {
            $config = new PayPalExpressCheckoutConfig($channel, $this->encoder);
            $this->configs[$config->getPaymentMethodIdentifier()] = $config;
        }

        return $this->getConfigs();
    }
}
