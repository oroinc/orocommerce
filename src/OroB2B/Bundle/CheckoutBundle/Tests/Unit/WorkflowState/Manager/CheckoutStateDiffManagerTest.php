<?php

namespace OroB2B\Bundle\CheckoutBundle\Tests\Unit\WorkflowState\Manager;

use OroB2B\Bundle\CheckoutBundle\Entity\Checkout;
use OroB2B\Bundle\CheckoutBundle\WorkflowState\Manager\CheckoutStateDiffManager;
use OroB2B\Bundle\CheckoutBundle\WorkflowState\Mapper\CheckoutStateDiffMapperInterface;
use OroB2B\Bundle\CheckoutBundle\WorkflowState\Mapper\ShipToBillingDiffMapper;

class CheckoutStateDiffManagerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var CheckoutStateDiffManager
     */
    private $checkoutStateDiffManager;

    /**
     * @var Checkout|\PHPUnit_Framework_MockObject_MockObject
     */
    private $checkout;

    public function setUp()
    {
        $this->checkoutStateDiffManager = new CheckoutStateDiffManager();

        $this->checkout = $this->getMock('OroB2B\Bundle\CheckoutBundle\Entity\Checkout');
    }


    public function testAddProvider()
    {
        /** @var CheckoutStateDiffMapperInterface|\PHPUnit_Framework_MockObject_MockObject $mapper */
        $mapper = $this->getMock('OroB2B\Bundle\CheckoutBundle\WorkflowState\Mapper\CheckoutStateDiffMapperInterface');

        $this->checkoutStateDiffManager->addMapper($mapper);

        $this->assertAttributeSame(
            [$mapper],
            'mappers',
            $this->checkoutStateDiffManager
        );
    }

    public function testGetCurrentState()
    {
        $this->checkoutStateDiffManager->addMapper(new ShipToBillingDiffMapper());
        $this->checkout->method('isShipToBillingAddress')->willReturn(true);

        $result = $this->checkoutStateDiffManager->getCurrentState($this->checkout);

        $this->assertEquals(
            [
                'shipToBillingAddress' => true,
            ],
            $result
        );
    }

    public function testGetCurrentStateUnsopportedEntity()
    {
        $this->checkoutStateDiffManager->addMapper(new ShipToBillingDiffMapper());
        $entity = new \stdClass();

        $result = $this->checkoutStateDiffManager->getCurrentState($entity);

        $this->assertEquals(
            [],
            $result
        );
    }

    public function testCompareStates()
    {
        $savedState = [
            'shipToBillingAddress' => true,
        ];
        $this->checkoutStateDiffManager->addMapper(new ShipToBillingDiffMapper());
        $this->checkout->method('isShipToBillingAddress')->willReturn(true);

        $this->assertEquals(true, $this->checkoutStateDiffManager->compareStates($this->checkout, $savedState));
    }

    public function testCompareStatesFalse()
    {
        $savedState = [
            'shipToBillingAddress' => false,
        ];
        $this->checkoutStateDiffManager->addMapper(new ShipToBillingDiffMapper());
        $this->checkout->method('isShipToBillingAddress')->willReturn(true);

        $this->assertEquals(false, $this->checkoutStateDiffManager->compareStates($this->checkout, $savedState));
    }

    public function testCompareStatesUnsupportedEntity()
    {
        $savedState = [
            'shipToBillingAddress' => true,
        ];
        $this->checkoutStateDiffManager->addMapper(new ShipToBillingDiffMapper());
        $entity = new \stdClass();

        $this->assertEquals(true, $this->checkoutStateDiffManager->compareStates($entity, $savedState));
    }
}
