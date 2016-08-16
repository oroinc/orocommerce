<?php

namespace OroB2B\Bundle\CheckoutBundle\Tests\Unit\EventListener;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Persistence\ObjectRepository;

use OroB2B\Bundle\CheckoutBundle\Entity\Checkout;
use OroB2B\Bundle\CheckoutBundle\Entity\Repository\CheckoutWorkflowStateRepository;
use OroB2B\Bundle\CheckoutBundle\EventListener\EntityCheckoutListener;

class EntityCheckoutListenerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var EntityCheckoutListener
     */
    private $listener;

    /**
     * @var CheckoutWorkflowStateRepository|\PHPUnit_Framework_MockObject_MockObject
     */
    private $checkoutWorkflowStateRepository;

    public function setUp()
    {
        $this->checkoutWorkflowStateRepository = $this->getMockBuilder(
            'OroB2B\Bundle\CheckoutBundle\Entity\Repository\CheckoutWorkflowStateRepository'
        )
            ->disableOriginalConstructor()
            ->getMock();

        /** @var ManagerRegistry|\PHPUnit_Framework_MockObject_MockObject $registry */
        $registry = $this->getMock(ManagerRegistry::class);
        $registry
            ->expects($this->once())
            ->method('getRepository')
            ->with('OroB2B\Bundle\CheckoutBundle\Entity\CheckoutWorkflowState')
            ->willReturn($this->checkoutWorkflowStateRepository);

        $this->listener = new EntityCheckoutListener(
            $registry,
            'OroB2B\Bundle\CheckoutBundle\Entity\CheckoutWorkflowState'
        );
    }

    public function tearDown()
    {
        unset($this->listener, $this->checkoutWorkflowStateRepository);
    }

    public function testPreRemove()
    {
        $checkout = new Checkout();

        $this->checkoutWorkflowStateRepository
            ->expects($this->once())
            ->method('deleteEntityStates')
            ->with(
                0,
                'OroB2B\Bundle\CheckoutBundle\Entity\Checkout'
            );

        $this->listener->preRemove($checkout);
    }
}
