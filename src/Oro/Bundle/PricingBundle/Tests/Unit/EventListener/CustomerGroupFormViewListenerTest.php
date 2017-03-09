<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\EventListener;

use Symfony\Component\HttpFoundation\RequestStack;

use Oro\Bundle\UIBundle\Event\BeforeListRenderEvent;
use Oro\Bundle\PricingBundle\Entity\PriceListCustomerFallback;
use Oro\Bundle\PricingBundle\Entity\PriceListCustomerGroupFallback;
use Oro\Bundle\PricingBundle\Entity\PriceListToCustomerGroup;
use Oro\Bundle\CustomerBundle\Entity\CustomerGroup;
use Oro\Bundle\PricingBundle\EventListener\CustomerGroupFormViewListener;

class CustomerGroupFormViewListenerTest extends AbstractCustomerFormViewListenerTest
{
    /**
     * @param RequestStack $requestStack
     * @return CustomerGroupFormViewListener
     */
    protected function getListener(RequestStack $requestStack)
    {
        return new CustomerGroupFormViewListener(
            $requestStack,
            $this->translator,
            $this->doctrineHelper,
            $this->websiteProvider
        );
    }

    /**
     * @return PriceListToCustomerGroup[]|\PHPUnit_Framework_MockObject_MockObject[]
     */
    protected function setRepositoryExpectations()
    {
        $websites = $this->websiteProvider->getWebsites();

        $customerGroup = new CustomerGroup();

        $priceListToCustomerGroup1 = new PriceListToCustomerGroup();
        $priceListToCustomerGroup1->setCustomerGroup($customerGroup);
        $priceListToCustomerGroup1->setSortOrder(3);
        $priceListToCustomerGroup1->setWebsite(current($this->websiteProvider->getWebsites()));
        $priceListToCustomerGroup2 = clone $priceListToCustomerGroup1;
        $priceListsToCustomerGroup = [$priceListToCustomerGroup1, $priceListToCustomerGroup2];

        $fallbackEntity = new PriceListCustomerGroupFallback();
        $fallbackEntity->setCustomerGroup($customerGroup);
        $fallbackEntity->setFallback(PriceListCustomerFallback::CURRENT_ACCOUNT_ONLY);

        $priceToCustomerGroupRepository = $this
            ->getMockBuilder('Oro\Bundle\PricingBundle\Entity\Repository\PriceListToCustomerGroupRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $priceToCustomerGroupRepository->expects($this->once())
            ->method('findBy')
            ->with(['customerGroup' => $customerGroup, 'website' => $websites])
            ->willReturn($priceListsToCustomerGroup);
        $fallbackRepository = $this
            ->getMockBuilder('Oro\Bundle\PricingBundle\Entity\Repository\PriceListToCustomerGroupRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $fallbackRepository->expects($this->once())
            ->method('findOneBy')
            ->with(['customerGroup' => $customerGroup, 'website' => $websites])
            ->willReturn($fallbackEntity);
        $this->doctrineHelper->expects($this->once())
            ->method('getEntityReference')
            ->willReturn($customerGroup);
        $this->doctrineHelper->expects($this->exactly(2))
            ->method('getEntityRepository')
            ->will(
                $this->returnValueMap(
                    [
                        ['OroPricingBundle:PriceListToCustomerGroup', $priceToCustomerGroupRepository],
                        ['OroPricingBundle:PriceListCustomerGroupFallback', $fallbackRepository]
                    ]
                )
            );

        return [$priceListToCustomerGroup1, $priceListToCustomerGroup2];
    }

    /**
     * {@inheritdoc}
     */
    protected function processEvent(RequestStack $requestStack, BeforeListRenderEvent $event)
    {
        $listener = $this->getListener($requestStack);
        $listener->onCustomerGroupView($event);
    }

    /**
     * {@inheritdoc}
     */
    protected function getFallbackLabel()
    {
        return 'oro.pricing.fallback.current_customer_group_only.label';
    }
}
