<?php

namespace OroB2B\Bundle\SaleBundle\Tests\Unit\Twig;

use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\CurrencyBundle\Model\Price;
use Oro\Bundle\LocaleBundle\Formatter\NumberFormatter;

use OroB2B\Bundle\ProductBundle\Entity\ProductUnit;
use OroB2B\Bundle\ProductBundle\Formatter\ProductUnitValueFormatter;

use OroB2B\Bundle\SaleBundle\Twig\QuoteExtension;
use OroB2B\Bundle\SaleBundle\Entity\QuoteProductOffer;

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

        $this->translator
            ->expects($this->any())
            ->method('trans')
            ->will($this->returnCallback(function ($id, $params) {
                $ids = [
                    'orob2b.product_unit.kg.label.full'     => 'kilogram',
                    'orob2b.product_unit.item.label.full'   => 'item',
                    'orob2b.sale.quoteproductoffer.item'     => '{units}, {price} per {unit}',
                ];

                return str_replace(array_keys($params), array_values($params), $ids[$id]);
            }))
        ;

        $this->numberFormatter = $this->getMockBuilder('Oro\Bundle\LocaleBundle\Formatter\NumberFormatter')
            ->disableOriginalConstructor()
            ->getMock();

        $this->numberFormatter
            ->expects($this->any())
            ->method('formatCurrency')
            ->will($this->returnCallback(function ($value, $currency) {
                return sprintf('%01.2f %s', $value, $currency);
            }));

        $this->productUnitValueFormatter = $this->getMockBuilder(
            'OroB2B\Bundle\ProductBundle\Formatter\ProductUnitValueFormatter'
        )
            ->disableOriginalConstructor()
            ->getMock();

        $this->productUnitValueFormatter
            ->expects($this->any())
            ->method('format')
            ->will($this->returnCallback(function ($quantity, ProductUnit $productUnit) {
                $code = $this->translator->trans('orob2b.product_unit.' . $productUnit->getCode() . '.label.full');
                return sprintf('%d %s', $quantity, $code);
            }));

        $this->extension = new QuoteExtension(
            $this->translator,
            $this->numberFormatter,
            $this->productUnitValueFormatter
        );
    }

    public function testGetFilters()
    {
        /* @var $filters \Twig_SimpleFilter[] */
        $filters = $this->extension->getFilters();

        $this->assertCount(2, $filters);

        $this->assertInstanceOf('Twig_SimpleFilter', $filters[0]);
        $this->assertEquals('orob2b_format_sale_quote_product_offer', $filters[0]->getName());

        $this->assertInstanceOf('Twig_SimpleFilter', $filters[1]);
        $this->assertEquals('orob2b_format_sale_quote_product_request', $filters[1]->getName());
    }

    /**
     * @param string $expected
     * @param int $quantity
     * @param string $unitCode
     * @param Price $price
     * @param ProductUnit $unit
     * @dataProvider formatProductItemProvider
     */
    public function testFormatProductItem($expected, $quantity, $unitCode, Price $price, ProductUnit $unit = null)
    {
        $item = new QuoteProductOffer();
        $item
            ->setQuantity($quantity)
            ->setProductUnit($unit)
            ->setProductUnitCode($unitCode)
            ->setPrice($price)
        ;

        $this->assertEquals($expected, $this->extension->formatProductItem($item));
    }

    public function testGetName()
    {
        $this->assertEquals(QuoteExtension::NAME, $this->extension->getName());
    }

    /**
     * @return array
     */
    public function formatProductItemProvider()
    {
        return [
            'existed product unit' => [
                'expectedResult'    => '15 kilogram, 10.00 USD per kilogram',
                'quantity'          => 15,
                'unitCode'          => 'kg',
                'price'             => Price::create(10, 'USD'),
                'productUnit'       => (new ProductUnit())->setCode('kg'),
            ],
            'deleted product unit' => [
                'expectedResult'    => '25 item, 20.00 EUR per item',
                'quantity'          => 25,
                'unitCode'          => 'item',
                'price'             => Price::create(20, 'EUR'),
                'productUnit'       => null,
            ],
        ];
    }
}
