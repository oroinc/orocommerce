<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\Layout\DataProvider;

use Doctrine\Common\Collections\ArrayCollection;

use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowStep;
use Oro\Bundle\WorkflowBundle\Model\Step;
use Oro\Bundle\WorkflowBundle\Model\Transition;
use Oro\Bundle\WorkflowBundle\Model\WorkflowManager;
use Oro\Bundle\CheckoutBundle\Layout\DataProvider\TransitionProvider;
use Oro\Bundle\CheckoutBundle\Model\TransitionData;

class TransitionDataProviderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|WorkflowManager
     */
    protected $workflowManager;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|TransitionProvider
     */
    protected $provider;

    protected function setUp()
    {
        $this->workflowManager = $this->getMockBuilder('Oro\Bundle\WorkflowBundle\Model\WorkflowManager')
            ->disableOriginalConstructor()
            ->getMock();
        $this->workflowManager->expects($this->any())
            ->method('isTransitionAvailable')
            ->willReturn(true);

        $this->provider = new TransitionProvider($this->workflowManager);
    }

    public function testGetBackTransitions()
    {
        $workflowItem = new WorkflowItem();
        $step = new WorkflowStep();
        $workflowItem->setCurrentStep($step);

        $transition = new Transition();
        $transition->setName('transition1');

        $step = new Step();
        $step->setName('to_step');
        $step->setOrder(10);
        $backTransition = new Transition();
        $backTransition->setName('transition3');
        $backTransition->setFrontendOptions(['is_checkout_back' => true]);
        $backTransition->setStepTo($step);

        $transitions = [
            $transition,
            $backTransition
        ];

        $this->workflowManager->expects($this->once())
            ->method('getTransitionsByWorkflowItem')
            ->with($workflowItem)
            ->will($this->returnValue($transitions));

        $expected = [$step->getName() => new TransitionData($backTransition, true, new ArrayCollection())];
        $this->assertEquals($expected, $this->provider->getBackTransitions($workflowItem));
    }

    public function testGetContinueTransition()
    {
        $workflowItem = new WorkflowItem();
        $step = new WorkflowStep();
        $workflowItem->setCurrentStep($step);

        $transitionWithoutForm = new Transition();
        $transitionWithoutForm->setName('transition1');

        $continueTransition = new Transition();
        $continueTransition->setName('transition3');
        $continueTransition->setFrontendOptions(['is_checkout_continue' => true]);
        $continueTransition->setFormType('transition_type');

        $transitions = [
            $transitionWithoutForm,
            $continueTransition
        ];

        $this->workflowManager->expects($this->once())
            ->method('getTransitionsByWorkflowItem')
            ->with($workflowItem)
            ->will($this->returnValue($transitions));

        $expected = new TransitionData($continueTransition, true, new ArrayCollection());
        $this->assertEquals($expected, $this->provider->getContinueTransition($workflowItem));
    }

    public function testGetBackTransition()
    {
        $workflowItem = new WorkflowItem();
        $step = new WorkflowStep();
        $workflowItem->setCurrentStep($step);

        $transition = new Transition();
        $transition->setName('transition1');

        $step = new Step();
        $step->setName('to_step');
        $step->setOrder(10);
        $backTransition = new Transition();
        $backTransition->setName('transition3');
        $backTransition->setFrontendOptions(['is_checkout_back' => true]);
        $backTransition->setStepTo($step);

        $transitions = [
            $transition,
            $backTransition
        ];

        $this->workflowManager->expects($this->once())
            ->method('getTransitionsByWorkflowItem')
            ->with($workflowItem)
            ->will($this->returnValue($transitions));

        $expected = new TransitionData($backTransition, true, new ArrayCollection());
        $this->assertEquals($expected, $this->provider->getBackTransition($workflowItem));
    }

    public function testGetBackTransitionNull()
    {
        $workflowItem = new WorkflowItem();
        $step = new WorkflowStep();
        $workflowItem->setCurrentStep($step);

        $transitions = [];

        $this->workflowManager->expects($this->once())
            ->method('getTransitionsByWorkflowItem')
            ->with($workflowItem)
            ->will($this->returnValue($transitions));

        $this->assertNull($this->provider->getBackTransition($workflowItem));
    }
}
