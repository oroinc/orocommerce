<?php

namespace OroB2B\Bundle\SaleBundle\Tests\Unit\Formatter;

use Symfony\Component\Translation\TranslatorInterface;

use OroB2B\Bundle\SaleBundle\Formatter\QuoteProductOfferFormatter;

class QuoteProductOfferFormatterTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var QuoteProductOfferFormatter
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

        $this->formatter    = new QuoteProductOfferFormatter($this->translator);
    }

    public function testFormatPriceTypeLabel()
    {
        $this->translator->expects($this->once())
            ->method('trans')
            ->with('orob2b.sale.quoteproductoffer.price_type.test_type')
        ;

        $this->formatter->formatPriceTypeLabel('test_type');
    }

    /**
     * @param array $inputData
     * @param array $expectedData
     *
     * @dataProvider formatPriceTypeLabelsProvider
     */
    public function testFormatPriceTypeLabels(array $inputData, array $expectedData)
    {
        $this->translator->expects($this->any())
            ->method('trans')
            ->will($this->returnCallback(function ($type) {
                return $type;
            }))
        ;

        $this->assertSame($expectedData, $this->formatter->formatPriceTypeLabels($inputData));
    }

    /**
     * @return array
     */
    public function formatPriceTypeLabelsProvider()
    {
        return [
            [
                'input' => [
                    1 => 'type_1',
                    2 => 'type_2'
                ],
                'expected' => [
                    1 => 'orob2b.sale.quoteproductoffer.price_type.type_1',
                    2 => 'orob2b.sale.quoteproductoffer.price_type.type_2'
                ],
            ]
        ];
    }
}
