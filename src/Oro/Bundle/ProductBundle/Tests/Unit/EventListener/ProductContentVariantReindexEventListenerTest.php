<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\EventListener;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\FormInterface;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\UnitOfWork;

use Oro\Component\WebCatalog\Entity\ContentNodeInterface;
use Oro\Bundle\WebsiteSearchBundle\Event\ReindexationRequestEvent;
use Oro\Bundle\FormBundle\Event\FormHandler\AfterFormProcessEvent;
use Oro\Bundle\ProductBundle\ContentVariantType\ProductPageContentVariantType;
use Oro\Bundle\ProductBundle\EventListener\ProductContentVariantReindexEventListener;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Tests\Unit\ContentVariant\Stub\ContentVariantStub;

class ProductContentVariantReindexEventListenerTest extends \PHPUnit_Framework_TestCase
{
    /** @var OnFlushEventArgs|\PHPUnit_Framework_MockObject_MockObject */
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

    public function setUp()
    {
        $this->eventDispatcher = $this->getMockBuilder(EventDispatcherInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->eventListener = new ProductContentVariantReindexEventListener($this->eventDispatcher);
    }

    public function testItAcceptsOnlyContentNodeAfterFlush()
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

    public function testItReindexWithManyProductAfterFlush()
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

        $this->eventListener->onFlush($this->onFlushEventArgs);
    }

    public function testItDoesntReindexWhenNoProductsAfterFormFlush()
    {
        $this->prepareMocksForOnFormFlush();

        $this->eventDispatcher
            ->expects($this->never())
            ->method('dispatch');

        $contentVariant = $this->generateContentVariant(ProductPageContentVariantType::TYPE);

        $this->contentNode
            ->method('getContentVariants')
            ->willReturn([$contentVariant]);

        $this->eventListener->onFormAfterFlush($this->afterFormProcessEvent);
    }

    public function testItReindexWithManyProductAfterFormFlush()
    {
        $this->prepareMocksForOnFormFlush();

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

        $this->eventDispatcher
            ->expects($this->once())
            ->method('dispatch');

        $this->contentNode
            ->method('getContentVariants')
            ->willReturn([
                $contentVariant1,
                $contentVariant2,
                $contentVariant3,
                $contentVariant4,
                $contentVariant5,
                $contentVariant6
            ]);

        $this->eventListener->onFormAfterFlush($this->afterFormProcessEvent);
    }

    public function testItReindexEachProductOnlyOnceAfterFormFlush()
    {
        $this->prepareMocksForOnFormFlush();

        $this->eventDispatcher
            ->expects($this->once())
            ->method('dispatch');

        $contentVariant1 = $this->generateContentVariant(ProductPageContentVariantType::TYPE, 1);
        $contentVariant2 = $this->generateContentVariant(ProductPageContentVariantType::TYPE, 2);
        $contentVariant3 = $this->generateContentVariant(ProductPageContentVariantType::TYPE, 3);

        $this->contentNode
            ->method('getContentVariants')
            ->willReturn([$contentVariant1, $contentVariant2, $contentVariant3]);

        $this->eventDispatcher
            ->expects($this->once())
            ->method('dispatch')
            ->with(
                ReindexationRequestEvent::EVENT_NAME,
                new ReindexationRequestEvent([Product::class], [], [1, 2, 3])
            );

        $this->eventListener->onFormAfterFlush($this->afterFormProcessEvent);
    }

    /**
     * @param $type
     * @param int $productId
     * @return ContentVariantStub
     */
    protected function generateContentVariant($type, $productId = 0)
    {
        $contentVariant = new ContentVariantStub();
        $contentVariant->setType($type);
        if ($productId !== 0) {
            /** @var Product|\PHPUnit_Framework_MockObject_MockObject $product */
            $product = $this->createMock(Product::class);
            $product->method('getId')
                ->willReturn($productId);

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
}
