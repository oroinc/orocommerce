<?php

namespace Oro\Bundle\PaymentTermBundle\Method\Config\Provider\Basic;

use Oro\Bundle\PaymentTermBundle\Entity\Repository\PaymentTermSettingsRepository;
use Oro\Bundle\PaymentTermBundle\Method\Config\Factory\Settings\PaymentTermConfigBySettingsFactoryInterface;
use Oro\Bundle\PaymentTermBundle\Method\Config\Provider\PaymentTermConfigProviderInterface;

class BasicPaymentTermConfigProvider implements PaymentTermConfigProviderInterface
{
    /**
     * @var PaymentTermConfigBySettingsFactoryInterface
     */
    private $paymentTermConfigBySettingsFactory;

    /**
     * @var PaymentTermSettingsRepository
     */
    private $paymentTermSettingsRepository;

    /**
     * @param PaymentTermConfigBySettingsFactoryInterface $paymentTermConfigBySettingsFactory
     * @param PaymentTermSettingsRepository $paymentTermSettingsRepository
     */
    public function __construct(
        PaymentTermConfigBySettingsFactoryInterface $paymentTermConfigBySettingsFactory,
        PaymentTermSettingsRepository $paymentTermSettingsRepository
    ) {
        $this->paymentTermConfigBySettingsFactory = $paymentTermConfigBySettingsFactory;
        $this->paymentTermSettingsRepository = $paymentTermSettingsRepository;
    }

    /**
     * {@inheritdoc}
     */
    public function getPaymentConfigs()
    {
        $configs = [];

        $settings = $this->paymentTermSettingsRepository->findWithEnabledChannel();

        foreach ($settings as $setting) {
            $config = $this->paymentTermConfigBySettingsFactory->createConfigBySettings($setting);

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
