<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Builder;

use Oro\Bundle\PricingBundle\Builder\CombinedPriceListsBuilderFacade;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceList;
use Oro\Bundle\PricingBundle\Event\CombinedPriceList\Assignment\GetAssociatedWebsitesEvent;
use Oro\Bundle\PricingBundle\Event\CombinedPriceList\Assignment\ProcessEvent;
use Oro\Bundle\PricingBundle\Model\CombinedPriceListTriggerHandler;
use Oro\Bundle\PricingBundle\PricingStrategy\PriceCombiningStrategyInterface;
use Oro\Bundle\PricingBundle\PricingStrategy\StrategyRegister;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Component\Testing\Unit\EntityTrait;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class CombinedPriceListsBuilderFacadeTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /**
     * @var MockObject|EventDispatcherInterface
     */
    private $dispatcher;

    /**
     * @var MockObject|StrategyRegister
     */
    private $strategyRegister;

    /**
     * @var CombinedPriceListTriggerHandler|MockObject
     */
    private $triggerHandler;

    /**
     * @var CombinedPriceListsBuilderFacade
     */
    private $facade;

    protected function setUp(): void
    {
        $this->dispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->strategyRegister = $this->createMock(StrategyRegister::class);
        $this->triggerHandler = $this->createMock(CombinedPriceListTriggerHandler::class);

        $this->facade = new CombinedPriceListsBuilderFacade(
            $this->dispatcher,
            $this->strategyRegister,
            $this->triggerHandler
        );
    }

    public function testRebuild()
    {
        $combinedPriceList1 = $this->getEntity(CombinedPriceList::class, ['id' => 11]);
        $combinedPriceList2 = $this->getEntity(CombinedPriceList::class, ['id' => 22]);
        $combinedPriceLists = [$combinedPriceList1, $combinedPriceList2];
        $products = [$this->getEntity(Product::class, ['id' => 1])];

        $strategy = $this->createMock(PriceCombiningStrategyInterface::class);
        $this->strategyRegister->expects($this->once())
            ->method('getCurrentStrategy')
            ->willReturn($strategy);

        $strategy->expects($this->exactly(2))
            ->method('combinePrices')
            ->withConsecutive(
                [$combinedPriceList1, $products],
                [$combinedPriceList2, $products]
            );

        $this->facade->rebuild($combinedPriceLists, $products);
    }

    public function testProcessAssignments()
    {
        $combinedPriceList = $this->getEntity(CombinedPriceList::class, ['id' => 11]);
        $assignTo = [
            'config' => true,
            'website' => [
                'ids' => [1, 2],
                'id:1' => [
                    'customer_group' => ['ids' => [3]],
                    'customer' => ['ids' => [5]]
                ]
            ]
        ];
        $event = new ProcessEvent($combinedPriceList, $assignTo, true);
        $this->dispatcher->expects($this->once())
            ->method('dispatch')
            ->with($event, $event::NAME);

        $this->facade->processAssignments($combinedPriceList, $assignTo, true);
    }

    public function testProcessAssignmentsWhenNoAssinmentsPassed()
    {
        $combinedPriceList = $this->getEntity(CombinedPriceList::class, ['id' => 11]);
        $assignTo = [];
        $this->dispatcher->expects($this->never())
            ->method('dispatch');

        $this->facade->processAssignments($combinedPriceList, $assignTo, true);
    }

    public function testTriggerProductIndexation()
    {
        $cpl = $this->getEntity(CombinedPriceList::class, ['id' => 11]);
        $assignTo = [
            'config' => true,
            'website' => [
                'ids' => [1, 2],
                'id:1' => [
                    'customer_group' => ['ids' => [3]],
                    'customer' => ['ids' => [5]]
                ]
            ]
        ];
        $productIds = [1, 10];
        $website = $this->getEntity(Website::class, ['id' => 100]);

        $this->dispatcher->expects($this->once())
            ->method('dispatch')
            ->willReturnCallback(function (GetAssociatedWebsitesEvent $event, string $name) use ($website) {
                $this->assertEquals($event::NAME, $name);
                $event->addWebsiteAssociation($website);

                return $event;
            });

        $this->triggerHandler->expects($this->once())
            ->method('processByProduct')
            ->with($cpl, $productIds, $website);

        $this->facade->triggerProductIndexation($cpl, $assignTo, $productIds);
    }
}
