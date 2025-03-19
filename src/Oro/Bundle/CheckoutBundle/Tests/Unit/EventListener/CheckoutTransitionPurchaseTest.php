<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\EventListener;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\CheckoutBundle\Event\CheckoutTransitionAfterEvent;
use Oro\Bundle\CheckoutBundle\EventListener\CheckoutTransitionPurchase;
use Oro\Bundle\CustomerBundle\Security\Token\AnonymousCustomerUserToken;
use Oro\Bundle\SecurityBundle\Authentication\Token\UsernamePasswordOrganizationToken;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Event\EventDispatcher;
use Oro\Bundle\WorkflowBundle\Model\Transition;
use Oro\Bundle\WorkflowBundle\Model\WorkflowManager;
use Oro\Bundle\WorkflowBundle\Model\WorkflowResult;
use Oro\Bundle\WorkflowBundle\Resolver\TransitionOptionsResolver;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class CheckoutTransitionPurchaseTest extends TestCase
{
    private CheckoutTransitionPurchase $listener;
    private WorkflowManager|MockObject $manager;
    private TokenStorageInterface|MockObject $tokenStorage;

    protected function setUp(): void
    {
        $this->manager = self::createMock(WorkflowManager::class);
        $this->tokenStorage = self::createMock(TokenStorageInterface::class);

        $this->listener = new CheckoutTransitionPurchase($this->manager, $this->tokenStorage, 'verify_payment');
    }

    public function testTransitForAnonymousUser(): void
    {
        $eventDispatcher = self::createMock(EventDispatcher::class);

        $transitionOptionsResolver = self::createMock(TransitionOptionsResolver::class);
        $translator = $this->createMock(TranslatorInterface::class);
        $transition = new Transition($transitionOptionsResolver, $eventDispatcher, $translator);

        $workflowItem = new WorkflowItem();
        $event = new CheckoutTransitionAfterEvent($workflowItem, $transition, true, new ArrayCollection([]));

        $token = self::createMock(AnonymousCustomerUserToken::class);
        $this->tokenStorage->expects(self::once())
            ->method('getToken')
            ->willReturn($token);

        $this->manager->expects(self::never())
            ->method('transitIfAllowed');

        $this->listener->onAfter($event);
    }

    public function testTransitIfPaymentNoSuccessful(): void
    {
        $eventDispatcher = self::createMock(EventDispatcher::class);

        $transitionOptionsResolver = self::createMock(TransitionOptionsResolver::class);
        $translator = $this->createMock(TranslatorInterface::class);
        $transition = new Transition($transitionOptionsResolver, $eventDispatcher, $translator);

        $workflowResult = new WorkflowResult(['responseData' => ['successful' => false]]);

        $workflowItem = self::createMock(WorkflowItem::class);
        $workflowItem->expects(self::once())
            ->method('getResult')
            ->willReturn($workflowResult);

        $event = new CheckoutTransitionAfterEvent($workflowItem, $transition, true, new ArrayCollection([]));

        $token = self::createMock(UsernamePasswordOrganizationToken::class);
        $this->tokenStorage->expects(self::once())
            ->method('getToken')
            ->willReturn($token);

        $this->manager->expects(self::never())
            ->method('transitIfAllowed');

        $this->listener->onAfter($event);
    }

    public function testTransitIfUserValidAndPaymentSuccessful(): void
    {
        $eventDispatcher = self::createMock(EventDispatcher::class);

        $transitionOptionsResolver = self::createMock(TransitionOptionsResolver::class);
        $translator = $this->createMock(TranslatorInterface::class);
        $transition = new Transition($transitionOptionsResolver, $eventDispatcher, $translator);

        $workflowResult = new WorkflowResult(['responseData' => ['successful' => true]]);

        $workflowItem = self::createMock(WorkflowItem::class);
        $workflowItem->expects(self::once())
            ->method('getResult')
            ->willReturn($workflowResult);

        $event = new CheckoutTransitionAfterEvent($workflowItem, $transition, true, new ArrayCollection([]));

        $token = self::createMock(UsernamePasswordOrganizationToken::class);
        $this->tokenStorage->expects(self::once())
            ->method('getToken')
            ->willReturn($token);

        $this->manager->expects(self::once())
            ->method('transitIfAllowed')
            ->with($workflowItem, 'verify_payment');

        $this->listener->onAfter($event);
    }
}
