<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\Layout\DataProvider;

use Doctrine\Common\Collections\ArrayCollection;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Component\Layout\LayoutContext;
use Oro\Component\Testing\Unit\EntityTrait;
use Oro\Bundle\CheckoutBundle\DataProvider\Manager\CheckoutLineItemsManager;
use Oro\Bundle\CheckoutBundle\Layout\DataProvider\LineItemsWithTotalsProvider;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Provider\LineItemSubtotalProvider;
use Oro\Bundle\ProductBundle\Tests\Unit\Entity\Stub\Product;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;

class LineItemsWithTotalsProviderTest extends \PHPUnit_Framework_TestCase
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
     * @var LineItemsWithTotalsProvider
     */
    protected $provider;

    public function setUp()
    {
        $this->checkoutLineItemsManager = $this
            ->getMockBuilder('Oro\Bundle\CheckoutBundle\DataProvider\Manager\CheckoutLineItemsManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->lineItemSubtotalProvider = $this
            ->getMockBuilder('Oro\Bundle\PricingBundle\SubtotalProcessor\Provider\LineItemSubtotalProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $this->provider = new LineItemsWithTotalsProvider(
            $this->checkoutLineItemsManager,
            $this->lineItemSubtotalProvider
        );
    }

    public function testGetData()
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

        $checkout = $this->getEntity('Oro\Bundle\CheckoutBundle\Entity\Checkout', ['id' => 42]);

        $this->checkoutLineItemsManager->expects($this->once())
            ->method('getData')
            ->with($checkout)
            ->willReturn(new ArrayCollection(iterator_to_array($lineItems)));

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

        $result = $this->provider->getData($checkout);
        $this->assertEquals($lineItems, $result);
    }
}
