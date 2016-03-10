<?php

namespace OroB2B\Bundle\PricingBundle\EventListener;

use OroB2B\Bundle\AccountBundle\Event\AccountGroupEvent;
use OroB2B\Bundle\PricingBundle\Entity\PriceListAccountGroupFallback;
use OroB2B\Bundle\WebsiteBundle\Entity\Website;

class AccountGroupListener extends AbstractPriceListCollectionAwareListener
{
    /**
     * @var string
     */
    protected $relationClass = 'OroB2B\Bundle\PricingBundle\Entity\PriceListToAccountGroup';

    /**
     * @var string
     */
    protected $fallbackClass = 'OroB2B\Bundle\PricingBundle\Entity\PriceListAccountGroupFallback';

    /**
     * @param AccountGroupEvent $event
     */
    public function onGroupRemove(AccountGroupEvent $event)
    {
        $this->triggerHandler->handleAccountGroupRemove($event->getData());
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
            ->findBy(['accountGroup' => $targetEntity]);
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
        $fallback = new PriceListAccountGroupFallback();
        $fallback->setAccountGroup($targetEntity)
            ->setWebsite($website);

        return $fallback;
    }

    /**
     * {@inheritdoc}
     */
    protected function getDefaultFallback()
    {
        return PriceListAccountGroupFallback::WEBSITE;
    }

    /**
     * {@inheritdoc}
     */
    protected function handleCollectionChanges($targetEntity, Website $website)
    {
        $this->triggerHandler->handleAccountGroupChange($targetEntity, $website);
    }
}
