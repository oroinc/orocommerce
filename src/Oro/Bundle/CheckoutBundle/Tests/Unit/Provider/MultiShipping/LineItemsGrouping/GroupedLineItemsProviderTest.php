<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\Provider\MultiShipping\LineItemsGrouping;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Entity\CheckoutLineItem;
use Oro\Bundle\CheckoutBundle\Provider\MultiShipping\LineItemsGrouping\GroupedLineItemsProvider;
use Oro\Bundle\ShippingBundle\Provider\GroupLineItemHelperInterface;

class GroupedLineItemsProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var GroupLineItemHelperInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $groupLineItemHelper;

    /** @var GroupedLineItemsProvider */
    private $groupedLineItemsProvider;

    protected function setUp(): void
    {
        $this->groupLineItemHelper = $this->createMock(GroupLineItemHelperInterface::class);

        $this->groupedLineItemsProvider = new GroupedLineItemsProvider($this->groupLineItemHelper);
    }

    public function testGetGroupedLineItems(): void
    {
        $checkout = new Checkout();
        $checkout->setLineItems(new ArrayCollection([new CheckoutLineItem()]));
        $groupingFieldPath = 'product.owner';
        $groupedLineItems = ['product.owner:1' => [new CheckoutLineItem()]];

        $this->groupLineItemHelper->expects($this->once())
            ->method('getGroupingFieldPath')
            ->willReturn($groupingFieldPath);
        $this->groupLineItemHelper->expects(self::once())
            ->method('getGroupedLineItems')
            ->with($checkout->getLineItems(), $groupingFieldPath)
            ->willReturn($groupedLineItems);

        self::assertSame($groupedLineItems, $this->groupedLineItemsProvider->getGroupedLineItems($checkout));
        // test memory cache
        self::assertSame($groupedLineItems, $this->groupedLineItemsProvider->getGroupedLineItems($checkout));
    }
}
