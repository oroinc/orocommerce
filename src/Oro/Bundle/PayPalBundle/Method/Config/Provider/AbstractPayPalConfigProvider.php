<?php

namespace Oro\Bundle\PayPalBundle\Method\Config\Provider;

use Doctrine\Common\Persistence\ManagerRegistry;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\PayPalBundle\Method\Config\Builder\Factory\PayPalConfigFactoryInterface;
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
     * @return array
     */
    abstract public function getPaymentConfigs();

    /**
     * @param ManagerRegistry $doctrine
     * @param LoggerInterface $logger
     * @param PayPalConfigFactoryInterface $factory
     * @param string $type
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
     * @return array|Channel[]
     */
    protected function getEnabledIntegrationChannels()
    {
        try {
            return $this->doctrine->getManagerForClass('OroIntegrationBundle:Channel')
                ->getRepository('OroIntegrationBundle:Channel')
                ->findBy(['type' => $this->getType(), 'enabled' => true]);
        } catch (\UnexpectedValueException $e) {
            $this->logger->critical($e->getMessage());

            return [];
        }
    }
}
