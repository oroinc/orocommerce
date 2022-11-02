<?php

namespace Oro\Bundle\PayPalBundle\Method\Config\Provider;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\PayPalBundle\Entity\PayPalSettings;
use Oro\Bundle\PayPalBundle\Method\Config\Factory\PayPalConfigFactoryInterface;
use Oro\Bundle\PayPalBundle\Method\Config\PayPalConfigInterface;
use Psr\Log\LoggerInterface;

abstract class AbstractPayPalConfigProvider
{
    /**
     * @var string
     */
    protected $type;

    /**
     * @var ManagerRegistry
     */
    protected $doctrine;

    /**
     * @var PayPalConfigFactoryInterface
     */
    protected $factory;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @return PayPalConfigInterface[]
     */
    abstract public function getPaymentConfigs();

    /**
     * @param ManagerRegistry              $doctrine
     * @param LoggerInterface              $logger
     * @param PayPalConfigFactoryInterface $factory
     * @param string                       $type
     */
    public function __construct(
        ManagerRegistry $doctrine,
        LoggerInterface $logger,
        PayPalConfigFactoryInterface $factory,
        $type
    ) {
        $this->doctrine = $doctrine;
        $this->logger = $logger;
        $this->factory = $factory;
        $this->type = $type;
    }

    /**
     * @param string $identifier
     *
     * @return bool
     */
    public function hasPaymentConfig($identifier)
    {
        $configs = $this->getPaymentConfigs();

        return array_key_exists($identifier, $configs);
    }

    /**
     * @return string
     */
    protected function getType()
    {
        return $this->type;
    }

    /**
     * @return PayPalSettings[]
     */
    protected function getEnabledIntegrationSettings()
    {
        try {
            return $this->doctrine->getManagerForClass('OroPayPalBundle:PayPalSettings')
                ->getRepository('OroPayPalBundle:PayPalSettings')
                ->getEnabledSettingsByType($this->getType());
        } catch (\UnexpectedValueException $e) {
            $this->logger->critical($e->getMessage());

            return [];
        }
    }

    /**
     * @return array
     */
    protected function collectConfigs()
    {
        $configs = [];
        $settings = $this->getEnabledIntegrationSettings();

        foreach ($settings as $setting) {
            $config = $this->factory->createConfig($setting);
            $configs[$config->getPaymentMethodIdentifier()] = $config;
        }

        return $configs;
    }
}
