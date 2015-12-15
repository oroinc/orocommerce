<?php

namespace OroB2B\Bundle\ProductBundle\Tests\Unit\Twig;

use OroB2B\Bundle\ProductBundle\Entity\ProductUnit;
use OroB2B\Bundle\ProductBundle\Twig\ProductUnitValueExtension;
use OroB2B\Bundle\ProductBundle\Formatter\ProductUnitValueFormatter;

class ProductUnitValueExtensionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ProductUnitValueExtension
     */
    protected $extension;

    /**
     * @var ProductUnitValueFormatter|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $formatter;

    /**
     * {@inheritDoc}
     */
    protected function setUp()
    {
        $this->formatter = $this->getMockBuilder('OroB2B\Bundle\ProductBundle\Formatter\ProductUnitValueFormatter')
            ->disableOriginalConstructor()
            ->getMock();

        $this->extension = new ProductUnitValueExtension($this->formatter);
    }

    public function testGetFilters()
    {
        /** @var \Twig_SimpleFilter[] $filters */
        $filters = $this->extension->getFilters();

        $this->assertInternalType('array', $filters);
        foreach ($filters as $filter) {
            $this->assertInstanceOf('Twig_SimpleFilter', $filter);
        }
    }

    public function testFormat()
    {
        /** @var \Twig_SimpleFilter[] $filters */
        $filters = $this->extension->getFilters();

        $value = 42;
        $unit = (new ProductUnit())->setCode('kg');
        $expectedResult = '42 kilograms';

        $this->formatter->expects($this->once())
            ->method('format')
            ->with($value, $unit)
            ->willReturn($expectedResult);

        $this->assertEquals($expectedResult, call_user_func_array($filters[0]->getCallable(), [$value, $unit]));
    }

    public function testFormatShort()
    {
        /** @var \Twig_SimpleFilter[] $filters */
        $filters = $this->extension->getFilters();

        $value = 42;
        $unit = (new ProductUnit())->setCode('kg');
        $expectedResult = '42 kg';

        $this->formatter->expects($this->once())
            ->method('formatShort')
            ->with($value, $unit)
            ->willReturn($expectedResult);

        $this->assertEquals($expectedResult, call_user_func_array($filters[1]->getCallable(), [$value, $unit]));
    }

    public function testFormatCode()
    {
        /** @var \Twig_SimpleFilter[] $filters */
        $filters = $this->extension->getFilters();

        $value = 42;
        $unitCode = 'kg';
        $expectedResult = '42 kg';

        $this->formatter->expects($this->once())
            ->method('formatCode')
            ->with($value, $unitCode)
            ->willReturn($expectedResult);

        $this->assertEquals($expectedResult, call_user_func_array($filters[2]->getCallable(), [$value, $unitCode]));
    }

    public function testGetName()
    {
        $this->assertEquals(ProductUnitValueExtension::NAME, $this->extension->getName());
    }
}
