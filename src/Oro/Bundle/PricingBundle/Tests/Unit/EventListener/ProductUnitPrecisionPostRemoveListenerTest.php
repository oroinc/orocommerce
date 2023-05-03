<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\EventListener;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Oro\Bundle\PricingBundle\Entity\PriceAttributeProductPrice;
use Oro\Bundle\PricingBundle\Entity\Repository\PriceAttributeProductPriceRepository;
use Oro\Bundle\PricingBundle\EventListener\ProductUnitPrecisionPostRemoveListener;
use Oro\Bundle\PricingBundle\Sharding\ShardManager;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Entity\ProductUnitPrecision;
use Oro\Component\Testing\ReflectionUtil;

class ProductUnitPrecisionPostRemoveListenerTest extends \PHPUnit\Framework\TestCase
{
    /** @var ShardManager|\PHPUnit\Framework\MockObject\MockObject */
    private $shardManager;

    /** @var FeatureChecker|\PHPUnit\Framework\MockObject\MockObject */
    private $featureChecker;

    /** @var ProductUnitPrecisionPostRemoveListener */
    private $listener;

    protected function setUp(): void
    {
        $this->shardManager = $this->createMock(ShardManager::class);
        $this->featureChecker = $this->createMock(FeatureChecker::class);

        $this->listener = new ProductUnitPrecisionPostRemoveListener($this->shardManager);
        $this->listener->setFeatureChecker($this->featureChecker);
        $this->listener->addFeature('oro_price_lists_combined');
    }

    private function getPostRemoveEventArgs(
        ProductUnitPrecision $entity,
        EntityManagerInterface $em
    ): LifecycleEventArgs {
        return new LifecycleEventArgs($entity, $em);
    }

    public function testPostRemoveFeatureDisabled()
    {
        $entity = new ProductUnitPrecision();

        $em = $this->createMock(EntityManager::class);

        $this->featureChecker->expects($this->any())
            ->method('isFeatureEnabled')
            ->with('oro_price_lists_combined')
            ->willReturn(false);

        $this->listener->postRemove($entity, $this->getPostRemoveEventArgs($entity, $em));
    }

    public function testPostRemoveForNewProduct()
    {
        $product = new Product();
        $unit = new ProductUnit();
        $entity = new ProductUnitPrecision();
        $entity
            ->setProduct($product)
            ->setUnit($unit);

        $em = $this->createMock(EntityManager::class);
        $em->expects($this->never())
            ->method('getRepository');

        $this->featureChecker->expects($this->any())
            ->method('isFeatureEnabled')
            ->with('oro_price_lists_combined')
            ->willReturn(true);

        $this->listener->postRemove($entity, $this->getPostRemoveEventArgs($entity, $em));
    }

    public function testPostRemoveForExistingProduct()
    {
        $product = new Product();
        ReflectionUtil::setId($product, 1);
        $unit = new ProductUnit();
        $entity = new ProductUnitPrecision();
        $entity
            ->setProduct($product)
            ->setUnit($unit);

        $em = $this->createMock(EntityManager::class);
        $repository = $this->createMock(PriceAttributeProductPriceRepository::class);
        $em->expects($this->once())
            ->method('getRepository')
            ->with(PriceAttributeProductPrice::class)
            ->willReturn($repository);
        $repository->expects($this->once())
            ->method('deleteByProductUnit')
            ->with($this->shardManager, $product, $unit);

        $this->featureChecker->expects($this->any())
            ->method('isFeatureEnabled')
            ->with('oro_price_lists_combined')
            ->willReturn(true);

        $this->listener->postRemove($entity, $this->getPostRemoveEventArgs($entity, $em));
    }
}
