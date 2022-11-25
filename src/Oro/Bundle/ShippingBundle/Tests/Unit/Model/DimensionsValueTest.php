<?php

namespace Oro\Bundle\ShippingBundle\Tests\Unit\Model;

use Oro\Bundle\ShippingBundle\Model\DimensionsValue;
use Oro\Component\Testing\Unit\EntityTestCaseTrait;

class DimensionsValueTest extends \PHPUnit\Framework\TestCase
{
    use EntityTestCaseTrait;

    /** @var DimensionsValue */
    private $model;

    protected function setUp(): void
    {
        $this->model = new DimensionsValue();
    }

    public function testAccessors()
    {
        self::assertPropertyAccessors(
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

        self::assertInstanceOf(DimensionsValue::class, $model);
        self::assertSame(12, $model->getLength());
        self::assertSame(34, $model->getWidth());
        self::assertSame(56, $model->getHeight());
    }

    /**
     * @dataProvider isEmptyDataProvider
     */
    public function testIsEmpty(?float $length, ?float $width, ?float $height, bool $expected)
    {
        $model = DimensionsValue::create($length, $width, $height);

        $this->assertEquals($expected, $model->isEmpty());
    }

    public function isEmptyDataProvider(): array
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
