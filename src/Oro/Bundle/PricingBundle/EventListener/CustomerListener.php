<?php

namespace Oro\Bundle\PricingBundle\EventListener;

use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Event\CustomerEvent;
use Oro\Bundle\CustomerBundle\Event\CustomerMassEvent;
use Oro\Bundle\PricingBundle\Entity\PriceListCustomerFallback;
use Oro\Bundle\PricingBundle\Entity\PriceListToCustomer;
use Oro\Bundle\WebsiteBundle\Entity\Repository\WebsiteRepository;
use Oro\Bundle\WebsiteBundle\Entity\Website;

/**
 * Schedule Combined Price List updates on Customer Price List sequence and fallback changes.
 * Schedule Combined Price List updates for all websites on Customer Group changes.
 */
class CustomerListener extends AbstractPriceListCollectionAwareListener
{
    /**
     * @var string
     */
    protected $relationClass = PriceListToCustomer::class;

    /**
     * @var string
     */
    protected $fallbackClass = PriceListCustomerFallback::class;

    public function onCustomerGroupChange(CustomerEvent $event)
    {
        $this->handleSingleCustomerGroupChange($event->getCustomer());
    }

    public function onCustomerGroupMassChange(CustomerMassEvent $event)
    {
        foreach ($event->getCustomers() as $customer) {
            $this->handleSingleCustomerGroupChange($customer);
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

    protected function handleSingleCustomerGroupChange(Customer $customer)
    {
        /** @var WebsiteRepository $websiteRepo */
        $websiteRepo = $this->doctrineHelper->getEntityRepository(Website::class);
        foreach ($websiteRepo->getAllWebsites($customer->getOrganization()) as $website) {
            $this->triggerHandler->handleCustomerChange($customer, $website);
        }
    }
}
