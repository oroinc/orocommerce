<?php

namespace Oro\Bundle\InventoryBundle\Tests\Unit\Form\Type;

use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\Form\Test\FormIntegrationTestCase;
use Symfony\Component\Validator\Validation;

use Oro\Bundle\WarehouseBundle\Entity\Warehouse;
use Oro\Component\Testing\Unit\Form\Type\Stub\EntityType;
use Oro\Bundle\WarehouseBundle\Form\Type\WarehouseSelectWithPriorityType;
use Oro\Bundle\WarehouseBundle\Form\Type\WarehouseSelectType;
use Oro\Bundle\InventoryBundle\Tests\Unit\Form\Type\Stub\WarehouseSelectTypeStub;

class WarehouseSelectWithPriorityTypeTest extends FormIntegrationTestCase
{
    /**
     * @var WarehouseSelectWithPriorityType
     */
    protected $formType;

    protected function setUp()
    {
        $this->formType = new WarehouseSelectWithPriorityType();

        parent::setUp();
    }

    /**
     * @dataProvider submitDataProvider
     * @param array $defaultData
     * @param array $submittedData
     * @param array $expectedData
     */
    public function testSubmit(array $defaultData, array $submittedData, array $expectedData)
    {
        $form = $this->factory->create($this->formType, $defaultData, []);

        $this->assertEquals($defaultData, $form->getData());

        $form->submit($submittedData);
        $this->assertTrue($form->isValid());
        $this->assertEquals($expectedData, $form->getData());
    }

    /**
     * @return array
     */
    public function submitDataProvider()
    {
        $existingWarehouse = $this->getWarehouse(WarehouseSelectTypeStub::WAREHOUSE_1);

        /** @var Warehouse $expectedWarehouse */
        $expectedWarehouse = $this->getWarehouse(WarehouseSelectTypeStub::WAREHOUSE_2);

        return [
            'without default data' => [
                'defaultData'   => [],
                'submittedData' => [
                    'warehouse' => WarehouseSelectTypeStub::WAREHOUSE_2,
                    'priority'  => 100,
                ],
                'expectedData' => [
                    'warehouse' => $expectedWarehouse,
                    'priority'  => 100,
                ]
            ],
            'with default data' => [
                'defaultData'   => [
                    'warehouse' => $existingWarehouse,
                    'priority'  => 50,
                ],
                'submittedData' => [
                    'warehouse' => WarehouseSelectTypeStub::WAREHOUSE_2,
                    'priority'  => 100,
                ],
                'expectedData' => [
                    'warehouse' => $expectedWarehouse,
                    'priority'  => 100,
                ]
            ],
        ];
    }

    public function testGetName()
    {
        $this->assertEquals(WarehouseSelectWithPriorityType::NAME, $this->formType->getName());
    }

    public function testGetBlockPrefix()
    {
        $this->assertEquals(WarehouseSelectWithPriorityType::NAME, $this->formType->getBlockPrefix());
    }

    /**
     * @param int $id
     * @return Warehouse
     */
    protected function getWarehouse($id)
    {
        $warehouse = new Warehouse();
        $reflectionClass = new \ReflectionClass($warehouse);
        $method = $reflectionClass->getProperty('id');
        $method->setAccessible(true);
        $method->setValue($warehouse, $id);

        return $warehouse;
    }
    /**
     * @return array
     */
    protected function getExtensions()
    {
        $entityType = new EntityType([]);

        return [
            new PreloadedExtension(
                [
                    $entityType->getName() => $entityType,
                    WarehouseSelectType::NAME => new WarehouseSelectTypeStub(),
                ],
                []
            ),
            new ValidatorExtension(Validation::createValidator())
        ];
    }
}
