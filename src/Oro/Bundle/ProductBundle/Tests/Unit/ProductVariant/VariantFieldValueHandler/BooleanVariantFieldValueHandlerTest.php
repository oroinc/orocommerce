<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\ProductVariant\VariantFieldValueHandler;

use Oro\Bundle\ProductBundle\ProductVariant\VariantFieldValueHandler\BooleanVariantFieldValueHandler;

class BooleanVariantFieldValueHandlerTest extends \PHPUnit_Framework_TestCase
{
    /** @var BooleanVariantFieldValueHandler */
    private $handler;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->handler = new BooleanVariantFieldValueHandler();
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown()
    {
        unset($this->handler);
    }

    public function testGetType()
    {
        $this->assertEquals(BooleanVariantFieldValueHandler::TYPE, $this->handler->getType());
    }

    public function testGetValues()
    {
        $this->assertEquals([0 => 'No', 1 => 'Yes'], $this->handler->getPossibleValues('testField'));
    }

    /**
     * @dataProvider getScalarValueProvider
     * @param $value
     * @param $expected
     */
    public function testGetScalarValue($value, $expected)
    {
        $this->assertEquals($expected, $this->handler->getScalarValue($value));
    }

    /**
     * @return array
     */
    public function getScalarValueProvider()
    {
        return [
            'return false' => [
                'value' => 0,
                'expected' => false
            ],
            'return true' => [
                'value' => 1,
                'expected' => true
            ]
        ];
    }
}
