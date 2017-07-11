<?php

namespace Oro\Bundle\PromotionBundle\Tests\Unit\Placeholder;

use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\PromotionBundle\Placeholder\OrderAdditionalPlaceholderFilter;
use Oro\Component\Testing\Unit\EntityTrait;

class OrderAdditionalPlaceholderFilterTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    /**
     * @var OrderAdditionalPlaceholderFilter
     */
    private $filter;

    protected function setUp()
    {
        $this->filter = new OrderAdditionalPlaceholderFilter();
    }

    /**
     * @dataProvider isApplicableProvider
     * @param mixed $entity
     * @param bool $expectedResult
     */
    public function testIsApplicable($entity, $expectedResult)
    {
        $this->assertEquals($expectedResult, $this->filter->isApplicable($entity));
    }

    /**
     * @return array
     */
    public function isApplicableProvider()
    {
        return [
            'not order' => [
                'entity' => new \stdClass(),
                'expectedResult' => false,
            ],
            'order without id' => [
                'entity' => new Order(),
                'expectedResult' => false,
            ],
            'order with id' => [
                'entity' => $this->getEntity(Order::class, ['id' => 777]),
                'expectedResult' => true,
            ],
        ];
    }
}
