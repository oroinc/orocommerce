<?php

namespace Oro\Bundle\PricingBundle\EventListener;

use Doctrine\Common\Collections\Criteria;
use Oro\Bundle\UIBundle\Event\BeforeListRenderEvent;
use Oro\Bundle\PricingBundle\Entity\PriceListAccountFallback;
use Oro\Bundle\PricingBundle\Entity\PriceListToAccount;
use Oro\Bundle\AccountBundle\Entity\Account;

class AccountFormViewListener extends AbstractAccountFormViewListener
{
    /**
     * @var array
     */
    protected $fallbackChoices = [
        PriceListAccountFallback::CURRENT_ACCOUNT_ONLY =>
            'oro.pricing.fallback.current_account_only.label',
        PriceListAccountFallback::ACCOUNT_GROUP =>
            'oro.pricing.fallback.account_group.label',
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
        $account = $this->doctrineHelper->getEntityReference('OroAccountBundle:Account', (int)$request->get('id'));

        /** @var PriceListToAccount[] $priceLists */
        $websites = $this->websiteProvider->getWebsites();
        $priceLists = $this->doctrineHelper
            ->getEntityRepository('OroPricingBundle:PriceListToAccount')
            ->findBy(['account' => $account, 'website' => $websites], ['priority' => Criteria::ASC]);

        /** @var PriceListAccountFallback $fallbackEntity */
        $fallbackEntity = $this->doctrineHelper
            ->getEntityRepository('OroPricingBundle:PriceListAccountFallback')
            ->findOneBy(['account' => $account, 'website' => $websites]);

        $fallback = $fallbackEntity
            ? $this->fallbackChoices[$fallbackEntity->getFallback()]
            : $this->fallbackChoices[PriceListAccountFallback::ACCOUNT_GROUP];

        $this->addPriceListInfo($event, $priceLists, $fallback);
    }
}
