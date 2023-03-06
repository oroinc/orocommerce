<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\EventListener;

use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\BatchBundle\Tests\Unit\ORM\Query\Stub\BufferedQueryResultIteratorStub;
use Oro\Bundle\PlatformBundle\Tests\Unit\EventListener\DemoDataFixturesListenerTestCase;
use Oro\Bundle\PricingBundle\Builder\CombinedPriceListsBuilderFacade;
use Oro\Bundle\PricingBundle\Builder\PriceListProductAssignmentBuilder;
use Oro\Bundle\PricingBundle\Builder\ProductPriceBuilder;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceList;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Entity\Repository\PriceListRepository;
use Oro\Bundle\PricingBundle\EventListener\BuildPricesDemoDataFixturesListener;
use Oro\Bundle\PricingBundle\Provider\CombinedPriceListAssociationsProvider;
use Oro\Bundle\PricingBundle\Provider\CombinedPriceListProvider;
use Oro\Bundle\PricingBundle\Provider\PriceListSequenceMember;
use Oro\Component\Testing\Unit\EntityTrait;
use PHPUnit\Framework\MockObject\MockObject;

class BuildPricesDemoDataFixturesListenerTest extends DemoDataFixturesListenerTestCase
{
    use EntityTrait;

    /** @var CombinedPriceListsBuilderFacade|MockObject */
    private $combinedPriceListsBuilderFacade;

    /** @var ProductPriceBuilder|MockObject */
    private $priceBuilder;

    /** @var PriceListProductAssignmentBuilder|MockObject */
    private $assignmentBuilder;

    /** @var ObjectManager|MockObject */
    private $objectManager;

    /** @var PriceListRepository|MockObject */
    private $priceListRepository;

    /**
     * @var CombinedPriceListAssociationsProvider|MockObject
     */
    private $associationsProvider;

    /**
     * @var CombinedPriceListProvider|MockObject
     */
    private $combinedPriceListProvider;

    /**
     * {@inheritDoc}
     */
    protected function setUp(): void
    {
        $this->priceBuilder = $this->createMock(ProductPriceBuilder::class);
        $this->assignmentBuilder = $this->createMock(PriceListProductAssignmentBuilder::class);
        $this->objectManager = $this->createMock(ObjectManager::class);
        $this->priceListRepository = $this->createMock(PriceListRepository::class);
        $this->combinedPriceListsBuilderFacade = $this->createMock(CombinedPriceListsBuilderFacade::class);
        $this->associationsProvider = $this->createMock(CombinedPriceListAssociationsProvider::class);
        $this->combinedPriceListProvider = $this->createMock(CombinedPriceListProvider::class);

        parent::setUp();
    }

    /**
     * {@inheritDoc}
     */
    protected function getListener()
    {
        return new BuildPricesDemoDataFixturesListener(
            $this->listenerManager,
            $this->combinedPriceListsBuilderFacade,
            $this->priceBuilder,
            $this->assignmentBuilder,
            $this->associationsProvider,
            $this->combinedPriceListProvider
        );
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

        $iterator = new BufferedQueryResultIteratorStub();
        $iterator->append($priceList);
        $this->priceListRepository->expects($this->once())
            ->method('getPriceListsWithRules')
            ->willReturn($iterator);

        $this->assignmentBuilder->expects($this->once())
            ->method('buildByPriceListWithoutEventDispatch')
            ->with($priceList);

        $this->priceBuilder->expects($this->once())
            ->method('buildByPriceListWithoutTriggers')
            ->with($priceList);

        $associations = [
            [
                'collection' => [new PriceListSequenceMember($this->getEntity(PriceList::class, ['id' => 10]), false)],
                'assign_to' => ['config' => true]
            ]
        ];
        $this->associationsProvider->expects($this->once())
            ->method('getCombinedPriceListsWithAssociations')
            ->with(true)
            ->willReturn($associations);
        $cpl = $this->getEntity(CombinedPriceList::class, ['id' => 1]);
        $this->combinedPriceListProvider->expects($this->once())
            ->method('getCombinedPriceListByCollectionInformation')
            ->with($associations[0]['collection'])
            ->willReturn($cpl);
        $this->combinedPriceListsBuilderFacade->expects($this->once())
            ->method('rebuild')
            ->with([$cpl]);
        $this->combinedPriceListsBuilderFacade->expects($this->once())
            ->method('processAssignments')
            ->with($cpl, $associations[0]['assign_to']);
        $this->combinedPriceListsBuilderFacade->expects($this->once())
            ->method('triggerProductIndexation')
            ->with($cpl, $associations[0]['assign_to']);

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
            ->method('buildByPriceListWithoutEventDispatch');

        $this->priceBuilder->expects($this->never())
            ->method('buildByPriceListWithoutTriggers');

        $this->listener->onPostLoad($this->event);
    }
}
