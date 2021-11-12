<?php

namespace Oro\Bundle\ShoppingListBundle\Tests\Unit\Action;

use Oro\Bundle\ShoppingListBundle\Action\AddStringSuffixAction;
use Oro\Component\Action\Exception\InvalidParameterException;
use Oro\Component\ConfigExpression\ContextAccessor;
use Oro\Component\ConfigExpression\Tests\Unit\Fixtures;
use Oro\Component\Testing\ReflectionUtil;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\PropertyAccess\PropertyPath;

class AddStringSuffixActionTest extends \PHPUnit\Framework\TestCase
{
    /** @var AddStringSuffixAction */
    private $action;

    protected function setUp(): void
    {
        $this->action = new AddStringSuffixAction(new ContextAccessor());
        $this->action->setDispatcher($this->createMock(EventDispatcherInterface::class));
    }

    public function testInitialize()
    {
        $options = [
            'attribute' => new PropertyPath('attribute'),
            'string' => 'label',
            'stringSuffix' => ' some additional string',
            'maxLength' => 5,
        ];
        $this->action->initialize($options);

        self::assertEquals($options, ReflectionUtil::getPropertyValue($this->action, 'options'));
    }

    /**
     * @dataProvider actionDataProvider
     */
    public function testAction(array $contextData, array $options, string $expectedResult)
    {
        $context = new Fixtures\ItemStub();
        foreach ($contextData as $key => $value) {
            $context->{$key} = $value;
        }
        $this->action->initialize($options);
        $this->action->execute($context);
        $this->assertEquals($expectedResult, $context->attribute);
    }

    public function actionDataProvider(): array
    {
        return [
            [
                'contextData' => [
                    'label' => '123456',
                    'suffix' => '12',
                ],
                'options' => [
                    'attribute' => new PropertyPath('attribute'),
                    'string' => new PropertyPath('label'),
                    'stringSuffix' => new PropertyPath('suffix'),
                    'maxLength' => 5,
                ],
                'expectedResult' => '12…12'
            ],
            [
                'contextData' => [
                    'label' => '1234',
                    'suffix' => '123',
                ],
                'options' => [
                    'attribute' => new PropertyPath('attribute'),
                    'string' => new PropertyPath('label'),
                    'stringSuffix' => new PropertyPath('suffix'),
                    'maxLength' => 5,
                ],
                'expectedResult' => '1…123'
            ],
            [
                'contextData' => [
                    'label' => '1',
                    'suffix' => '123456',
                ],
                'options' => [
                    'attribute' => new PropertyPath('attribute'),
                    'string' => new PropertyPath('label'),
                    'stringSuffix' => new PropertyPath('suffix'),
                    'maxLength' => 5,
                ],
                'expectedResult' => '11234'
            ],
        ];
    }

    /**
     * @dataProvider initializeExceptionDataProvider
     */
    public function testInitializeException(array $options, string $exceptionName, string $exceptionMessage)
    {
        $this->expectException($exceptionName);
        $this->expectExceptionMessage($exceptionMessage);
        $this->action->initialize($options);
    }

    public function initializeExceptionDataProvider(): array
    {
        return [
            'no attribute' => [
                'options' => [],
                'exceptionName' => InvalidParameterException::class,
                'exceptionMessage' => 'Attribute name parameter is required',
            ],
            'incorrect attribute' => [
                'options' => [
                    'attribute' => 'string'
                ],
                'exceptionName' => InvalidParameterException::class,
                'exceptionMessage' => 'Attribute must be valid property definition',
            ],
            'no string' => [
                'options' => [
                    'attribute' => new PropertyPath('attribute'),
                ],
                'exceptionName' => InvalidParameterException::class,
                'exceptionMessage' => 'String parameter must be specified',
            ],
            'no string suffix' => [
                'options' => [
                    'attribute' => new PropertyPath('attribute'),
                    'string' => new PropertyPath('string'),
                ],
                'exceptionName' => InvalidParameterException::class,
                'exceptionMessage' => 'String suffix parameter must be specified',
            ],
            'invalid max length' => [
                'options' => [
                    'attribute' => new PropertyPath('attribute'),
                    'string' => new PropertyPath('string'),
                    'stringSuffix' => new PropertyPath('stringSuffix'),
                    'maxLength' => 'string',
                ],
                'exceptionName' => InvalidParameterException::class,
                'exceptionMessage' => 'Max length must be integer',
            ],
            'negative max length' => [
                'options' => [
                    'attribute' => new PropertyPath('attribute'),
                    'string' => new PropertyPath('string'),
                    'stringSuffix' => new PropertyPath('stringSuffix'),
                    'maxLength' => -1,
                ],
                'exceptionName' => InvalidParameterException::class,
                'exceptionMessage' => 'Max length must be positive',
            ],
        ];
    }
}
