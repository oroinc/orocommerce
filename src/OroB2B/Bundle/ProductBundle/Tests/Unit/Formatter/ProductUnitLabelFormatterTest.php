<?php

namespace OroB2B\Bundle\ProductBundle\Tests\Unit\Formatter;

use Symfony\Component\Translation\TranslatorInterface;

use OroB2B\Bundle\ProductBundle\Formatter\ProductUnitLabelFormatter;

class ProductUnitLabelFormatterTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ProductUnitLabelFormatter
     */
    protected $formatter;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|TranslatorInterface
     */
    protected $translator;

    /**
     * {@inheritDoc}
     */
    protected function setUp()
    {
        $this->translator = $this->getMock('Symfony\Component\Translation\TranslatorInterface');

        $this->formatter = new ProductUnitLabelFormatter($this->translator);
    }

    /**
     * Test Format
     */
    public function testFormat()
    {
        $this->translator->expects($this->once())
            ->method('trans')
            ->with('orob2b.product_unit.kg.label.full');

        $this->formatter->format('kg');
    }

    /**
     * Test FormatShort
     */
    public function testFormatShort()
    {
        $this->translator->expects($this->once())
            ->method('trans')
            ->with('orob2b.product_unit.item.label.short');

        $this->formatter->formatShort('item');
    }
}
