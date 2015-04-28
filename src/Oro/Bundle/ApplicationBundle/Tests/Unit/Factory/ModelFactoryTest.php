<?php

namespace Oro\Bundle\ApplicationBundle\Tests\Unit\Factory;

use Oro\Bundle\ApplicationBundle\Factory\ModelFactory;
use Oro\Bundle\ApplicationBundle\Tests\Unit\Factory\Stub\TestModel;

class ModelFactoryTest extends \PHPUnit_Framework_TestCase
{
    const MODEL_CLASS = 'Oro\Bundle\ApplicationBundle\Tests\Unit\Factory\Stub\TestModel';

    /**
     * @var ModelFactory
     */
    protected $factory;

    protected function setUp()
    {
        $this->factory = new ModelFactory(self::MODEL_CLASS);
    }

    /**
     * @param array $arguments
     * @param string|null $expectedFirst
     * @param string|null $expectedSecond
     * @dataProvider createDataProvider
     */
    public function testCreate(array $arguments, $expectedFirst, $expectedSecond)
    {
        /** @var TestModel $model */
        $model = $this->factory->create($arguments);

        $this->assertInstanceOf(self::MODEL_CLASS, $model);
        $this->assertEquals($expectedFirst, $model->first);
        $this->assertEquals($expectedSecond, $model->second);
    }

    /**
     * @return array
     */
    public function createDataProvider()
    {
        return [
            'no arguments' => [
                'arguments' => [],
                'expectedFirst' => null,
                'expectedSecond' => null,
            ],
            'one argument' => [
                'arguments' => ['first_value'],
                'expectedFirst' => 'first_value',
                'expectedSecond' => null,
            ],
            'two arguments' => [
                'arguments' => ['first_value', 'second_value'],
                'expectedFirst' => 'first_value',
                'expectedSecond' => 'second_value',
            ],
        ];
    }
}
