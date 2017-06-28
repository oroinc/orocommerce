<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Validator\Constraints;

use Oro\Bundle\PricingBundle\Entity\PriceList;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

use Oro\Bundle\PricingBundle\Validator\Constraints\SchedulesIntersection;
use Oro\Bundle\PricingBundle\Validator\Constraints\SchedulesIntersectionValidator;
use Oro\Bundle\PricingBundle\Entity\PriceListSchedule;
use Oro\Bundle\PricingBundle\Form\Type\PriceListScheduleType;

class SchedulesIntersectionValidatorTest extends \PHPUnit_Framework_TestCase
{
    const MESSAGE = 'oro.pricing.validators.price_list.schedules_intersection.message';

    /**
     * @dataProvider validateSuccessDataProvider
     *
     * @param array $collection
     */
    public function testValidateSuccess(array $collection)
    {
        $constraint = new SchedulesIntersection();
        $context = $this->getContextMock();

        $context->expects($this->never())
            ->method('buildViolation');
        $collection = $this->normalizeCollection($collection);

        $validator = new SchedulesIntersectionValidator();
        $validator->initialize($context);

        $pl = (new PriceList())->setSchedules($collection);
        foreach($collection as $date) {
            $date->setPriceList($pl);
            $validator->validate($date, $constraint);
        }
    }

    /**
     * @return array
     */
    public function validateSuccessDataProvider()
    {
        return [
            'without intersections' => [
                'collection' => [
                    ['2016-01-01', '2016-01-31'],
                    ['2016-02-01', '2016-03-01'],
                ],
                'intersections' => []
            ],
            'without intersections, left=null' => [
                'collection' => [
                    [null, '2016-01-31'],
                    ['2016-02-01', '2016-03-01'],
                ],
                'intersections' => []
            ],
            'without intersections, right = null' => [
                'collection' => [
                    ['2016-01-01', '2016-01-31'],
                    ['2016-02-01', null],
                ],
                'intersections' => []
            ],
            'without intersections, right = null and left = null' => [
                'collection' => [
                    [null, '2016-01-31'],
                    ['2016-02-01', null],
                ],
                'intersections' => []
            ],
            'without intersections, right = null and left = null(inverse)' => [
                'collection' => [
                    ['2016-02-01', null],
                    [null, '2016-01-03'],
                ],
                'intersections' => []
            ],
        ];
    }

    /**
     * @dataProvider validateFailDataProvider
     *
     * @param array $collection
     * @param array $intersections
     */
    public function testValidateFail(array $collection, array $intersections)
    {
        $constraint = new SchedulesIntersection();
        $context = $this->getContextMock();
        $builder = $this->getBuilderMock();

        $builder->expects($this->any())
            ->method('addViolation')
            ->willReturn($builder);

        $context->expects($this->any())
            ->method('buildViolation')
            ->with(self::MESSAGE, [])
            ->willReturn($builder);

        $builder->expects($this->exactly(count($intersections)))
            ->method('atPath')
            ->with(PriceListScheduleType::ACTIVE_AT_FIELD)
            ->willReturn($this->getBuilderMock());

        $collection = $this->normalizeCollection($collection);

        $validator = new SchedulesIntersectionValidator();
        $validator->initialize($context);
        $pl = (new PriceList())->setSchedules($collection);
        foreach($collection as $date) {
            $date->setPriceList($pl);
            $validator->validate($date, $constraint);
        }
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testNotPriceListScheduleValue()
    {
        $constraint = new SchedulesIntersection();
        $context = $this->getContextMock();

        $validator = new SchedulesIntersectionValidator();
        $validator->initialize($context);
        /** @var array $notIterable */
        $notIterable = 12;
        $validator->validate($notIterable, $constraint);
    }

    /**
     * @return array
     */
    public function validateFailDataProvider()
    {
        return [
            'without intersections, left = null and right = null' => [
                'collection' => [
                    [null, '2016-02-01'],
                    ['2016-01-15', null],
                ],
                'intersections' => [0, 1]
            ],

            'intersects' => [
                'collection' => [
                    ['2016-01-01', '2016-02-01'],
                    ['2016-01-15', '2016-03-01'],
                ],
                'intersections' => [0, 1]
            ],
            'intersects, right = null' => [
                'collection' => [
                    ['2016-01-01', '2016-02-01'],
                    ['2016-01-15', null],
                ],
                'intersections' => [0, 1]
            ],
            'intersects, both right = null' => [
                'collection' => [
                    ['2016-01-01', null],
                    ['2016-01-15', null],
                ],
                'intersections' => [0, 1]
            ],
            'intersects, left = null' => [
                'collection' => [
                    [null, '2016-02-01'],
                    ['2016-01-15', '2016-03-01'],
                ],
                'intersections' => [0, 1]
            ],

            'contains' => [
                'collection' => [
                    ['2016-01-01', '2016-04-01'],
                    ['2016-02-01', '2016-03-01'],
                ],
                'intersections' => [0, 1]
            ],
            'contains, left = null' => [
                'collection' => [
                    [null, '2016-04-01'],
                    ['2016-02-01', '2016-03-01'],
                ],
                'intersections' => [0, 1]
            ],
            'contains, right = null' => [
                'collection' => [
                    ['2016-01-01', null],
                    ['2016-02-01', '2016-03-01'],
                ],
                'intersections' => [0, 1]
            ],
            'contains, all null' => [
                'collection' => [
                    [null, null],
                    ['2016-01-01', '2016-01-02'],
                ],
                'intersections' => [0, 1]
            ]
        ];
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function getBuilderMock()
    {
        return $this->getMockBuilder('Symfony\Component\Validator\Violation\ConstraintViolationBuilder')
            ->disableOriginalConstructor()
            ->getMock();
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|ExecutionContextInterface $context
     */
    protected function getContextMock()
    {
        return $this->createMock(ExecutionContextInterface::class);
    }

    /**
     * @param array $collection
     * @return PriceListSchedule[]
     */
    protected function normalizeCollection(array $collection)
    {
        $collection = array_map(function ($dates) {
            return $this->normalizeSingleDateData($dates);
        }, $collection);

        return $collection;
    }

    /**
     * @param array $dates
     *
     * @return PriceListSchedule
     */
    protected function normalizeSingleDateData(array $dates)
    {
        $start = (null === $dates[0]) ? null : new \DateTime($dates[0]);
        $end = (null === $dates[1]) ? null : new \DateTime($dates[1]);

        return new PriceListSchedule($start, $end);
    }
}
