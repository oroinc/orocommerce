<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Form\Type;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\ProductBundle\Entity\ProductKitItemProduct;
use Oro\Bundle\ProductBundle\Form\Type\ProductKitItemProductsType;
use Oro\Bundle\ProductBundle\Tests\Unit\Entity\Stub\ProductKitItemStub;
use Oro\Bundle\ProductBundle\Tests\Unit\Stub\ProductStub;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;
use Oro\Component\Testing\Unit\PreloadedExtension;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class ProductKitItemProductsTypeTest extends FormIntegrationTestCase
{
    use QuantityTypeTrait;

    private ProductKitItemProductsType $type;

    private DataTransformerInterface|MockObject $modelDataTransformer;

    protected function setUp(): void
    {
        $translator = $this->createMock(TranslatorInterface::class);
        $bypassCallback = static fn ($value) => $value;
        $viewDataTransformer = static fn () => new CallbackTransformer($bypassCallback, $bypassCallback);
        $this->modelDataTransformer = $this->createMock(DataTransformerInterface::class);

        $this->type = new ProductKitItemProductsType(
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
                ],
                []
            )
        ];
    }

    public function testBuildFormWithoutData(): void
    {
        $form = $this->factory->create(ProductKitItemProductsType::class);

        $this->assertFormOptionEqual(null, 'kit_item', $form);
        $this->assertFormOptionEqual(false, 'error_bubbling', $form);
        $this->assertFormOptionEqual(
            'oro.product.productkititem.kititemproducts.invalid_message',
            'invalid_message',
            $form
        );

        $formView = $form->createView();
        self::assertEquals(
            0,
            $formView->vars['kitItemId'],
            'Variable "kitItemId" is expected to be 0'
        );
        self::assertEquals(
            [],
            $formView->vars['selectedProductsIds'],
            'Variable "selectedProductsIds" is expected to be empty'
        );

        self::assertNull($form->getData());
    }

    public function testBuildFormWithData(): void
    {
        $product1 = (new ProductStub())->setId(10)->setSku('SKU1');
        $productKitItemProduct1 = (new ProductKitItemProduct())->setProduct($product1);
        $productKitItem = new ProductKitItemStub(42);

        $form = $this->factory->create(
            ProductKitItemProductsType::class,
            new ArrayCollection([$productKitItemProduct1]),
            ['kit_item' => $productKitItem]
        );

        $formView = $form->createView();
        self::assertEquals(
            $productKitItem->getId(),
            $formView->vars['kitItemId'],
            'Variable "kitItemId" is not as expected'
        );
        self::assertEquals(
            [$product1->getId()],
            $formView->vars['selectedProductsIds'],
            'Variable "selectedProductsIds" is not as expected'
        );

        self::assertEquals(new ArrayCollection([$productKitItemProduct1]), $form->getData());
    }

    public function testSubmitWhenEmptyInitialData(): void
    {
        $form = $this->factory->create(ProductKitItemProductsType::class);

        self::assertNull($form->getData());

        $product1 = (new ProductStub())->setSku('SKU1');
        $productKitItemProduct1 = (new ProductKitItemProduct())->setProduct($product1);

        $kitItemProductsRawData = 'raw data';
        $this->modelDataTransformer
            ->expects(self::once())
            ->method('reverseTransform')
            ->with($kitItemProductsRawData)
            ->willReturn(new ArrayCollection([$productKitItemProduct1]));

        $form->submit($kitItemProductsRawData);

        $this->assertFormIsValid($form);

        self::assertEquals(new ArrayCollection([$productKitItemProduct1]), $form->getData());
    }

    public function testSubmitWhenNotEmptyInitialData(): void
    {
        $product1 = (new ProductStub())->setSku('SKU1');
        $productKitItemProduct1 = (new ProductKitItemProduct())->setProduct($product1);

        $form = $this->factory->create(
            ProductKitItemProductsType::class,
            new ArrayCollection([$productKitItemProduct1])
        );

        self::assertEquals(new ArrayCollection([$productKitItemProduct1]), $form->getData());

        $product2 = (new ProductStub())->setSku('SKU1');
        $productKitItemProduct2 = (new ProductKitItemProduct())->setProduct($product2);

        $kitItemProductsRawData = 'raw data';
        $this->modelDataTransformer
            ->expects(self::once())
            ->method('reverseTransform')
            ->with($kitItemProductsRawData)
            ->willReturn(new ArrayCollection([$productKitItemProduct1, $productKitItemProduct2]));

        $form->submit($kitItemProductsRawData);

        $this->assertFormIsValid($form);

        self::assertEquals(new ArrayCollection([$productKitItemProduct1, $productKitItemProduct2]), $form->getData());
    }
}
