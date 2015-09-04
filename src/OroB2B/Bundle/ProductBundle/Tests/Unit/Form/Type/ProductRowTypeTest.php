<?php

namespace OroB2B\Bundle\ProductBundle\Tests\Unit\Form\Type;

use Symfony\Component\Form\Test\FormIntegrationTestCase;

use OroB2B\Bundle\ProductBundle\Form\Type\ProductRowType;

class ProductRowTypeTest extends FormIntegrationTestCase
{
    /**
     * @var ProductRowType
     */
    protected $formType;

    /**
     * {@inheritDoc}
     */
    protected function setUp()
    {
        $this->formType = new ProductRowType();

        parent::setUp();
    }

    /**
     * {@inheritDoc}
     */
    protected function tearDown()
    {
        unset($this->formType);
    }

    /**
     * @dataProvider submitDataProvider
     * @param array|null $defaultData
     * @param array $submittedData
     * @param array $expectedData
     */
    public function testSubmit($defaultData, array $submittedData, array $expectedData)
    {
        $form = $this->factory->create($this->formType, $defaultData);

        $this->assertEquals($defaultData, $form->getData());
        $form->submit($submittedData);
        $this->assertTrue($form->isValid());

        $data = $form->getData();

        $this->assertEquals($expectedData, $data);
    }

    /**
     * @return array
     */
    public function submitDataProvider()
    {
        return [
            'without default data' => [
                'defaultData' => null,
                'submittedData' => [
                    'productSku' => 'SKU_001',
                    'productQuantity' => '10'
                ],
                'expectedData' => [
                    'productSku' => 'SKU_001',
                    'productQuantity' => '10'
                ]
            ],
            'with default data' => [
                'defaultData' => [
                    'productSku' => 'SKU_001',
                    'productQuantity' => '10'
                ],
                'submittedData' => [
                    'productSku' => 'SKU_002',
                    'productQuantity' => '20'
                ],
                'expectedData' => [
                    'productSku' => 'SKU_002',
                    'productQuantity' => '20'
                ]
            ]
        ];
    }
    
    public function testGetName()
    {
        $this->assertEquals(ProductRowType::NAME, $this->formType->getName());
    }
}
