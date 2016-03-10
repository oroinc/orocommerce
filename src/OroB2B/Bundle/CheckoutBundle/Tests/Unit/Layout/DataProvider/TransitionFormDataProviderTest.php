<?php

namespace OroB2B\Bundle\CheckoutBundle\Tests\Unit\Layout\DataProvider;

use Symfony\Component\Form\FormFactoryInterface;

use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Model\Transition;
use Oro\Bundle\WorkflowBundle\Model\WorkflowData;
use Oro\Component\Layout\ContextInterface;
use Oro\Component\Layout\DataProviderInterface;

use OroB2B\Bundle\CheckoutBundle\Entity\Checkout;
use OroB2B\Bundle\CheckoutBundle\Layout\DataProvider\TransitionFormDataProvider;

class TransitionFormDataProviderTest extends \PHPUnit_Framework_TestCase
{
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
        $workflowItem->setData($workflowData);
        $checkout->setWorkflowItem($workflowItem);

        /** @var \PHPUnit_Framework_MockObject_MockObject|ContextInterface $context */
        $context = $this->getMock('Oro\Component\Layout\ContextInterface');

        $data = $this->getMockBuilder('Oro\Component\Layout\ContextDataCollection')
            ->disableOriginalConstructor()
            ->getMock();

        $data->expects($this->once())
            ->method('get')
            ->with('checkout')
            ->will($this->returnValue($checkout));
        $context->expects($this->once())
            ->method('data')
            ->will($this->returnValue($data));

        $continueTransition = new Transition();
        $continueTransition->setName('transition3');
        $continueTransition->setFormOptions(['attribute_fields' => ['test' => null]]);
        $continueTransition->setFormType('transition_type');

        $this->continueTransitionDataProvider->expects($this->once())
            ->method('getData')
            ->with($context)
            ->will($this->returnValue($continueTransition));

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
                ]
            )
            ->will($this->returnValue($form));

        $this->assertSame($formView, $this->dataProvider->getData($context));
    }
}
