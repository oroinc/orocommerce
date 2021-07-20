<?php

namespace Oro\Bundle\PricingBundle\EventListener;

use Oro\Bundle\CustomerBundle\Entity\CustomerGroup;
use Oro\Bundle\PricingBundle\Entity\PriceListCustomerGroupFallback;
use Oro\Bundle\PricingBundle\Entity\PriceListToCustomerGroup;
use Oro\Bundle\PricingBundle\Form\Type\PriceListCollectionType;
use Oro\Bundle\UIBundle\Event\BeforeListRenderEvent;

/**
 * Adds scroll blocks with price list data on customer group view page
 */
class CustomerGroupFormViewListener extends AbstractCustomerFormViewListener
{
    /**
     * @var array
     */
    protected $fallbackChoices = [
        PriceListCustomerGroupFallback::CURRENT_ACCOUNT_GROUP_ONLY =>
            'oro.pricing.fallback.current_customer_group_only.label',
        PriceListCustomerGroupFallback::WEBSITE =>
            'oro.pricing.fallback.website.label',
    ];

    public function onCustomerGroupView(BeforeListRenderEvent $event)
    {
        if (!$this->isFeaturesEnabled()) {
            return;
        }

        $request = $this->requestStack->getCurrentRequest();
        if (!$request) {
            return;
        }

        /** @var CustomerGroup $customerGroup */
        $customerGroup = $this->doctrineHelper->getEntityReference(
            'OroCustomerBundle:CustomerGroup',
            (int)$request->get('id')
        );

        /** @var PriceListToCustomerGroup[] $priceLists */
        $priceLists = $this->doctrineHelper
            ->getEntityRepository('OroPricingBundle:PriceListToCustomerGroup')
            ->findBy(
                ['customerGroup' => $customerGroup, 'website' => $this->websiteProvider->getWebsites()],
                ['sortOrder' => PriceListCollectionType::DEFAULT_ORDER]
            );

        /** @var PriceListCustomerGroupFallback $fallbackEntity */
        $fallbackEntity = $this->doctrineHelper
            ->getEntityRepository('OroPricingBundle:PriceListCustomerGroupFallback')
            ->findOneBy(['customerGroup' => $customerGroup, 'website' => $this->websiteProvider->getWebsites()]);

        $fallback = $fallbackEntity
            ? $this->fallbackChoices[$fallbackEntity->getFallback()]
            : $this->fallbackChoices[PriceListCustomerGroupFallback::WEBSITE];

        $this->addPriceListInfo($event, $priceLists, $fallback);
    }
}
