<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Form\Type;

use Oro\Bundle\FormBundle\Form\Type\CollectionType;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Form\Type\ProductAutocompleteType;
use Oro\Bundle\ProductBundle\Form\Type\ProductRowCollectionType;
use Oro\Bundle\ProductBundle\Form\Type\ProductRowType;
use Oro\Bundle\ProductBundle\Form\Type\ProductUnitsType;
use Oro\Bundle\ProductBundle\Form\Type\QuickAddType;
use Oro\Bundle\ProductBundle\Helper\ProductGrouper\ProductsGrouperFactory;
use Oro\Bundle\ProductBundle\Model\ProductRow;
use Oro\Bundle\ProductBundle\Provider\ProductUnitsProvider;
use Oro\Bundle\ProductBundle\Storage\ProductDataStorage;
use Oro\Bundle\ProductBundle\Tests\Unit\Form\Type\Stub\StubProductAutocompleteType;
use Oro\Component\Testing\Unit\PreloadedExtension;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\Test\FormIntegrationTestCase;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Validation;

class QuickAddTypeTest extends FormIntegrationTestCase
{
    /** @var QuickAddType */
    private $formType;

    protected function setUp(): void
    {
        $this->formType = new QuickAddType(new ProductsGrouperFactory());

        parent::setUp();
    }

    /**
     * {@inheritDoc}
     */
    protected function getExtensions(): array
    {
        $unitsProviderMock = $this->createMock(ProductUnitsProvider::class);
        $unitsProviderMock->expects($this->any())
            ->method('getAvailableProductUnits')
            ->willReturn([]);

        return [
            new PreloadedExtension([
                $this->formType,
                ProductRowCollectionType::class => new ProductRowCollectionType(),
                ProductRowType::class => new ProductRowType($unitsProviderMock),
                CollectionType::class => new CollectionType(),
                ProductAutocompleteType::class => new StubProductAutocompleteType(),
                ProductUnitsType::class => new ProductUnitsType($unitsProviderMock)
            ], []),
            new ValidatorExtension(Validation::createValidator())
        ];
    }

    /**
     * @dataProvider submitDataProvider
     */
    public function testSubmit(mixed $submittedData, mixed $expectedData)
    {
        $products = [new Product(), new Product()];
        $options = [
            'products' => $products,
        ];

        $form = $this->factory->create(QuickAddType::class, null, $options);
        $form->submit($submittedData);

        $collectionProducts = $form->get(QuickAddType::PRODUCTS_FIELD_NAME)->getConfig()->getOption('products');
        $this->assertEquals($products, $collectionProducts);

        $this->assertTrue($form->isValid());
        $this->assertTrue($form->isSynchronized());
        $this->assertEquals($expectedData, $form->getData());
    }

    public function submitDataProvider(): array
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
                    QuickAddType::TRANSITION_FIELD_NAME => 'start_from_quickorderform',
                ],
                'expectedData' => [
                    QuickAddType::PRODUCTS_FIELD_NAME => [
                        $productRow
                    ],
                    QuickAddType::COMPONENT_FIELD_NAME => 'component',
                    QuickAddType::ADDITIONAL_FIELD_NAME => 'additional',
                    QuickAddType::TRANSITION_FIELD_NAME => 'start_from_quickorderform',
                ],
            ],
        ];
    }

    public function testInvalidSubmit()
    {
        $form = $this->factory->create(QuickAddType::class);
        $form->submit([]);
        $this->assertFalse($form->isValid());
        $this->assertTrue($form->isSynchronized());
    }

    public function testConfigureOptions()
    {
        $resolver = $this->createMock(OptionsResolver::class);
        $resolver->expects($this->once())
            ->method('setDefaults')
            ->with(
                $this->callback(function (array $options) {
                    $this->assertArrayHasKey('products', $options);
                    $this->assertNull($options['products']);
                    return true;
                })
            );

        $this->formType->configureOptions($resolver);
    }
}
