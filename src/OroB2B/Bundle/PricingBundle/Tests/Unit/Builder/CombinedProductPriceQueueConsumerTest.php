<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Unit\Builder;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Persistence\ObjectManager;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;

use OroB2B\Bundle\PricingBundle\Builder\CombinedProductPriceQueueConsumer;
use OroB2B\Bundle\PricingBundle\Entity\ProductPriceChangeTrigger;
use OroB2B\Bundle\PricingBundle\Entity\CombinedPriceList;
use OroB2B\Bundle\PricingBundle\Entity\PriceList;
use OroB2B\Bundle\PricingBundle\Entity\Repository\ProductPriceChangeTriggerRepository;
use OroB2B\Bundle\PricingBundle\Entity\Repository\CombinedPriceListRepository;
use OroB2B\Bundle\PricingBundle\Event\CombinedPriceList\CombinedPriceListsUpdateEvent;
use OroB2B\Bundle\PricingBundle\Resolver\CombinedProductPriceResolver;

class CombinedProductPriceQueueConsumerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|ObjectManager
     */
    protected $manager;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|ManagerRegistry
     */
    protected $registry;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|CombinedProductPriceResolver
     */
    protected $resolver;

    /**
     * @var CombinedProductPriceQueueConsumer
     */
    protected $consumer;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|ProductPriceChangeTriggerRepository
     */
    protected $productPriceTriggerRepo;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|CombinedPriceListRepository
     */
    protected $combinedPriceListRepo;

    /**
     * @var EventDispatcherInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $eventDispatcher;

    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        $this->registry = $this->getMock(ManagerRegistry::class);
        $this->resolver = $this->getMockBuilder(CombinedProductPriceResolver::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->eventDispatcher = $this->getMock(EventDispatcherInterface::class);

        $this->consumer = new CombinedProductPriceQueueConsumer(
            $this->registry,
            $this->resolver,
            $this->eventDispatcher
        );
    }

    /**
     * @dataProvider processDataProvider
     * @param array $asserts
     * @param array $data
     */
    public function testProcess($asserts, $data)
    {
        $this->assertRebuild($asserts, $data);
        $this->consumer->process();
    }

    /**
     * @return array
     */
    public function processDataProvider()
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|PriceList $priceList */
        $priceList = $this->getMock(PriceList::class);

        /** @var ProductPriceChangeTrigger|\PHPUnit_Framework_MockObject_MockObject $changedPriceMock */
        $changedPriceMock = $this->getMockBuilder(ProductPriceChangeTrigger::class)
            ->disableOriginalConstructor()
            ->getMock();
        $changedPriceMock->expects($this->any())
            ->method('getPriceList')
            ->willReturn($priceList);

        return [
            [
                'asserts' => [
                    'resolverCombinePrices' => 0,
                ],
                'data' => [
                    'changesCollection' => [],
                    'combinedPriceLists' => [],
                ],
            ],
            [
                'asserts' => [
                    'resolverCombinePrices' => 8,
                ],
                'data' => [
                    'changesCollection' => [
                        $changedPriceMock,
                        $changedPriceMock,
                    ],
                    'combinedPriceLists' => $this->getCplMocks(4),
                ],
            ],
        ];
    }

    /**
     * @param integer $count
     * @return CombinedPriceList[]|\PHPUnit_Framework_MockObject_MockObject[] array
     */
    protected function getCplMocks($count)
    {
        $result = [];
        for ($i = 1; $i <= $count; $i++) {
            /** @var CombinedPriceList|\PHPUnit_Framework_MockObject_MockObject $cplMock */
            $cplMock = $this->getMockBuilder(CombinedPriceList::class)->getMock();
            $cplMock->expects($this->any())->method('getId')->willReturn($i);
            $result[$i] = $cplMock;
        }

        return $result;
    }

    /**
     * @param array $asserts
     * @param array $data
     */
    protected function assertRebuild($asserts, $data)
    {
        $this->resolver->expects($this->exactly($asserts['resolverCombinePrices']))->method('combinePrices');

        $this->manager = $this->getMock('\Doctrine\Common\Persistence\ObjectManager');
        if ($data['combinedPriceLists']) {
            $this->eventDispatcher->expects($this->exactly(count($data['changesCollection'])))
                ->method('dispatch')
                ->with(
                    CombinedPriceListsUpdateEvent::NAME,
                    new CombinedPriceListsUpdateEvent(array_keys($data['combinedPriceLists']))
                );
        } else {
            $this->eventDispatcher->expects($this->never())
                ->method('dispatch');
        }
        $class = 'OroB2B\Bundle\PricingBundle\Entity\Repository\ProductPriceChangeTriggerRepository';
        $this->productPriceTriggerRepo = $this->getMockBuilder($class)->disableOriginalConstructor()->getMock();

        $this->productPriceTriggerRepo->expects($this->once())
            ->method('getProductPriceChangeTriggersIterator')
            ->willReturn($data['changesCollection']);

        $class = 'OroB2B\Bundle\PricingBundle\Entity\Repository\CombinedPriceListRepository';
        $this->combinedPriceListRepo = $this->getMockBuilder($class)->disableOriginalConstructor()->getMock();

        $this->combinedPriceListRepo->expects($this->any())
            ->method('getCombinedPriceListsByPriceList')
            ->willReturn($data['combinedPriceLists']);

        $this->manager->expects($this->any())
            ->method('getRepository')
            ->will(
                $this->returnValueMap(
                    [
                        [
                            'OroB2B\Bundle\PricingBundle\Entity\ProductPriceChangeTrigger',
                            $this->productPriceTriggerRepo,
                        ],
                        [
                            'OroB2B\Bundle\PricingBundle\Entity\CombinedPriceList',
                            $this->combinedPriceListRepo,
                        ],
                    ]
                )
            );

        $this->registry->expects($this->any())
            ->method('getManagerForClass')
            ->will(
                $this->returnValueMap(
                    [
                        [ProductPriceChangeTrigger::class, $this->manager],
                        ['OroB2B\Bundle\PricingBundle\Entity\CombinedPriceList', $this->manager],
                    ]
                )
            );
    }
}
