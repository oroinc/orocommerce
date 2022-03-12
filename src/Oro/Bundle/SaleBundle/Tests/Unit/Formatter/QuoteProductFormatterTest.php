<?php

namespace Oro\Bundle\SaleBundle\Tests\Unit\Formatter;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\LocaleBundle\Formatter\NumberFormatter;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Formatter\UnitLabelFormatterInterface;
use Oro\Bundle\ProductBundle\Formatter\UnitValueFormatterInterface;
use Oro\Bundle\SaleBundle\Entity\QuoteProduct;
use Oro\Bundle\SaleBundle\Entity\QuoteProductOffer;
use Oro\Bundle\SaleBundle\Entity\QuoteProductRequest;
use Oro\Bundle\SaleBundle\Formatter\QuoteProductFormatter;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @SuppressWarnings(PHPMD.NPathComplexity)
 */
class QuoteProductFormatterTest extends \PHPUnit\Framework\TestCase
{
    /** @var TranslatorInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $translator;

    /** @var UnitValueFormatterInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $productUnitValueFormatter;

    /** @var UnitLabelFormatterInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $productUnitLabelFormatter;

    /** @var NumberFormatter|\PHPUnit\Framework\MockObject\MockObject */
    private $numberFormatter;

    /** @var QuoteProductFormatter */
    private $formatter;

    protected function setUp(): void
    {
        $this->translator = $this->createMock(TranslatorInterface::class);
        $this->numberFormatter = $this->createMock(NumberFormatter::class);
        $this->productUnitValueFormatter = $this->createMock(UnitValueFormatterInterface::class);
        $this->productUnitLabelFormatter = $this->createMock(UnitLabelFormatterInterface::class);

        $this->formatter = new QuoteProductFormatter(
            $this->translator,
            $this->numberFormatter,
            $this->productUnitValueFormatter,
            $this->productUnitLabelFormatter
        );
    }

    /**
     * @dataProvider formatTypeProvider
     */
    public function testFormatType(int $inputData, string $expectedData)
    {
        $this->translator->expects($this->any())
            ->method('trans')
            ->with($expectedData);

        $this->formatter->formatType($inputData);
    }

    public function testFormatTypeLabel()
    {
        $this->translator->expects($this->once())
            ->method('trans')
            ->with('oro.sale.quoteproduct.type.test_type');

        $this->formatter->formatTypeLabel('test_type');
    }

    /**
     * @dataProvider formatTypeLabelsProvider
     */
    public function testFormatTypeLabels(array $inputData, array $expectedData)
    {
        $this->translator->expects($this->any())
            ->method('trans')
            ->willReturnCallback(function ($type) {
                return $type;
            });

        $this->assertSame($expectedData, $this->formatter->formatTypeLabels($inputData));
    }

    /**
     * @dataProvider formatRequestProvider
     */
    public function testFormatRequest(array $inputData, array $expectedData)
    {
        /* @var QuoteProductRequest $item */
        $item = $inputData['item'];

        $item
            ->setQuantity($inputData['quantity'])
            ->setProductUnit($inputData['unit'])
            ->setProductUnitCode($inputData['unitCode'])
            ->setPrice($inputData['price']);

        $this->productUnitValueFormatter->expects($expectedData['formatUnitValue'] ? $this->once() : $this->never())
            ->method('format')
            ->with($inputData['quantity'], $inputData['unitCode'])
            ->willReturn($expectedData['formattedUnits']);

        $price = $inputData['price'] ?: new Price();

        $this->numberFormatter->expects($expectedData['formatPrice'] ? $this->once() : $this->never())
            ->method('formatCurrency')
            ->with($price->getValue(), $price->getCurrency())
            ->willReturn($expectedData['formattedPrice']);

        $this->productUnitLabelFormatter->expects($expectedData['formatUnitLabel'] ? $this->once() : $this->never())
            ->method('format')
            ->with($inputData['unitCode'])
            ->willReturn($expectedData['formattedUnit']);

        $this->translator->expects($this->any())
            ->method('trans')
            ->willReturnArgument(0);

        $this->assertSame(
            $expectedData['formattedString'],
            $this->formatter->formatRequest($inputData['item'])
        );
    }

    /**
     * @dataProvider formatOfferProvider
     */
    public function testFormatOffer(array $inputData, array $expectedData)
    {
        /* @var QuoteProductOffer $item */
        $item = $inputData['item'];

        $item
            ->setQuantity($inputData['quantity'])
            ->setProductUnit($inputData['unit'])
            ->setProductUnitCode($inputData['unitCode'])
            ->setPrice($inputData['price']);

        $this->productUnitValueFormatter->expects($inputData['unit'] ? $this->once() : $this->never())
            ->method('format')
            ->with($inputData['quantity'], $inputData['unitCode'])
            ->willReturn($expectedData['formattedUnits']);

        $price = $inputData['price'] ?: new Price();

        $this->numberFormatter->expects($price ? $this->once() : $this->never())
            ->method('formatCurrency')
            ->with($price->getValue(), $price->getCurrency())
            ->willReturn($expectedData['formattedPrice']);

        $this->productUnitLabelFormatter->expects($this->once())
            ->method('format')
            ->with($inputData['unitCode'])
            ->willReturn($expectedData['formattedUnit']);

        $this->translator->expects($this->once())
            ->method('trans')
            ->with($expectedData['transConstant'], [
                '%count%'   => $expectedData['transIndex'],
                '{units}'   => $expectedData['formattedUnits'],
                '{price}'   => $expectedData['formattedPrice'],
                '{unit}'    => $expectedData['formattedUnit'],
            ]);

        $this->formatter->formatOffer($inputData['item']);
    }

    public function formatTypeProvider(): array
    {
        $res = [
            'invalid type' => [
                'input'     => 0,
                'expected'  => 'N/A',
            ],
        ];

        foreach (QuoteProduct::getTypes() as $key => $value) {
            $res[$value] = [
                'input'     => $key,
                'expected'  => 'oro.sale.quoteproduct.type.' . $value,
            ];
        }

        return $res;
    }

    public function formatTypeLabelsProvider(): array
    {
        return [
            [
                'input' => [
                    1 => 'type_1',
                    2 => 'type_2'
                ],
                'expected' => [
                    1 => 'oro.sale.quoteproduct.type.type_1',
                    2 => 'oro.sale.quoteproduct.type.type_2'
                ],
            ]
        ];
    }

    public function formatRequestProvider(): array
    {
        return [
            'existing product unit' => [
                'inputData' => [
                    'item'      => new QuoteProductRequest(),
                    'quantity'  => 15,
                    'unitCode'  => 'kg',
                    'price'     => Price::create(10, 'USD'),
                    'unit'      => (new ProductUnit())->setCode('kg'),
                ],
                'expectedData' => [
                    'formatPrice'       => true,
                    'formatUnitValue'   => true,
                    'formatUnitLabel'   => true,
                    'formattedUnits'    => '15 kilogram',
                    'formattedPrice'    => '10.00 USD',
                    'formattedUnit'     => 'kilogram',
                    'formattedString'   => 'oro.sale.quoteproductrequest.item',
                ],
            ],
            'empty price' => [
                'inputData' => [
                    'item'      => new QuoteProductRequest(),
                    'quantity'  => 15,
                    'unitCode'  => 'kg',
                    'price'     => null,
                    'unit'      => (new ProductUnit())->setCode('kg'),
                ],
                'expectedData' => [
                    'formatPrice'       => false,
                    'formatUnitValue'   => true,
                    'formatUnitLabel'   => true,
                    'formattedUnits'    => '15 kilogram',
                    'formattedPrice'    => 'N/A',
                    'formattedUnit'     => 'kilogram',
                    'formattedString'   => 'oro.sale.quoteproductrequest.item',
                ],
            ],
            'empty quantity' => [
                'inputData' => [
                    'item'      => new QuoteProductRequest(),
                    'quantity'  => null,
                    'unitCode'  => 'kg',
                    'price'     => Price::create(10, 'USD'),
                    'unit'      => (new ProductUnit())->setCode('kg'),
                ],
                'expectedData' => [
                    'formatPrice'       => true,
                    'formatUnitValue'   => false,
                    'formatUnitLabel'   => true,
                    'formattedUnits'    => 'N/A',
                    'formattedPrice'    => '10.00 USD',
                    'formattedUnit'     => 'kilogram',
                    'formattedString'   => 'oro.sale.quoteproductrequest.item',
                ],
            ],
            'empty quantity and price' => [
                'inputData' => [
                    'item'      => new QuoteProductRequest(),
                    'quantity'  => null,
                    'unitCode'  => 'kg',
                    'price'     => null,
                    'unit'      => (new ProductUnit())->setCode('kg'),
                ],
                'expectedData' => [
                    'formatPrice'       => false,
                    'formatUnitValue'   => false,
                    'formatUnitLabel'   => false,
                    'formattedUnits'    => 'N/A',
                    'formattedPrice'    => 'N/A',
                    'formattedUnit'     => 'kilogram',
                    'formattedString'   => 'N/A',
                ],
            ],
            'deleted product unit' => [
                'inputData' => [
                    'item'      => new QuoteProductRequest(),
                    'quantity'  => 25,
                    'unitCode'  => 'item',
                    'price'     => Price::create(20, 'EUR'),
                    'unit'      => null,
                ],
                'expectedData' => [
                    'formatPrice'       => true,
                    'formatUnitValue'   => false,
                    'formatUnitLabel'   => true,
                    'formattedUnits'    => '25 item',
                    'formattedPrice'    => '20.00 EUR',
                    'formattedUnit'     => 'item',
                    'formattedString'   => 'oro.sale.quoteproductrequest.item',
                ],
            ],
        ];
    }

    public function formatOfferProvider(): array
    {
        return [
            'existing product unit and bundled price type' => [
                'inputData' => [
                    'item'      => (new QuoteProductOffer())->setPriceType(QuoteProductOffer::PRICE_TYPE_BUNDLED),
                    'quantity'  => 15,
                    'unitCode'  => 'kg',
                    'price'     => Price::create(10, 'USD'),
                    'unit'      => (new ProductUnit())->setCode('kg'),
                ],
                'expectedData' => [
                    'formattedUnits'    => '15 kilogram',
                    'formattedPrice'    => '10.00 USD',
                    'formattedUnit'     => 'kilogram',
                    'transConstant'     => 'oro.sale.quoteproductoffer.item_bundled',
                    'transIndex'        => 0,
                ],
            ],
            'existing product unit and default price type' => [
                'inputData' => [
                    'item'      => new QuoteProductOffer(),
                    'quantity'  => 15,
                    'unitCode'  => 'kg',
                    'price'     => Price::create(10, 'USD'),
                    'unit'      => (new ProductUnit())->setCode('kg'),
                ],
                'expectedData' => [
                    'formattedUnits'    => '15 kilogram',
                    'formattedPrice'    => '10.00 USD',
                    'formattedUnit'     => 'kilogram',
                    'transConstant'     => 'oro.sale.quoteproductoffer.item',
                    'transIndex'        => 0,
                ],
            ],
            'existing product unit and allowed increments' => [
                'inputData' => [
                    'item'      => (new QuoteProductOffer())->setAllowIncrements(true),
                    'quantity'  => 15,
                    'unitCode'  => 'kg',
                    'price'     => Price::create(10, 'USD'),
                    'unit'      => (new ProductUnit())->setCode('kg'),
                ],
                'expectedData' => [
                    'formattedUnits'    => '15 kilogram',
                    'formattedPrice'    => '10.00 USD',
                    'formattedUnit'     => 'kilogram',
                    'transConstant'     => 'oro.sale.quoteproductoffer.item',
                    'transIndex'        => 1,
                ],
            ],
            'deleted product unit' => [
                'inputData' => [
                    'item'      => new QuoteProductOffer(),
                    'quantity'  => 25,
                    'unitCode'  => 'item',
                    'price'     => Price::create(20, 'EUR'),
                    'unit'      => null,
                ],
                'expectedData' => [
                    'formattedUnits'    => '25 item',
                    'formattedPrice'    => '20.00 EUR',
                    'formattedUnit'     => 'item',
                    'transConstant'     => 'oro.sale.quoteproductoffer.item',
                    'transIndex'        => 0,
                ],
            ],
        ];
    }
}
