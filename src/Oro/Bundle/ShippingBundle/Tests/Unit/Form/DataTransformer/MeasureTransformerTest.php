<?php

namespace Oro\Bundle\ShippingBundle\Tests\Unit\Form\DataTransformer;

use Doctrine\Persistence\ObjectRepository;
use Oro\Bundle\ProductBundle\Entity\MeasureUnitInterface;
use Oro\Bundle\ShippingBundle\Form\DataTransformer\MeasureTransformer;

class MeasureTransformerTest extends \PHPUnit\Framework\TestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject|ObjectRepository */
    protected $repository;

    /** @var MeasureTransformer */
    protected $transformer;

    protected function setUp(): void
    {
        $this->repository = $this->createMock('Doctrine\Persistence\ObjectRepository');

        $this->transformer = new MeasureTransformer($this->repository);
    }

    protected function tearDown(): void
    {
        unset($this->transformer, $this->repository);
    }

    /**
     * @dataProvider reverseTransformDataProvider
     *
     * @param MeasureUnitInterface[] $value
     * @param array $expected
     */
    public function testReverseTransform($value, $expected)
    {
        $this->assertEquals($expected, $this->transformer->reverseTransform($value));
    }

    /**
     * @return array
     */
    public function reverseTransformDataProvider()
    {
        return [
            [null, []],
            [[], []],
            ['string', []],
            ['string', []],
            [[$this->createUnit()], [null]],
            [[$this->createUnit('test code')], ['test code']]
        ];
    }

    /**
     * @dataProvider transformDataProvider
     *
     * @param array $value
     * @param MeasureUnitInterface[] $expected
     */
    public function testTransform($value, $expected)
    {
        $this->repository->expects(is_array($value) && count($value) ? $this->once() : $this->never())
            ->method('find')
            ->willReturnCallback(
                function ($value) {
                    return $this->createUnit($value);
                }
            );

        $this->assertEquals($expected, $this->transformer->transform($value));
    }

    /**
     * @return array
     */
    public function transformDataProvider()
    {
        return [
            [null, []],
            [[], []],
            ['string', []],
            ['string', []],
            [[null], [$this->createUnit()]],
            [['test code'], [$this->createUnit('test code')]]
        ];
    }

    /**
     * @param string $code
     * @return MeasureUnitInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected function createUnit($code = null)
    {
        /** @var MeasureUnitInterface|\PHPUnit\Framework\MockObject\MockObject $unit */
        $unit = $this->createMock('Oro\Bundle\ProductBundle\Entity\MeasureUnitInterface');
        $unit->expects($this->any())
            ->method('getCode')
            ->willReturn($code);

        return $unit;
    }
}
