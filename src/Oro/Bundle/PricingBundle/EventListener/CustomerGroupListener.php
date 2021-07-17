<?php

namespace Oro\Bundle\PricingBundle\EventListener;

use Oro\Bundle\CustomerBundle\Event\CustomerGroupEvent;
use Oro\Bundle\PricingBundle\Entity\PriceListCustomerGroupFallback;
use Oro\Bundle\WebsiteBundle\Entity\Website;

class CustomerGroupListener extends AbstractPriceListCollectionAwareListener
{
    /**
     * @var string
     */
    protected $relationClass = 'Oro\Bundle\PricingBundle\Entity\PriceListToCustomerGroup';

    /**
     * @var string
     */
    protected $fallbackClass = 'Oro\Bundle\PricingBundle\Entity\PriceListCustomerGroupFallback';

    public function onGroupRemove(CustomerGroupEvent $event)
    {
        $this->triggerHandler->handleCustomerGroupRemove($event->getData());
    }

    /**
     * @param string $relationClass
     */
    public function setRelationClass($relationClass)
    {
        $this->relationClass = $relationClass;
    }

    /**
     * @param string $fallbackClass
     */
    public function setFallbackClass($fallbackClass)
    {
        $this->fallbackClass = $fallbackClass;
    }

    /**
     * {@inheritdoc}
     */
    protected function getFallbacks($targetEntity)
    {
        return $this->doctrineHelper->getEntityRepository($this->fallbackClass)
            ->findBy(['customerGroup' => $targetEntity]);
    }

    /**
     * @return string
     */
    protected function getRelationClass()
    {
        return $this->relationClass;
    }

    /**
     * {@inheritdoc}
     */
    protected function createFallback($targetEntity, Website $website)
    {
        $fallback = new PriceListCustomerGroupFallback();
        $fallback->setCustomerGroup($targetEntity)
            ->setWebsite($website);

        return $fallback;
    }

    /**
     * {@inheritdoc}
     */
    protected function getDefaultFallback()
    {
        return PriceListCustomerGroupFallback::WEBSITE;
    }

    /**
     * {@inheritdoc}
     */
    protected function handleCollectionChanges($targetEntity, Website $website)
    {
        $this->triggerHandler->handleCustomerGroupChange($targetEntity, $website);
    }
}
