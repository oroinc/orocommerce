<?php

namespace Oro\Bundle\MoneyOrderBundle\Method\Config\Provider;

use Oro\Bundle\MoneyOrderBundle\Entity\Repository\MoneyOrderSettingsRepository;
use Oro\Bundle\MoneyOrderBundle\Method\Config\Factory\MoneyOrderConfigFactoryInterface;
use Oro\Bundle\MoneyOrderBundle\Method\Config\MoneyOrderConfigInterface;

class MoneyOrderConfigProvider implements MoneyOrderConfigProviderInterface
{
    /**
     * @var MoneyOrderSettingsRepository
     */
    protected $settingsRepository;

    /**
     * @var MoneyOrderConfigFactoryInterface
     */
    protected $configFactory;

    /**
     * @var MoneyOrderConfigInterface[]
     */
    protected $configs;

    /**
     * @param MoneyOrderSettingsRepository $settingsRepository
     * @param MoneyOrderConfigFactoryInterface $configFactory
     */
    public function __construct(
        MoneyOrderSettingsRepository $settingsRepository,
        MoneyOrderConfigFactoryInterface $configFactory
    ) {
        $this->settingsRepository = $settingsRepository;
        $this->configFactory = $configFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function getPaymentConfigs()
    {
        $configs = [];

        $settings = $this->settingsRepository->findWithEnabledChannel();

        foreach ($settings as $setting) {
            $config = $this->configFactory->create($setting);

            $configs[$config->getPaymentMethodIdentifier()] = $config;
        }

        return $configs;
    }

    /**
     * {@inheritdoc}
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
     * {@inheritdoc}
     */
    public function hasPaymentConfig($identifier)
    {
        return null !== $this->getPaymentConfig($identifier);
    }
}
