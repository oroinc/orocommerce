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
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
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
                    ['id' => 2, PriceListScheduleResolver::ON => 20160102, PriceListScheduleResolver::OFF => 20160103],
                ],
                'priceListRelations' => [1, 2],
                'expectedResult' => [
                    0        => [
                        PriceListScheduleResolver::PRICE_LISTS_KEY => [1],
                        PriceListScheduleResolver::ACTIVATE_AT_KEY => null,
                        PriceListScheduleResolver::EXPIRE_AT_KEY => $this->getDateTimeWithTimestamp(20160102)
                    ],
                    20160102 => [
                        PriceListScheduleResolver::PRICE_LISTS_KEY => [1, 2],
                        PriceListScheduleResolver::ACTIVATE_AT_KEY => $this->getDateTimeWithTimestamp(20160102),
                        PriceListScheduleResolver::EXPIRE_AT_KEY => $this->getDateTimeWithTimestamp(20160103),
                    ],
                    20160103 => [
                        PriceListScheduleResolver::PRICE_LISTS_KEY => [1],
                        PriceListScheduleResolver::ACTIVATE_AT_KEY => $this->getDateTimeWithTimestamp(20160103),
                        PriceListScheduleResolver::EXPIRE_AT_KEY => null
                    ],
                ]
            ],
            [
                'priceListSchedules' => [
                    ['id' => 2, PriceListScheduleResolver::ON => null, PriceListScheduleResolver::OFF => 20160103],
                ],
                'priceListRelations' => [2],
                'expectedResult' => [
                    0        => [
                        PriceListScheduleResolver::PRICE_LISTS_KEY => [2],
                        PriceListScheduleResolver::ACTIVATE_AT_KEY => null,
                        PriceListScheduleResolver::EXPIRE_AT_KEY => $this->getDateTimeWithTimestamp(20160103)
                    ],
                    20160103 => [
                        PriceListScheduleResolver::PRICE_LISTS_KEY => [],
                        PriceListScheduleResolver::ACTIVATE_AT_KEY => $this->getDateTimeWithTimestamp(20160103),
                        PriceListScheduleResolver::EXPIRE_AT_KEY => null
                    ],
                ]
            ],
            [
                'priceListSchedules' => [
                    ['id' => 2, PriceListScheduleResolver::ON => null, PriceListScheduleResolver::OFF => 20160103],
                    ['id' => 2, PriceListScheduleResolver::ON => 20160104, PriceListScheduleResolver::OFF => 20160105],
                ],
                'priceListRelations' => [2],
                'expectedResult' => [
                    0        => [
                        PriceListScheduleResolver::PRICE_LISTS_KEY => [2],
                        PriceListScheduleResolver::ACTIVATE_AT_KEY => null,
                        PriceListScheduleResolver::EXPIRE_AT_KEY => $this->getDateTimeWithTimestamp(20160103)
                    ],
                    20160103 => [
                        PriceListScheduleResolver::PRICE_LISTS_KEY => [],
                        PriceListScheduleResolver::ACTIVATE_AT_KEY => $this->getDateTimeWithTimestamp(20160103),
                        PriceListScheduleResolver::EXPIRE_AT_KEY => $this->getDateTimeWithTimestamp(20160104)
                    ],
                    20160104 => [
                        PriceListScheduleResolver::PRICE_LISTS_KEY => [2],
                        PriceListScheduleResolver::ACTIVATE_AT_KEY => $this->getDateTimeWithTimestamp(20160104),
                        PriceListScheduleResolver::EXPIRE_AT_KEY => $this->getDateTimeWithTimestamp(20160105)
                    ],
                    20160105 => [
                        PriceListScheduleResolver::PRICE_LISTS_KEY => [],
                        PriceListScheduleResolver::ACTIVATE_AT_KEY => $this->getDateTimeWithTimestamp(20160105),
                        PriceListScheduleResolver::EXPIRE_AT_KEY => null
                    ],
                ]
            ],
            [
                'priceListSchedules' => [
                    ['id' => 2, PriceListScheduleResolver::ON => 20160102, PriceListScheduleResolver::OFF => 20160103],
                    ['id' => 3, PriceListScheduleResolver::ON => 20160103, PriceListScheduleResolver::OFF => 20160106],
                    ['id' => 2, PriceListScheduleResolver::ON => 20160105, PriceListScheduleResolver::OFF => 20160108],
                ],
                'priceListRelations' => [1, 2, 3],
                'expectedResult' => [
                    0        => [
                        PriceListScheduleResolver::PRICE_LISTS_KEY => [1],
                        PriceListScheduleResolver::ACTIVATE_AT_KEY => null,
                        PriceListScheduleResolver::EXPIRE_AT_KEY => $this->getDateTimeWithTimestamp(20160102)
                    ],
                    20160102 => [
                        PriceListScheduleResolver::PRICE_LISTS_KEY => [1, 2],
                        PriceListScheduleResolver::ACTIVATE_AT_KEY => $this->getDateTimeWithTimestamp(20160102),
                        PriceListScheduleResolver::EXPIRE_AT_KEY => $this->getDateTimeWithTimestamp(20160103)
                    ],
                    20160103 => [
                        PriceListScheduleResolver::PRICE_LISTS_KEY => [1, 3],
                        PriceListScheduleResolver::ACTIVATE_AT_KEY => $this->getDateTimeWithTimestamp(20160103),
                        PriceListScheduleResolver::EXPIRE_AT_KEY => $this->getDateTimeWithTimestamp(20160105)
                    ],
                    20160105 => [
                        PriceListScheduleResolver::PRICE_LISTS_KEY => [1, 2, 3],
                        PriceListScheduleResolver::ACTIVATE_AT_KEY => $this->getDateTimeWithTimestamp(20160105),
                        PriceListScheduleResolver::EXPIRE_AT_KEY => $this->getDateTimeWithTimestamp(20160106)
                    ],
                    20160106 => [
                        PriceListScheduleResolver::PRICE_LISTS_KEY => [1, 2],
                        PriceListScheduleResolver::ACTIVATE_AT_KEY => $this->getDateTimeWithTimestamp(20160106),
                        PriceListScheduleResolver::EXPIRE_AT_KEY => $this->getDateTimeWithTimestamp(20160108)
                    ],
                    20160108 => [
                        PriceListScheduleResolver::PRICE_LISTS_KEY => [1],
                        PriceListScheduleResolver::ACTIVATE_AT_KEY => $this->getDateTimeWithTimestamp(20160108),
                        PriceListScheduleResolver::EXPIRE_AT_KEY => null
                    ],
                ]
            ],
            [
                'priceListSchedules' => [
                    ['id' => 1, PriceListScheduleResolver::ON => 20160102, PriceListScheduleResolver::OFF => 20160103],
                    ['id' => 1, PriceListScheduleResolver::ON => 20160105, PriceListScheduleResolver::OFF => null],
                    ['id' => 2, PriceListScheduleResolver::ON => 20160102, PriceListScheduleResolver::OFF => 20160103],
                    ['id' => 1, PriceListScheduleResolver::ON => 20160105, PriceListScheduleResolver::OFF => null],
                ],
                'priceListRelations' => [1, 2],
                'expectedResult' => [
                    0        => [
                        PriceListScheduleResolver::PRICE_LISTS_KEY => [],
                        PriceListScheduleResolver::ACTIVATE_AT_KEY => null,
                        PriceListScheduleResolver::EXPIRE_AT_KEY => $this->getDateTimeWithTimestamp(20160102)
                    ],
                    20160102 => [
                        PriceListScheduleResolver::PRICE_LISTS_KEY => [1, 2],
                        PriceListScheduleResolver::ACTIVATE_AT_KEY => $this->getDateTimeWithTimestamp(20160102),
                        PriceListScheduleResolver::EXPIRE_AT_KEY => $this->getDateTimeWithTimestamp(20160103)
                    ],
                    20160103 => [
                        PriceListScheduleResolver::PRICE_LISTS_KEY => [],
                        PriceListScheduleResolver::ACTIVATE_AT_KEY => $this->getDateTimeWithTimestamp(20160103),
                        PriceListScheduleResolver::EXPIRE_AT_KEY => $this->getDateTimeWithTimestamp(20160105)
                    ],
                    20160105 => [
                        PriceListScheduleResolver::PRICE_LISTS_KEY => [1],
                        PriceListScheduleResolver::ACTIVATE_AT_KEY => $this->getDateTimeWithTimestamp(20160105),
                        PriceListScheduleResolver::EXPIRE_AT_KEY => null
                    ],
                ]
            ],
            [
                'priceListSchedules' => [
                    ['id' => 2, PriceListScheduleResolver::ON => 20160101, PriceListScheduleResolver::OFF => null],
                    ['id' => 3, PriceListScheduleResolver::ON => null, PriceListScheduleResolver::OFF => 20160103],
                ],
                'priceListRelations' => [1, 2, 3, 4],
                'expectedResult' => [
                    0        => [
                        PriceListScheduleResolver::PRICE_LISTS_KEY => [1, 3, 4],
                        PriceListScheduleResolver::ACTIVATE_AT_KEY => null,
                        PriceListScheduleResolver::EXPIRE_AT_KEY => $this->getDateTimeWithTimestamp(20160101)
                    ],
                    20160101 => [
                        PriceListScheduleResolver::PRICE_LISTS_KEY => [1, 2, 3, 4],
                        PriceListScheduleResolver::ACTIVATE_AT_KEY => $this->getDateTimeWithTimestamp(20160101),
                        PriceListScheduleResolver::EXPIRE_AT_KEY => $this->getDateTimeWithTimestamp(20160103)
                    ],
                    20160103 => [
                        PriceListScheduleResolver::PRICE_LISTS_KEY => [1, 2, 4],
                        PriceListScheduleResolver::ACTIVATE_AT_KEY => $this->getDateTimeWithTimestamp(20160103),
                        PriceListScheduleResolver::EXPIRE_AT_KEY => null
                    ],
                ]
            ],
            [
                'priceListSchedules' => [
                    ['id' => 2, PriceListScheduleResolver::ON => null, PriceListScheduleResolver::OFF => 20160102],
                    ['id' => 3, PriceListScheduleResolver::ON => 20160101, PriceListScheduleResolver::OFF => 20160103],
                ],
                'priceListRelations' => [2, 3],
                'expectedResult' => [
                    0        => [
                        PriceListScheduleResolver::PRICE_LISTS_KEY => [2],
                        PriceListScheduleResolver::ACTIVATE_AT_KEY => null,
                        PriceListScheduleResolver::EXPIRE_AT_KEY => $this->getDateTimeWithTimestamp(20160101)
                    ],
                    20160101 => [
                        PriceListScheduleResolver::PRICE_LISTS_KEY => [2, 3],
                        PriceListScheduleResolver::ACTIVATE_AT_KEY => $this->getDateTimeWithTimestamp(20160101),
                        PriceListScheduleResolver::EXPIRE_AT_KEY => $this->getDateTimeWithTimestamp(20160102)
                    ],
                    20160102 => [
                        PriceListScheduleResolver::PRICE_LISTS_KEY => [3],
                        PriceListScheduleResolver::ACTIVATE_AT_KEY => $this->getDateTimeWithTimestamp(20160102),
                        PriceListScheduleResolver::EXPIRE_AT_KEY => $this->getDateTimeWithTimestamp(20160103)
                    ],
                    20160103 => [
                        PriceListScheduleResolver::PRICE_LISTS_KEY => [],
                        PriceListScheduleResolver::ACTIVATE_AT_KEY => $this->getDateTimeWithTimestamp(20160103),
                        PriceListScheduleResolver::EXPIRE_AT_KEY => null
                    ],
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
        if ($priceListSchedule[PriceListScheduleResolver::ON]) {
            $obj->setActiveAt($this->getDateTimeWithTimestamp($priceListSchedule[PriceListScheduleResolver::ON]));
        }
        if ($priceListSchedule[PriceListScheduleResolver::OFF]) {
            $obj->setDeactivateAt($this->getDateTimeWithTimestamp($priceListSchedule[PriceListScheduleResolver::OFF]));
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

    /**
     * @param $timestamp
     * @return \DateTime
     */
    protected function getDateTimeWithTimestamp($timestamp)
    {
        $date = new \DateTime();
        $date->setTimestamp($timestamp);

        return $date;
    }
}
