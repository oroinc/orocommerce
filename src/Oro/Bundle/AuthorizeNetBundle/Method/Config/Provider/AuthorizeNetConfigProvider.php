<?php

namespace Oro\Bundle\AuthorizeNetBundle\Method\Config\Provider;

use Psr\Log\LoggerInterface;
use Doctrine\Common\Persistence\ManagerRegistry;

use Oro\Bundle\AuthorizeNetBundle\Entity\AuthorizeNetSettings;
use Oro\Bundle\AuthorizeNetBundle\Method\Config\AuthorizeNetConfigInterface;
use Oro\Bundle\AuthorizeNetBundle\Method\Config\Factory\AuthorizeNetConfigFactoryInterface;

class AuthorizeNetConfigProvider implements AuthorizeNetConfigProviderInterface
{
    /**
     * @var AuthorizeNetConfigInterface[]|null
     */
    protected $configs;

    /**
     * @var string
     */
    protected $type;

    /**
     * @var ManagerRegistry
     */
    protected $doctrine;

    /**
     * @var AuthorizeNetConfigFactoryInterface
     */
    protected $configFactory;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @param ManagerRegistry                    $doctrine
     * @param LoggerInterface                    $logger
     * @param AuthorizeNetConfigFactoryInterface $configFactory
     * @param string                             $type
     */
    public function __construct(
        ManagerRegistry $doctrine,
        LoggerInterface $logger,
        AuthorizeNetConfigFactoryInterface $configFactory,
        $type
    ) {
        $this->doctrine = $doctrine;
        $this->logger = $logger;
        $this->configFactory = $configFactory;
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
     * @return AuthorizeNetSettings[]
     */
    protected function getEnabledIntegrationSettings()
    {
        try {
            return $this->doctrine->getManagerForClass(AuthorizeNetSettings::class)
                ->getRepository(AuthorizeNetSettings::class)
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
            $config = $this->configFactory->createConfig($setting);
            $configs[$config->getPaymentMethodIdentifier()] = $config;
        }

        return $configs;
    }

    /**
     * {@inheritdoc}
     */
    public function getPaymentConfigs()
    {
        if ($this->configs === null) {
            $this->configs = $this->collectConfigs();
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
