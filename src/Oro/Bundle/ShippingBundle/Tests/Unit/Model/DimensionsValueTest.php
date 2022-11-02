<?php

namespace Oro\Bundle\ShippingBundle\Tests\Unit\Model;

use Oro\Bundle\ShippingBundle\Model\DimensionsValue;
use Oro\Component\Testing\Unit\EntityTestCaseTrait;

class DimensionsValueTest extends \PHPUnit\Framework\TestCase
{
    use EntityTestCaseTrait;

    /** @var DimensionsValue */
    protected $model;

    protected function setUp(): void
    {
        $this->model = new DimensionsValue();
    }

    protected function tearDown(): void
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

        static::assertInstanceOf(DimensionsValue::class, $model);
        static::assertSame(12, $model->getLength());
        static::assertSame(34, $model->getWidth());
        static::assertSame(56, $model->getHeight());
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
