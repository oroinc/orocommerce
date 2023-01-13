<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\Layout\DataProvider;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\CheckoutBundle\Layout\DataProvider\SinglePageTransitionProvider;
use Oro\Bundle\CheckoutBundle\Layout\DataProvider\TransitionProviderInterface;
use Oro\Bundle\CheckoutBundle\Model\TransitionData;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Model\Transition;

class SinglePageTransitionProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject|TransitionProviderInterface */
    private $baseProvider;

    /** @var SinglePageTransitionProvider */
    private $provider;

    protected function setUp(): void
    {
        $this->baseProvider = $this->createMock(TransitionProviderInterface::class);
        $this->provider = new SinglePageTransitionProvider($this->baseProvider);
    }

    public function testGetBackTransitions()
    {
        $workflowItem = new WorkflowItem();
        $transitions = [$this->createMock(TransitionData::class)];

        $this->baseProvider->expects($this->once())
            ->method('getBackTransitions')
            ->with($workflowItem)
            ->willReturn($transitions);

        $this->assertEquals($transitions, $this->provider->getBackTransitions($workflowItem));
    }

    /**
     * @dataProvider continueTransitionDataProvider
     */
    public function testGetContinueTransition(bool $isAllowed)
    {
        $workflowItem = new WorkflowItem();
        /** @var Transition $transition */
        $transition = $this->createMock(Transition::class);
        $errors = new ArrayCollection(['some' => 'error']);
        $transitionData = new TransitionData($transition, $isAllowed, $errors);
        $transitionName = 'testName';

        $this->baseProvider->expects($this->once())
            ->method('getContinueTransition')
            ->with($workflowItem, $transitionName)
            ->willReturn($transitionData);

        $expectedTransitionData = new TransitionData($transition, true, $errors);

        $this->assertEquals(
            $expectedTransitionData,
            $this->provider->getContinueTransition($workflowItem, $transitionName)
        );
    }

    public function testGetContinueTransitionWhenNullReturned()
    {
        $workflowItem = new WorkflowItem();
        $transitionName = 'testName';

        $this->baseProvider->expects($this->once())
            ->method('getContinueTransition')
            ->with($workflowItem, $transitionName)
            ->willReturn(null);

        $this->assertNull($this->provider->getContinueTransition($workflowItem, $transitionName));
    }

    public function continueTransitionDataProvider(): array
    {
        return [
            'transition is allowed' => [
                'isAllowed' => true
            ],
            'transition is not allowed' => [
                'isAllowed' => true
            ]
        ];
    }

    public function testGetBackTransition()
    {
        $transitionData = $this->createMock(TransitionData::class);
        $workflowItem = new WorkflowItem();

        $this->baseProvider->expects($this->once())
            ->method('getBackTransition')
            ->with($workflowItem)
            ->willReturn($transitionData);

        $this->assertEquals($transitionData, $this->provider->getBackTransition($workflowItem));
    }

    public function testClearCache()
    {
        $this->baseProvider->expects($this->once())
            ->method('clearCache');

        $this->provider->clearCache();
    }
}
