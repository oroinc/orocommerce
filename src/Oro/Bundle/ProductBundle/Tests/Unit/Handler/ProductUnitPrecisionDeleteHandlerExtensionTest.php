<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Handler;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\EntityBundle\Handler\EntityDeleteAccessDeniedExceptionFactory;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Entity\ProductUnitPrecision;
use Oro\Bundle\ProductBundle\Handler\ProductUnitPrecisionDeleteHandlerExtension;
use Oro\Bundle\ProductBundle\Provider\ProductKitsByUnitPrecisionProvider;
use Oro\Bundle\ProductBundle\Tests\Unit\Stub\ProductStub;
use Oro\Bundle\ProductBundle\Tests\Unit\Stub\ProductUnitPrecisionStub;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Contracts\Translation\TranslatorInterface;

class ProductUnitPrecisionDeleteHandlerExtensionTest extends \PHPUnit\Framework\TestCase
{
    private ProductKitsByUnitPrecisionProvider|\PHPUnit\Framework\MockObject\MockObject
        $productKitsByUnitPrecisionProvider;

    private TranslatorInterface|\PHPUnit\Framework\MockObject\MockObject $translator;

    private ProductUnitPrecisionDeleteHandlerExtension $extension;

    protected function setUp(): void
    {
        $this->productKitsByUnitPrecisionProvider = $this->createMock(ProductKitsByUnitPrecisionProvider::class);
        $this->translator = $this->createMock(TranslatorInterface::class);

        $this->extension = new ProductUnitPrecisionDeleteHandlerExtension(
            $this->productKitsByUnitPrecisionProvider,
            $this->translator
        );
        $this->extension->setDoctrine($this->createMock(ManagerRegistry::class));
        $this->extension->setAccessDeniedExceptionFactory(new EntityDeleteAccessDeniedExceptionFactory());
    }

    public function testAssertDeleteGrantedWhenNotProductUnitPrecisionsEntity(): void
    {
        $this->translator
            ->expects(self::never())
            ->method('trans');

        $this->extension->assertDeleteGranted(null);
    }

    public function testAssertDeleteGrantedWhenNoProduct(): void
    {
        $unitPrecision = new ProductUnitPrecision();

        $this->productKitsByUnitPrecisionProvider
            ->expects(self::never())
            ->method(self::anything());

        $this->translator
            ->expects(self::never())
            ->method(self::anything());

        $this->extension->assertDeleteGranted($unitPrecision);
    }

    public function testAssertDeleteGrantedWhenPrimaryUnitPrecision(): void
    {
        $product = (new ProductStub());
        $unitPrecision = (new ProductUnitPrecisionStub(42))
            ->setProduct($product);
        $product->setPrimaryUnitPrecision($unitPrecision);

        $this->productKitsByUnitPrecisionProvider
            ->expects(self::once())
            ->method('getRelatedProductKitsSku')
            ->with($unitPrecision)
            ->willReturn([]);

        $this->translator->expects(self::never())
            ->method('trans');

        $this->extension->assertDeleteGranted($unitPrecision);
    }

    public function testAssertDeleteGrantedWhenNoReferencedKitItems(): void
    {
        $product = (new ProductStub());
        $unitPrecision = (new ProductUnitPrecisionStub(42))
            ->setProduct($product);
        $product->setPrimaryUnitPrecision(new ProductUnitPrecisionStub(4242));

        $this->productKitsByUnitPrecisionProvider
            ->expects(self::once())
            ->method('getRelatedProductKitsSku')
            ->with($unitPrecision)
            ->willReturn([]);

        $this->translator->expects(self::never())
            ->method('trans');

        $this->extension->assertDeleteGranted($unitPrecision);
    }

    public function testAssertDeleteGrantedWhenHasReferencedKitItems(): void
    {
        $this->expectExceptionObject(
            new AccessDeniedException('The delete operation is forbidden. Reason: translated exception message.')
        );

        $productUnit = (new ProductUnit())->setCode('item');
        $product = (new ProductStub());
        $unitPrecision = (new ProductUnitPrecisionStub(42))
            ->setProduct($product)
            ->setUnit($productUnit);
        $product->setPrimaryUnitPrecision(new ProductUnitPrecisionStub(4242));

        $this->productKitsByUnitPrecisionProvider
            ->expects(self::once())
            ->method('getRelatedProductKitsSku')
            ->with($unitPrecision)
            ->willReturn(['sku-2', 'sku-3']);

        $this->translator
            ->expects(self::once())
            ->method('trans')
            ->with(
                'oro.product.unit_precisions_items.referenced_by_product_kits',
                [
                    '{{ product_unit }}' => $productUnit->getCode(),
                    '{{ product_kits_skus }}' => 'sku-2, sku-3',
                ],
                'validators'
            )
            ->willReturn('translated exception message');

        $this->extension->assertDeleteGranted($unitPrecision);
    }
}
