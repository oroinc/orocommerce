<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\AttributeFilter;

use Oro\Bundle\ProductBundle\AttributeFilter\RestrictableProductAttributesMovingFilter;

class RestrictableProductAttributesMovingFilterTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var RestrictableProductAttributesMovingFilter
     */
    protected $restrictableProductAttributesMovingFilter;

    protected function setUp()
    {
        $this->restrictableProductAttributesMovingFilter = new RestrictableProductAttributesMovingFilter();
    }

    /**
     * @dataProvider attributesDataProvider
     * @param $attributeName
     * @param $expectedResult
     */
    public function testIsRestrictedToMove($attributeName, $expectedResult)
    {
        self::assertEquals(
            $expectedResult,
            $this->restrictableProductAttributesMovingFilter->isRestrictedToMove($attributeName)
        );
    }

    /**
     * @return array
     */
    public function attributesDataProvider()
    {
        return [
            ['sku', false],
            ['inventory_status', true],
        ];
    }
}
