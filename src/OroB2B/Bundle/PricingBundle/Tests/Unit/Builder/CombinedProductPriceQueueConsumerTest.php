<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Unit\Builder;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Persistence\ObjectManager;

use OroB2B\Bundle\PricingBundle\Builder\CombinedProductPriceQueueConsumer;
use OroB2B\Bundle\PricingBundle\Entity\ChangedProductPrice;
use OroB2B\Bundle\PricingBundle\Entity\CombinedPriceList;
use OroB2B\Bundle\PricingBundle\Entity\PriceList;
use OroB2B\Bundle\PricingBundle\Entity\Repository\ChangedProductPriceRepository;
use OroB2B\Bundle\PricingBundle\Entity\Repository\CombinedPriceListRepository;
use OroB2B\Bundle\PricingBundle\Resolver\CombinedProductPriceResolver;

class CombinedProductPriceQueueConsumerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var string
     */
    protected $changedProductPriceClass = 'OroB2B\Bundle\PricingBundle\Entity\ChangedProductPrice';
    /**
     * @var string
     */
    protected $combinedPriceListClass = 'OroB2B\Bundle\PricingBundle\Entity\CombinedPriceList';
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
     * @var \PHPUnit_Framework_MockObject_MockObject|ChangedProductPriceRepository
     */
    protected $changedProductPriceRepo;
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|CombinedPriceListRepository
     */
    protected $combinedPriceListRepo;

    public function setUp()
    {
        $this->registry = $this->getMock('Doctrine\Common\Persistence\ManagerRegistry');
        $this->resolver = $this->getMockBuilder('OroB2B\Bundle\PricingBundle\Resolver\CombinedProductPriceResolver')
            ->disableOriginalConstructor()
            ->getMock();

        $this->consumer = new CombinedProductPriceQueueConsumer($this->registry, $this->resolver);
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
        $priceList = $this->getMock('OroB2B\Bundle\PricingBundle\Entity\PriceList');

        /** @var \PHPUnit_Framework_MockObject_MockObject|ChangedProductPrice $changedPriceMock */
        $changedPriceMock = $this->getMockBuilder($this->changedProductPriceClass)
            ->disableOriginalConstructor()
            ->getMock();
        $changedPriceMock->expects($this->any())
            ->method('getPriceList')
            ->willReturn($priceList);

        /** @var CombinedPriceList $cplMock */
        $cplMock = $this->getMockBuilder($this->combinedPriceListClass)->getMock();

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
                    'combinedPriceLists' => [
                        $cplMock,
                        $cplMock,
                        $cplMock,
                        $cplMock,
                    ],
                ],
            ],
        ];
    }

    /**
     * @param array $asserts
     * @param array $data
     */
    protected function assertRebuild($asserts, $data)
    {
        $this->resolver->expects($this->exactly($asserts['resolverCombinePrices']))->method('combinePrices');

        $this->manager = $this->getMock('\Doctrine\Common\Persistence\ObjectManager');

        $class = 'OroB2B\Bundle\PricingBundle\Entity\Repository\ChangedProductPriceRepository';
        $this->changedProductPriceRepo = $this->getMockBuilder($class)->disableOriginalConstructor()->getMock();

        $this->changedProductPriceRepo->expects($this->once())
            ->method('getCollectionChangesIterator')
            ->willReturn($data['changesCollection']);

        $class = 'OroB2B\Bundle\PricingBundle\Entity\Repository\CombinedPriceListRepository';
        $this->combinedPriceListRepo = $this->getMockBuilder($class)->disableOriginalConstructor()->getMock();

        $this->combinedPriceListRepo->expects($this->any())
            ->method('getCombinedPriceListsByPriceListProduct')
            ->willReturn($data['combinedPriceLists']);

        $this->manager->expects($this->any())
            ->method('getRepository')
            ->will(
                $this->returnValueMap(
                    [
                        ['OroB2B\Bundle\PricingBundle\Entity\ChangedProductPrice', $this->changedProductPriceRepo],
                        ['OroB2B\Bundle\PricingBundle\Entity\CombinedPriceList', $this->combinedPriceListRepo],
                    ]
                )
            );

        $this->registry->expects($this->any())
            ->method('getManagerForClass')
            ->will(
                $this->returnValueMap(
                    [
                        [$this->changedProductPriceClass, $this->manager],
                        ['OroB2B\Bundle\PricingBundle\Entity\CombinedPriceList', $this->manager],
                    ]
                )
            );
    }
}
