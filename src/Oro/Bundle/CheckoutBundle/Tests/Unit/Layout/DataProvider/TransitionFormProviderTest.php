<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\Layout\DataProvider;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\CheckoutBundle\Layout\DataProvider\TransitionFormProvider;
use Oro\Bundle\CheckoutBundle\Layout\DataProvider\TransitionProvider;
use Oro\Bundle\CheckoutBundle\Layout\Provider\CheckoutThemeBCProvider;
use Oro\Bundle\CheckoutBundle\Model\TransitionData;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Event\EventDispatcher;
use Oro\Bundle\WorkflowBundle\Model\Transition;
use Oro\Bundle\WorkflowBundle\Model\WorkflowData;
use Oro\Bundle\WorkflowBundle\Resolver\TransitionOptionsResolver;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class TransitionFormProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var TransitionProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $transitionProvider;

    /** @var CheckoutThemeBCProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $checkoutThemeBCProvider;

    /** @var \PHPUnit\Framework\MockObject\MockObject|FormFactoryInterface */
    private $formFactory;

    /** @var TransitionFormProvider */
    private $provider;

    #[\Override]
    protected function setUp(): void
    {
        $this->checkoutThemeBCProvider = $this->createMock(CheckoutThemeBCProvider::class);
        $this->transitionProvider = $this->createMock(TransitionProvider::class);
        $this->formFactory = $this->createMock(FormFactoryInterface::class);
        $router = $this->createMock(UrlGeneratorInterface::class);

        $this->provider = new TransitionFormProvider($this->formFactory, $router);
        $this->provider->setTransitionProvider($this->transitionProvider);
        $this->provider->setThemeBCProvider($this->checkoutThemeBCProvider);
    }

    public function testGetTransitionFormView(): void
    {
        $workflowData = new WorkflowData();
        $workflowItem = new WorkflowItem();
        $workflowItem->setData($workflowData);
        $optionsResolver = $this->createMock(TransitionOptionsResolver::class);

        $continueTransition = new Transition(
            $optionsResolver,
            $this->createMock(EventDispatcher::class),
            $this->createMock(TranslatorInterface::class)
        );
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
                    'csrf_protection' => false
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

        $transition = new Transition(
            $optionsResolver,
            $this->createMock(EventDispatcher::class),
            $this->createMock(TranslatorInterface::class)
        );
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
                    'csrf_protection' => false
                ]
            )
            ->willReturn($form);

        $this->assertSame($form, $this->provider->getTransitionFormByTransition($workflowItem, $transition));
    }

    public function testGetTransitionFormByTransitionWhenNoForm(): void
    {
        $transition = new Transition(
            $this->createMock(TransitionOptionsResolver::class),
            $this->createMock(EventDispatcher::class),
            $this->createMock(TranslatorInterface::class)
        );
        $transition->setName('transition3');

        $this->formFactory
            ->expects($this->never())
            ->method('create');

        $this->assertNull($this->provider->getTransitionFormByTransition(new WorkflowItem(), $transition));
    }
}
