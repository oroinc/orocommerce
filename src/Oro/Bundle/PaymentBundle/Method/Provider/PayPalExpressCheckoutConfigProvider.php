<?php

namespace Oro\Bundle\PaymentBundle\Method\Provider;

use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\PayPalBundle\Method\Config\PayPalExpressCheckoutConfig;
use Oro\Bundle\PayPalBundle\Method\Config\PayPalExpressCheckoutConfigInterface;

class PayPalExpressCheckoutConfigProvider extends PayPalConfigProvider
{
    /**
     * @return PayPalExpressCheckoutConfigInterface[]
     */
    public function getPaymentConfigs()
    {
        if ($this->getConfigs() !== null) {
            return $this->getConfigs();
        }

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
