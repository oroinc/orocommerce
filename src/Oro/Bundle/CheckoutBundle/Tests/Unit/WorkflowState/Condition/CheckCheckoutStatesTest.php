<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\WorkflowState\Condition;

use Oro\Bundle\CheckoutBundle\WorkflowState\Condition\CheckCheckoutStates;
use Oro\Bundle\CheckoutBundle\WorkflowState\Manager\CheckoutStateDiffManager;
use Oro\Component\ConfigExpression\Condition\AbstractCondition;
use Oro\Component\ConfigExpression\Exception\InvalidArgumentException;

class CheckCheckoutStatesTest extends \PHPUnit\Framework\TestCase
{
    /** @var CheckoutStateDiffManager|\PHPUnit\Framework\MockObject\MockObject */
    private $diffManager;

    /** @var CheckCheckoutStates */
    private $condition;

    protected function setUp(): void
    {
        $this->diffManager = $this->createMock(CheckoutStateDiffManager::class);

        $this->condition = new CheckCheckoutStates($this->diffManager);
    }

    public function testInitialize()
    {
        $options = [
            'entity' => new \stdClass(),
            'state1' => ['state1'],
            'state2' => ['state2'],
        ];

        $this->assertInstanceOf(AbstractCondition::class, $this->condition->initialize($options));
    }

    public function testInitializeWithoutState1()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Missing "state1" option');

        $options = [
            'entity' => new \stdClass(),
        ];

        $this->condition->initialize($options);
    }

    public function testInitializeWithoutState2()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Missing "state2" option');

        $options = [
            'entity' => new \stdClass(),
            'state1' => ['state1'],
        ];

        $this->condition->initialize($options);
    }

    /**
     * @dataProvider evaluateProvider
     */
    public function testEvaluate(bool $expected)
    {
        $entity = new \stdClass();
        $state1 = ['state1'];
        $state2 = ['state2'];

        $options = [
            'entity' => $entity,
            'state1' => $state1,
            'state2' => $state2,
        ];

        $this->diffManager->expects($this->once())
            ->method('isStatesEqual')
            ->with($entity, $state1, $state2)
            ->willReturn($expected);

        $this->condition->initialize($options);
        $this->assertEquals($expected, $this->condition->evaluate([]));
    }

    public function evaluateProvider(): array
    {
        return [
            [true],
            [false]
        ];
    }

    public function testGetName()
    {
        $this->assertEquals('check_checkout_states', $this->condition->getName());
    }

    public function testToArray()
    {
        $options = [
            'entity' => new \stdClass(),
            'state1' => ['state1'],
            'state2' => ['state2'],
        ];

        $this->condition->initialize($options);
        $result = $this->condition->toArray();

        $key = '@' . CheckCheckoutStates::NAME;

        $this->assertIsArray($result);
        $this->assertArrayHasKey($key, $result);

        $resultSection = $result[$key];
        $this->assertIsArray($resultSection);
        $this->assertArrayHasKey('parameters', $resultSection);
        $this->assertContains($options['entity'], $resultSection['parameters']);
        $this->assertContains($options['state1'], $resultSection['parameters']);
        $this->assertContains($options['state2'], $resultSection['parameters']);
    }

    public function testCompile()
    {
        $entity = new ToStringStub();

        $state1 = 'state1_property_path';
        $state2 = 'state2_property_path';

        $options = [
            'entity' => $entity,
            'state1' => $state1,
            'state2' => $state2,
        ];

        $this->condition->initialize($options);
        $result = $this->condition->compile('$factory');
        $this->assertEquals(sprintf(
            '$factory->create(\'%s\', [%s, \'%s\', \'%s\'])',
            CheckCheckoutStates::NAME,
            $entity,
            $state1,
            $state2
        ), $result);
    }
}
