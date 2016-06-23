<?php

namespace OroB2B\Bundle\CheckoutBundle\Tests\Unit\Layout\DataProvider;

use Doctrine\Common\Collections\ArrayCollection;

use Symfony\Component\Form\FormFactoryInterface;

use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Model\Transition;
use Oro\Bundle\WorkflowBundle\Model\WorkflowData;

use Oro\Component\Layout\DataProviderInterface;

use OroB2B\Bundle\CheckoutBundle\Entity\Checkout;
use OroB2B\Bundle\CheckoutBundle\Layout\DataProvider\TransitionFormDataProvider;
use OroB2B\Bundle\CheckoutBundle\Model\TransitionData;

class TransitionFormDataProviderTest extends \PHPUnit_Framework_TestCase
{
    use CheckoutAwareContextTrait;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|DataProviderInterface
     */
    protected $continueTransitionDataProvider;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|FormFactoryInterface
     */
    protected $formFactory;

    /**
     * @var TransitionFormDataProvider
     */
    protected $dataProvider;

    protected function setUp()
    {
        $this->continueTransitionDataProvider = $this->getMock('Oro\Component\Layout\DataProviderInterface');
        $this->formFactory = $this->getMock('Symfony\Component\Form\FormFactoryInterface');

        $this->dataProvider = new TransitionFormDataProvider($this->formFactory);
        $this->dataProvider->setContinueTransitionDataProvider($this->continueTransitionDataProvider);
    }

    public function testGetData()
    {
        $workflowData = new WorkflowData();
        $checkout = new Checkout();
        $workflowItem = new WorkflowItem();
        $checkout->setWorkflowItem($workflowItem);

        $context = $this->prepareContext($checkout, $workflowItem);

        $continueTransition = new Transition();
        $continueTransition->setName('transition3');
        $continueTransition->setFormOptions(['attribute_fields' => ['test' => null]]);
        $continueTransition->setFormType('transition_type');

        $transitionData = new TransitionData($continueTransition, true, new ArrayCollection());
        $this->continueTransitionDataProvider->expects($this->once())
            ->method('getData')
            ->with($context)
            ->will($this->returnValue($transitionData));

        $formView = $this->getMock('Symfony\Component\Form\FormView');
        $form = $this->getMock('Symfony\Component\Form\FormInterface');
        $form->expects($this->once())
            ->method('createView')
            ->will($this->returnValue($formView));
        $this->formFactory->expects($this->once())
            ->method('create')
            ->with(
                'transition_type',
                $workflowData,
                [
                    'workflow_item' => $workflowItem,
                    'transition_name' => 'transition3',
                    'attribute_fields' => ['test' => null],
                    'disabled' => false
                ]
            )
            ->will($this->returnValue($form));

        $this->assertSame($formView, $this->dataProvider->getData($context));
    }
}
