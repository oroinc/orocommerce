<?php

namespace OroB2B\Bundle\RFPAdminBundle\Tests\Unit\Twig;

use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\CurrencyBundle\Model\Price;
use Oro\Bundle\LocaleBundle\Formatter\NumberFormatter;

use OroB2B\Bundle\ProductBundle\Entity\ProductUnit;
use OroB2B\Bundle\ProductBundle\Formatter\ProductUnitValueFormatter;
use OroB2B\Bundle\ProductBundle\Formatter\ProductUnitLabelFormatter;

use OroB2B\Bundle\RFPAdminBundle\Twig\RequestExtension;
use OroB2B\Bundle\RFPAdminBundle\Entity\RequestProductItem;

class RequestExtensionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var RequestExtension
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

        $this->numberFormatter = $this->getMockBuilder('Oro\Bundle\LocaleBundle\Formatter\NumberFormatter')
            ->disableOriginalConstructor()
            ->getMock();

        $this->extension = new RequestExtension(
            $this->translator,
            $this->numberFormatter,
            $this->productUnitValueFormatter,
            $this->productUnitLabelFormatter
        );
    }

    public function testGetFilters()
    {
        /* @var $filters \Twig_SimpleFilter[] */
        $filters = $this->extension->getFilters();

        $this->assertCount(1, $filters);

        $this->assertArrayHasKey(0, $filters);
        $this->assertInstanceOf('Twig_SimpleFilter', $filters[0]);
        $this->assertEquals('orob2b_format_rfpadmin_request_product_item', $filters[0]->getName());
    }

    /**
     * @param int $quantity
     * @param string $unitCode
     * @param string $formattedUnits
     * @param Price $price
     * @param string $formattedPrice
     * @param string $formattedUnit
     * @param ProductUnit $unit
     *
     * @dataProvider formatProductItemProvider
     */
    public function testFormatProductItem(
        $quantity,
        $unitCode,
        $formattedUnits,
        Price $price,
        $formattedPrice,
        $formattedUnit,
        ProductUnit $unit = null
    ) {
        $item = new RequestProductItem();
        $item
            ->setQuantity($quantity)
            ->setProductUnit($unit)
            ->setProductUnitCode($unitCode)
            ->setPrice($price)
        ;

        $this->productUnitValueFormatter->expects($unit ? $this->once() : $this->never())
            ->method('format')
            ->with($quantity, $unitCode)
            ->will($this->returnValue($formattedUnits))
        ;

        $this->numberFormatter->expects($this->once())
            ->method('formatCurrency')
            ->with($price->getValue(), $price->getCurrency())
            ->will($this->returnValue($formattedPrice))
        ;

        $this->productUnitLabelFormatter->expects($this->once())
            ->method('format')
            ->with($unitCode)
            ->will($this->returnValue($formattedUnit))
        ;

        $this->translator->expects($this->once())
            ->method('trans')
            ->with('orob2b.rfpadmin.requestproductitem.item', [
                '{units}'   => $formattedUnits,
                '{price}'   => $formattedPrice,
                '{unit}'    => $formattedUnit,
            ])
        ;

        $this->extension->formatProductItem($item);
    }

    public function testGetName()
    {
        $this->assertEquals(RequestExtension::NAME, $this->extension->getName());
    }

    /**
     * @return array
     */
    public function formatProductItemProvider()
    {
        return [
            'existing product unit' => [
                'quantity'          => 15,
                'unitCode'          => 'kg',
                'formattedUnits'    => '15 kilogram',
                'price'             => Price::create(10, 'USD'),
                'formattedPrice'    => '10.00 USD',
                'formattedUnit'     => 'kilogram',
                'productUnit'       => (new ProductUnit())->setCode('kg'),
            ],
            'deleted product unit' => [
                'quantity'          => 25,
                'unitCode'          => 'item',
                'formattedUnits'    => '25 item',
                'price'             => Price::create(20, 'EUR'),
                'formattedPrice'    => '20.00 EUR',
                'formattedUnit'     => 'item',
                'productUnit'       => null,
            ],
        ];
    }
}
