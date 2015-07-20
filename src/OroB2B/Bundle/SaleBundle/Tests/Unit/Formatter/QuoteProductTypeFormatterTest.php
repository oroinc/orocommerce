<?php

namespace OroB2B\Bundle\SaleBundle\Tests\Unit\Formatter;

use Symfony\Component\Translation\TranslatorInterface;

use OroB2B\Bundle\SaleBundle\Formatter\QuoteProductTypeFormatter;

class QuoteProductTypeFormatterTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var QuoteProductTypeFormatter
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
        $this->translator   = $this->getMock('Symfony\Component\Translation\TranslatorInterface');

        $this->formatter    = new QuoteProductTypeFormatter($this->translator);
    }

    public function testFormatTypeLabel()
    {
        $this->translator->expects($this->once())
            ->method('trans')
            ->with('orob2b.sale.quoteproduct.type.test_type')
        ;

        $this->formatter->formatTypeLabel('test_type');
    }

    /**
     * @param array $inputData
     * @param array $expectedData
     *
     * @dataProvider formatTypeLabelsProvider
     */
    public function testFormatTypeLabels(array $inputData, array $expectedData)
    {
        $this->translator->expects($this->any())
            ->method('trans')
            ->will($this->returnCallback(function ($type) {
                return $type;
            }))
        ;

        $this->assertSame($expectedData, $this->formatter->formatTypeLabels($inputData));
    }

    /**
     * @return array
     */
    public function formatTypeLabelsProvider()
    {
        return [
            [
                'input' => [
                    1 => 'type_1',
                    2 => 'type_2'
                ],
                'expected' => [
                    1 => 'orob2b.sale.quoteproduct.type.type_1',
                    2 => 'orob2b.sale.quoteproduct.type.type_2'
                ],
            ]
        ];
    }
}
