<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Unit\Validator\Constraints;

use Symfony\Component\Validator\Context\ExecutionContext;

use OroB2B\Bundle\PricingBundle\Validator\Constraints\SchedulesIntersection;
use OroB2B\Bundle\PricingBundle\Validator\Constraints\SchedulesIntersectionValidator;
use OroB2B\Bundle\PricingBundle\Entity\PriceListSchedule;
use OroB2B\Bundle\PricingBundle\Form\Type\PriceListScheduleType;

class SchedulesIntersectionValidatorTest extends \PHPUnit_Framework_TestCase
{
    const MESSAGE = 'orob2b.pricing.validators.price_list.schedules_intersection.message';

    /**
     * @dataProvider validateDataProvider
     *
     * @param array $collection
     * @param array $intersections
     */
    public function testValidate(array $collection, array $intersections)
    {
        $constraint = new SchedulesIntersection();
        $context = $this->getContextMock();

        if (!$intersections) {
            $context->expects($this->never())
                ->method('buildViolation');
        } else {
            $builder = $this->getBuilderMock();

            $builder->expects($this->any())
                ->method('addViolation')
                ->willReturn($builder);

            $context->expects($this->any())
                ->method('buildViolation')
                ->with(self::MESSAGE, [])
                ->willReturn($builder);

            foreach ($intersections as $i => $intersection) {
                $path = printf('[%d].%s', $intersection, PriceListScheduleType::ACTIVE_AT_FIELD);
                $builder->expects($this->at($i))
                    ->method('atPath')
                    ->with($path)
                    ->willReturn($this->getBuilderMock());
            }
        }

        $collection = array_map(function ($dates) {
            $start = (null === $dates[0]) ? null : new \DateTime($dates[0]);
            $end = (null === $dates[1]) ? null : new \DateTime($dates[1]);
            return new PriceListSchedule($start, $end);
        }, $collection);

        $validator = new SchedulesIntersectionValidator();
        $validator->initialize($context);
        $validator->validate($collection, $constraint);
    }

    /**
     * @return array
     */
    public function validateDataProvider()
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
     * @return \PHPUnit_Framework_MockObject_MockObject|ExecutionContext $context
     */
    protected function getContextMock()
    {
        return $this->getMockBuilder('Symfony\Component\Validator\Context\ExecutionContext')
            ->disableOriginalConstructor()
            ->getMock();
    }
}
