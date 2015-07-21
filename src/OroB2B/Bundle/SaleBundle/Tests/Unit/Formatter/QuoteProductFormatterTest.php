<?php

namespace OroB2B\Bundle\SaleBundle\Tests\Unit\Formatter;

use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\CurrencyBundle\Model\Price;
use Oro\Bundle\CurrencyBundle\Model\OptionalPrice;
use Oro\Bundle\LocaleBundle\Formatter\NumberFormatter;

use OroB2B\Bundle\ProductBundle\Entity\ProductUnit;

use OroB2B\Bundle\ProductBundle\Formatter\ProductUnitValueFormatter;
use OroB2B\Bundle\ProductBundle\Formatter\ProductUnitLabelFormatter;

use OroB2B\Bundle\SaleBundle\Entity\QuoteProduct;
use OroB2B\Bundle\SaleBundle\Entity\QuoteProductOffer;
use OroB2B\Bundle\SaleBundle\Entity\QuoteProductRequest;
use OroB2B\Bundle\SaleBundle\Formatter\QuoteProductFormatter;

/**
 * @SuppressWarnings(PHPMD.NPathComplexity)
 */
class QuoteProductTypeFormatterTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var QuoteProductFormatter
     */
    protected $formatter;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|TranslatorInterface
     */
    protected $translator;

   /**
     * @var \PHPUnit_Framework_MockObject_MockObject|ProductUnitValueFormatter
     */
    protected $productUnitValueFormatter;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|ProductUnitLabelFormatter
     */
    protected $productUnitLabelFormatter;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|NumberFormatter
     */
    protected $numberFormatter;

    /**
     * {@inheritDoc}
     */
    protected function setUp()
    {
        $this->translator = $this->getMock('Symfony\Component\Translation\TranslatorInterface');

        $this->numberFormatter = $this->getMockBuilder('Oro\Bundle\LocaleBundle\Formatter\NumberFormatter')
            ->disableOriginalConstructor()
            ->getMock();

        $this->productUnitValueFormatter = $this->getMockBuilder(
            'OroB2B\Bundle\ProductBundle\Formatter\ProductUnitValueFormatter'
        )
            ->disableOriginalConstructor()
            ->getMock();

        $this->productUnitLabelFormatter = $this->getMockBuilder(
            'OroB2B\Bundle\ProductBundle\Formatter\ProductUnitLabelFormatter'
        )
            ->disableOriginalConstructor()
            ->getMock();

        $this->formatter = new QuoteProductFormatter(
            $this->translator,
            $this->numberFormatter,
            $this->productUnitValueFormatter,
            $this->productUnitLabelFormatter
        );
    }

    /**
     * @param mixed $inputData
     * @param mixed $expectedData
     *
     * @dataProvider formatTypeProvider
     */
    public function testFormatType($inputData, $expectedData)
    {
        $this->translator->expects($this->any())
            ->method('trans')
            ->with($expectedData)
        ;

        $this->formatter->formatType($inputData);
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
     * @param array $inputData
     * @param array $expectedData
     *
     * @dataProvider formatRequestProvider
     */
    public function testFormatRequest(array $inputData, array $expectedData)
    {
        /* @var $item QuoteProductRequest */
        $item = $inputData['item'];

        $item
            ->setQuantity($inputData['quantity'])
            ->setProductUnit($inputData['unit'])
            ->setProductUnitCode($inputData['unitCode'])
            ->setPrice($inputData['price'])
        ;

        $this->productUnitValueFormatter->expects($expectedData['formatUnitValue'] ? $this->once() : $this->never())
            ->method('format')
            ->with($inputData['quantity'], $inputData['unitCode'])
            ->will($this->returnValue($expectedData['formattedUnits']))
        ;

        $price = $inputData['price'] ?: new OptionalPrice();

        $this->numberFormatter->expects($expectedData['formatPrice'] ? $this->once() : $this->never())
            ->method('formatCurrency')
            ->with($price->getValue(), $price->getCurrency())
            ->will($this->returnValue($expectedData['formattedPrice']))
        ;

        $this->productUnitLabelFormatter->expects($expectedData['formatUnitLabel'] ? $this->once() : $this->never())
            ->method('format')
            ->with($inputData['unitCode'])
            ->will($this->returnValue($expectedData['formattedUnit']))
        ;

        $this->translator->expects($this->any())
            ->method('trans')
            ->will($this->returnCallback(function ($id) {
                return $id;
            }))
        ;

        $this->assertSame(
            $expectedData['formattedString'],
            $this->formatter->formatRequest($inputData['item'])
        );
    }

    /**
     * @param array $inputData
     * @param array $expectedData
     *
     * @dataProvider formatOfferProvider
     */
    public function testFormatOffer(array $inputData, array $expectedData)
    {
        /* @var $item QuoteProductOffer */
        $item = $inputData['item'];

        $item
            ->setQuantity($inputData['quantity'])
            ->setProductUnit($inputData['unit'])
            ->setProductUnitCode($inputData['unitCode'])
            ->setPrice($inputData['price'])
        ;

        $this->productUnitValueFormatter->expects($inputData['unit'] ? $this->once() : $this->never())
            ->method('format')
            ->with($inputData['quantity'], $inputData['unitCode'])
            ->will($this->returnValue($expectedData['formattedUnits']))
        ;

        $price = $inputData['price'] ?: new Price();

        $this->numberFormatter->expects($price ? $this->once() : $this->never())
            ->method('formatCurrency')
            ->with($price->getValue(), $price->getCurrency())
            ->will($this->returnValue($expectedData['formattedPrice']))
        ;

        $this->productUnitLabelFormatter->expects($this->once())
            ->method('format')
            ->with($inputData['unitCode'])
            ->will($this->returnValue($expectedData['formattedUnit']))
        ;

        $this->translator->expects($this->once())
            ->method('transChoice')
            ->with($expectedData['transConstant'], $expectedData['transIndex'], [
                '{units}'   => $expectedData['formattedUnits'],
                '{price}'   => $expectedData['formattedPrice'],
                '{unit}'    => $expectedData['formattedUnit'],
            ])
        ;

        $this->formatter->formatOffer($inputData['item']);
    }

    /**
     * @return array
     */
    public function formatTypeProvider()
    {
        $res = [
            'invalid type' => [
                'input'     => 'asdf',
                'expected'  => 'N/A',
            ],
        ];

        foreach (QuoteProduct::getTypes() as $key => $value) {
            $res[$value] = [
                'input'     => $key,
                'expected'  => 'orob2b.sale.quoteproduct.type.' . $value,
            ];
        }

        return $res;
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

    /**
     * @return array
     */
    public function formatRequestProvider()
    {
        return [
            'existing product unit' => [
                'inputData' => [
                    'item'      => new QuoteProductRequest(),
                    'quantity'  => 15,
                    'unitCode'  => 'kg',
                    'price'     => OptionalPrice::create(10, 'USD'),
                    'unit'      => (new ProductUnit())->setCode('kg'),
                ],
                'expectedData' => [
                    'formatPrice'       => true,
                    'formatUnitValue'   => true,
                    'formatUnitLabel'   => true,
                    'formattedUnits'    => '15 kilogram',
                    'formattedPrice'    => '10.00 USD',
                    'formattedUnit'     => 'kilogram',
                    'formattedString'   => 'orob2b.sale.quoteproductrequest.item',
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
                    'formattedString'   => 'orob2b.sale.quoteproductrequest.item',
                ],
            ],
            'empty quantity' => [
                'inputData' => [
                    'item'      => new QuoteProductRequest(),
                    'quantity'  => null,
                    'unitCode'  => 'kg',
                    'price'     => OptionalPrice::create(10, 'USD'),
                    'unit'      => (new ProductUnit())->setCode('kg'),
                ],
                'expectedData' => [
                    'formatPrice'       => true,
                    'formatUnitValue'   => false,
                    'formatUnitLabel'   => true,
                    'formattedUnits'    => 'N/A',
                    'formattedPrice'    => '10.00 USD',
                    'formattedUnit'     => 'kilogram',
                    'formattedString'   => 'orob2b.sale.quoteproductrequest.item',
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
                    'price'     => OptionalPrice::create(20, 'EUR'),
                    'unit'      => null,
                ],
                'expectedData' => [
                    'formatPrice'       => true,
                    'formatUnitValue'   => false,
                    'formatUnitLabel'   => true,
                    'formattedUnits'    => '25 item',
                    'formattedPrice'    => '20.00 EUR',
                    'formattedUnit'     => 'item',
                    'formattedString'   => 'orob2b.sale.quoteproductrequest.item',
                ],
            ],
        ];
    }

    /**
     * @return array
     */
    public function formatOfferProvider()
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
                    'transConstant'     => 'orob2b.sale.quoteproductoffer.item_bundled',
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
                    'transConstant'     => 'orob2b.sale.quoteproductoffer.item',
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
                    'transConstant'     => 'orob2b.sale.quoteproductoffer.item',
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
                    'transConstant'     => 'orob2b.sale.quoteproductoffer.item',
                    'transIndex'        => 0,
                ],
            ],
        ];
    }
}
