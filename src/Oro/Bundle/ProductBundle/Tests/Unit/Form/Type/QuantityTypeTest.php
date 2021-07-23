<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Form\Type;

use Oro\Bundle\ProductBundle\Form\Type\QuantityType;
use Oro\Bundle\ProductBundle\Tests\Unit\Form\Type\Stub\QuantityParentTypeStub;
use Oro\Component\Testing\Unit\Form\Type\Stub\EntityType as EntityTypeStub;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;
use Oro\Component\Testing\Unit\PreloadedExtension;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\PropertyAccess\Exception\NoSuchPropertyException;
use Symfony\Component\PropertyAccess\PropertyAccess;

class QuantityTypeTest extends FormIntegrationTestCase
{
    use QuantityTypeTrait;

    /** @var QuantityType */
    protected $formType;

    /** @var QuantityParentTypeStub */
    protected $parentType;

    /**
     * {@inheritDoc}
     */
    protected function setUp(): void
    {
        $this->formType = $this->getQuantityType();
        $this->parentType = new QuantityParentTypeStub();

        parent::setUp();
    }

    /**
     * @dataProvider defaultDataProvider
     * @param array $options
     * @param mixed $expectedBefore
     * @param mixed $submittedData
     * @param mixed $expectedAfter
     */
    public function testSetDefaultData(array $options, $expectedBefore, $submittedData, $expectedAfter)
    {
        $form = $this->factory->create(QuantityType::class, null, $options);
        $this->assertEquals($expectedBefore, $form->getData());
        $form->submit($submittedData);
        $this->assertTrue($form->isValid());
        $this->assertTrue($form->isSynchronized());
        $this->assertEquals($expectedAfter, $form->getData());
    }

    /**
     * @return array
     */
    public function defaultDataProvider()
    {
        return [
            'submit empty should leave default' => [['default_data' => '42'], 42, null, 42],
            'submitted value should override default' => [['default_data' => '42'], 42, 1, 1],
            'submit value without default' => [['default_data' => null], null, 1, 1],
            'submit empty without default' => [['default_data' => null], null, null, null],
        ];
    }

    /**
     * @return array
     */
    protected function getExtensions()
    {
        $entityType = new EntityTypeStub(
            [
                1 => $this->getEntity(
                    'Oro\Bundle\ProductBundle\Entity\Product',
                    [
                        'id' => 1,
                        'unitPrecisions' => [
                            $this->getEntity(
                                'Oro\Bundle\ProductBundle\Entity\ProductUnitPrecision',
                                [
                                    'unit' => $this->getEntity(
                                        'Oro\Bundle\ProductBundle\Entity\ProductUnit',
                                        ['code' => 'kg']
                                    ),
                                    'precision' => 3,
                                ]
                            ),
                        ],
                    ]
                ),
                2 => $this->getEntity('Oro\Bundle\ProductBundle\Entity\Product', ['id' => 2]),
                3 => $this->getEntity('Oro\Bundle\ProductBundle\Entity\Product', ['id' => 3]),
                'kg' => $this->getEntity('Oro\Bundle\ProductBundle\Entity\ProductUnit', ['code' => 'kg']),
                'item' => $this->getEntity(
                    'Oro\Bundle\ProductBundle\Entity\ProductUnit',
                    ['code' => 'item', 'defaultPrecision' => 5]
                ),
            ]
        );

        return [
            new PreloadedExtension([
                QuantityParentTypeStub::class => $this->parentType,
                EntityType::class => $entityType,
                $this->getQuantityType()
            ], []),
        ];
    }

    /**
     * @param string $className
     * @param array $properties
     * @return object
     */
    protected function getEntity($className, array $properties = [])
    {
        $entity = new $className;
        $reflectionClass = new \ReflectionClass($className);
        $propertyAccessor = PropertyAccess::createPropertyAccessor();

        foreach ($properties as $property => $value) {
            try {
                $propertyAccessor->setValue($entity, $property, $value);
            } catch (NoSuchPropertyException $e) {
                $method = $reflectionClass->getProperty($property);
                $method->setAccessible(true);
                $method->setValue($entity, $value);
            } catch (\ReflectionException $e) {
            }
        }

        return $entity;
    }
}
