<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Form\Type;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\FormBundle\Provider\FormFieldsMapProvider;
use Oro\Bundle\FormBundle\Tests\Unit\Stub\TooltipFormExtensionStub;
use Oro\Bundle\LocaleBundle\Form\Type\LocalizedFallbackValueCollectionType;
use Oro\Bundle\LocaleBundle\Tests\Unit\Form\Type\Stub\LocalizedFallbackValueCollectionTypeStub;
use Oro\Bundle\ProductBundle\Entity\ProductKitItem;
use Oro\Bundle\ProductBundle\Entity\ProductKitItemLabel;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Form\Type\ProductKitItemCollectionType;
use Oro\Bundle\ProductBundle\Form\Type\ProductKitItemProductsType;
use Oro\Bundle\ProductBundle\Form\Type\ProductKitItemType;
use Oro\Bundle\ProductBundle\Form\Type\ProductUnitSelectType;
use Oro\Bundle\ProductBundle\Form\Type\QuantityType;
use Oro\Bundle\ProductBundle\Formatter\UnitLabelFormatterInterface;
use Oro\Component\Testing\Unit\Form\Type\Stub\EntityTypeStub;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;
use Oro\Component\Testing\Unit\PreloadedExtension;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Contracts\Translation\TranslatorInterface;

class ProductKitItemCollectionTypeTest extends FormIntegrationTestCase
{
    use QuantityTypeTrait;

    private ProductKitItemCollectionType $type;

    private DataTransformerInterface|MockObject $modelDataTransformer;

    private ProductKitItemProductsType $kitItemProductsType;

    private UnitLabelFormatterInterface|MockObject $productUnitLabelFormatter;

    private FormFieldsMapProvider|MockObject $fieldsMapProvider;

    protected function setUp(): void
    {
        $this->fieldsMapProvider = $this->createMock(FormFieldsMapProvider::class);
        $this->type = new ProductKitItemCollectionType();
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

        parent::setUp();
    }

    protected function getExtensions(): array
    {
        return [
            new PreloadedExtension(
                [
                    $this->type,
                    new ProductKitItemType($this->fieldsMapProvider),
                    ProductKitItemProductsType::class => $this->kitItemProductsType,
                    new ProductUnitSelectType($this->productUnitLabelFormatter),
                    EntityType::class => new EntityTypeStub([
                        'each' => (new ProductUnit())->setCode('each'),
                        'item' => (new ProductUnit())->setCode('item'),
                        'kg' => (new ProductUnit())->setCode('kg')
                    ]),
                    LocalizedFallbackValueCollectionType::class => new LocalizedFallbackValueCollectionTypeStub(),
                    QuantityType::class => $this->getQuantityType(),
                ],
                [
                    FormType::class => [
                        new TooltipFormExtensionStub($this),
                    ]
                ]
            )
        ];
    }

    public function testBuildFormWithoutData(): void
    {
        $form = $this->factory->create(ProductKitItemCollectionType::class);

        self::assertCount(0, $form);
        self::assertNull($form->getData());
    }

    public function testBuildFormWithData(): void
    {
        $productKitItem = new ProductKitItem();

        $form = $this->factory->create(ProductKitItemCollectionType::class, new ArrayCollection([$productKitItem]));

        self::assertEquals($productKitItem, $form->get(0)->getData());
    }

    public function testSubmitWhenEmptyInitialData(): void
    {
        $form = $this->factory->create(ProductKitItemCollectionType::class);

        self::assertNull($form->getData());

        $this->modelDataTransformer
            ->expects(self::once())
            ->method('reverseTransform')
            ->willReturn(new ArrayCollection([]));

        $form->submit([
            [
                'labels' => [['string' => 'Sample Label']],
            ]
        ]);

        $this->assertFormIsValid($form);

        $expected = (new ProductKitItem())
            ->addLabel((new ProductKitItemLabel())->setString('Sample Label'));

        self::assertEquals($expected, $form->get(0)->getData());
    }

    public function testSubmitWhenNotEmptyInitialData(): void
    {
        $initialProductKitItem = new ProductKitItem();
        $form = $this->factory->create(
            ProductKitItemCollectionType::class,
            new ArrayCollection([$initialProductKitItem])
        );

        self::assertSame($initialProductKitItem, $form->get(0)->getData());

        $this->modelDataTransformer
            ->expects(self::any())
            ->method('reverseTransform')
            ->willReturn(new ArrayCollection());

        $form->submit([
            [
                'labels' => [['string' => 'Sample Label Updated']],
            ],
        ]);

        $this->assertFormIsValid($form);

        $expected1 = new ProductKitItem();
        $expected1
            ->getLabels()
            ->add((new ProductKitItemLabel())->setString('Sample Label Updated'));
        self::assertEquals($expected1, $form->get(0)->getData());
    }
}
