<?php

namespace Oro\Bundle\PricingBundle\EventListener;

use Oro\Bundle\CustomerBundle\Event\CustomerEvent;
use Oro\Bundle\PricingBundle\Entity\PriceListCustomerFallback;
use Oro\Bundle\PricingBundle\Entity\Repository\PriceListToCustomerRepository;
use Oro\Bundle\WebsiteBundle\Entity\Website;

class CustomerListener extends AbstractPriceListCollectionAwareListener
{
    /**
     * @var string
     */
    protected $relationClass = 'Oro\Bundle\PricingBundle\Entity\PriceListToCustomer';

    /**
     * @var string
     */
    protected $fallbackClass = 'Oro\Bundle\PricingBundle\Entity\PriceListCustomerFallback';

    /**
     * @param CustomerEvent $event
     */
    public function onCustomerGroupChange(CustomerEvent $event)
    {
        /** @var PriceListToCustomerRepository $repository */
        $repository = $this->doctrineHelper->getEntityRepository($this->relationClass);

        $customerWebsitePairs = $repository->getCustomerWebsitePairsByCustomer($event->getCustomer());
        foreach ($customerWebsitePairs as $customerWebsitePair) {
            $this->triggerHandler
                ->handleCustomerChange($customerWebsitePair->getCustomer(), $customerWebsitePair->getWebsite());
        }
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
            ->findBy(['customer' => $targetEntity]);
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
        $fallback = new PriceListCustomerFallback();
        $fallback->setCustomer($targetEntity)
            ->setWebsite($website);

        return $fallback;
    }

    /**
     * {@inheritdoc}
     */
    protected function getDefaultFallback()
    {
        return PriceListCustomerFallback::ACCOUNT_GROUP;
    }

    /**
     * {@inheritdoc}
     */
    protected function handleCollectionChanges($targetEntity, Website $website)
    {
        $this->triggerHandler->handleCustomerChange($targetEntity, $website);
    }
}
