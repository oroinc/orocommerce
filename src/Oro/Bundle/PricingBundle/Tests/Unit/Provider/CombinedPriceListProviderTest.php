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
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class CombinedPriceListProviderTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /**
     * @var CombinedPriceListProvider
     */
    protected $provider;

    /**
     * @var MockObject|EventDispatcherInterface
     */
    protected $eventDispatcher;

    /**
     * @var MockObject|ManagerRegistry
     */
    protected $registry;

    /**
     * @var MockObject|StrategyRegister
     */
    protected $strategyRegister;

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

    protected function tearDown(): void
    {
        unset($this->provider, $this->registry, $this->resolver);
    }

    public function testGetCombinedPriceListCreateNew()
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

        $repository = $this->createMock(CombinedPriceListRepository::class);
        $manager = $this->createMock(EntityManagerInterface::class);
        $manager->expects($this->any())
            ->method('getRepository')
            ->willReturn($repository);

        $persistedEntities = [];

        $manager->expects($this->exactly(4))
            ->method('persist')
            ->willReturnCallback(
                static function ($entity) use (&$persistedEntities) {
                    $persistedEntities[] = $entity;
                }
            );
        $flushedEntities = [];
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

        $repository->expects($this->once())
            ->method('findOneBy')
            ->willReturn(null);

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

        $repository = $this->createMock(CombinedPriceListRepository::class);
        $manager = $this->createMock(EntityManagerInterface::class);
        $manager->expects($this->any())
            ->method('getRepository')
            ->willReturn($repository);

        $manager->expects($this->never())
            ->method('persist');
        $manager->expects($this->never())
            ->method('flush');

        $this->registry->expects($this->any())
            ->method('getManagerForClass')
            ->willReturn($manager);

        $repository->expects($this->once())
            ->method('findOneBy')
            ->willReturn($cpl);

        $this->eventDispatcher->expects($this->never())
            ->method('dispatch');

        $combinedPriceList = $this->provider->getCombinedPriceList($priceListsRelations);
        $this->assertEquals($cpl, $combinedPriceList);
        $this->provider->getCombinedPriceList($priceListsRelations);
    }

    public function testGetCombinedPriceListWithEventOptions()
    {
        $options = ['test' => true];

        $repository = $this->getRepositoryMock();
        $repository->expects($this->any())
            ->method('findOneBy')
            ->willReturn(null);

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

        $this->strategyRegister->expects($this->atLeastOnce())
            ->method('getCurrentStrategy')
            ->willReturn($this->createMock(PriceCombiningStrategyInterface::class));

        $repository = $this->getRepositoryMock();
        $repository->expects($this->any())
            ->method('findOneBy')
            ->with(['name' => $identifier])
            ->willReturn($priceList);

        $this->eventDispatcher->expects($this->never())
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
        $strategy->expects($this->once())
            ->method('getCombinedPriceListIdentifier')
            ->willReturn($identifier);
        $this->strategyRegister->expects($this->atLeastOnce())
            ->method('getCurrentStrategy')
            ->willReturn($strategy);

        $repository = $this->getRepositoryMock();
        $repository->expects($this->any())
            ->method('findOneBy')
            ->with(['name' => $identifier])
            ->willReturn($priceList);

        $this->eventDispatcher->expects($this->never())
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

    public function testActualizeCurrenciesNoCurrencies()
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

    /**
     * @return MockObject|ManagerRegistry
     */
    protected function getRepositoryMock()
    {
        $repository = $this->createMock(CombinedPriceListRepository::class);
        $manager = $this->createMock(EntityManagerInterface::class);

        $manager->expects($this->any())
            ->method('getRepository')
            ->willReturn($repository);

        $this->registry->expects($this->any())
            ->method('getManagerForClass')
            ->willReturn($manager);

        return $repository;
    }

    /**
     * @param array $relations
     * @return array
     */
    protected function getPriceListsRelationMocks(array $relations)
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
