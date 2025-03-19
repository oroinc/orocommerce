<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\Helper;

use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Helper\CheckoutWorkflowHelper;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowStep;
use Oro\Bundle\WorkflowBundle\Model\WorkflowManager;
use Oro\Component\Testing\ReflectionUtil;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class CheckoutWorkflowHelperTest extends TestCase
{
    /** @var WorkflowManager|MockObject */
    private $workflowManager;

    /** @var CheckoutWorkflowHelper */
    private $helper;

    #[\Override]
    protected function setUp(): void
    {
        $this->workflowManager = $this->createMock(WorkflowManager::class);

        $this->helper = new CheckoutWorkflowHelper($this->workflowManager);
    }

    private function getCheckout(int $id = 1): Checkout
    {
        $checkout = new Checkout();
        ReflectionUtil::setId($checkout, $id);

        return $checkout;
    }

    private function getWorkflowItem(int $id): WorkflowItem
    {
        $workflowItem = new WorkflowItem();
        $workflowItem->setId($id);
        $workflowItem->setCurrentStep(new WorkflowStep());

        return $workflowItem;
    }

    public function testGetWorkflowItemWhenOneWorkflowItemFound()
    {
        $workflowItem = $this->getWorkflowItem(1);

        $checkout = $this->getCheckout();
        $this->workflowManager->expects(self::once())
            ->method('getWorkflowItemsByEntity')
            ->with($checkout)
            ->willReturn([$workflowItem]);

        self::assertSame($workflowItem, $this->helper->getWorkflowItem($checkout));
    }

    public function testGetWorkflowItemWhenSeveralWorkflowItemsFound()
    {
        $checkout = $this->getCheckout();
        $this->workflowManager->expects(self::once())
            ->method('getWorkflowItemsByEntity')
            ->with($checkout)
            ->willReturn([$this->getWorkflowItem(1), $this->getWorkflowItem(2)]);

        self::assertNull($this->helper->getWorkflowItem($checkout));
    }

    public function testGetWorkflowItemWhenNoWorkflowItemsFound()
    {
        $checkout = $this->getCheckout();
        $this->workflowManager->expects(self::once())
            ->method('getWorkflowItemsByEntity')
            ->with($checkout)
            ->willReturn([]);

        self::assertNull($this->helper->getWorkflowItem($checkout));
    }

    public function testFindWorkflowItems()
    {
        $workflowItems = [$this->getWorkflowItem(1), $this->getWorkflowItem(2)];
        $anotherWorkflowItems = [$this->getWorkflowItem(3)];

        $checkout = $this->getCheckout(10);
        $anotherCheckout = $this->getCheckout(20);
        $this->workflowManager->expects(self::exactly(2))
            ->method('getWorkflowItemsByEntity')
            ->withConsecutive([$checkout], [$anotherCheckout])
            ->willReturnOnConsecutiveCalls($workflowItems, $anotherWorkflowItems);

        self::assertSame($workflowItems, $this->helper->findWorkflowItems($checkout));
        self::assertSame($anotherWorkflowItems, $this->helper->findWorkflowItems($anotherCheckout));
        // test memory cache
        self::assertSame($workflowItems, $this->helper->findWorkflowItems($checkout));
        self::assertSame($anotherWorkflowItems, $this->helper->findWorkflowItems($anotherCheckout));
    }

    public function testFindWorkflowItemWithClearCaches()
    {
        $workflowItems = [$this->getWorkflowItem(1), $this->getWorkflowItem(2)];
        $anotherWorkflowItems = [$this->getWorkflowItem(3)];

        $checkout = $this->getCheckout(10);
        $anotherCheckout = $this->getCheckout(20);
        $this->workflowManager->expects(self::exactly(3))
            ->method('getWorkflowItemsByEntity')
            ->withConsecutive([$checkout], [$anotherCheckout], [$checkout])
            ->willReturnOnConsecutiveCalls(
                $workflowItems,
                $anotherWorkflowItems,
                $workflowItems
            );

        self::assertSame($workflowItems, $this->helper->findWorkflowItems($checkout));
        self::assertSame($anotherWorkflowItems, $this->helper->findWorkflowItems($anotherCheckout));

        $this->helper->clearCaches($checkout);

        // test memory cache
        self::assertSame($workflowItems, $this->helper->findWorkflowItems($checkout));
        self::assertSame($anotherWorkflowItems, $this->helper->findWorkflowItems($anotherCheckout));
    }

    /**
     * @dataProvider isCheckoutWorkflowDataProvider
     */
    public function testIsCheckoutWorkflow(?array $metadata, bool $expected)
    {
        $workflowItem = new WorkflowItem();
        $definition = new WorkflowDefinition();
        $workflowItem->setDefinition($definition);
        if ($metadata !== null) {
            $definition->setMetadata($metadata);
        }

        self::assertSame($expected, CheckoutWorkflowHelper::isCheckoutWorkflow($workflowItem));
    }

    /**
     * @dataProvider isCheckoutWorkflowDataProvider
     */
    public function testIsCheckoutWorkflowDefinition(?array $metadata, bool $expected)
    {
        $definition = new WorkflowDefinition();
        if ($metadata !== null) {
            $definition->setMetadata($metadata);
        }

        self::assertSame($expected, CheckoutWorkflowHelper::isCheckoutWorkflowDefinition($definition));
    }

    public function isCheckoutWorkflowDataProvider(): iterable
    {
        yield [
            [],
            false
        ];

        yield [
            null,
            false
        ];

        yield [
            ['is_checkout_workflow' => true],
            true
        ];

        yield [
            ['is_checkout_workflow' => false],
            false
        ];
    }

    /**
     * @dataProvider isSinglePageCheckoutWorkflowDataProvider
     */
    public function testIsSinglePageCheckoutWorkflow(?array $metadata, bool $expected)
    {
        $workflowItem = new WorkflowItem();
        $definition = new WorkflowDefinition();
        $workflowItem->setDefinition($definition);
        if ($metadata !== null) {
            $definition->setMetadata($metadata);
        }

        self::assertSame($expected, CheckoutWorkflowHelper::isSinglePageCheckoutWorkflow($workflowItem));
    }

    /**
     * @dataProvider isSinglePageCheckoutWorkflowDataProvider
     */
    public function testIsSinglePageCheckoutWorkflowDefinition(?array $metadata, bool $expected)
    {
        $definition = new WorkflowDefinition();
        if ($metadata !== null) {
            $definition->setMetadata($metadata);
        }

        self::assertSame($expected, CheckoutWorkflowHelper::isSinglePageCheckoutWorkflowDefinition($definition));
    }

    public function isSinglePageCheckoutWorkflowDataProvider(): iterable
    {
        yield [
            [],
            false
        ];

        yield [
            null,
            false
        ];

        yield [
            ['is_checkout_workflow' => true],
            false
        ];

        yield [
            ['is_checkout_workflow' => false],
            false
        ];

        yield [
            ['is_checkout_workflow' => true, 'is_single_page_checkout' => true],
            true
        ];

        yield [
            ['is_checkout_workflow' => true, 'is_single_page_checkout' => false],
            false
        ];
    }

    /**
     * @dataProvider isMultiStepCheckoutWorkflowDataProvider
     */
    public function testIsMultiStepCheckoutWorkflow(?array $metadata, bool $expected)
    {
        $workflowItem = new WorkflowItem();
        $definition = new WorkflowDefinition();
        $workflowItem->setDefinition($definition);
        if ($metadata !== null) {
            $definition->setMetadata($metadata);
        }

        self::assertSame($expected, CheckoutWorkflowHelper::isMultiStepCheckoutWorkflow($workflowItem));
    }

    /**
     * @dataProvider isMultiStepCheckoutWorkflowDataProvider
     */
    public function testIsMultiStepCheckoutWorkflowDefinition(?array $metadata, bool $expected)
    {
        $definition = new WorkflowDefinition();
        if ($metadata !== null) {
            $definition->setMetadata($metadata);
        }

        self::assertSame($expected, CheckoutWorkflowHelper::isMultiStepCheckoutWorkflowDefinition($definition));
    }

    public function isMultiStepCheckoutWorkflowDataProvider(): iterable
    {
        yield [
            [],
            false
        ];

        yield [
            null,
            false
        ];

        yield [
            ['is_checkout_workflow' => true],
            true
        ];

        yield [
            ['is_checkout_workflow' => false],
            false
        ];

        yield [
            ['is_checkout_workflow' => true, 'is_single_page_checkout' => true],
            false
        ];

        yield [
            ['is_checkout_workflow' => true, 'is_single_page_checkout' => false],
            true
        ];
    }
}
