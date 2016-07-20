<?php

namespace OroB2B\Bundle\CheckoutBundle\Tests\Unit\WorkflowState\Manager;

use OroB2B\Bundle\CheckoutBundle\WorkflowState\Manager\CheckoutStateDiffManager;
use OroB2B\Bundle\CheckoutBundle\WorkflowState\Mapper\CheckoutStateDiffMapperInterface;
use OroB2B\Bundle\CheckoutBundle\WorkflowState\Mapper\CheckoutStateDiffMapperRegistry;

class CheckoutStateDiffManagerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var CheckoutStateDiffManager
     */
    protected $checkoutStateDiffManager;

    /**
     * @var CheckoutStateDiffMapperRegistry|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $mapperRegistry;

    public function setUp()
    {
        $this->mapperRegistry =
            $this->getMock('OroB2B\Bundle\CheckoutBundle\WorkflowState\Mapper\CheckoutStateDiffMapperRegistry');

        $this->checkoutStateDiffManager = new CheckoutStateDiffManager($this->mapperRegistry);
    }

    public function tearDown()
    {
        unset($this->mapperRegistry, $this->checkoutStateDiffManager);
    }

    /**
     * @param string $name
     * @param bool $isEntitySupported
     * @param object $object
     * @return CheckoutStateDiffMapperInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function getBaseMapperMock($name, $isEntitySupported, $object)
    {
        /** @var CheckoutStateDiffMapperInterface|\PHPUnit_Framework_MockObject_MockObject $mapper */
        $mapper = $this->getMock('OroB2B\Bundle\CheckoutBundle\WorkflowState\Mapper\CheckoutStateDiffMapperInterface');

        $mapper
            ->expects($this->any())
            ->method('getName')
            ->willReturn($name);

        $mapper
            ->expects($this->once())
            ->method('isEntitySupported')
            ->with($object)
            ->willReturn($isEntitySupported);

        return $mapper;
    }

    /**
     * @param string $name
     * @param bool $isEntitySupported
     * @param mixed $currentState
     * @param object $object
     * @return CheckoutStateDiffMapperInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function getMapperCurrentStateMock($name, $isEntitySupported, $currentState, $object)
    {
        $mapper = $this->getBaseMapperMock($name, $isEntitySupported, $object);

        $mapper
            ->expects($isEntitySupported ? $this->once() : $this->never())
            ->method('getCurrentState')
            ->with($object)
            ->willReturn($currentState);

        return $mapper;
    }

    /**
     * @param string $name
     * @param bool $isEntitySupported
     * @param bool $isStateActual
     * @param array $savedState
     * @param object $object
     * @return CheckoutStateDiffMapperInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function getMapperStateActualMock($name, $isEntitySupported, $isStateActual, $savedState, $object)
    {
        $mapper = $this->getBaseMapperMock($name, $isEntitySupported, $object);

        $mapper
            ->expects($isEntitySupported ? $this->once() : $this->never())
            ->method('isStateActual')
            ->with($object, $savedState)
            ->willReturn($isStateActual);

        return $mapper;
    }

    public function testGetCurrentState()
    {
        $object = new \stdClass();
        $mapper1 = $this->getMapperCurrentStateMock('mapperName1', true, true, $object);
        $mapper2 = $this->getMapperCurrentStateMock(
            'mapperName2',
            true,
            [
                'parameter1' => 7635,
                'parameter2' => 'test value'
            ],
            $object
        );

        $this->mapperRegistry->expects($this->once())
            ->method('getMappers')
            ->willReturn([$mapper1, $mapper2]);

        $result = $this->checkoutStateDiffManager->getCurrentState($object);

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
        $object = new \stdClass();
        $mapper1 = $this->getMapperCurrentStateMock('mapperName1', true, true, $object);
        $mapper2 = $this->getMapperCurrentStateMock(
            'mapperName2',
            false,
            [
                'parameter1' => 7635,
                'parameter2' => 'test value'
            ],
            $object
        );

        $this->mapperRegistry->expects($this->once())
            ->method('getMappers')
            ->willReturn([$mapper1, $mapper2]);

        $result = $this->checkoutStateDiffManager->getCurrentState($object);

        $this->assertEquals(
            [
                'mapperName1' => true,
            ],
            $result
        );
    }

    public function testIsStateActual()
    {
        $object = new \stdClass();
        $savedState = [
            'mapperName1' => true,
            'mapperName2' => [
                'parameter1' => 7635,
                'parameter2' => 'test value',
            ],
        ];

        $mapper1 = $this->getMapperStateActualMock('mapperName1', true, true, $savedState, $object);
        $mapper2 = $this->getMapperStateActualMock('mapperName2', true, true, $savedState, $object);

        $this->mapperRegistry->expects($this->once())
            ->method('getMappers')
            ->willReturn([$mapper1, $mapper2]);

        $this->assertEquals(true, $this->checkoutStateDiffManager->isStateActual($object, $savedState));
    }

    public function testIsStateActualFalse()
    {
        $object = new \stdClass();
        $savedState = [
            'mapperName1' => true,
            'mapperName2' => [
                'parameter1' => 7635,
                'parameter2' => 'test value',
            ],
        ];

        $mapper1 = $this->getMapperStateActualMock('mapperName1', true, true, $savedState, $object);
        $mapper2 = $this->getMapperStateActualMock('mapperName2', true, false, $savedState, $object);

        $this->mapperRegistry->expects($this->once())
            ->method('getMappers')
            ->willReturn([$mapper1, $mapper2]);

        $this->assertEquals(false, $this->checkoutStateDiffManager->isStateActual($object, $savedState));
    }

    public function testIsStateActualUnsupportedEntity()
    {
        $object = new \stdClass();
        $savedState = [
            'mapperName1' => true,
            'mapperName2' => [
                'parameter1' => 7635,
                'parameter2' => 'test value',
            ],
        ];

        $mapper1 = $this->getMapperStateActualMock('mapperName1', true, true, $savedState, $object);
        $mapper2 = $this->getMapperStateActualMock('mapperName2', false, false, $savedState, $object);

        $this->mapperRegistry->expects($this->once())
            ->method('getMappers')
            ->willReturn([$mapper1, $mapper2]);

        $this->assertEquals(true, $this->checkoutStateDiffManager->isStateActual($object, $savedState));
    }
}
