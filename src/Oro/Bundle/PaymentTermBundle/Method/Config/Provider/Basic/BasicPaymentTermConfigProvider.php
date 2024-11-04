<?php

namespace Oro\Bundle\PaymentTermBundle\Method\Config\Provider\Basic;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\PaymentTermBundle\Entity\PaymentTermSettings;
use Oro\Bundle\PaymentTermBundle\Method\Config\Factory\Settings\PaymentTermConfigBySettingsFactoryInterface;
use Oro\Bundle\PaymentTermBundle\Method\Config\Provider\PaymentTermConfigProviderInterface;
use Psr\Log\LoggerInterface;

/**
 * Provides payment method configs for all enabled payment term integrations
 */
class BasicPaymentTermConfigProvider implements PaymentTermConfigProviderInterface
{
    /**
     * @var PaymentTermConfigBySettingsFactoryInterface
     */
    private $paymentTermConfigBySettingsFactory;

    /**
     * @var ManagerRegistry
     */
    protected $doctrine;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    public function __construct(
        ManagerRegistry $doctrine,
        LoggerInterface $logger,
        PaymentTermConfigBySettingsFactoryInterface $paymentTermConfigBySettingsFactory
    ) {
        $this->doctrine = $doctrine;
        $this->logger = $logger;
        $this->paymentTermConfigBySettingsFactory = $paymentTermConfigBySettingsFactory;
    }

    #[\Override]
    public function getPaymentConfigs()
    {
        $configs = [];

        $settings = $this->getEnabledIntegrationSettings();

        foreach ($settings as $setting) {
            $config = $this->paymentTermConfigBySettingsFactory->createConfigBySettings($setting);

            $configs[$config->getPaymentMethodIdentifier()] = $config;
        }

        return $configs;
    }

    #[\Override]
    public function getPaymentConfig($identifier)
    {
        $paymentConfigs = $this->getPaymentConfigs();

        if ([] === $paymentConfigs || false === array_key_exists($identifier, $paymentConfigs)) {
            return null;
        }

        return $paymentConfigs[$identifier];
    }

    #[\Override]
    public function hasPaymentConfig($identifier)
    {
        return null !== $this->getPaymentConfig($identifier);
    }

    /**
     * @return PaymentTermSettings[]
     */
    protected function getEnabledIntegrationSettings()
    {
        try {
            return $this->doctrine->getManagerForClass(PaymentTermSettings::class)
                ->getRepository(PaymentTermSettings::class)
                ->findWithEnabledChannel();
        } catch (\UnexpectedValueException $e) {
            $this->logger->critical($e->getMessage());

            return [];
        }
    }
}
