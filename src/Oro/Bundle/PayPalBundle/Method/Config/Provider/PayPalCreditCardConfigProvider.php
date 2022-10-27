<?php

namespace Oro\Bundle\PayPalBundle\Method\Config\Provider;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\PayPalBundle\Method\Config\Factory\PayPalCreditCardConfigFactoryInterface;
use Oro\Bundle\PayPalBundle\Method\Config\PayPalCreditCardConfigInterface;
use Psr\Log\LoggerInterface;

class PayPalCreditCardConfigProvider extends AbstractPayPalConfigProvider implements
    PayPalCreditCardConfigProviderInterface
{
    /**
     * @var PayPalCreditCardConfigInterface[]
     */
    protected $configs = [];

    /**
     * {@inheritdoc}
     */
    public function __construct(
        ManagerRegistry $doctrine,
        LoggerInterface $logger,
        PayPalCreditCardConfigFactoryInterface $factory,
        $type
    ) {
        parent::__construct($doctrine, $logger, $factory, $type);
    }

    /**
     * {@inheritdoc}
     */
    public function getPaymentConfigs()
    {
        if (0 === count($this->configs)) {
            return $this->configs = $this->collectConfigs();
        }

        return $this->configs;
    }

    /**
     * {@inheritdoc}
     */
    public function getPaymentConfig($identifier)
    {
        if (!$this->hasPaymentConfig($identifier)) {
            return null;
        }

        $configs = $this->getPaymentConfigs();

        return $configs[$identifier];
    }
}
