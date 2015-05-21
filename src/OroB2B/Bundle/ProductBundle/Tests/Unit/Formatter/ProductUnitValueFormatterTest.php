<?php

namespace OroB2B\Bundle\ProductBundle\Tests\Unit\Rounding;

use Symfony\Component\Translation\TranslatorInterface;

use OroB2B\Bundle\ProductBundle\Entity\ProductUnit;
use OroB2B\Bundle\ProductBundle\Formatter\ProductUnitValueFormatter;

class ProductUnitValueFormatterTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ProductUnitValueFormatter
     */
    protected $formatter;

    /**
     * @var TranslatorInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $translator;

    /**
     * {@inheritDoc}
     */
    protected function setUp()
    {
        $this->translator = $this->getMock('Symfony\Component\Translation\TranslatorInterface');

        $this->formatter = new ProductUnitValueFormatter($this->translator);
    }

    /**
     * Test Format
     */
    public function testFormat()
    {
        $this->translator->expects($this->once())
            ->method('transChoice')
            ->with('orob2b.product_unit.kg.value.full', 42);

        $this->formatter->format(42, (new ProductUnit())->setCode('kg'));
    }

    /**
     * Test FormatShort
     */
    public function testFormatShort()
    {
        $this->translator->expects($this->once())
            ->method('transChoice')
            ->with('orob2b.product_unit.item.value.short', 42);

        $this->formatter->formatShort(42, (new ProductUnit())->setCode('item'));
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage The parameter "value" must be a numeric, but it is of type string.
     */
    public function testFormatWithInvalidValue()
    {
        $this->formatter->formatShort('test', (new ProductUnit())->setCode('item'));
    }
}
