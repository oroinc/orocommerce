<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Api\Processor;

use Oro\Bundle\ApiBundle\Tests\Unit\Processor\FormProcessorTestCase;
use Oro\Bundle\CustomerBundle\Entity\CustomerGroup;
use Oro\Bundle\PricingBundle\Api\Processor\PriceListRelationTriggerHandlerForWebsiteAndCustomerGroup;
use Oro\Bundle\PricingBundle\Entity\PriceListCustomerGroupFallback;
use Oro\Bundle\PricingBundle\Entity\PriceListToCustomerGroup;
use Oro\Bundle\PricingBundle\Model\PriceListRelationTriggerHandler;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Bundle\WebsiteBundle\Entity\WebsiteAwareInterface;

class PriceListRelationTriggerHandlerForWebsiteAndCustomerGroupTest extends FormProcessorTestCase
{
    /** @var PriceListRelationTriggerHandler|\PHPUnit\Framework\MockObject\MockObject */
    private $relationChangesHandler;

    /** @var PriceListRelationTriggerHandlerForWebsiteAndCustomerGroup */
    private $processor;

    protected function setUp()
    {
        parent::setUp();

        $this->relationChangesHandler = $this->createMock(PriceListRelationTriggerHandler::class);

        $this->processor = new PriceListRelationTriggerHandlerForWebsiteAndCustomerGroup(
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

    public function testProcessForNotCustomerGroupAware()
    {
        $this->relationChangesHandler
            ->expects(static::never())
            ->method('handleCustomerGroupChange');

        $this->context->setResult($this->createMock(WebsiteAwareInterface::class));
        $this->processor->process($this->context);
    }


    public function testProcessForCustomerGroupFallback()
    {
        $group = $this->createCustomerGroup(1);
        $website = $this->createWebsite(2);
        $fallback = $this->createFallback($group, $website);

        $this->relationChangesHandler
            ->expects(static::once())
            ->method('handleCustomerGroupChange')
            ->with($group, $website);

        $this->context->setResult($fallback);
        $this->processor->process($this->context);
    }

    public function testProcessForMultipleCustomerGroupFallbacks()
    {
        $group = $this->createCustomerGroup(1);
        $website = $this->createWebsite(2);
        $fallback = $this->createFallback($group, $website);

        $group2 = $this->createCustomerGroup(3);
        $website2 = $this->createWebsite(4);
        $fallback2 = $this->createFallback($group2, $website2);

        $this->relationChangesHandler
            ->expects(static::exactly(2))
            ->method('handleCustomerGroupChange')
            ->withConsecutive(
                [$group, $website],
                [$group2, $website2]
            );

        $this->context->setResult([$fallback, $fallback, $fallback2]);
        $this->processor->process($this->context);
    }

    public function testProcessForPriceListToCustomerGroup()
    {
        $group = $this->createCustomerGroup(1);
        $website = $this->createWebsite(2);
        $relation = $this->createPriceListToCustomerGroup($group, $website);

        $this->relationChangesHandler
            ->expects(static::once())
            ->method('handleCustomerGroupChange')
            ->with($group, $website);

        $this->context->setResult($relation);
        $this->processor->process($this->context);
    }

    public function testProcessForMultiplePriceListToCustomerGroups()
    {
        $group = $this->createCustomerGroup(1);
        $website = $this->createWebsite(2);
        $relation = $this->createPriceListToCustomerGroup($group, $website);

        $group2 = $this->createCustomerGroup(3);
        $website2 = $this->createWebsite(4);
        $relation2 = $this->createPriceListToCustomerGroup($group2, $website2);

        $this->relationChangesHandler
            ->expects(static::exactly(2))
            ->method('handleCustomerGroupChange')
            ->withConsecutive(
                [$group, $website],
                [$group2, $website2]
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
     * @return CustomerGroup|\PHPUnit\Framework\MockObject\MockObject
     */
    private function createCustomerGroup(int $id)
    {
        $customerGroup = $this->createMock(CustomerGroup::class);
        $customerGroup
            ->expects(static::any())
            ->method('getId')
            ->willReturn($id);

        return $customerGroup;
    }

    /**
     * @param CustomerGroup $group
     * @param Website       $website
     *
     * @return PriceListToCustomerGroup
     */
    private function createPriceListToCustomerGroup(CustomerGroup $group, Website $website): PriceListToCustomerGroup
    {
        $relation = new PriceListToCustomerGroup();
        $relation
            ->setCustomerGroup($group)
            ->setWebsite($website);

        return $relation;
    }

    /**
     * @param CustomerGroup $group
     * @param Website       $website
     *
     * @return PriceListCustomerGroupFallback
     */
    private function createFallback(CustomerGroup $group, Website $website): PriceListCustomerGroupFallback
    {
        $fallback = new PriceListCustomerGroupFallback();
        $fallback
            ->setCustomerGroup($group)
            ->setWebsite($website)
            ->setFallback(0);

        return $fallback;
    }
}
