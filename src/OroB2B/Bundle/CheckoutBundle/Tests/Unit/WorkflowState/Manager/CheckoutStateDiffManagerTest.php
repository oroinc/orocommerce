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


    public function testAddMapper()
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

    public function testGetMappers()
    {
        /** @var CheckoutStateDiffMapperInterface|\PHPUnit_Framework_MockObject_MockObject $mapper1 */
        $mapper1 = $this->getMock('OroB2B\Bundle\CheckoutBundle\WorkflowState\Mapper\CheckoutStateDiffMapperInterface');
        $mapper1->method('getPriority')->willReturn(20);

        /** @var CheckoutStateDiffMapperInterface|\PHPUnit_Framework_MockObject_MockObject $mapper2 */
        $mapper2 = $this->getMock('OroB2B\Bundle\CheckoutBundle\WorkflowState\Mapper\CheckoutStateDiffMapperInterface');
        $mapper2->method('getPriority')->willReturn(10);

        /** @var CheckoutStateDiffMapperInterface|\PHPUnit_Framework_MockObject_MockObject $mapper3 */
        $mapper3 = $this->getMock('OroB2B\Bundle\CheckoutBundle\WorkflowState\Mapper\CheckoutStateDiffMapperInterface');
        $mapper3->method('getPriority')->willReturn(10);

        $reflectionClasss = new \ReflectionClass(
            'OroB2B\Bundle\CheckoutBundle\WorkflowState\Manager\CheckoutStateDiffManager'
        );
        $getMappersMethod = $reflectionClasss->getMethod('getMappers');
        $getMappersMethod->setAccessible(true);

        $this->checkoutStateDiffManager->addMapper($mapper1);
        $this->checkoutStateDiffManager->addMapper($mapper2);
        $this->checkoutStateDiffManager->addMapper($mapper3);

        $result = $getMappersMethod->invoke($this->checkoutStateDiffManager);

        $this->assertEquals([
            0 => $mapper2,
            1 => $mapper3,
            2 => $mapper1,
        ], $result);
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
