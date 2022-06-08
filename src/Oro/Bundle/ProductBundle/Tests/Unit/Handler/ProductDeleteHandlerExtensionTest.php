<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Handler;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\EntityBundle\Handler\EntityDeleteAccessDeniedExceptionFactory;
use Oro\Bundle\ProductBundle\Handler\ProductDeleteHandlerExtension;
use Oro\Bundle\ProductBundle\Provider\ProductKitsByProductProvider;
use Oro\Bundle\ProductBundle\Tests\Unit\Stub\ProductStub;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Contracts\Translation\TranslatorInterface;

class ProductDeleteHandlerExtensionTest extends \PHPUnit\Framework\TestCase
{
    private ProductKitsByProductProvider|\PHPUnit\Framework\MockObject\MockObject $productKitsByProductProvider;

    private TranslatorInterface|\PHPUnit\Framework\MockObject\MockObject $translator;

    private ProductDeleteHandlerExtension $extension;

    protected function setUp(): void
    {
        $this->productKitsByProductProvider = $this->createMock(ProductKitsByProductProvider::class);
        $this->translator = $this->createMock(TranslatorInterface::class);

        $this->extension = new ProductDeleteHandlerExtension($this->productKitsByProductProvider, $this->translator);
        $this->extension->setDoctrine($this->createMock(ManagerRegistry::class));
        $this->extension->setAccessDeniedExceptionFactory(new EntityDeleteAccessDeniedExceptionFactory());
    }

    public function testAssertDeleteGrantedWhenNotProductEntity(): void
    {
        $this->translator
            ->expects(self::never())
            ->method('trans');

        $this->extension->assertDeleteGranted(null);
    }

    public function testAssertDeleteGrantedWhenNoReferencedKitItems(): void
    {
        $product = new ProductStub();

        $this->productKitsByProductProvider
            ->expects(self::once())
            ->method('getRelatedProductKitsSku')
            ->with($product)
            ->willReturn([]);

        $this->translator->expects(self::never())
            ->method('trans');

        $this->extension->assertDeleteGranted($product);
    }

    public function testAssertDeleteGrantedWhenHasReferencedKitItems(): void
    {
        $this->expectExceptionObject(
            new AccessDeniedException('The delete operation is forbidden. Reason: translated exception message.')
        );

        $product = (new ProductStub())
            ->setSku('sku-1');

        $this->productKitsByProductProvider
            ->expects(self::once())
            ->method('getRelatedProductKitsSku')
            ->with($product)
            ->willReturn(['sku-2', 'sku-3']);

        $this->translator
            ->expects(self::once())
            ->method('trans')
            ->with(
                'oro.product.referenced_by_product_kits',
                [
                    '{{ product_sku }}' => 'sku-1',
                    '{{ product_kits_skus }}' => 'sku-2, sku-3',
                ],
                'validators'
            )
            ->willReturn('translated exception message');

        $this->extension->assertDeleteGranted($product);
    }
}
