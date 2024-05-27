<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\EventListener;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\CheckoutBundle\Event\CheckoutTransitionAfterEvent;
use Oro\Bundle\CheckoutBundle\EventListener\CheckoutTransitionPurchase;
use Oro\Bundle\CustomerBundle\Security\Token\AnonymousCustomerUserToken;
use Oro\Bundle\SecurityBundle\Authentication\Token\UsernamePasswordOrganizationToken;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Model\Transition;
use Oro\Bundle\WorkflowBundle\Model\WorkflowManager;
use Oro\Bundle\WorkflowBundle\Model\WorkflowResult;
use Oro\Bundle\WorkflowBundle\Resolver\TransitionOptionsResolver;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class CheckoutTransitionPurchaseTest extends TestCase
{
    private CheckoutTransitionPurchase $listener;
    private WorkflowManager|MockObject $manager;
    private TokenStorageInterface|MockObject $tokenStorage;

    protected function setUp(): void
    {
        $this->manager = self::createMock(WorkflowManager::class);
        $this->tokenStorage = self::createMock(TokenStorageInterface::class);

        $this->listener = new CheckoutTransitionPurchase($this->manager, $this->tokenStorage, 'final_transition');
    }

    public function testGetSubscribedEvents(): void
    {
        self::assertEquals(
            [CheckoutTransitionAfterEvent::class => 'onAfter'],
            CheckoutTransitionPurchase::getSubscribedEvents()
        );
    }

    public function testTransitForAnonymousUser(): void
    {
        $transitionOptionsResolver = $this->createMock(TransitionOptionsResolver::class);
        $transition = new Transition($transitionOptionsResolver);

        $workflowItem = new WorkflowItem();
        $event = new CheckoutTransitionAfterEvent($workflowItem, $transition, true, new ArrayCollection([]));

        $token = self::createMock(AnonymousCustomerUserToken::class);
        $this->tokenStorage
            ->expects(self::once())
            ->method('getToken')
            ->willReturn($token);

        $this->manager
            ->expects(self::never())
            ->method('transitIfAllowed');

        $this->listener->onAfter($event);
    }

    public function testTransitIfPaymentNoSuccessful(): void
    {
        $transitionOptionsResolver = $this->createMock(TransitionOptionsResolver::class);
        $transition = new Transition($transitionOptionsResolver);

        $workflowResult = new WorkflowResult(['responseData' => ['successful' => false]]);

        $workflowItem = self::createMock(WorkflowItem::class);
        $workflowItem
            ->expects(self::once())
            ->method('getResult')
            ->willReturn($workflowResult);

        $event = new CheckoutTransitionAfterEvent($workflowItem, $transition, true, new ArrayCollection([]));

        $token = $this->createMock(UsernamePasswordOrganizationToken::class);
        $this->tokenStorage
            ->expects(self::once())
            ->method('getToken')
            ->willReturn($token);

        $this->manager
            ->expects(self::never())
            ->method('transitIfAllowed');

        $this->listener->onAfter($event);
    }

    public function testTransitIfUserValidAndPaymentSuccessful(): void
    {
        $transitionOptionsResolver = $this->createMock(TransitionOptionsResolver::class);
        $transition = new Transition($transitionOptionsResolver);

        $workflowResult = new WorkflowResult(['responseData' => ['successful' => true]]);

        $workflowItem = self::createMock(WorkflowItem::class);
        $workflowItem
            ->expects(self::once())
            ->method('getResult')
            ->willReturn($workflowResult);

        $event = new CheckoutTransitionAfterEvent($workflowItem, $transition, true, new ArrayCollection([]));

        $token = $this->createMock(UsernamePasswordOrganizationToken::class);
        $this->tokenStorage
            ->expects(self::once())
            ->method('getToken')
            ->willReturn($token);

        $this->manager
            ->expects(self::once())
            ->method('transitIfAllowed')
            ->with($workflowItem, 'final_transition');

        $this->listener->onAfter($event);
    }
}
