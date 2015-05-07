<?php

namespace Oro\Bundle\ApplicationBundle\Tests\Unit\Factory;

use Oro\Bundle\ApplicationBundle\Factory\ModelFactory;
use Oro\Bundle\ApplicationBundle\Tests\Unit\Stub\TestModel;

class ModelFactoryTest extends \PHPUnit_Framework_TestCase
{
    const MODEL_CLASS = 'Oro\Bundle\ApplicationBundle\Tests\Unit\Stub\TestModel';

    /**
     * @param array $arguments
     * @param string|null $expectedFirst
     * @param string|null $expectedSecond
     * @dataProvider createDataProvider
     */
    public function testCreate(array $arguments, $expectedFirst, $expectedSecond)
    {
        /** @var TestModel $model */
        $factory = new ModelFactory(self::MODEL_CLASS);
        $model = $factory->create($arguments);

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

    /**
     * @expectedException \LogicException
     * @expectedExceptionMessage Class "UndefinedClass" is not defined
     */
    public function testConstructUndefinedClass()
    {
        new ModelFactory('UndefinedClass');
    }

    /**
     * @expectedException \LogicException
     * @expectedExceptionMessage Class "\DateTime" must implement ModelInterface
     */
    public function testConstructInvalidClass()
    {
        new ModelFactory('\DateTime');
    }
}
