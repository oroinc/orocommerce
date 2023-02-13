<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\Helper;

use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Helper\CheckoutLineItemGroupingInvalidationHelper;
use Oro\Bundle\CheckoutBundle\Provider\MultiShipping\ConfigProvider;
use Oro\Bundle\CheckoutBundle\Provider\MultiShipping\GroupedCheckoutLineItemsProvider;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Component\Testing\ReflectionUtil;

class CheckoutLineItemGroupingInvalidationHelperTest extends \PHPUnit\Framework\TestCase
{
    /** @var ConfigProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $multiShippingConfigProvider;

    /** @var GroupedCheckoutLineItemsProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $groupedCheckoutLineItemsProvider;

    /** @var CheckoutLineItemGroupingInvalidationHelper */
    private $helper;

    protected function setUp(): void
    {
        $this->multiShippingConfigProvider = $this->createMock(ConfigProvider::class);
        $this->groupedCheckoutLineItemsProvider = $this->createMock(GroupedCheckoutLineItemsProvider::class);

        $this->helper = new CheckoutLineItemGroupingInvalidationHelper(
            $this->multiShippingConfigProvider,
            $this->groupedCheckoutLineItemsProvider
        );
    }

    private function getWorkflowItem(int $id, \DateTime $updated): WorkflowItem
    {
        $workflowItem = new WorkflowItem();
        $workflowItem->setId($id);
        ReflectionUtil::setPropertyValue($workflowItem, 'updated', $updated);

        return $workflowItem;
    }

    public function testShouldInvalidateLineItemGrouping(): void
    {
        $this->multiShippingConfigProvider->expects($this->once())
            ->method('isLineItemsGroupingEnabled')
            ->willReturn(true);

        $workflowItem = $this->getWorkflowItem(1, new \DateTime('-24 hours', new \DateTimeZone('UTC')));

        $this->assertTrue($this->helper->shouldInvalidateLineItemGrouping($workflowItem));
    }

    public function testShouldNotInvalidateLineItemGrouping(): void
    {
        $this->multiShippingConfigProvider->expects($this->once())
            ->method('isLineItemsGroupingEnabled')
            ->willReturn(true);

        $workflowItem = $this->getWorkflowItem(1, new \DateTime('-23 hours', new \DateTimeZone('UTC')));

        $this->assertFalse($this->helper->shouldInvalidateLineItemGrouping($workflowItem));
    }

    public function testShouldInvalidateLineItemGroupingWithGroupingDisabled(): void
    {
        $this->multiShippingConfigProvider->expects($this->once())
            ->method('isLineItemsGroupingEnabled')
            ->willReturn(false);

        $workflowItem = $this->getWorkflowItem(1, new \DateTime('-24 hours', new \DateTimeZone('UTC')));

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
        $dateDifference =
            $workflowItem->getUpdated()->getTimestamp()
            - (new \DateTime('now', new \DateTimeZone('UTC')))->getTimestamp();

        $this->assertTrue(abs($dateDifference) < 60);
    }
}
