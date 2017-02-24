<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\EventListener;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\UnitOfWork;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;

use Oro\Bundle\ProductBundle\Tests\Unit\Entity\Stub\ContentNodeStub;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Component\DoctrineUtils\ORM\FieldUpdatesChecker;
use Oro\Bundle\CatalogBundle\ContentVariantType\CategoryPageContentVariantType;
use Oro\Bundle\WebCatalogBundle\Entity\ContentNode;
use Oro\Bundle\WebsiteSearchBundle\Event\ReindexationRequestEvent;
use Oro\Bundle\ProductBundle\ContentVariantType\ProductPageContentVariantType;
use Oro\Bundle\ProductBundle\EventListener\ProductContentVariantReindexEventListener;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Tests\Unit\ContentVariant\Stub\ContentVariantStub;
use Oro\Component\WebCatalog\Entity\WebCatalogInterface;
use Oro\Component\WebCatalog\Provider\WebCatalogUsageProviderInterface;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class ProductContentVariantReindexEventListenerTest extends \PHPUnit_Framework_TestCase
{
    /** @var ProductContentVariantReindexEventListener */
    private $eventListener;

    /** @var WebCatalogUsageProviderInterface|\PHPUnit_Framework_MockObject_MockObject */
    private $webCatalogUsageProvider;

    /** @var FieldUpdatesChecker|\PHPUnit_Framework_MockObject_MockObject */
    private $fieldUpdatesChecker;

    /** @var EventDispatcherInterface|\PHPUnit_Framework_MockObject_MockObject */
    private $eventDispatcher;

    public function setUp()
    {
        $this->eventDispatcher = $this->getMockBuilder(EventDispatcherInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->fieldUpdatesChecker = $this->getMockBuilder(FieldUpdatesChecker::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->webCatalogUsageProvider = $this->getMockBuilder(WebCatalogUsageProviderInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->eventListener = new ProductContentVariantReindexEventListener(
            $this->eventDispatcher,
            $this->fieldUpdatesChecker,
            $this->webCatalogUsageProvider
        );
    }

    public function testItAcceptsOnlyContentVariantAfterFlush()
    {
        $entityManager = $this->getEntityManager([new \stdClass()], [], []);
        $this->eventDispatcher
            ->expects($this->never())
            ->method('dispatch');

        $this->webCatalogUsageProvider
            ->method('getAssignedWebCatalogs')
            ->willReturn([]);

        $this->eventListener->onFlush(new OnFlushEventArgs($entityManager));
    }

    public function testItDoesNotReindexWhenNoProductsAfterFlush()
    {
        $this->eventDispatcher
            ->expects($this->never())
            ->method('dispatch');

        $contentVariant = $this->generateContentVariant(ProductPageContentVariantType::TYPE);
        $entityManager = $this->getEntityManager([$contentVariant], [], []);

        $this->webCatalogUsageProvider
            ->method('getAssignedWebCatalogs')
            ->willReturn([]);

        $this->eventListener->onFlush(new OnFlushEventArgs($entityManager));
    }

    public function testItReindexWithManyProductsAfterFlush()
    {
        $contentVariant1 = $this->generateContentVariant(ProductPageContentVariantType::TYPE, 1);
        $contentVariant2 = $this->generateContentVariant(ProductPageContentVariantType::TYPE, 2);
        $contentVariant3 = $this->generateContentVariant(ProductPageContentVariantType::TYPE, 3);
        $contentVariant4 = $this->generateContentVariant(ProductPageContentVariantType::TYPE, 4);
        $contentVariant5 = $this->generateContentVariant(ProductPageContentVariantType::TYPE, 5);
        $contentVariant6 = $this->generateContentVariant(ProductPageContentVariantType::TYPE, 6);

        $entityManager = $this->getEntityManager(
            [$contentVariant1, $contentVariant2],
            [$contentVariant3, $contentVariant4],
            [$contentVariant5, $contentVariant6]
        );

        $this->webCatalogUsageProvider
            ->method('getAssignedWebCatalogs')
            ->willReturn([]);

        $this->assertReindexEvent([1, 2, 3, 4, 5, 6]);
        $this->eventListener->onFlush(new OnFlushEventArgs($entityManager));
    }

    public function testItReindexEachProductOnlyOnceAfterFlush()
    {
        $contentVariant1 = $this->generateContentVariant(ProductPageContentVariantType::TYPE, 1);
        $contentVariant2 = $this->generateContentVariant(ProductPageContentVariantType::TYPE, 2);
        $contentVariant3 = $this->generateContentVariant(ProductPageContentVariantType::TYPE, 3);

        $entityManager = $this->getEntityManager(
            [$contentVariant1, $contentVariant2, $contentVariant3],
            [$contentVariant1, $contentVariant2, $contentVariant3],
            [$contentVariant1, $contentVariant2, $contentVariant3]
        );

        $this->webCatalogUsageProvider
            ->method('getAssignedWebCatalogs')
            ->willReturn([]);

        $this->assertReindexEvent([1, 2, 3]);
        $this->eventListener->onFlush(new OnFlushEventArgs($entityManager));
    }

    public function testItReindexWithManyProductsAfterFlushWithChangeSet()
    {
        $contentVariant1 = $this->generateContentVariant(ProductPageContentVariantType::TYPE, 1);
        $contentVariant2 = $this->generateContentVariant(ProductPageContentVariantType::TYPE, 2);
        $oldProduct = $contentVariant1->getProductPageProduct();
        $newProduct = $this->generateProduct(3);
        $contentVariant1->setProductPageProduct($newProduct);

        $entityChangeSet = ['product_page_product' => [$oldProduct, $newProduct]];
        $entityManager = $this->getEntityManager([$contentVariant1, $contentVariant2], [], [], $entityChangeSet);

        $this->webCatalogUsageProvider
            ->method('getAssignedWebCatalogs')
            ->willReturn([]);

        $this->assertReindexEvent([3, 1, 2]);
        $this->eventListener->onFlush(new OnFlushEventArgs($entityManager));
    }

    public function testItReindexWithManyProductsAfterFlushWithEmptyChangeSet()
    {
        $contentVariant = $this->generateContentVariant(ProductPageContentVariantType::TYPE, 1);
        $product = $this->generateProduct(1);
        $contentVariant->setProductPageProduct($product);

        $entityChangeSet = ['product_page_product' => [0 => null, 1 => null]];
        $entityManager = $this->getEntityManager([$contentVariant], [], [], $entityChangeSet);
        $this->webCatalogUsageProvider
            ->method('getAssignedWebCatalogs')
            ->willReturn([]);

        $this->assertReindexEvent([1]);
        $this->eventListener->onFlush(new OnFlushEventArgs($entityManager));
    }

    public function testProductsOfRelatedContentVariantWillBeReindexOnlyIfConfigurableFieldsHaveSomeChanges()
    {
        $contentVariant1 = $this->generateContentVariant(ProductPageContentVariantType::TYPE, 1);
        $contentVariant2 = $this->generateContentVariant(ProductPageContentVariantType::TYPE, 2);
        $contentVariant3 = $this->generateContentVariant(ProductPageContentVariantType::TYPE, 3);
        $contentVariant4 = $this->generateContentVariant(ProductPageContentVariantType::TYPE, 4);

        $contentNodeWithFieldChanges = (new ContentNodeStub(1))
            ->addContentVariant($contentVariant1)
            ->addContentVariant($contentVariant2);
        $contentNodeWithoutFieldChanges = (new ContentNodeStub(2))
            ->addContentVariant($contentVariant3)
            ->addContentVariant($contentVariant4);

        $entityManager = $this->getEntityManager(
            [],
            [$contentNodeWithFieldChanges, $contentNodeWithoutFieldChanges, new \stdClass()],
            []
        );
        $this->fieldUpdatesChecker
            ->method('isRelationFieldChanged')
            ->willReturnMap([
                [$contentNodeWithFieldChanges, 'titles', true],
                [$contentNodeWithoutFieldChanges, 'titles', false],
            ]);

        $this->webCatalogUsageProvider
            ->method('getAssignedWebCatalogs')
            ->willReturn([0 => '1']);

        // only products from $contentNodeWithFieldChanges should be reindex
        $this->assertReindexEvent([1, 2]);
        $this->eventListener->onFlush(new OnFlushEventArgs($entityManager));
    }

    public function testItReindexRelatedWebsitesAfterFlush()
    {
        $contentVariant1 = $this->generateContentVariant(ProductPageContentVariantType::TYPE, 1, 1);
        $contentVariant2 = $this->generateContentVariant(ProductPageContentVariantType::TYPE, 2, 1);
        $contentVariant3 = $this->generateContentVariant(CategoryPageContentVariantType::TYPE, null, 1);

        $entityManager = $this->getEntityManager([$contentVariant1, $contentVariant2, $contentVariant3]);

        $this->webCatalogUsageProvider
            ->method('getAssignedWebCatalogs')
            ->willReturn([1 => '1', 2 => '2', 3 => '1', 4 => '2', 5 => '3', 6 => '5']);

        $this->assertReindexEvent([1, 2], [1, 3]);
        $this->eventListener->onFlush(new OnFlushEventArgs($entityManager));
    }

    public function testItReindexDefaultRelatedWebsiteAfterFlush()
    {
        $websiteMock = $this->createMock(Website::class);
        $websiteMock->method('getId')
            ->willReturn(1);

        $contentVariant1 = $this->generateContentVariant(ProductPageContentVariantType::TYPE, 1, 1);
        $contentVariant2 = $this->generateContentVariant(ProductPageContentVariantType::TYPE, 2, 1);
        $contentVariant3 = $this->generateContentVariant(CategoryPageContentVariantType::TYPE, null, 1);

        $entityManager = $this->getEntityManager([$contentVariant1, $contentVariant2, $contentVariant3]);

        $this->webCatalogUsageProvider
            ->method('getAssignedWebCatalogs')
            ->willReturn([
                0 => '1'
            ]);

        $this->assertReindexEvent([1, 2]);
        $this->eventListener->onFlush(new OnFlushEventArgs($entityManager));
    }

    /**
     * @param string $type
     * @param int|null $productId
     * @param int|null $webCatalogId
     * @return ContentVariantStub
     */
    private function generateContentVariant($type, $productId = null, $webCatalogId = null)
    {
        $contentVariant = new ContentVariantStub();
        $contentVariant->setType($type);
        if ($productId !== null) {
            $product = $this->generateProduct($productId);
            $contentVariant->setProductPageProduct($product);
        }
        $webCatalogMock = $this->createMock(WebCatalogInterface::class);
        $webCatalogMock->method('getId')
            ->willReturn($webCatalogId);

        $contentNodeMock = $this->createMock(ContentNode::class);
        $contentNodeMock->method('getWebCatalog')
            ->willReturn($webCatalogMock);

        $contentVariant->setNode($contentNodeMock);

        return $contentVariant;
    }

    /**
     * @param int $productId
     * @return Product|\PHPUnit_Framework_MockObject_MockObject
     */
    private function generateProduct($productId)
    {
        $product = $this->createMock(Product::class);
        $product->method('getId')
            ->willReturn($productId);

        return $product;
    }

    /**
     * @param array $insertions
     * @param array $updates
     * @param array $deletions
     * @param array $entityChangeSet
     * @return EntityManagerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private function getEntityManager(
        array $insertions = [],
        array $updates = [],
        array $deletions = [],
        array $entityChangeSet = []
    ) {
        $unitOfWork = $this->getMockBuilder(UnitOfWork::class)
            ->disableOriginalConstructor()
            ->getMock();

        $unitOfWork->expects($this->once())
            ->method('getScheduledEntityInsertions')
            ->willReturn($insertions);
        $unitOfWork->expects($this->once())
            ->method('getScheduledEntityUpdates')
            ->willReturn($updates);
        $unitOfWork->expects($this->once())
            ->method('getScheduledEntityDeletions')
            ->willReturn($deletions);
        $unitOfWork
            ->method('getEntityChangeSet')
            ->willReturn($entityChangeSet);

        /** @var EntityManagerInterface|\PHPUnit_Framework_MockObject_MockObject $entityManager */
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->any())
            ->method('getUnitOfWork')
            ->willReturn($unitOfWork);

        return $entityManager;
    }

    /**
     * @param array $productIds
     * @param array $websiteIds
     */
    private function assertReindexEvent(array $productIds = [], array $websiteIds = [])
    {
        $this->eventDispatcher
            ->expects($this->once())
            ->method('dispatch')
            ->with(
                ReindexationRequestEvent::EVENT_NAME,
                new ReindexationRequestEvent([Product::class], $websiteIds, $productIds)
            );
    }
}
