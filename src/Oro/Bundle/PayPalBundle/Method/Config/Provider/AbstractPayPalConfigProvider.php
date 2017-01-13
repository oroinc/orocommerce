<?php

namespace Oro\Bundle\PayPalBundle\Method\Config\Provider;

use Doctrine\Common\Persistence\ManagerRegistry;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\LocaleBundle\Helper\LocalizationHelper;
use Oro\Bundle\SecurityBundle\Encoder\SymmetricCrypterInterface;
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
     * @var SymmetricCrypterInterface
     */
    protected $encoder;

    /**
     * @var LocalizationHelper
     */
    protected $localizationHelper;

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
     * @param SymmetricCrypterInterface $encoder
     * @param LocalizationHelper $localizationHelper
     * @param LoggerInterface $logger
     * @param string $type
     */
    public function __construct(
        ManagerRegistry $doctrine,
        SymmetricCrypterInterface $encoder,
        LocalizationHelper $localizationHelper,
        LoggerInterface $logger,
        $type
    ) {
        $this->doctrine = $doctrine;
        $this->encoder = $encoder;
        $this->localizationHelper = $localizationHelper;
        $this->logger = $logger;
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
