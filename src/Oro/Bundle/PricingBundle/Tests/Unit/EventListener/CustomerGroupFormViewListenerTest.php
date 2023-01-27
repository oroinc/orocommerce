<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\EventListener;

use Oro\Bundle\CustomerBundle\Entity\CustomerGroup;
use Oro\Bundle\PricingBundle\Entity\PriceListCustomerFallback;
use Oro\Bundle\PricingBundle\Entity\PriceListCustomerGroupFallback;
use Oro\Bundle\PricingBundle\Entity\PriceListToCustomerGroup;
use Oro\Bundle\PricingBundle\Entity\Repository\PriceListToCustomerGroupRepository;
use Oro\Bundle\PricingBundle\EventListener\CustomerGroupFormViewListener;
use Oro\Bundle\UIBundle\Event\BeforeListRenderEvent;

class CustomerGroupFormViewListenerTest extends AbstractCustomerFormViewListenerTest
{
    public function testOnCustomerGroupViewFeatureDisabled()
    {
        $this->featureChecker->expects($this->once())
            ->method('isFeatureEnabled')
            ->with('feature1', null)
            ->willReturn(false);

        $listener = $this->getListener();
        $listener->setFeatureChecker($this->featureChecker);
        $listener->addFeature('feature1');

        $this->requestStack->expects($this->never())
            ->method('getCurrentRequest');

        $event = $this->createEvent($this->env);
        $listener->onCustomerGroupView($event);
    }

    /**
     * @return CustomerGroupFormViewListener
     */
    protected function getListener()
    {
        return new CustomerGroupFormViewListener(
            $this->requestStack,
            $this->translator,
            $this->doctrineHelper,
            $this->websiteProvider
        );
    }

    /**
     * @return PriceListToCustomerGroup[]|\PHPUnit\Framework\MockObject\MockObject[]
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

        $priceToCustomerGroupRepository = $this->createMock(PriceListToCustomerGroupRepository::class);
        $priceToCustomerGroupRepository->expects($this->once())
            ->method('findBy')
            ->with(['customerGroup' => $customerGroup, 'website' => $websites])
            ->willReturn($priceListsToCustomerGroup);
        $fallbackRepository = $this->createMock(PriceListToCustomerGroupRepository::class);
        $fallbackRepository->expects($this->once())
            ->method('findOneBy')
            ->with(['customerGroup' => $customerGroup, 'website' => $websites])
            ->willReturn($fallbackEntity);
        $this->doctrineHelper->expects($this->once())
            ->method('getEntityReference')
            ->willReturn($customerGroup);
        $this->doctrineHelper->expects($this->exactly(2))
            ->method('getEntityRepository')
            ->willReturnMap([
                ['OroPricingBundle:PriceListToCustomerGroup', $priceToCustomerGroupRepository],
                ['OroPricingBundle:PriceListCustomerGroupFallback', $fallbackRepository]
            ]);

        return [$priceListToCustomerGroup1, $priceListToCustomerGroup2];
    }

    /**
     * {@inheritdoc}
     */
    protected function processEvent(BeforeListRenderEvent $event)
    {
        $this->featureChecker->expects($this->once())
            ->method('isFeatureEnabled')
            ->with('feature1', null)
            ->willReturn(true);

        $listener = $this->getListener();
        $listener->setFeatureChecker($this->featureChecker);
        $listener->addFeature('feature1');
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
