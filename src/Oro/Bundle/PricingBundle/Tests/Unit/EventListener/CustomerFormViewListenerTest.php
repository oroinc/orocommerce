<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\EventListener;

use Symfony\Component\HttpFoundation\RequestStack;

use Oro\Bundle\UIBundle\Event\BeforeListRenderEvent;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\PricingBundle\Entity\PriceListCustomerFallback;
use Oro\Bundle\PricingBundle\Entity\PriceListToCustomer;
use Oro\Bundle\PricingBundle\EventListener\CustomerFormViewListener;

class CustomerFormViewListenerTest extends AbstractCustomerFormViewListenerTest
{
    /**
     * @param RequestStack $requestStack
     * @return CustomerFormViewListener
     */
    protected function getListener(RequestStack $requestStack)
    {
        return new CustomerFormViewListener(
            $requestStack,
            $this->translator,
            $this->doctrineHelper,
            $this->websiteProvider
        );
    }

    /**
     * @return PriceListToCustomer[]|\PHPUnit_Framework_MockObject_MockObject[]
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

        $priceToCustomerRepository = $this
            ->getMockBuilder('Oro\Bundle\PricingBundle\Entity\Repository\PriceListToCustomerRepository')
            ->disableOriginalConstructor()
            ->getMock();

        $priceToCustomerRepository->expects($this->once())
            ->method('findBy')
            ->with(['customer' => $customer, 'website' => $websites])
            ->willReturn($priceListsToCustomer);

        $fallbackRepository = $this
            ->getMockBuilder('Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->getMock();

        $fallbackRepository->expects($this->once())
            ->method('findOneBy')
            ->with(['customer' => $customer, 'website' => $websites])
            ->willReturn($fallbackEntity);

        $this->doctrineHelper->expects($this->once())
            ->method('getEntityReference')
            ->willReturn($customer);

        $this->doctrineHelper->expects($this->exactly(2))
            ->method('getEntityRepository')
            ->will(
                $this->returnValueMap(
                    [
                        ['OroPricingBundle:PriceListToCustomer', $priceToCustomerRepository],
                        ['OroPricingBundle:PriceListCustomerFallback', $fallbackRepository],
                    ]
                )
            );

        return [$priceListToCustomer1, $priceListToCustomer2];
    }

    /**
     * {@inheritdoc}
     */
    protected function processEvent(RequestStack $requestStack, BeforeListRenderEvent $event)
    {
        $listener = $this->getListener($requestStack);
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
