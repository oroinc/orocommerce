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

    public function testGetCurrentState()
    {
        $object = new \stdClass();
        $mapper1 = $this->getBaseMapperMock('mapperName1', true, $object);
        $mapper2 = $this->getBaseMapperMock('mapperName2', true, $object);

        $mapper1
            ->expects($this->once())
            ->method('getCurrentState')
            ->with($object)
            ->willReturn(true);

        $mapper2
            ->expects($this->once())
            ->method('getCurrentState')
            ->with($object)
            ->willReturn(['parameter1' => 7635, 'parameter2' => 'test value']);

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
        $mapper1 = $this->getBaseMapperMock('mapperName1', true, $object);
        $mapper2 = $this->getBaseMapperMock('mapperName2', false, $object);

        $mapper1
            ->expects($this->once())
            ->method('getCurrentState')
            ->with($object)
            ->willReturn(true);

        $mapper2
            ->expects($this->never())
            ->method('getCurrentState');

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

        $mapper1 = $this->getBaseMapperMock('mapperName1', true, $object);
        $mapper2 = $this->getBaseMapperMock('mapperName2', true, $object);

        $mapper1
            ->expects($this->once())
            ->method('isStateActual')
            ->with($object, $savedState)
            ->willReturn(true);

        $mapper2
            ->expects($this->once())
            ->method('isStateActual')
            ->with($object, $savedState)
            ->willReturn(true);

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

        $mapper1 = $this->getBaseMapperMock('mapperName1', true, $object);
        $mapper2 = $this->getBaseMapperMock('mapperName2', true, $object);

        $mapper1
            ->expects($this->once())
            ->method('isStateActual')
            ->with($object, $savedState)
            ->willReturn(true);

        $mapper2
            ->expects($this->once())
            ->method('isStateActual');

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

        $mapper = $this->getBaseMapperMock('mapperName2', false, $object);

        $mapper
            ->expects($this->never())
            ->method('isStateActual');

        $this->mapperRegistry->expects($this->once())
            ->method('getMappers')
            ->willReturn([$mapper]);

        $this->assertEquals(true, $this->checkoutStateDiffManager->isStateActual($object, $savedState));
    }
}
