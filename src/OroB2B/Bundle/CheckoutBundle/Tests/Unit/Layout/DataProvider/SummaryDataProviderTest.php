<?php

namespace OroB2B\Bundle\CheckoutBundle\Tests\Unit\Layout\DataProvider;

use Doctrine\Common\Collections\ArrayCollection;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Component\Layout\LayoutContext;
use Oro\Component\Testing\Unit\EntityTrait;

use OroB2B\Bundle\CheckoutBundle\DataProvider\Manager\CheckoutLineItemsManager;
use OroB2B\Bundle\CheckoutBundle\Layout\DataProvider\SummaryDataProvider;
use OroB2B\Bundle\PricingBundle\SubtotalProcessor\Model\Subtotal;
use OroB2B\Bundle\PricingBundle\SubtotalProcessor\Provider\LineItemSubtotalProvider;
use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\OrderBundle\Entity\OrderLineItem;

class SummaryDataProviderTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    /**
     * @var CheckoutLineItemsManager|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $checkoutLineItemsManager;

    /**
     * @var LineItemSubtotalProvider|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $lineItemsSubtotalProvider;

    /**
     * @var SummaryDataProvider
     */
    protected $provider;

    public function setUp()
    {
        $this->checkoutLineItemsManager = $this
            ->getMockBuilder('OroB2B\Bundle\CheckoutBundle\DataProvider\Manager\CheckoutLineItemsManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->lineItemsSubtotalProvider = $this
            ->getMockBuilder('OroB2B\Bundle\PricingBundle\SubtotalProcessor\Provider\LineItemSubtotalProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $this->provider = new SummaryDataProvider(
            $this->checkoutLineItemsManager,
            $this->lineItemsSubtotalProvider
        );
    }

    /**
     * @expectedException \BadMethodCallException
     */
    public function testGetIdentifier()
    {
        $this->provider->getIdentifier();
    }

    /**
     * @dataProvider getDataDataProvider
     * @param ArrayCollection $LineItems
     * @param array $expected
     */
    public function testGetData(ArrayCollection $LineItems, array $expected)
    {
        $checkout = $this->getEntity('OroB2B\Bundle\CheckoutBundle\Entity\Checkout', ['id' => 42]);

        $this->checkoutLineItemsManager->expects($this->once())
            ->method('getData')
            ->with($checkout)
            ->willReturn($LineItems);

        $generalTotal = new Subtotal();
        $generalTotal->setAmount('600');
        $generalTotal->setCurrency('USD');

        $this->lineItemsSubtotalProvider->expects($this->once())
            ->method('getSubtotal')
            ->willReturn($generalTotal);

        $lineItemTotals = $expected['lineItemTotals'];
        for ($i = 0; $i < count($expected['lineItemTotals']); $i++) {
            /** @var Price $total */
            $total = array_shift($lineItemTotals);
            $this->lineItemsSubtotalProvider->expects($this->at($i))
                ->method('getRowTotal')
                ->willReturn($total->getValue());
        }

        $context = new LayoutContext();
        $context->data()->set('checkout', null, $checkout);

        $result = $this->provider->getData($context);
        $this->assertEquals($expected, $result);
    }

    /**
     * @return array
     */
    public function getDataDataProvider()
    {
        $product1 = (new Product())->setSku('productSku01');
        $product2 = (new Product())->setSku('productSku02');

        $lineItem1 = new OrderLineItem();
        $lineItem1->setProduct($product1);
        $lineItem1->setProductSku($product1->getSku());
        $lineItem1->setCurrency('USD');

        $lineItem2 = new OrderLineItem();
        $lineItem2->setProduct($product2);
        $lineItem2->setProductSku($product2->getSku());
        $lineItem2->setCurrency('USD');

        $LineItems = new ArrayCollection();
        $LineItems->add($lineItem1);
        $LineItems->add($lineItem2);

        $lineItem1Total = new Price();
        $lineItem1Total->setValue(500);
        $lineItem1Total->setCurrency('USD');

        $lineItem2Total = new Price();
        $lineItem2Total->setValue(100);
        $lineItem2Total->setCurrency('USD');

        $totalPrice = new Price();
        $totalPriceValue = (float)$lineItem1Total->getValue() + (float)$lineItem2Total->getValue();
        $totalPrice->setValue($totalPriceValue);
        $totalPrice->setCurrency('USD');

        return [
            [
                'LineItems' => $LineItems,

                'expected' => [
                    'lineItemTotals' => [
                        $product1->getSku() => $lineItem1Total,
                        $product2->getSku() => $lineItem2Total,
                    ],
                    'lineItems' => $LineItems,
                    'lineItemsCount' => 2,
                    'totalPrice' => $totalPrice
                ]
            ]
        ];
    }
}
