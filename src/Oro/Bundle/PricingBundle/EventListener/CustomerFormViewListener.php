<?php

namespace Oro\Bundle\PricingBundle\EventListener;

use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\PricingBundle\Entity\PriceListCustomerFallback;
use Oro\Bundle\PricingBundle\Entity\PriceListToCustomer;
use Oro\Bundle\PricingBundle\Form\Type\PriceListCollectionType;
use Oro\Bundle\UIBundle\Event\BeforeListRenderEvent;

/**
 * Adds scroll blocks with price list data on customer view page
 */
class CustomerFormViewListener extends AbstractCustomerFormViewListener
{
    /**
     * @var array
     */
    protected $fallbackChoices = [
        PriceListCustomerFallback::CURRENT_ACCOUNT_ONLY =>
            'oro.pricing.fallback.current_customer_only.label',
        PriceListCustomerFallback::ACCOUNT_GROUP =>
            'oro.pricing.fallback.customer_group.label',
    ];

    public function onCustomerView(BeforeListRenderEvent $event)
    {
        if (!$this->isFeaturesEnabled()) {
            return;
        }

        $request = $this->requestStack->getCurrentRequest();
        if (!$request) {
            return;
        }

        /** @var Customer $customer */
        $customer = $this->doctrineHelper->getEntityReference('OroCustomerBundle:Customer', (int)$request->get('id'));

        /** @var PriceListToCustomer[] $priceLists */
        $websites = $this->websiteProvider->getWebsites();
        $priceLists = $this->doctrineHelper
            ->getEntityRepository('OroPricingBundle:PriceListToCustomer')
            ->findBy(
                ['customer' => $customer, 'website' => $websites],
                ['sortOrder' => PriceListCollectionType::DEFAULT_ORDER]
            );

        /** @var PriceListCustomerFallback $fallbackEntity */
        $fallbackEntity = $this->doctrineHelper
            ->getEntityRepository('OroPricingBundle:PriceListCustomerFallback')
            ->findOneBy(['customer' => $customer, 'website' => $websites]);

        $fallback = $fallbackEntity
            ? $this->fallbackChoices[$fallbackEntity->getFallback()]
            : $this->fallbackChoices[PriceListCustomerFallback::ACCOUNT_GROUP];

        $this->addPriceListInfo($event, $priceLists, $fallback);
    }
}
