<?php

namespace OroB2B\Bundle\CheckoutBundle\Tests\Unit\WorkflowState\Manager;

use OroB2B\Bundle\CheckoutBundle\Entity\Checkout;
use OroB2B\Bundle\CheckoutBundle\WorkflowState\Manager\CheckoutStateDiffManager;
use OroB2B\Bundle\CheckoutBundle\WorkflowState\Mapper\CheckoutStateDiffMapperInterface;
use OroB2B\Bundle\CheckoutBundle\WorkflowState\Mapper\CheckoutStateDiffMapperRegistry;
use OroB2B\Bundle\CheckoutBundle\WorkflowState\Mapper\ShipToBillingDiffMapper;

class CheckoutStateDiffManagerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var CheckoutStateDiffManager
     */
    private $checkoutStateDiffManager;

    /**
     * @var CheckoutStateDiffMapperRegistry
     */
    private $mapperRegistry;

    /**
     * @var Checkout|\PHPUnit_Framework_MockObject_MockObject
     */
    private $checkout;

    public function setUp()
    {
        $this->mapperRegistry = new CheckoutStateDiffMapperRegistry();
        $this->checkoutStateDiffManager = new CheckoutStateDiffManager($this->mapperRegistry);
        $this->checkout = $this->getMock('OroB2B\Bundle\CheckoutBundle\Entity\Checkout');
    }

    /**
     * @return CheckoutStateDiffMapperInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private function getMapperMock()
    {
        return $this->getMock('OroB2B\Bundle\CheckoutBundle\WorkflowState\Mapper\CheckoutStateDiffMapperInterface');
    }

    public function testGetCurrentState()
    {
        $mapper1 = $this->getMapperMock();
        $mapper1
            ->expects($this->any())
            ->method('getName')
            ->willReturn('mapperName1');
        $mapper1
            ->expects($this->once())
            ->method('isEntitySupported')
            ->with($this->checkout)
            ->willReturn(true);
        $mapper1
            ->expects($this->once())
            ->method('getCurrentState')
            ->with($this->checkout)
            ->willReturn(true);

        $mapper2 = $this->getMapperMock();
        $mapper2
            ->expects($this->any())
            ->method('getName')
            ->willReturn('mapperName2');
        $mapper2
            ->expects($this->once())
            ->method('isEntitySupported')
            ->with($this->checkout)
            ->willReturn(true);
        $mapper2
            ->expects($this->once())
            ->method('getCurrentState')
            ->with($this->checkout)
            ->willReturn([
                'parameter1' => 7635,
                'parameter2' => 'test value',
            ]);

        $this->mapperRegistry->addMapper($mapper1);
        $this->mapperRegistry->addMapper($mapper2);

        $result = $this->checkoutStateDiffManager->getCurrentState($this->checkout);

        $this->assertEquals(
            [
                'mapperName1' => true,
                'mapperName2' => [
                    'parameter1' => 7635,
                    'parameter2' => 'test value',
                ],
            ],
            $result
        );
    }

    public function testGetCurrentStateUnsupportedEntity()
    {
        $mapper1 = $this->getMapperMock();
        $mapper1
            ->expects($this->any())
            ->method('getName')
            ->willReturn('mapperName1');
        $mapper1
            ->expects($this->once())
            ->method('isEntitySupported')
            ->with($this->checkout)
            ->willReturn(true);
        $mapper1
            ->expects($this->once())
            ->method('getCurrentState')
            ->with($this->checkout)
            ->willReturn(true);

        $mapper2 = $this->getMapperMock();
        $mapper2
            ->expects($this->any())
            ->method('getName')
            ->willReturn('mapperName2');
        $mapper2
            ->expects($this->once())
            ->method('isEntitySupported')
            ->with($this->checkout)
            ->willReturn(false);
        $mapper2
            ->expects($this->any())
            ->method('getCurrentState')
            ->with($this->checkout)
            ->willReturn([
                'parameter1' => 7635,
                'parameter2' => 'test value',
            ]);

        $this->mapperRegistry->addMapper($mapper1);
        $this->mapperRegistry->addMapper($mapper2);

        $result = $this->checkoutStateDiffManager->getCurrentState($this->checkout);

        $this->assertEquals(
            [
                'mapperName1' => true,
            ],
            $result
        );
    }

    public function testIsStateActual()
    {
        $savedState = [
            'mapperName1' => true,
            'mapperName2' => [
                'parameter1' => 7635,
                'parameter2' => 'test value',
            ],
        ];

        $mapper1 = $this->getMapperMock();
        $mapper1
            ->expects($this->any())
            ->method('getName')
            ->willReturn('mapperName1');
        $mapper1
            ->expects($this->once())
            ->method('isEntitySupported')
            ->with($this->checkout)
            ->willReturn(true);
        $mapper1
            ->expects($this->once())
            ->method('isStateActual')
            ->with($this->checkout, $savedState)
            ->willReturn(true);

        $mapper2 = $this->getMapperMock();
        $mapper2
            ->expects($this->any())
            ->method('getName')
            ->willReturn('mapperName2');
        $mapper2
            ->expects($this->once())
            ->method('isEntitySupported')
            ->with($this->checkout)
            ->willReturn(true);
        $mapper2
            ->expects($this->once())
            ->method('isStateActual')
            ->with($this->checkout, $savedState)
            ->willReturn(true);

        $this->mapperRegistry->addMapper($mapper1);
        $this->mapperRegistry->addMapper($mapper2);

        $this->assertEquals(true, $this->checkoutStateDiffManager->isStateActual($this->checkout, $savedState));
    }

    public function testIsStateActualFalse()
    {
        $savedState = [
            'mapperName1' => true,
            'mapperName2' => [
                'parameter1' => 7635,
                'parameter2' => 'test value',
            ],
        ];

        $mapper1 = $this->getMapperMock();
        $mapper1
            ->expects($this->any())
            ->method('getName')
            ->willReturn('mapperName1');
        $mapper1
            ->expects($this->once())
            ->method('isEntitySupported')
            ->with($this->checkout)
            ->willReturn(true);
        $mapper1
            ->expects($this->once())
            ->method('isStateActual')
            ->with($this->checkout, $savedState)
            ->willReturn(true);

        $mapper2 = $this->getMapperMock();
        $mapper2
            ->expects($this->any())
            ->method('getName')
            ->willReturn('mapperName2');
        $mapper2
            ->expects($this->once())
            ->method('isEntitySupported')
            ->with($this->checkout)
            ->willReturn(true);
        $mapper2
            ->expects($this->once())
            ->method('isStateActual')
            ->with($this->checkout, $savedState)
            ->willReturn(false);

        $this->mapperRegistry->addMapper($mapper1);
        $this->mapperRegistry->addMapper($mapper2);

        $this->assertEquals(false, $this->checkoutStateDiffManager->isStateActual($this->checkout, $savedState));
    }

    public function testIsStateActualUnsupportedEntity()
    {
        $savedState = [
            'mapperName1' => true,
            'mapperName2' => [
                'parameter1' => 7635,
                'parameter2' => 'test value',
            ],
        ];

        $mapper1 = $this->getMapperMock();
        $mapper1
            ->expects($this->any())
            ->method('getName')
            ->willReturn('mapperName1');
        $mapper1
            ->expects($this->once())
            ->method('isEntitySupported')
            ->with($this->checkout)
            ->willReturn(true);
        $mapper1
            ->expects($this->once())
            ->method('isStateActual')
            ->with($this->checkout, $savedState)
            ->willReturn(true);

        $mapper2 = $this->getMapperMock();
        $mapper2
            ->expects($this->any())
            ->method('getName')
            ->willReturn('mapperName2');
        $mapper2
            ->expects($this->once())
            ->method('isEntitySupported')
            ->with($this->checkout)
            ->willReturn(false);
        $mapper2
            ->expects($this->any())
            ->method('isStateActual')
            ->with($this->checkout, $savedState)
            ->willReturn(true);

        $this->mapperRegistry->addMapper($mapper1);
        $this->mapperRegistry->addMapper($mapper2);

        $this->assertEquals(true, $this->checkoutStateDiffManager->isStateActual($this->checkout, $savedState));
    }
}
