<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Storage;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Oro\Bundle\PricingBundle\Model\DTO\ProductPriceDTO;
use Oro\Bundle\PricingBundle\Model\ProductPriceScopeCriteria;
use Oro\Bundle\PricingBundle\Model\ProductPriceScopeCriteriaInterface;
use Oro\Bundle\PricingBundle\Storage\CompositeProductPriceStorage;
use Oro\Bundle\PricingBundle\Storage\ProductPriceStorageInterface;
use Oro\Bundle\ProductBundle\Entity\MeasureUnitInterface;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Component\Testing\Unit\EntityTrait;
use PHPUnit\Framework\MockObject\MockObject;

class CompositeProductPriceStorageTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /**
     * @var ProductPriceStorageInterface|MockObject
     */
    private $flatPricingStorage;

    /**
     * @var ProductPriceStorageInterface|MockObject
     */
    private $combinedPricingStorage;

    /**
     * @var FeatureChecker|MockObject
     */
    private $featureChecker;

    /**
     * @var CompositeProductPriceStorage
     */
    private $storage;

    protected function setUp(): void
    {
        $this->flatPricingStorage = $this->createMock(ProductPriceStorageInterface::class);
        $this->combinedPricingStorage = $this->createMock(ProductPriceStorageInterface::class);
        $this->featureChecker = $this->createMock(FeatureChecker::class);

        $this->storage = new CompositeProductPriceStorage(
            $this->flatPricingStorage,
            $this->combinedPricingStorage,
            $this->featureChecker
        );
    }

    public function testGetPricesCombined()
    {
        $customer = $this->getEntity(Customer::class, ['id' => 1]);
        $website = $this->getEntity(Website::class, ['id' => 2]);
        $product = $this->getEntity(Product::class, ['id' => 4]);
        $unit = $this->createMock(MeasureUnitInterface::class);
        $scopeCriteria = $this->getScopeCriteria($website, $customer);
        $prices = [[new ProductPriceDTO($product, Price::create(10, 'USD'), 1, $unit)]];

        $this->featureChecker->expects($this->any())
            ->method('isFeatureEnabled')
            ->willReturnMap([
                ['oro_price_lists_combined', null, true],
                ['oro_price_lists_flat', null, false],
            ]);

        $this->flatPricingStorage->expects($this->never())
            ->method('getPrices');
        $this->combinedPricingStorage->expects($this->once())
            ->method('getPrices')
            ->willReturn($prices);

        $this->assertSame($prices, $this->storage->getPrices($scopeCriteria, [$product]));
    }

    public function testGetPricesFlat()
    {
        $customer = $this->getEntity(Customer::class, ['id' => 1]);
        $website = $this->getEntity(Website::class, ['id' => 2]);
        $product = $this->getEntity(Product::class, ['id' => 4]);
        $unit = $this->createMock(MeasureUnitInterface::class);
        $scopeCriteria = $this->getScopeCriteria($website, $customer);
        $prices = [[new ProductPriceDTO($product, Price::create(10, 'USD'), 1, $unit)]];

        $this->featureChecker->expects($this->any())
            ->method('isFeatureEnabled')
            ->willReturnMap([
                ['oro_price_lists_combined', null, false],
                ['oro_price_lists_flat', null, true],
            ]);

        $this->combinedPricingStorage->expects($this->never())
            ->method('getPrices');
        $this->flatPricingStorage->expects($this->once())
            ->method('getPrices')
            ->willReturn($prices);

        $this->assertSame($prices, $this->storage->getPrices($scopeCriteria, [$product]));
    }

    public function testGetPricesNone()
    {
        $customer = $this->getEntity(Customer::class, ['id' => 1]);
        $website = $this->getEntity(Website::class, ['id' => 2]);
        $product = $this->getEntity(Product::class, ['id' => 4]);
        $scopeCriteria = $this->getScopeCriteria($website, $customer);

        $this->featureChecker->expects($this->any())
            ->method('isFeatureEnabled')
            ->willReturnMap([
                ['oro_price_lists_combined', null, false],
                ['oro_price_lists_flat', null, false],
            ]);

        $this->combinedPricingStorage->expects($this->never())
            ->method('getPrices');
        $this->flatPricingStorage->expects($this->never())
            ->method('getPrices');

        $this->assertSame([], $this->storage->getPrices($scopeCriteria, [$product]));
    }

    public function testGetSupportedCurrenciesCombined()
    {
        $customer = $this->getEntity(Customer::class, ['id' => 1]);
        $website = $this->getEntity(Website::class, ['id' => 2]);
        $scopeCriteria = $this->getScopeCriteria($website, $customer);
        $currencies = ['USD'];

        $this->featureChecker->expects($this->any())
            ->method('isFeatureEnabled')
            ->willReturnMap([
                ['oro_price_lists_combined', null, true],
                ['oro_price_lists_flat', null, false],
            ]);

        $this->flatPricingStorage->expects($this->never())
            ->method('getSupportedCurrencies');
        $this->combinedPricingStorage->expects($this->once())
            ->method('getSupportedCurrencies')
            ->willReturn($currencies);

        $this->assertSame($currencies, $this->storage->getSupportedCurrencies($scopeCriteria));
    }

    public function testGetSupportedCurrenciesFlat()
    {
        $customer = $this->getEntity(Customer::class, ['id' => 1]);
        $website = $this->getEntity(Website::class, ['id' => 2]);
        $scopeCriteria = $this->getScopeCriteria($website, $customer);
        $currencies = ['USD'];

        $this->featureChecker->expects($this->any())
            ->method('isFeatureEnabled')
            ->willReturnMap([
                ['oro_price_lists_combined', null, false],
                ['oro_price_lists_flat', null, true],
            ]);

        $this->combinedPricingStorage->expects($this->never())
            ->method('getSupportedCurrencies');
        $this->flatPricingStorage->expects($this->once())
            ->method('getSupportedCurrencies')
            ->willReturn($currencies);

        $this->assertSame($currencies, $this->storage->getSupportedCurrencies($scopeCriteria));
    }

    public function testGetSupportedCurrenciesNone()
    {
        $customer = $this->getEntity(Customer::class, ['id' => 1]);
        $website = $this->getEntity(Website::class, ['id' => 2]);
        $scopeCriteria = $this->getScopeCriteria($website, $customer);

        $this->featureChecker->expects($this->any())
            ->method('isFeatureEnabled')
            ->willReturnMap([
                ['oro_price_lists_combined', null, false],
                ['oro_price_lists_flat', null, false],
            ]);

        $this->combinedPricingStorage->expects($this->never())
            ->method('getSupportedCurrencies');
        $this->flatPricingStorage->expects($this->never())
            ->method('getSupportedCurrencies');

        $this->assertSame([], $this->storage->getSupportedCurrencies($scopeCriteria));
    }

    protected function getScopeCriteria(Website $website, Customer $customer): ProductPriceScopeCriteriaInterface
    {
        $scopeCriteria = new ProductPriceScopeCriteria();
        $scopeCriteria->setWebsite($website);
        $scopeCriteria->setCustomer($customer);

        return $scopeCriteria;
    }
}
