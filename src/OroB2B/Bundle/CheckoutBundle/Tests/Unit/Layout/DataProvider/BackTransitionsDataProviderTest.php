<?php

namespace OroB2B\Bundle\CheckoutBundle\Tests\Unit\Layout\DataProvider;

use Doctrine\Common\Collections\ArrayCollection;

use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowStep;
use Oro\Bundle\WorkflowBundle\Model\Step;
use Oro\Bundle\WorkflowBundle\Model\Transition;

use OroB2B\Bundle\CheckoutBundle\Entity\Checkout;
use OroB2B\Bundle\CheckoutBundle\Layout\DataProvider\BackTransitionsDataProvider;
use OroB2B\Bundle\CheckoutBundle\Model\TransitionData;

class BackTransitionsDataProviderTest extends AbstractTransitionDataProviderTest
{
    /**
     * @var BackTransitionsDataProvider
     */
    protected $dataProvider;

    protected function setUp()
    {
        parent::setUp();

        $this->dataProvider = new BackTransitionsDataProvider($this->workflowManager);
    }

    public function testGetData()
    {
        $checkout = new Checkout();
        $workflowItem = new WorkflowItem();
        $step = new WorkflowStep();
        $workflowItem->setCurrentStep($step);
        $context = $this->prepareContext($checkout, $workflowItem);

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
        $this->assertEquals($expected, $this->dataProvider->getData($context));
    }
}
