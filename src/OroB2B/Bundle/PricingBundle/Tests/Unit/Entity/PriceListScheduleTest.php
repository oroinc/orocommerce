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
     * @dataProvider testEqualsDataProvider
     * @param array $schedule
     * @param array $compared
     * @param bool $result
     */
    public function testEquals(array $schedule, array $compared, $result)
    {
        $comparedSchedule = (new PriceListSchedule(
            new \DateTime($compared['dates'][0]),
            new \DateTime($compared['dates'][1])
        ))
            ->setPriceList($compared['priceList']);
        $plSchedule = (new PriceListSchedule(
            new \DateTime($schedule['dates'][0]),
            new \DateTime($schedule['dates'][1])
        ))
            ->setPriceList($schedule['priceList']);
        $this->assertSame($plSchedule->equals($comparedSchedule), $result);
    }

    /**
     * @return array
     */
    public function testEqualsDataProvider()
    {
        $priceList1 = new PriceList();
        $priceList2 = new PriceList();
        $date1 = '2016-03-01T22:00:00Z';
        $date2 = '2016-04-01T22:00:00Z';
        $date3 = '2016-05-01T22:00:00Z';

        return [
            'true' => [
                'schedule' => [
                    'priceList' => $priceList1,
                    'dates' => [$date1, $date2]
                ],
                'compared' => [
                    'priceList' => $priceList1,
                    'dates' => [$date1, $date2]
                ],
                'result' => true
            ],
            'false: different price lists' => [
                'schedule' => [
                    'priceList' => $priceList1,
                    'dates' => [$date1, $date2]
                ],
                'compared' => [
                    'priceList' => $priceList2,
                    'dates' => [$date1, $date2]
                ],
                'result' => false
            ],
            'false: different activeAt' => [
                'schedule' => [
                    'priceList' => $priceList1,
                    'dates' => [$date1, $date2]
                ],
                'compared' => [
                    'priceList' => $priceList1,
                    'dates' => [$date3, $date2]
                ],
                'result' => false
            ],
            'false: different deactivateAt' => [
                'schedule' => [
                    'priceList' => $priceList1,
                    'dates' => [$date1, $date2]
                ],
                'compared' => [
                    'priceList' => $priceList1,
                    'dates' => [$date1, $date3]
                ],
                'result' => false
            ]
        ];
    }
}
