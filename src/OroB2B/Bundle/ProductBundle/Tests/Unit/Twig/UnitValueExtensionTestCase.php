<?php

namespace OroB2B\Bundle\ProductBundle\Tests\Unit\Twig;

use OroB2B\Bundle\ProductBundle\Entity\MeasureUnitInterface;
use OroB2B\Bundle\ProductBundle\Formatter\UnitValueFormatter;

abstract class UnitValueExtensionTestCase extends \PHPUnit_Framework_TestCase
{
    /** @var UnitValueFormatter|\PHPUnit_Framework_MockObject_MockObject */
    protected $formatter;

    protected function setUp()
    {
        $this->formatter = $this->getMockBuilder('OroB2B\Bundle\ProductBundle\Formatter\UnitValueFormatter')
            ->disableOriginalConstructor()
            ->getMock();
    }

    public function testGetFilters()
    {
        /** @var \Twig_SimpleFilter[] $filters */
        $filters = $this->getExtension()->getFilters();

        $this->assertCount(3, $filters);

        $this->assertInternalType('array', $filters);
        foreach ($filters as $filter) {
            $this->assertInstanceOf('Twig_SimpleFilter', $filter);
        }
    }

    public function testFormat()
    {
        /** @var \Twig_SimpleFilter[] $filters */
        $filters = $this->getExtension()->getFilters();

        $value = 42;
        $unit = $this->createObject('kg');
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
        $filters = $this->getExtension()->getFilters();

        $value = 42;
        $unit = $this->createObject('kg');
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
        $filters = $this->getExtension()->getFilters();

        $value = 42;
        $unitCode = 'kg';
        $expectedResult = '42 kg';

        $this->formatter->expects($this->once())
            ->method('formatCode')
            ->with($value, $unitCode)
            ->willReturn($expectedResult);

        $this->assertEquals($expectedResult, call_user_func_array($filters[2]->getCallable(), [$value, $unitCode]));
    }

    /**
     * @return \Twig_Extension
     */
    abstract protected function getExtension();

    /**
     * @param string $code
     * @return MeasureUnitInterface
     */
    abstract protected function createObject($code);
}
