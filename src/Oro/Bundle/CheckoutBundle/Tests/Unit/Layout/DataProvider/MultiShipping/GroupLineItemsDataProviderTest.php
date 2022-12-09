<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\Layout\DataProvider\MultiShipping;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Entity\CheckoutLineItem;
use Oro\Bundle\CheckoutBundle\Layout\DataProvider\MultiShipping\GroupLineItemsDataProvider;
use Oro\Bundle\CheckoutBundle\Provider\MultiShipping\ConfigProvider;
use Oro\Bundle\CheckoutBundle\Provider\MultiShipping\GroupedCheckoutLineItemsProvider;
use Oro\Bundle\CheckoutBundle\Provider\MultiShipping\LineItemGroupTitleProvider;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Model\WorkflowData;
use Oro\Component\Testing\Unit\EntityTrait;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\PropertyAccess\Exception\NoSuchPropertyException;

class GroupLineItemsDataProviderTest extends TestCase
{
    use EntityTrait;

    private LineItemGroupTitleProvider|MockObject $titleProvider;
    private ConfigProvider|MockObject $configProvider;
    private GroupedCheckoutLineItemsProvider|MockObject $groupedLineItemsProvider;
    private GroupLineItemsDataProvider $groupLineItemsDataProvider;

    protected function setUp(): void
    {
        $this->titleProvider = $this->createMock(LineItemGroupTitleProvider::class);
        $this->configProvider = $this->createMock(ConfigProvider::class);
        $this->groupedLineItemsProvider = $this->createMock(GroupedCheckoutLineItemsProvider::class);

        $this->groupLineItemsDataProvider = new GroupLineItemsDataProvider(
            $this->titleProvider,
            $this->configProvider,
            $this->groupedLineItemsProvider
        );
    }

    public function testGetGroupedLineItemsWithGroupedLineItemsInWorkflowData()
    {
        $groupedLineItems = $this->getGroupedLineItems();

        $checkout = new Checkout();
        $lineItems = array_merge(...array_values($groupedLineItems));
        $checkout->setLineItems(new ArrayCollection($lineItems));

        $groupedLineItemsIds = [
            'product.owner:1' => ['sku-1:item','sku-3:item'],
            'product.owner:2' => ['sku-2:set']
        ];

        $workflowItem = $this->createWorkflowItem($groupedLineItemsIds);

        $this->groupedLineItemsProvider->expects($this->once())
            ->method('getGroupedLineItemsByIds')
            ->with($checkout, $groupedLineItemsIds)
            ->willReturn($groupedLineItems);

        $result = $this->groupLineItemsDataProvider->getGroupedLineItems($workflowItem, $checkout);

        $expectedResult = [
            'product.owner:1' => [1, 3],
            'product.owner:2' => [2]
        ];
        $this->assertEquals($expectedResult, $result);
    }

    public function testGetGroupedLineItemsWhenEmptyWorkflowData()
    {
        $groupedLineItems = $this->getGroupedLineItems();

        $checkout = new Checkout();
        $lineItems = array_merge(...array_values($groupedLineItems));
        $checkout->setLineItems(new ArrayCollection($lineItems));

        $workflowItem = new WorkflowItem();
        $workflowItem->setData(new WorkflowData());

        $this->groupedLineItemsProvider->expects($this->once())
            ->method('getGroupedLineItems')
            ->with($checkout)
            ->willReturn($groupedLineItems);

        $result = $this->groupLineItemsDataProvider->getGroupedLineItems($workflowItem, $checkout);

        $expectedResult = [
            'product.owner:1' => [1, 3],
            'product.owner:2' => [2]
        ];

        $this->assertEquals($expectedResult, $result);
    }

    public function testGetGroupedLineItemsTitles()
    {
        $groupedLineItems = $this->getGroupedLineItems();

        $checkout = new Checkout();
        $lineItems = array_merge(...array_values($groupedLineItems));
        $checkout->setLineItems(new ArrayCollection($lineItems));

        $groupedLineItemsIds = [
            'product.owner:1' => ['sku-1:item','sku-3:item'],
            'product.owner:2' => ['sku-2:set']
        ];

        $workflowItem = $this->createWorkflowItem($groupedLineItemsIds);

        $this->groupedLineItemsProvider->expects($this->once())
            ->method('getGroupedLineItemsByIds')
            ->with($checkout, $groupedLineItemsIds)
            ->willReturn($groupedLineItems);

        $this->titleProvider->expects($this->exactly(2))
            ->method('getTitle')
            ->will($this->onConsecutiveCalls(
                'Owner Title 1',
                'Owner Title 2'
            ));

        $expectedResult = [
            'product.owner:1' => 'Owner Title 1',
            'product.owner:2' => 'Owner Title 2',
        ];

        $result = $this->groupLineItemsDataProvider->getGroupedLineItemsTitles($workflowItem, $checkout);
        $this->assertEquals($expectedResult, $result);
    }

    public function testGetGroupedLineItemsTitlesWithException()
    {
        $groupedLineItems = $this->getGroupedLineItems();

        $checkout = new Checkout();
        $lineItems = array_merge(...array_values($groupedLineItems));
        $checkout->setLineItems(new ArrayCollection($lineItems));

        $groupedLineItemsIds = [
            'product.owner:1' => ['sku-1:item','sku-3:item'],
            'product.owner:2' => ['sku-2:set']
        ];

        $workflowItem = $this->createWorkflowItem($groupedLineItemsIds);

        $this->groupedLineItemsProvider->expects($this->once())
            ->method('getGroupedLineItemsByIds')
            ->with($checkout, $groupedLineItemsIds)
            ->willReturn($groupedLineItems);

        $this->titleProvider->expects($this->exactly(2))
            ->method('getTitle')
            ->willThrowException(new NoSuchPropertyException());

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Unable to get title for the checkout line items group');

        $this->groupLineItemsDataProvider->getGroupedLineItemsTitles($workflowItem, $checkout);
    }

    private function getGroupedLineItems(): array
    {
        $lineItem1 = $this->getEntity(CheckoutLineItem::class, [
            'id' => 1,
            'productSku' => 'sku-1',
            'productUnitCode' => 'item'
        ]);

        $lineItem2 = $this->getEntity(CheckoutLineItem::class, [
            'id' => 2,
            'productSku' => 'sku-2',
            'productUnitCode' => 'item'
        ]);

        $lineItem3 = $this->getEntity(CheckoutLineItem::class, [
            'id' => 3,
            'productSku' => 'sku-3',
            'productUnitCode' => 'item'
        ]);

        return [
            'product.owner:1' => [$lineItem1, $lineItem3],
            'product.owner:2' => [$lineItem2]
        ];
    }

    private function createWorkflowItem(array $groupedLineItems): WorkflowItem
    {
        $workflowItem = new WorkflowItem();
        $workflowData = new WorkflowData();

        $workflowData->set('grouped_line_items', $groupedLineItems);
        $workflowItem->setData($workflowData);

        return $workflowItem;
    }
}
