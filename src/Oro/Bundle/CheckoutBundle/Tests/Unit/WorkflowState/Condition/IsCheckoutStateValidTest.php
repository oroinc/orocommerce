<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\WorkflowState\Condition;

use Oro\Bundle\CheckoutBundle\WorkflowState\Condition\IsCheckoutStateValid;
use Oro\Bundle\CheckoutBundle\WorkflowState\Manager\CheckoutStateDiffManager;
use Oro\Bundle\CheckoutBundle\WorkflowState\Storage\CheckoutDiffStorage;
use Oro\Component\ConfigExpression\Condition\AbstractCondition;
use Oro\Component\ConfigExpression\Exception\InvalidArgumentException;

class IsCheckoutStateValidTest extends \PHPUnit\Framework\TestCase
{
    /** @var CheckoutStateDiffManager|\PHPUnit\Framework\MockObject\MockObject */
    private $checkoutDiffManager;

    /** @var CheckoutDiffStorage|\PHPUnit\Framework\MockObject\MockObject */
    private $checkoutDiffStorage;

    /** @var IsCheckoutStateValid */
    private $condition;

    protected function setUp(): void
    {
        $this->checkoutDiffManager = $this->createMock(CheckoutStateDiffManager::class);
        $this->checkoutDiffStorage = $this->createMock(CheckoutDiffStorage::class);

        $this->condition = new IsCheckoutStateValid($this->checkoutDiffManager, $this->checkoutDiffStorage);
    }

    public function testInitialize(): void
    {
        $options = [
            'entity' => new \stdClass(),
            'token' => 'sample_token',
            'current_state' => ['sample_state_key' => 'sample_state_value'],
        ];

        $this->assertInstanceOf(AbstractCondition::class, $this->condition->initialize($options));
    }

    public function testInitializeWithoutToken(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Missing "token" option');

        $this->condition->initialize(['entity' => new \stdClass()]);
    }

    public function testInitializeWithoutCurrentState(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Missing "current_state" option');

        $this->condition->initialize(
            ['entity' => new \stdClass(), 'token' => 'sample_token']
        );
    }

    public function testEvaluateWhenStateValid(): void
    {
        $entity = new \stdClass();
        $token = ['token'];
        $currentState = ['sample_state_key' => 'sample_state_value'];
        $savedState = ['sample_saved_state_key' => 'sample_saved_state_value'];

        $options = [
            'entity' => $entity,
            'token' => $token,
            'current_state' => $currentState,
        ];

        $this->checkoutDiffStorage
            ->expects($this->once())
            ->method('getState')
            ->with($entity, $token)
            ->willReturn($savedState);

        $this->checkoutDiffManager
            ->expects($this->once())
            ->method('isStatesEqual')
            ->with($entity, $savedState, $currentState)
            ->willReturn(true);

        $this->checkoutDiffStorage
            ->expects($this->never())
            ->method('deleteStates');

        $this->condition->initialize($options);
        $this->assertTrue($this->condition->evaluate([]));
    }

    public function testEvaluateWhenStateInvalid(): void
    {
        $entity = new \stdClass();
        $token = ['token'];
        $currentState = ['sample_state_key' => 'sample_state_value'];
        $savedState = ['sample_saved_state_key' => 'sample_saved_state_value'];

        $options = [
            'entity' => $entity,
            'token' => $token,
            'current_state' => $currentState,
        ];

        $this->checkoutDiffStorage
            ->expects($this->once())
            ->method('getState')
            ->with($entity, $token)
            ->willReturn($savedState);

        $this->checkoutDiffManager
            ->expects($this->once())
            ->method('isStatesEqual')
            ->with($entity, $savedState, $currentState)
            ->willReturn(false);

        $this->checkoutDiffStorage
            ->expects($this->once())
            ->method('deleteStates')
            ->with($entity, $token);

        $this->checkoutDiffStorage
            ->expects($this->once())
            ->method('addState')
            ->with($entity, $currentState);

        $this->condition->initialize($options);
        $this->assertFalse($this->condition->evaluate([]));
    }

    public function testGetName(): void
    {
        $this->assertEquals('is_checkout_state_valid', $this->condition->getName());
    }

    public function testToArray(): void
    {
        $options = [
            'entity' => new \stdClass(),
            'token' => 'sample_token',
            'current_state' => ['sample_state_key' => 'sample_state_value'],
        ];

        $this->condition->initialize($options);
        $result = $this->condition->toArray();

        $key = '@is_checkout_state_valid';

        $this->assertArrayHasKey($key, $result);

        $resultSection = $result[$key];
        $this->assertArrayHasKey('parameters', $resultSection);
        $this->assertContains($options['entity'], $resultSection['parameters']);
        $this->assertContains($options['token'], $resultSection['parameters']);
        $this->assertContains($options['current_state'], $resultSection['parameters']);
    }

    public function testCompile(): void
    {
        $entity = new ToStringStub();

        $token = 'sample_token';
        $current_state = 'sample_state';

        $options = [
            'entity' => $entity,
            'token' => 'sample_token',
            'current_state' => $current_state,
        ];

        $this->condition->initialize($options);
        $result = $this->condition->compile('$factory');
        $this->assertEquals(
            sprintf(
                '$factory->create(\'%s\', [%s, \'%s\', \'%s\'])',
                'is_checkout_state_valid',
                $entity,
                $token,
                $current_state
            ),
            $result
        );
    }
}
