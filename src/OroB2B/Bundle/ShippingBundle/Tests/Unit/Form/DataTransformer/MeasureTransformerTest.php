<?php

namespace OroB2B\Bundle\ShippingBundle\Tests\Unit\Form\DataTransformer;

use Doctrine\Common\Persistence\ObjectRepository;

use OroB2B\Bundle\ProductBundle\Entity\MeasureUnitInterface;
use OroB2B\Bundle\ShippingBundle\Form\DataTransformer\MeasureTransformer;

class MeasureTransformerTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject|ObjectRepository */
    protected $repository;

    /** @var MeasureTransformer */
    protected $transformer;

    protected function setUp()
    {
        $this->repository = $this->getMock('Doctrine\Common\Persistence\ObjectRepository');

        $this->transformer = new MeasureTransformer($this->repository);
    }

    protected function tearDown()
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
     * @return MeasureUnitInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function createUnit($code = null)
    {
        /** @var MeasureUnitInterface|\PHPUnit_Framework_MockObject_MockObject $unit */
        $unit = $this->getMock('OroB2B\Bundle\ProductBundle\Entity\MeasureUnitInterface');
        $unit->expects($this->any())
            ->method('getCode')
            ->willReturn($code);

        return $unit;
    }
}
