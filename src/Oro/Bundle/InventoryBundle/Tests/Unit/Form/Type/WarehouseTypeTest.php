<?php

namespace Oro\Bundle\InventoryBundle\Tests\Unit\Form\Type;

use Symfony\Component\Form\Test\FormIntegrationTestCase;

use Oro\Bundle\InventoryBundle\Form\Type\WarehouseType;

class WarehouseTypeTest extends FormIntegrationTestCase
{
    /** @var  WarehouseType $type */
    protected $formType;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();
        $this->formType = new WarehouseType();
    }

    /**
     * @param bool $isValid
     * @param mixed $defaultData
     * @param array $submittedData
     * @param mixed $expectedData
     * @param array $options
     * @dataProvider submitProvider
     */
    public function testSubmit($isValid, $defaultData, $submittedData, $expectedData, array $options = [])
    {
        $form = $this->factory->create($this->formType, $defaultData, $options);
        $this->assertEquals($defaultData, $form->getData());
        $form->submit($submittedData);
        $this->assertEquals($isValid, $form->isValid());
        $this->assertEquals($expectedData, $form->getData());
    }

    /**
     * @return array
     */
    public function submitProvider()
    {
        return [
            'warehouse_valid' => [
                'isValid'       => true,
                'defaultData'   => ['name' => 'Warehouse 1'],
                'submittedData' => [
                    'name' => 'Warehouse 2'
                ],
                'expectedData'  => ['name' => 'Warehouse 2']
            ]
        ];
    }

    public function testGetName()
    {
        $this->assertEquals(WarehouseType::NAME, $this->formType->getName());
    }
}
