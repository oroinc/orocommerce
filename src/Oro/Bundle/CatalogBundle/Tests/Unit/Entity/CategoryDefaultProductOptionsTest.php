<?php

namespace Oro\Bundle\CatalogBundle\Tests\Unit\Entity;

use Oro\Bundle\CatalogBundle\Entity\CategoryDefaultProductOptions;
use Oro\Bundle\CatalogBundle\Model\CategoryUnitPrecision;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Component\Testing\ReflectionUtil;
use Oro\Component\Testing\Unit\EntityTestCaseTrait;

class CategoryDefaultProductOptionsTest extends \PHPUnit\Framework\TestCase
{
    use EntityTestCaseTrait;

    /** @var CategoryDefaultProductOptions */
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

        ReflectionUtil::setId($this->entity, 123);
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

        ReflectionUtil::setPropertyValue($this->entity, 'precision', $precision);
        ReflectionUtil::setPropertyValue($this->entity, 'unit', $unit);
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

        ReflectionUtil::setPropertyValue($this->entity, 'unitPrecision', null);
        $this->entity->updateUnitPrecision();
        $this->entity->loadUnitPrecision();
        static::assertNull($this->entity->getUnitPrecision()->getPrecision());
        static::assertNull($this->entity->getUnitPrecision()->getUnit());
    }

    public function testToString()
    {
        ReflectionUtil::setId($this->entity, 123);
        $this->assertSame('123', (string)$this->entity);
    }
}
