<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Provider;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\PricingBundle\Entity\PriceAttributePriceList;
use Oro\Bundle\PricingBundle\Entity\PriceAttributeProductPrice;
use Oro\Bundle\PricingBundle\Entity\Repository\PriceAttributePriceListRepository;
use Oro\Bundle\PricingBundle\Entity\Repository\PriceAttributeProductPriceRepository;
use Oro\Bundle\PricingBundle\Manager\UserCurrencyManager;
use Oro\Bundle\PricingBundle\Model\DTO\ProductPriceDTO;
use Oro\Bundle\PricingBundle\Model\ProductPriceScopeCriteria;
use Oro\Bundle\PricingBundle\Model\ProductPriceScopeCriteriaFactoryInterface;
use Oro\Bundle\PricingBundle\Provider\FrontendProductPricesExportProvider;
use Oro\Bundle\PricingBundle\Provider\ProductPriceProviderInterface;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Entity\ProductUnitPrecision;
use Oro\Bundle\ProductBundle\Tests\Unit\Stub\ProductStub;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Oro\Bundle\WebsiteBundle\Entity\Website;

class FrontendProductPricesExportProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var ConfigManager|\PHPUnit\Framework\MockObject\MockObject */
    private $configManager;

    /** @var ProductPriceProviderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $priceProvider;

    /** @var TokenAccessorInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $tokenAccessor;

    /** @var ProductPriceScopeCriteriaFactoryInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $priceScopeCriteriaFactory;

    /** @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrine;

    /** @var UserCurrencyManager|\PHPUnit\Framework\MockObject\MockObject */
    private $currencyManager;

    /** @var FrontendProductPricesExportProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $provider;

    protected function setUp(): void
    {
        $this->configManager = $this->createMock(ConfigManager::class);
        $this->priceProvider = $this->createMock(ProductPriceProviderInterface::class);
        $this->tokenAccessor = $this->createMock(TokenAccessorInterface::class);
        $this->priceScopeCriteriaFactory = $this->createMock(ProductPriceScopeCriteriaFactoryInterface::class);
        $this->doctrine = $this->createMock(ManagerRegistry::class);
        $this->currencyManager = $this->createMock(UserCurrencyManager::class);

        $this->provider = new FrontendProductPricesExportProvider(
            $this->doctrine,
            $this->configManager,
            $this->priceProvider,
            $this->tokenAccessor,
            $this->priceScopeCriteriaFactory,
            $this->currencyManager
        );
    }

    public function testGetAvailableExportPriceAttributes(): void
    {
        $this->tokenAccessor->expects(self::once())
            ->method('getOrganization')
            ->willReturn(new Organization());

        $repository = $this->createMock(PriceAttributePriceListRepository::class);

        $priceAttributes = [
            $this->createPriceAttributePriceList('test_price'),
            $this->createPriceAttributePriceList('custom_price'),
        ];

        $repository->expects(self::once())
            ->method('findBy')
            ->willReturn($priceAttributes);

        $this->doctrine->expects(self::once())
            ->method('getRepository')
            ->willReturn($repository);

        $result = $this->provider->getAvailableExportPriceAttributes();

        self::assertCount(2, $priceAttributes);
        self::assertEquals($priceAttributes, $result);
    }

    public function testGetProductPriceAttributesPrices(): void
    {
        $product = $this->createProduct(1);
        $product2 = $this->createProduct(2);
        $product3 = $this->createProduct(3);

        $this->tokenAccessor->expects(self::once())
            ->method('getOrganization')
            ->willReturn(new Organization());

        $priceAttributeRepository = $this->createMock(PriceAttributePriceListRepository::class);

        $testPriceAttribute = $this->createPriceAttributePriceList('test_price');
        $customPriceAttribute = $this->createPriceAttributePriceList('custom_price');

        $priceAttributes = [
            $testPriceAttribute,
            $customPriceAttribute,
        ];

        $priceAttributeRepository->expects(self::once())
            ->method('findBy')
            ->willReturn($priceAttributes);

        $pricesRepository = $this->createMock(PriceAttributeProductPriceRepository::class);

        /**
         * For product1 expecting two prices with correct unit and currency.
         * For product2 expecting one price with correct unit and currency and two with different currency and unit.
         * For product3 expecting no any prices.
         */
        $priceAttributePrices = [
            $this->createPriceAttributeProductPrice(
                $product,
                $testPriceAttribute,
                $product->getPrimaryUnitPrecision()->getUnit(),
                Price::create(10.00, 'USD')
            ),
            $this->createPriceAttributeProductPrice(
                $product,
                $customPriceAttribute,
                $product->getPrimaryUnitPrecision()->getUnit(),
                Price::create(15.00, 'USD')
            ),
            $this->createPriceAttributeProductPrice(
                $product2,
                $testPriceAttribute,
                $product->getPrimaryUnitPrecision()->getUnit(),
                Price::create(20.00, 'USD')
            ),
            $this->createPriceAttributeProductPrice(
                $product2,
                $testPriceAttribute,
                $product->getPrimaryUnitPrecision()->getUnit(),
                Price::create(17.00, 'EUR')
            ),
            $this->createPriceAttributeProductPrice(
                $product2,
                $customPriceAttribute,
                (new ProductUnit())->setCode('set'),
                Price::create(30.00, 'USD')
            ),
        ];

        $pricesRepository->expects(self::once())
            ->method('findByPriceAttributeProductPriceIdsAndProductIds')
            ->willReturn($priceAttributePrices);

        $this->doctrine->expects(self::exactly(2))
            ->method('getRepository')
            ->willReturnOnConsecutiveCalls($priceAttributeRepository, $pricesRepository);

        $result = $this->provider->getProductPriceAttributesPrices(
            $product,
            ['currentCurrency' => 'USD', 'ids' => [1, 2, 3]]
        );
        self::assertEquals([$priceAttributePrices[0], $priceAttributePrices[1]], $result);

        $result2 = $this->provider->getProductPriceAttributesPrices(
            $product2,
            ['currentCurrency' => 'USD', 'ids' => [1, 2, 3]]
        );
        self::assertEquals([$priceAttributePrices[2]], $result2);

        $result3 = $this->provider->getProductPriceAttributesPrices(
            $product3,
            ['currentCurrency' => 'USD', 'ids' => [1, 2, 3]]
        );
        self::assertEmpty($result3);
    }

    public function testGetProductPriceAttributesPricesWithEmptyOptions(): void
    {
        $product = $this->createProduct(1);

        $this->tokenAccessor->expects(self::once())
            ->method('getOrganization')
            ->willReturn(new Organization());

        $testPriceAttribute = $this->createPriceAttributePriceList('test_price');
        $customPriceAttribute = $this->createPriceAttributePriceList('custom_price');

        $priceAttributeRepository = $this->createMock(PriceAttributePriceListRepository::class);

        $priceAttributeRepository->expects(self::once())
            ->method('findBy')
            ->willReturn([$testPriceAttribute, $customPriceAttribute]);

        $pricesRepository = $this->createMock(PriceAttributeProductPriceRepository::class);

        $priceAttributePrices = [
            $this->createPriceAttributeProductPrice(
                $product,
                $testPriceAttribute,
                $product->getPrimaryUnitPrecision()->getUnit(),
                Price::create(10.00, 'USD')
            ),
            $this->createPriceAttributeProductPrice(
                $product,
                $customPriceAttribute,
                $product->getPrimaryUnitPrecision()->getUnit(),
                Price::create(15.00, 'USD')
            ),
        ];

        $pricesRepository->expects(self::once())
            ->method('findByPriceAttributeProductPriceIdsAndProductIds')
            ->willReturn($priceAttributePrices);

        $this->doctrine->expects(self::exactly(2))
            ->method('getRepository')
            ->willReturnOnConsecutiveCalls($priceAttributeRepository, $pricesRepository);

        $this->currencyManager->expects(self::once())
            ->method('getUserCurrency')
            ->willReturn('USD');

        $result = $this->provider->getProductPriceAttributesPrices($product, []);

        self::assertEquals($priceAttributePrices, $result);
    }

    public function testGetProductPrice(): void
    {
        $customerUser = $this->createCustomerUser();

        $this->tokenAccessor->expects(self::once())
            ->method('getUser')
            ->willReturn($customerUser);

        $productPriceScopeCriteria = new ProductPriceScopeCriteria();
        $productPriceScopeCriteria->setCustomer($customerUser->getCustomer());
        $productPriceScopeCriteria->setWebsite($customerUser->getWebsite());

        $this->priceScopeCriteriaFactory->expects(self::once())
            ->method('create')
            ->willReturn($productPriceScopeCriteria);

        $product = $this->createProduct(1);
        $productUnit = $product->getPrimaryUnitPrecision()->getUnit();
        $productTierPrices = [
            $product->getId() => [
                new ProductPriceDTO($product, Price::create(10.00, 'USD'), 1, $productUnit),
                new ProductPriceDTO($product, Price::create(7.00, 'USD'), 10, $productUnit),
                new ProductPriceDTO($product, Price::create(5.00, 'USD'), 20, $productUnit),
            ],
        ];

        $this->priceProvider->expects(self::once())
            ->method('getPricesByScopeCriteriaAndProducts')
            ->with($productPriceScopeCriteria)
            ->willReturn($productTierPrices);

        $result = $this->provider->getProductPrice(
            $product,
            ['currentCurrency' => 'USD', 'ids' => [$product->getId()]]
        );
        self::assertEquals(reset($productTierPrices[$product->getId()]), $result);

        // Checks local caching.
        $result = $this->provider->getProductPrice(
            $product,
            ['currentCurrency' => 'USD', 'ids' => [$product->getId()]]
        );
        self::assertEquals(reset($productTierPrices[$product->getId()]), $result);
    }

    public function testGetProductPriceWithEmptyOptions(): void
    {
        $customerUser = $this->createCustomerUser();

        $this->tokenAccessor->expects(self::once())
            ->method('getUser')
            ->willReturn($customerUser);

        $priceCriteria = new ProductPriceScopeCriteria();
        $priceCriteria->setCustomer($customerUser->getCustomer());
        $priceCriteria->setWebsite($customerUser->getWebsite());

        $this->priceScopeCriteriaFactory->expects(self::once())
            ->method('create')
            ->willReturn($priceCriteria);

        $product = $this->createProduct(1);
        $productUnit = $product->getPrimaryUnitPrecision()->getUnit();
        $productTierPrices = [
            $product->getId() => [
                new ProductPriceDTO($product, Price::create(10.00, 'USD'), 1, $productUnit),
                new ProductPriceDTO($product, Price::create(7.00, 'USD'), 10, $productUnit),
                new ProductPriceDTO($product, Price::create(5.00, 'USD'), 20, $productUnit),
            ],
        ];

        $this->priceProvider->expects(self::once())
            ->method('getPricesByScopeCriteriaAndProducts')
            ->willReturn($productTierPrices);

        $this->currencyManager->expects(self::exactly(2))
            ->method('getUserCurrency')
            ->willReturn('USD');

        $result = $this->provider->getProductPrice($product, []);
        self::assertEquals(reset($productTierPrices[$product->getId()]), $result);

        // Checks local caching.
        $result = $this->provider->getProductPrice($product, []);
        self::assertEquals(reset($productTierPrices[$product->getId()]), $result);
    }

    public function testGetTierPrices(): void
    {
        $customerUser = $this->createCustomerUser();

        $this->tokenAccessor->expects(self::once())
            ->method('getUser')
            ->willReturn($customerUser);

        $productPriceScopeCriteria = new ProductPriceScopeCriteria();
        $productPriceScopeCriteria->setCustomer($customerUser->getCustomer());
        $productPriceScopeCriteria->setWebsite($customerUser->getWebsite());

        $this->priceScopeCriteriaFactory->expects(self::once())
            ->method('create')
            ->willReturn($productPriceScopeCriteria);

        $product = $this->createProduct(1);
        $productUnit = $product->getPrimaryUnitPrecision()->getUnit();
        $productTierPrices = [
            $product->getId() => [
                new ProductPriceDTO($product, Price::create(10.00, 'USD'), 1, $productUnit),
                new ProductPriceDTO($product, Price::create(7.00, 'USD'), 10, $productUnit),
                new ProductPriceDTO($product, Price::create(5.00, 'USD'), 20, $productUnit),
            ],
        ];

        $this->priceProvider->expects(self::once())
            ->method('getPricesByScopeCriteriaAndProducts')
            ->with($productPriceScopeCriteria)
            ->willReturn($productTierPrices);

        $result = $this->provider->getTierPrices($product, ['currentCurrency' => 'USD', 'ids' => [$product->getId()]]);
        self::assertEquals($productTierPrices[$product->getId()], $result);

        // Checks local caching.
        $result = $this->provider->getTierPrices($product, ['currentCurrency' => 'USD', 'ids' => [$product->getId()]]);
        self::assertEquals($productTierPrices[$product->getId()], $result);
    }

    public function testGetTierPricesWithEmptyOptions(): void
    {
        $customerUser = $this->createCustomerUser();

        $this->tokenAccessor->expects(self::once())
            ->method('getUser')
            ->willReturn($customerUser);

        $priceCriteria = new ProductPriceScopeCriteria();
        $priceCriteria->setCustomer($customerUser->getCustomer());
        $priceCriteria->setWebsite($customerUser->getWebsite());

        $this->priceScopeCriteriaFactory->expects(self::once())
            ->method('create')
            ->willReturn($priceCriteria);

        $product = $this->createProduct(1);
        $productUnit = $product->getPrimaryUnitPrecision()->getUnit();
        $productTierPrices = [
            $product->getId() => [
                new ProductPriceDTO($product, Price::create(10.00, 'USD'), 1, $productUnit),
                new ProductPriceDTO($product, Price::create(7.00, 'USD'), 10, $productUnit),
                new ProductPriceDTO($product, Price::create(5.00, 'USD'), 20, $productUnit),
            ],
        ];

        $this->priceProvider->expects(self::once())
            ->method('getPricesByScopeCriteriaAndProducts')
            ->willReturn($productTierPrices);

        $this->currencyManager->expects(self::exactly(2))
            ->method('getUserCurrency')
            ->willReturn('USD');

        $result = $this->provider->getTierPrices($product, []);
        self::assertEquals($productTierPrices[$product->getId()], $result);

        // Checks local caching.
        $result = $this->provider->getTierPrices($product, []);
        self::assertEquals($productTierPrices[$product->getId()], $result);
    }

    /**
     * @dataProvider exportEnabledDataProvider
     */
    public function testIsPriceAttributesExportEnabled(bool $configValue, bool $expectedValue): void
    {
        $this->configManager->expects(self::once())
            ->method('get')
            ->willReturn($configValue);

        $result = $this->provider->isPricesExportEnabled();

        self::assertEquals($expectedValue, $result);
    }

    /**
     * @dataProvider exportEnabledDataProvider
     */
    public function testIsTierPricesExportEnabled(bool $configValue, bool $expectedValue): void
    {
        $this->configManager->expects(self::once())
            ->method('get')
            ->willReturn($configValue);

        $result = $this->provider->isTierPricesExportEnabled();

        self::assertEquals($expectedValue, $result);
    }

    public function exportEnabledDataProvider(): array
    {
        return [
            [false, false],
            [true, true],
        ];
    }

    private function createPriceAttributePriceList(?string $name): PriceAttributePriceList
    {
        return (new PriceAttributePriceList())
            ->setName($name)
            ->setFieldName($name);
    }

    private function createProduct(int $id = 0): Product
    {
        $product = (new ProductStub())->setId($id);

        $productUnit = (new ProductUnit())->setCode('item');
        $productUnitPrecision = (new ProductUnitPrecision())
            ->setProduct($product)
            ->setUnit($productUnit);

        $product->setPrimaryUnitPrecision($productUnitPrecision);

        return $product;
    }

    private function createCustomerUser(): CustomerUser
    {
        $customer = (new Customer())->setName('Test Customer');
        $website = (new Website())->setName('Test Website');

        return (new CustomerUser())
            ->setUsername('Test')
            ->setCustomer($customer)
            ->setWebsite($website);
    }

    private function createPriceAttributeProductPrice(
        Product $product,
        PriceAttributePriceList $priceAttributePriceList,
        ProductUnit $productUnit,
        Price $price
    ): PriceAttributeProductPrice {
        return (new PriceAttributeProductPrice())
            ->setProduct($product)
            ->setPriceList($priceAttributePriceList)
            ->setUnit($productUnit)
            ->setPrice($price);
    }
}
