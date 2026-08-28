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
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\EventDispatcher\EventDispatcherInterface;

/**
 * Checks that product reindexation is requested for single product price changes
 * and skipped for prices written by a versioned mass operation.
 */
class ProductPriceFlatEntityListenerTest extends TestCase
{
    use EntityTrait;

    private ProductPriceFlatEntityListener $productPriceFlatEntityListener;

    private EventDispatcherInterface&MockObject $eventDispatcher;
    private FeatureChecker&MockObject $featureChecker;

    #[\Override]
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

    /**
     * @dataProvider versionedPriceDataProvider
     */
    public function testOnSaveWithVersionedPrice(array $changeSet, ?int $version, bool $expectsReindex): void
    {
        $this->assertListenerStatus();
        $priceList = $this->getEntity(PriceList::class, ['id' => 1]);
        $product = $this->getEntity(Product::class, ['id' => 1]);
        $productPrice = $this->getProductPriceEntity($product, $priceList, $version);
        $saveAfterEvent = $this->getSaveAfterEvent($productPrice, $changeSet);

        if ($expectsReindex) {
            $event = new ReindexationRequestEvent([Product::class], [], [$product->getId()], true, ['pricing']);
            $this->eventDispatcher
                ->expects($this->once())
                ->method('dispatch')
                ->with($event, ReindexationRequestEvent::EVENT_NAME);
        } else {
            $this->eventDispatcher
                ->expects($this->never())
                ->method('dispatch');
        }

        $this->productPriceFlatEntityListener->onSave($saveAfterEvent);
    }

    public function versionedPriceDataProvider(): array
    {
        return [
            // Versioned mass operations are reindexed in bulk by ResolveVersionedFlatPriceTopic.
            'batch API price creation' => [
                'changeSet' => ['id' => [null, 1], 'value' => [null, 10], 'version' => [null, 42]],
                'version' => 42,
                'expectsReindex' => false,
            ],
            'batch API price update' => [
                'changeSet' => ['value' => [10, 20], 'version' => [41, 42]],
                'version' => 42,
                'expectsReindex' => false,
            ],
            'import price creation' => [
                'changeSet' => [],
                'version' => 42,
                'expectsReindex' => false,
            ],
            // Single price changes are not covered by a bulk job and must be reindexed per price.
            'price creation via single resource API' => [
                'changeSet' => ['id' => [null, 1], 'value' => [null, 10], 'version' => [null, null]],
                'version' => null,
                'expectsReindex' => true,
            ],
            'manual update of a previously versioned price' => [
                'changeSet' => ['value' => [10, 20]],
                'version' => 42,
                'expectsReindex' => true,
            ],
            'manual price creation' => [
                'changeSet' => [],
                'version' => null,
                'expectsReindex' => true,
            ],
        ];
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

    public function testOnRemoveWithVersionedPrice(): void
    {
        $this->assertListenerStatus();
        $priceList = $this->getEntity(PriceList::class, ['id' => 1]);
        $product = $this->getEntity(Product::class, ['id' => 1]);
        $productPrice = $this->getProductPriceEntity($product, $priceList, 42);
        $removeEvent = $this->getProductPriceRemoveEvent($productPrice);

        // Removal is not covered by the bulk job, so a versioned price must still be reindexed.
        $event = new ReindexationRequestEvent([Product::class], [], [$product->getId()], true, ['pricing']);
        $this->eventDispatcher
            ->expects($this->once())
            ->method('dispatch')
            ->with($event, ReindexationRequestEvent::EVENT_NAME);

        $this->productPriceFlatEntityListener->onRemove($removeEvent);
    }

    /**
     * @dataProvider featureAndListenerDataProvider
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

    private function getSaveAfterEvent(ProductPrice $productPrice, array $changeSet = []): ProductPriceSaveAfterEvent
    {
        $entityManager = $this->createMock(EntityManager::class);
        $args = new PreUpdateEventArgs($productPrice, $entityManager, $changeSet);

        return new ProductPriceSaveAfterEvent($args);
    }

    private function getProductPriceEntity(
        Product $product,
        PriceList $priceList,
        ?int $version = null
    ): ProductPrice {
        return $this->getEntity(
            ProductPrice::class,
            ['id' => 1, 'priceList' => $priceList, 'product' => $product, 'version' => $version]
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
