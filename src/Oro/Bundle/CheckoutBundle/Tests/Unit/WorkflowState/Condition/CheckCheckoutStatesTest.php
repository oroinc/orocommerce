<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\WorkflowState\Condition;

use Oro\Bundle\CheckoutBundle\WorkflowState\Manager\CheckoutStateDiffManager;
use Oro\Bundle\CheckoutBundle\WorkflowState\Condition\CheckCheckoutStates;

class CheckCheckoutStatesTest extends \PHPUnit_Framework_TestCase
{
    /** @var CheckoutStateDiffManager|\PHPUnit_Framework_MockObject_MockObject */
    protected $diffManager;

    /** @var CheckCheckoutStates */
    protected $condition;

    protected function setUp()
    {
        $this->diffManager = $this->getMockBuilder(CheckoutStateDiffManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->condition = new CheckCheckoutStates($this->diffManager);
    }

    protected function tearDown()
    {
        unset($this->diffManager, $this->condition);
    }

    public function testInitialize()
    {
        $options = [
            'entity' => new \stdClass(),
            'state1' => ['state1'],
            'state2' => ['state2'],
        ];

        $this->assertInstanceOf(
            'Oro\Component\ConfigExpression\Condition\AbstractCondition',
            $this->condition->initialize($options)
        );
    }

    /**
     * @expectedException \Oro\Component\ConfigExpression\Exception\InvalidArgumentException
     * @expectedExceptionMessage Missing "state1" option
     */
    public function testInitializeWithoutState1()
    {
        $options = [
            'entity' => new \stdClass(),
        ];

        $this->condition->initialize($options);
    }

    /**
     * @expectedException \Oro\Component\ConfigExpression\Exception\InvalidArgumentException
     * @expectedExceptionMessage Missing "state2" option
     */
    public function testInitializeWithoutState2()
    {
        $options = [
            'entity' => new \stdClass(),
            'state1' => ['state1'],
        ];

        $this->condition->initialize($options);
    }

    /**
     * @dataProvider evaluateProvider
     * @param bool $expected
     */
    public function testEvaluate($expected)
    {
        $entity = new \stdClass();
        $state1 = ['state1'];
        $state2 = ['state2'];

        $options = [
            'entity' => $entity,
            'state1' => $state1,
            'state2' => $state2,
        ];

        $this->diffManager
            ->expects($this->once())
            ->method('isStatesEqual')
            ->with($entity, $state1, $state2)
            ->willReturn($expected);

        $this->condition->initialize($options);
        $this->assertEquals($expected, $this->condition->evaluate([]));
    }

    /**
     * @return array
     */
    public function evaluateProvider()
    {
        return [
            [
                'expected' => true,
            ],
            [
                'expected' => false,
            ],
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

        $this->assertInternalType('array', $result);
        $this->assertArrayHasKey($key, $result);

        $resultSection = $result[$key];
        $this->assertInternalType('array', $resultSection);
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
