<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Api\Processor;

use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\PricingBundle\Api\Processor\PriceListRelationTriggerHandlerForWebsiteAndCustomer;
use Oro\Bundle\PricingBundle\Entity\PriceListCustomerFallback;
use Oro\Bundle\PricingBundle\Entity\PriceListToCustomer;
use Oro\Bundle\PricingBundle\Tests\Unit\Api\Processor\PriceListRelationTriggerHandlerProcessorTestCase as BaseTestCase;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Bundle\WebsiteBundle\Entity\WebsiteAwareInterface;

class PriceListRelationTriggerHandlerForWebsiteAndCustomerTest extends BaseTestCase
{
    /**
     * @var PriceListRelationTriggerHandlerForWebsiteAndCustomer
     */
    private $processor;

    protected function setUp()
    {
        parent::setUp();

        $this->processor = new PriceListRelationTriggerHandlerForWebsiteAndCustomer(
            $this->relationChangesHandler
        );
    }

    public function testProcessForNotWebsiteAware()
    {
        $context = $this->createContext(null);

        $this->relationChangesHandler
            ->expects(static::never())
            ->method('handleCustomerGroupChange');

        $this->processor->process($context);
    }

    public function testProcessForNotCustomerAware()
    {
        $context = $this->createContext($this->createMock(WebsiteAwareInterface::class));

        $this->relationChangesHandler
            ->expects(static::never())
            ->method('handleCustomerGroupChange');

        $this->processor->process($context);
    }

    public function testProcessForCustomerFallback()
    {
        $customer = $this->createCustomer(1);
        $website = $this->createWebsite(2);
        $fallback = $this->createFallback($customer, $website);

        $context = $this->createContext($fallback);

        $this->relationChangesHandler
            ->expects(static::once())
            ->method('handleCustomerChange')
            ->with($customer, $website);

        $this->processor->process($context);
    }

    public function testProcessForMultipleCustomerFallbacks()
    {
        $customer = $this->createCustomer(1);
        $website = $this->createWebsite(2);
        $fallback = $this->createFallback($customer, $website);

        $customer2 = $this->createCustomer(3);
        $website2 = $this->createWebsite(4);
        $fallback2 = $this->createFallback($customer2, $website2);

        $context = $this->createContext([$fallback, $fallback, $fallback2]);

        $this->relationChangesHandler
            ->expects(static::exactly(2))
            ->method('handleCustomerChange')
            ->withConsecutive(
                [$customer, $website],
                [$customer2, $website2]
            );

        $this->processor->process($context);
    }

    public function testProcessForPriceListToCustomer()
    {
        $customer = $this->createCustomer(1);
        $website = $this->createWebsite(2);
        $relation = $this->createPriceListToCustomer($customer, $website);

        $context = $this->createContext($relation);

        $this->relationChangesHandler
            ->expects(static::once())
            ->method('handleCustomerChange')
            ->with($customer, $website);

        $this->processor->process($context);
    }

    public function testProcessForMultiplePriceListToCustomerGroups()
    {
        $customer = $this->createCustomer(1);
        $website = $this->createWebsite(2);
        $relation = $this->createPriceListToCustomer($customer, $website);

        $customer2 = $this->createCustomer(3);
        $website2 = $this->createWebsite(4);
        $relation2 = $this->createPriceListToCustomer($customer2, $website2);

        $context = $this->createContext([$relation, $relation, $relation2]);

        $this->relationChangesHandler
            ->expects(static::exactly(2))
            ->method('handleCustomerChange')
            ->withConsecutive(
                [$customer, $website],
                [$customer2, $website2]
            );

        $this->processor->process($context);
    }

    /**
     * @param int $id
     *
     * @return Customer|\PHPUnit_Framework_MockObject_MockObject
     */
    private function createCustomer(int $id)
    {
        $customer = $this->createMock(Customer::class);
        $customer
            ->expects(static::any())
            ->method('getId')
            ->willReturn($id);

        return $customer;
    }

    /**
     * @param Customer $customer
     * @param Website  $website
     *
     * @return PriceListCustomerFallback
     */
    private function createFallback(Customer $customer, Website $website): PriceListCustomerFallback
    {
        $fallback = new PriceListCustomerFallback();
        $fallback
            ->setCustomer($customer)
            ->setWebsite($website)
            ->setFallback(0);

        return $fallback;
    }

    /**
     * @param Customer $customer
     * @param Website  $website
     *
     * @return PriceListToCustomer
     */
    private function createPriceListToCustomer(Customer $customer, Website $website): PriceListToCustomer
    {
        $relation = new PriceListToCustomer();
        $relation
            ->setCustomer($customer)
            ->setWebsite($website);

        return $relation;
    }
}
