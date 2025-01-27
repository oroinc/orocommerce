<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\EventListener;

use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Entity\CheckoutSource;
use Oro\Bundle\CheckoutBundle\Event\CheckoutTransitionBeforeEvent;
use Oro\Bundle\CheckoutBundle\EventListener\CheckoutTransitionSourceConfiguration;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\ShoppingListBundle\Manager\ShoppingListLimitManager;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Model\Transition;
use Oro\Bundle\WorkflowBundle\Model\WorkflowData;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CheckoutTransitionSourceConfigurationTest extends TestCase
{
    private ShoppingListLimitManager|MockObject $shoppingListLimitManager;
    private CheckoutTransitionSourceConfiguration $transitionSourceConfiguration;

    protected function setUp(): void
    {
        $this->shoppingListLimitManager = $this->createMock(ShoppingListLimitManager::class);

        $this->transitionSourceConfiguration = new CheckoutTransitionSourceConfiguration(
            $this->shoppingListLimitManager
        );
    }

    /**
     * @dataProvider testOnBeforeProvider
     */
    public function testOnBefore(bool $isOnlyOneEnabled, array $expectedData): void
    {
        $shoppingList = new ShoppingList();

        $source = $this->createMock(CheckoutSource::class);
        $source
            ->expects($this->once())
            ->method('getEntity')
            ->willReturn($shoppingList);
        $checkout = new Checkout();
        $checkout->setSource($source);

        $workflowData = new WorkflowData([
            'allow_manual_source_remove' => false,
            'remove_source' => false,
            'clear_source' => false,
        ]);
        $workflowItem = (new WorkflowItem())
            ->setEntity($checkout)
            ->setData($workflowData);

        $this->shoppingListLimitManager
            ->expects($this->once())
            ->method('isOnlyOneEnabled')
            ->willReturn($isOnlyOneEnabled);

        $transition = $this->createMock(Transition::class);

        $event = new CheckoutTransitionBeforeEvent($workflowItem, $transition);
        $this->transitionSourceConfiguration->onBefore($event);

        $this->assertEquals($expectedData, $event->getWorkflowItem()->getData()->toArray());
    }

    public function testOnBeforeProvider(): array
    {
        return [
            [
                'isOnlyOneEnabled' => true,
                'expectedData' => [
                    'allow_manual_source_remove' => false,
                    'remove_source' => false,
                    'clear_source' => true
                ]
            ],
            [
                'isOnlyOneEnabled' => false,
                'expectedData' => [
                    'allow_manual_source_remove' => true,
                    'remove_source' => true,
                    'clear_source' => false
                ]
            ]
        ];
    }
}
