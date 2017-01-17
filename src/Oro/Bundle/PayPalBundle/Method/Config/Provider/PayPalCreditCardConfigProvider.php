<?php

namespace Oro\Bundle\PayPalBundle\Method\Config\Provider;

use Oro\Bundle\PayPalBundle\Method\Config\Builder\PayPalCreditCardConfigBuilder;
use Oro\Bundle\PayPalBundle\Method\Config\PayPalCreditCardConfigInterface;

class PayPalCreditCardConfigProvider extends AbstractPayPalConfigProvider implements
    PayPalCreditCardConfigProviderInterface
{
    /**
     * @var PayPalCreditCardConfigInterface[]
     */
    protected $configs = [];

    /**
     * {inheritdoc}
     */
    public function getPaymentConfigs()
    {
        if (0 === count($this->configs)) {
            $this->fillConfigs();
        }

        return $this->configs;
    }

    /**
     * @param string $identifier
     * @return PayPalCreditCardConfigInterface|null
     */
    public function getPaymentConfig($identifier)
    {
        if (!$this->hasPaymentConfig($identifier)) {
            return null;
        }

        $configs = $this->getPaymentConfigs();

        return $configs[$identifier];
    }

    protected function fillConfigs()
    {
        $channels = $this->getEnabledIntegrationChannels();
        /** @var PayPalCreditCardConfigBuilder $builder */
        $builder = $this->factory->createPayPalConfigBuilder();
        foreach ($channels as $channel) {
            $config = $builder->setChannel($channel)->getResult();
            $this->configs[$config->getPaymentMethodIdentifier()] = $config;
        }
    }
}
