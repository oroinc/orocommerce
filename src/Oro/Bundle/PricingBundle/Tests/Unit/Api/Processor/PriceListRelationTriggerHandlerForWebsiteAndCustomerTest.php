<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Api\Processor;

use Oro\Bundle\ApiBundle\Tests\Unit\Processor\FormProcessorTestCase;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\PricingBundle\Api\Processor\PriceListRelationTriggerHandlerForWebsiteAndCustomer;
use Oro\Bundle\PricingBundle\Entity\PriceListCustomerFallback;
use Oro\Bundle\PricingBundle\Entity\PriceListToCustomer;
use Oro\Bundle\PricingBundle\Model\PriceListRelationTriggerHandler;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Bundle\WebsiteBundle\Entity\WebsiteAwareInterface;

class PriceListRelationTriggerHandlerForWebsiteAndCustomerTest extends FormProcessorTestCase
{
    /** @var PriceListRelationTriggerHandler|\PHPUnit\Framework\MockObject\MockObject */
    private $relationChangesHandler;

    /** @var PriceListRelationTriggerHandlerForWebsiteAndCustomer */
    private $processor;

    protected function setUp()
    {
        parent::setUp();

        $this->relationChangesHandler = $this->createMock(PriceListRelationTriggerHandler::class);

        $this->processor = new PriceListRelationTriggerHandlerForWebsiteAndCustomer(
            $this->relationChangesHandler
        );
    }

    public function testProcessForNotWebsiteAware()
    {
        $this->relationChangesHandler
            ->expects(static::never())
            ->method('handleCustomerGroupChange');

        $this->processor->process($this->context);
    }

    public function testProcessForNotCustomerAware()
    {
        $this->relationChangesHandler
            ->expects(static::never())
            ->method('handleCustomerGroupChange');

        $this->context->setResult($this->createMock(WebsiteAwareInterface::class));
        $this->processor->process($this->context);
    }

    public function testProcessForCustomerFallback()
    {
        $customer = $this->createCustomer(1);
        $website = $this->createWebsite(2);
        $fallback = $this->createFallback($customer, $website);

        $this->relationChangesHandler
            ->expects(static::once())
            ->method('handleCustomerChange')
            ->with($customer, $website);

        $this->context->setResult($fallback);
        $this->processor->process($this->context);
    }

    public function testProcessForMultipleCustomerFallbacks()
    {
        $customer = $this->createCustomer(1);
        $website = $this->createWebsite(2);
        $fallback = $this->createFallback($customer, $website);

        $customer2 = $this->createCustomer(3);
        $website2 = $this->createWebsite(4);
        $fallback2 = $this->createFallback($customer2, $website2);

        $this->relationChangesHandler
            ->expects(static::exactly(2))
            ->method('handleCustomerChange')
            ->withConsecutive(
                [$customer, $website],
                [$customer2, $website2]
            );

        $this->context->setResult([$fallback, $fallback, $fallback2]);
        $this->processor->process($this->context);
    }

    public function testProcessForPriceListToCustomer()
    {
        $customer = $this->createCustomer(1);
        $website = $this->createWebsite(2);
        $relation = $this->createPriceListToCustomer($customer, $website);

        $this->relationChangesHandler
            ->expects(static::once())
            ->method('handleCustomerChange')
            ->with($customer, $website);

        $this->context->setResult($relation);
        $this->processor->process($this->context);
    }

    public function testProcessForMultiplePriceListToCustomerGroups()
    {
        $customer = $this->createCustomer(1);
        $website = $this->createWebsite(2);
        $relation = $this->createPriceListToCustomer($customer, $website);

        $customer2 = $this->createCustomer(3);
        $website2 = $this->createWebsite(4);
        $relation2 = $this->createPriceListToCustomer($customer2, $website2);

        $this->relationChangesHandler
            ->expects(static::exactly(2))
            ->method('handleCustomerChange')
            ->withConsecutive(
                [$customer, $website],
                [$customer2, $website2]
            );

        $this->context->setResult([$relation, $relation, $relation2]);
        $this->processor->process($this->context);
    }

    /**
     * @param int $id
     *
     * @return Website|\PHPUnit\Framework\MockObject\MockObject
     */
    private function createWebsite(int $id)
    {
        $website = $this->createMock(Website::class);
        $website
            ->expects(static::any())
            ->method('getId')
            ->willReturn($id);

        return $website;
    }

    /**
     * @param int $id
     *
     * @return Customer|\PHPUnit\Framework\MockObject\MockObject
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
