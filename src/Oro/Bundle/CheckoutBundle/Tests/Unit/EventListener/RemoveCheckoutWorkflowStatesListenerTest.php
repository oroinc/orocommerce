<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\EventListener;

use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Entity\CheckoutWorkflowState;
use Oro\Bundle\CheckoutBundle\Entity\Repository\CheckoutWorkflowStateRepository;
use Oro\Bundle\CheckoutBundle\EventListener\RemoveCheckoutWorkflowStatesListener;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Component\Testing\Unit\EntityTrait;

class RemoveCheckoutWorkflowStatesListenerTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    private const CHECKOUT_WORKFLOW_STATE_CLASS = CheckoutWorkflowState::class;

    /** @var DoctrineHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrineHelper;

    /** @var CheckoutWorkflowStateRepository|\PHPUnit\Framework\MockObject\MockObject */
    private $checkoutWorkflowStateRepository;

    /** @var RemoveCheckoutWorkflowStatesListener */
    private $listener;

    protected function setUp(): void
    {
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->checkoutWorkflowStateRepository = $this->createMock(CheckoutWorkflowStateRepository::class);

        $this->listener = new RemoveCheckoutWorkflowStatesListener(
            $this->doctrineHelper,
            self::CHECKOUT_WORKFLOW_STATE_CLASS
        );
    }

    public function testPreRemove()
    {
        $this->doctrineHelper->expects($this->once())
            ->method('getEntityRepositoryForClass')
            ->with(self::CHECKOUT_WORKFLOW_STATE_CLASS)
            ->willReturn($this->checkoutWorkflowStateRepository);

        $checkout = $this->getEntity(Checkout::class, ['id' => 1]);

        $this->checkoutWorkflowStateRepository->expects($this->once())
            ->method('deleteEntityStates')
            ->with(1, Checkout::class);

        $this->listener->preRemove($checkout);
    }
}
