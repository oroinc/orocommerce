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
use OroB2B\Bundle\PricingBundle\SubtotalProcessor\TotalProcessorProvider;
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
    protected $lineItemSubtotalProvider;

    /**
     * @var TotalProcessorProvider|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $totalProcessorProvider;

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

        $this->lineItemSubtotalProvider = $this
            ->getMockBuilder('OroB2B\Bundle\PricingBundle\SubtotalProcessor\Provider\LineItemSubtotalProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $this->totalProcessorProvider = $this
            ->getMockBuilder('OroB2B\Bundle\PricingBundle\SubtotalProcessor\TotalProcessorProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $this->provider = new SummaryDataProvider(
            $this->checkoutLineItemsManager,
            $this->lineItemSubtotalProvider,
            $this->totalProcessorProvider
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
     * @param \SplObjectStorage $lineItems
     * @param array $expected
     * @param Subtotal $totalPrice
     * @param Subtotal[] $subtotals
     */
    public function testGetData(\SplObjectStorage $lineItems, array $expected, Subtotal $totalPrice, array $subtotals)
    {
        $checkout = $this->getEntity('OroB2B\Bundle\CheckoutBundle\Entity\Checkout', ['id' => 42]);

        $this->checkoutLineItemsManager->expects($this->once())
            ->method('getData')
            ->with($checkout)
            ->willReturn(new ArrayCollection(iterator_to_array($lineItems)));

        $generalTotal = new Subtotal();
        $generalTotal->setAmount('600');
        $generalTotal->setCurrency('USD');

        $this->totalProcessorProvider
            ->expects($this->once())
            ->method('getTotal')
            ->willReturn($totalPrice);

        $this->totalProcessorProvider
            ->expects($this->once())
            ->method('getSubtotals')
            ->willReturn($subtotals);

        $this->totalProcessorProvider
            ->expects($this->once())
            ->method('enableRecalculation')
            ->willReturn($this->totalProcessorProvider);

        $i = 0;
        while ($lineItems->valid()) {
            $info = $lineItems->getInfo();
            /** @var Price $total */
            $total = $info['total'];
            $this->lineItemSubtotalProvider->expects($this->at($i))
                ->method('getRowTotal')
                ->willReturn($total->getValue());
            $i++;
            $lineItems->next();
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

        $lineItem1Total = new Price();
        $lineItem1Total->setValue(500);
        $lineItem1Total->setCurrency('USD');

        $lineItem2 = new OrderLineItem();
        $lineItem2->setProduct($product2);
        $lineItem2->setProductSku($product2->getSku());
        $lineItem2->setCurrency('USD');

        $lineItem2Total = new Price();
        $lineItem2Total->setValue(100);
        $lineItem2Total->setCurrency('USD');

        $lineItems = new \SplObjectStorage();
        $lineItems->attach($lineItem1, ['total' => $lineItem1Total]);
        $lineItems->attach($lineItem2, ['total' => $lineItem2Total]);

        $totalPrice = new Subtotal();
        $totalPriceValue = (float)$lineItem1Total->getValue() + (float)$lineItem2Total->getValue();
        $totalPrice->setAmount($totalPriceValue);
        $totalPrice->setCurrency('USD');

        return [
            [
                'LineItems' => $lineItems,

                'expected' => [
                    'lineItemsWithTotals' => $lineItems,
                    'generalTotal' => $totalPrice,
                    'subtotals' => [$totalPrice]
                ],
                'totalPrice' => $totalPrice,
                'subtotals' => [$totalPrice]
            ]
        ];
    }
}
