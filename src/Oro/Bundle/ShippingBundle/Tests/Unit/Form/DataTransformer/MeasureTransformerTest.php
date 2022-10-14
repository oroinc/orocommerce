<?php

namespace Oro\Bundle\ShippingBundle\Tests\Unit\Form\DataTransformer;

use Doctrine\Persistence\ObjectRepository;
use Oro\Bundle\ProductBundle\Entity\MeasureUnitInterface;
use Oro\Bundle\ShippingBundle\Form\DataTransformer\MeasureTransformer;

class MeasureTransformerTest extends \PHPUnit\Framework\TestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject|ObjectRepository */
    private $repository;

    /** @var MeasureTransformer */
    private $transformer;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(ObjectRepository::class);

        $this->transformer = new MeasureTransformer($this->repository);
    }

    /**
     * @dataProvider reverseTransformDataProvider
     */
    public function testReverseTransform(array|string|null $value, array $expected)
    {
        $this->assertEquals($expected, $this->transformer->reverseTransform($value));
    }

    public function reverseTransformDataProvider(): array
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
     */
    public function testTransform(array|string|null $value, array $expected)
    {
        $this->repository->expects(is_array($value) && count($value) ? $this->once() : $this->never())
            ->method('find')
            ->willReturnCallback(function ($value) {
                return $this->createUnit($value);
            });

        $this->assertEquals($expected, $this->transformer->transform($value));
    }

    public function transformDataProvider(): array
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

    private function createUnit(?string $code = null): MeasureUnitInterface
    {
        $unit = $this->createMock(MeasureUnitInterface::class);
        $unit->expects($this->any())
            ->method('getCode')
            ->willReturn($code);

        return $unit;
    }
}
