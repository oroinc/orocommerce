<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\WorkflowState\Manager;

use Oro\Bundle\CheckoutBundle\WorkflowState\Manager\CheckoutStateDiffManager;
use Oro\Bundle\CheckoutBundle\WorkflowState\Mapper\CheckoutStateDiffMapperInterface;
use Oro\Bundle\CheckoutBundle\WorkflowState\Mapper\CheckoutStateDiffMapperRegistry;

class CheckoutStateDiffManagerTest extends \PHPUnit\Framework\TestCase
{
    /** @var CheckoutStateDiffMapperRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $mapperRegistry;

    /** @var CheckoutStateDiffManager */
    private $checkoutStateDiffManager;

    protected function setUp(): void
    {
        $this->mapperRegistry = $this->createMock(CheckoutStateDiffMapperRegistry::class);

        $this->checkoutStateDiffManager = new CheckoutStateDiffManager($this->mapperRegistry);
    }

    public function testGetCurrentState()
    {
        $object = new \stdClass();
        $mapper1 = $this->getBaseMapper('mapperName1');
        $mapper2 = $this->getBaseMapper('mapperName2');

        $mapper1State = true;
        $mapper2State = ['parameter1' => 7635, 'parameter2' => 'test value'];

        $mapper1->expects($this->once())
            ->method('getCurrentState')
            ->with($object)
            ->willReturn($mapper1State);
        $mapper1->expects($this->once())
            ->method('isEntitySupported')
            ->with($object)
            ->willReturn(true);

        $mapper2->expects($this->once())
            ->method('isEntitySupported')
            ->with($object)
            ->willReturn(true);
        $mapper2->expects($this->once())
            ->method('getCurrentState')
            ->with($object)
            ->willReturn($mapper2State);

        $this->mapperRegistry->expects($this->once())
            ->method('getMappers')
            ->willReturn([$mapper1, $mapper2]);

        $result = $this->checkoutStateDiffManager->getCurrentState($object);

        $this->assertEquals(
            [
                'mapperName1' => $mapper1State,
                'mapperName2' => $mapper2State,
            ],
            $result
        );
    }

    public function testGetCurrentStateUnsupportedEntity()
    {
        $object = new \stdClass();
        $mapper1 = $this->getBaseMapper('mapperName1');
        $mapper2 = $this->getBaseMapper('mapperName2');

        $mapper1State = true;

        $mapper1->expects($this->once())
            ->method('getCurrentState')
            ->with($object)
            ->willReturn($mapper1State);
        $mapper1->expects($this->once())
            ->method('isEntitySupported')
            ->with($object)
            ->willReturn(true);

        $mapper2->expects($this->once())
            ->method('isEntitySupported')
            ->with($object)
            ->willReturn(false);
        $mapper2->expects($this->never())
            ->method('getCurrentState');

        $this->mapperRegistry->expects($this->once())
            ->method('getMappers')
            ->willReturn([$mapper1, $mapper2]);

        $result = $this->checkoutStateDiffManager->getCurrentState($object);

        $this->assertEquals(
            [
                'mapperName1' => $mapper1State,
            ],
            $result
        );
    }

    public function testIsStatesEqual()
    {
        $object = new \stdClass();
        $mapper1State = true;
        $mapper2State = [
            'parameter1' => 7635,
            'parameter2' => 'test value',
        ];

        $state1 = [
            'mapperName1' => $mapper1State,
            'mapperName2' => $mapper2State,
        ];

        $state2 = [
            'mapperName1' => $mapper1State,
            'mapperName2' => $mapper2State,
        ];

        $mapper1 = $this->getBaseMapper('mapperName1');
        $mapper2 = $this->getBaseMapper('mapperName2');

        $mapper1->expects($this->once())
            ->method('isStatesEqual')
            ->with($object, $mapper1State, $mapper1State)
            ->willReturn(true);
        $mapper1->expects($this->once())
            ->method('isEntitySupported')
            ->with($object)
            ->willReturn(true);

        $mapper2->expects($this->once())
            ->method('isEntitySupported')
            ->with($object)
            ->willReturn(true);

        $mapper2->expects($this->once())
            ->method('isStatesEqual')
            ->with($object, $mapper2State, $mapper2State)
            ->willReturn(true);

        $this->mapperRegistry->expects($this->once())
            ->method('getMappers')
            ->willReturn([$mapper1, $mapper2]);

        $this->assertTrue($this->checkoutStateDiffManager->isStatesEqual($object, $state1, $state2));
    }

    public function testIsStatesEqualFalse()
    {
        $object = new \stdClass();

        $mapper1OldState = true;
        $mapper1NewState = false;

        $mapper2State = [
            'parameter1' => 7635,
            'parameter2' => 'test value',
        ];

        $oldState = [
            'mapperName1' => $mapper1OldState,
            'mapperName2' => $mapper2State,
        ];

        $newState = [
            'mapperName1' => $mapper1NewState,
            'mapperName2' => $mapper2State,
        ];

        $mapper1 = $this->getBaseMapper('mapperName1');
        $mapper2 = $this->getBaseMapper('mapperName2');

        $mapper1->expects($this->once())
            ->method('isEntitySupported')
            ->with($object)
            ->willReturn(true);
        $mapper1->expects($this->once())
            ->method('isStatesEqual')
            ->with($object, $mapper1OldState, $mapper1NewState)
            ->willReturn(false);

        $mapper2->expects($this->never())
            ->method('isEntitySupported');
        $mapper2->expects($this->never())
            ->method('isStatesEqual');

        $this->mapperRegistry->expects($this->once())
            ->method('getMappers')
            ->willReturn([$mapper1, $mapper2]);

        $this->assertFalse($this->checkoutStateDiffManager->isStatesEqual($object, $oldState, $newState));
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

        $mapper = $this->getBaseMapper('mapperName2');
        $mapper->expects($this->once())
            ->method('isEntitySupported')
            ->with($object)
            ->willReturn(false);
        $mapper->expects($this->never())
            ->method('isStatesEqual');

        $this->mapperRegistry->expects($this->once())
            ->method('getMappers')
            ->willReturn([$mapper]);

        $this->assertTrue($this->checkoutStateDiffManager->isStatesEqual($object, $state1, $state2));
    }

    /**
     * @dataProvider isStatesEqualNullStateProvider
     */
    public function testIsStatesEqualNullState(array $state1, array $state2)
    {
        $object = new \stdClass();

        $mapper = $this->getBaseMapper('mapperName');
        $mapper->expects($this->once())
            ->method('isEntitySupported')
            ->with($object)
            ->willReturn(true);
        $mapper->expects($this->never())
            ->method('isStatesEqual');

        $this->mapperRegistry->expects($this->once())
            ->method('getMappers')
            ->willReturn([$mapper]);

        $this->assertTrue($this->checkoutStateDiffManager->isStatesEqual($object, $state1, $state2));
    }

    public function isStatesEqualNullStateProvider(): array
    {
        return [
            'state1 is null only' => [
                'state1' => [
                    'mapperName' => null,
                ],
                'state2' => [
                    'mapperName' => 'value',
                ],
            ],
            'state2 is null only' => [
                'state1' => [
                    'mapperName' => 'value',
                ],
                'state2' => [
                    'mapperName' => null,
                ],
            ],
            'states are null' => [
                'state1' => [
                    'mapperName' => null,
                ],
                'state2' => [
                    'mapperName' => null,
                ],
            ],
            'state1 is empty only' => [
                'state1' => [
                ],
                'state2' => [
                    'mapperName' => 'value',
                ],
            ],
            'state2 is empty only' => [
                'state1' => [
                    'mapperName' => 'value',
                ],
                'state2' => [
                ],
            ],
            'states are empty' => [
                'state1' => [
                ],
                'state2' => [
                ],
            ],
        ];
    }

    /**
     * @return CheckoutStateDiffMapperInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private function getBaseMapper(string $name)
    {
        $mapper = $this->createMock(CheckoutStateDiffMapperInterface::class);
        $mapper->expects($this->any())
            ->method('getName')
            ->willReturn($name);

        return $mapper;
    }
}
