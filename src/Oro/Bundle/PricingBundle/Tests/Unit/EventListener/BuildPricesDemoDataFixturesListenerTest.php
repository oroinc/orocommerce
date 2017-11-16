<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\EventListener;

use Doctrine\Common\Persistence\ObjectManager;
use Oro\Bundle\PricingBundle\EventListener\BuildPricesDemoDataFixturesListener;
use Oro\Bundle\MigrationBundle\Event\MigrationDataFixturesEvent;
use Oro\Bundle\PlatformBundle\Manager\OptionalListenerManager;
use Oro\Bundle\PricingBundle\Builder\CombinedPriceListsBuilder;
use Oro\Bundle\PricingBundle\Builder\PriceListProductAssignmentBuilder;
use Oro\Bundle\PricingBundle\Builder\ProductPriceBuilder;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Entity\Repository\PriceListRepository;
use Oro\Component\Testing\Unit\EntityTrait;
use Symfony\Bridge\Doctrine\RegistryInterface;

class BuildPricesDemoDataFixturesListenerTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    /** @var OptionalListenerManager|\PHPUnit_Framework_MockObject_MockObject */
    protected $listenerManager;

    /** @var RegistryInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $doctrine;

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

    /** @var MigrationDataFixturesEvent|\PHPUnit_Framework_MockObject_MockObject */
    protected $event;

    /** @var BuildPricesDemoDataFixturesListener */
    protected $listener;

    /**
     * {@inheritDoc}
     */
    protected function setUp()
    {
        $this->listenerManager = $this->createMock(OptionalListenerManager::class);
        $this->doctrine = $this->createMock(RegistryInterface::class);
        $this->priceListBuilder = $this->createMock(CombinedPriceListsBuilder::class);
        $this->priceBuilder = $this->createMock(ProductPriceBuilder::class);
        $this->assignmentBuilder = $this->createMock(PriceListProductAssignmentBuilder::class);

        $this->objectManager = $this->createMock(ObjectManager::class);
        $this->priceListRepository = $this->createMock(PriceListRepository::class);
        $this->event = $this->createMock(MigrationDataFixturesEvent::class);

        $this->listener = new BuildPricesDemoDataFixturesListener(
            $this->listenerManager,
            $this->doctrine,
            $this->priceListBuilder,
            $this->priceBuilder,
            $this->assignmentBuilder
        );
    }

    public function testOnPreLoad()
    {
        $this->event->expects($this->once())
            ->method('isDemoFixtures')
            ->willReturn(true);

        $this->listenerManager->expects($this->once())
            ->method('disableListeners')
            ->with(BuildPricesDemoDataFixturesListener::LISTENERS);

        $this->listener->onPreLoad($this->event);
    }

    public function testOnPreLoadWithNoDemoFixtures()
    {
        $this->event->expects($this->once())
            ->method('isDemoFixtures')
            ->willReturn(false);

        $this->listenerManager->expects($this->never())
            ->method('disableListeners');

        $this->listener->onPreLoad($this->event);
    }

    public function testOnPostLoad()
    {
        $this->event->expects($this->once())
            ->method('isDemoFixtures')
            ->willReturn(true);

        $this->listenerManager->expects($this->once())
            ->method('enableListeners')
            ->with(BuildPricesDemoDataFixturesListener::LISTENERS);

        $this->event->expects($this->once())
            ->method('log')
            ->with('processing of all Price rules and combining all Price Lists');

        $priceList = $this->getEntity(PriceList::class, ['id' => 10]);

        $this->doctrine->expects($this->once())
            ->method('getManagerForClass')
            ->with(PriceList::class)
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

        $this->priceListBuilder->expects($this->once())
            ->method('build');

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
