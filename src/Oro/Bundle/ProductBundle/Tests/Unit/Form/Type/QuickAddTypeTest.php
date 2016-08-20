<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Form\Type;

use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\Form\Test\FormIntegrationTestCase;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Validation;

use Oro\Bundle\FormBundle\Form\Type\CollectionType;
use Oro\Bundle\ProductBundle\Model\ProductRow;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Form\Type\ProductAutocompleteType;
use Oro\Bundle\ProductBundle\Form\Type\ProductRowCollectionType;
use Oro\Bundle\ProductBundle\Form\Type\ProductRowType;
use Oro\Bundle\ProductBundle\Form\Type\QuickAddType;
use Oro\Bundle\ProductBundle\Storage\ProductDataStorage;
use Oro\Bundle\ProductBundle\Tests\Unit\Form\Type\Stub\StubProductAutocompleteType;

class QuickAddTypeTest extends FormIntegrationTestCase
{
    /** @var QuickAddType */
    protected $formType;

    /**
     * {@inheritDoc}
     */
    protected function setUp()
    {
        $this->formType = new QuickAddType();

        parent::setUp();
    }

    /**
     * @return array
     */
    protected function getExtensions()
    {
        return [
            new PreloadedExtension([
                ProductRowCollectionType::NAME => new ProductRowCollectionType(),
                ProductRowType::NAME => new ProductRowType(),
                CollectionType::NAME => new CollectionType(),
                ProductAutocompleteType::NAME => new StubProductAutocompleteType(),
            ], []),
            new ValidatorExtension(Validation::createValidator())
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
        $products = [new Product(), new Product()];
        $options = [
            'products' => $products,
        ];

        $form = $this->factory->create($this->formType, null, $options);
        $form->submit($submittedData);

        $collectionProducts = $form->get(QuickAddType::PRODUCTS_FIELD_NAME)->getConfig()->getOption('products');
        $this->assertEquals($products, $collectionProducts);

        $this->assertTrue($form->isValid());
        $this->assertEquals($expectedData, $form->getData());
    }

    /**
     * @return array
     */
    public function submitDataProvider()
    {
        $productRow = new ProductRow();
        $productRow->productSku = 'sku';
        $productRow->productQuantity = 42;
        return [
            'valid data' => [
                'submittedData' => [
                    QuickAddType::PRODUCTS_FIELD_NAME => [
                        [
                            ProductDataStorage::PRODUCT_SKU_KEY => 'sku',
                            ProductDataStorage::PRODUCT_QUANTITY_KEY => '42',
                        ]
                    ],
                    QuickAddType::COMPONENT_FIELD_NAME => 'component',
                    QuickAddType::ADDITIONAL_FIELD_NAME => 'additional',
                ],
                'expectedData' => [
                    QuickAddType::PRODUCTS_FIELD_NAME => [
                        $productRow
                    ],
                    QuickAddType::COMPONENT_FIELD_NAME => 'component',
                    QuickAddType::ADDITIONAL_FIELD_NAME => 'additional',
                ],
            ],
        ];
    }

    public function testInvalidSubmit()
    {
        $form = $this->factory->create($this->formType);
        $form->submit([]);
        $this->assertFalse($form->isValid());
    }

    public function testConfigureOptions()
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|OptionsResolver $resolver */
        $resolver = $this->getMock('Symfony\Component\OptionsResolver\OptionsResolver');
        $resolver->expects($this->once())
            ->method('setDefaults')
            ->with(
                $this->callback(
                    function (array $options) {
                        $this->assertArrayHasKey('products', $options);
                        $this->assertNull($options['products']);
                        return true;
                    }
                )
            );

        $this->formType->configureOptions($resolver);
    }

    public function testGetName()
    {
        $this->assertEquals(QuickAddType::NAME, $this->formType->getName());
    }
}
