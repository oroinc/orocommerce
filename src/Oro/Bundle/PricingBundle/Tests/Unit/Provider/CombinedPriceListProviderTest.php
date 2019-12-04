<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Provider;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Persistence\ObjectRepository;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceList;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceListToPriceList;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Event\CombinedPriceList\CombinedPriceListCreateEvent;
use Oro\Bundle\PricingBundle\Provider\CombinedPriceListProvider;
use Oro\Component\Testing\Unit\EntityTrait;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class CombinedPriceListProviderTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /**
     * @var CombinedPriceListProvider
     */
    protected $provider;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|EventDispatcherInterface
     */
    protected $eventDispatcher;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|ManagerRegistry
     */
    protected $registry;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|ObjectRepository
     */
    protected $repository;

    protected function setUp()
    {
        $this->registry = $this->getRegistryMockWithRepository();
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);

        $this->provider = new CombinedPriceListProvider($this->registry, $this->eventDispatcher);
    }

    protected function tearDown()
    {
        unset($this->provider, $this->registry, $this->resolver);
    }

    /**
     * @dataProvider getCombinedPriceListDataProvider
     * @param array $data
     * @param array $expected
     */
    public function testGetCombinedPriceList(array $data, array $expected)
    {
        $this->repository->expects($this->any())
            ->method('findOneBy')
            ->willReturn($data['priceListFromRepository']);

        $this->eventDispatcher->expects($this->exactly($expected['combineCallsCount']))
            ->method('dispatch')
            ->willReturnCallback(
                function (string $eventName, CombinedPriceListCreateEvent $event) {
                    $this->assertEquals(CombinedPriceListCreateEvent::NAME, $eventName);
                    $this->assertInstanceOf(CombinedPriceList::class, $event->getCombinedPriceList());
                }
            );

        $priceListsRelations = $this->getPriceListsRelationMocks($data['priceListsRelationsData']);
        $combinedPriceList = $this->provider->getCombinedPriceList($priceListsRelations);
        $this->assertInstanceOf(CombinedPriceList::class, $combinedPriceList);
        $this->assertEquals($expected['name'], $combinedPriceList->getName());
        $this->assertEquals($expected['currencies'], $combinedPriceList->getCurrencies());

        $this->provider->getCombinedPriceList($priceListsRelations);
    }

    /**
     * @return array
     */
    public function getCombinedPriceListDataProvider()
    {
        $priceList = $this->createMock('Oro\Bundle\PricingBundle\Entity\CombinedPriceList');
        $priceList->expects($this->any())->method('getName')->willReturn('');
        $priceList->expects($this->any())->method('getCurrencies')->willReturn([]);

        return [
            'duplicate price lists force call' => [
                'data' => [
                    'priceListsRelationsData' => [
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
                    ],
                    'priceListFromRepository' => null,
                ],
                'expected' => [
                    'name' => md5('1t_2f_2t'),
                    'currencies' => ['EUR', 'USD'],
                    'combineCallsCount' => 2,
                ]
            ],
            'empty price lists normal call' => [
                'data' => [
                    'priceListsRelationsData' => [],
                    'priceListFromRepository' => $priceList,
                ],
                'expected' => [
                    'name' => '',
                    'currencies' => [],
                    'combineCallsCount' => 0,
                ]
            ],
        ];
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
     * @return \PHPUnit\Framework\MockObject\MockObject|\Doctrine\Common\Persistence\ManagerRegistry
     */
    protected function getRegistryMockWithRepository()
    {
        $this->repository = $this->getMockBuilder('\Doctrine\Common\Persistence\ObjectRepository')
            ->disableOriginalConstructor()
            ->getMock();

        $manager = $this->getMockBuilder('\Doctrine\Common\Persistence\ObjectManager')
            ->disableOriginalConstructor()
            ->getMock();

        $manager->expects($this->any())
            ->method('getRepository')
            ->willReturn($this->repository);

        $registry = $this->getRegistryMock();

        $registry->expects($this->any())
            ->method('getManagerForClass')
            ->willReturn($manager);

        return $registry;
    }

    /**
     * @param array $relations
     * @return array
     */
    protected function getPriceListsRelationMocks(array $relations)
    {
        $priceListsRelations = [];
        foreach ($relations as $priceListData) {
            $priceList = $this->createMock('Oro\Bundle\PricingBundle\Entity\PriceList');
            $priceList->expects($this->any())
                ->method('getId')
                ->willReturn($priceListData['price_list_id']);
            $priceList->expects($this->any())
                ->method('getCurrencies')
                ->willReturn($priceListData['currencies']);

            $priceListRelation = $this->getMockBuilder('Oro\Bundle\PricingBundle\Entity\BasePriceListRelation')
                ->disableOriginalConstructor()
                ->getMock();
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


    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|\Doctrine\Common\Persistence\ManagerRegistry
     */
    protected function getRegistryMock()
    {
        return $this->createMock('Doctrine\Common\Persistence\ManagerRegistry');
    }
}
