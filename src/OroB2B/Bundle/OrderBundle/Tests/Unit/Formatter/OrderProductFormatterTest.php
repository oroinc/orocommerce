<?php

namespace OroB2B\Bundle\OrderBundle\Tests\Unit\Formatter;

use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\CurrencyBundle\Model\Price;
use Oro\Bundle\LocaleBundle\Formatter\NumberFormatter;

use OroB2B\Bundle\ProductBundle\Entity\ProductUnit;

use OroB2B\Bundle\ProductBundle\Formatter\ProductUnitValueFormatter;
use OroB2B\Bundle\ProductBundle\Formatter\ProductUnitLabelFormatter;

use OroB2B\Bundle\OrderBundle\Entity\OrderProductItem;
use OroB2B\Bundle\OrderBundle\Formatter\OrderProductFormatter;

class OrderProductFormatterTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var OrderProductFormatter
     */
    protected $formatter;

    /**
     * @var TranslatorInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $translator;

   /**
     * @var ProductUnitValueFormatter|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $productUnitValueFormatter;

    /**
     * @var ProductUnitLabelFormatter|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $productUnitLabelFormatter;

    /**
     * @var NumberFormatter|\PHPUnit_Framework_MockObject_MockObject
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

        $this->formatter = new OrderProductFormatter(
            $this->translator,
            $this->numberFormatter,
            $this->productUnitValueFormatter,
            $this->productUnitLabelFormatter
        );
    }

    /**
     * @param array $inputData
     * @param array $expectedData
     *
     * @dataProvider formatItemProvider
     */
    public function testFormatItem(array $inputData, array $expectedData)
    {
        /* @var $item OrderProductItem */
        $item = $inputData['item'];

        $item
            ->setQuantity($inputData['quantity'])
            ->setProductUnit($inputData['unit'])
            ->setProductUnitCode($inputData['unitCode'])
            ->setPrice($inputData['price'])
        ;

        $this->productUnitValueFormatter
            ->expects($inputData['unit'] && $inputData['quantity'] ? $this->once() : $this->never())
            ->method('format')
            ->with($inputData['quantity'], $inputData['unitCode'])
            ->will($this->returnValue($expectedData['formattedUnits']))
        ;

        $this->productUnitLabelFormatter->expects($this->once())
            ->method('format')
            ->with($inputData['unitCode'])
            ->will($this->returnValue($expectedData['formattedUnit']))
        ;

        $price = $inputData['price'] ?: new Price();

        $this->numberFormatter->expects($inputData['price'] ? $this->once() : $this->never())
            ->method('formatCurrency')
            ->with($price->getValue(), $price->getCurrency())
            ->will($this->returnValue($expectedData['formattedPrice']))
        ;

        $this->translator->expects($this->once())
            ->method('trans')
            ->with($expectedData['transConstant'], [
                '{units}'   => $expectedData['formattedUnits'],
                '{price}'   => $expectedData['formattedPrice'],
                '{unit}'    => $expectedData['formattedUnit'],
            ])
        ;

        $this->formatter->formatItem($inputData['item']);
    }

    /**
     * @return array
     */
    public function formatItemProvider()
    {
        return [
            'existing product unit and bundled price type' => [
                'inputData' => [
                    'item'      => (new OrderProductItem())->setPriceType(OrderProductItem::PRICE_TYPE_BUNDLED),
                    'quantity'  => 16,
                    'unitCode'  => 'kg',
                    'price'     => Price::create(11, 'USD'),
                    'unit'      => (new ProductUnit())->setCode('kg'),
                ],
                'expectedData' => [
                    'formattedUnits'    => '16 kilogram',
                    'formattedPrice'    => '11.00 USD',
                    'formattedUnit'     => 'kilogram',
                    'transConstant'     => 'orob2b.order.orderproductitem.item_bundled',
                ],
            ],
            'existing product unit and default price type' => [
                'inputData' => [
                    'item'      => new OrderProductItem(),
                    'quantity'  => 17,
                    'unitCode'  => 'kg',
                    'price'     => Price::create(12, 'USD'),
                    'unit'      => (new ProductUnit())->setCode('kg'),
                ],
                'expectedData' => [
                    'formattedUnits'    => '17 kilogram',
                    'formattedPrice'    => '12.00 USD',
                    'formattedUnit'     => 'kilogram',
                    'transConstant'     => 'orob2b.order.orderproductitem.item',
                ],
            ],
            'defaults' => [
                'inputData' => [
                    'item'      => (new OrderProductItem())->setProductUnit(new ProductUnit()),
                    'quantity'  => null,
                    'unitCode'  => null,
                    'price'     => null,
                    'unit'      => (new ProductUnit())->setCode('kg'),
                ],
                'expectedData' => [
                    'formattedUnits'    => '',
                    'formattedPrice'    => '',
                    'formattedUnit'     => 'kilogram',
                    'transConstant'     => 'orob2b.order.orderproductitem.item',
                ],
            ],
            'deleted product unit' => [
                'inputData' => [
                    'item'      => new OrderProductItem(),
                    'quantity'  => 18,
                    'unitCode'  => 'item',
                    'price'     => Price::create(13, 'EUR'),
                    'unit'      => null,
                ],
                'expectedData' => [
                    'formattedUnits'    => '18 item',
                    'formattedPrice'    => '13.00 EUR',
                    'formattedUnit'     => 'item',
                    'transConstant'     => 'orob2b.order.orderproductitem.item',
                ],
            ],
        ];
    }
}
