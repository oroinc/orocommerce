<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\EventListener;

use Doctrine\ORM\EntityManager;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Oro\Bundle\PricingBundle\Entity\PriceRule;
use Oro\Bundle\PricingBundle\Entity\ProductPrice;
use Oro\Bundle\PricingBundle\Entity\Repository\ProductPriceRepository;
use Oro\Bundle\PricingBundle\EventListener\ProductDuplicateListener;
use Oro\Bundle\PricingBundle\Manager\PriceManager;
use Oro\Bundle\PricingBundle\Sharding\ShardManager;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Event\ProductDuplicateAfterEvent;

class ProductDuplicateListenerTest extends \PHPUnit\Framework\TestCase
{
    /** @var ShardManager|\PHPUnit\Framework\MockObject\MockObject */
    private $shardManager;

    /** @var string */
    private $productPriceClass = 'stdClass';

    /** @var ProductPriceRepository|\PHPUnit\Framework\MockObject\MockObject */
    private $productPriceRepository;

    /** @var ObjectManager|\PHPUnit\Framework\MockObject\MockObject */
    private $objectManager;

    /** @var PriceManager|\PHPUnit\Framework\MockObject\MockObject */
    private $priceManager;

    /** @var FeatureChecker|\PHPUnit\Framework\MockObject\MockObject */
    private $featureChecker;

    /** @var Product */
    private $product;

    /** @var Product */
    private $sourceProduct;

    /** @var ProductDuplicateListener */
    private $listener;

    protected function setUp(): void
    {
        $this->product = new Product();
        $this->sourceProduct = new Product();

        $this->featureChecker = $this->createMock(FeatureChecker::class);
        $this->shardManager = $this->createMock(ShardManager::class);
        $this->productPriceRepository = $this->createMock(ProductPriceRepository::class);
        $this->objectManager = $this->createMock(EntityManager::class);
        $this->priceManager = $this->createMock(PriceManager::class);

        $doctrineHelper = $this->createMock(DoctrineHelper::class);
        $doctrineHelper->expects($this->any())
            ->method('getEntityRepository')
            ->with($this->productPriceClass)
            ->willReturn($this->productPriceRepository);
        $doctrineHelper->expects($this->any())
            ->method('getEntityManager')
            ->with($this->productPriceClass)
            ->willReturn($this->objectManager);

        $this->listener = new ProductDuplicateListener();
        $this->listener->setProductPriceClass($this->productPriceClass);
        $this->listener->setDoctrineHelper($doctrineHelper);
        $this->listener->setShardManager($this->shardManager);
        $this->listener->setPriceManager($this->priceManager);
    }

    public function testOnDuplicateAfterFeatureDisabled()
    {
        $this->featureChecker->expects($this->once())
            ->method('isFeatureEnabled')
            ->with('feature1', null)
            ->willReturn(false);

        $this->listener->setFeatureChecker($this->featureChecker);
        $this->listener->addFeature('feature1');

        $event = $this->createMock(ProductDuplicateAfterEvent::class);
        $event->expects($this->never())
            ->method('getProduct');

        $this->listener->onDuplicateAfter($event);
    }

    public function testOnDuplicateAfter()
    {
        $price1 = new ProductPrice();
        $price1->setId('price1Id');
        $priceRule1 = new PriceRule();
        $price1->setPriceRule($priceRule1);
        $this->productPriceRepository->expects($this->once())
            ->method('getPricesByProduct')
            ->with($this->shardManager, $this->sourceProduct)
            ->willReturn([$price1, new ProductPrice(), new ProductPrice()]);

        $this->priceManager->expects($this->exactly(3))
            ->method('persist')
        ->withConsecutive(
            [(new ProductPrice())->setProduct($this->product)->setPriceRule($priceRule1)],
            [(new ProductPrice())->setProduct($this->product)],
            [(new ProductPrice())->setProduct($this->product)]
        );
        $this->priceManager->expects($this->once())
            ->method('flush');

        $event = new ProductDuplicateAfterEvent($this->product, $this->sourceProduct);

        $this->featureChecker->expects($this->once())
            ->method('isFeatureEnabled')
            ->with('feature1', null)
            ->willReturn(true);

        $this->listener->setFeatureChecker($this->featureChecker);
        $this->listener->addFeature('feature1');
        $this->listener->onDuplicateAfter($event);
    }

    public function testOnDuplicateAfterSourceProductWithoutPrices()
    {
        $this->productPriceRepository->expects($this->once())
            ->method('getPricesByProduct')
            ->with($this->shardManager, $this->sourceProduct)
            ->willReturn([]);

        $this->priceManager->expects($this->never())
            ->method('persist');
        $this->priceManager->expects($this->once())
            ->method('flush');

        $event = new ProductDuplicateAfterEvent($this->product, $this->sourceProduct);

        $this->listener->onDuplicateAfter($event);
    }
}
