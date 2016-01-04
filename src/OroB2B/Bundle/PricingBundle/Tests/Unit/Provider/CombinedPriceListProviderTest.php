<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Unit\Provider;

use Doctrine\Common\Persistence\ManagerRegistry;

use OroB2B\Bundle\PricingBundle\Entity\BasePriceListRelation;
use OroB2B\Bundle\PricingBundle\Provider\CombinedPriceListProvider;
use OroB2B\Bundle\PricingBundle\Resolver\CombinedProductPriceResolver;

class CombinedPriceListProviderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var CombinedPriceListProvider
     */
    protected $provider;

    /**
     * @var CombinedProductPriceResolver
     */
    protected $resolver;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|ManagerRegistry
     */
    protected $registry;

    protected function setUp()
    {
        $this->registry = $this->getRegistryMockWithRepository();
        $this->resolver = $this->getMockBuilder('OroB2B\Bundle\PricingBundle\Resolver\CombinedProductPriceResolver')
            ->disableOriginalConstructor()
            ->getMock();

        $this->provider = new CombinedPriceListProvider($this->registry);
        $this->provider->setClassName('OroB2B\Bundle\PricingBundle\Entity\CombinedPriceList');
        $this->provider->setResolver($this->resolver);
    }

    protected function tearDown()
    {
        unset($this->provider, $this->registry);
    }

    /**
     * @dataProvider getCombinedPriceListDataProvider
     * @param $data
     * @param $expected
     */
    public function testGetCombinedPriceList($data, $expected)
    {
        $priceListsRelations = [];
        foreach ($data['priceListsRelations'] as $priceListData) {
            /**
             * @var BasePriceListRelation
             */
            $priceList = $this->getMock('OroB2B\Bundle\PricingBundle\Entity\PriceList');
            $priceList->expects($this->any())
                ->method('getId')
                ->willReturn($priceListData['price_list_id']);
            $priceList->expects($this->any())
                ->method('getCurrencies')
                ->willReturn($priceListData['currencies']);

            $priceListRelation = $this->getMockBuilder('OroB2B\Bundle\PricingBundle\Entity\BasePriceListRelation')
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

        $combinedPriceList = $this->provider->getCombinedPriceList($priceListsRelations);
        $this->assertInstanceOf(
            'OroB2B\Bundle\PricingBundle\Entity\CombinedPriceList',
            $combinedPriceList
        );
        $this->assertEquals($expected['name'], $combinedPriceList->getName());
        $this->assertEquals($expected['currencies'], $combinedPriceList->getCurrencies());

    }

    /**
     * @return array
     */
    public function getCombinedPriceListDataProvider()
    {
        return [
            'duplicate price lists' => [
                'data' => [
                    'priceListsRelations' => [
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
                ],
                'expected' => [
                    'name' => '1t2f2t',
                    'currencies' => ['EUR', 'USD'],
                ]
            ]
        ];
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|\Symfony\Bridge\Doctrine\RegistryInterface
     */
    protected function getRegistryMockWithRepository()
    {

        $repository = $this->getMockBuilder('\Doctrine\Common\Persistence\ObjectRepository')
            ->disableOriginalConstructor()
            ->getMock();

        $repository->expects($this->any())
            ->method('findBy')
            ->willReturn(null);

        $manager = $this->getMockBuilder('\Doctrine\Common\Persistence\ObjectManager')
            ->disableOriginalConstructor()
            ->getMock();

        $manager->expects($this->any())
            ->method('getRepository')
            ->willReturn($repository);

        $registry = $this->getRegistryMock();

        $registry->expects($this->any())
            ->method('getManagerForClass')
            ->willReturn($manager);

        return $registry;
    }


    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|\Symfony\Bridge\Doctrine\RegistryInterface
     */
    protected function getRegistryMock()
    {
        return $this->getMock('Symfony\Bridge\Doctrine\RegistryInterface');
    }
}
