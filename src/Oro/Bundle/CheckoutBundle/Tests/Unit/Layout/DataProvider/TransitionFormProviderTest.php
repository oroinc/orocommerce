<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\Layout\DataProvider;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\CheckoutBundle\Layout\DataProvider\TransitionFormProvider;
use Oro\Bundle\CheckoutBundle\Layout\DataProvider\TransitionProvider;
use Oro\Bundle\CheckoutBundle\Model\TransitionData;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Model\Transition;
use Oro\Bundle\WorkflowBundle\Model\WorkflowData;
use Oro\Bundle\WorkflowBundle\Resolver\TransitionOptionsResolver;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class TransitionFormProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var TransitionProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $transitionProvider;

    /** @var \PHPUnit\Framework\MockObject\MockObject|FormFactoryInterface */
    private $formFactory;

    /** @var TransitionFormProvider */
    private $provider;

    protected function setUp(): void
    {
        $this->transitionProvider = $this->createMock(TransitionProvider::class);
        $this->formFactory = $this->createMock(FormFactoryInterface::class);
        $router = $this->createMock(UrlGeneratorInterface::class);

        $this->provider = new TransitionFormProvider($this->formFactory, $router);
        $this->provider->setTransitionProvider($this->transitionProvider);
    }

    public function testGetTransitionFormView(): void
    {
        $workflowData = new WorkflowData();
        $workflowItem = new WorkflowItem();
        $workflowItem->setData($workflowData);
        $optionsResolver = $this->createMock(TransitionOptionsResolver::class);

        $continueTransition = new Transition($optionsResolver);
        $continueTransition->setName('transition3');
        $continueTransition->setFormOptions(['attribute_fields' => ['test' => null]]);
        $continueTransition->setFormType('transition_type');

        $transitionData = new TransitionData($continueTransition, true, new ArrayCollection());
        $this->transitionProvider->expects($this->once())
            ->method('getContinueTransition')
            ->with($workflowItem)
            ->willReturn($transitionData);

        $formView = $this->createMock(FormView::class);
        $form = $this->createMock(FormInterface::class);
        $form->expects($this->once())
            ->method('createView')
            ->willReturn($formView);
        $this->formFactory->expects($this->once())
            ->method('create')
            ->with(
                'transition_type',
                $workflowData,
                [
                    'workflow_item' => $workflowItem,
                    'transition_name' => 'transition3',
                    'attribute_fields' => ['test' => null],
                    'allow_extra_fields' => true,
                ]
            )
            ->willReturn($form);

        $this->assertSame($formView, $this->provider->getTransitionFormView($workflowItem));
    }

    public function testGetTransitionFormByTransition(): void
    {
        $workflowData = new WorkflowData();
        $workflowItem = new WorkflowItem();
        $workflowItem->setData($workflowData);
        $optionsResolver = $this->createMock(TransitionOptionsResolver::class);

        $transition = new Transition($optionsResolver);
        $transition->setName('transition3');
        $transition->setFormOptions(['attribute_fields' => ['test' => null]]);
        $transition->setFormType('transition_type');

        $form = $this->createMock(FormInterface::class);
        $this->formFactory
            ->expects($this->once())
            ->method('create')
            ->with(
                'transition_type',
                $workflowData,
                [
                    'workflow_item' => $workflowItem,
                    'transition_name' => 'transition3',
                    'attribute_fields' => ['test' => null],
                    'allow_extra_fields' => true,
                ]
            )
            ->willReturn($form);

        $this->assertSame($form, $this->provider->getTransitionFormByTransition($workflowItem, $transition));
    }

    public function testGetTransitionFormByTransitionWhenNoForm(): void
    {
        $transition = new Transition($this->createMock(TransitionOptionsResolver::class));
        $transition->setName('transition3');

        $this->formFactory
            ->expects($this->never())
            ->method('create');

        $this->assertNull($this->provider->getTransitionFormByTransition(new WorkflowItem(), $transition));
    }
}
