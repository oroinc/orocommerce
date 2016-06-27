<?php

namespace OroB2B\Bundle\PricingBundle\EventListener;

use Oro\Bundle\UIBundle\Event\BeforeListRenderEvent;

use OroB2B\Bundle\PricingBundle\Entity\PriceListAccountGroupFallback;
use OroB2B\Bundle\PricingBundle\Entity\PriceListToAccountGroup;
use OroB2B\Bundle\AccountBundle\Entity\AccountGroup;

class AccountGroupFormViewListener extends AbstractAccountFormViewListener
{
    /**
     * @var array
     */
    protected $fallbackChoices = [
        PriceListAccountGroupFallback::CURRENT_ACCOUNT_GROUP_ONLY =>
            'orob2b.pricing.fallback.current_account_group_only.label',
        PriceListAccountGroupFallback::WEBSITE =>
            'orob2b.pricing.fallback.website.label',
    ];
    
    /**
     * @param BeforeListRenderEvent $event
     */
    public function onAccountGroupView(BeforeListRenderEvent $event)
    {
        $request = $this->requestStack->getCurrentRequest();
        if (!$request) {
            return;
        }

        /** @var AccountGroup $accountGroup */
        $accountGroup = $this->doctrineHelper->getEntityReference(
            'OroB2BAccountBundle:AccountGroup',
            (int)$request->get('id')
        );
        
        /** @var PriceListToAccountGroup[] $priceLists */
        $priceLists = $this->doctrineHelper
            ->getEntityRepository('OroB2BPricingBundle:PriceListToAccountGroup')
            ->findBy(['accountGroup' => $accountGroup]);
        
        /** @var PriceListAccountGroupFallback $fallbackEntity */
        $fallbackEntity = $this->doctrineHelper
            ->getEntityRepository('OroB2BPricingBundle:PriceListAccountGroupFallback')
            ->findOneBy(['accountGroup' => $accountGroup]);

        $fallback = $fallbackEntity
            ? $this->fallbackChoices[$fallbackEntity->getFallback()]
            : $this->fallbackChoices[PriceListAccountGroupFallback::WEBSITE];
        
        $this->addPriceListInfo($event, $priceLists, $fallback);
    }
}
