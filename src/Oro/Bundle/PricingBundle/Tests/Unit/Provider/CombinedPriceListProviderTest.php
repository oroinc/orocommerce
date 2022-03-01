<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Provider;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityNotFoundException;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\PricingBundle\Entity\BasePriceListRelation;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceList;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceListToPriceList;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Entity\ProductPrice;
use Oro\Bundle\PricingBundle\Entity\Repository\CombinedPriceListRepository;
use Oro\Bundle\PricingBundle\Entity\Repository\PriceListRepository;
use Oro\Bundle\PricingBundle\Entity\Repository\ProductPriceRepository;
use Oro\Bundle\PricingBundle\Event\CombinedPriceList\CombinedPriceListCreateEvent;
use Oro\Bundle\PricingBundle\PricingStrategy\MinimalPricesCombiningStrategy;
use Oro\Bundle\PricingBundle\PricingStrategy\PriceCombiningStrategyInterface;
use Oro\Bundle\PricingBundle\PricingStrategy\StrategyRegister;
use Oro\Bundle\PricingBundle\Provider\CombinedPriceListProvider;
use Oro\Bundle\PricingBundle\Provider\PriceListSequenceMember;
use Oro\Bundle\PricingBundle\Sharding\ShardManager;
use Oro\Component\Testing\Unit\EntityTrait;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class CombinedPriceListProviderTest extends TestCase
{
    use EntityTrait;

    /**
     * @var EventDispatcherInterface|MockObject
     */
    private $eventDispatcher;

    /**
     * @var ManagerRegistry|MockObject
     */
    private $registry;

    /**
     * @var StrategyRegister|MockObject
     */
    private $strategyRegister;

    /**
     * @var CombinedPriceListProvider
     */
    private $provider;

    /**
     * @var ShardManager|MockObject
     */
    private $shardManager;

    protected function setUp(): void
    {
        $this->registry = $this->createMock(ManagerRegistry::class);
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->strategyRegister = $this->createMock(StrategyRegister::class);
        $this->shardManager = $this->createMock(ShardManager::class);

        $this->provider = new CombinedPriceListProvider(
            $this->registry,
            $this->eventDispatcher,
            $this->strategyRegister
        );
        $this->provider->setShardManager($this->shardManager);
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

        $this->strategyRegister->expects($this->atLeastOnce())
            ->method('getCurrentStrategy')
            ->willReturn($this->createMock(PriceCombiningStrategyInterface::class));

        [$combinedPriceListRepository, $productPriceRepository] = $this->getRepositoryMock();
        $productPriceRepository
            ->expects($this->any())
            ->method('hasPrices')
            ->willReturn(true);
        $combinedPriceListRepository
            ->expects($this->once())
            ->method('findOneBy')
            ->willReturn(null);

        $persistedEntities = [];
        $flushedEntities = [];

        $manager = $this->createMock(EntityManagerInterface::class);
        $manager->expects($this->exactly(4))
            ->method('persist')
            ->willReturnCallback(
                static function ($entity) use (&$persistedEntities) {
                    $persistedEntities[] = $entity;
                }
            );
        $manager->expects($this->once())
            ->method('flush')
            ->with($this->isType('array'))
            ->willReturnCallback(
                static function ($entities) use (&$flushedEntities) {
                    $flushedEntities = $entities;
                }
            );

        $this->registry->expects($this->any())
            ->method('getManagerForClass')
            ->willReturn($manager);

        $this->eventDispatcher->expects($this->once())
            ->method('dispatch')
            ->willReturnCallback(
                function (CombinedPriceListCreateEvent $event, string $eventName) {
                    $this->assertEquals(CombinedPriceListCreateEvent::NAME, $eventName);
                    $this->assertInstanceOf(CombinedPriceList::class, $event->getCombinedPriceList());
                }
            );

        $combinedPriceList = $this->provider->getCombinedPriceList($priceListsRelations);
        $this->assertInstanceOf(CombinedPriceList::class, $combinedPriceList);
        $this->assertEquals($expectedIdentifier, $combinedPriceList->getName());
        $this->assertEquals(['EUR', 'USD'], $combinedPriceList->getCurrencies());

        $this->assertCount(4, $persistedEntities);
        $this->assertEquals($persistedEntities, $flushedEntities);
        $this->assertEquals($combinedPriceList, $persistedEntities[0]);
        $this->assertInstanceOf(CombinedPriceListToPriceList::class, $persistedEntities[1]);
        $this->assertInstanceOf(CombinedPriceListToPriceList::class, $persistedEntities[2]);
        $this->assertInstanceOf(CombinedPriceListToPriceList::class, $persistedEntities[3]);

        $this->provider->getCombinedPriceList($priceListsRelations);
    }

    public function testGetCombinedPriceListExisting()
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

        $this->strategyRegister->expects($this->atLeastOnce())
            ->method('getCurrentStrategy')
            ->willReturn($this->createMock(PriceCombiningStrategyInterface::class));

        [$combinedPriceListRepository, $productPriceRepository] = $this->getRepositoryMock();
        $productPriceRepository
            ->expects($this->any())
            ->method('hasPrices')
            ->willReturn(true);
        $combinedPriceListRepository
            ->expects($this->once())
            ->method('findOneBy')
            ->willReturn($cpl);

        $manager = $this->createMock(EntityManagerInterface::class);
        $manager->expects($this->never())
            ->method('persist');
        $manager->expects($this->never())
            ->method('flush');

        $this->registry->expects($this->any())
            ->method('getManagerForClass')
            ->willReturn($manager);

        $this->eventDispatcher->expects($this->never())
            ->method('dispatch');

        $combinedPriceList = $this->provider->getCombinedPriceList($priceListsRelations);
        $this->assertEquals($cpl, $combinedPriceList);
        $this->provider->getCombinedPriceList($priceListsRelations);
    }

    public function testGetCombinedPriceListWithEventOptions()
    {
        $options = ['test' => true];

        [$combinedPriceListRepository, $productPriceRepository] = $this->getRepositoryMock();
        $productPriceRepository
            ->expects($this->any())
            ->method('hasPrices')
            ->willReturn(true);
        $combinedPriceListRepository
            ->expects($this->any())
            ->method('findOneBy')
            ->willReturn(null);

        $manager = $this->createMock(EntityManagerInterface::class);
        $this->registry->expects(self::any())
            ->method('getManagerForClass')
            ->willReturn($manager);
        $this->registry
            ->expects($this->any())
            ->method('getManagerForClass')
            ->willReturn($manager);

        $this->eventDispatcher->expects($this->once())
            ->method('dispatch')
            ->willReturnCallback(
                function (CombinedPriceListCreateEvent $event, string $eventName) use ($options) {
                    $this->assertEquals(CombinedPriceListCreateEvent::NAME, $eventName);
                    $this->assertInstanceOf(CombinedPriceList::class, $event->getCombinedPriceList());
                    $this->assertEquals($options, $event->getOptions());
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

    public function testGetCombinedPriceListNonIdentifierProviderStrategy()
    {
        $identifier = md5('1t_2f');
        $priceList = $this->createMock(CombinedPriceList::class);
        $priceList->expects($this->any())->method('getName')->willReturn($identifier);
        $priceList->expects($this->any())->method('getCurrencies')->willReturn(['USD']);

        $this->strategyRegister
            ->expects($this->atLeastOnce())
            ->method('getCurrentStrategy')
            ->willReturn($this->createMock(PriceCombiningStrategyInterface::class));

        [$combinedPriceListRepository, $productPriceRepository] = $this->getRepositoryMock();
        $productPriceRepository
            ->expects($this->any())
            ->method('hasPrices')
            ->willReturn(true);
        $combinedPriceListRepository
            ->expects($this->any())
            ->method('findOneBy')
            ->with(['name' => $identifier])
            ->willReturn($priceList);

        $manager = $this->createMock(EntityManagerInterface::class);
        $this->registry
            ->expects($this->any())
            ->method('getManagerForClass')
            ->willReturn($manager);

        $this->eventDispatcher
            ->expects($this->never())
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

        $this->assertInstanceOf(CombinedPriceList::class, $combinedPriceList);
        $this->assertEquals($identifier, $combinedPriceList->getName());
        $this->assertEquals(['USD'], $combinedPriceList->getCurrencies());
    }

    public function testGetCombinedPriceListWithIdentifierProviderStrategy()
    {
        $identifier = md5('1_2');
        $priceList = $this->createMock(CombinedPriceList::class);
        $priceList->expects($this->any())->method('getName')->willReturn($identifier);
        $priceList->expects($this->any())->method('getCurrencies')->willReturn(['USD']);

        $strategy = $this->createMock(MinimalPricesCombiningStrategy::class);
        $strategy
            ->expects($this->once())
            ->method('getCombinedPriceListIdentifier')
            ->willReturn($identifier);
        $this->strategyRegister
            ->expects($this->atLeastOnce())
            ->method('getCurrentStrategy')
            ->willReturn($strategy);

        [$combinedPriceListRepository, $productPriceRepository] = $this->getRepositoryMock();
        $productPriceRepository
            ->expects($this->any())
            ->method('hasPrices')
            ->willReturn(true);
        $combinedPriceListRepository
            ->expects($this->any())
            ->method('findOneBy')
            ->with(['name' => $identifier])
            ->willReturn($priceList);

        $manager = $this->createMock(EntityManagerInterface::class);
        $this->registry
            ->expects($this->any())
            ->method('getManagerForClass')
            ->willReturn($manager);

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

        $this->assertInstanceOf(CombinedPriceList::class, $combinedPriceList);
        $this->assertEquals($identifier, $combinedPriceList->getName());
        $this->assertEquals(['USD'], $combinedPriceList->getCurrencies());
    }

    public function testActualizeCurrencies()
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

        $this->assertEquals(['EUR', 'UAH', 'USD'], $actualCurrencies);
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

        $this->assertEquals([], $cpl->getCurrencies());
    }

    public function testGetCollectionInformation()
    {
        $relations = [
            new PriceListSequenceMember($this->getEntity(PriceList::class, ['id' => 1]), true),
            new PriceListSequenceMember($this->getEntity(PriceList::class, ['id' => 2]), false)
        ];
        $this->assertEquals(
            [
                'identifier' => '35850c5607d24a9f0a9df0a106837868',
                'elements' => [
                    ['p' => 1, 'm' => true],
                    ['p' => 2, 'm' => false]
                ]
            ],
            $this->provider->getCollectionInformation($relations)
        );
    }

    public function testGetCombinedPriceListByCollectionInformation()
    {
        $collectionInfo = [
            ['p' => 1, 'm' => true],
            ['p' => 2, 'm' => false]
        ];

        $cpl = new CombinedPriceList();
        $this->strategyRegister->expects(self::atLeastOnce())
            ->method('getCurrentStrategy')
            ->willReturn($this->createMock(PriceCombiningStrategyInterface::class));

        [$combinedPriceListRepository, $productPriceRepository, $priceListRepository] = $this->getRepositoryMock();
        $productPriceRepository
            ->expects($this->any())
            ->method('hasPrices')
            ->willReturn(true);
        $combinedPriceListRepository->expects(self::once())
            ->method('findOneBy')
            ->with(['name' => '35850c5607d24a9f0a9df0a106837868'])
            ->willReturn($cpl);
        $priceListRepository->expects($this->once())
            ->method('findBy')
            ->with(['id' => [1, 2]])
            ->willReturn([
                $this->getEntity(PriceList::class, ['id' => 1]),
                $this->getEntity(PriceList::class, ['id' => 2])
            ]);

        $manager = $this->createMock(EntityManagerInterface::class);
        $manager
            ->expects($this->any())
            ->method('getReference')
            ->willReturnCallback(function ($className, $id) {
                return $this->getEntity($className, ['id' => $id]);
            });

        $this->assertEquals($cpl, $this->provider->getCombinedPriceListByCollectionInformation($collectionInfo));
    }

    private function getRepositoryMock(): array
    {
        $combinedPriceListRepository = $this->createMock(CombinedPriceListRepository::class);
        $productPriceRepository = $this->createMock(ProductPriceRepository::class);
        $priceListRepository = $this->createMock(PriceListRepository::class);

        $this->registry
            ->expects(self::any())
            ->method('getRepository')
            ->willReturnMap([
                [CombinedPriceList::class, null, $combinedPriceListRepository],
                [ProductPrice::class, null, $productPriceRepository],
                [PriceList::class, null, $priceListRepository],

            ]);

        return [$combinedPriceListRepository, $productPriceRepository, $priceListRepository];
    }

    public function testGetCombinedPriceListByCollectionInformationOneOfPlsNotFound()
    {
        $collectionInfo = [
            ['p' => 1, 'm' => true],
            ['p' => 2, 'm' => false]
        ];

        $this->strategyRegister->expects(self::never())
            ->method('getCurrentStrategy');

        $plRepo = $this->createMock(PriceListRepository::class);
        $plRepo->expects($this->once())
            ->method('findBy')
            ->with(['id' => [1, 2]])
            ->willReturn([
                $this->getEntity(PriceList::class, ['id' => 1])
            ]);

        $this->registry->expects($this->any())
            ->method('getRepository')
            ->with(PriceList::class)
            ->willReturn($plRepo);

        $this->expectException(EntityNotFoundException::class);
        $this->expectDeprecationMessage(
            "Entity of type 'Oro\Bundle\PricingBundle\Entity\PriceList' for IDs id(2) was not found"
        );

        $this->provider->getCombinedPriceListByCollectionInformation($collectionInfo);
    }


    private function getPriceListsRelationMocks(array $relations): array
    {
        $priceListsRelations = [];
        foreach ($relations as $priceListData) {
            $priceList = $this->createMock(PriceList::class);
            $priceList->expects($this->any())
                ->method('getId')
                ->willReturn($priceListData['price_list_id']);
            $priceList->expects($this->any())
                ->method('getCurrencies')
                ->willReturn($priceListData['currencies']);
            $priceList
                ->expects($this->any())
                ->method('isActive')
                ->willReturn(true);

            $priceListRelation = $this->createMock(BasePriceListRelation::class);
            $priceListRelation->expects($this->any())
                ->method('getPriceList')
                ->willReturn($priceList);
            $priceListRelation->expects($this->any())
                ->method('isMergeAllowed')
                ->willReturn($priceListData['mergeAllowed']);

            $priceListsRelations[] = $priceListRelation;
        }

        return $priceListsRelations;
    }
}
