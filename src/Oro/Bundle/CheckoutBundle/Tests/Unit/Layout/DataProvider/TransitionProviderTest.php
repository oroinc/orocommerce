<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\Layout\DataProvider;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\CheckoutBundle\Layout\DataProvider\TransitionProvider;
use Oro\Bundle\CheckoutBundle\Model\TransitionData;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowStep;
use Oro\Bundle\WorkflowBundle\Model\Step;
use Oro\Bundle\WorkflowBundle\Model\Transition;
use Oro\Bundle\WorkflowBundle\Model\WorkflowManager;
use Oro\Bundle\WorkflowBundle\Resolver\TransitionOptionsResolver;

class TransitionProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject|WorkflowManager */
    private $workflowManager;

    /** @var \PHPUnit\Framework\MockObject\MockObject|TransitionOptionsResolver */
    private $optionsResolver;

    /** @var TransitionProvider */
    private $provider;

    protected function setUp(): void
    {
        $this->workflowManager = $this->createMock(WorkflowManager::class);
        $this->optionsResolver = $this->createMock(TransitionOptionsResolver::class);

        $this->provider = new TransitionProvider($this->workflowManager);
    }

    public function testGetBackTransitions()
    {
        /** @var Transition $backTransition */
        /** @var WorkflowItem $workflowItem */
        [$workflowItem, $backTransition] = $this->prepareTestEntities();

        $stepName = $backTransition->getStepTo()->getName();
        $expected = [$stepName => new TransitionData($backTransition, true, new ArrayCollection())];
        $this->assertEquals($expected, $this->provider->getBackTransitions($workflowItem));
    }

    public function testGetContinueTransition()
    {
        $workflowItem = new WorkflowItem();
        $step = new WorkflowStep();
        $workflowItem->setCurrentStep($step);

        $transitionWithoutForm = $this->createTransition('transition1');

        $hiddenTransition = $this->createTransition('transition2')
            ->setFrontendOptions(['is_checkout_continue' => true])
            ->setHidden(true);

        $continueTransition = $this->createTransition('transition3')
            ->setFrontendOptions(['is_checkout_continue' => true])
            ->setFormType('transition_type');

        $transitions = new ArrayCollection([$transitionWithoutForm, $hiddenTransition, $continueTransition]);

        $this->workflowManager->expects($this->any())
            ->method('isTransitionAvailable')
            ->willReturn(true);

        $this->workflowManager->expects($this->once())
            ->method('getTransitionsByWorkflowItem')
            ->with($workflowItem)
            ->willReturn($transitions);

        $expected = new TransitionData($continueTransition, true, new ArrayCollection());
        $this->assertEquals($expected, $this->provider->getContinueTransition($workflowItem));
    }

    public function testGetContinueTransitionWithCache()
    {
        $workflowItem = new WorkflowItem();
        $step = new WorkflowStep();
        $workflowItem->setCurrentStep($step);

        $transitionWithoutForm = $this->createTransition('transition1');

        $continueTransition1 = $this->createTransition('transition3')
            ->setFrontendOptions(['is_checkout_continue' => true])
            ->setFormType('transition_type')
            ->setUnavailableHidden(true);

        $continueTransition2 = $this->createTransition('transition4')
            ->setFrontendOptions(['is_checkout_continue' => true])
            ->setFormType('transition_type')
            ->setUnavailableHidden(true);

        $transitions = new ArrayCollection([$transitionWithoutForm, $continueTransition1, $continueTransition2]);

        $this->workflowManager->expects($this->exactly(2))
            ->method('getTransitionsByWorkflowItem')
            ->with($workflowItem)
            ->willReturn($transitions);

        $errors = new ArrayCollection();
        $this->workflowManager->expects($this->exactly(3))
            ->method('isTransitionAvailable')
            ->withConsecutive(
                [$workflowItem, $continueTransition1, $errors],
                [$workflowItem, $continueTransition1, $errors],
                [$workflowItem, $continueTransition2, $errors]
            )
            ->willReturnOnConsecutiveCalls(
                true,
                false,
                true
            );

        $expected1 = new TransitionData($continueTransition1, true, new ArrayCollection());
        $expected2 = new TransitionData($continueTransition2, true, new ArrayCollection());

        $this->assertEquals($expected1, $this->provider->getContinueTransition($workflowItem));
        $this->provider->clearCache();

        $this->assertEquals($expected2, $this->provider->getContinueTransition($workflowItem));
    }

    public function testGetBackTransition()
    {
        /** @var Transition $backTransition */
        /** @var WorkflowItem $workflowItem */
        [$workflowItem, $backTransition] = $this->prepareTestEntities();

        $expected = new TransitionData($backTransition, true, new ArrayCollection());
        $this->assertEquals($expected, $this->provider->getBackTransition($workflowItem));
    }

    public function testGetBackTransitionNull()
    {
        $workflowItem = new WorkflowItem();
        $step = new WorkflowStep();
        $workflowItem->setCurrentStep($step);

        $this->workflowManager->expects($this->once())
            ->method('getTransitionsByWorkflowItem')
            ->with($workflowItem)
            ->willReturn(new ArrayCollection());

        $this->assertNull($this->provider->getBackTransition($workflowItem));
    }

    private function prepareTestEntities(): array
    {
        $this->workflowManager->expects($this->any())
            ->method('isTransitionAvailable')
            ->willReturn(true);

        $workflowItem = new WorkflowItem();
        $step = new WorkflowStep();
        $workflowItem->setCurrentStep($step);

        $transition = $this->createTransition('transition1');

        $step = new Step();
        $step->setName('to_step');
        $step->setOrder(10);
        $backTransition = $this->createTransition('transition3')
            ->setFrontendOptions(['is_checkout_back' => true])
            ->setStepTo($step);

        $transitions = new ArrayCollection([$transition, $backTransition]);

        $this->workflowManager->expects($this->once())
            ->method('getTransitionsByWorkflowItem')
            ->with($workflowItem)
            ->willReturn($transitions);

        return [$workflowItem, $backTransition];
    }

    private function createTransition(string $name): Transition
    {
        $transition = new Transition($this->optionsResolver);
        $transition->setName($name);

        return $transition;
    }
}
