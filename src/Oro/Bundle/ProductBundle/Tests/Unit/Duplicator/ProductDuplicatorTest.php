<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Duplicator;

use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\AttachmentBundle\Entity\Attachment;
use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\AttachmentBundle\Manager\FileManager;
use Oro\Bundle\AttachmentBundle\Provider\AttachmentProvider;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\ProductBundle\Duplicator\ProductDuplicator;
use Oro\Bundle\ProductBundle\Duplicator\SkuIncrementorInterface;
use Oro\Bundle\ProductBundle\Entity\ProductDescription;
use Oro\Bundle\ProductBundle\Entity\ProductKitItem;
use Oro\Bundle\ProductBundle\Entity\ProductKitItemLabel;
use Oro\Bundle\ProductBundle\Entity\ProductKitItemProduct;
use Oro\Bundle\ProductBundle\Entity\ProductName;
use Oro\Bundle\ProductBundle\Entity\ProductShortDescription;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Entity\ProductUnitPrecision;
use Oro\Bundle\ProductBundle\Event\ProductDuplicateAfterEvent;
use Oro\Bundle\ProductBundle\Tests\Unit\Entity\Stub\Product;
use Oro\Bundle\ProductBundle\Tests\Unit\Entity\Stub\ProductKitItemStub;
use Oro\Bundle\ProductBundle\Tests\Unit\Entity\Stub\StubProductImage;
use Oro\Bundle\RedirectBundle\Entity\Slug;
use Oro\Component\Testing\ReflectionUtil;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class ProductDuplicatorTest extends \PHPUnit\Framework\TestCase
{
    private const PRODUCT_SKU = 'SKU-1';
    private const PRODUCT_COPY_SKU = 'SKU-2';
    private const PRODUCT_STATUS = Product::STATUS_DISABLED;
    private const UNIT_PRECISION_CODE_1 = 'kg';
    private const UNIT_PRECISION_DEFAULT_PRECISION_1 = 2;
    private const UNIT_PRECISION_CODE_2 = 'mg';
    private const UNIT_PRECISION_DEFAULT_PRECISION_2 = 4;
    private const NAME_DEFAULT_LOCALE = 'name default';
    private const NAME_CUSTOM_LOCALE = 'name custom';
    private const DESCRIPTION_DEFAULT_LOCALE = 'description default';
    private const DESCRIPTION_CUSTOM_LOCALE = 'description custom';
    private const SHORT_DESCRIPTION_DEFAULT_LOCALE = 'short description default';
    private const SHORT_DESCRIPTION_CUSTOM_LOCALE = 'short description custom';
    private const KIT_ITEM_1_LABEL = 'Kit item 1 label';
    private const KIT_ITEM_2_LABEL = 'Kit item 2 label';

    private SkuIncrementorInterface|MockObject $skuIncrementor;

    private FileManager|MockObject $fileManager;

    private AttachmentProvider|MockObject $attachmentProvider;

    private Connection|MockObject $connection;

    private ProductDuplicator $duplicator;

    protected function setUp(): void
    {
        $eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $eventDispatcher->expects(self::once())
            ->method('dispatch')
            ->with(
                $this->isInstanceOf(ProductDuplicateAfterEvent::class),
                ProductDuplicateAfterEvent::NAME
            );

        $this->skuIncrementor = $this->createMock(SkuIncrementorInterface::class);
        $this->fileManager = $this->createMock(FileManager::class);
        $this->attachmentProvider = $this->createMock(AttachmentProvider::class);
        $this->connection = $this->createMock(Connection::class);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects(self::any())
            ->method('getConnection')
            ->willReturn($this->connection);

        $doctrineHelper = $this->createMock(DoctrineHelper::class);
        $doctrineHelper->expects(self::any())
            ->method('getEntityManager')
            ->with(self::anything())
            ->willReturn($em);

        $this->duplicator = new ProductDuplicator(
            $doctrineHelper,
            $eventDispatcher,
            $this->fileManager,
            $this->attachmentProvider,
            $this->skuIncrementor
        );
    }

    private function getFile(int $id): File
    {
        $file = new File();
        ReflectionUtil::setId($file, $id);

        return $file;
    }

    private function getProduct(int $id): Product
    {
        $product = new Product();
        ReflectionUtil::setId($product, $id);

        return $product;
    }

    private function getProductName(?string $string, ?string $text, Localization $localization = null): ProductName
    {
        $value = new ProductName();
        $value->setString($string);
        $value->setText($text);
        $value->setLocalization($localization);

        return $value;
    }

    private function getProductDescription(?string $string, ?string $text): ProductDescription
    {
        $value = new ProductDescription();
        $value->setString($string);
        $value->setText($text);

        return $value;
    }

    private function getProductShortDescription(?string $string, ?string $text): ProductShortDescription
    {
        $value = new ProductShortDescription();
        $value->setString($string);
        $value->setText($text);

        return $value;
    }

    private function getProductUnitPrecision(string $code, int $defaultPrecision): ProductUnitPrecision
    {
        $productUnit = new ProductUnit();
        $productUnit->setCode($code);
        $productUnit->setDefaultPrecision($defaultPrecision);

        $productUnitPrecision = new ProductUnitPrecision();
        $productUnitPrecision->setUnit($productUnit);

        return $productUnitPrecision;
    }

    private function getKitItem(int $id, string $label, Product $product): ProductKitItem
    {
        $productKitItemLabel = (new ProductKitItemLabel())->setString($label);
        $productKitItemProduct = (new ProductKitItemProduct())->setProduct($product);

        return (new ProductKitItemStub($id))
            ->addLabel($productKitItemLabel)
            ->addKitItemProduct($productKitItemProduct);
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testDuplicate(): void
    {
        $image1 = $this->getFile(1);
        $image2 = $this->getFile(2);
        $image1Copy = $this->getFile(3);

        $productImage1 = new StubProductImage();
        $productImage1->setId(11);
        $productImage1->setImage($image1);
        $productImage2 = new StubProductImage();
        $productImage2->setId(12);
        $productImage2->setImage($image2);
        $productImage1Copy = new StubProductImage();
        $productImage1Copy->setImage($image1Copy);

        $productSlug = new Slug();
        $productSlug->setUrl('/url');
        $productSlug->setRouteName('route_name');

        $attachmentFile1 = $this->getFile(4);
        $attachmentFile2 = $this->getFile(5);
        $attachmentFileCopy2 = $this->getFile(6);

        $attachment1 = (new Attachment())
            ->setFile($attachmentFile1);
        $attachment2 = (new Attachment())
            ->setFile($attachmentFile2);

        $product43 = $this->getProduct(43);

        $product = $this->getProduct(42);
        $product->setSku(self::PRODUCT_SKU)
            ->setPrimaryUnitPrecision($this->getProductUnitPrecision(
                self::UNIT_PRECISION_CODE_1,
                self::UNIT_PRECISION_DEFAULT_PRECISION_1
            ))
            ->addAdditionalUnitPrecision($this->getProductUnitPrecision(
                self::UNIT_PRECISION_CODE_2,
                self::UNIT_PRECISION_DEFAULT_PRECISION_2
            ))
            ->addSlug($productSlug)
            ->addName($this->getProductName(self::NAME_DEFAULT_LOCALE, null))
            ->addName($this->getProductName(self::NAME_CUSTOM_LOCALE, null, new Localization()))
            ->addDescription($this->getProductDescription(null, self::DESCRIPTION_DEFAULT_LOCALE))
            ->addDescription($this->getProductDescription(null, self::DESCRIPTION_CUSTOM_LOCALE))
            ->addShortDescription($this->getProductShortDescription(null, self::SHORT_DESCRIPTION_DEFAULT_LOCALE))
            ->addShortDescription($this->getProductShortDescription(null, self::SHORT_DESCRIPTION_CUSTOM_LOCALE))
            ->addImage($productImage1)
            ->addImage($productImage2)
            ->addKitItem($this->getKitItem(1, self::KIT_ITEM_1_LABEL, $product43))
            ->addKitItem($this->getKitItem(2, self::KIT_ITEM_2_LABEL, $product43));

        $this->skuIncrementor->expects(self::once())
            ->method('increment')
            ->with(self::PRODUCT_SKU)
            ->willReturn(self::PRODUCT_COPY_SKU);

        $this->attachmentProvider->expects(self::once())
            ->method('getEntityAttachments')
            ->with($product)
            ->willReturn([$attachment1, $attachment2]);

        $this->fileManager->expects(self::exactly(4))
            ->method('cloneFileEntity')
            ->withConsecutive(
                [$image1],
                [$image2],
                [$attachmentFile1],
                [$attachmentFile2]
            )
            ->willReturnOnConsecutiveCalls($image1Copy, $attachmentFileCopy2);

        $this->connection->expects(self::once())
            ->method('beginTransaction');
        $this->connection->expects(self::once())
            ->method('commit');

        $productCopy = $this->duplicator->duplicate($product);
        $productCopyUnitPrecisions = $productCopy->getUnitPrecisions();

        self::assertEmpty($productCopy->getSlugPrototypes());
        self::assertEmpty($productCopy->getSlugs());

        self::assertEquals(self::PRODUCT_COPY_SKU, $productCopy->getSku());
        self::assertEquals(self::PRODUCT_STATUS, $productCopy->getStatus());
        self::assertCount(2, $productCopyUnitPrecisions);
        self::assertEquals(self::UNIT_PRECISION_CODE_1, $productCopyUnitPrecisions[0]->getUnit()->getCode());
        self::assertEquals(
            self::UNIT_PRECISION_DEFAULT_PRECISION_1,
            $productCopyUnitPrecisions[0]->getUnit()->getDefaultPrecision()
        );
        self::assertEquals(self::UNIT_PRECISION_CODE_2, $productCopyUnitPrecisions[1]->getUnit()->getCode());
        self::assertEquals(
            self::UNIT_PRECISION_DEFAULT_PRECISION_2,
            $productCopyUnitPrecisions[1]->getUnit()->getDefaultPrecision()
        );

        $productCopyNames = $productCopy->getNames();
        self::assertEquals(self::NAME_DEFAULT_LOCALE, $productCopyNames[0]->getString());
        self::assertEquals(self::NAME_CUSTOM_LOCALE, $productCopyNames[1]->getString());

        $productCopyDescriptions = $productCopy->getDescriptions();
        self::assertEquals(self::DESCRIPTION_DEFAULT_LOCALE, $productCopyDescriptions[0]->getText());
        self::assertEquals(self::DESCRIPTION_CUSTOM_LOCALE, $productCopyDescriptions[1]->getText());

        $productCopyShortDescriptions = $productCopy->getShortDescriptions();
        self::assertEquals(self::SHORT_DESCRIPTION_DEFAULT_LOCALE, $productCopyShortDescriptions[0]->getText());
        self::assertEquals(self::SHORT_DESCRIPTION_CUSTOM_LOCALE, $productCopyShortDescriptions[1]->getText());

        self::assertEquals($image1Copy, $productImage1Copy->getImage());

        $productCopyKitItems = $productCopy->getKitItems();
        self::assertCount(2, $productCopyKitItems);

        $productCopyKitItem1 = $productCopyKitItems[0];
        $productCopyKitItem1Labels = $productCopyKitItem1->getLabels();
        self::assertCount(1, $productCopyKitItem1Labels);
        self::assertEquals(self::KIT_ITEM_1_LABEL, $productCopyKitItem1Labels[0]->getString());

        $productCopyKitItem1KitItemProducts = $productCopyKitItem1->getProducts();
        self::assertCount(1, $productCopyKitItem1KitItemProducts);
        self::assertEquals(43, $productCopyKitItem1KitItemProducts[0]->getId());

        $productCopyKitItem2 = $productCopyKitItems[1];
        $productCopyKitItem2Labels = $productCopyKitItem2->getLabels();
        self::assertCount(1, $productCopyKitItem2Labels);
        self::assertEquals(self::KIT_ITEM_2_LABEL, $productCopyKitItem2Labels[0]->getString());

        $productCopyKitItem2KitItemProducts = $productCopyKitItem2->getProducts();
        self::assertCount(1, $productCopyKitItem2KitItemProducts);
        self::assertEquals(43, $productCopyKitItem2KitItemProducts[0]->getId());
    }

    public function testDuplicateFailed(): void
    {
        $this->expectException(\Exception::class);
        $product = (new Product())
            ->setSku(self::PRODUCT_SKU)
            ->setPrimaryUnitPrecision($this->getProductUnitPrecision(
                self::UNIT_PRECISION_CODE_1,
                self::UNIT_PRECISION_DEFAULT_PRECISION_1
            ));

        $this->skuIncrementor->expects(self::once())
            ->method('increment')
            ->with(self::PRODUCT_SKU)
            ->willReturn(self::PRODUCT_COPY_SKU);

        $this->attachmentProvider->expects(self::once())
            ->method('getEntityAttachments')
            ->with($product)
            ->willReturn([]);

        $this->connection->expects(self::once())
            ->method('beginTransaction');
        $this->connection->expects(self::once())
            ->method('commit')
            ->willThrowException(new \Exception());
        $this->connection->expects(self::once())
            ->method('rollback');

        $this->duplicator->duplicate($product);
    }
}
