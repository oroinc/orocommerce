<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Unit\Resolver;

use OroB2B\Bundle\PricingBundle\Entity\CombinedPriceListToPriceList;
use OroB2B\Bundle\PricingBundle\Entity\PriceList;
use OroB2B\Bundle\PricingBundle\Entity\PriceListSchedule;
use OroB2B\Bundle\PricingBundle\Resolver\PriceListScheduleResolver;

class PriceListScheduleResolverTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var PriceListScheduleResolver
     */
    protected $resolver;

    public function setUp()
    {
        $this->resolver = new PriceListScheduleResolver();
    }

    /**
     * @dataProvider dataProviderMergeSchedule
     * @param array $priceListSchedules
     * @param array $priceListRelations
     * @param array $expectedResult
     */
    public function testMergeSchedule(array $priceListSchedules, array $priceListRelations, array $expectedResult)
    {
        $result = $this->resolver->mergeSchedule($priceListSchedules, $priceListRelations);
        $this->assertEquals($expectedResult, $result);
    }

    /**
     * @return array
     */
    public function dataProviderMergeSchedule()
    {
        $data = [
            [
                'priceListSchedules' => [],
                'priceListRelations' => [],
                'expectedResult' => []
            ],
            [
                'priceListSchedules' => [],
                'priceListRelations' => [1],
                'expectedResult' => []
            ],
            [
                'priceListSchedules' => [
                    ['id' => 2, 'on' => 20160102, 'off' => 20160103],
                ],
                'priceListRelations' => [1, 2],
                'expectedResult' => [
                    0        => ['priceLists' => [1], 'activateAt' => null, 'expireAt' => 20160102],
                    20160102 => ['priceLists' => [1, 2], 'activateAt' => 20160102, 'expireAt' => 20160103],
                    20160103 => ['priceLists' => [1], 'activateAt' => 20160103, 'expireAt' => null],
                ]
            ],
            [
                'priceListSchedules' => [
                    ['id' => 2, 'on' => 20160102, 'off' => 20160103],
                    ['id' => 3, 'on' => 20160103, 'off' => 20160106],
                    ['id' => 2, 'on' => 20160105, 'off' => 20160108],
                ],
                'priceListRelations' => [1, 2, 3],
                'expectedResult' => [
                    0        => ['priceLists' => [1], 'activateAt' => null, 'expireAt' => 20160102],
                    20160102 => ['priceLists' => [1, 2], 'activateAt' => 20160102, 'expireAt' => 20160103],
                    20160103 => ['priceLists' => [1, 3], 'activateAt' => 20160103, 'expireAt' => 20160105],
                    20160105 => ['priceLists' => [1, 2, 3], 'activateAt' => 20160105, 'expireAt' => 20160106],
                    20160106 => ['priceLists' => [1, 2], 'activateAt' => 20160106, 'expireAt' => 20160108],
                    20160108 => ['priceLists' => [1], 'activateAt' => 20160108, 'expireAt' => null],
                ]
            ],
            [
                'priceListSchedules' => [
                    ['id' => 1, 'on' => 20160102, 'off' => 20160103],
                    ['id' => 1, 'on' => 20160105, 'off' => null],
                    ['id' => 2, 'on' => 20160102, 'off' => 20160103],
                    ['id' => 1, 'on' => 20160105, 'off' => null],
                ],
                'priceListRelations' => [1, 2],
                'expectedResult' => [
                    0        => ['priceLists' => [], 'activateAt' => null, 'expireAt' => 20160102],
                    20160102 => ['priceLists' => [1, 2], 'activateAt' => 20160102, 'expireAt' => 20160103],
                    20160103 => ['priceLists' => [], 'activateAt' => 20160103, 'expireAt' => 20160105],
                    20160105 => ['priceLists' => [1], 'activateAt' => 20160105, 'expireAt' => null],
                ]
            ],
            [
                'priceListSchedules' => [
                    ['id' => 2, 'on' => 20160101, 'off' => null],
                    ['id' => 3, 'on' => null, 'off' => 20160103],
                ],
                'priceListRelations' => [1, 2, 3, 4],
                'expectedResult' => [
                    0        => ['priceLists' => [1, 3, 4], 'activateAt' => null, 'expireAt' => 20160101],
                    20160101 => ['priceLists' => [1, 2, 3, 4], 'activateAt' => 20160101, 'expireAt' => 20160103],
                    20160103 => ['priceLists' => [1, 2, 4], 'activateAt' => 20160103, 'expireAt' => null],
                ]
            ],
        ];

        foreach ($data as $testCase => $testData) {
            $priceListSchedules = [];
            $priceListRelations = [];
            foreach ($testData['priceListSchedules'] as $priceListSchedule) {
                $priceListSchedules[] = $this->createScheduleItem($priceListSchedule);
            }
            foreach ($testData['priceListRelations'] as $priceListId) {
                $priceListRelations[] = $this->createCombinedPriceListToPriceList($priceListId);
            }
            $data[$testCase]['priceListSchedules'] = $priceListSchedules;
            $data[$testCase]['priceListRelations'] = $priceListRelations;
        }

        return $data;
    }

    /**
     * @param array $priceListSchedule
     * @return PriceListSchedule
     */
    protected function createScheduleItem(array $priceListSchedule)
    {
        $obj = new PriceListSchedule();
        if ($priceListSchedule['on']) {
            $obj->setActiveAt(new \DateTime());
            $obj->getActiveAt()->setTimestamp($priceListSchedule['on']);
        }
        if ($priceListSchedule['off']) {
            $obj->setDeactivateAt(new \DateTime());
            $obj->getDeactivateAt()->setTimestamp($priceListSchedule['off']);
        }
        /** @var PriceList|\PHPUnit_Framework_MockObject_MockObject $priceList */
        $priceList = $this->getMock('OroB2B\Bundle\PricingBundle\Entity\PriceList');
        $priceList->method('getId')->willReturn($priceListSchedule['id']);
        $obj->setPriceList($priceList);

        return $obj;
    }

    /**
     * @param int $priceListId
     * @return CombinedPriceListToPriceList
     */
    protected function createCombinedPriceListToPriceList($priceListId)
    {
        $obj = new CombinedPriceListToPriceList();
        /** @var PriceList|\PHPUnit_Framework_MockObject_MockObject $priceList */
        $priceList = $this->getMock('OroB2B\Bundle\PricingBundle\Entity\PriceList');
        $priceList->method('getId')->willReturn($priceListId);
        $obj->setPriceList($priceList);
        return $obj;
    }
}
