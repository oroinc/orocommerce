<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\WorkflowState\Manager;

use Oro\Bundle\CheckoutBundle\WorkflowState\Manager\CheckoutStateDiffManager;
use Oro\Bundle\CheckoutBundle\WorkflowState\Mapper\CheckoutStateDiffMapperInterface;
use Oro\Bundle\CheckoutBundle\WorkflowState\Mapper\CheckoutStateDiffMapperRegistry;

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
            $this->getMock('Oro\Bundle\CheckoutBundle\WorkflowState\Mapper\CheckoutStateDiffMapperRegistry');

        $this->checkoutStateDiffManager = new CheckoutStateDiffManager($this->mapperRegistry);
    }

    public function tearDown()
    {
        unset($this->mapperRegistry, $this->checkoutStateDiffManager);
    }

    /**
     * @param string $name
     * @return CheckoutStateDiffMapperInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function getBaseMapperMock($name)
    {
        /** @var CheckoutStateDiffMapperInterface|\PHPUnit_Framework_MockObject_MockObject $mapper */
        $mapper = $this->getMock('Oro\Bundle\CheckoutBundle\WorkflowState\Mapper\CheckoutStateDiffMapperInterface');

        $mapper
            ->expects($this->any())
            ->method('getName')
            ->willReturn($name);

        return $mapper;
    }

    public function testGetCurrentState()
    {
        $object = new \stdClass();
        $mapper1 = $this->getBaseMapperMock('mapperName1');
        $mapper2 = $this->getBaseMapperMock('mapperName2');

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

        $mapper1
            ->expects($this->once())
            ->method('isEntitySupported')
            ->with($object)
            ->willReturn(true);

        $mapper2
            ->expects($this->once())
            ->method('isEntitySupported')
            ->with($object)
            ->willReturn(true);


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
        $mapper1 = $this->getBaseMapperMock('mapperName1');
        $mapper2 = $this->getBaseMapperMock('mapperName2');

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

        $mapper1
            ->expects($this->once())
            ->method('isEntitySupported')
            ->with($object)
            ->willReturn(true);

        $mapper2
            ->expects($this->once())
            ->method('isEntitySupported')
            ->with($object)
            ->willReturn(false);

        $result = $this->checkoutStateDiffManager->getCurrentState($object);

        $this->assertEquals(
            [
                'mapperName1' => true,
            ],
            $result
        );
    }

    public function testIsStatesEqual()
    {
        $object = new \stdClass();
        $state1 = [
            'mapperName1' => true,
            'mapperName2' => [
                'parameter1' => 7635,
                'parameter2' => 'test value',
            ],
        ];

        $state2 = [
            'mapperName1' => true,
            'mapperName2' => [
                'parameter1' => 7635,
                'parameter2' => 'test value',
            ],
        ];

        $mapper1 = $this->getBaseMapperMock('mapperName1');
        $mapper2 = $this->getBaseMapperMock('mapperName2');

        $mapper1
            ->expects($this->once())
            ->method('isStatesEqual')
            ->with($object, $state1, $state2)
            ->willReturn(true);

        $mapper2
            ->expects($this->once())
            ->method('isStatesEqual')
            ->with($object, $state1, $state2)
            ->willReturn(true);

        $this->mapperRegistry->expects($this->once())
            ->method('getMappers')
            ->willReturn([$mapper1, $mapper2]);

        $mapper1
            ->expects($this->once())
            ->method('isEntitySupported')
            ->with($object)
            ->willReturn(true);

        $mapper2
            ->expects($this->once())
            ->method('isEntitySupported')
            ->with($object)
            ->willReturn(true);

        $this->assertEquals(true, $this->checkoutStateDiffManager->isStatesEqual($object, $state1, $state2));
    }

    public function testIsStatesEqualFalse()
    {
        $object = new \stdClass();
        $state1 = [
            'mapperName1' => true,
            'mapperName2' => [
                'parameter1' => 7635,
                'parameter2' => 'test value',
            ],
        ];

        $state2 = [
            'mapperName1' => true,
            'mapperName2' => [
                'parameter1' => 7635,
                'parameter2' => 'test value',
            ],
        ];

        $mapper1 = $this->getBaseMapperMock('mapperName1');
        $mapper2 = $this->getBaseMapperMock('mapperName2');

        $mapper1
            ->expects($this->once())
            ->method('isEntitySupported')
            ->with($object)
            ->willReturn(true);

        $mapper2
            ->expects($this->once())
            ->method('isEntitySupported')
            ->with($object)
            ->willReturn(true);

        $mapper1
            ->expects($this->once())
            ->method('isStatesEqual')
            ->with($object, $state1, $state2)
            ->willReturn(true);

        $mapper2
            ->expects($this->once())
            ->method('isStatesEqual');

        $this->mapperRegistry->expects($this->once())
            ->method('getMappers')
            ->willReturn([$mapper1, $mapper2]);

        $this->assertEquals(false, $this->checkoutStateDiffManager->isStatesEqual($object, $state1, $state2));
    }

    public function testIsStateActualUnsupportedEntity()
    {
        $object = new \stdClass();
        $state1 = [
            'mapperName1' => true,
            'mapperName2' => [
                'parameter1' => 7635,
                'parameter2' => 'test value',
            ],
        ];

        $state2 = [];

        $mapper = $this->getBaseMapperMock('mapperName2');

        $mapper
            ->expects($this->once())
            ->method('isEntitySupported')
            ->with($object)
            ->willReturn(false);

        $mapper
            ->expects($this->never())
            ->method('isStatesEqual');

        $this->mapperRegistry->expects($this->once())
            ->method('getMappers')
            ->willReturn([$mapper]);

        $this->assertEquals(true, $this->checkoutStateDiffManager->isStatesEqual($object, $state1, $state2));
    }
}
