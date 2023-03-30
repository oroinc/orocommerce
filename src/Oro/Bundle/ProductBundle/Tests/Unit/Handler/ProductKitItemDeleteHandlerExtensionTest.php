<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Handler;

use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManager;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\EntityBundle\Handler\EntityDeleteAccessDeniedExceptionFactory;
use Oro\Bundle\LocaleBundle\Helper\LocalizationHelper;
use Oro\Bundle\ProductBundle\Entity\ProductKitItem;
use Oro\Bundle\ProductBundle\Entity\ProductKitItemLabel;
use Oro\Bundle\ProductBundle\Entity\Repository\ProductKitItemRepository;
use Oro\Bundle\ProductBundle\Handler\ProductKitItemDeleteHandlerExtension;
use Oro\Bundle\ProductBundle\Tests\Unit\Entity\Stub\ProductKitItemStub;
use Oro\Bundle\ProductBundle\Tests\Unit\Stub\ProductStub;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Contracts\Translation\TranslatorInterface;

class ProductKitItemDeleteHandlerExtensionTest extends \PHPUnit\Framework\TestCase
{
    private TranslatorInterface|\PHPUnit\Framework\MockObject\MockObject $translator;

    private ProductKitItemDeleteHandlerExtension $extension;

    private ProductKitItemRepository|\PHPUnit\Framework\MockObject\MockObject $productKitItemRepository;

    protected function setUp(): void
    {
        $localizationHelper = $this->createMock(LocalizationHelper::class);
        $localizationHelper
            ->expects(self::any())
            ->method('getLocalizedValue')
            ->willReturnCallback(static fn (Collection $values) => (string)$values[0]);

        $this->translator = $this->createMock(TranslatorInterface::class);

        $this->extension = new ProductKitItemDeleteHandlerExtension($localizationHelper, $this->translator);

        $managerRegistry = $this->createMock(ManagerRegistry::class);
        $entityManager = $this->createMock(EntityManager::class);
        $managerRegistry
            ->expects(self::any())
            ->method('getManagerForClass')
            ->with(ProductKitItem::class)
            ->willReturn($entityManager);
        $this->productKitItemRepository = $this->createMock(ProductKitItemRepository::class);
        $entityManager
            ->expects(self::any())
            ->method('getRepository')
            ->with(ProductKitItem::class)
            ->willReturn($this->productKitItemRepository);

        $this->extension->setDoctrine($managerRegistry);
        $this->extension->setAccessDeniedExceptionFactory(new EntityDeleteAccessDeniedExceptionFactory());
    }

    public function testAssertDeleteGrantedWhenNotProductKitItemEntity(): void
    {
        $this->translator
            ->expects(self::never())
            ->method('trans');

        $this->extension->assertDeleteGranted(null);
    }

    public function testAssertDeleteGrantedWhenNotLastKitItem(): void
    {
        $productKit = (new ProductStub())->setId(4242);
        $productKitItem = (new ProductKitItemStub())
            ->setId(42)
            ->setProductKit($productKit);

        $this->productKitItemRepository
            ->expects(self::once())
            ->method('getKitItemsCount')
            ->with($productKit->getId())
            ->willReturn(2);

        $this->translator->expects(self::never())
            ->method('trans');

        $this->extension->assertDeleteGranted($productKitItem);
    }

    public function testAssertDeleteGrantedWhenHasReferencedKitItems(): void
    {
        $this->expectExceptionObject(
            new AccessDeniedException('The delete operation is forbidden. Reason: translated exception message.')
        );

        $productKit = (new ProductStub())
            ->setId(4242)
            ->setSku('SKU1');
        $productKitItemLabel = (new ProductKitItemLabel())->setString('Kit Item Label 1');
        $productKitItem = (new ProductKitItemStub())
            ->setId(42)
            ->addLabel($productKitItemLabel)
            ->setProductKit($productKit);

        $this->productKitItemRepository
            ->expects(self::once())
            ->method('getKitItemsCount')
            ->with($productKit->getId())
            ->willReturn(1);

        $this->translator
            ->expects(self::once())
            ->method('trans')
            ->with(
                'oro.product.kit_items.last_one',
                [
                    '{{ kit_item_label }}' => $productKitItemLabel->getString(),
                    '{{ product_kit_sku }}' => $productKit->getSku(),
                ],
                'validators'
            )
            ->willReturn('translated exception message');

        $this->extension->assertDeleteGranted($productKitItem);
    }
}
