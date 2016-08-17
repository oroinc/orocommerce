<?php

namespace OroB2B\Bundle\CheckoutBundle\Tests\Unit\EventListener;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Component\Testing\Unit\EntityTrait;

use OroB2B\Bundle\CheckoutBundle\Entity\Checkout;
use OroB2B\Bundle\CheckoutBundle\Entity\Repository\CheckoutWorkflowStateRepository;
use OroB2B\Bundle\CheckoutBundle\EventListener\RemoveCheckoutWorkflowStatesListener;

class RemoveCheckoutWorkflowStatesListenerTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    const CHECKOUT_WORKFLOW_STATE_CLASS = 'OroB2B\Bundle\CheckoutBundle\Entity\CheckoutWorkflowState';

    /**
     * @var DoctrineHelper|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $doctrineHelper;

    /**
     * @var CheckoutWorkflowStateRepository|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $checkoutWorkflowStateRepository;

    /**
     * @var RemoveCheckoutWorkflowStatesListener
     */
    protected $listener;

    public function setUp()
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

    public function tearDown()
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
                'OroB2B\Bundle\CheckoutBundle\Entity\Checkout'
            );

        $this->listener->preRemove($checkout);
    }
}
