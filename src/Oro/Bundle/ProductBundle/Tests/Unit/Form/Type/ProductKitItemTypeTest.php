<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Form\Type;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\FormBundle\Provider\FormFieldsMapProvider;
use Oro\Bundle\FormBundle\Tests\Unit\Stub\TooltipFormExtensionStub;
use Oro\Bundle\LocaleBundle\Form\Type\LocalizedFallbackValueCollectionType;
use Oro\Bundle\LocaleBundle\Tests\Unit\Form\Type\Stub\LocalizedFallbackValueCollectionTypeStub;
use Oro\Bundle\ProductBundle\Entity\ProductKitItem;
use Oro\Bundle\ProductBundle\Entity\ProductKitItemLabel;
use Oro\Bundle\ProductBundle\Entity\ProductKitItemProduct;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Form\Type\ProductKitItemProductsType;
use Oro\Bundle\ProductBundle\Form\Type\ProductKitItemType;
use Oro\Bundle\ProductBundle\Form\Type\ProductUnitSelectType;
use Oro\Bundle\ProductBundle\Form\Type\QuantityType;
use Oro\Bundle\ProductBundle\Formatter\UnitLabelFormatterInterface;
use Oro\Bundle\ProductBundle\Tests\Unit\Stub\ProductStub;
use Oro\Component\Testing\Unit\Form\Type\Stub\EntityTypeStub;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;
use Oro\Component\Testing\Unit\PreloadedExtension;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Contracts\Translation\TranslatorInterface;

class ProductKitItemTypeTest extends FormIntegrationTestCase
{
    use QuantityTypeTrait;

    private ProductKitItemType $type;

    private DataTransformerInterface|MockObject $modelDataTransformer;

    private ProductKitItemProductsType $kitItemProductsType;

    private UnitLabelFormatterInterface|MockObject $productUnitLabelFormatter;

    private FormFieldsMapProvider|MockObject $fieldsMapProvider;

    private array $fieldsMap;

    protected function setUp(): void
    {
        $this->fieldsMapProvider = $this->createMock(FormFieldsMapProvider::class);
        $this->type = new ProductKitItemType($this->fieldsMapProvider);

        $translator = $this->createMock(TranslatorInterface::class);
        $bypassCallback = static fn ($value) => $value;
        $viewDataTransformer = static fn () => new CallbackTransformer($bypassCallback, $bypassCallback);
        $this->modelDataTransformer = $this->createMock(DataTransformerInterface::class);
        $this->productUnitLabelFormatter = $this->createMock(UnitLabelFormatterInterface::class);

        $this->kitItemProductsType = new ProductKitItemProductsType(
            $translator,
            $viewDataTransformer,
            fn () => $this->modelDataTransformer
        );

        $this->fieldsMap = [
            'sortOrder' => [
                'key' => 'sortOrder',
                'name' => 'oro_product_kit_item[sortOrder]',
                'id' => 'oro_product_kit_item_sortOrder',
            ],
            'optional' => [
                'key' => 'optional',
                'name' => 'oro_product_kit_item[optional]',
                'id' => 'oro_product_kit_item_optional',
            ],
            'minimumQuantity' => [
                'key' => 'minimumQuantity',
                'name' => 'oro_product_kit_item[minimumQuantity]',
                'id' => 'oro_product_kit_item_minimumQuantity',
            ],
            'maximumQuantity' => [
                'key' => 'maximumQuantity',
                'name' => 'oro_product_kit_item[maximumQuantity]',
                'id' => 'oro_product_kit_item_maximumQuantity',
            ],
            'productUnit' => [
                'key' => 'productUnit',
                'name' => 'oro_product_kit_item[productUnit]',
                'id' => 'oro_product_kit_item_productUnit',
            ],
            'kitItemProducts' => [
                'key' => 'kitItemProducts',
                'name' => 'oro_product_kit_item[kitItemProducts]',
                'id' => 'oro_product_kit_item_kitItemProducts',
            ],
        ];

        $this->fieldsMapProvider
            ->expects(self::any())
            ->method('getFormFieldsMap')
            ->willReturn($this->fieldsMap);

        parent::setUp();
    }

    protected function getExtensions(): array
    {
        return [
            new PreloadedExtension(
                [
                    $this->type,
                    ProductKitItemProductsType::class => $this->kitItemProductsType,
                    new ProductUnitSelectType($this->productUnitLabelFormatter),
                    EntityType::class => new EntityTypeStub([
                        'each' => (new ProductUnit())->setCode('each'),
                        'item' => (new ProductUnit())->setCode('item'),
                        'kg' => (new ProductUnit())->setCode('kg'),
                    ]),
                    LocalizedFallbackValueCollectionType::class => new LocalizedFallbackValueCollectionTypeStub(),
                    QuantityType::class => $this->getQuantityType(),
                ],
                [
                    FormType::class => [
                        new TooltipFormExtensionStub($this),
                    ],
                ]
            ),
        ];
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testBuildFormWithoutData(): void
    {
        $form = $this->factory->create(ProductKitItemType::class);

        $this->assertFormContainsField('labels', $form);
        $this->assertFormContainsField('sortOrder', $form);
        $this->assertFormContainsField('optional', $form);
        $this->assertFormContainsField('minimumQuantity', $form);
        $this->assertFormContainsField('maximumQuantity', $form);
        $this->assertFormContainsField('productUnit', $form);
        $this->assertFormContainsField('kitItemProducts', $form);

        $this->assertFormOptionEqual(ProductKitItem::class, 'data_class', $form);
        $this->assertFormOptionEqual(null, 'kit_item', $form->get('kitItemProducts'));

        $formView = $form->createView();
        self::assertEquals(
            [],
            $formView->vars['selectedProductsSkus'],
            'Variable "selectedProductsSkus" is expected to be empty'
        );
        self::assertEquals(
            [
                'labels' => [
                    'key' => 'labels',
                    'name' => 'oro_product_kit_item[labels]',
                    'id' => 'oro_product_kit_item_labels',
                ],
            ] + $this->fieldsMap,
            $formView->vars['fieldsMap'],
            'Variable "fieldMap" is not expected to be empty'
        );

        self::assertNull($form->getData());
    }

    public function testBuildFormWithData(): void
    {
        $product1 = (new ProductStub())->setSku('SKU1');
        $productKitItemProduct1 = (new ProductKitItemProduct())->setProduct($product1);
        $productUnit = (new ProductUnit())
            ->setCode('item');
        $productKitItem = (new ProductKitItem())
            ->addLabel((new ProductKitItemLabel())->setString('Sample Label'))
            ->setOptional(true)
            ->setSortOrder(11)
            ->setMinimumQuantity(1)
            ->setMinimumQuantity(10)
            ->setProductUnit($productUnit)
            ->addKitItemProduct($productKitItemProduct1);

        $form = $this->factory->create(ProductKitItemType::class, $productKitItem);

        $this->assertFormOptionEqual(ProductKitItem::class, 'data_class', $form);
        $this->assertEquals(
            $productKitItem,
            $form->get('kitItemProducts')->getConfig()->getOption('kit_item')
        );

        $formView = $form->createView();
        self::assertEquals(
            [$product1->getSku()],
            $formView->vars['selectedProductsSkus'],
            'Variable "selectedProductsSkus" is not as expected'
        );
        self::assertEquals(
            [
                'labels' => [
                    'key' => 'labels',
                    'name' => 'oro_product_kit_item[labels][0][string]',
                    'id' => 'oro_product_kit_item_labels_0_string',
                ],
            ] + $this->fieldsMap,
            $formView->vars['fieldsMap'],
            'Variable "fieldMap" is not expected to be empty'
        );
        self::assertSame($productKitItem, $form->getData());
    }

    public function testSubmitWhenEmptyInitialData(): void
    {
        $form = $this->factory->create(ProductKitItemType::class);

        self::assertNull($form->getData());

        $product1 = (new ProductStub())->setSku('SKU1');
        $productKitItemProduct1 = (new ProductKitItemProduct())->setProduct($product1);
        $productUnitItem = (new ProductUnit())
            ->setCode('item');

        $kitItemProductsRawData = 'raw data';
        $this->modelDataTransformer
            ->expects(self::once())
            ->method('reverseTransform')
            ->with($kitItemProductsRawData)
            ->willReturn(new ArrayCollection([$productKitItemProduct1]));

        $form->submit([
            'labels' => [['string' => 'Sample Label']],
            'optional' => true,
            'sortOrder' => 11,
            'minimumQuantity' => 1,
            'maximumQuantity' => 10,
            'productUnit' => $productUnitItem->getCode(),
            'kitItemProducts' => $kitItemProductsRawData,
        ]);

        $this->assertFormIsValid($form);

        $expected = (new ProductKitItem())
            ->addLabel((new ProductKitItemLabel())->setString('Sample Label'))
            ->setOptional(true)
            ->setSortOrder(11)
            ->setMinimumQuantity(1)
            ->setMaximumQuantity(10)
            ->setProductUnit($productUnitItem)
            ->addKitItemProduct($productKitItemProduct1);

        self::assertEquals($expected, $form->getData());
    }

    public function testSubmitWhenNotEmptyInitialData(): void
    {
        $product1 = (new ProductStub())->setSku('SKU1');
        $productKitItemProduct1 = (new ProductKitItemProduct())->setProduct($product1);
        $productUnitItem = (new ProductUnit())
            ->setCode('item');

        $initialProductKitItem = (new ProductKitItem())
            ->addLabel((new ProductKitItemLabel())->setString('Sample Label'))
            ->setOptional(true)
            ->setSortOrder(11)
            ->setMinimumQuantity(1)
            ->setMaximumQuantity(10)
            ->setProductUnit($productUnitItem)
            ->addKitItemProduct($productKitItemProduct1);

        $form = $this->factory->create(ProductKitItemType::class, $initialProductKitItem);

        self::assertSame($initialProductKitItem, $form->getData());

        $product2 = (new ProductStub())->setSku('SKU1');
        $productKitItemProduct2 = (new ProductKitItemProduct())->setProduct($product2);
        $productUnitEach = (new ProductUnit())
            ->setCode('each');

        $kitItemProductsRawData = 'raw data';

        $this->modelDataTransformer
            ->expects(self::once())
            ->method('reverseTransform')
            ->with($kitItemProductsRawData)
            ->willReturn(new ArrayCollection([$productKitItemProduct2]));

        $form->submit([
            'labels' => [['string' => 'Sample Label Updated']],
            'optional' => false,
            'sortOrder' => 22,
            'minimumQuantity' => 0,
            'maximumQuantity' => 5,
            'productUnit' => $productUnitEach->getCode(),
            'kitItemProducts' => $kitItemProductsRawData,
        ]);

        $this->assertFormIsValid($form);

        $expected = (new ProductKitItem())
            ->addLabel((new ProductKitItemLabel())->setString('Sample Label Updated'))
            ->setOptional(false)
            ->setSortOrder(22)
            ->setMinimumQuantity(0)
            ->setMaximumQuantity(5)
            ->setProductUnit($productUnitEach)
            ->addKitItemProduct($productKitItemProduct1)
            ->removeKitItemProduct($productKitItemProduct1)
            ->addKitItemProduct($productKitItemProduct2);

        self::assertEquals($expected, $form->getData());
    }
}
