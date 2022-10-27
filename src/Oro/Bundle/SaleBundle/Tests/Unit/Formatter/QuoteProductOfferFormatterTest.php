<?php

namespace Oro\Bundle\SaleBundle\Tests\Unit\Formatter;

use Oro\Bundle\SaleBundle\Formatter\QuoteProductOfferFormatter;
use Symfony\Contracts\Translation\TranslatorInterface;

class QuoteProductOfferFormatterTest extends \PHPUnit\Framework\TestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject|TranslatorInterface */
    private $translator;

    /** @var QuoteProductOfferFormatter */
    private $formatter;

    protected function setUp(): void
    {
        $this->translator = $this->createMock(TranslatorInterface::class);

        $this->formatter = new QuoteProductOfferFormatter($this->translator);
    }

    public function testFormatPriceTypeLabel()
    {
        $this->translator->expects($this->once())
            ->method('trans')
            ->with('oro.sale.quoteproductoffer.price_type.test_type');

        $this->formatter->formatPriceTypeLabel('test_type');
    }

    /**
     * @dataProvider formatPriceTypeLabelsProvider
     */
    public function testFormatPriceTypeLabels(array $inputData, array $expectedData)
    {
        $this->translator->expects($this->any())
            ->method('trans')
            ->willReturnCallback(function ($type) {
                return $type;
            });

        $this->assertSame($expectedData, $this->formatter->formatPriceTypeLabels($inputData));
    }

    public function formatPriceTypeLabelsProvider(): array
    {
        return [
            [
                'input' => [
                    1 => 'type_1',
                    2 => 'type_2'
                ],
                'expected' => [
                    1 => 'oro.sale.quoteproductoffer.price_type.type_1',
                    2 => 'oro.sale.quoteproductoffer.price_type.type_2'
                ],
            ]
        ];
    }
}
