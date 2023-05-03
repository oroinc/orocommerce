<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\Provider\MultiShipping;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Entity\CheckoutLineItem;
use Oro\Bundle\CheckoutBundle\Provider\MultiShipping\ConfigProvider;
use Oro\Bundle\CheckoutBundle\Provider\MultiShipping\GroupedCheckoutLineItemsProvider;
use Oro\Bundle\CheckoutBundle\Provider\MultiShipping\SplitCheckoutProvider;
use Oro\Bundle\CheckoutBundle\Splitter\MultiShipping\CheckoutSplitter;
use Oro\Bundle\WorkflowBundle\Entity\Repository\WorkflowItemRepository;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Model\WorkflowData;
use Oro\Component\Testing\ReflectionUtil;

class SplitCheckoutProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrine;

    /** @var CheckoutSplitter|\PHPUnit\Framework\MockObject\MockObject */
    private $checkoutSplitter;

    /** @var GroupedCheckoutLineItemsProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $groupedLineItemsProvider;

    /** @var ConfigProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $configProvider;

    /** @var SplitCheckoutProvider */
    private $provider;

    protected function setUp(): void
    {
        $this->doctrine = $this->createMock(ManagerRegistry::class);
        $this->checkoutSplitter = $this->createMock(CheckoutSplitter::class);
        $this->groupedLineItemsProvider = $this->createMock(GroupedCheckoutLineItemsProvider::class);
        $this->configProvider = $this->createMock(ConfigProvider::class);

        $this->provider = new SplitCheckoutProvider(
            $this->doctrine,
            $this->checkoutSplitter,
            $this->groupedLineItemsProvider,
            $this->configProvider
        );
    }

    private function getCheckout(int $id): Checkout
    {
        $checkout = new Checkout();
        ReflectionUtil::setId($checkout, $id);

        return $checkout;
    }

    private function getCheckoutLineItem(string $sku, string $unitCode): CheckoutLineItem
    {
        $lineItem = new CheckoutLineItem();
        $lineItem->setProductSku($sku);
        $lineItem->setProductUnitCode($unitCode);

        return $lineItem;
    }

    private function getWorkflowItem(
        bool $active,
        ?array $groupedLineItems = null,
        array $exclusiveGroups = ['b2b_checkout_flow']
    ): WorkflowItem {
        $workflowItem = new WorkflowItem();
        $workflowDefinition = new WorkflowDefinition();

        $workflowData = new WorkflowData();
        $workflowItem->setDefinition($workflowDefinition);
        $workflowItem->setData($workflowData);

        $workflowDefinition->setExclusiveRecordGroups($exclusiveGroups);
        $workflowDefinition->setActive($active);

        if (null !== $groupedLineItems) {
            $workflowData->set('grouped_line_items', $groupedLineItems);
        }

        return $workflowItem;
    }

    public function testGetSubCheckouts()
    {
        $lineItem1 = $this->getCheckoutLineItem('sku-1', 'item');
        $lineItem2 = $this->getCheckoutLineItem('sku-2', 'set');
        $lineItem3 = $this->getCheckoutLineItem('sku-3', 'item');

        $checkout = $this->getCheckout(1);
        $checkout->setLineItems(new ArrayCollection([$lineItem1, $lineItem2, $lineItem3]));

        $groupedLineItemsIds = [
            'product.owner:1' => ['sku-1:item','sku-3:item'],
            'product.owner:2' => ['sku-2:set']
        ];

        $groupedLineItems = [
            'product.owner:1' => [$lineItem1, $lineItem3],
            'product.owner:2' => [$lineItem2]
        ];

        $workflowItem = $this->getWorkflowItem(true, $groupedLineItemsIds);
        $repository = $this->createMock(WorkflowItemRepository::class);

        $this->doctrine->expects($this->once())
            ->method('getRepository')
            ->willReturn($repository);

        $repository->expects($this->once())
            ->method('findAllByEntityMetadata')
            ->willReturn([$workflowItem]);

        $this->groupedLineItemsProvider->expects($this->never())
            ->method('getGroupedLineItemsIds');

        $this->groupedLineItemsProvider->expects($this->once())
            ->method('getGroupedLineItemsByIds')
            ->willReturn($groupedLineItems);

        $this->checkoutSplitter->expects($this->once())
            ->method('split')
            ->willReturn([
                'product.owner:1' => (new Checkout())->setLineItems(new ArrayCollection([$lineItem1, $lineItem2])),
                'product.owner:2' => (new Checkout())->setLineItems(new ArrayCollection([$lineItem2]))
            ]);

        $this->configProvider->expects($this->once())
            ->method('isCreateSubOrdersForEachGroupEnabled')
            ->willReturn(true);

        $result = $this->provider->getSubCheckouts($checkout);

        $this->assertCount(2, $result);
        $this->assertArrayHasKey('product.owner:1', $result);
        $this->assertArrayHasKey('product.owner:2', $result);

        $subCheckout1 = $result['product.owner:1'];
        $subCheckout2 = $result['product.owner:2'];

        $this->assertCount(2, $subCheckout1->getLineItems());
        $this->assertCount(1, $subCheckout2->getLineItems());
    }

    /**
     * @dataProvider getDataForTestGetSubCheckoutWithWorkflowDisabledOrNotExists
     */
    public function testGetSubCheckoutWithWorkflowDisabledOrNotExists(array $workflowItems)
    {
        $checkout = $this->getCheckout(1);

        $repository = $this->createMock(WorkflowItemRepository::class);

        $this->doctrine->expects($this->once())
            ->method('getRepository')
            ->willReturn($repository);

        $repository->expects($this->once())
            ->method('findAllByEntityMetadata')
            ->willReturn($workflowItems);

        $this->groupedLineItemsProvider->expects($this->never())
            ->method('getGroupedLineItemsIds');

        $this->groupedLineItemsProvider->expects($this->once())
            ->method('getGroupedLineItemsByIds')
            ->with($checkout, [])
            ->willReturn([]);

        $this->checkoutSplitter->expects($this->never())
            ->method('split');

        $this->configProvider->expects($this->once())
            ->method('isCreateSubOrdersForEachGroupEnabled')
            ->willReturn(true);

        $result = $this->provider->getSubCheckouts($checkout);

        $this->assertEmpty($result);
    }

    public function getDataForTestGetSubCheckoutWithWorkflowDisabledOrNotExists(): array
    {
        $workflowItemWithDisabledDefinition = $this->getWorkflowItem(false, []);
        $workflowItemNotSupportedGrouping = $this->getWorkflowItem(true);
        $workflowIsNotCheckout = $this->getWorkflowItem(true, [], []);
        $workflowWithNotCheckoutGroup = $this->getWorkflowItem(true, ['b2b_quote_backoffice_flow'], []);

        return [
            'WorkflowItem with disabled workflow' => [
                'workflowItems' => [$workflowItemWithDisabledDefinition]
            ],
            'Workflow item which does not support grouping line items' => [
                'workflowItems' => [$workflowItemNotSupportedGrouping]
            ],
            'Workflow is not checkout' => [
                'workflowItems' => [$workflowIsNotCheckout]
            ],
            'Workflow with non checkout group' => [
                'workflowItems' => [$workflowWithNotCheckoutGroup]
            ],
            'Empty workflowItems set' => [
                'workflowItems' => []
            ]
        ];
    }

    public function testGetSubCheckoutsWithEmptyGroupedLineItemsWorkflowAttribute()
    {
        $lineItem1 = $this->getCheckoutLineItem('sku-1', 'item');
        $lineItem2 = $this->getCheckoutLineItem('sku-2', 'set');
        $lineItem3 = $this->getCheckoutLineItem('sku-3', 'item');

        $checkout = $this->getCheckout(1);
        $checkout->setLineItems(new ArrayCollection([$lineItem1, $lineItem2, $lineItem3]));

        $groupedLineItemsIds = [
            'product.owner:1' => ['sku-1:item','sku-3:item'],
            'product.owner:2' => ['sku-2:set']
        ];

        $groupedLineItems = [
            'product.owner:1' => [$lineItem1, $lineItem3],
            'product.owner:2' => [$lineItem2]
        ];

        $workflowItem = $this->getWorkflowItem(true, []);
        $repository = $this->createMock(WorkflowItemRepository::class);

        $this->doctrine->expects($this->once())
            ->method('getRepository')
            ->willReturn($repository);

        $repository->expects($this->once())
            ->method('findAllByEntityMetadata')
            ->willReturn([$workflowItem]);

        $this->groupedLineItemsProvider->expects($this->once())
            ->method('getGroupedLineItemsIds')
            ->willReturn($groupedLineItemsIds);

        $this->groupedLineItemsProvider->expects($this->once())
            ->method('getGroupedLineItemsByIds')
            ->willReturn($groupedLineItems);

        $this->checkoutSplitter->expects($this->once())
            ->method('split')
            ->willReturn([
                'product.owner:1' => (new Checkout())->setLineItems(new ArrayCollection([$lineItem1, $lineItem2])),
                'product.owner:2' => (new Checkout())->setLineItems(new ArrayCollection([$lineItem2]))
            ]);

        $this->configProvider->expects($this->once())
            ->method('isCreateSubOrdersForEachGroupEnabled')
            ->willReturn(true);

        $result = $this->provider->getSubCheckouts($checkout);

        $this->assertCount(2, $result);
        $this->assertArrayHasKey('product.owner:1', $result);
        $this->assertArrayHasKey('product.owner:2', $result);

        $subCheckout1 = $result['product.owner:1'];
        $subCheckout2 = $result['product.owner:2'];

        $this->assertCount(2, $subCheckout1->getLineItems());
        $this->assertCount(1, $subCheckout2->getLineItems());
    }

    public function testGetSubCheckoutsWithCachedValues()
    {
        $lineItem1 = $this->getCheckoutLineItem('sku-1', 'item');
        $lineItem2 = $this->getCheckoutLineItem('sku-2', 'set');
        $lineItem3 = $this->getCheckoutLineItem('sku-3', 'item');

        $cachedSubCheckouts = [
            1 => [
                'product.owner:1' => (new Checkout())->setLineItems(new ArrayCollection([$lineItem1, $lineItem3])),
                'product.owner:2' => (new Checkout())->setLineItems(new ArrayCollection([$lineItem2]))
            ]
        ];

        ReflectionUtil::setPropertyValue($this->provider, 'subCheckouts', $cachedSubCheckouts);

        $this->doctrine->expects($this->never())
            ->method('getRepository');

        $this->groupedLineItemsProvider->expects($this->never())
            ->method('getGroupedLineItemsIds');

        $this->groupedLineItemsProvider->expects($this->never())
            ->method('getGroupedLineItemsByIds');

        $this->checkoutSplitter->expects($this->never())
            ->method('split');

        $this->configProvider->expects($this->never())
            ->method('isCreateSubOrdersForEachGroupEnabled');

        $checkout = $this->getCheckout(1);

        $result = $this->provider->getSubCheckouts($checkout);

        $this->assertCount(2, $result);
        $this->assertArrayHasKey('product.owner:1', $result);
        $this->assertArrayHasKey('product.owner:2', $result);

        $subCheckout1 = $result['product.owner:1'];
        $subCheckout2 = $result['product.owner:2'];

        $this->assertCount(2, $subCheckout1->getLineItems());
        $this->assertCount(1, $subCheckout2->getLineItems());
    }

    public function testGetSubCheckoutsWithCachedValuesSkip()
    {
        $lineItem1 = $this->getCheckoutLineItem('sku-1', 'item');
        $lineItem2 = $this->getCheckoutLineItem('sku-2', 'set');
        $lineItem3 = $this->getCheckoutLineItem('sku-3', 'item');

        $cachedSubCheckouts = [
            1 => [
                'product.owner:1' => (new Checkout())->setLineItems(new ArrayCollection([$lineItem1, $lineItem2])),
                'product.owner:2' => (new Checkout())->setLineItems(new ArrayCollection([$lineItem2]))
            ]
        ];

        ReflectionUtil::setPropertyValue($this->provider, 'subCheckouts', $cachedSubCheckouts);

        $checkout = $this->getCheckout(1);
        $checkout->setLineItems(new ArrayCollection([$lineItem1, $lineItem2, $lineItem3]));

        $groupedLineItemsIds = [
            'product.owner:1' => ['sku-1:item','sku-3:item'],
            'product.owner:2' => ['sku-2:set']
        ];

        $groupedLineItems = [
            'product.owner:1' => [$lineItem1, $lineItem3],
            'product.owner:2' => [$lineItem2]
        ];

        $workflowItem = $this->getWorkflowItem(true, []);
        $repository = $this->createMock(WorkflowItemRepository::class);

        $this->doctrine->expects($this->once())
            ->method('getRepository')
            ->willReturn($repository);

        $repository->expects($this->once())
            ->method('findAllByEntityMetadata')
            ->willReturn([$workflowItem]);

        $this->groupedLineItemsProvider->expects($this->once())
            ->method('getGroupedLineItemsIds')
            ->willReturn($groupedLineItemsIds);

        $this->groupedLineItemsProvider->expects($this->once())
            ->method('getGroupedLineItemsByIds')
            ->willReturn($groupedLineItems);

        $this->checkoutSplitter->expects($this->once())
            ->method('split')
            ->willReturn([
                'product.owner:1' => (new Checkout())->setLineItems(new ArrayCollection([$lineItem1, $lineItem2])),
                'product.owner:2' => (new Checkout())->setLineItems(new ArrayCollection([$lineItem2]))
            ]);

        $this->configProvider->expects($this->once())
            ->method('isCreateSubOrdersForEachGroupEnabled')
            ->willReturn(true);

        $result = $this->provider->getSubCheckouts($checkout, false);

        $this->assertCount(2, $result);
        $this->assertArrayHasKey('product.owner:1', $result);
        $this->assertArrayHasKey('product.owner:2', $result);

        $subCheckout1 = $result['product.owner:1'];
        $subCheckout2 = $result['product.owner:2'];

        $this->assertCount(2, $subCheckout1->getLineItems());
        $this->assertCount(1, $subCheckout2->getLineItems());
    }

    public function testGetSubCheckoutsWhenCreateSubOrdersDisabled()
    {
        $this->configProvider->expects($this->once())
            ->method('isCreateSubOrdersForEachGroupEnabled')
            ->willReturn(false);

        $this->doctrine->expects($this->never())
            ->method('getRepository');

        $this->groupedLineItemsProvider->expects($this->never())
            ->method('getGroupedLineItemsIds');

        $this->groupedLineItemsProvider->expects($this->never())
            ->method('getGroupedLineItemsByIds');

        $this->checkoutSplitter->expects($this->never())
            ->method('split');

        $result = $this->provider->getSubCheckouts(new Checkout(), false);
        $this->assertEmpty($result);
    }

    public function getTestIsCreateSubOrdersEnabledData(): array
    {
        return [
            [true, true],
            [false, false]
        ];
    }
}
