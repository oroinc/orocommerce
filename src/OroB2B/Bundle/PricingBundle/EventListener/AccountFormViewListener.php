<?php

namespace OroB2B\Bundle\PricingBundle\EventListener;

use Oro\Bundle\UIBundle\Event\BeforeListRenderEvent;

use OroB2B\Bundle\PricingBundle\Entity\PriceListAccountFallback;
use OroB2B\Bundle\PricingBundle\Entity\PriceListToAccount;
use OroB2B\Bundle\AccountBundle\Entity\Account;

class AccountFormViewListener extends AbstractAccountFormViewListener
{
    /**
     * @var array
     */
    protected $fallbackChoices = [
        PriceListAccountFallback::CURRENT_ACCOUNT_ONLY =>
            'orob2b.pricing.fallback.current_account_only.label',
        PriceListAccountFallback::ACCOUNT_GROUP =>
            'orob2b.pricing.fallback.account_group.label',
    ];

    /**
     * @param BeforeListRenderEvent $event
     */
    public function onAccountView(BeforeListRenderEvent $event)
    {
        $request = $this->requestStack->getCurrentRequest();
        if (!$request) {
            return;
        }

        /** @var Account $account */
        $account = $this->doctrineHelper->getEntityReference('OroB2BAccountBundle:Account', (int)$request->get('id'));

        /** @var PriceListToAccount[] $priceLists */
        $websites = $this->websiteProvider->getWebsites();
        $priceLists = $this->doctrineHelper
            ->getEntityRepository('OroB2BPricingBundle:PriceListToAccount')
            ->findBy(['account' => $account, 'website' => $websites]);

        /** @var PriceListAccountFallback $fallbackEntity */
        $fallbackEntity = $this->doctrineHelper
            ->getEntityRepository('OroB2BPricingBundle:PriceListAccountFallback')
            ->findOneBy(['account' => $account, 'website' => $websites]);

        $fallback = $fallbackEntity
            ? $this->fallbackChoices[$fallbackEntity->getFallback()]
            : $this->fallbackChoices[PriceListAccountFallback::ACCOUNT_GROUP];

        $this->addPriceListInfo($event, $priceLists, $fallback);
    }
}
