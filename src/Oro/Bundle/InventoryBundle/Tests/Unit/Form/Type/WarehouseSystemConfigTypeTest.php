<?php

namespace Oro\Bundle\InventoryBundle\Tests\Unit\Form\Type;

use Symfony\Component\Validator\Validation;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\Form\Test\FormIntegrationTestCase;

use Oro\Bundle\FormBundle\Form\Type\CollectionType;
use Oro\Bundle\WarehouseProBundle\Entity\Warehouse;
use Oro\Bundle\WarehouseProBundle\Form\Type\WarehouseCollectionType;
use Oro\Bundle\WarehouseProBundle\Form\Type\WarehouseSelectType;
use Oro\Bundle\WarehouseProBundle\Form\Type\WarehouseSelectWithPriorityType;
use Oro\Bundle\WarehouseProBundle\Form\Type\WarehouseSystemConfigType;
use Oro\Bundle\WarehouseProBundle\SystemConfig\WarehouseConfig;
use Oro\Bundle\InventoryBundle\Tests\Unit\Form\Type\Stub\WarehouseSelectTypeStub;
use Oro\Component\Testing\Unit\EntityTrait;
use Oro\Component\Testing\Unit\Form\Type\Stub\EntityType;

class WarehouseSystemConfigTypeTest extends FormIntegrationTestCase
{
    use EntityTrait;

    /**
     * @var WarehouseSystemConfigType
     */
    protected $formType;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->formType = new WarehouseSystemConfigType(WarehouseConfig::class);

        parent::setUp();
    }

    /**
     * @return array
     */
    protected function getExtensions()
    {
        $entityType = new EntityType([]);
        $oroCollectionType = new CollectionType();
        $warehouseCollectionType = new WarehouseCollectionType();
        $warehouseWithPriorityType = new WarehouseSelectWithPriorityType();

        return [
            new PreloadedExtension(
                [
                    $oroCollectionType::NAME => $oroCollectionType,
                    $warehouseCollectionType::NAME => $warehouseCollectionType,
                    $warehouseWithPriorityType::NAME => $warehouseWithPriorityType,
                    WarehouseSelectType::NAME => new WarehouseSelectTypeStub(),
                    $entityType->getName() => $entityType,
                ],
                []
            ),
            new ValidatorExtension(Validation::createValidator())
        ];
    }

    public function testSubmit()
    {
        $defaultData = [];
        /** @var Warehouse $warehouse1 */
        $warehouse1 = $this->getEntity(Warehouse::class, ['id' => 1]);
        /** @var Warehouse $warehouse2 */
        $warehouse2 = $this->getEntity(Warehouse::class, ['id' => 2]);
        $expectedData = [
            new WarehouseConfig($warehouse1, 100),
            new WarehouseConfig($warehouse2, 200)
        ];

        $form = $this->factory->create($this->formType, $defaultData, []);
        $this->assertEquals($defaultData, $form->getData());

        $form->submit([
            [
                'warehouse' => 1,
                'priority' => 100,
            ],
            [
                'warehouse' => 2,
                'priority' => 200,
            ]
        ]);
        $this->assertTrue($form->isValid());
        $this->assertEquals($expectedData, $form->getData());
    }

    public function testGetParent()
    {
        $this->assertEquals(WarehouseCollectionType::NAME, $this->formType->getParent());
    }

    public function testGetName()
    {
        $this->assertEquals(WarehouseSystemConfigType::NAME, $this->formType->getName());
    }

    public function testGetBlockPrefix()
    {
        $this->assertEquals(WarehouseSystemConfigType::NAME, $this->formType->getBlockPrefix());
    }
}
