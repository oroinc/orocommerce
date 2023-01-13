<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\ProductVariant\Form\Type;

use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeFamily;
use Oro\Bundle\EntityExtendBundle\Tests\Unit\Fixtures\TestEnumValue;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Provider\LocalizationProviderInterface;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductName;
use Oro\Bundle\ProductBundle\ProductVariant\Form\Type\FrontendVariantFiledType;
use Oro\Bundle\ProductBundle\ProductVariant\Registry\ProductVariantTypeHandlerInterface;
use Oro\Bundle\ProductBundle\ProductVariant\Registry\ProductVariantTypeHandlerRegistry;
use Oro\Bundle\ProductBundle\Provider\ProductVariantAvailabilityProvider;
use Oro\Bundle\ProductBundle\Provider\VariantField;
use Oro\Bundle\ProductBundle\Provider\VariantFieldProvider;
use Oro\Bundle\ProductBundle\Tests\Unit\Stub\ProductStub;
use Oro\Bundle\TranslationBundle\Entity\Language;
use Oro\Component\Testing\Unit\EntityTrait;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;
use Oro\Component\Testing\Unit\PreloadedExtension;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
use Symfony\Component\OptionsResolver\Exception\MissingOptionsException;
use Symfony\Component\OptionsResolver\OptionsResolver;

class FrontendVariantFiledTypeTest extends FormIntegrationTestCase
{
    use EntityTrait;

    private const FIELD_COLOR = 'testColor';
    private const FIELD_NEW = 'testNew';
    private const PRODUCT_CLASS = Product::class;

    /** @var ProductVariantAvailabilityProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $productVariantAvailabilityProvider;

    /** @var ProductVariantTypeHandlerRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $productVariantTypeHandlerRegistry;

    /** @var VariantFieldProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $variantFieldProvider;

    /** @var LocalizationProviderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $localizationProvider;

    /** @var FrontendVariantFiledType */
    private $type;

    protected function setUp(): void
    {
        $this->productVariantAvailabilityProvider = $this->createMock(ProductVariantAvailabilityProvider::class);
        $this->productVariantTypeHandlerRegistry = $this->createMock(ProductVariantTypeHandlerRegistry::class);
        $this->variantFieldProvider = $this->createMock(VariantFieldProvider::class);
        $this->localizationProvider = $this->createMock(LocalizationProviderInterface::class);

        $this->type = new FrontendVariantFiledType(
            $this->productVariantAvailabilityProvider,
            $this->productVariantTypeHandlerRegistry,
            $this->variantFieldProvider,
            $this->localizationProvider,
            $this->getPropertyAccessor(),
            self::PRODUCT_CLASS
        );
        parent::setUp();
    }

    /**
     * {@inheritDoc}
     */
    protected function getExtensions(): array
    {
        return [
            new PreloadedExtension([$this->type], [])
        ];
    }

    public function testGetBlockPrefix()
    {
        $this->assertEquals('oro_product_product_variant_frontend_variant_field', $this->type->getBlockPrefix());
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testBuildFormConfigurableProduct()
    {
        $attributeFamily = $this->getEntity(AttributeFamily::class);

        $parentProduct = $this->getEntity(Product::class, [
            'type' => Product::TYPE_CONFIGURABLE,
            'variantFields' => [self::FIELD_COLOR, self::FIELD_NEW],
            'attributeFamily' => $attributeFamily,
        ]);

        $defaultVariant = new ProductStub();
        $defaultVariant->{self::FIELD_COLOR} = new TestEnumValue('id', 'name');
        $defaultVariant->{self::FIELD_NEW} = true;

        $options = [
            'parentProduct' => $parentProduct,
        ];

        $enumAvailability = [
            'red' => false,
            'green' => true
        ];

        $enumHandler = $this->getTypeHandler(
            self::FIELD_COLOR,
            $enumAvailability,
            [
                'data' => $defaultVariant->{self::FIELD_COLOR},
                'label' => self::FIELD_COLOR,
                'placeholder' => 'oro.product.type.please_select_option',
                'empty_data' => null
            ]
        );

        $booleanAvailability = [
            0 => false,
            1 => true
        ];

        $booleanHandler = $this->getTypeHandler(
            self::FIELD_NEW,
            $booleanAvailability,
            [
                'data' => $defaultVariant->{self::FIELD_NEW},
                'label' => self::FIELD_NEW,
                'placeholder' => 'oro.product.type.please_select_option',
                'empty_data' => null
            ]
        );

        $this->productVariantTypeHandlerRegistry->expects($this->exactly(2))
            ->method('getVariantTypeHandler')
            ->withConsecutive(['enum'], ['boolean'])
            ->willReturnOnConsecutiveCalls($enumHandler, $booleanHandler);

        $this->productVariantAvailabilityProvider->expects($this->exactly(2))
            ->method('getCustomFieldType')
            ->withConsecutive([self::FIELD_COLOR], [self::FIELD_NEW])
            ->willReturnOnConsecutiveCalls('enum', 'boolean');

        $this->productVariantAvailabilityProvider->expects($this->once())
            ->method('getVariantFieldsAvailability')
            ->with($parentProduct, [])
            ->willReturn([
                self::FIELD_COLOR => [
                    'red' => false,
                    'green' => true
                ],
                self::FIELD_NEW => [
                    0 => false,
                    1 => true
                ]
            ]);

        $customFields = [
            self::FIELD_COLOR => new VariantField(self::FIELD_COLOR, self::FIELD_COLOR),
            self::FIELD_NEW => new VariantField(self::FIELD_NEW, self::FIELD_NEW)
        ];
        $this->variantFieldProvider->expects($this->once())
            ->method('getVariantFields')
            ->with($attributeFamily)
            ->willReturn($customFields);

        $form = $this->factory->create(FrontendVariantFiledType::class, $defaultVariant, $options);

        $this->assertTrue($form->has(self::FIELD_COLOR));
        $this->assertTrue($form->has(self::FIELD_NEW));

        $variantProduct = new ProductStub();
        $variantProduct->{self::FIELD_COLOR} = new TestEnumValue('id2', 'name2');
        $variantProduct->{self::FIELD_NEW} = false;

        $submittedData = [
            self::FIELD_COLOR => 'green',
            self::FIELD_NEW => true
        ];

        $variantFieldValues = [
            self::FIELD_COLOR => $variantProduct->{self::FIELD_COLOR},
            self::FIELD_NEW => $variantProduct->{self::FIELD_NEW}
        ];

        $this->productVariantAvailabilityProvider->expects($this->once())
            ->method('getVariantFieldsValuesForVariant')
            ->with($parentProduct, $defaultVariant)
            ->willReturn($variantFieldValues);

        $this->productVariantAvailabilityProvider->expects($this->once())
            ->method('getSimpleProductByVariantFields')
            ->with($parentProduct, $variantFieldValues, false)
            ->willReturn($variantProduct);

        $form->submit($submittedData);
        $this->assertEquals(new TestEnumValue('id', 'name'), $defaultVariant->{self::FIELD_COLOR});
        $this->assertEquals(true, $defaultVariant->{self::FIELD_NEW});

        $this->assertEquals($variantProduct, $form->getData());
    }

    public function testConfigureOptions()
    {
        $resolver = $this->createMock(OptionsResolver::class);
        $resolver->expects($this->once())
            ->method('setRequired')
            ->with(['parentProduct']);

        $this->type->configureOptions($resolver);
    }

    public function testBuildFormWithoutProductInOptions()
    {
        $this->expectException(MissingOptionsException::class);
        $this->expectExceptionMessage('The required option "parentProduct" is missing.');

        $this->factory->create(FrontendVariantFiledType::class);
    }

    public function testBuildWhenRequiredFieldProductHasOtherObject()
    {
        $this->expectException(InvalidOptionsException::class);
        $this->expectExceptionMessage(
            'The option "parentProduct" with value stdClass is expected to be of type'
            . ' "Oro\Bundle\ProductBundle\Entity\Product", but is of type "stdClass".'
        );

        $options['parentProduct'] = new \stdClass();
        $this->factory->create(FrontendVariantFiledType::class, [], $options);
    }

    private function getTypeHandler(
        string $fieldName,
        array $availability,
        array $expectedOptions
    ): ProductVariantTypeHandlerInterface {
        $form = $this->factory->createNamed($fieldName, FormType::class, null, ['auto_initialize' => false]);

        $handler = $this->createMock(ProductVariantTypeHandlerInterface::class);
        $handler->expects($this->once())
            ->method('createForm')
            ->with($fieldName, $availability, $expectedOptions)
            ->willReturn($form);

        return $handler;
    }

    /**
     * @dataProvider getFinishViewDataProvider
     */
    public function testFinishView(
        array $productVariantNames,
        string $expectedSimpleProductName,
        ?Localization $localization = null
    ) {
        $form = $this->createMock(FormInterface::class);

        $formView = new FormView();

        $product = new Product();
        $product->setVariantFields(['field_first', 'field_second']);

        $productVariantId = 1;
        $productVariantSku = 'SKU';

        $productVariant = new ProductStub();
        $productVariant->setId($productVariantId);
        $productVariant->setSku($productVariantSku);
        $productVariant->setNames($productVariantNames);

        $this->productVariantAvailabilityProvider->expects($this->once())
            ->method('getSimpleProductsByVariantFields')
            ->with($product)
            ->willReturn([$productVariant]);

        $this->productVariantAvailabilityProvider->expects($this->exactly(2))
            ->method('getVariantFieldScalarValue')
            ->withConsecutive(
                [$productVariant, 'field_first'],
                [$productVariant, 'field_second']
            )
            ->willReturnOnConsecutiveCalls('value1', 'value2');

        $this->localizationProvider->expects($this->any())
            ->method('getCurrentLocalization')
            ->willReturn($localization);

        $this->type->finishView($formView, $form, ['parentProduct' => $product]);

        $this->assertArrayHasKey('attr', $formView->vars);

        $attr = $formView->vars['attr'];
        $this->assertArrayHasKey('data-page-component-options', $attr);

        $expectedComponentOptions = [
            'simpleProductVariants' => [
                $productVariantId => [
                    'sku' => $productVariantSku,
                    'name' => $expectedSimpleProductName,
                    'attributes' => [
                        'field_first' => 'value1',
                        'field_second' => 'value2',
                    ]
                ],
            ],
            'view' => 'oroproduct/js/app/views/base-product-variants-view'
        ];
        $this->assertEquals(
            json_encode($expectedComponentOptions, JSON_THROW_ON_ERROR),
            $attr['data-page-component-options']
        );
    }

    public function getFinishViewDataProvider(): array
    {
        $productVariantDefaultName = 'simpleProductName';
        $productVariantDefaultNameLocalized = 'simpleProductNameLocalized';

        $localization = new Localization();
        $localization->setLanguage((new Language())->setCode('en_US'));

        return [
            'no default name' => [
                'productVariantNames' => [],
                'expectedSimpleProductName' => '',
            ],
            'default name' => [
                'productVariantNames' => [(new ProductName())->setString($productVariantDefaultName)],
                'expectedSimpleProductName' => $productVariantDefaultName,
            ],
            'localized name' => [
                'productVariantNames' => [
                    (new ProductName())->setString($productVariantDefaultName),
                    (new ProductName())->setString($productVariantDefaultNameLocalized)
                        ->setLocalization($localization),
                ],
                'expectedSimpleProductName' => $productVariantDefaultNameLocalized,
                'localization' => $localization,
            ],
        ];
    }
}
