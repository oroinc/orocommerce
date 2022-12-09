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
use Oro\Component\Testing\Unit\EntityTrait;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class SplitCheckoutProviderTest extends TestCase
{
    use EntityTrait;

    private ManagerRegistry|MockObject $managerRegistry;
    private CheckoutSplitter|MockObject $checkoutSplitter;
    private GroupedCheckoutLineItemsProvider|MockObject $groupedLineItemsProvider;
    private ConfigProvider|MockObject $configProvider;
    private SplitCheckoutProvider $provider;

    protected function setUp(): void
    {
        $this->managerRegistry = $this->createMock(ManagerRegistry::class);
        $this->checkoutSplitter = $this->createMock(CheckoutSplitter::class);
        $this->groupedLineItemsProvider = $this->createMock(GroupedCheckoutLineItemsProvider::class);
        $this->configProvider = $this->createMock(ConfigProvider::class);

        $this->provider = new SplitCheckoutProvider(
            $this->managerRegistry,
            $this->checkoutSplitter,
            $this->groupedLineItemsProvider,
            $this->configProvider
        );
    }

    public function testGetSubCheckouts()
    {
        $lineItem1 = $this->getEntity(CheckoutLineItem::class, [
            'productSku' => 'sku-1',
            'productUnitCode' => 'item'
        ]);

        $lineItem2 = $this->getEntity(CheckoutLineItem::class, [
            'productSku' => 'sku-2',
            'productUnitCode' => 'set'
        ]);

        $lineItem3 = $this->getEntity(CheckoutLineItem::class, [
            'productSku' => 'sku-3',
            'productUnitCode' => 'item'
        ]);

        $checkout = new Checkout();
        ReflectionUtil::setId($checkout, 1);
        $checkout->setLineItems(new ArrayCollection([$lineItem1, $lineItem2, $lineItem3]));

        $groupedLineItemsIds = [
            'product.owner:1' => ['sku-1:item','sku-3:item'],
            'product.owner:2' => ['sku-2:set']
        ];

        $groupedLineItems = [
            'product.owner:1' => [$lineItem1, $lineItem3],
            'product.owner:2' => [$lineItem2]
        ];

        $workflowItem = $this->createWorkflowItem(true, $groupedLineItemsIds);
        $repository = $this->createMock(WorkflowItemRepository::class);

        $this->managerRegistry->expects($this->once())
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
     * @param array $workflowItems
     * @dataProvider getDataForTestGetSubCheckoutWithWorkflowDisabledOrNotExists
     */
    public function testGetSubCheckoutWithWorkflowDisabledOrNotExists(array $workflowItems)
    {
        $checkout = new Checkout();
        ReflectionUtil::setId($checkout, 1);

        $repository = $this->createMock(WorkflowItemRepository::class);

        $this->managerRegistry->expects($this->once())
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
        $workflowItemWithDisabledDefinition = $this->createWorkflowItem(false, []);
        $workflowItemNotSupportedGrouping = $this->createWorkflowItem(true);
        $workflowIsNotCheckout = $this->createWorkflowItem(true, [], []);
        $workflowWithNotCheckoutGroup = $this->createWorkflowItem(true, ['b2b_quote_backoffice_flow'], []);

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
        $lineItem1 = $this->getEntity(CheckoutLineItem::class, [
            'productSku' => 'sku-1',
            'productUnitCode' => 'item'
        ]);

        $lineItem2 = $this->getEntity(CheckoutLineItem::class, [
            'productSku' => 'sku-2',
            'productUnitCode' => 'set'
        ]);

        $lineItem3 = $this->getEntity(CheckoutLineItem::class, [
            'productSku' => 'sku-3',
            'productUnitCode' => 'item'
        ]);

        $checkout = new Checkout();
        ReflectionUtil::setId($checkout, 1);
        $checkout->setLineItems(new ArrayCollection([$lineItem1, $lineItem2, $lineItem3]));

        $groupedLineItemsIds = [
            'product.owner:1' => ['sku-1:item','sku-3:item'],
            'product.owner:2' => ['sku-2:set']
        ];

        $groupedLineItems = [
            'product.owner:1' => [$lineItem1, $lineItem3],
            'product.owner:2' => [$lineItem2]
        ];

        $workflowItem = $this->createWorkflowItem(true, []);
        $repository = $this->createMock(WorkflowItemRepository::class);

        $this->managerRegistry->expects($this->once())
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
        $lineItem1 = $this->getEntity(CheckoutLineItem::class, [
            'productSku' => 'sku-1',
            'productUnitCode' => 'item'
        ]);

        $lineItem2 = $this->getEntity(CheckoutLineItem::class, [
            'productSku' => 'sku-2',
            'productUnitCode' => 'set'
        ]);

        $lineItem3 = $this->getEntity(CheckoutLineItem::class, [
            'productSku' => 'sku-3',
            'productUnitCode' => 'item'
        ]);

        $cachedSubCheckouts = [
            1 => [
                'product.owner:1' => (new Checkout())->setLineItems(new ArrayCollection([$lineItem1, $lineItem3])),
                'product.owner:2' => (new Checkout())->setLineItems(new ArrayCollection([$lineItem2]))
            ]
        ];

        ReflectionUtil::setPropertyValue($this->provider, 'subCheckouts', $cachedSubCheckouts);

        $this->managerRegistry->expects($this->never())
            ->method('getRepository');

        $this->groupedLineItemsProvider->expects($this->never())
            ->method('getGroupedLineItemsIds');

        $this->groupedLineItemsProvider->expects($this->never())
            ->method('getGroupedLineItemsByIds');

        $this->checkoutSplitter->expects($this->never())
            ->method('split');

        $this->configProvider->expects($this->never())
            ->method('isCreateSubOrdersForEachGroupEnabled');

        $checkout = new Checkout();
        ReflectionUtil::setId($checkout, 1);

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
        $lineItem1 = $this->getEntity(CheckoutLineItem::class, [
            'productSku' => 'sku-1',
            'productUnitCode' => 'item'
        ]);

        $lineItem2 = $this->getEntity(CheckoutLineItem::class, [
            'productSku' => 'sku-2',
            'productUnitCode' => 'set'
        ]);

        $lineItem3 = $this->getEntity(CheckoutLineItem::class, [
            'productSku' => 'sku-3',
            'productUnitCode' => 'item'
        ]);

        $cachedSubCheckouts = [
            1 => [
                'product.owner:1' => (new Checkout())->setLineItems(new ArrayCollection([$lineItem1, $lineItem2])),
                'product.owner:2' => (new Checkout())->setLineItems(new ArrayCollection([$lineItem2]))
            ]
        ];

        ReflectionUtil::setPropertyValue($this->provider, 'subCheckouts', $cachedSubCheckouts);

        $checkout = new Checkout();
        ReflectionUtil::setId($checkout, 1);
        $checkout->setLineItems(new ArrayCollection([$lineItem1, $lineItem2, $lineItem3]));

        $groupedLineItemsIds = [
            'product.owner:1' => ['sku-1:item','sku-3:item'],
            'product.owner:2' => ['sku-2:set']
        ];

        $groupedLineItems = [
            'product.owner:1' => [$lineItem1, $lineItem3],
            'product.owner:2' => [$lineItem2]
        ];

        $workflowItem = $this->createWorkflowItem(true, []);
        $repository = $this->createMock(WorkflowItemRepository::class);

        $this->managerRegistry->expects($this->once())
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

    private function createWorkflowItem(
        $active = true,
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

    public function testGetSubCheckoutsWhenCreateSubOrdersDisabled()
    {
        $this->configProvider->expects($this->once())
            ->method('isCreateSubOrdersForEachGroupEnabled')
            ->willReturn(false);

        $this->managerRegistry->expects($this->never())
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

    public function getTestIsCreateSubOrdersEnabledData()
    {
        return [
            [true, true],
            [false, false]
        ];
    }
}
