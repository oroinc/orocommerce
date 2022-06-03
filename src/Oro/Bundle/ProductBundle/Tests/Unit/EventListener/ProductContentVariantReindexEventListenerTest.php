<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\EventListener;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\UnitOfWork;
use Oro\Bundle\CatalogBundle\ContentVariantType\CategoryPageContentVariantType;
use Oro\Bundle\ProductBundle\ContentVariantType\ProductCollectionContentVariantType;
use Oro\Bundle\ProductBundle\ContentVariantType\ProductPageContentVariantType;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\EventListener\ProductCollectionVariantReindexMessageSendListener;
use Oro\Bundle\ProductBundle\EventListener\ProductContentVariantReindexEventListener;
use Oro\Bundle\ProductBundle\Tests\Unit\ContentVariant\Stub\ContentVariantStub;
use Oro\Bundle\ProductBundle\Tests\Unit\Entity\Stub\ContentNodeStub;
use Oro\Bundle\SegmentBundle\Entity\Segment;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Bundle\WebsiteSearchBundle\Event\ReindexationRequestEvent;
use Oro\Component\DoctrineUtils\ORM\FieldUpdatesChecker;
use Oro\Component\WebCatalog\Entity\ContentNodeInterface;
use Oro\Component\WebCatalog\Entity\ContentVariantInterface;
use Oro\Component\WebCatalog\Entity\WebCatalogInterface;
use Oro\Component\WebCatalog\Provider\WebCatalogUsageProviderInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class ProductContentVariantReindexEventListenerTest extends \PHPUnit\Framework\TestCase
{
    /** @var WebCatalogUsageProviderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $webCatalogUsageProvider;

    /** @var FieldUpdatesChecker|\PHPUnit\Framework\MockObject\MockObject */
    private $fieldUpdatesChecker;

    /** @var EventDispatcherInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $eventDispatcher;

    /** @var ProductCollectionVariantReindexMessageSendListener|\PHPUnit\Framework\MockObject\MockObject */
    private $messageSendListener;

    /** @var ProductContentVariantReindexEventListener */
    private $eventListener;

    protected function setUp(): void
    {
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->fieldUpdatesChecker = $this->createMock(FieldUpdatesChecker::class);
        $this->webCatalogUsageProvider = $this->createMock(WebCatalogUsageProviderInterface::class);
        $this->messageSendListener = $this->createMock(ProductCollectionVariantReindexMessageSendListener::class);

        $this->eventListener = new ProductContentVariantReindexEventListener(
            $this->eventDispatcher,
            $this->fieldUpdatesChecker,
            $this->messageSendListener,
            $this->webCatalogUsageProvider
        );
    }

    public function testItAcceptsOnlyContentVariantAfterFlush()
    {
        $entityManager = $this->getEntityManager([new \stdClass()]);
        $this->webCatalogUsageProvider->expects($this->once())
            ->method('getAssignedWebCatalogs')
            ->willReturn([]);
        $this->eventDispatcher->expects($this->never())
            ->method('dispatch');

        $this->eventListener->onFlush(new OnFlushEventArgs($entityManager));
    }

    public function testCreatedAndDeletedProductsOfContentVariantsWillBeReindexButNoProductsWithoutChangeSet()
    {
        $contentVariant1 = $this->generateContentVariant(1);
        $contentVariant2 = $this->generateContentVariant(2);
        $contentVariant3 = $this->generateContentVariant(3);
        $contentVariant4 = $this->generateContentVariant(4);
        $contentVariant5 = $this->generateContentVariant(5);
        $contentVariant6 = $this->generateContentVariant(6);

        $entityManager = $this->getEntityManager(
            [$contentVariant1, $contentVariant2],
            [$contentVariant3, $contentVariant4],
            [$contentVariant5, $contentVariant6]
        );

        $this->webCatalogUsageProvider->expects($this->atLeastOnce())
            ->method('getAssignedWebCatalogs')
            ->willReturn([1 => 1]);

        $this->assertReindexEvent([1, 2, 5, 6], [1]);
        $this->eventListener->onFlush(new OnFlushEventArgs($entityManager));
    }

    public function testItReindexEachProductOnlyOnceAfterFlush()
    {
        $contentVariant1 = $this->generateContentVariant(1);
        $contentVariant2 = $this->generateContentVariant(2);
        $contentVariant3 = $this->generateContentVariant(3);

        $entityManager = $this->getEntityManager(
            [$contentVariant1, $contentVariant2, $contentVariant3],
            [$contentVariant1, $contentVariant2, $contentVariant3],
            [$contentVariant1, $contentVariant2, $contentVariant3]
        );

        $this->webCatalogUsageProvider->expects($this->atLeastOnce())
            ->method('getAssignedWebCatalogs')
            ->willReturn([1 => 1]);

        $this->assertReindexEvent([1, 2, 3], [1]);
        $this->eventListener->onFlush(new OnFlushEventArgs($entityManager));
    }

    public function testProductIdsOfChangedContentVariantsWillBeTriggered()
    {
        $contentVariant1 = $this->generateContentVariant(1);
        $oldProduct = $contentVariant1->getProductPageProduct();
        $newProduct = $this->generateProduct(3);
        $contentVariant1->setProductPageProduct($newProduct);

        $entityChangeSet = ['product_page_product' => [$oldProduct, $newProduct]];
        $entityManager = $this->getEntityManager(
            [],
            [$contentVariant1],
            [],
            $entityChangeSet
        );

        $this->webCatalogUsageProvider->expects($this->once())
            ->method('getAssignedWebCatalogs')
            ->willReturn([1 => 1]);

        $this->assertReindexEvent([1, 3], [1]);
        $this->eventListener->onFlush(new OnFlushEventArgs($entityManager));
    }

    public function testItReindexWithManyProductsAfterFlushWithEmptyChangeSet()
    {
        $contentVariant = $this->generateContentVariant(1);
        $product = $this->generateProduct(1);
        $contentVariant->setProductPageProduct($product);

        $entityChangeSet = ['product_page_product' => [0 => null, 1 => null]];
        $entityManager = $this->getEntityManager(
            [$contentVariant],
            [],
            [],
            $entityChangeSet
        );
        $this->webCatalogUsageProvider->expects($this->once())
            ->method('getAssignedWebCatalogs')
            ->willReturn([1 => 1]);

        $this->assertReindexEvent([1], [1]);
        $this->eventListener->onFlush(new OnFlushEventArgs($entityManager));
    }

    public function testProductsOfRelatedContentVariantWillBeReindexOnlyIfConfigurableFieldsHaveSomeChanges()
    {
        $contentVariant1 = $this->generateContentVariant(1);
        $contentVariant2 = $this->generateContentVariant(2);
        $contentVariant3 = $this->generateContentVariant(3);
        $contentVariant4 = $this->generateContentVariant(4);

        $contentNodeWithFieldChanges = (new ContentNodeStub(1))
            ->addContentVariant($contentVariant1)
            ->addContentVariant($contentVariant2);
        $contentNodeWithoutFieldChanges = (new ContentNodeStub(2))
            ->addContentVariant($contentVariant3)
            ->addContentVariant($contentVariant4);

        $entityManager = $this->getEntityManager(
            [],
            [$contentNodeWithFieldChanges, $contentNodeWithoutFieldChanges, new \stdClass()]
        );
        $this->fieldUpdatesChecker->expects($this->atLeastOnce())
            ->method('isRelationFieldChanged')
            ->willReturnMap([
                [$contentNodeWithFieldChanges, 'titles', true],
                [$contentNodeWithoutFieldChanges, 'titles', false],
            ]);

        $this->webCatalogUsageProvider->expects($this->atLeastOnce())
            ->method('getAssignedWebCatalogs')
            ->willReturn([1 => 1]);

        // only products from $contentNodeWithFieldChanges should be reindex
        $this->assertReindexEvent([1, 2], [1]);
        $this->eventListener->onFlush(new OnFlushEventArgs($entityManager));
    }

    public function testItReindexRelatedWebsitesAfterFlush()
    {
        $contentVariant1 = $this->generateContentVariant(1, 1);
        $contentVariant2 = $this->generateContentVariant(2, 1);
        $contentVariant3 = $this->generateContentVariant(null, 1);
        $contentVariant3->setType(CategoryPageContentVariantType::TYPE);

        $entityManager = $this->getEntityManager(
            [$contentVariant1, $contentVariant2, $contentVariant3]
        );

        $this->webCatalogUsageProvider->expects($this->atLeastOnce())
            ->method('getAssignedWebCatalogs')
            ->willReturn([1 => '1', 2 => '2', 3 => '1', 4 => '2', 5 => '3', 6 => '5']);

        $this->assertReindexEvent([1, 2], [1, 3]);
        $this->eventListener->onFlush(new OnFlushEventArgs($entityManager));
    }

    public function testItReindexDefaultRelatedWebsiteAfterFlush()
    {
        $websiteMock = $this->createMock(Website::class);
        $websiteMock->expects($this->any())
            ->method('getId')
            ->willReturn(1);

        $contentVariant1 = $this->generateContentVariant(1, 1);
        $contentVariant2 = $this->generateContentVariant(2, 1);
        $contentVariant3 = $this->generateContentVariant(null, 1);
        $contentVariant3->setType(CategoryPageContentVariantType::TYPE);

        $entityManager = $this->getEntityManager(
            [$contentVariant1, $contentVariant2, $contentVariant3]
        );

        $this->webCatalogUsageProvider->expects($this->atLeastOnce())
            ->method('getAssignedWebCatalogs')
            ->willReturn([1 => 1]);

        $this->assertReindexEvent([1, 2], [1]);
        $this->eventListener->onFlush(new OnFlushEventArgs($entityManager));
    }

    public function testWebsiteIdsWillNotBeCollectedIfThereAreNoProductsToReindex()
    {
        $entityManager = $this->getEntityManager();

        $this->webCatalogUsageProvider->expects($this->never())
            ->method('getAssignedWebCatalogs');
        $this->eventDispatcher->expects($this->never())
            ->method('dispatch');

        $this->eventListener->onFlush(new OnFlushEventArgs($entityManager));
    }

    public function testReindexationWillNotBeTriggeredWhenThereAreNoWebsitesWithCurrentWebCatalog()
    {
        $contentVariant = $this->generateContentVariant(1);
        $entityManager = $this->getEntityManager([$contentVariant]);

        $this->webCatalogUsageProvider->expects($this->once())
            ->method('getAssignedWebCatalogs')
            ->willReturn([]);
        $this->eventDispatcher->expects($this->never())
            ->method('dispatch');

        $this->eventListener->onFlush(new OnFlushEventArgs($entityManager));
    }

    /**
     * @dataProvider dataProviderForNotWebCatalogAwareEntities
     */
    public function testReindexationWillNotBeTriggeredWhenThereAreNotWebCatalogAwareEntitiesChanged(
        ContentVariantInterface $contentVariant
    ) {
        $entityManager = $this->getEntityManager([$contentVariant]);

        $this->webCatalogUsageProvider->expects($this->once())
            ->method('getAssignedWebCatalogs')
            ->willReturn([1 => 1]);
        $this->eventDispatcher->expects($this->never())
            ->method('dispatch');

        $this->eventListener->onFlush(new OnFlushEventArgs($entityManager));
    }

    public function dataProviderForNotWebCatalogAwareEntities(): array
    {
        $notContentNodeAwareEntity = $this->createMock(ContentVariantInterface::class);
        $notWebCatalogAwareEntity = $this->generateContentVariant(1);
        $notWebCatalogAwareEntity->setNode($this->createMock(ContentNodeInterface::class));

        return [
            [$notContentNodeAwareEntity],
            [$notWebCatalogAwareEntity],
        ];
    }

    public function testReindexationForProductCollectionIfNodeFieldsWasChanged()
    {
        [$contentNode, $segment] = $this->generateContentNodeAndSegment();
        $entityManager = $this->getEntityManager([], [$contentNode]);
        $this->fieldUpdatesChecker->expects($this->once())
            ->method('isRelationFieldChanged')
            ->with($contentNode, 'titles')
            ->willReturn(true);
        $this->messageSendListener->expects($this->once())
            ->method('scheduleSegment')
            ->with($segment);
        $this->webCatalogUsageProvider->expects($this->atLeastOnce())
            ->method('getAssignedWebCatalogs')
            ->willReturn([]);

        $this->eventListener->onFlush(new OnFlushEventArgs($entityManager));
    }

    public function testReindexationForProductCollectionIfNodeFieldsWithoutChanges()
    {
        [$contentNode] = $this->generateContentNodeAndSegment();
        $entityManager = $this->getEntityManager([], [$contentNode]);
        $this->fieldUpdatesChecker->expects($this->once())
            ->method('isRelationFieldChanged')
            ->with($contentNode, 'titles')
            ->willReturn(false);
        $this->messageSendListener->expects($this->never())
            ->method('scheduleSegment');
        $this->webCatalogUsageProvider->expects($this->once())
            ->method('getAssignedWebCatalogs')
            ->willReturn([]);

        $this->eventListener->onFlush(new OnFlushEventArgs($entityManager));
    }

    /**
     * @return EntityManagerInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private function getEntityManager(
        array $insertions = [],
        array $updates = [],
        array $deletions = [],
        array $entityChangeSet = []
    ) {
        $unitOfWork = $this->createMock(UnitOfWork::class);
        $unitOfWork->expects($this->any())
            ->method('getScheduledEntityInsertions')
            ->willReturn($insertions);
        $unitOfWork->expects($this->any())
            ->method('getScheduledEntityUpdates')
            ->willReturn($updates);
        $unitOfWork->expects($this->any())
            ->method('getScheduledEntityDeletions')
            ->willReturn($deletions);
        $unitOfWork->expects($this->any())
            ->method('getEntityChangeSet')
            ->willReturn($entityChangeSet);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->any())
            ->method('getUnitOfWork')
            ->willReturn($unitOfWork);

        return $entityManager;
    }

    /**
     * @param int $webCatalogId
     * @return array [<ContentNode>, <Segment>]
     */
    private function generateContentNodeAndSegment($webCatalogId = 1)
    {
        $contentVariant = new ContentVariantStub();
        $contentVariant->setType(ProductCollectionContentVariantType::TYPE);
        $segment = new Segment();
        $contentVariant->setProductCollectionSegment($segment);
        $webCatalogMock = $this->createMock(WebCatalogInterface::class);
        $webCatalogMock->expects($this->any())
            ->method('getId')
            ->willReturn($webCatalogId);

        $contentNode = new ContentNodeStub(1);
        $contentNode->setWebCatalog($webCatalogMock);
        $contentNode->addContentVariant($contentVariant);
        $contentVariant->setNode($contentNode);

        return [$contentNode, $segment];
    }

    /**
     * @param int|null $productId
     * @param int|null $webCatalogId
     * @return ContentVariantStub
     */
    private function generateContentVariant($productId = null, $webCatalogId = 1)
    {
        $contentVariant = new ContentVariantStub();
        $contentVariant->setType(ProductPageContentVariantType::TYPE);
        if ($productId !== null) {
            $product = $this->generateProduct($productId);
            $contentVariant->setProductPageProduct($product);
        }
        $webCatalogMock = $this->createMock(WebCatalogInterface::class);
        $webCatalogMock->expects($this->any())
            ->method('getId')
            ->willReturn($webCatalogId);

        $contentVariant->setNode((new ContentNodeStub(1))->setWebCatalog($webCatalogMock));

        return $contentVariant;
    }

    /**
     * @param int $productId
     * @return Product|\PHPUnit\Framework\MockObject\MockObject
     */
    private function generateProduct($productId)
    {
        $product = $this->createMock(Product::class);
        $product->expects($this->any())
            ->method('getId')
            ->willReturn($productId);

        return $product;
    }

    private function assertReindexEvent(array $productIds = [], array $websiteIds = [])
    {
        $this->eventDispatcher->expects($this->once())
            ->method('dispatch')
            ->with(
                new ReindexationRequestEvent([Product::class], $websiteIds, $productIds, true, ['main']),
                ReindexationRequestEvent::EVENT_NAME
            );
    }
}
