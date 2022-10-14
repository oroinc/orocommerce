<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Resolver;

use Oro\Bundle\PricingBundle\Entity\CombinedPriceListToPriceList;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Entity\PriceListSchedule;
use Oro\Bundle\PricingBundle\Resolver\PriceListScheduleResolver;

class PriceListScheduleResolverTest extends \PHPUnit\Framework\TestCase
{
    /** @var PriceListScheduleResolver */
    private $resolver;

    protected function setUp(): void
    {
        $this->resolver = new PriceListScheduleResolver();
    }

    /**
     * @dataProvider dataProviderMergeSchedule
     */
    public function testMergeSchedule(array $priceListSchedules, array $priceListRelations, array $expectedResult)
    {
        $result = $this->resolver->mergeSchedule($priceListSchedules, $priceListRelations);
        $this->assertEquals($expectedResult, $result);
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function dataProviderMergeSchedule(): array
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

    private function createScheduleItem(array $priceListSchedule): PriceListSchedule
    {
        $obj = new PriceListSchedule();
        if ($priceListSchedule[PriceListScheduleResolver::ON]) {
            $obj->setActiveAt($this->getDateTimeWithTimestamp($priceListSchedule[PriceListScheduleResolver::ON]));
        }
        if ($priceListSchedule[PriceListScheduleResolver::OFF]) {
            $obj->setDeactivateAt($this->getDateTimeWithTimestamp($priceListSchedule[PriceListScheduleResolver::OFF]));
        }
        $priceList = $this->createMock(PriceList::class);
        $priceList->expects($this->any())
            ->method('getId')
            ->willReturn($priceListSchedule['id']);
        $obj->setPriceList($priceList);

        return $obj;
    }

    private function createCombinedPriceListToPriceList(int $priceListId): CombinedPriceListToPriceList
    {
        $obj = new CombinedPriceListToPriceList();
        $priceList = $this->createMock(PriceList::class);
        $priceList->expects($this->any())
            ->method('getId')
            ->willReturn($priceListId);
        $obj->setPriceList($priceList);
        return $obj;
    }

    private function getDateTimeWithTimestamp(int $timestamp): \DateTime
    {
        $date = new \DateTime();
        $date->setTimestamp($timestamp);

        return $date;
    }
}
