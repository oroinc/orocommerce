<?php

namespace Oro\Bundle\UPSBundle\Method;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\IntegrationBundle\Entity\Repository\ChannelRepository;
use Oro\Bundle\ShippingBundle\Method\Factory\IntegrationShippingMethodFactoryInterface;
use Oro\Bundle\ShippingBundle\Method\ShippingMethodInterface;
use Oro\Bundle\ShippingBundle\Method\ShippingMethodProviderInterface;
use Oro\Bundle\UPSBundle\Provider\ChannelType;

class UPSShippingMethodProvider implements ShippingMethodProviderInterface
{
    /**
     * @var DoctrineHelper
     */
    private $doctrineHelper;

    /**
     * @var IntegrationShippingMethodFactoryInterface
     */
    private $methodFactory;

    /**
     * @var ShippingMethodInterface[]
     */
    private $methods;

    /**
     * @param DoctrineHelper $doctrineHelper
     * @param IntegrationShippingMethodFactoryInterface $methodFactory
     */
    public function __construct(
        DoctrineHelper $doctrineHelper,
        IntegrationShippingMethodFactoryInterface $methodFactory
    ) {
        $this->doctrineHelper = $doctrineHelper;
        $this->methodFactory = $methodFactory;
    }

    /**
     * {@inheritDoc}
     */
    public function getShippingMethods()
    {
        if (!$this->methods) {
            $channels = $this->getRepository()->findByType(ChannelType::TYPE);
            $this->methods = [];
            /** @var Channel $channel */
            foreach ($channels as $channel) {
                $method = $this->methodFactory->create($channel);
                $this->methods[$method->getIdentifier()] = $method;
            }
        }

        return $this->methods;
    }

    /**
     * {@inheritDoc}
     */
    public function getShippingMethod($name)
    {
        if ($this->hasShippingMethod($name)) {
            return $this->getShippingMethods()[$name];
        }

        return null;
    }

    /**
     * {@inheritDoc}
     */
    public function hasShippingMethod($name)
    {
        return array_key_exists($name, $this->getShippingMethods());
    }

    /**
     * @return ChannelRepository|\Doctrine\ORM\EntityRepository
     */
    private function getRepository()
    {
        return $this->doctrineHelper->getEntityRepository('OroIntegrationBundle:Channel');
    }
}
