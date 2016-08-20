<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Form\Type;

use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\PropertyAccess\Exception\NoSuchPropertyException;
use Symfony\Component\PropertyAccess\PropertyAccess;

use Oro\Component\Testing\Unit\Form\Type\Stub\EntityType;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Model\ProductHolderInterface;
use Oro\Bundle\ProductBundle\Form\Type\QuantityType;
use Oro\Bundle\ProductBundle\Tests\Unit\Form\Type\Stub\QuantityParentTypeStub;

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
    protected function setUp()
    {
        $this->formType = $this->getQuantityType();
        $this->parentType = new QuantityParentTypeStub();

        $this->addRoundingServiceExpect();

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
        $form = $this->factory->create($this->formType, null, $options);
        $this->assertEquals($expectedBefore, $form->getData());
        $form->submit($submittedData);
        $this->assertTrue($form->isValid());
        $this->assertEquals($expectedAfter, $form->getData());
    }


    /**
     * @return array
     */
    public function defaultDataProvider()
    {
        return [
            'submit empty should leave default' => [['default_data' => 42], 42, null, 42],
            'submitted value should override default' => [['default_data' => 42], 42, 1, 1],
            'submit value without default' => [['default_data' => null], null, 1, 1],
            'submit empty without default' => [['default_data' => null], null, null, null],
        ];
    }

    /**
     * @return array
     */
    protected function getExtensions()
    {
        $entityType = new EntityType(
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
            new PreloadedExtension(['entity' => $entityType, QuantityType::NAME => $this->getQuantityType()], []),
        ];
    }

    /**
     * @param array $options
     * @param array $submittedData
     * @param array $expectedData
     * @param array $expectedException
     *
     * @dataProvider quantityDataProvider
     */
    public function testRoundQuantity(
        array $options = [],
        array $submittedData = [],
        array $expectedData = [],
        array $expectedException = []
    ) {
        if ($expectedException) {
            list($exception, $message) = $expectedException;
            $this->setExpectedException($exception, $message);
        }

        $this->parentType->setQuantityOptions($options);
        $form = $this->factory->create($this->parentType);
        $form->submit($submittedData);
        $this->assertTrue($form->isValid());
        $this->assertEquals($expectedData, $form->getData());
    }

    /**
     * @return array
     *
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function quantityDataProvider()
    {
        return [
            'product passed from parent type, product field not exists' => [
                [
                    'product_holder' => $this->getProductHolder(
                        $this->getEntity('Oro\Bundle\ProductBundle\Entity\Product', ['id' => 2])
                    ),
                ],
                [],
                [],
                ['\InvalidArgumentException', 'Missing "productUnit" on form'],
            ],
            'product not passed from parent type, product field not exists' => [
                [],
                [],
                ['productField' => null, 'productUnitField' => null, 'quantityField' => null],
            ],
            'product from product field' => [
                ['product_field' => 'productField'],
                ['productField' => 1],
                [],
                ['\InvalidArgumentException', 'Missing "productUnit" on form'],
            ],
            'invalid product' => [
                ['product_field' => 'productField'],
                ['productField' => 4],
                ['productUnitField' => null, 'quantityField' => null],
            ],
            'product from field overrides product from options' => [
                [
                    'product_field' => 'productField',
                    'product_unit_field' => 'productUnitField',
                    'product_holder' => $this->getProductHolder(
                        $this->getEntity('Oro\Bundle\ProductBundle\Entity\Product', ['id' => 2])
                    ),
                ],
                ['productField' => 3],
                [
                    'productField' => $this->getEntity('Oro\Bundle\ProductBundle\Entity\Product', ['id' => 3]),
                    'productUnitField' => null,
                    'quantityField' => null,
                ],
            ],
            'invalid product unit field' => [
                ['product_field' => 'productField', 'product_unit_field' => 'productUnitNotExists'],
                ['productField' => 1],
                [],
                ['\InvalidArgumentException', 'Missing "productUnitNotExists" on form'],
            ],
            'invalid product unit' => [
                ['product_field' => 'productField', 'product_unit_field' => 'productUnitField'],
                ['productField' => 2, 'productUnitField' => 'missing'],
                [
                    'productField' => $this->getEntity('Oro\Bundle\ProductBundle\Entity\Product', ['id' => 2]),
                    'quantityField' => null,
                ],
            ],
            'get precision from unit precision' => [
                ['product_field' => 'productField', 'product_unit_field' => 'productUnitField'],
                ['productField' => 1, 'productUnitField' => 'kg', 'quantityField' => 0.555555555555],
                [
                    'productField' => $this->getEntity(
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
                    'productUnitField' => $this->getEntity(
                        'Oro\Bundle\ProductBundle\Entity\ProductUnit',
                        ['code' => 'kg']
                    ),
                    'quantityField' => 0.556,
                ],
            ],
            'get default precision from unit' => [
                ['product_field' => 'productField', 'product_unit_field' => 'productUnitField'],
                ['productField' => 2, 'productUnitField' => 'item', 'quantityField' => 0.555555555555],
                [
                    'productField' => $this->getEntity('Oro\Bundle\ProductBundle\Entity\Product', ['id' => 2]),
                    'productUnitField' => $this->getEntity(
                        'Oro\Bundle\ProductBundle\Entity\ProductUnit',
                        ['code' => 'item', 'defaultPrecision' => 5]
                    ),
                    'quantityField' => 0.55556,
                ],
            ],
        ];
    }

    /**
     * @param Product|null $product
     * @return \PHPUnit_Framework_MockObject_MockObject|ProductHolderInterface
     */
    protected function getProductHolder(Product $product = null)
    {
        $holder = $this->getMock('Oro\Bundle\ProductBundle\Model\ProductHolderInterface');
        $holder->expects($this->any())->method('getProduct')->willReturn($product);

        return $holder;
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
