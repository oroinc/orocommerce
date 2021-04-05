<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Provider;

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
use Oro\Bundle\PricingBundle\Provider\ProductPriceProvider;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Entity\ProductUnitPrecision;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Component\Testing\Unit\EntityTrait;
use PHPUnit\Framework\TestCase;
use Symfony\Bridge\Doctrine\ManagerRegistry;

class FrontendProductPricesExportProviderTest extends TestCase
{
    use EntityTrait;

    /**
     * @var ConfigManager|\PHPUnit\Framework\MockObject\MockObject
     */
    private $configManager;

    /**
     * @var ProductPriceProvider|\PHPUnit\Framework\MockObject\MockObject
     */
    private $priceProvider;

    /**
     * @var TokenAccessorInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $tokenAccessor;

    /**
     * @var ProductPriceScopeCriteriaFactoryInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $priceScopeCriteriaFactory;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|ManagerRegistry
     */
    private $managerRegistry;

    /**
     * @var UserCurrencyManager|\PHPUnit\Framework\MockObject\MockObject
     */
    private $currencyManager;

    /**
     * @var FrontendProductPricesExportProvider
     */
    private $provider;

    protected function setUp(): void
    {
        $this->configManager = $this->createMock(ConfigManager::class);
        $this->priceProvider = $this->createMock(ProductPriceProvider::class);
        $this->tokenAccessor = $this->createMock(TokenAccessorInterface::class);
        $this->priceScopeCriteriaFactory = $this->createMock(ProductPriceScopeCriteriaFactoryInterface::class);
        $this->managerRegistry = $this->createMock(ManagerRegistry::class);
        $this->currencyManager = $this->createMock(UserCurrencyManager::class);

        $this->provider = new FrontendProductPricesExportProvider(
            $this->configManager,
            $this->priceProvider,
            $this->tokenAccessor,
            $this->priceScopeCriteriaFactory,
            $this->managerRegistry,
            $this->currencyManager
        );
    }

    public function testGetAvailableExportPriceAttributes()
    {
        $this->tokenAccessor->expects($this->once())
            ->method('getOrganization')
            ->willReturn(new Organization());

        $repositoryMock = $this->createMock(PriceAttributePriceListRepository::class);

        $priceAttributes = [
            $this->createPriceAttribute('test_price'),
            $this->createPriceAttribute('custom_price')
        ];

        $repositoryMock->expects($this->once())
            ->method('findBy')
            ->willReturn($priceAttributes);

        $this->managerRegistry->expects($this->once())
            ->method('getRepository')
            ->willReturn($repositoryMock);

        $result = $this->provider->getAvailableExportPriceAttributes();

        $this->assertCount(2, $priceAttributes);
        $this->assertEquals($priceAttributes, $result);
    }

    public function testGetProductPrices()
    {
        $product = $this->createProduct(['id' => 1]);
        $product2 = $this->createProduct(['id' => 2 ]);
        $product3 = $this->createProduct(['id' => 3]);

        $this->tokenAccessor->expects($this->once())
            ->method('getOrganization')
            ->willReturn(new Organization());

        $priceAttributeRepositoryMock = $this->createMock(PriceAttributePriceListRepository::class);

        $testPriceAttribute = $this->createPriceAttribute('test_price');
        $customPriceAttribute = $this->createPriceAttribute('custom_price');

        $priceAttributes = [
            $testPriceAttribute,
            $customPriceAttribute
        ];

        $priceAttributeRepositoryMock->expects($this->once())
            ->method('findBy')
            ->willReturn($priceAttributes);

        $pricesRepository = $this->createMock(PriceAttributeProductPriceRepository::class);

        /**
         * For product1 expecting two prices with correct unit and currency.
         * For product2 expecting one price with correct unit and currency and two with different currency and unit.
         * For product3 expecting no any prices.
         */
        $priceAttributePrices = [
            $this->getEntity(PriceAttributeProductPrice::class, [
                'product' => $product,
                'priceList' => $testPriceAttribute,
                'unit' => $product->getPrimaryUnitPrecision()->getUnit(),
                'price' => Price::create(10.00, 'USD')
            ]),
            $this->getEntity(PriceAttributeProductPrice::class, [
                'product' => $product,
                'priceList' => $customPriceAttribute,
                'unit' => $product->getPrimaryUnitPrecision()->getUnit(),
                'price' => Price::create(15.00, 'USD')
            ]),
            $this->getEntity(PriceAttributeProductPrice::class, [
                'product' => $product2,
                'priceList' => $testPriceAttribute,
                'unit' => $product->getPrimaryUnitPrecision()->getUnit(),
                'price' => Price::create(20.00, 'USD')
            ]),
            $this->getEntity(PriceAttributeProductPrice::class, [
                'product' => $product2,
                'priceList' => $testPriceAttribute,
                'unit' => $product->getPrimaryUnitPrecision()->getUnit(),
                'price' => Price::create(17.00, 'EUR')
            ]),
            $this->getEntity(PriceAttributeProductPrice::class, [
                'product' => $product2,
                'priceList' => $customPriceAttribute,
                'unit' => $this->getEntity(ProductUnit::class, ['code' => 'set']),
                'price' => Price::create(30.00, 'USD')
            ]),
        ];

        $pricesRepository->expects($this->once())
            ->method('findByPriceAttributeProductPriceIdsAndProductIds')
            ->willReturn($priceAttributePrices);

        $this->managerRegistry->expects($this->exactly(2))
            ->method('getRepository')
            ->willReturnOnConsecutiveCalls($priceAttributeRepositoryMock, $pricesRepository);

        $result = $this->provider->getProductPrices($product, ['currentCurrency' => 'USD', 'ids' => [1, 2, 3]]);

        $this->assertCount(2, $result);

        $expectedResultForProduct1 = [
            'test_price' => 10.00,
            'custom_price' => 15.00
        ];

        $this->assertEquals($expectedResultForProduct1, $result);

        $result2 = $this->provider->getProductPrices($product2, ['currentCurrency' => 'USD', 'ids' => [1, 2, 3]]);

        $this->assertCount(1, $result2);

        $expectedResultForProduct2 = [
            'test_price' => 20.00
        ];

        $this->assertEquals($expectedResultForProduct2, $result2);

        $result3 = $this->provider->getProductPrices($product3, ['currentCurrency' => 'USD', 'ids' => [1, 2, 3]]);
        $this->assertCount(0, $result3);
        $this->assertEmpty($result3);
    }

    public function testGetProductPricesWithoutCurrencyInOptions()
    {
        $product = $this->createProduct(['id' => 1]);

        $this->tokenAccessor->expects($this->once())
            ->method('getOrganization')
            ->willReturn(new Organization());

        $priceAttributeRepositoryMock = $this->createMock(PriceAttributePriceListRepository::class);

        $testPriceAttribute = $this->createPriceAttribute('test_price');
        $customPriceAttribute = $this->createPriceAttribute('custom_price');

        $priceAttributes = [
            $testPriceAttribute,
            $customPriceAttribute
        ];

        $priceAttributeRepositoryMock->expects($this->once())
            ->method('findBy')
            ->willReturn($priceAttributes);

        $pricesRepository = $this->createMock(PriceAttributeProductPriceRepository::class);

        $priceAttributePrices = [
            $this->getEntity(PriceAttributeProductPrice::class, [
                'product'   => $product,
                'priceList' => $testPriceAttribute,
                'unit'      => $product->getPrimaryUnitPrecision()->getUnit(),
                'price'     => Price::create(10.00, 'USD')
            ]),
            $this->getEntity(PriceAttributeProductPrice::class, [
                'product'   => $product,
                'priceList' => $customPriceAttribute,
                'unit'      => $product->getPrimaryUnitPrecision()->getUnit(),
                'price'     => Price::create(15.00, 'USD')
            ]),
        ];

        $pricesRepository->expects($this->once())
            ->method('findByPriceAttributeProductPriceIdsAndProductIds')
            ->willReturn($priceAttributePrices);

        $this->managerRegistry->expects($this->exactly(2))
            ->method('getRepository')
            ->willReturnOnConsecutiveCalls($priceAttributeRepositoryMock, $pricesRepository);

        $this->currencyManager->expects($this->once())
            ->method('getUserCurrency')
            ->willReturn('USD');

        $result = $this->provider->getProductPrices($product, ['ids' => [1, 2]]);

        $this->assertCount(2, $result);

        $expectedResult = [
            'test_price'   => 10.00,
            'custom_price' => 15.00
        ];

        $this->assertEquals($expectedResult, $result);
    }

    public function testGetProductPricesWithEmptyOptions()
    {
        $product = $this->createProduct(['id' => 1]);

        $this->tokenAccessor->expects($this->never())
            ->method('getOrganization');

        $priceAttributeRepositoryMock = $this->createMock(PriceAttributePriceListRepository::class);

        $priceAttributeRepositoryMock->expects($this->never())
            ->method('findBy');

        $pricesRepository = $this->createMock(PriceAttributeProductPriceRepository::class);

        $pricesRepository->expects($this->never())
            ->method('findByPriceAttributeProductPriceIdsAndProductIds');

        $this->managerRegistry->expects($this->never())
            ->method('getRepository');

        $result = $this->provider->getProductPrices($product, []);

        $this->assertCount(0, $result);
    }

    public function testGetTierPrices()
    {
        $customerUser = $this->createCustomerUser();

        $this->tokenAccessor->expects($this->once())
            ->method('getUser')
            ->willReturn($customerUser);

        $priceCriteria = new ProductPriceScopeCriteria();
        $priceCriteria->setCustomer($customerUser->getCustomer());
        $priceCriteria->setWebsite($customerUser->getWebsite());

        $this->priceScopeCriteriaFactory->expects($this->once())
            ->method('create')
            ->willReturn($priceCriteria);

        $product = $this->createProduct();
        $productUnit = $product->getPrimaryUnitPrecision()->getUnit();
        $productTierPrices = [
            1 => [
                new ProductPriceDTO($product, Price::create(10.00, 'USD'), 1, $productUnit),
                new ProductPriceDTO($product, Price::create(7.00, 'USD'), 10, $productUnit),
                new ProductPriceDTO($product, Price::create(5.00, 'USD'), 20, $productUnit)
            ]
        ];

        $this->priceProvider->expects($this->once())
            ->method('getPricesByScopeCriteriaAndProducts')
            ->willReturn($productTierPrices);

        $result = $this->provider->getTierPrices($product, ['currentCurrency' => 'USD']);

        $this->assertCount(1, $result);
        $this->assertCount(3, current($result));
        $this->assertEquals($productTierPrices, $result);
    }

    public function testGetTierPricesWithEmptyOptions()
    {
        $customerUser = $this->createCustomerUser();

        $this->tokenAccessor->expects($this->once())
            ->method('getUser')
            ->willReturn($customerUser);

        $priceCriteria = new ProductPriceScopeCriteria();
        $priceCriteria->setCustomer($customerUser->getCustomer());
        $priceCriteria->setWebsite($customerUser->getWebsite());

        $this->priceScopeCriteriaFactory->expects($this->once())
            ->method('create')
            ->willReturn($priceCriteria);

        $product = $this->createProduct();
        $productUnit = $product->getPrimaryUnitPrecision()->getUnit();
        $productTierPrices = [
            1 => [
                new ProductPriceDTO($product, Price::create(10.00, 'USD'), 1, $productUnit),
                new ProductPriceDTO($product, Price::create(7.00, 'USD'), 10, $productUnit),
                new ProductPriceDTO($product, Price::create(5.00, 'USD'), 20, $productUnit)
            ]
        ];

        $this->priceProvider->expects($this->once())
            ->method('getPricesByScopeCriteriaAndProducts')
            ->willReturn($productTierPrices);

        $this->currencyManager->expects($this->once())
            ->method('getUserCurrency')
            ->willReturn('USD');

        $result = $this->provider->getTierPrices($product, []);

        $this->assertCount(1, $result);
        $this->assertCount(3, current($result));
        $this->assertEquals($productTierPrices, $result);
    }

    /**
     * @dataProvider exportEnabledDataProvider
     * @param bool $configValue
     * @param bool $expectedValue
     */
    public function testIsPriceAttributesExportEnabled(bool $configValue, bool $expectedValue)
    {
        $this->configManager->expects($this->once())
            ->method('get')
            ->willReturn($configValue);

        $result = $this->provider->isPriceAttributesExportEnabled();

        $this->assertEquals($expectedValue, $result);
    }

    /**
     * @dataProvider exportEnabledDataProvider
     * @param bool $configValue
     * @param bool $expectedValue
     */
    public function testIsTierPricesExportEnabled(bool $configValue, bool $expectedValue)
    {
        $this->configManager->expects($this->once())
            ->method('get')
            ->willReturn($configValue);

        $result = $this->provider->isTierPricesExportEnabled();

        $this->assertEquals($expectedValue, $result);
    }

    public function exportEnabledDataProvider()
    {
        return [
            [false, false],
            [true, true]
        ];
    }

    /**
     * @param string|null $name
     * @return PriceAttributePriceList
     */
    private function createPriceAttribute(?string $name): PriceAttributePriceList
    {
        return $this->getEntity(PriceAttributePriceList::class, [
            'name' => $name,
            'fieldName' => $name
        ]);
    }

    /**
     * @param array $productOptions
     * @return Product
     */
    private function createProduct(array $productOptions = []): Product
    {
        $product = $this->getEntity(Product::class, $productOptions);

        $productUnit = new ProductUnit();
        $productUnit->setCode('item');
        $productUnitPrecision = $this->getEntity(ProductUnitPrecision::class, [
            'product' => $product,
            'unit' => $productUnit
        ]);

        $product->setPrimaryUnitPrecision($productUnitPrecision);

        return $product;
    }

    /**
     * @return CustomerUser
     */
    private function createCustomerUser(): CustomerUser
    {
        $customer = $this->getEntity(Customer::class, [
            'name' => 'Test Customer'
        ]);

        $website = $this->getEntity(Website::class, [
            'name' => 'Test Website'
        ]);

        $user = $this->getEntity(CustomerUser::class, [
            'username' => 'Test',
            'customer' => $customer,
            'website' => $website
        ]);


        return $user;
    }
}
