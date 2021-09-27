<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Provider;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\PricingBundle\Entity\BasePriceListRelation;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceList;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceListToPriceList;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Entity\Repository\CombinedPriceListRepository;
use Oro\Bundle\PricingBundle\Event\CombinedPriceList\CombinedPriceListCreateEvent;
use Oro\Bundle\PricingBundle\PricingStrategy\MinimalPricesCombiningStrategy;
use Oro\Bundle\PricingBundle\PricingStrategy\PriceCombiningStrategyInterface;
use Oro\Bundle\PricingBundle\PricingStrategy\StrategyRegister;
use Oro\Bundle\PricingBundle\Provider\CombinedPriceListProvider;
use Oro\Component\Testing\Unit\EntityTrait;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class CombinedPriceListProviderTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    private EventDispatcherInterface|\PHPUnit\Framework\MockObject\MockObject $eventDispatcher;

    private ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject $registry;

    private StrategyRegister|\PHPUnit\Framework\MockObject\MockObject $strategyRegister;

    private CombinedPriceListProvider $provider;

    protected function setUp(): void
    {
        $this->registry = $this->createMock(ManagerRegistry::class);
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->strategyRegister = $this->createMock(StrategyRegister::class);

        $this->provider = new CombinedPriceListProvider(
            $this->registry,
            $this->eventDispatcher,
            $this->strategyRegister
        );
    }

    public function testGetCombinedPriceListCreateNew(): void
    {
        $priceListsRelationsData = [
            [
                'price_list_id' => 1,
                'currencies' => ['USD'],
                'mergeAllowed' => true,
            ],
            [
                'price_list_id' => 1,
                'currencies' => ['USD'],
                'mergeAllowed' => false,
            ],
            [
                'price_list_id' => 2,
                'currencies' => ['USD', 'EUR'],
                'mergeAllowed' => false,
            ],
            [
                'price_list_id' => 2,
                'currencies' => ['USD', 'EUR'],
                'mergeAllowed' => true,
            ],
        ];

        $expectedIdentifier = md5('1t_2f_2t');

        $priceListsRelations = $this->getPriceListsRelationMocks($priceListsRelationsData);

        $this->strategyRegister->expects(self::atLeastOnce())
            ->method('getCurrentStrategy')
            ->willReturn($this->createMock(PriceCombiningStrategyInterface::class));

        $repository = $this->createMock(CombinedPriceListRepository::class);
        $manager = $this->createMock(EntityManagerInterface::class);
        $manager->expects(self::any())
            ->method('getRepository')
            ->willReturn($repository);

        $persistedEntities = [];

        $manager->expects(self::exactly(4))
            ->method('persist')
            ->willReturnCallback(
                static function ($entity) use (&$persistedEntities) {
                    $persistedEntities[] = $entity;
                }
            );
        $flushedEntities = [];
        $manager->expects(self::once())
            ->method('flush')
            ->with(self::isType('array'))
            ->willReturnCallback(
                static function ($entities) use (&$flushedEntities) {
                    $flushedEntities = $entities;
                }
            );

        $this->registry->expects(self::any())
            ->method('getManagerForClass')
            ->willReturn($manager);

        $repository->expects(self::once())
            ->method('findOneBy')
            ->willReturn(null);

        $this->eventDispatcher->expects(self::once())
            ->method('dispatch')
            ->willReturnCallback(
                function (CombinedPriceListCreateEvent $event, string $eventName) {
                    $this->assertEquals(CombinedPriceListCreateEvent::NAME, $eventName);
                    $this->assertInstanceOf(CombinedPriceList::class, $event->getCombinedPriceList());

                    return $event;
                }
            );

        $combinedPriceList = $this->provider->getCombinedPriceList($priceListsRelations);
        self::assertInstanceOf(CombinedPriceList::class, $combinedPriceList);
        self::assertEquals($expectedIdentifier, $combinedPriceList->getName());
        self::assertEquals(['EUR', 'USD'], $combinedPriceList->getCurrencies());

        self::assertCount(4, $persistedEntities);
        self::assertEquals($persistedEntities, $flushedEntities);
        self::assertEquals($combinedPriceList, $persistedEntities[0]);
        self::assertInstanceOf(CombinedPriceListToPriceList::class, $persistedEntities[1]);
        self::assertInstanceOf(CombinedPriceListToPriceList::class, $persistedEntities[2]);
        self::assertInstanceOf(CombinedPriceListToPriceList::class, $persistedEntities[3]);

        $this->provider->getCombinedPriceList($priceListsRelations);
    }

    public function testGetCombinedPriceListExisting(): void
    {
        $priceListsRelationsData = [
            [
                'price_list_id' => 1,
                'currencies' => ['USD'],
                'mergeAllowed' => true,
            ]
        ];
        $cpl = new CombinedPriceList();

        $priceListsRelations = $this->getPriceListsRelationMocks($priceListsRelationsData);

        $this->strategyRegister->expects(self::atLeastOnce())
            ->method('getCurrentStrategy')
            ->willReturn($this->createMock(PriceCombiningStrategyInterface::class));

        $repository = $this->createMock(CombinedPriceListRepository::class);
        $manager = $this->createMock(EntityManagerInterface::class);
        $manager->expects(self::any())
            ->method('getRepository')
            ->willReturn($repository);

        $manager->expects(self::never())
            ->method('persist');
        $manager->expects(self::never())
            ->method('flush');

        $this->registry->expects(self::any())
            ->method('getManagerForClass')
            ->willReturn($manager);

        $repository->expects(self::once())
            ->method('findOneBy')
            ->willReturn($cpl);

        $this->eventDispatcher->expects(self::never())
            ->method('dispatch');

        $combinedPriceList = $this->provider->getCombinedPriceList($priceListsRelations);
        self::assertEquals($cpl, $combinedPriceList);
        $this->provider->getCombinedPriceList($priceListsRelations);
    }

    public function testGetCombinedPriceListWithEventOptions(): void
    {
        $options = ['test' => true];

        $repository = $this->getRepositoryMock();
        $repository->expects(self::any())
            ->method('findOneBy')
            ->willReturn(null);

        $this->eventDispatcher->expects(self::once())
            ->method('dispatch')
            ->willReturnCallback(
                function (CombinedPriceListCreateEvent $event, string $eventName) use ($options) {
                    $this->assertEquals(CombinedPriceListCreateEvent::NAME, $eventName);
                    $this->assertInstanceOf(CombinedPriceList::class, $event->getCombinedPriceList());
                    $this->assertEquals($options, $event->getOptions());

                    return $event;
                }
            );

        $priceListsRelations = $this->getPriceListsRelationMocks([
            [
                'price_list_id' => 1,
                'currencies' => ['USD'],
                'mergeAllowed' => true,
            ]
        ]);
        $this->provider->getCombinedPriceList($priceListsRelations, $options);
    }

    public function testGetCombinedPriceListNonIdentifierProviderStrategy(): void
    {
        $identifier = md5('1t_2f');
        $priceList = $this->createMock(CombinedPriceList::class);
        $priceList->expects(self::any())->method('getName')->willReturn($identifier);
        $priceList->expects(self::any())->method('getCurrencies')->willReturn(['USD']);

        $this->strategyRegister->expects(self::atLeastOnce())
            ->method('getCurrentStrategy')
            ->willReturn($this->createMock(PriceCombiningStrategyInterface::class));

        $repository = $this->getRepositoryMock();
        $repository->expects(self::any())
            ->method('findOneBy')
            ->with(['name' => $identifier])
            ->willReturn($priceList);

        $this->eventDispatcher->expects(self::never())
            ->method('dispatch');

        $priceListsRelations = $this->getPriceListsRelationMocks(
            [
                [
                    'price_list_id' => 1,
                    'currencies' => ['USD'],
                    'mergeAllowed' => true,
                ],
                [
                    'price_list_id' => 2,
                    'currencies' => ['USD'],
                    'mergeAllowed' => false
                ]
            ]
        );
        $combinedPriceList = $this->provider->getCombinedPriceList($priceListsRelations);

        self::assertInstanceOf(CombinedPriceList::class, $combinedPriceList);
        self::assertEquals($identifier, $combinedPriceList->getName());
        self::assertEquals(['USD'], $combinedPriceList->getCurrencies());
    }

    public function testGetCombinedPriceListWithIdentifierProviderStrategy(): void
    {
        $identifier = md5('1_2');
        $priceList = $this->createMock(CombinedPriceList::class);
        $priceList->expects(self::any())->method('getName')->willReturn($identifier);
        $priceList->expects(self::any())->method('getCurrencies')->willReturn(['USD']);

        $strategy = $this->createMock(MinimalPricesCombiningStrategy::class);
        $strategy->expects(self::once())
            ->method('getCombinedPriceListIdentifier')
            ->willReturn($identifier);
        $this->strategyRegister->expects(self::atLeastOnce())
            ->method('getCurrentStrategy')
            ->willReturn($strategy);

        $repository = $this->getRepositoryMock();
        $repository->expects(self::any())
            ->method('findOneBy')
            ->with(['name' => $identifier])
            ->willReturn($priceList);

        $this->eventDispatcher->expects(self::never())
            ->method('dispatch');

        $priceListsRelations = $this->getPriceListsRelationMocks(
            [
                [
                    'price_list_id' => 1,
                    'currencies' => ['USD'],
                    'mergeAllowed' => true
                ],
                [
                    'price_list_id' => 2,
                    'currencies' => ['USD'],
                    'mergeAllowed' => true
                ],
                [
                    'price_list_id' => 1,
                    'currencies' => ['USD'],
                    'mergeAllowed' => false
                ],
            ]
        );
        $combinedPriceList = $this->provider->getCombinedPriceList($priceListsRelations);

        self::assertInstanceOf(CombinedPriceList::class, $combinedPriceList);
        self::assertEquals($identifier, $combinedPriceList->getName());
        self::assertEquals(['USD'], $combinedPriceList->getCurrencies());
    }

    public function testActualizeCurrencies(): void
    {
        $pl1 = new PriceList();
        $pl1->setCurrencies(['USD', 'EUR']);

        $pl2 = new PriceList();
        $pl2->setCurrencies(['USD', 'UAH']);

        $cpl = new CombinedPriceList();

        $relation1 = new CombinedPriceListToPriceList();
        $relation1->setCombinedPriceList($cpl);
        $relation1->setPriceList($pl1);

        $relation2 = new CombinedPriceListToPriceList();
        $relation2->setCombinedPriceList($cpl);
        $relation2->setPriceList($pl2);
        $relations = [
            $relation1,
            $relation2
        ];

        $this->provider->actualizeCurrencies($cpl, $relations);

        $actualCurrencies = $cpl->getCurrencies();
        sort($actualCurrencies);

        self::assertEquals(['EUR', 'UAH', 'USD'], $actualCurrencies);
    }

    public function testActualizeCurrenciesNoCurrencies(): void
    {
        $pl1 = new PriceList();
        $cpl = new CombinedPriceList();

        $relation1 = new CombinedPriceListToPriceList();
        $relation1->setCombinedPriceList($cpl);
        $relation1->setPriceList($pl1);
        $relations = [$relation1];

        $this->provider->actualizeCurrencies($cpl, $relations);

        self::assertEquals([], $cpl->getCurrencies());
    }

    private function getRepositoryMock(): ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject
    {
        $repository = $this->createMock(CombinedPriceListRepository::class);
        $manager = $this->createMock(EntityManagerInterface::class);

        $manager->expects(self::any())
            ->method('getRepository')
            ->willReturn($repository);

        $this->registry->expects(self::any())
            ->method('getManagerForClass')
            ->willReturn($manager);

        return $repository;
    }

    private function getPriceListsRelationMocks(array $relations): array
    {
        $priceListsRelations = [];
        foreach ($relations as $priceListData) {
            $priceList = $this->createMock(PriceList::class);
            $priceList->expects(self::any())
                ->method('getId')
                ->willReturn($priceListData['price_list_id']);
            $priceList->expects(self::any())
                ->method('getCurrencies')
                ->willReturn($priceListData['currencies']);

            $priceListRelation = $this->createMock(BasePriceListRelation::class);
            $priceListRelation->expects(self::any())
                ->method('getPriceList')
                ->willReturn($priceList);
            $priceListRelation->expects(self::any())
                ->method('isMergeAllowed')
                ->willReturn($priceListData['mergeAllowed']);

            $priceListsRelations[] = $priceListRelation;
        }

        return $priceListsRelations;
    }
}
