<?php

namespace OroB2B\Bundle\CatalogBundle\Tests\Unit\Entity;

use Oro\Component\Testing\Unit\EntityTestCaseTrait;

use OroB2B\Bundle\CatalogBundle\Entity\CategoryDefaultProductOptions;
use OroB2B\Bundle\CatalogBundle\Model\CategoryUnitPrecision;
use OroB2B\Bundle\ProductBundle\Entity\ProductUnit;

class CategoryDefaultProductOptionsTest extends \PHPUnit_Framework_TestCase
{
    use EntityTestCaseTrait;

    /** @var  CategoryDefaultProductOptions $defaultProductOptions */
    protected $entity;

    public function setup()
    {
        $this->entity = new CategoryDefaultProductOptions();
    }

    public function testProperties()
    {
        $properties = [
            [
                'unit' => new ProductUnit(),
                'precision' => 3,
                'unitPrecision', new CategoryUnitPrecision()
            ]
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
        $this->assertNull($this->entity->getUnitPrecision());

        $this->entity->updateUnitPrecision();
        $this->assertAttributeEquals(null, 'unit', $this->entity);
        $this->assertAttributeEquals(null, 'precision', $this->entity);

        $precision = 11.1;
        $unit = new ProductUnit();

        $this->setProperty($this->entity, 'precision', $precision);
        $this->setProperty($this->entity, 'unit', $unit);
        $this->entity->loadUnitPrecision();

        $unitPrecision = $this->entity->getUnitPrecision();
        $this->assertInstanceOf('OroB2B\Bundle\CatalogBundle\Model\CategoryUnitPrecision', $unitPrecision);
        $this->assertEquals($precision, $unitPrecision->getPrecision());
        $this->assertSame($unit, $unitPrecision->getUnit());

        $unitPrecision = CategoryUnitPrecision::create(3, new ProductUnit('set'));
        $this->entity->setUnitPrecision($unitPrecision);
        $this->assertSame($unitPrecision, $this->entity->getUnitPrecision());

        $this->entity->updateUnitPrecision();
        $this->assertAttributeEquals($unitPrecision->getPrecision(), 'precision', $this->entity);
        $this->assertAttributeEquals($unitPrecision->getUnit(), 'unit', $this->entity);

        $this->setProperty($this->entity, 'unitPrecision', null);
        $this->entity->updateUnitPrecision();
        $this->assertAttributeEquals(null, 'precision', $this->entity);
        $this->assertAttributeEquals(null, 'unit', $this->entity);
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
