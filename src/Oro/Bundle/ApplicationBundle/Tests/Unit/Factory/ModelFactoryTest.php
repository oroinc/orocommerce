<?php

namespace Oro\Bundle\ApplicationBundle\Tests\Unit\Factory;

use Oro\Bundle\ApplicationBundle\Factory\ModelFactory;
use Oro\Bundle\ApplicationBundle\Tests\Unit\Stub\TestModel;
use Oro\Bundle\ApplicationBundle\Tests\Unit\Stub\TestEntity;

class ModelFactoryTest extends \PHPUnit_Framework_TestCase
{
    const MODEL_CLASS = 'Oro\Bundle\ApplicationBundle\Tests\Unit\Stub\TestModel';
    const ENTITY_CLASS = 'Oro\Bundle\ApplicationBundle\Tests\Unit\Stub\TestEntity';

    /**
     * @param array $arguments
     * @param object $expectedEntity
     * @param string|null $expectedFirst
     * @param string|null $expectedSecond
     * @dataProvider createDataProvider
     */
    public function testCreate(array $arguments, $expectedEntity, $expectedFirst, $expectedSecond)
    {
        /** @var TestModel $model */
        $factory = new ModelFactory(self::MODEL_CLASS, self::ENTITY_CLASS);
        $model = $factory->create($arguments);

        $this->assertInstanceOf(self::MODEL_CLASS, $model);
        $this->assertEquals([$expectedEntity], $model->getEntities());
        $this->assertEquals($expectedFirst, $model->first);
        $this->assertEquals($expectedSecond, $model->second);
    }

    /**
     * @return array
     */
    public function createDataProvider()
    {
        $entity = new \stdClass();

        return [
            'no arguments' => [
                'arguments' => [],
                'expectedEntity' => new TestEntity(),
                'expectedFirst' => null,
                'expectedSecond' => null,
            ],
            'one argument' => [
                'arguments' => [$entity],
                'expectedEntity' => $entity,
                'expectedFirst' => null,
                'expectedSecond' => null,
            ],
            'two argument' => [
                'arguments' => [$entity, 'first_value'],
                'expectedEntity' => $entity,
                'expectedFirst' => 'first_value',
                'expectedSecond' => null,
            ],
            'three arguments' => [
                'arguments' => [$entity, 'first_value', 'second_value'],
                'expectedEntity' => $entity,
                'expectedFirst' => 'first_value',
                'expectedSecond' => 'second_value',
            ],
        ];
    }

    /**
     * @expectedException \LogicException
     * @expectedExceptionMessage Class "UndefinedClass" is not defined
     */
    public function testConstructUndefinedModelClass()
    {
        new ModelFactory('UndefinedClass', self::ENTITY_CLASS);
    }

    /**
     * @expectedException \LogicException
     * @expectedExceptionMessage Class "\DateTime" must implement ModelInterface
     */
    public function testConstructInvalidModelClass()
    {
        new ModelFactory('\DateTime', self::ENTITY_CLASS);
    }

    /**
     * @expectedException \LogicException
     * @expectedExceptionMessage Class "UndefinedClass" is not defined
     */
    public function testConstructUndefinedEntityClass()
    {
        new ModelFactory(self::MODEL_CLASS, 'UndefinedClass');
    }
}
