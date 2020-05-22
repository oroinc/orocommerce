<?php

namespace Oro\Bundle\ApplicationBundle\Tests\Unit\Factory;

use Oro\Bundle\ApplicationBundle\Factory\ModelFactory;
use Oro\Bundle\ApplicationBundle\Tests\Unit\Stub\TestEntity;
use Oro\Bundle\ApplicationBundle\Tests\Unit\Stub\TestModel;

class ModelFactoryTest extends \PHPUnit\Framework\TestCase
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

    public function testConstructUndefinedModelClass()
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Class "UndefinedClass" is not defined');

        new ModelFactory('UndefinedClass', self::ENTITY_CLASS);
    }

    public function testConstructInvalidModelClass()
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Class "\DateTime" must implement ModelInterface');

        new ModelFactory('\DateTime', self::ENTITY_CLASS);
    }

    public function testConstructUndefinedEntityClass()
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Class "UndefinedClass" is not defined');

        new ModelFactory(self::MODEL_CLASS, 'UndefinedClass');
    }
}
