<?php

namespace Oro\Bundle\CatalogBundle\Tests\Unit\Entity;

use Oro\Bundle\CatalogBundle\Entity\CategoryDefaultProductOptions;
use Oro\Bundle\CatalogBundle\Model\CategoryUnitPrecision;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Component\Testing\Unit\EntityTestCaseTrait;

class CategoryDefaultProductOptionsTest extends \PHPUnit\Framework\TestCase
{
    use EntityTestCaseTrait;

    /** @var  CategoryDefaultProductOptions $defaultProductOptions */
    protected $entity;

    protected function setUp(): void
    {
        $this->entity = new CategoryDefaultProductOptions();
    }

    public function testProperties()
    {
        $properties = [
            ['unitPrecision', new CategoryUnitPrecision()]
        ];
        $this->assertPropertyAccessors(new CategoryDefaultProductOptions(), $properties);
    }

    public function testGetId()
    {
        $this->assertNull($this->entity->getId());

        $this->setProperty($this->entity, 'id', 123);

        $this->assertSame(123, $this->entity->getId());
    }

    public function testSetGetUnitPrecision()
    {
        static::assertNull($this->entity->getUnitPrecision());

        $this->entity->updateUnitPrecision();
        $this->entity->loadUnitPrecision();
        static::assertNull($this->entity->getUnitPrecision()->getPrecision());
        static::assertNull($this->entity->getUnitPrecision()->getUnit());

        $precision = 11.1;
        $unit = new ProductUnit();

        $this->setProperty($this->entity, 'precision', $precision);
        $this->setProperty($this->entity, 'unit', $unit);
        $this->entity->loadUnitPrecision();

        $unitPrecision = $this->entity->getUnitPrecision();
        static::assertInstanceOf(CategoryUnitPrecision::class, $unitPrecision);
        static::assertEquals($precision, $unitPrecision->getPrecision());
        static::assertSame($unit, $unitPrecision->getUnit());

        $unitPrecision = CategoryUnitPrecision::create(3, new ProductUnit('set'));
        $this->entity->setUnitPrecision($unitPrecision);
        static::assertSame($unitPrecision, $this->entity->getUnitPrecision());

        $this->entity->updateUnitPrecision();
        static::assertEquals($unitPrecision->getPrecision(), $this->entity->getUnitPrecision()->getPrecision());
        static::assertEquals($unitPrecision->getUnit(), $this->entity->getUnitPrecision()->getUnit());

        $this->setProperty($this->entity, 'unitPrecision', null);
        $this->entity->updateUnitPrecision();
        $this->entity->loadUnitPrecision();
        static::assertNull($this->entity->getUnitPrecision()->getPrecision());
        static::assertNull($this->entity->getUnitPrecision()->getUnit());
    }

    public function testToString()
    {
        $this->setProperty($this->entity, 'id', 123);

        $this->assertEquals('123', (string)$this->entity);
    }

    /**
     * @param object $object
     * @param string $property
     * @param mixed $value
     */
    protected function setProperty($object, $property, $value)
    {
        $reflection = new \ReflectionProperty(get_class($object), $property);
        $reflection->setAccessible(true);
        $reflection->setValue($object, $value);
    }
}
