<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\Layout\DataProvider;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\CheckoutBundle\DataProvider\Manager\CheckoutLineItemsManager;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Layout\DataProvider\LineItemsWithTotalsProvider;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Provider\LineItemSubtotalProvider;
use Oro\Bundle\ProductBundle\Tests\Unit\Entity\Stub\Product;
use Oro\Component\Testing\Unit\EntityTrait;

class LineItemsWithTotalsProviderTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /** @var CheckoutLineItemsManager|\PHPUnit\Framework\MockObject\MockObject */
    private $checkoutLineItemsManager;

    /** @var LineItemSubtotalProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $lineItemSubtotalProvider;

    /** @var LineItemsWithTotalsProvider */
    private $provider;

    protected function setUp(): void
    {
        $this->checkoutLineItemsManager = $this->createMock(CheckoutLineItemsManager::class);
        $this->lineItemSubtotalProvider = $this->createMock(LineItemSubtotalProvider::class);

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

        /** @var Checkout $checkout */
        $checkout = $this->getEntity(Checkout::class, ['id' => 42]);

        $this->checkoutLineItemsManager->expects($this->once())
            ->method('getData')
            ->with($checkout)
            ->willReturn(new ArrayCollection([$lineItem1, $lineItem2]));

        $this->lineItemSubtotalProvider->expects($this->exactly(2))
            ->method('getRowTotal')
            ->withConsecutive(
                [$lineItem1, $lineItem1->getCurrency()],
                [$lineItem2, $lineItem2->getCurrency()]
            )
            ->willReturnOnConsecutiveCalls(
                $lineItem1Total,
                $lineItem2Total
            );

        $result = $this->provider->getData($checkout);
        $this->assertEquals($lineItems, $result);
    }
}
