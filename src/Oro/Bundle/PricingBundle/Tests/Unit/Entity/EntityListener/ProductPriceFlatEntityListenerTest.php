<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Entity\EntityListener;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Oro\Bundle\PricingBundle\Entity\EntityListener\ProductPriceFlatEntityListener;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Entity\ProductPrice;
use Oro\Bundle\PricingBundle\Event\ProductPriceRemove;
use Oro\Bundle\PricingBundle\Event\ProductPriceSaveAfterEvent;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\WebsiteSearchBundle\Event\ReindexationRequestEvent;
use Oro\Component\Testing\Unit\EntityTrait;
use Psr\EventDispatcher\EventDispatcherInterface;

class ProductPriceFlatEntityListenerTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /** @var ProductPriceFlatEntityListener */
    private $productPriceFlatEntityListener;

    /** @var EventDispatcherInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $eventDispatcher;

    /** @var FeatureChecker */
    private $featureChecker;

    protected function setUp(): void
    {
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->featureChecker = $this->createMock(FeatureChecker::class);
        $this->productPriceFlatEntityListener = new ProductPriceFlatEntityListener($this->eventDispatcher);
        $this->assertFeatureChecker();
    }

    public function testOnSaveWithFeatureEnabled(): void
    {
        $this->assertListenerStatus();
        $priceList = $this->getEntity(PriceList::class, ['id' => 1]);
        $product = $this->getEntity(Product::class, ['id' => 1]);
        $productPrice = $this->getProductPriceEntity($product, $priceList);
        $saveAfterEvent = $this->getSaveAfterEvent($productPrice);

        $event = new ReindexationRequestEvent([Product::class], [], [$product->getId()], true, ['pricing']);
        $this->eventDispatcher
            ->expects($this->once())
            ->method('dispatch')
            ->with($event, ReindexationRequestEvent::EVENT_NAME);

        $this->productPriceFlatEntityListener->onSave($saveAfterEvent);
    }

    /**
     * @dataProvider featureAndListenerDataProvider
     *
     * @param string $feature
     * @param bool $enabled
     *
     * @return void
     */
    public function testOnSaveWithFeatureAndListenerDisabled(string $feature, bool $enabled): void
    {
        $this->assertListenerStatus($enabled, $feature);
        $priceList = $this->getEntity(PriceList::class, ['id' => 1]);
        $product = $this->getEntity(Product::class, ['id' => 1]);
        $productPrice = $this->getProductPriceEntity($product, $priceList);
        $saveAfterEvent = $this->getSaveAfterEvent($productPrice);

        $this->eventDispatcher
            ->expects($this->never())
            ->method('dispatch');

        $this->productPriceFlatEntityListener->onSave($saveAfterEvent);
    }

    public function testOnRemoveWithFeatureEnabled(): void
    {
        $this->assertListenerStatus();
        $priceList = $this->getEntity(PriceList::class, ['id' => 1]);
        $product = $this->getEntity(Product::class, ['id' => 1]);
        $productPrice = $this->getProductPriceEntity($product, $priceList);
        $removeEvent = $this->getProductPriceRemoveEvent($productPrice);

        $event = new ReindexationRequestEvent([Product::class], [], [$product->getId()], true, ['pricing']);
        $this->eventDispatcher
            ->expects($this->once())
            ->method('dispatch')
            ->with($event, ReindexationRequestEvent::EVENT_NAME);

        $this->productPriceFlatEntityListener->onRemove($removeEvent);
    }

    /**
     * @dataProvider featureAndListenerDataProvider
     *
     * @param string $feature
     * @param bool $enabled
     *
     * @return void
     */
    public function testOnRemoveWithFeatureAndListenerDisabled(string $feature, bool $enabled): void
    {
        $this->assertListenerStatus($enabled, $feature);
        $priceList = $this->getEntity(PriceList::class, ['id' => 1]);
        $product = $this->getEntity(Product::class, ['id' => 1]);
        $productPrice = $this->getProductPriceEntity($product, $priceList);
        $removeEvent = $this->getProductPriceRemoveEvent($productPrice);

        $this->eventDispatcher
            ->expects($this->never())
            ->method('dispatch');

        $this->productPriceFlatEntityListener->onRemove($removeEvent);
    }

    public function featureAndListenerDataProvider(): array
    {
        return [
            'Disabled' => ['any_feature', false],
            'Listener enabled' => ['any_feature', true],
            'Feature enabled' => ['oro_price_lists_flat', false]
        ];
    }

    private function getSaveAfterEvent(ProductPrice $productPrice): ProductPriceSaveAfterEvent
    {
        $changeSet = [];
        $entityManager = $this->createMock(EntityManager::class);
        $args = new PreUpdateEventArgs($productPrice, $entityManager, $changeSet);

        return new ProductPriceSaveAfterEvent($args);
    }

    private function getProductPriceEntity(Product $product, PriceList $priceList): ProductPrice
    {
        return $this->getEntity(
            ProductPrice::class,
            ['id' => 1, 'priceList' => $priceList, 'product' => $product]
        );
    }

    private function getProductPriceRemoveEvent(ProductPrice $productPrice): ProductPriceRemove
    {
        return new ProductPriceRemove($productPrice);
    }

    private function assertFeatureChecker(): void
    {
        $this->featureChecker
            ->expects($this->any())
            ->method('isFeatureEnabled')
            ->willReturnCallback(fn (string $feature) => $feature == 'oro_price_lists_flat');

        $this->productPriceFlatEntityListener->setFeatureChecker($this->featureChecker);
    }

    private function assertListenerStatus(bool $enabled = true, string $feature = 'oro_price_lists_flat'): void
    {
        $this->productPriceFlatEntityListener->setEnabled($enabled);
        $this->productPriceFlatEntityListener->addFeature($feature);
    }
}
