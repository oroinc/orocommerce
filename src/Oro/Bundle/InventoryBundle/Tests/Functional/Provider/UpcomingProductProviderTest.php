<?php

namespace Oro\Bundle\InventoryBundle\Tests\Functional\Provider;

use Oro\Bundle\ConfigBundle\Tests\Functional\Traits\ConfigManagerAwareTestTrait;
use Oro\Bundle\InventoryBundle\Provider\UpcomingProductProvider;
use Oro\Bundle\InventoryBundle\Tests\Functional\DataFixtures\LoadProductUpcomingData;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class UpcomingProductProviderTest extends WebTestCase
{
    use ConfigManagerAwareTestTrait;

    protected function setUp(): void
    {
        $this->initClient();
        $this->loadFixtures([LoadProductUpcomingData::class]);
    }

    public function testIsUpcomingWhenHideLabelsDisabled(): void
    {
        $this->setHideLabelsPastAvailabilityDateOption(false);
        $provider = $this->getUpcomingProductProvider();

        $this->assertTrue($provider->isUpcoming($this->getReference(LoadProductData::PRODUCT_1)));
        $this->assertFalse($provider->isUpcoming($this->getReference(LoadProductData::PRODUCT_2)));
        $this->assertTrue($provider->isUpcoming($this->getReference(LoadProductData::PRODUCT_3)));
        $this->assertTrue($provider->isUpcoming($this->getReference(LoadProductData::PRODUCT_4)));
        $this->assertTrue($provider->isUpcoming($this->getReference(LoadProductData::PRODUCT_5)));
    }

    public function testIsUpcomingWhenHideLabelsEnabled(): void
    {
        $this->setHideLabelsPastAvailabilityDateOption(true);
        $provider = $this->getUpcomingProductProvider();

        $this->assertTrue($provider->isUpcoming($this->getReference(LoadProductData::PRODUCT_1)));
        $this->assertFalse($provider->isUpcoming($this->getReference(LoadProductData::PRODUCT_2)));

        // Old product's availability date
        $this->assertFalse($provider->isUpcoming($this->getReference(LoadProductData::PRODUCT_3)));

        // Old category's fallback availability date
        $this->assertFalse($provider->isUpcoming($this->getReference(LoadProductData::PRODUCT_4)));

        $this->assertTrue($provider->isUpcoming($this->getReference(LoadProductData::PRODUCT_5)));
    }

    public function testGetAvailabilityDateWhenHideLabelsDisabled(): void
    {
        $this->setHideLabelsPastAvailabilityDateOption(false);
        $provider = $this->getUpcomingProductProvider();

        $this->assertEquals(new \DateTime('2070-10-10'), $provider->getAvailabilityDate(
            $this->getReference(LoadProductData::PRODUCT_1)
        ));

        // Old product's availability date
        $this->assertNull($provider->getAvailabilityDate($this->getReference(LoadProductData::PRODUCT_3)));

        // Old category's fallback availability date
        $this->assertNull($provider->getAvailabilityDate($this->getReference(LoadProductData::PRODUCT_4)));

        $this->assertEquals(new \DateTime('2050-10-10'), $provider->getAvailabilityDate(
            $this->getReference(LoadProductData::PRODUCT_5)
        ));
    }

    public function testGetAvailabilityDateWhenHideLabelsEnabledAndDateIsInTheFuture(): void
    {
        $this->setHideLabelsPastAvailabilityDateOption(true);
        $provider = $this->getUpcomingProductProvider();

        $this->assertEquals(new \DateTime('2070-10-10'), $provider->getAvailabilityDate(
            $this->getReference(LoadProductData::PRODUCT_1)
        ));

        $this->assertEquals(new \DateTime('2050-10-10'), $provider->getAvailabilityDate(
            $this->getReference(LoadProductData::PRODUCT_5)
        ));
    }

    public function testGetAvailabilityDateWhenHideLabelsEnabledAndProductDateIsInThePast(): void
    {
        $this->setHideLabelsPastAvailabilityDateOption(true);
        $provider = $this->getUpcomingProductProvider();

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('You cant get Availability Date for product, which is not upcoming');
        $this->assertNull($provider->getAvailabilityDate($this->getReference(LoadProductData::PRODUCT_3)));
    }

    public function testGetAvailabilityDateWhenHideLabelsEnabledAndCategoryFallbackDateIsInThePast(): void
    {
        $this->setHideLabelsPastAvailabilityDateOption(true);
        $provider = $this->getUpcomingProductProvider();

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('You cant get Availability Date for product, which is not upcoming');
        $this->assertNull($provider->getAvailabilityDate($this->getReference(LoadProductData::PRODUCT_4)));
    }

    /**
     * @dataProvider hideLabelsProvider
     */
    public function testGetLatestAvailabilityDateWhenDatesAreInTheFuture(bool $enabled): void
    {
        $this->setHideLabelsPastAvailabilityDateOption($enabled);

        $products = [
            $this->getReference(LoadProductData::PRODUCT_1),
            $this->getReference(LoadProductData::PRODUCT_2),
            $this->getReference(LoadProductData::PRODUCT_5),
        ];

        $this->assertEquals(
            new \DateTime('2070-10-10'),
            $this->getUpcomingProductProvider()->getLatestAvailabilityDate($products)
        );
    }

    /**
     * @dataProvider hideLabelsProvider
     */
    public function testGetLatestAvailabilityDatesWhenDatesAreInThePast(bool $enabled): void
    {
        $this->setHideLabelsPastAvailabilityDateOption($enabled);

        $products = [
            $this->getReference(LoadProductData::PRODUCT_3),
            $this->getReference(LoadProductData::PRODUCT_4),
        ];

        $this->assertNull($this->getUpcomingProductProvider()->getLatestAvailabilityDate($products));
    }

    /**
     * @dataProvider hideLabelsProvider
     */
    public function testGetAvailabilityDateForNonUpcomingProduct(bool $enabled): void
    {
        $this->expectException(\LogicException::class);

        $this->setHideLabelsPastAvailabilityDateOption($enabled);
        $this->getUpcomingProductProvider()->getAvailabilityDate($this->getReference(LoadProductData::PRODUCT_2));
    }

    /**
     * @dataProvider hideLabelsProvider
     */
    public function testGetAvailabilityDateForNonUpcomingProduct2(bool $enabled): void
    {
        $this->expectException(\LogicException::class);

        $this->setHideLabelsPastAvailabilityDateOption($enabled);
        $this->getUpcomingProductProvider()->getAvailabilityDate($this->getReference(LoadProductData::PRODUCT_6));
    }

    public function hideLabelsProvider(): array
    {
        return [
            'hide labels enabled' =>  [
                'enabled' => true
            ],
            'hide labels disabled' =>  [
                'enabled' => false
            ],
        ];
    }

    public function getUpcomingProductProvider(): UpcomingProductProvider
    {
        return self::getContainer()->get('oro_inventory.provider.upcoming_product_provider');
    }

    private function setHideLabelsPastAvailabilityDateOption(bool $enabled): void
    {
        $configManager = self::getConfigManager();
        $configManager->set('oro_inventory.hide_labels_past_availability_date', $enabled);
        $configManager->flush();
    }
}
