<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\Layout\DataProvider;

use Oro\Bundle\CheckoutBundle\Layout\DataProvider\TransitionProviderInterface;
use Oro\Bundle\CheckoutBundle\Layout\DataProvider\WorkflowRelatedTransitionProvider;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Model\WorkflowManager;

class WorkflowRelatedTransitionProviderTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var WorkflowManager|\PHPUnit\Framework\MockObject\MockObject
     */
    private $workflowManager;

    /**
     * @var TransitionProviderInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $defaultProvider;

    /**
     * @var WorkflowRelatedTransitionProvider
     */
    private $provider;

    protected function setUp(): void
    {
        $this->workflowManager = $this->createMock(WorkflowManager::class);
        $this->defaultProvider = $this->createMock(TransitionProviderInterface::class);

        $this->provider = new WorkflowRelatedTransitionProvider(
            $this->workflowManager,
            $this->defaultProvider
        );
    }

    /**
     * @dataProvider methodsDataProvider
     */
    public function testDefaultProviderCalls(string $method, array $arguments)
    {
        $this->defaultProvider->expects($this->once())
            ->method($method)
            ->with(...$arguments);

        $this->workflowManager->expects($this->never())
            ->method($this->anything());

        $this->provider->$method(...$arguments);
    }

    /**
     * @dataProvider methodsDataProvider
     */
    public function testWorkflowRelateProviderCalls(string $method, array $arguments)
    {
        $provider = $this->createMock(TransitionProviderInterface::class);
        $this->provider->addWorkflowRelatedProvider('test_workflow', $provider);

        $this->workflowManager->expects($this->once())
            ->method('isActiveWorkflow')
            ->with('test_workflow')
            ->willReturn(true);

        $this->defaultProvider->expects($this->never())
            ->method($method)
            ->with(...$arguments);

        $provider->expects($this->once())
            ->method($method)
            ->with(...$arguments);

        $this->provider->$method(...$arguments);
    }

    /**
     * @dataProvider methodsDataProvider
     */
    public function testWorkflowRelateProviderCallsWhenIncative(string $method, array $arguments)
    {
        $provider = $this->createMock(TransitionProviderInterface::class);
        $this->provider->addWorkflowRelatedProvider('test_workflow', $provider);

        $this->workflowManager->expects($this->once())
            ->method('isActiveWorkflow')
            ->with('test_workflow')
            ->willReturn(false);

        $this->defaultProvider->expects($this->once())
            ->method($method)
            ->with(...$arguments);

        $provider->expects($this->never())
            ->method($method)
            ->with(...$arguments);

        $this->provider->$method(...$arguments);
    }

    public function methodsDataProvider(): array
    {
        return [
            'getBackTransition' => ['getBackTransition', [new WorkflowItem()]],
            'getBackTransitions' => ['getBackTransitions', [new WorkflowItem()]],
            'getContinueTransition' => ['getContinueTransition', [new WorkflowItem(), 'test']],
            'clearCache' => ['clearCache', []],

        ];
    }
}
