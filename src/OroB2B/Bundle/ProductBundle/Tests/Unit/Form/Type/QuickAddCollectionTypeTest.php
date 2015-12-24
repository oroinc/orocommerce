<?php

namespace OroB2B\Bundle\ProductBundle\Tests\Unit\Form\Type;

use Symfony\Component\Form\PreloadedExtension;

use Oro\Bundle\FormBundle\Form\Type\CollectionType;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;

use OroB2B\Bundle\ProductBundle\Form\Type\QuickAddCollectionType;
use OroB2B\Bundle\ProductBundle\Form\Type\QuickAddProductRowType;
use OroB2B\Bundle\ProductBundle\Storage\ProductDataStorage;

class QuickAddCollectionTypeTest extends FormIntegrationTestCase
{
    /**
     * @var array
     */
    protected $validData = [
        [
            ProductDataStorage::PRODUCT_SKU_KEY => 'SKU1',
            ProductDataStorage::PRODUCT_QUANTITY_KEY => 1
        ],
        [
            ProductDataStorage::PRODUCT_SKU_KEY => 'SKU2',
            ProductDataStorage::PRODUCT_QUANTITY_KEY => 2.5
        ]
    ];

    /** @var QuickAddCollectionType */
    protected $formType;

    /**
     * {@inheritDoc}
     */
    protected function setUp()
    {
        $this->formType = new QuickAddCollectionType();

        parent::setUp();
    }

    /**
     * @return array
     */
    protected function getExtensions()
    {
        return [
            new PreloadedExtension([
                CollectionType::NAME => new CollectionType(),
                QuickAddProductRowType::NAME => new QuickAddProductRowType()
            ], []),
        ];
    }

    /**
     * @dataProvider submitDataProvider
     *
     * @param mixed $submittedData
     * @param mixed $expectedData
     */
    public function testSubmit($submittedData, $expectedData)
    {
        $form = $this->factory->create($this->formType);
        $form->setData($this->validData);
        $form->submit($submittedData);

        $this->assertTrue($form->isValid());
        $this->assertEquals($expectedData, $form->getData());
    }

    /**
     * @return array
     */
    public function submitDataProvider()
    {
        return [
            'valid data' => [
                'submittedData' => $this->validData,
                'expectedData' => $this->validData
            ],
        ];
    }
}
