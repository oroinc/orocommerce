<?php

namespace OroB2B\Bundle\CheckoutBundle\Tests\Unit\Layout\DataProvider;

use Doctrine\Common\Collections\ArrayCollection;

use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowStep;
use Oro\Bundle\WorkflowBundle\Model\Transition;

use OroB2B\Bundle\CheckoutBundle\Entity\Checkout;
use OroB2B\Bundle\CheckoutBundle\Layout\DataProvider\ContinueTransitionDataProvider;
use OroB2B\Bundle\CheckoutBundle\Model\TransitionData;

class ContinueTransitionDataProviderTest extends AbstractTransitionDataProviderTest
{
    /**
     * @var ContinueTransitionDataProvider
     */
    protected $dataProvider;

    protected function setUp()
    {
        parent::setUp();

        $this->dataProvider = new ContinueTransitionDataProvider($this->workflowManager);
    }

    public function testGetData()
    {
        $checkout = new Checkout();
        $workflowItem = new WorkflowItem();
        $step = new WorkflowStep();
        $workflowItem->setCurrentStep($step);
        $context = $this->prepareContext($checkout, $workflowItem);

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
        $this->assertEquals($expected, $this->dataProvider->getData($context));
    }
}
