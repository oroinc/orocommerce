<?php

namespace Oro\Bundle\MoneyOrderBundle\Method\Config;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\IntegrationBundle\Entity\Repository\ChannelRepository;
use Oro\Bundle\MoneyOrderBundle\Integration\MoneyOrderChannelType;
use Oro\Bundle\PaymentBundle\Method\Provider\PaymentConfigProviderInterface;

class MoneyOrderConfigProvider implements PaymentConfigProviderInterface
{
    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /**
     * @param DoctrineHelper $doctrineHelper
     */
    public function __construct(DoctrineHelper $doctrineHelper)
    {
        $this->doctrineHelper = $doctrineHelper;
    }

    /**
     * @return MoneyOrderConfig[]
     */
    public function getPaymentConfigs()
    {
        $configs = [];

        $channels = $this->getMoneyOrderChannels();
        foreach ($channels as $channel) {
            $config = new MoneyOrderConfig($channel);

            $configs[$config->getPaymentMethodIdentifier()] = $config;
        }

        return $configs;
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
     * @param string $identifier
     *
     * @return MoneyOrderConfig|null
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
     * @return Channel[]
     */
    private function getMoneyOrderChannels()
    {
        return $this->getChannelRepository()->findBy([
            'type' => MoneyOrderChannelType::TYPE,
            'enabled' => true
        ]);
    }

    /**
     * @return ChannelRepository
     */
    private function getChannelRepository()
    {
        return $this->doctrineHelper->getEntityRepository('OroIntegrationBundle:Channel');
    }
}
