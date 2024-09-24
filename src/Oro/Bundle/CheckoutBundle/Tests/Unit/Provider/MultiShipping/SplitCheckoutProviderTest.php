<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\Provider\MultiShipping;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Entity\CheckoutLineItem;
use Oro\Bundle\CheckoutBundle\Helper\CheckoutWorkflowHelper;
use Oro\Bundle\CheckoutBundle\Provider\MultiShipping\ConfigProvider;
use Oro\Bundle\CheckoutBundle\Provider\MultiShipping\GroupedCheckoutLineItemsProvider;
use Oro\Bundle\CheckoutBundle\Provider\MultiShipping\SplitCheckoutProvider;
use Oro\Bundle\CheckoutBundle\Splitter\MultiShipping\CheckoutSplitter;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Model\WorkflowData;
use Oro\Component\Testing\ReflectionUtil;

class SplitCheckoutProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var CheckoutWorkflowHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $checkoutWorkflowHelper;

    /** @var CheckoutSplitter|\PHPUnit\Framework\MockObject\MockObject */
    private $checkoutSplitter;

    /** @var GroupedCheckoutLineItemsProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $groupedLineItemsProvider;

    /** @var ConfigProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $configProvider;

    /** @var SplitCheckoutProvider */
    private $provider;

    #[\Override]
    protected function setUp(): void
    {
        $this->checkoutWorkflowHelper = $this->createMock(CheckoutWorkflowHelper::class);
        $this->checkoutSplitter = $this->createMock(CheckoutSplitter::class);
        $this->groupedLineItemsProvider = $this->createMock(GroupedCheckoutLineItemsProvider::class);
        $this->configProvider = $this->createMock(ConfigProvider::class);

        $this->provider = new SplitCheckoutProvider(
            $this->checkoutWorkflowHelper,
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

    public function testGetSubCheckouts(): void
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

        $this->checkoutWorkflowHelper->expects(self::once())
            ->method('findWorkflowItems')
            ->with(self::identicalTo($checkout))
            ->willReturn([$workflowItem]);

        $this->groupedLineItemsProvider->expects(self::never())
            ->method('getGroupedLineItemsIds');

        $this->groupedLineItemsProvider->expects(self::once())
            ->method('getGroupedLineItemsByIds')
            ->willReturn($groupedLineItems);

        $this->checkoutSplitter->expects(self::once())
            ->method('split')
            ->willReturn([
                'product.owner:1' => (new Checkout())->setLineItems(new ArrayCollection([$lineItem1, $lineItem2])),
                'product.owner:2' => (new Checkout())->setLineItems(new ArrayCollection([$lineItem2]))
            ]);

        $this->configProvider->expects(self::once())
            ->method('isCreateSubOrdersForEachGroupEnabled')
            ->willReturn(true);

        $result = $this->provider->getSubCheckouts($checkout);

        self::assertCount(2, $result);
        self::assertArrayHasKey('product.owner:1', $result);
        self::assertArrayHasKey('product.owner:2', $result);
        self::assertCount(2, $result['product.owner:1']->getLineItems());
        self::assertCount(1, $result['product.owner:2']->getLineItems());
    }

    /**
     * @dataProvider getDataForTestGetSubCheckoutWithWorkflowDisabledOrNotExists
     */
    public function testGetSubCheckoutWithWorkflowDisabledOrNotExists(array $workflowItems): void
    {
        $checkout = $this->getCheckout(1);

        $this->checkoutWorkflowHelper->expects(self::once())
            ->method('findWorkflowItems')
            ->with(self::identicalTo($checkout))
            ->willReturn($workflowItems);

        $this->groupedLineItemsProvider->expects(self::never())
            ->method('getGroupedLineItemsIds');

        $this->groupedLineItemsProvider->expects(self::once())
            ->method('getGroupedLineItemsByIds')
            ->with(self::identicalTo($checkout), [])
            ->willReturn([]);

        $this->checkoutSplitter->expects(self::never())
            ->method('split');

        $this->configProvider->expects(self::once())
            ->method('isCreateSubOrdersForEachGroupEnabled')
            ->willReturn(true);

        self::assertSame([], $this->provider->getSubCheckouts($checkout));
    }

    public function getDataForTestGetSubCheckoutWithWorkflowDisabledOrNotExists(): array
    {
        return [
            'WorkflowItem with disabled workflow' => [
                'workflowItems' => [$this->getWorkflowItem(false, [])]
            ],
            'Workflow item which does not support grouping line items' => [
                'workflowItems' => [$this->getWorkflowItem(true)]
            ],
            'Workflow is not checkout' => [
                'workflowItems' => [$this->getWorkflowItem(true, [], [])]
            ],
            'Workflow with non checkout group' => [
                'workflowItems' => [$this->getWorkflowItem(true, ['b2b_quote_backoffice_flow'], [])]
            ],
            'Empty workflowItems set' => [
                'workflowItems' => []
            ]
        ];
    }

    public function testGetSubCheckoutsWithEmptyGroupedLineItemsWorkflowAttribute(): void
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

        $this->checkoutWorkflowHelper->expects(self::once())
            ->method('findWorkflowItems')
            ->with(self::identicalTo($checkout))
            ->willReturn([$workflowItem]);

        $this->groupedLineItemsProvider->expects(self::once())
            ->method('getGroupedLineItemsIds')
            ->willReturn($groupedLineItemsIds);

        $this->groupedLineItemsProvider->expects(self::once())
            ->method('getGroupedLineItemsByIds')
            ->willReturn($groupedLineItems);

        $this->checkoutSplitter->expects(self::once())
            ->method('split')
            ->willReturn([
                'product.owner:1' => (new Checkout())->setLineItems(new ArrayCollection([$lineItem1, $lineItem2])),
                'product.owner:2' => (new Checkout())->setLineItems(new ArrayCollection([$lineItem2]))
            ]);

        $this->configProvider->expects(self::once())
            ->method('isCreateSubOrdersForEachGroupEnabled')
            ->willReturn(true);

        $result = $this->provider->getSubCheckouts($checkout);

        self::assertCount(2, $result);
        self::assertArrayHasKey('product.owner:1', $result);
        self::assertArrayHasKey('product.owner:2', $result);
        self::assertCount(2, $result['product.owner:1']->getLineItems());
        self::assertCount(1, $result['product.owner:2']->getLineItems());
    }

    public function testGetSubCheckoutsWhenCreateSubOrdersDisabled(): void
    {
        $this->configProvider->expects(self::once())
            ->method('isCreateSubOrdersForEachGroupEnabled')
            ->willReturn(false);

        $this->checkoutWorkflowHelper->expects(self::never())
            ->method('findWorkflowItems');

        $this->groupedLineItemsProvider->expects(self::never())
            ->method('getGroupedLineItemsIds');

        $this->groupedLineItemsProvider->expects(self::never())
            ->method('getGroupedLineItemsByIds');

        $this->checkoutSplitter->expects(self::never())
            ->method('split');

        self::assertSame([], $this->provider->getSubCheckouts(new Checkout()));
    }
}
