<?php

namespace Oro\Bundle\MoneyOrderBundle\Method\Config\Provider;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\MoneyOrderBundle\Entity\MoneyOrderSettings;
use Oro\Bundle\MoneyOrderBundle\Method\Config\Factory\MoneyOrderConfigFactoryInterface;
use Oro\Bundle\MoneyOrderBundle\Method\Config\MoneyOrderConfigInterface;
use Psr\Log\LoggerInterface;

class MoneyOrderConfigProvider implements MoneyOrderConfigProviderInterface
{
    /**
     * @var ManagerRegistry
     */
    protected $doctrine;

    /**
     * @var MoneyOrderConfigFactoryInterface
     */
    protected $configFactory;

    /**
     * @var MoneyOrderConfigInterface[]
     */
    protected $configs;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    public function __construct(
        ManagerRegistry $doctrine,
        LoggerInterface $logger,
        MoneyOrderConfigFactoryInterface $configFactory
    ) {
        $this->doctrine = $doctrine;
        $this->logger = $logger;
        $this->configFactory = $configFactory;
    }

    /**
     * {@inheritDoc}
     */
    public function getPaymentConfigs()
    {
        $configs = [];

        $settings = $this->getEnabledIntegrationSettings();

        foreach ($settings as $setting) {
            $config = $this->configFactory->create($setting);

            $configs[$config->getPaymentMethodIdentifier()] = $config;
        }

        return $configs;
    }

    /**
     * {@inheritDoc}
     */
    public function getPaymentConfig($identifier)
    {
        $paymentConfigs = $this->getPaymentConfigs();

        if ([] === $paymentConfigs || false === array_key_exists($identifier, $paymentConfigs)) {
            return null;
        }

        return $paymentConfigs[$identifier];
    }

    /**
     * {@inheritDoc}
     */
    public function hasPaymentConfig($identifier)
    {
        return null !== $this->getPaymentConfig($identifier);
    }

    /**
     * @return MoneyOrderSettings[]
     */
    protected function getEnabledIntegrationSettings()
    {
        try {
            return $this->doctrine->getManagerForClass('OroMoneyOrderBundle:MoneyOrderSettings')
                ->getRepository('OroMoneyOrderBundle:MoneyOrderSettings')
                ->findWithEnabledChannel();
        } catch (\UnexpectedValueException $e) {
            $this->logger->critical($e->getMessage());

            return [];
        }
    }
}
