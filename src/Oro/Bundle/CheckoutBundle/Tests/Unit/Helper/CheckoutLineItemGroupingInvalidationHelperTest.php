<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\Helper;

use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Helper\CheckoutLineItemGroupingInvalidationHelper;
use Oro\Bundle\CheckoutBundle\Provider\MultiShipping\ConfigProvider;
use Oro\Bundle\CheckoutBundle\Provider\MultiShipping\GroupedCheckoutLineItemsProvider;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Component\Testing\Unit\EntityTrait;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CheckoutLineItemGroupingInvalidationHelperTest extends TestCase
{
    use EntityTrait;

    private ConfigProvider|MockObject $multiShippingConfigProvider;
    private GroupedCheckoutLineItemsProvider|MockObject $groupedCheckoutLineItemsProvider;
    private CheckoutLineItemGroupingInvalidationHelper $helper;

    protected function setUp(): void
    {
        $this->multiShippingConfigProvider = $this->createMock(ConfigProvider::class);
        $this->groupedCheckoutLineItemsProvider = $this->createMock(GroupedCheckoutLineItemsProvider::class);

        $this->helper = new CheckoutLineItemGroupingInvalidationHelper(
            $this->multiShippingConfigProvider,
            $this->groupedCheckoutLineItemsProvider
        );
    }

    public function testShouldInvalidateLineItemGrouping(): void
    {
        $this->multiShippingConfigProvider->expects($this->once())
            ->method('isLineItemsGroupingEnabled')
            ->willReturn(true);

        /** @var WorkflowItem $workflowItem */
        $workflowItem = $this->getEntity(WorkflowItem::class, [
            'id' => 1,
            'updated' => new \DateTime('-24 hours', new \DateTimeZone('UTC')),
        ]);

        $this->assertTrue($this->helper->shouldInvalidateLineItemGrouping($workflowItem));
    }

    public function testShouldNotInvalidateLineItemGrouping(): void
    {
        $this->multiShippingConfigProvider->expects($this->once())
            ->method('isLineItemsGroupingEnabled')
            ->willReturn(true);

        /** @var WorkflowItem $workflowItem */
        $workflowItem = $this->getEntity(WorkflowItem::class, [
            'id' => 1,
            'updated' => new \DateTime('-23 hours', new \DateTimeZone('UTC')),
        ]);

        $this->assertFalse($this->helper->shouldInvalidateLineItemGrouping($workflowItem));
    }

    public function testShouldInvalidateLineItemGroupingWithGroupingDisabled(): void
    {
        $this->multiShippingConfigProvider->expects($this->once())
            ->method('isLineItemsGroupingEnabled')
            ->willReturn(false);

        /** @var WorkflowItem $workflowItem */
        $workflowItem = $this->getEntity(WorkflowItem::class, [
            'id' => 1,
            'updated' => new \DateTime('-24 hours', new \DateTimeZone('UTC')),
        ]);

        $this->assertFalse($this->helper->shouldInvalidateLineItemGrouping($workflowItem));
    }

    public function testInvalidateLineItemGrouping()
    {
        $workflowItem = new WorkflowItem();
        $checkout = new Checkout();

        $groupedLineItemsIds = [
            'product.owner:1' => ['sku-1:item','sku-2:item'],
            'product.owner:2' => ['sku-4:set','sku-5:item'],
        ];

        $this->groupedCheckoutLineItemsProvider->expects($this->once())
            ->method('getGroupedLineItemsIds')
            ->willReturn($groupedLineItemsIds);

        $this->assertNull($workflowItem->getUpdated());

        $this->helper->invalidateLineItemGrouping($checkout, $workflowItem);

        $this->assertSame(
            $groupedLineItemsIds,
            $workflowItem->getData()->offsetGet('grouped_line_items')
        );

        // Asserting that $workflowItem updated at date roughly equals to current time (with 60 seconds delta)
        $dateDifference = $workflowItem->getUpdated()->getTimestamp() -
            (new \DateTime('now', new \DateTimeZone('UTC')))->getTimestamp();

        $this->assertTrue(abs($dateDifference) < 60);
    }
}
