<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\EventListener;

use Doctrine\Common\Persistence\ObjectManager;
use Oro\Bundle\PlatformBundle\Tests\Unit\EventListener\DemoDataFixturesListenerTestCase;
use Oro\Bundle\PricingBundle\Builder\CombinedPriceListsBuilder;
use Oro\Bundle\PricingBundle\Builder\CombinedPriceListsBuilderFacade;
use Oro\Bundle\PricingBundle\Builder\PriceListProductAssignmentBuilder;
use Oro\Bundle\PricingBundle\Builder\ProductPriceBuilder;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Entity\Repository\PriceListRepository;
use Oro\Bundle\PricingBundle\EventListener\BuildPricesDemoDataFixturesListener;

class BuildPricesDemoDataFixturesListenerTest extends DemoDataFixturesListenerTestCase
{
    /** @var CombinedPriceListsBuilderFacade|\PHPUnit_Framework_MockObject_MockObject CombinedPriceListsBuilderFacade */
    protected $combinedPriceListsBuilderFacade;

    /** @var CombinedPriceListsBuilder|\PHPUnit_Framework_MockObject_MockObject */
    protected $priceListBuilder;

    /** @var ProductPriceBuilder|\PHPUnit_Framework_MockObject_MockObject */
    protected $priceBuilder;

    /** @var PriceListProductAssignmentBuilder|\PHPUnit_Framework_MockObject_MockObject */
    protected $assignmentBuilder;

    /** @var ObjectManager|\PHPUnit_Framework_MockObject_MockObject */
    protected $objectManager;

    /** @var PriceListRepository|\PHPUnit_Framework_MockObject_MockObject */
    protected $priceListRepository;

    /**
     * {@inheritDoc}
     */
    protected function setUp()
    {
        $this->priceListBuilder = $this->createMock(CombinedPriceListsBuilder::class);
        $this->priceBuilder = $this->createMock(ProductPriceBuilder::class);
        $this->assignmentBuilder = $this->createMock(PriceListProductAssignmentBuilder::class);
        $this->objectManager = $this->createMock(ObjectManager::class);
        $this->priceListRepository = $this->createMock(PriceListRepository::class);
        $this->combinedPriceListsBuilderFacade = $this->createMock(CombinedPriceListsBuilderFacade::class);

        parent::setUp();
    }

    /**
     * {@inheritDoc}
     */
    protected function getListener()
    {
        $listener = new BuildPricesDemoDataFixturesListener(
            $this->listenerManager,
            $this->priceListBuilder,
            $this->priceBuilder,
            $this->assignmentBuilder
        );
        $listener->setCombinedPriceListsBuilderFacade($this->combinedPriceListsBuilderFacade);

        return $listener;
    }

    public function testOnPostLoad()
    {
        $this->event->expects($this->once())
            ->method('isDemoFixtures')
            ->willReturn(true);

        $this->listenerManager->expects($this->once())
            ->method('enableListeners')
            ->with(self::LISTENERS);

        $this->event->expects($this->once())
            ->method('log')
            ->with('building all combined price lists');

        $priceList = $this->getEntity(PriceList::class, ['id' => 10]);

        $this->event->expects($this->once())
            ->method('getObjectManager')
            ->willReturn($this->objectManager);

        $this->objectManager->expects($this->once())
            ->method('getRepository')
            ->with(PriceList::class)
            ->willReturn($this->priceListRepository);

        $this->priceListRepository->expects($this->once())
            ->method('getPriceListsWithRules')
            ->willReturn([$priceList]);

        $this->assignmentBuilder->expects($this->once())
            ->method('buildByPriceList')
            ->with($priceList);

        $this->priceBuilder->expects($this->once())
            ->method('buildByPriceList')
            ->with($priceList);

        $this->combinedPriceListsBuilderFacade->expects($this->once())
            ->method('rebuildAll');

        $this->listener->onPostLoad($this->event);
    }

    public function testOnPostLoadWithNoDemoFixtures()
    {
        $this->event->expects($this->once())
            ->method('isDemoFixtures')
            ->willReturn(false);

        $this->listenerManager->expects($this->never())
            ->method('enableListeners');

        $this->event->expects($this->never())
            ->method('log');

        $this->priceListRepository->expects($this->never())
            ->method('getPriceListsWithRules');

        $this->assignmentBuilder->expects($this->never())
            ->method('buildByPriceList');

        $this->priceBuilder->expects($this->never())
            ->method('buildByPriceList');

        $this->priceListBuilder->expects($this->never())
            ->method('build');

        $this->listener->onPostLoad($this->event);
    }
}
