<?php

namespace Oro\Bundle\WarehouseBundle\Tests\Unit\Form\Type;

use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\Validator\Validation;

use Oro\Bundle\FormBundle\Form\Type\CollectionType;
use Oro\Bundle\WarehouseProBundle\Entity\Warehouse;
use Oro\Bundle\WarehouseProBundle\Form\Type\WarehouseCollectionType;
use Oro\Bundle\WarehouseProBundle\Form\Type\WarehouseSelectType;
use Oro\Bundle\WarehouseProBundle\Form\Type\WarehouseSelectWithPriorityType;
use Oro\Bundle\WarehouseProBundle\SystemConfig\WarehouseConfig;
use Oro\Bundle\WarehouseBundle\Tests\Unit\Form\Type\Stub\WarehouseSelectTypeStub;
use Oro\Component\Testing\Unit\EntityTrait;
use Oro\Component\Testing\Unit\Form\Type\Stub\EntityType;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;

class WarehouseCollectionTypeTest extends FormIntegrationTestCase
{
    use EntityTrait;

    /**
     * @var WarehouseCollectionType
     */
    protected $type;

    protected function setUp()
    {
        parent::setUp();
        $this->type = new WarehouseCollectionType();
    }

    /**
     * @dataProvider submitDataProvider
     *
     * @param array|WarehouseConfig[] $existing
     * @param array $submitted
     * @param array|WarehouseConfig[] $expected
     */
    public function testSubmit(array $existing, array $submitted, array $expected = null)
    {
        $options = [
            'options' => [
                'data_class' => WarehouseConfig::class
            ]
        ];

        $form = $this->factory->create($this->type, $existing, $options);
        $form->submit($submitted);

        $this->assertTrue($form->isValid());
        $this->assertEquals($expected, $form->getData());
    }

    /**
     * @return array
     */
    public function submitDataProvider()
    {
        /** @var Warehouse $warehouse1 */
        $warehouse1 = $this->getEntity(Warehouse::class, ['id' => 1]);
        /** @var Warehouse $warehouse2 */
        $warehouse2 = $this->getEntity(Warehouse::class, ['id' => 2]);
        /** @var Warehouse $warehouse3 */
        $warehouse3 = $this->getEntity(Warehouse::class, ['id' => 3]);

        return [
            'test' => [
                'existing' => [
                    new WarehouseConfig($warehouse1, 100),
                    new WarehouseConfig($warehouse2, 200),
                    new WarehouseConfig($warehouse3, 300),
                ],
                'submitted' => [
                    [
                        WarehouseSelectWithPriorityType::WAREHOUSE_FIELD => '3',
                        WarehouseSelectWithPriorityType::PRIORITY_FIELD => '500',
                    ],
                    [
                        WarehouseSelectWithPriorityType::WAREHOUSE_FIELD => '1',
                        WarehouseSelectWithPriorityType::PRIORITY_FIELD => '400',
                    ],
                    [
                        WarehouseSelectWithPriorityType::WAREHOUSE_FIELD => '2',
                        WarehouseSelectWithPriorityType::PRIORITY_FIELD => '600',
                    ],
                    [
                        WarehouseSelectWithPriorityType::WAREHOUSE_FIELD => '',
                        WarehouseSelectWithPriorityType::PRIORITY_FIELD => '',
                    ]
                ],
                'expected' => [
                    new WarehouseConfig($warehouse3, 500),
                    new WarehouseConfig($warehouse1, 400),
                    new WarehouseConfig($warehouse2, 600),
                ]
            ]
        ];
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
                    CollectionType::NAME => new CollectionType(),
                    WarehouseSelectWithPriorityType::NAME => new WarehouseSelectWithPriorityType(),
                    WarehouseSelectType::NAME => new WarehouseSelectTypeStub(),
                    WarehouseCollectionType::NAME => new WarehouseCollectionType(),
                    $entityType->getName() => $entityType,
                ],
                []
            ),
            new ValidatorExtension(Validation::createValidator())
        ];
    }

    public function testGetName()
    {
        $this->assertSame(WarehouseCollectionType::NAME, $this->type->getName());
    }

    public function testGetParent()
    {
        $this->assertSame(CollectionType::NAME, $this->type->getParent());
    }

    public function testGetBlockPrefix()
    {
        $this->assertSame(WarehouseCollectionType::NAME, $this->type->getBlockPrefix());
    }
}
