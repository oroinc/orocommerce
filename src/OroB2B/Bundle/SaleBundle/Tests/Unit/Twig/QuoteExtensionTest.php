<?php

namespace OroB2B\Bundle\SaleBundle\Tests\Unit\Twig;

use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\CurrencyBundle\Model\Price;
use OroB2B\Bundle\ProductBundle\Entity\ProductUnit;

use OroB2B\Bundle\SaleBundle\Twig\QuoteExtension;
use OroB2B\Bundle\SaleBundle\Entity\QuoteProductItem;

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
     * @var \PHPUnit_Framework_MockObject_MockObject|\Twig_Environment
     */
    protected $twigEnvironment;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject[]|\Twig_SimpleFilter[]
     */
    protected $twigFilters;

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
            ->method('trans')
            ->will($this->returnCallback(function ($id, $params) {
                $ids = [
                    'orob2b.product_unit.kg.label.full'     => 'kilogram',
                    'orob2b.product_unit.item.label.full'   => 'item',
                    'orob2b.sale.quoteproductitem.item'     => '{units}, {price} per {unit}',
                ];

                return str_replace(array_keys($params), array_values($params), $ids[$id]);
            }))
        ;

        $this->twigEnvironment = $this->getMockBuilder('\Twig_Environment')
            ->disableOriginalConstructor()
            ->getMock();

        $this->twigFilters['orob2b_format_product_unit_value'] = $this->getMockBuilder('\Twig_SimpleFilter')
            ->disableOriginalConstructor()
            ->getMock()
        ;
        $this->twigFilters['orob2b_format_product_unit_value']
            ->method('getCallable')
            ->will($this->returnValue(function ($value, ProductUnit $unit) {
                $code = $this->translator->trans('orob2b.product_unit.' . $unit->getCode() . '.label.full');
                return sprintf('%d %s', $value, $code);
            }))
        ;

        $this->twigFilters['oro_format_price'] = $this->getMockBuilder('\Twig_SimpleFilter')
            ->disableOriginalConstructor()
            ->getMock()
        ;
        $this->twigFilters['oro_format_price']
            ->method('getCallable')
            ->will($this->returnValue(function (Price $price, array $options = []) {
                return sprintf('%01.2f %s', $price->getValue(), $price->getCurrency());
            }))
        ;

        $this->twigEnvironment
            ->method('getFilter')
            ->will($this->returnCallback(function ($name) {
                return $this->twigFilters[$name];
            }))
        ;

        $this->extension = new QuoteExtension($this->translator, $this->twigEnvironment);
    }

    public function testGetFilters()
    {
        /* @var $filters \Twig_SimpleFilter[] */
        $filters = $this->extension->getFilters();

        $this->assertCount(1, $filters);

        $this->assertInstanceOf('Twig_SimpleFilter', $filters[0]);
        $this->assertEquals('orob2b_format_sale_quote_product_item', $filters[0]->getName());
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
        $item = new QuoteProductItem();
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
