<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Unit\Entity;

use Oro\Component\Testing\Unit\EntityTestCaseTrait;

use OroB2B\Bundle\PricingBundle\Entity\PriceList;
use OroB2B\Bundle\PricingBundle\Entity\PriceListSchedule;

class PriceListScheduleTest extends \PHPUnit_Framework_TestCase
{
    use EntityTestCaseTrait;

    public function testAccessors()
    {
        $this->assertPropertyAccessors(
            new PriceListSchedule(),
            [
                ['priceList', new PriceList()],
                ['activeAt', new \DateTime()],
                ['deactivateAt', new \DateTime()]
            ]
        );
    }

    /**
     * @dataProvider schedulesDataProvider
     * @param PriceListSchedule $schedule1
     * @param PriceListSchedule $schedule2
     * @param bool $isEquivalent
     */
    public function testEquals(PriceListSchedule $schedule1, PriceListSchedule $schedule2, $isEquivalent)
    {
        $this->assertSame($schedule1->equals($schedule2), $isEquivalent);
    }

    /**
     * @dataProvider schedulesDataProvider
     * @param PriceListSchedule $schedule1
     * @param PriceListSchedule $schedule2
     * @param bool $isEquivalent
     */
    public function testGetHash(PriceListSchedule $schedule1, PriceListSchedule $schedule2, $isEquivalent)
    {
        $hash1 = $schedule1->getHash();
        $hash2 = $schedule2->getHash();

        // hash should be same for two equivalent objects
        if ($isEquivalent) {
            $this->assertSame($hash1, $hash2);
        } else {
            $this->assertNotSame($hash1, $hash2);
        }
    }

    /**
     * @return array
     */
    public function schedulesDataProvider()
    {
        $priceList1 = new PriceList();
        $priceList2 = new PriceList();
        $date1 = '2016-03-01T22:00:00Z';
        $date2 = '2016-04-01T22:00:00Z';
        $date3 = '2016-05-01T22:00:00Z';

        $data = [
            'equivalent objects' => [
                'schedule1' => [
                    'priceList' => $priceList1,
                    'dates' => [$date1, $date2]
                ],
                'schedule2' => [
                    'priceList' => $priceList1,
                    'dates' => [$date1, $date2]
                ],
                'isEquivalent' => true
            ],
            'false: different price lists' => [
                'schedule1' => [
                    'priceList' => $priceList1,
                    'dates' => [$date1, $date2]
                ],
                'schedule2' => [
                    'priceList' => $priceList2,
                    'dates' => [$date1, $date2]
                ],
                'isEquivalent' => false
            ],
            'false: different activeAt' => [
                'schedule1' => [
                    'priceList' => $priceList1,
                    'dates' => [$date1, $date2]
                ],
                'schedule2' => [
                    'priceList' => $priceList1,
                    'dates' => [$date3, $date2]
                ],
                'isEquivalent' => false
            ],
            'false: different deactivateAt' => [
                'schedule1' => [
                    'priceList' => $priceList1,
                    'dates' => [$date1, $date2]
                ],
                'schedule2' => [
                    'priceList' => $priceList1,
                    'dates' => [$date1, $date3]
                ],
                'isEquivalent' => false
            ]
        ];

        foreach ($data as &$item) {
            $item['schedule1'] = (new PriceListSchedule(
                new \DateTime($item['schedule1']['dates'][0]),
                new \DateTime($item['schedule1']['dates'][1])
            ))
                ->setPriceList($item['schedule1']['priceList']);
            $item['schedule2'] = (new PriceListSchedule(
                new \DateTime($item['schedule2']['dates'][0]),
                new \DateTime($item['schedule2']['dates'][1])
            ))
                ->setPriceList($item['schedule2']['priceList']);
        };

        return $data;
    }
}
