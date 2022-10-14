<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\EventListener;

use Doctrine\ORM\EntityRepository;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\PricingBundle\Entity\PriceListCustomerFallback;
use Oro\Bundle\PricingBundle\Entity\PriceListToCustomer;
use Oro\Bundle\PricingBundle\Entity\Repository\PriceListToCustomerRepository;
use Oro\Bundle\PricingBundle\EventListener\CustomerFormViewListener;
use Oro\Bundle\UIBundle\Event\BeforeListRenderEvent;

class CustomerFormViewListenerTest extends AbstractCustomerFormViewListenerTest
{
    public function testOnCustomerViewFeatureDisabled()
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
        $listener->onCustomerView($event);
    }

    /**
     * @return CustomerFormViewListener
     */
    protected function getListener()
    {
        return new CustomerFormViewListener(
            $this->requestStack,
            $this->translator,
            $this->doctrineHelper,
            $this->websiteProvider
        );
    }

    /**
     * @return PriceListToCustomer[]|\PHPUnit\Framework\MockObject\MockObject[]
     */
    protected function setRepositoryExpectations()
    {
        $websites = $this->websiteProvider->getWebsites();

        $customer = new Customer();

        $priceListToCustomer1 = new PriceListToCustomer();
        $priceListToCustomer1->setCustomer($customer);
        $priceListToCustomer1->setSortOrder(3);
        $priceListToCustomer2 = clone $priceListToCustomer1;
        $priceListsToCustomer = [$priceListToCustomer1, $priceListToCustomer2];

        $fallbackEntity = new PriceListCustomerFallback();
        $fallbackEntity->setCustomer($customer);
        $fallbackEntity->setFallback(PriceListCustomerFallback::CURRENT_ACCOUNT_ONLY);
        $fallbackEntity->setWebsite(current($websites));

        $priceToCustomerRepository = $this->createMock(PriceListToCustomerRepository::class);

        $priceToCustomerRepository->expects($this->once())
            ->method('findBy')
            ->with(['customer' => $customer, 'website' => $websites])
            ->willReturn($priceListsToCustomer);

        $fallbackRepository = $this->createMock(EntityRepository::class);

        $fallbackRepository->expects($this->once())
            ->method('findOneBy')
            ->with(['customer' => $customer, 'website' => $websites])
            ->willReturn($fallbackEntity);

        $this->doctrineHelper->expects($this->once())
            ->method('getEntityReference')
            ->willReturn($customer);

        $this->doctrineHelper->expects($this->exactly(2))
            ->method('getEntityRepository')
            ->willReturnMap([
                ['OroPricingBundle:PriceListToCustomer', $priceToCustomerRepository],
                ['OroPricingBundle:PriceListCustomerFallback', $fallbackRepository],
            ]);

        return [$priceListToCustomer1, $priceListToCustomer2];
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
        $listener->onCustomerView($event);
    }

    /**
     * {@inheritdoc}
     */
    protected function getFallbackLabel()
    {
        return 'oro.pricing.fallback.current_customer_only.label';
    }
}
