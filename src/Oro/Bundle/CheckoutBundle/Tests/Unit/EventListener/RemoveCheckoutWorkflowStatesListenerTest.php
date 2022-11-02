<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\EventListener;

use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Entity\Repository\CheckoutWorkflowStateRepository;
use Oro\Bundle\CheckoutBundle\EventListener\RemoveCheckoutWorkflowStatesListener;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Component\Testing\Unit\EntityTrait;

class RemoveCheckoutWorkflowStatesListenerTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    const CHECKOUT_WORKFLOW_STATE_CLASS = 'Oro\Bundle\CheckoutBundle\Entity\CheckoutWorkflowState';

    /**
     * @var DoctrineHelper|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $doctrineHelper;

    /**
     * @var CheckoutWorkflowStateRepository|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $checkoutWorkflowStateRepository;

    /**
     * @var RemoveCheckoutWorkflowStatesListener
     */
    protected $listener;

    protected function setUp(): void
    {
        $this->doctrineHelper = $this->getMockBuilder(DoctrineHelper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->checkoutWorkflowStateRepository = $this->getMockBuilder(CheckoutWorkflowStateRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->listener = new RemoveCheckoutWorkflowStatesListener(
            $this->doctrineHelper,
            self::CHECKOUT_WORKFLOW_STATE_CLASS
        );
    }

    protected function tearDown(): void
    {
        unset($this->listener, $this->checkoutWorkflowStateRepository, $this->doctrineHelper);
    }

    public function testPreRemove()
    {
        $this->doctrineHelper
            ->expects($this->once())
            ->method('getEntityRepositoryForClass')
            ->with(self::CHECKOUT_WORKFLOW_STATE_CLASS)
            ->willReturn($this->checkoutWorkflowStateRepository);

        $checkout = $this->getEntity(Checkout::class, ['id' => 1]);

        $this->checkoutWorkflowStateRepository
            ->expects($this->once())
            ->method('deleteEntityStates')
            ->with(
                1,
                'Oro\Bundle\CheckoutBundle\Entity\Checkout'
            );

        $this->listener->preRemove($checkout);
    }
}
