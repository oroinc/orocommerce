<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\Splitter\MultiShipping;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Entity\CheckoutLineItem;
use Oro\Bundle\CheckoutBundle\Factory\MultiShipping\CheckoutFactoryInterface;
use Oro\Bundle\CheckoutBundle\Splitter\MultiShipping\CheckoutSplitter;
use Oro\Component\Testing\ReflectionUtil;
use Oro\Component\Testing\Unit\EntityTrait;
use PHPUnit\Framework\TestCase;

class CheckoutSplitterTest extends TestCase
{
    use EntityTrait;

    private CheckoutFactoryInterface $checkoutFactory;
    private CheckoutSplitter $checkoutSplitter;

    protected function setUp(): void
    {
        $this->checkoutFactory = $this->createMock(CheckoutFactoryInterface::class);
        $this->checkoutSplitter = new CheckoutSplitter($this->checkoutFactory);
    }

    public function testSplit()
    {
        $lineItem1 = new CheckoutLineItem();
        ReflectionUtil::setId($lineItem1, 1);

        $lineItem2 = new CheckoutLineItem();
        ReflectionUtil::setId($lineItem2, 2);

        $lineItem3 = new CheckoutLineItem();
        ReflectionUtil::setId($lineItem3, 3);

        $groupedLineItems = [
            'product.owner:1' => [$lineItem1, $lineItem3],
            'product.owner:2' => [$lineItem2]
        ];

        $checkout1 = new Checkout();
        $checkout1->setLineItems(new ArrayCollection([$lineItem1, $lineItem3]));

        $checkout2 = new Checkout();
        $checkout2->setLineItems(new ArrayCollection([$lineItem2]));

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
