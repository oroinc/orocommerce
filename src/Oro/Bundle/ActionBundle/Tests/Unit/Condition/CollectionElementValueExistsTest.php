<?php

namespace Oro\Bundle\ActionBundle\Tests\Unit\Condition;

use Symfony\Component\PropertyAccess\PropertyPath;

use Oro\Bundle\ActionBundle\Condition\CollectionElementValueExists;
use Oro\Component\ConfigExpression\ContextAccessor;

class CollectionElementValueExistsTest extends \PHPUnit_Framework_TestCase
{
    /** @var CollectionElementValueExists */
    protected $condition;

    public function setUp()
    {
        $this->condition = new CollectionElementValueExists();
        $this->condition->setContextAccessor(new ContextAccessor());
    }

    /**
     * @dataProvider evaluateDataProvider
     * @param array $options
     * @param array $context
     * @param bool $expectedResult
     */
    public function testEvaluate(array $options, array $context, $expectedResult)
    {
        $this->assertSame($this->condition, $this->condition->initialize($options));
        $this->assertEquals($expectedResult, $this->condition->evaluate($context));
    }

    /**
     * @return array
     */
    public function evaluateDataProvider()
    {
        $options = [new PropertyPath('foo.words'), new PropertyPath('data.name'), new PropertyPath('bar')];

        return [
            'in_array' => [
                'options' => $options,
                'context' => [
                    'foo' => [
                        'sth',
                        'words' => [
                            ['name' => 'worda'],
                            ['name' => 'wordb']
                        ],
                        'sth else'
                    ],
                    'bar' => 'wordb'
                ],
                'expectedResult' => true
            ],
            'not_in_array' => [
                'options' => $options,
                'context' => [
                    'foo' => [
                        'sth',
                        'words' => [
                            ['name' => 'worda'],
                            ['name' => 'wordb']
                        ],
                        'sth else'
                    ],
                    'bar' => 'wordc'
                ],
                'expectedResult' => false,
            ],
        ];
    }
}
