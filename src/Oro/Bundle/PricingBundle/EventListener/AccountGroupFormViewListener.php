<?php

namespace Oro\Bundle\PricingBundle\EventListener;

use Oro\Bundle\UIBundle\Event\BeforeListRenderEvent;
use Oro\Bundle\PricingBundle\Entity\PriceListAccountGroupFallback;
use Oro\Bundle\PricingBundle\Entity\PriceListToAccountGroup;
use Oro\Bundle\AccountBundle\Entity\AccountGroup;

class AccountGroupFormViewListener extends AbstractAccountFormViewListener
{
    /**
     * @var array
     */
    protected $fallbackChoices = [
        PriceListAccountGroupFallback::CURRENT_ACCOUNT_GROUP_ONLY =>
            'oro.pricing.fallback.current_account_group_only.label',
        PriceListAccountGroupFallback::WEBSITE =>
            'oro.pricing.fallback.website.label',
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
            'OroAccountBundle:AccountGroup',
            (int)$request->get('id')
        );
        
        /** @var PriceListToAccountGroup[] $priceLists */
        $priceLists = $this->doctrineHelper
            ->getEntityRepository('OroPricingBundle:PriceListToAccountGroup')
            ->findBy(['accountGroup' => $accountGroup, 'website' => $this->websiteProvider->getWebsites()]);
        
        /** @var PriceListAccountGroupFallback $fallbackEntity */
        $fallbackEntity = $this->doctrineHelper
            ->getEntityRepository('OroPricingBundle:PriceListAccountGroupFallback')
            ->findOneBy(['accountGroup' => $accountGroup, 'website' => $this->websiteProvider->getWebsites()]);

        $fallback = $fallbackEntity
            ? $this->fallbackChoices[$fallbackEntity->getFallback()]
            : $this->fallbackChoices[PriceListAccountGroupFallback::WEBSITE];
        
        $this->addPriceListInfo($event, $priceLists, $fallback);
    }
}
