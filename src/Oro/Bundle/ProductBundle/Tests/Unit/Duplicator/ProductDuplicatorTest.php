<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Duplicator;

use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManager;
use Oro\Bundle\AttachmentBundle\Entity\Attachment;
use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\AttachmentBundle\Manager\FileManager;
use Oro\Bundle\AttachmentBundle\Provider\AttachmentProvider;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\ProductBundle\Duplicator\ProductDuplicator;
use Oro\Bundle\ProductBundle\Duplicator\SkuIncrementorInterface;
use Oro\Bundle\ProductBundle\Entity\ProductDescription;
use Oro\Bundle\ProductBundle\Entity\ProductName;
use Oro\Bundle\ProductBundle\Entity\ProductShortDescription;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Entity\ProductUnitPrecision;
use Oro\Bundle\ProductBundle\Tests\Unit\Entity\Stub\Product;
use Oro\Bundle\ProductBundle\Tests\Unit\Entity\Stub\StubProductImage;
use Oro\Bundle\RedirectBundle\Entity\Slug;
use Oro\Component\Testing\Unit\EntityTrait;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class ProductDuplicatorTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    const PRODUCT_SKU = 'SKU-1';
    const PRODUCT_COPY_SKU = 'SKU-2';
    const PRODUCT_STATUS = Product::STATUS_DISABLED;
    const UNIT_PRECISION_CODE_1 = 'kg';
    const UNIT_PRECISION_DEFAULT_PRECISION_1 = 2;
    const UNIT_PRECISION_CODE_2 = 'mg';
    const UNIT_PRECISION_DEFAULT_PRECISION_2 = 4;
    const NAME_DEFAULT_LOCALE = 'name default';
    const NAME_CUSTOM_LOCALE = 'name custom';
    const DESCRIPTION_DEFAULT_LOCALE = 'description default';
    const DESCRIPTION_CUSTOM_LOCALE = 'description custom';
    const SHORT_DESCRIPTION_DEFAULT_LOCALE = 'short description default';
    const SHORT_DESCRIPTION_CUSTOM_LOCALE = 'short description custom';

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|EntityManager
     */
    protected $objectManager;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|DoctrineHelper
     */
    protected $doctrineHelper;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|EventDispatcherInterface
     */
    protected $eventDispatcher;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|SkuIncrementorInterface
     */
    protected $skuIncrementor;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|FileManager
     */
    protected $fileManager;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|AttachmentProvider
     */
    protected $attachmentProvider;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|Connection
     */
    protected $connection;

    /**
     * @var ProductDuplicator
     */
    protected $duplicator;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->objectManager = $this->createMock(EntityManager::class);
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->skuIncrementor = $this->createMock(SkuIncrementorInterface::class);
        $this->fileManager = $this->createMock(FileManager::class);
        $this->attachmentProvider = $this->createMock(AttachmentProvider::class);
        $this->connection = $this->createMock(Connection::class);

        $this->doctrineHelper->expects($this->any())
            ->method('getEntityManager')
            ->with($this->anything())
            ->willReturn($this->objectManager);

        $this->objectManager->expects($this->any())
            ->method('getConnection')
            ->willReturn($this->connection);

        $this->duplicator = new ProductDuplicator(
            $this->doctrineHelper,
            $this->eventDispatcher,
            $this->fileManager,
            $this->attachmentProvider
        );

        $this->duplicator->setSkuIncrementor($this->skuIncrementor);
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testDuplicate()
    {
        /** @var File $image1 */
        $image1 = $this->getEntity(File::class, ['id' => 1]);
        /** @var File $image2 */
        $image2 = $this->getEntity(File::class, ['id' => 2]);
        /** @var File $image1Copy */
        $image1Copy = $this->getEntity(File::class, ['id' => 3]);

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

        /** @var File $attachmentFile1 */
        $attachmentFile1 = $this->getEntity(File::class, ['id' => 4]);
        /** @var File $attachmentFile2 */
        $attachmentFile2 = $this->getEntity(File::class, ['id' => 5]);
        /** @var File $attachmentFileCopy2 */
        $attachmentFileCopy2 = $this->getEntity(File::class, ['id' => 6]);

        $attachment1 = (new Attachment())
            ->setFile($attachmentFile1);
        $attachment2 = (new Attachment())
            ->setFile($attachmentFile2);

        /** @var Product $product */
        $product = $this->getEntity(Product::class, ['id' => 42]);
        $product->setSku(self::PRODUCT_SKU)
            ->setPrimaryUnitPrecision($this->prepareUnitPrecision(
                self::UNIT_PRECISION_CODE_1,
                self::UNIT_PRECISION_DEFAULT_PRECISION_1
            ))
            ->addAdditionalUnitPrecision($this->prepareUnitPrecision(
                self::UNIT_PRECISION_CODE_2,
                self::UNIT_PRECISION_DEFAULT_PRECISION_2
            ))
            ->addSlug($productSlug)
            ->addName($this->prepareLocalizedValue(self::NAME_DEFAULT_LOCALE, null, ProductName::class))
            ->addName(
                $this->prepareLocalizedValue(self::NAME_CUSTOM_LOCALE, null, ProductName::class)
                    ->setLocalization((new Localization()))
            )
            ->addDescription(
                $this->prepareLocalizedValue(null, self::DESCRIPTION_DEFAULT_LOCALE, ProductDescription::class)
            )
            ->addDescription(
                $this->prepareLocalizedValue(null, self::DESCRIPTION_CUSTOM_LOCALE, ProductDescription::class)
            )
            ->addShortDescription(
                $this->prepareLocalizedValue(
                    null,
                    self::SHORT_DESCRIPTION_DEFAULT_LOCALE,
                    ProductShortDescription::class
                )
            )
            ->addShortDescription(
                $this->prepareLocalizedValue(
                    null,
                    self::SHORT_DESCRIPTION_CUSTOM_LOCALE,
                    ProductShortDescription::class
                )
            )
            ->addImage($productImage1)
            ->addImage($productImage2);

        $this->skuIncrementor->expects($this->once())
            ->method('increment')
            ->with(self::PRODUCT_SKU)
            ->willReturn(self::PRODUCT_COPY_SKU);

        $this->attachmentProvider->expects($this->once())
            ->method('getEntityAttachments')
            ->with($product)
            ->willReturn([$attachment1, $attachment2]);

        $this->fileManager->expects($this->exactly(4))
            ->method('cloneFileEntity')
            ->withConsecutive(
                [$image1],
                [$image2],
                [$attachmentFile1],
                [$attachmentFile2]
            )
            ->willReturnOnConsecutiveCalls($image1Copy, $attachmentFileCopy2);

        $this->connection->expects($this->once())
            ->method('beginTransaction');
        $this->connection->expects($this->once())
            ->method('commit');

        $productCopy = $this->duplicator->duplicate($product);
        $productCopyUnitPrecisions = $productCopy->getUnitPrecisions();

        $this->assertEmpty($productCopy->getSlugPrototypes());
        $this->assertEmpty($productCopy->getSlugs());

        $this->assertEquals(self::PRODUCT_COPY_SKU, $productCopy->getSku());
        $this->assertEquals(self::PRODUCT_STATUS, $productCopy->getStatus());
        $this->assertCount(2, $productCopyUnitPrecisions);
        $this->assertEquals(self::UNIT_PRECISION_CODE_1, $productCopyUnitPrecisions[0]->getUnit()->getCode());
        $this->assertEquals(
            self::UNIT_PRECISION_DEFAULT_PRECISION_1,
            $productCopyUnitPrecisions[0]->getUnit()->getDefaultPrecision()
        );
        $this->assertEquals(self::UNIT_PRECISION_CODE_2, $productCopyUnitPrecisions[1]->getUnit()->getCode());
        $this->assertEquals(
            self::UNIT_PRECISION_DEFAULT_PRECISION_2,
            $productCopyUnitPrecisions[1]->getUnit()->getDefaultPrecision()
        );

        $productCopyNames = $productCopy->getNames();
        $this->assertEquals(self::NAME_DEFAULT_LOCALE, $productCopyNames[0]->getString());
        $this->assertEquals(self::NAME_CUSTOM_LOCALE, $productCopyNames[1]->getString());

        $productCopyDescriptions = $productCopy->getDescriptions();
        $this->assertEquals(self::DESCRIPTION_DEFAULT_LOCALE, $productCopyDescriptions[0]->getText());
        $this->assertEquals(self::DESCRIPTION_CUSTOM_LOCALE, $productCopyDescriptions[1]->getText());

        $productCopyShortDescriptions = $productCopy->getShortDescriptions();
        $this->assertEquals(self::SHORT_DESCRIPTION_DEFAULT_LOCALE, $productCopyShortDescriptions[0]->getText());
        $this->assertEquals(self::SHORT_DESCRIPTION_CUSTOM_LOCALE, $productCopyShortDescriptions[1]->getText());

        $this->assertEquals($image1Copy, $productImage1Copy->getImage());
    }

    public function testDuplicateFailed()
    {
        $this->expectException(\Exception::class);
        $product = (new Product())
            ->setSku(self::PRODUCT_SKU)
            ->setPrimaryUnitPrecision($this->prepareUnitPrecision(
                self::UNIT_PRECISION_CODE_1,
                self::UNIT_PRECISION_DEFAULT_PRECISION_1
            ));

        $this->skuIncrementor->expects($this->once())
            ->method('increment')
            ->with(self::PRODUCT_SKU)
            ->willReturn(self::PRODUCT_COPY_SKU);

        $this->attachmentProvider->expects($this->once())
            ->method('getEntityAttachments')
            ->with($product)
            ->willReturn([]);

        $this->connection->expects($this->once())
            ->method('beginTransaction');
        $this->connection->expects($this->once())
            ->method('commit')
            ->will($this->throwException(new \Exception()));
        $this->connection->expects($this->once())
            ->method('rollback');

        $this->duplicator->duplicate($product);
    }

    /**
     * @param string $code
     * @param int $defaultPrecision
     * @return ProductUnitPrecision
     */
    protected function prepareUnitPrecision($code, $defaultPrecision)
    {
        $productUnit = (new ProductUnit())
            ->setCode($code)
            ->setDefaultPrecision($defaultPrecision);

        return (new ProductUnitPrecision())
            ->setUnit($productUnit);
    }

    /**
     * @param string|null $string
     * @param string|null $text
     * @param string $className
     * @return object
     */
    protected function prepareLocalizedValue(?string $string = null, ?string $text = null, string $className)
    {
        $value = new $className();
        $value->setString($string)
            ->setText($text);

        return $value;
    }
}
