<?php

namespace OroB2B\Bundle\SaleBundle\Tests\Unit\Twig;

use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\CurrencyBundle\Model\Price;
use Oro\Bundle\CurrencyBundle\Model\OptionalPrice;
use Oro\Bundle\LocaleBundle\Formatter\NumberFormatter;

use OroB2B\Bundle\ProductBundle\Entity\ProductUnit;
use OroB2B\Bundle\ProductBundle\Formatter\ProductUnitValueFormatter;
use OroB2B\Bundle\ProductBundle\Formatter\ProductUnitLabelFormatter;

use OroB2B\Bundle\SaleBundle\Twig\QuoteExtension;
use OroB2B\Bundle\SaleBundle\Entity\QuoteProduct;
use OroB2B\Bundle\SaleBundle\Entity\QuoteProductOffer;
use OroB2B\Bundle\SaleBundle\Entity\QuoteProductRequest;
use OroB2B\Bundle\SaleBundle\Formatter\QuoteProductTypeFormatter;
/**
 * @SuppressWarnings(PHPMD.NPathComplexity)
 */
class QuoteExtensionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var QuoteExtension
     */
    protected $extension;

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
     * @var \PHPUnit_Framework_MockObject_MockObject|QuoteProductTypeFormatter
     */
    protected $quoteProductTypeFormatter;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|NumberFormatter
     */
    protected $numberFormatter;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->translator = $this->getMockBuilder('Symfony\Component\Translation\TranslatorInterface')
            ->disableOriginalConstructor()
            ->getMock()
        ;

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

        $this->quoteProductTypeFormatter = $this->getMockBuilder(
            'OroB2B\Bundle\SaleBundle\Formatter\QuoteProductTypeFormatter'
        )
            ->disableOriginalConstructor()
            ->getMock();

        $this->numberFormatter = $this->getMockBuilder('Oro\Bundle\LocaleBundle\Formatter\NumberFormatter')
            ->disableOriginalConstructor()
            ->getMock();

        $this->extension = new QuoteExtension(
            $this->translator,
            $this->numberFormatter,
            $this->productUnitValueFormatter,
            $this->productUnitLabelFormatter,
            $this->quoteProductTypeFormatter
        );
    }

    public function testGetFilters()
    {
        /* @var $filters \Twig_SimpleFilter[] */
        $filters = $this->extension->getFilters();

        $this->assertCount(3, $filters);

        $this->assertInstanceOf('Twig_SimpleFilter', $filters[0]);
        $this->assertEquals('orob2b_format_sale_quote_product_offer', $filters[0]->getName());

        $this->assertInstanceOf('Twig_SimpleFilter', $filters[1]);
        $this->assertEquals('orob2b_format_sale_quote_product_type', $filters[1]->getName());

        $this->assertInstanceOf('Twig_SimpleFilter', $filters[2]);
        $this->assertEquals('orob2b_format_sale_quote_product_request', $filters[2]->getName());
    }

    public function testGetName()
    {
        $this->assertEquals(QuoteExtension::NAME, $this->extension->getName());
    }

    /**
     * @param bool $valid
     * @param mixed $inputData
     * @param mixed $expectedData
     *
     * @dataProvider formatProductTypeProvider
     */
    public function testFormatProductType($valid, $inputData, $expectedData)
    {
        $this->quoteProductTypeFormatter->expects($valid ? $this->once() : $this->never())
            ->method('formatTypeLabel')
            ->with($expectedData)
        ;

        $this->translator->expects($valid ? $this->never() : $this->once())
            ->method('trans')
            ->with('N/A')
        ;

        $this->extension->formatProductType($inputData);
    }

    /**
     * @return array
     */
    public function formatProductTypeProvider()
    {
        $res = [
            'invalid type' => [
                'valid'     => false,
                'input'     => 'asdf',
                'expected'  => 'N/A',
            ],
        ];

        foreach (QuoteProduct::getTypes() as $key => $value) {
            $res[$value] = [
                'valid'     => true,
                'input'     => $key,
                'expected'  => $value,
            ];
        }

        return $res;
    }

    /**
     * @param array $inputData
     * @param array $expectedData
     *
     * @dataProvider formatProductOfferProvider
     */
    public function testFormatProductOffer(array $inputData, array $expectedData)
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

        $this->extension->formatProductOffer($inputData['item']);
    }

    /**
     * @param array $inputData
     * @param array $expectedData
     *
     * @dataProvider formatProductRequestProvider
     */
    public function testFormatProductRequest(array $inputData, array $expectedData)
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
            $this->extension->formatProductRequest($inputData['item'])
        );
    }

    /**
     * @return array
     */
    public function formatProductRequestProvider()
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
    public function formatProductOfferProvider()
    {
        return [
            'existing product unit and bundled price type' => [
                'inputData' => [
                    'item'      => (new QuoteProductOffer())->setPriceType(QuoteProductOffer::PRICE_TYPE_BUNDLED),
                    'quantity'  => 15,
                    'unitCode'  => 'kg',
                    'price'     => OptionalPrice::create(10, 'USD'),
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
                    'price'     => OptionalPrice::create(10, 'USD'),
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
                    'price'     => OptionalPrice::create(10, 'USD'),
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
                    'price'     => OptionalPrice::create(20, 'EUR'),
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
