<?php

namespace Oro\Bundle\PricingBundle\EventListener;

use Oro\Bundle\AccountBundle\Event\AccountEvent;
use Oro\Bundle\PricingBundle\Entity\PriceListAccountFallback;
use Oro\Bundle\PricingBundle\Entity\Repository\PriceListToAccountRepository;
use Oro\Bundle\WebsiteBundle\Entity\Website;

class AccountListener extends AbstractPriceListCollectionAwareListener
{
    /**
     * @var string
     */
    protected $relationClass = 'Oro\Bundle\PricingBundle\Entity\PriceListToAccount';

    /**
     * @var string
     */
    protected $fallbackClass = 'Oro\Bundle\PricingBundle\Entity\PriceListAccountFallback';

    /**
     * @param AccountEvent $event
     */
    public function onAccountGroupChange(AccountEvent $event)
    {
        /** @var PriceListToAccountRepository $repository */
        $repository = $this->doctrineHelper->getEntityRepository($this->relationClass);

        $accountWebsitePairs = $repository->getAccountWebsitePairsByAccount($event->getAccount());
        foreach ($accountWebsitePairs as $accountWebsitePair) {
            $this->triggerHandler
                ->handleAccountChange($accountWebsitePair->getAccount(), $accountWebsitePair->getWebsite());
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
            ->findBy(['account' => $targetEntity]);
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
        $fallback = new PriceListAccountFallback();
        $fallback->setAccount($targetEntity)
            ->setWebsite($website);

        return $fallback;
    }

    /**
     * {@inheritdoc}
     */
    protected function getDefaultFallback()
    {
        return PriceListAccountFallback::ACCOUNT_GROUP;
    }

    /**
     * {@inheritdoc}
     */
    protected function handleCollectionChanges($targetEntity, Website $website)
    {
        $this->triggerHandler->handleAccountChange($targetEntity, $website);
    }
}
