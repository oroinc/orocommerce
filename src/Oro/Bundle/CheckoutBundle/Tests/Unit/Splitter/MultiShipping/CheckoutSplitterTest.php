<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\Splitter\MultiShipping;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Entity\CheckoutLineItem;
use Oro\Bundle\CheckoutBundle\Factory\MultiShipping\CheckoutFactoryInterface;
use Oro\Bundle\CheckoutBundle\Splitter\MultiShipping\CheckoutSplitter;
use Oro\Component\Testing\ReflectionUtil;

class CheckoutSplitterTest extends \PHPUnit\Framework\TestCase
{
    /** @var CheckoutFactoryInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $checkoutFactory;

    /** @var CheckoutSplitter */
    private $checkoutSplitter;

    protected function setUp(): void
    {
        $this->checkoutFactory = $this->createMock(CheckoutFactoryInterface::class);

        $this->checkoutSplitter = new CheckoutSplitter($this->checkoutFactory);
    }

    private function getCheckout(array $lineItems): Checkout
    {
        $checkout = new Checkout();
        $checkout->setLineItems(new ArrayCollection($lineItems));

        return $checkout;
    }

    private function getCheckoutLineItem(int $id): CheckoutLineItem
    {
        $lineItem = new CheckoutLineItem();
        ReflectionUtil::setId($lineItem, $id);

        return $lineItem;
    }

    public function testSplit()
    {
        $lineItem1 = $this->getCheckoutLineItem(1);
        $lineItem2 = $this->getCheckoutLineItem(2);
        $lineItem3 = $this->getCheckoutLineItem(3);

        $groupedLineItems = [
            'product.owner:1' => [$lineItem1, $lineItem3],
            'product.owner:2' => [$lineItem2]
        ];

        $checkout1 = $this->getCheckout([$lineItem1, $lineItem3]);
        $checkout2 = $this->getCheckout([$lineItem2]);

        $this->checkoutFactory->expects($this->exactly(2))
            ->method('createCheckout')
            ->willReturnOnConsecutiveCalls($checkout1, $checkout2);

        $result = $this->checkoutSplitter->split(new Checkout(), $groupedLineItems);

        $this->assertNotEmpty($result);
        $this->assertCount(2, $result);

        $this->assertArrayHasKey('product.owner:1', $result);
        $this->assertArrayHasKey('product.owner:2', $result);

        $resultCheckout1 = $result['product.owner:1'];
        $this->assertInstanceOf(Checkout::class, $resultCheckout1);
        $this->assertCount(2, $resultCheckout1->getLineItems());

        $resultCheckout2 = $result['product.owner:2'];
        $this->assertInstanceOf(Checkout::class, $resultCheckout2);
        $this->assertCount(1, $resultCheckout2->getLineItems());
    }
}
