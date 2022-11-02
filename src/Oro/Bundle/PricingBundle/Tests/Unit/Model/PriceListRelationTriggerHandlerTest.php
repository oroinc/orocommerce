<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Model;

use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Entity\CustomerGroup;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Model\PriceListRelationTriggerHandler;
use Oro\Bundle\PricingBundle\Model\PriceListRelationTriggerHandlerInterface;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use PHPUnit\Framework\MockObject\MockObject;

class PriceListRelationTriggerHandlerTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var PriceListRelationTriggerHandlerInterface|MockObject
     */
    private $cplHandler;

    /**
     * @var PriceListRelationTriggerHandlerInterface|MockObject
     */
    private $flatHandler;

    /**
     * @var FeatureChecker|MockObject
     */
    private $featureChecker;

    /**
     * @var PriceListRelationTriggerHandler
     */
    private $handler;

    protected function setUp(): void
    {
        $this->cplHandler = $this->createMock(PriceListRelationTriggerHandlerInterface::class);
        $this->flatHandler = $this->createMock(PriceListRelationTriggerHandlerInterface::class);
        $this->featureChecker = $this->createMock(FeatureChecker::class);

        $this->handler = new PriceListRelationTriggerHandler(
            $this->cplHandler,
            $this->flatHandler
        );
        $this->handler->setFeatureChecker($this->featureChecker);
        $this->handler->addFeature('oro_price_lists_flat');
    }

    /**
     * @dataProvider methodsDataProvider
     */
    public function testMethods(
        string $method,
        array $args,
        bool $featureEnabled
    ) {
        $this->featureChecker->expects($this->once())
            ->method('isFeatureEnabled')
            ->with('oro_price_lists_flat')
            ->willReturn($featureEnabled);

        $expectedHandler = $featureEnabled ? $this->flatHandler : $this->cplHandler;
        $expectedHandler->expects($this->once())
            ->method($method);

        $this->handler->{$method}(...$args);
    }

    /**
     * @return \Generator
     */
    public function methodsDataProvider(): ?\Generator
    {
        $website = $this->createMock(Website::class);
        $customer = $this->createMock(Customer::class);
        $customerGroup = $this->createMock(CustomerGroup::class);
        $priceList = $this->createMock(PriceList::class);

        $methods = [
            ['handleFullRebuild', []],
            ['handleWebsiteChange', [$website]],
            ['handleCustomerGroupChange', [$customerGroup, $website]],
            ['handleCustomerGroupRemove', [$customerGroup]],
            ['handleCustomerChange', [$customer, $website]],
            ['handlePriceListStatusChange', [$priceList]]
        ];

        foreach ($methods as $methodData) {
            yield array_merge($methodData, [true]);
            yield array_merge($methodData, [false]);
        }
    }
}
