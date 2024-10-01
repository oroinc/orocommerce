<?php

declare(strict_types=1);

namespace Oro\Bundle\ProductBundle\Tests\Functional\ProductKit\EventListener;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductKitItem;
use Oro\Bundle\ProductBundle\Entity\ProductKitItemProduct;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadDisabledProductKitData;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductKitData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @dbIsolationPerTest
 *
 * @covers \Oro\Bundle\ProductBundle\ProductKit\EventListener\StatusListener
 * @covers \Oro\Bundle\ProductBundle\ProductKit\Resolver\ProductKitStatusResolver
 */
class StatusListenerTest extends WebTestCase
{
    private ManagerRegistry $registry;

    #[\Override]
    protected function setUp(): void
    {
        $this->initClient([], self::generateBasicAuthHeader());
        $this->client->useHashNavigation(true);

        $this->loadFixtures(
            [
                LoadProductKitData::class,
                LoadDisabledProductKitData::class,
            ]
        );
        $this->registry = self::getContainer()->get(ManagerRegistry::class);
        $this->registry->getManager()->clear();
    }

    public function testStatusesCorrectlySetAfterFixtureLoaded(): void
    {
        $kit1 = $this->getReference(LoadProductKitData::PRODUCT_KIT_1);
        $kit2 = $this->getReference(LoadProductKitData::PRODUCT_KIT_2);
        $kit3 = $this->getReference(LoadProductKitData::PRODUCT_KIT_3);
        $kit4 = $this->getReference(LoadDisabledProductKitData::PRODUCT_KIT_4);

        self::assertEquals(
            [
                Product::STATUS_ENABLED,
                Product::STATUS_ENABLED,
                Product::STATUS_ENABLED,
                Product::STATUS_DISABLED,
            ],
            [
                $kit1->getStatus(),
                $kit2->getStatus(),
                $kit3->getStatus(),
                $kit4->getStatus(),
            ]
        );
    }

    /**
     * @dataProvider getChangedToDisabledStatusDataProvider
     */
    public function testStatusIfRelatedProductChanged(
        string $productKitReferenceName,
        string $originalStatus,
        string $expectedStatus
    ): void {
        $productKit = $this->getReference($productKitReferenceName);
        self::assertEquals($originalStatus, $productKit->getStatus());

        $product = $this->getReference('product-4');
        $product->setStatus(Product::STATUS_DISABLED);
        $this->registry->getManager()->flush();
        $this->registry->getManager()->clear();

        self::assertEquals($expectedStatus, $productKit->getStatus());
    }

    /**
     * @dataProvider getRemainsTheSameStatusDataProvider
     */
    public function testStatusIfRelatedProductRemoved(
        string $productKitReferenceName,
        string $originalStatus,
        string $expectedStatus
    ): void {
        $productKit = $this->getReference($productKitReferenceName);
        self::assertEquals($originalStatus, $productKit->getStatus());

        $product = $this->getReference('product-5'); // it disabled in fixtures
        $this->registry->getManager()->remove($product);
        $this->registry->getManager()->flush();
        $this->registry->getManager()->clear();

        self::assertEquals($expectedStatus, $productKit->getStatus());
    }

    /**
     * @dataProvider getRemainsTheSameStatusDataProvider
     */
    public function testStatusIfProductKitItemWithDisabledProductIsRemoved(
        string $productKitReferenceName,
        string $originalStatus,
        string $expectedStatus
    ): void {
        /** @var Product $productKit */
        $productKit = $this->getReference($productKitReferenceName);
        self::assertEquals($originalStatus, $productKit->getStatus());

        $productKitItem = $productKit->getKitItems()->filter(
            static fn (ProductKitItem $kitItem) => $kitItem->getProducts()->filter(
                static fn (Product $product) => $product->getSku() === LoadProductData::PRODUCT_5
            )->count()
        );
        $this->registry->getManager()->remove($productKitItem->first());
        $this->registry->getManager()->flush();
        $this->registry->getManager()->clear();

        self::assertEquals($expectedStatus, $productKit->getStatus());
    }

    /**
     * @dataProvider getRemainsTheSameStatusDataProvider
     */
    public function testStatusIfProductKitItemWithDisabledProductTurnedIntoOptional(
        string $productKitReferenceName,
        string $originalStatus,
        string $expectedStatus
    ): void {
        /** @var Product $productKit */
        $productKit = $this->getReference($productKitReferenceName);
        self::assertEquals($originalStatus, $productKit->getStatus());

        $productKitItem = $productKit->getKitItems()->filter(
            static fn (ProductKitItem $kitItem) => $kitItem->getProducts()->filter(
                static fn (Product $product) => $product->getSku() === LoadProductData::PRODUCT_5
            )->count()
        );
        $productKitItem = $productKitItem->first();
        $productKitItem->setOptional(true);
        $this->registry->getManager()->flush();
        $this->registry->getManager()->clear();

        self::assertEquals($expectedStatus, $productKit->getStatus());
    }

    /**
     * @dataProvider getRemainsTheSameStatusDataProvider
     */
    public function testStatusIfProductKitItemProductWithDisabledProductWasRemoved(
        string $productKitReferenceName,
        string $originalStatus,
        string $expectedStatus
    ): void {
        /** @var Product $productKit */
        $productKit = $this->getReference($productKitReferenceName);
        self::assertEquals($originalStatus, $productKit->getStatus());

        $productKitItem = $productKit->getKitItems()->filter(
            static fn (ProductKitItem $kitItem) => $kitItem->getProducts()->filter(
                static fn (Product $product) => $product->getSku() === LoadProductData::PRODUCT_5
            )->count()
        );
        /** @var ProductKitItem $productKitItem */
        $productKitItem = $productKitItem->first();
        $productKitItemProduct = $productKitItem->getKitItemProducts()->filter(
            fn (ProductKitItemProduct $product) => $product->getProduct()->getSku() === LoadProductData::PRODUCT_5
        );
        $productKitItemProduct = $productKitItemProduct->first();
        $this->registry->getManager()->remove($productKitItemProduct);
        $this->registry->getManager()->flush();
        $this->registry->getManager()->clear();

        self::assertEquals($expectedStatus, $productKit->getStatus());
    }

    /**
     * @dataProvider getRemainsTheSameStatusDataProvider
     */
    public function testStatusIfProductKitItemProductWithDisabledProductWasChanged(
        string $productKitReferenceName,
        string $originalStatus,
        string $expectedStatus
    ): void {
        /** @var Product $productKit */
        $productKit = $this->getReference($productKitReferenceName);
        self::assertEquals($originalStatus, $productKit->getStatus());

        $productKitItem = $productKit->getKitItems()->filter(
            static fn (ProductKitItem $kitItem) => $kitItem->getProducts()->filter(
                static fn (Product $product) => $product->getSku() === LoadProductData::PRODUCT_5
            )->count()
        );
        /** @var ProductKitItem $productKitItem */
        $productKitItem = $productKitItem->first();
        $productKitItemProduct = $productKitItem->getKitItemProducts()->filter(
            fn (ProductKitItemProduct $product) => $product->getProduct()->getSku() === LoadProductData::PRODUCT_5
        );
        $productKitItemProduct = $productKitItemProduct->get(1);
        $productKitItemProduct->setProduct($this->getReference('product-1'));
        $this->registry->getManager()->flush();
        $this->registry->getManager()->clear();

        self::assertEquals($expectedStatus, $productKit->getStatus());
    }

    public function getChangedToDisabledStatusDataProvider(): array
    {
        return [
            'kit 3' => [
                'productKitReferenceName' => LoadProductKitData::PRODUCT_KIT_3,
                'originalStatus' => Product::STATUS_ENABLED,
                'expectedStatus' => Product::STATUS_DISABLED,
            ],
            'kit 4' => [
                'productKitReferenceName' => LoadDisabledProductKitData::PRODUCT_KIT_4,
                'originalStatus' => Product::STATUS_DISABLED,
                'expectedStatus' => Product::STATUS_DISABLED,
            ],
        ];
    }

    public function getRemainsTheSameStatusDataProvider(): array
    {
        return [
            'kit 3' => [
                'productKitReferenceName' => LoadProductKitData::PRODUCT_KIT_3,
                'originalStatus' => Product::STATUS_ENABLED,
                'expectedStatus' => Product::STATUS_ENABLED,
            ],
            'kit 4' => [
                'productKitReferenceName' => LoadDisabledProductKitData::PRODUCT_KIT_4,
                'originalStatus' => Product::STATUS_DISABLED,
                'expectedStatus' => Product::STATUS_DISABLED,
            ],
        ];
    }
}
