<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\EventListener;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\FormInterface;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\UnitOfWork;

use Oro\Bundle\ProductBundle\Tests\Unit\Entity\Stub\ContentNodeStub;
use Oro\Component\DoctrineUtils\ORM\FieldUpdatesChecker;
use Oro\Component\WebCatalog\Entity\ContentNodeInterface;
use Oro\Bundle\WebsiteSearchBundle\Event\ReindexationRequestEvent;
use Oro\Bundle\FormBundle\Event\FormHandler\AfterFormProcessEvent;
use Oro\Bundle\ProductBundle\ContentVariantType\ProductPageContentVariantType;
use Oro\Bundle\ProductBundle\EventListener\ProductContentVariantReindexEventListener;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Tests\Unit\ContentVariant\Stub\ContentVariantStub;

class ProductContentVariantReindexEventListenerTest extends \PHPUnit_Framework_TestCase
{
    /** @var OnFlushEventArgs */
    private $onFlushEventArgs;

    /** @var UnitOfWork|\PHPUnit_Framework_MockObject_MockObject */
    private $unitOfWork;

    /** @var EventDispatcherInterface|\PHPUnit_Framework_MockObject_MockObject */
    private $eventDispatcher;

    /** @var ContentNodeInterface|\PHPUnit_Framework_MockObject_MockObject */
    private $contentNode;

    /** @var ProductContentVariantReindexEventListener */
    private $eventListener;

    /** @var AfterFormProcessEvent */
    private $afterFormProcessEvent;

    /** @var FieldUpdatesChecker|\PHPUnit_Framework_MockObject_MockObject */
    private $fieldUpdatesChecker;

    public function setUp()
    {
        $this->eventDispatcher = $this->getMockBuilder(EventDispatcherInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->fieldUpdatesChecker = $this->getMockBuilder(FieldUpdatesChecker::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->eventListener = new ProductContentVariantReindexEventListener(
            $this->eventDispatcher,
            $this->fieldUpdatesChecker
        );
    }

    public function testItAcceptsOnlyContentVariantAfterFlush()
    {
        $this->prepareMocksForOnFlush();

        $this->eventDispatcher
            ->expects($this->never())
            ->method('dispatch');

        $object = new \stdClass();

        $this->unitOfWork
            ->method('getScheduledEntityInsertions')
            ->willReturn([$object]);
        $this->unitOfWork
            ->method('getScheduledEntityUpdates')
            ->willReturn([]);
        $this->unitOfWork
            ->method('getScheduledEntityDeletions')
            ->willReturn([]);

        $this->eventListener->onFlush($this->onFlushEventArgs);
    }

    public function testItDoesntReindexWhenNoProductsAfterFlush()
    {
        $this->prepareMocksForOnFlush();

        $this->eventDispatcher
            ->expects($this->never())
            ->method('dispatch');

        $contentVariant = $this->generateContentVariant(ProductPageContentVariantType::TYPE);

        $this->unitOfWork
            ->method('getScheduledEntityInsertions')
            ->willReturn([$contentVariant]);
        $this->unitOfWork
            ->method('getScheduledEntityUpdates')
            ->willReturn([]);
        $this->unitOfWork
            ->method('getScheduledEntityDeletions')
            ->willReturn([]);

        $this->eventListener->onFlush($this->onFlushEventArgs);
    }

    public function testItReindexWithManyProductsAfterFlush()
    {
        $this->prepareMocksForOnFlush();

        $this->eventDispatcher
            ->expects($this->once())
            ->method('dispatch')
            ->with(
                ReindexationRequestEvent::EVENT_NAME,
                new ReindexationRequestEvent([Product::class], [], [1, 2, 3, 4, 5, 6])
            );

        $contentVariant1 = $this->generateContentVariant(ProductPageContentVariantType::TYPE, 1);
        $contentVariant2 = $this->generateContentVariant(ProductPageContentVariantType::TYPE, 2);
        $contentVariant3 = $this->generateContentVariant(ProductPageContentVariantType::TYPE, 3);
        $contentVariant4 = $this->generateContentVariant(ProductPageContentVariantType::TYPE, 4);
        $contentVariant5 = $this->generateContentVariant(ProductPageContentVariantType::TYPE, 5);
        $contentVariant6 = $this->generateContentVariant(ProductPageContentVariantType::TYPE, 6);

        $this->unitOfWork
            ->method('getScheduledEntityInsertions')
            ->willReturn([$contentVariant1, $contentVariant2]);

        $this->unitOfWork
            ->method('getScheduledEntityUpdates')
            ->willReturn([$contentVariant3, $contentVariant4]);

        $this->unitOfWork
            ->method('getScheduledEntityDeletions')
            ->willReturn([$contentVariant5, $contentVariant6]);

        $this->unitOfWork
            ->method('getEntityChangeSet')
            ->willReturn([]);

        $this->eventListener->onFlush($this->onFlushEventArgs);
    }

    public function testItReindexEachProductOnlyOnceAfterFlush()
    {
        $this->prepareMocksForOnFlush();

        $this->eventDispatcher
            ->expects($this->once())
            ->method('dispatch')
            ->with(
                ReindexationRequestEvent::EVENT_NAME,
                new ReindexationRequestEvent([Product::class], [], [1, 2, 3])
            );

        $contentVariant1 = $this->generateContentVariant(ProductPageContentVariantType::TYPE, 1);
        $contentVariant2 = $this->generateContentVariant(ProductPageContentVariantType::TYPE, 2);
        $contentVariant3 = $this->generateContentVariant(ProductPageContentVariantType::TYPE, 3);

        $this->unitOfWork
            ->method('getScheduledEntityInsertions')
            ->willReturn([$contentVariant1, $contentVariant2, $contentVariant3]);

        $this->unitOfWork
            ->method('getScheduledEntityUpdates')
            ->willReturn([$contentVariant1, $contentVariant2, $contentVariant3]);

        $this->unitOfWork
            ->method('getScheduledEntityDeletions')
            ->willReturn([$contentVariant1, $contentVariant2, $contentVariant3]);

        $this->unitOfWork
            ->method('getEntityChangeSet')
            ->willReturn([]);

        $this->eventListener->onFlush($this->onFlushEventArgs);
    }

    public function testItReindexWithManyProductsAfterFlushWithChangeSet()
    {
        $this->prepareMocksForOnFlush();

        $this->eventDispatcher
            ->expects($this->once())
            ->method('dispatch')
            ->with(
                ReindexationRequestEvent::EVENT_NAME,
                new ReindexationRequestEvent([Product::class], [], [3, 1, 2])
            );

        $contentVariant1 = $this->generateContentVariant(ProductPageContentVariantType::TYPE, 1);
        $contentVariant2 = $this->generateContentVariant(ProductPageContentVariantType::TYPE, 2);
        $oldProduct = $contentVariant1->getProductPageProduct();
        $newProduct = $this->generateProduct(3);
        $contentVariant1->setProductPageProduct($newProduct);

        $this->unitOfWork
            ->method('getScheduledEntityInsertions')
            ->willReturn([$contentVariant1, $contentVariant2]);
        $this->unitOfWork
            ->method('getScheduledEntityUpdates')
            ->willReturn([]);
        $this->unitOfWork
            ->method('getScheduledEntityDeletions')
            ->willReturn([]);

        $this->unitOfWork
            ->method('getEntityChangeSet')
            ->with($contentVariant1)
            ->willReturn(['product_page_product' => [$oldProduct, $newProduct]]);

        $this->eventListener->onFlush($this->onFlushEventArgs);
    }

    public function testItReindexWithManyProductsAfterFlushWithEmptyChangeSet()
    {
        $this->prepareMocksForOnFlush();

        $this->eventDispatcher
            ->expects($this->once())
            ->method('dispatch')
            ->with(
                ReindexationRequestEvent::EVENT_NAME,
                new ReindexationRequestEvent([Product::class], [], [1])
            );

        $contentVariant = $this->generateContentVariant(ProductPageContentVariantType::TYPE, 1);
        $product = $this->generateProduct(1);
        $contentVariant->setProductPageProduct($product);

        $this->unitOfWork
            ->method('getScheduledEntityInsertions')
            ->willReturn([$contentVariant]);
        $this->unitOfWork
            ->method('getScheduledEntityUpdates')
            ->willReturn([]);
        $this->unitOfWork
            ->method('getScheduledEntityDeletions')
            ->willReturn([]);

        $this->unitOfWork
            ->method('getEntityChangeSet')
            ->with($contentVariant)
            ->willReturn(['product_page_product' => [0 => null, 1 => null]]);

        $this->eventListener->onFlush($this->onFlushEventArgs);
    }

    public function testProductsOfRelatedContentVariantWillBeReindexOnlyIfConfigurableFieldsHaveSomeChanges()
    {
        $this->prepareMocksForOnFlush();

        $contentVariant1 = $this->generateContentVariant(ProductPageContentVariantType::TYPE, 1);
        $contentVariant2 = $this->generateContentVariant(ProductPageContentVariantType::TYPE, 2);
        $contentVariant3 = $this->generateContentVariant(ProductPageContentVariantType::TYPE, 3);
        $contentVariant4 = $this->generateContentVariant(ProductPageContentVariantType::TYPE, 4);
        $contentVariant5 = $this->generateContentVariant(ProductPageContentVariantType::TYPE, 5);
        $contentVariant6 = $this->generateContentVariant(ProductPageContentVariantType::TYPE, 6);

        $contentNodeWithFieldChanges = (new ContentNodeStub(1))
            ->addContentVariant($contentVariant1)
            ->addContentVariant($contentVariant2)
            ->addContentVariant($contentVariant3);
        $contentNodeWithoutFieldChanges = (new ContentNodeStub(2))
            ->addContentVariant($contentVariant4)
            ->addContentVariant($contentVariant5)
            ->addContentVariant($contentVariant6);

        $this->unitOfWork
            ->method('getScheduledEntityInsertions')
            ->willReturn([]);
        $this->unitOfWork
            ->method('getScheduledEntityDeletions')
            ->willReturn([]);
        $this->unitOfWork
            ->method('getScheduledEntityUpdates')
            ->willReturn([$contentNodeWithFieldChanges, $contentNodeWithoutFieldChanges, new \stdClass()]);

        $this->fieldUpdatesChecker
            ->method('isRelationFieldChanged')
            ->willReturnMap([
                [$contentNodeWithFieldChanges, 'titles', true],
                [$contentNodeWithoutFieldChanges, 'titles', false],
            ]);

        // only products from $contentNodeWithFieldChanges should be reindex
        $this->eventDispatcher
            ->expects($this->once())
            ->method('dispatch')
            ->with(
                ReindexationRequestEvent::EVENT_NAME,
                new ReindexationRequestEvent([Product::class], [], [1, 2, 3])
            );

        $this->eventListener->onFlush($this->onFlushEventArgs);
    }

    /**
     * @param string $type
     * @param int $productId
     * @return ContentVariantStub
     */
    protected function generateContentVariant($type, $productId = 0)
    {
        $contentVariant = new ContentVariantStub();
        $contentVariant->setType($type);
        if ($productId !== 0) {
            $product = $this->generateProduct($productId);
            $contentVariant->setProductPageProduct($product);
        }

        return $contentVariant;
    }

    protected function prepareMocksForOnFlush()
    {
        $this->unitOfWork = $this->getMockBuilder(UnitOfWork::class)
            ->disableOriginalConstructor()
            ->getMock();

        /** @var EntityManager|\PHPUnit_Framework_MockObject_MockObject $em */
        $em = $this->getMockBuilder(EntityManager::class)
            ->disableOriginalConstructor()
            ->getMock();
        $em->method('getUnitOfWork')
            ->willReturn($this->unitOfWork);

        $this->onFlushEventArgs = new OnFlushEventArgs($em);
    }

    protected function prepareMocksForOnFormFlush()
    {
        $this->contentNode = $this->createMock(ContentNodeInterface::class);
        /** @var FormInterface $form */
        $form = $this->createMock(FormInterface::class);

        $this->afterFormProcessEvent = new AfterFormProcessEvent($form, $this->contentNode);
    }

    /**
     * @param int $productId
     * @return Product|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function generateProduct($productId)
    {
        $product = $this->createMock(Product::class);
        $product->method('getId')
            ->willReturn($productId);
        return $product;
    }
}
