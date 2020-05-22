<?php

namespace Oro\Bundle\CatalogBundle\Tests\Unit\EventListener;

use Doctrine\ORM\AbstractQuery;
use Oro\Bundle\CatalogBundle\EventListener\ProductNormalizerEventListener;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\ImportExport\Event\ProductNormalizerEvent;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;

class ProductNormalizerEventListenerTest extends AbstractProductImportEventListenerTest
{
    const CATEGORY_CLASS = 'Oro\Bundle\CatalogBundle\Entity\Category';

    /**
     * @var AclHelper
     */
    private $aclHelper;

    /**
     * @var ProductNormalizerEventListener
     */
    protected $listener;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        parent::setUp();

        /** @var AclHelper $aclHelper */
        $this->aclHelper = $this->createMock(AclHelper::class);
        $this->listener = new ProductNormalizerEventListener($this->registry, $this->aclHelper, self::CATEGORY_CLASS);
    }

    protected function tearDown(): void
    {
        unset($this->listener);
        parent::tearDown();
    }

    public function testOnNormalize()
    {
        $product = $this->getPreparedProduct();
        $query = $this->createMock(AbstractQuery::class);
        $query
            ->expects($this->once())
            ->method('getOneOrNullResult')
            ->willReturn($this->categoriesByProduct[$product->getSku()]);

        $this->aclHelper
            ->expects($this->once())
            ->method('apply')
            ->willReturn($query);

        $event = new ProductNormalizerEvent($product, []);
        $this->listener->onNormalize($event);
        $this->assertEquals($product, $event->getProduct());

        $plainData = $event->getPlainData();
        $this->assertArrayHasKey(ProductNormalizerEventListener::CATEGORY_KEY, $plainData);
        $this->assertEquals(
            $this->categoriesByProduct[$product->getSku()]->getDefaultTitle(),
            $plainData[ProductNormalizerEventListener::CATEGORY_KEY]
        );

        // Should be used cache
        $this->listener->onNormalize($event);
        $this->assertEquals(1, $this->findByProductSkuCalls[$product->getSku()]);
    }

    public function testOnClear()
    {
        $product = $this->getPreparedProduct();
        $query = $this->createMock(AbstractQuery::class);
        $query
            ->expects($this->exactly(2))
            ->method('getOneOrNullResult')
            ->willReturn($this->categoriesByProduct[$product->getSku()]);

        $this->aclHelper
            ->expects($this->exactly(2))
            ->method('apply')
            ->willReturn($query);

        $event = new ProductNormalizerEvent($product, []);
        $this->listener->onNormalize($event);
        $this->listener->onClear();
        $this->listener->onNormalize($event);
        $this->assertEquals(2, $this->findByProductSkuCalls[$product->getSku()]);
    }

    public function testOnNormalizeWithoutCategory()
    {
        $product = (new Product())
            ->setSku('test');

        $query = $this->createMock(AbstractQuery::class);
        $query
            ->expects($this->once())
            ->method('getOneOrNullResult')
            ->willReturn(null);

        $this->aclHelper
            ->expects($this->once())
            ->method('apply')
            ->willReturn($query);

        $event = new ProductNormalizerEvent($product, []);
        $this->listener->onNormalize($event);
        $this->assertArrayNotHasKey(ProductNormalizerEventListener::CATEGORY_KEY, $event->getPlainData());
    }
}
