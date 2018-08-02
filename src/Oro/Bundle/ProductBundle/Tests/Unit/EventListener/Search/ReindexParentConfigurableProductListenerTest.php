<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\EventListener\Search;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\OnClearEventArgs;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductVariantLink;
use Oro\Bundle\ProductBundle\EventListener\Search\ReindexParentConfigurableProductListener;
use Oro\Bundle\WebsiteSearchBundle\Event\ReindexationRequestEvent;
use Oro\Component\Testing\Unit\EntityTrait;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class ReindexParentConfigurableProductListenerTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /** @var EventDispatcherInterface|\PHPUnit\Framework\MockObject\MockObject */
    protected $eventDispatcher;

    /** @var ReindexParentConfigurableProductListener */
    protected $listener;

    protected function setUp()
    {
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);

        $this->listener = new ReindexParentConfigurableProductListener($this->eventDispatcher);
    }

    public function testPostPersist()
    {
        $simpleProduct = $this->getSimpleProduct(1);
        $productVariant = $this->getProductVariant(2, 3);

        $this->listener->postPersist($simpleProduct);
        $this->listener->postPersist($productVariant);

        $this->assertAttributeEquals([2], 'productIds', $this->listener);
    }

    public function testPostUpdate()
    {
        $simpleProduct = $this->getSimpleProduct(1);
        $productVariant = $this->getProductVariant(2, 3);

        $this->listener->postUpdate($simpleProduct);
        $this->listener->postUpdate($productVariant);

        $this->assertAttributeEquals([2], 'productIds', $this->listener);
    }

    public function testPreRemove()
    {
        $simpleProduct = $this->getSimpleProduct(1);
        $productVariant = $this->getProductVariant(2, 3);

        $this->listener->preRemove($simpleProduct);
        $this->listener->preRemove($productVariant);

        $this->assertAttributeEquals([2], 'productIds', $this->listener);
    }

    public function testPostFlushWithProductIds()
    {
        $productVariant = $this->getProductVariant(1, 2);

        $this->eventDispatcher->expects($this->once())
            ->method('dispatch')
            ->with(
                ReindexationRequestEvent::EVENT_NAME,
                new ReindexationRequestEvent([Product::class], [], [1])
            );

        $this->listener->postUpdate($productVariant);
        $this->listener->postUpdate($productVariant);

        $this->assertAttributeEquals([1, 1], 'productIds', $this->listener);

        $this->listener->postFlush();

        $this->assertAttributeEmpty('productIds', $this->listener);
    }

    public function testPostFlushWithoutProductIds()
    {
        $this->eventDispatcher->expects($this->never())
            ->method('dispatch');

        $this->assertAttributeEmpty('productIds', $this->listener);

        $this->listener->postFlush();
    }

    public function testOnClearAllEntities()
    {
        /** @var EntityManagerInterface $entityManager */
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $event = new OnClearEventArgs($entityManager);

        $productVariant = $this->getProductVariant(1, 2);

        $this->assertAttributeEmpty('productIds', $this->listener);

        $this->listener->postUpdate($productVariant);

        $this->assertAttributeEquals([1], 'productIds', $this->listener);

        $this->listener->onClear($event);

        $this->assertAttributeEmpty('productIds', $this->listener);
    }

    public function testOnClearProductEntity()
    {
        /** @var EntityManagerInterface $entityManager */
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $event = new OnClearEventArgs($entityManager, Product::class);

        $productVariant = $this->getProductVariant(1, 2);

        $this->assertAttributeEmpty('productIds', $this->listener);

        $this->listener->postUpdate($productVariant);

        $this->assertAttributeEquals([1], 'productIds', $this->listener);

        $this->listener->onClear($event);

        $this->assertAttributeEmpty('productIds', $this->listener);
    }

    public function testOnClearNotProductEntity()
    {
        /** @var EntityManagerInterface $entityManager */
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $event = new OnClearEventArgs($entityManager, \DateTime::class);

        $productVariant = $this->getProductVariant(1, 2);

        $this->assertAttributeEmpty('productIds', $this->listener);

        $this->listener->postUpdate($productVariant);

        $this->assertAttributeEquals([1], 'productIds', $this->listener);

        $this->listener->onClear($event);

        $this->assertAttributeEquals([1], 'productIds', $this->listener);
    }

    /**
     * @param int $configurableProductId
     * @param int $productVariantId
     * @return Product
     */
    private function getProductVariant($configurableProductId, $productVariantId)
    {
        /** @var Product $productVariant */
        $productVariant = $this->getEntity(
            Product::class,
            ['id' => $productVariantId, 'type' => Product::TYPE_SIMPLE]
        );
        /** @var Product $configurableProduct */
        $configurableProduct = $this->getEntity(
            Product::class,
            ['id' => $configurableProductId, 'type' => Product::TYPE_CONFIGURABLE]
        );
        /** @var ProductVariantLink $variantLink */
        $variantLink = $this->getEntity(ProductVariantLink::class);
        $productVariant->addParentVariantLink($variantLink);
        $configurableProduct->addVariantLink($variantLink);

        return $productVariant;
    }

    /**
     * @param int $simpleProductId
     * @return Product
     */
    private function getSimpleProduct($simpleProductId)
    {
        /** @var Product $simpleProduct */
        $simpleProduct = $this->getEntity(Product::class, ['id' => $simpleProductId, 'type' => Product::TYPE_SIMPLE]);

        return $simpleProduct;
    }
}
