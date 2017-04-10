<?php

namespace Oro\Bundle\InfinitePayBundle\Method\Config\Provider;

use Doctrine\Common\Persistence\ManagerRegistry;
use Oro\Bundle\InfinitePayBundle\Entity\InfinitePaySettings;
use Oro\Bundle\InfinitePayBundle\Method\Config\Factory\InfinitePayConfigFactoryInterface;
use Oro\Bundle\InfinitePayBundle\Method\Config\InfinitePayConfigInterface;
use Psr\Log\LoggerInterface;

class InfinitePayConfigProvider implements InfinitePayConfigProviderInterface
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
     * @var InfinitePayConfigFactoryInterface
     */
    protected $factory;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var InfinitePayConfigInterface[]
     */
    protected $configs = [];

    /**
     * {@inheritdoc}
     */
    public function __construct(
        ManagerRegistry $doctrine,
        LoggerInterface $logger,
        InfinitePayConfigFactoryInterface $factory,
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

    /**
     * @return InfinitePaySettings[]
     */
    protected function getEnabledIntegrationSettings()
    {
        try {
            return $this->doctrine->getManagerForClass(InfinitePaySettings::class)
                ->getRepository(InfinitePaySettings::class)
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
