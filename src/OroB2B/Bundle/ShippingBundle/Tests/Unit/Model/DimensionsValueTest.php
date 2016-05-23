<?php

namespace OroB2B\Bundle\ShippingBundle\Tests\Unit\Model;

use Oro\Component\Testing\Unit\EntityTestCaseTrait;

use OroB2B\Bundle\ShippingBundle\Model\DimensionsValue;

class DimensionsValueTest extends \PHPUnit_Framework_TestCase
{
    use EntityTestCaseTrait;

    /** @var DimensionsValue */
    protected $model;

    protected function setUp()
    {
        $this->model = new DimensionsValue();
    }

    protected function tearDown()
    {
        unset($this->model);
    }

    public function testAccessors()
    {
        static::assertPropertyAccessors(
            $this->model,
            [
                ['length', 12.3],
                ['width', 45.6],
                ['height', 78.9]
            ]
        );
    }

    public function testCreate()
    {
        $model = DimensionsValue::create(12, 34, 56);

        $this->assertInstanceOf('OroB2B\Bundle\ShippingBundle\Model\DimensionsValue', $model);
        $this->assertAttributeSame(12, 'length', $model);
        $this->assertAttributeSame(34, 'width', $model);
        $this->assertAttributeSame(56, 'height', $model);
    }

    /**
     * @dataProvider isEmptyDataProvider
     *
     * @param float $length
     * @param float $width
     * @param float $height
     * @param bool $expected
     */
    public function testIsEmpty($length, $width, $height, $expected)
    {
        $model = DimensionsValue::create($length, $width, $height);

        $this->assertEquals($expected, $model->isEmpty());
    }

    public function isEmptyDataProvider()
    {
        return [
            [
                'length' => 12.3,
                'width' => 45.6,
                'height' => 78.9,
                'expected' => false
            ],
            [
                'length' => 12.3,
                'width' => null,
                'height' => null,
                'expected' => false
            ],
            [
                'length' => null,
                'width' => 45.6,
                'height' => null,
                'expected' => false
            ],
            [
                'length' => null,
                'width' => null,
                'height' => 78.9,
                'expected' => false
            ],
            [
                'length' => 12.3,
                'width' => 45.6,
                'height' => null,
                'expected' => false
            ],
            [
                'length' => 12.3,
                'width' => null,
                'height' => 78.9,
                'expected' => false
            ],
            [
                'length' => null,
                'width' => 45.6,
                'height' => 78.9,
                'expected' => false
            ],
            [
                'length' => null,
                'width' => null,
                'height' => null,
                'expected' => true
            ]
        ];
    }
}
