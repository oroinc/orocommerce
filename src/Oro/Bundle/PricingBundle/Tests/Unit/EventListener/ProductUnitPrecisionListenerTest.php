<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\EventListener;

use Doctrine\Persistence\Event\LifecycleEventArgs;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Oro\Bundle\PricingBundle\Entity\Repository\ProductPriceRepository;
use Oro\Bundle\PricingBundle\Event\ProductPricesRemoveAfter;
use Oro\Bundle\PricingBundle\Event\ProductPricesRemoveBefore;
use Oro\Bundle\PricingBundle\EventListener\ProductUnitPrecisionListener;
use Oro\Bundle\PricingBundle\Sharding\ShardManager;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Entity\ProductUnitPrecision;
use Oro\Component\Testing\Unit\EntityTrait;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class ProductUnitPrecisionListenerTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /** @var \PHPUnit\Framework\MockObject\MockObject|EventDispatcherInterface */
    private $eventDispatcher;

    /** @var \PHPUnit\Framework\MockObject\MockObject|DoctrineHelper */
    private $doctrineHelper;

    /** @var ShardManager|\PHPUnit\Framework\MockObject\MockObject */
    private $shardManager;

    /** @var ProductUnitPrecisionListener */
    private $listener;

    /** @var FeatureChecker|\PHPUnit\Framework\MockObject\MockObject */
    private $featureChecker;

    /** @var string */
    private $productPriceClass;

    protected function setUp(): void
    {
        $this->productPriceClass = 'stdClass';
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->shardManager = $this->createMock(ShardManager::class);
        $this->featureChecker = $this->createMock(FeatureChecker::class);
        $this->listener = new ProductUnitPrecisionListener(
            $this->productPriceClass,
            $this->eventDispatcher,
            $this->shardManager,
            $this->doctrineHelper
        );
    }

    public function testOnBeforeFlushFeatureDisabled()
    {
        $this->featureChecker->expects($this->once())
            ->method('isFeatureEnabled')
            ->with('feature1', null)
            ->willReturn(false);

        $this->listener->setFeatureChecker($this->featureChecker);
        $this->listener->addFeature('feature1');

        $event = $this->createMock(LifecycleEventArgs::class);
        $entity = $this->createMock(ProductUnitPrecision::class);
        $entity->expects($this->never())
            ->method('getProduct');

        $this->listener->postRemove($entity, $event);
    }

    public function testPostRemoveWithExistingProduct()
    {
        $entity = new ProductUnitPrecision();
        /** @var Product $product */
        $product = $this->getEntity(Product::class, ['id' => 1]);
        $unit = new ProductUnit();
        $entity->setProduct($product)
            ->setUnit($unit);

        $event = $this->createMock(LifecycleEventArgs::class);

        $repository =$this->createMock(ProductPriceRepository::class);
        $repository->expects($this->once())
            ->method('deleteByProductUnit')
            ->with($this->shardManager, $product, $unit);

        $this->doctrineHelper->expects($this->once())
            ->method('getEntityRepository')
            ->with($this->productPriceClass)
            ->willReturn($repository);

        $this->eventDispatcher->expects($this->exactly(2))
            ->method('dispatch');
        $this->eventDispatcher->expects($this->exactly(2))
            ->method('dispatch')
            ->withConsecutive(
                [$this->isInstanceOf(ProductPricesRemoveBefore::class), ProductPricesRemoveBefore::NAME],
                [$this->isInstanceOf(ProductPricesRemoveAfter::class), ProductPricesRemoveAfter::NAME]
            );

        $this->featureChecker->expects($this->once())
            ->method('isFeatureEnabled')
            ->with('feature1', null)
            ->willReturn(true);

        $this->listener->setFeatureChecker($this->featureChecker);
        $this->listener->addFeature('feature1');
        $this->listener->postRemove($entity, $event);
    }

    public function testPostRemoveWithDeletedProduct()
    {
        $entity = new ProductUnitPrecision();
        $product = new Product();
        $unit = new ProductUnit();
        $entity->setProduct($product)
            ->setUnit($unit);

        $event = $this->createMock(LifecycleEventArgs::class);

        $this->doctrineHelper->expects($this->never())
            ->method('getEntityRepository');

        $this->eventDispatcher->expects($this->never())
            ->method('dispatch');

        $this->listener->postRemove($entity, $event);
    }
}
