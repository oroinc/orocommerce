<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\DataProvider\Manager;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Oro\Bundle\CacheBundle\Provider\MemoryCacheProviderInterface;
use Oro\Bundle\CheckoutBundle\DataProvider\Converter\CheckoutLineItemsConverter;
use Oro\Bundle\CheckoutBundle\DataProvider\Manager\CheckoutLineItemsManager;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\EntityExtendBundle\Tests\Unit\Fixtures\TestEnumValue;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\PricingBundle\Manager\UserCurrencyManager;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Tests\Unit\Entity\Stub\Product;
use Oro\Component\Checkout\DataProvider\CheckoutDataProviderInterface;
use Oro\Component\Testing\ReflectionUtil;
use Oro\Component\Testing\Unit\EntityTrait;

class CheckoutLineItemsManagerTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /** @var CheckoutLineItemsConverter|\PHPUnit\Framework\MockObject\MockObject */
    private $checkoutLineItemsConverter;

    /** @var UserCurrencyManager|\PHPUnit\Framework\MockObject\MockObject */
    private $currencyManager;

    /** @var ConfigManager|\PHPUnit\Framework\MockObject\MockObject */
    private $configManager;

    /** @var MemoryCacheProviderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $memoryCacheProvider;

    #[\Override]
    protected function setUp(): void
    {
        $this->checkoutLineItemsConverter = $this->createMock(CheckoutLineItemsConverter::class);
        $this->currencyManager = $this->createMock(UserCurrencyManager::class);
        $this->configManager = $this->createMock(ConfigManager::class);
        $this->memoryCacheProvider = $this->createMock(MemoryCacheProviderInterface::class);

        $this->currencyManager->expects($this->any())
            ->method('getUserCurrency')
            ->willReturn('USD');

        $this->checkoutLineItemsConverter->expects($this->any())
            ->method('convert')
            ->willReturnCallback(function ($data) {
                $result = new ArrayCollection();
                foreach ($data as $productData) {
                    $result->add($this->getEntity(OrderLineItem::class, $productData));
                }

                return $result;
            });
    }

    private function getCheckoutLineItemsManager(array $providers): CheckoutLineItemsManager
    {
        return new CheckoutLineItemsManager(
            $providers,
            $this->checkoutLineItemsConverter,
            $this->currencyManager,
            $this->configManager,
            $this->memoryCacheProvider
        );
    }

    public function testGetDataWhenDataCached(): void
    {
        $checkout = $this->createMock(Checkout::class);
        $disablePriceFilter = false;
        $configVisibilityPath = 'oro_order.frontend_product_visibility';
        $lineItems = $this->createMock(Collection::class);

        $this->memoryCacheProvider->expects(self::once())
            ->method('get')
            ->willReturnCallback(function () use ($lineItems) {
                return $lineItems;
            });

        $this->assertEquals(
            $lineItems,
            $this->getCheckoutLineItemsManager([])->getData($checkout, $disablePriceFilter, $configVisibilityPath)
        );
    }

    /**
     * @dataProvider getDataDataProvider
     */
    public function testGetDataEntitySupported(array $withDataProvider, bool $isEntitySupported, bool $visible)
    {
        $checkout = new Checkout();
        $data = [];

        $providers = [];
        if ($withDataProvider) {
            if ($isEntitySupported && $visible) {
                $data = [$this->getLineItemData(true, 42, 'PRO', 'in_stock', 10, 'litre', Price::create(10, 'USD'))];
            }
            $providers[] = $this->getProvider($checkout, $data, $isEntitySupported);
        }

        $this->configManager->expects($this->any())
            ->method('get')
            ->with('oro_order.frontend_product_visibility')
            ->willReturn(['test_enum_code.in_stock']);

        $this->memoryCacheProvider->expects(self::once())
            ->method('get')
            ->willReturnCallback(function ($arguments, $callable) {
                return $callable($arguments);
            });

        $checkoutLineItemsManager = $this->getCheckoutLineItemsManager($providers);
        $result = $checkoutLineItemsManager->getData($checkout);
        $this->assertEquals($this->checkoutLineItemsConverter->convert($data), $result);
    }

    /**
     * @dataProvider getDataDataProvider
     */
    public function testGetData(
        array $providerData,
        bool $disablePriceFilter,
        bool $visible,
        array|ArrayCollection $expectedData
    ) {
        $checkout = new Checkout();

        $checkoutLineItemsManager = $this->getCheckoutLineItemsManager(
            [$this->getProvider($checkout, $providerData)]
        );

        $this->configManager->expects($this->any())
            ->method('get')
            ->with('oro_order.frontend_product_visibility')
            ->willReturn(['test_enum_code.in_stock']);

        $this->memoryCacheProvider->expects(self::once())
            ->method('get')
            ->willReturnCallback(function ($arguments, $callable) {
                return $callable($arguments);
            });

        $expectedData = $this->checkoutLineItemsConverter->convert($expectedData);
        $actualData = $checkoutLineItemsManager->getData($checkout, $disablePriceFilter);
        $this->assertEquals($expectedData, $actualData);
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function getDataDataProvider(): array
    {
        $hasProduct = true;
        $productFree = false;

        return [
            [
                'providerData' => [
                    $this->getLineItemData($hasProduct, 42, 'PRO', 'in_stock', 10, 'litre', Price::create(10, 'USD')),
                    $this->getLineItemData($hasProduct, 42, 'PRO', 'in_stock', 10, 'litre', Price::create(0, 'USD')),
                    $this->getLineItemData($productFree, 42, 'PRO', 'in_stock', 10, 'litre', Price::create(10, 'USD')),
                    $this->getLineItemData($hasProduct, 42, 'PRO', 'in_stock', 10, 'litre', null),
                    $this->getLineItemData($hasProduct, 42, 'PRO', 'out_of_stock', 10, 'litre', null),
                    $this->getLineItemData($hasProduct, 42, 'PRO', 'in_stock', 10, 'litre', Price::create(10, 'UAH')),
                    $this->getLineItemData($hasProduct, 42, 'PRO', null, 10, 'litre', Price::create(10, 'USD')),
                    $this->getLineItemData(
                        $hasProduct,
                        42,
                        'PRO',
                        'out_of_stock',
                        10,
                        'litre',
                        Price::create(10, 'USD')
                    ),
                ],
                'disablePriceFilter' => false,
                'visible' => true,
                'expectedData' => [
                    $this->getLineItemData($hasProduct, 42, 'PRO', 'in_stock', 10, 'litre', Price::create(10, 'USD')),
                    $this->getLineItemData($hasProduct, 42, 'PRO', 'in_stock', 10, 'litre', Price::create(0, 'USD')),
                    $this->getLineItemData($productFree, 42, 'PRO', 'in_stock', 10, 'litre', Price::create(10, 'USD')),
                ],
            ],
            [
                'providerData' => [
                    $this->getLineItemData($hasProduct, 42, 'PRO', 'in_stock', 10, 'litre', Price::create(10, 'USD')),
                    $this->getLineItemData($hasProduct, 42, 'PRO', 'in_stock', 10, 'litre', null),
                    $this->getLineItemData($hasProduct, 42, 'PRO', 'out_of_stock', 10, 'litre', null),
                    $this->getLineItemData($hasProduct, 42, 'PRO', 'in_stock', 10, 'litre', Price::create(10, 'UAH')),
                    $this->getLineItemData($hasProduct, 42, 'PRO', null, 10, 'litre', Price::create(10, 'USD')),
                    $this->getLineItemData(
                        $hasProduct,
                        42,
                        'PRO',
                        'out_of_stock',
                        10,
                        'litre',
                        Price::create(10, 'USD')
                    ),
                    $this->getLineItemData($productFree, 42, 'PRO', 'in_stock', 10, 'litre', Price::create(10, 'USD')),
                ],
                'disablePriceFilter' => true,
                'visible' => true,
                'expectedData' => [
                    $this->getLineItemData($hasProduct, 42, 'PRO', 'in_stock', 10, 'litre', Price::create(10, 'USD')),
                    $this->getLineItemData($hasProduct, 42, 'PRO', 'in_stock', 10, 'litre', null),
                    $this->getLineItemData($hasProduct, 42, 'PRO', 'out_of_stock', 10, 'litre', null),
                    $this->getLineItemData($hasProduct, 42, 'PRO', 'in_stock', 10, 'litre', Price::create(10, 'UAH')),
                    $this->getLineItemData($hasProduct, 42, 'PRO', null, 10, 'litre', Price::create(10, 'USD')),
                    $this->getLineItemData(
                        $hasProduct,
                        42,
                        'PRO',
                        'out_of_stock',
                        10,
                        'litre',
                        Price::create(10, 'USD')
                    ),
                    $this->getLineItemData($productFree, 42, 'PRO', 'in_stock', 10, 'litre', Price::create(10, 'USD')),
                ],
            ],
            [
                'providerData' => [
                    $this->getLineItemData($productFree, 42, 'PRO', 'in_stock', 10, 'litre', Price::create(10, 'USD')),
                ],
                'disablePriceFilter' => true,
                'visible' => false,
                'expectedData' => [
                    $this->getLineItemData($productFree, 42, 'PRO', 'in_stock', 10, 'litre', Price::create(10, 'USD')),
                ],
            ],
        ];
    }

    private function getLineItemData(
        bool $hasProduct,
        int $productId,
        ?string $productSku,
        ?string $inventoryStatus,
        float $qty,
        string $unit,
        ?Price $price,
        string $status = Product::STATUS_ENABLED
    ): array {
        $product = null;
        if ($hasProduct && $productSku) {
            $product = new Product();
            ReflectionUtil::setId($product, $productId);
            $product->setSku($productSku);
            $product->setStatus($status);

            if ($inventoryStatus) {
                $inventoryStatus = new TestEnumValue('test_enum_code', 'Test', $inventoryStatus);
                $product->setInventoryStatus($inventoryStatus);
            }
        }

        $productUnit = new ProductUnit();
        $productUnit->setCode($unit);

        return [
            'product' => $product,
            'productSku' => $productSku,
            'quantity' => $qty,
            'productUnit' => $productUnit,
            'productUnitCode' => $productUnit->getCode(),
            'price' => $price
        ];
    }

    private function getProvider(
        Checkout $entity,
        array $returnData,
        bool $isSupported = true
    ): CheckoutDataProviderInterface {
        $provider = $this->createMock(CheckoutDataProviderInterface::class);

        $provider->expects($this->once())
            ->method('isEntitySupported')
            ->with($entity)
            ->willReturn($isSupported);

        if ($isSupported) {
            $provider->expects($this->once())
                ->method('getData')
                ->with($entity)
                ->willReturn($returnData);
        }
        return $provider;
    }

    /**
     * @dataProvider getLineItemsWithoutQuantityDataProvider
     */
    public function testGetLineItemsWithoutQuantity(array $providerData, array|ArrayCollection $expectedData)
    {
        $checkout = new Checkout();

        $checkoutLineItemsManager = $this->getCheckoutLineItemsManager(
            [$this->getProvider($checkout, $providerData)]
        );

        $this->configManager->expects($this->any())
            ->method('get')
            ->with('oro_order.frontend_product_visibility')
            ->willReturn(['in_stock']);

        $this->memoryCacheProvider->expects(self::once())
            ->method('get')
            ->willReturnCallback(function ($arguments, $callable) {
                return $callable($arguments);
            });

        $expectedData = $this->checkoutLineItemsConverter->convert($expectedData);
        $actualData = $checkoutLineItemsManager->getLineItemsWithoutQuantity($checkout);
        $this->assertEquals($expectedData, $actualData);
    }

    public function getLineItemsWithoutQuantityDataProvider(): array
    {
        $hasProduct = true;
        $productFree = false;

        return [
            [
                'providerData' => [
                    $this->getLineItemData($hasProduct, 42, 'PRO', 'in_stock', 10, 'litre', Price::create(10, 'USD')),
                    $this->getLineItemData($hasProduct, 42, 'PRO', 'in_stock', 0, 'litre', Price::create(0, 'USD')),
                    $this->getLineItemData($productFree, 42, 'PRO', 'in_stock', 0, 'litre', Price::create(10, 'USD')),
                    $this->getLineItemData($hasProduct, 42, 'PRO', 'in_stock', 10, 'litre', null),
                    $this->getLineItemData($hasProduct, 42, 'PRO', 'out_of_stock', 0, 'litre', null),
                    $this->getLineItemData($hasProduct, 42, 'PRO', 'in_stock', 10, 'litre', Price::create(10, 'UAH')),
                    $this->getLineItemData($hasProduct, 42, 'PRO', null, 0, 'litre', Price::create(10, 'USD')),
                    $this->getLineItemData(
                        $hasProduct,
                        42,
                        'PRO',
                        'out_of_stock',
                        10,
                        'litre',
                        Price::create(10, 'USD')
                    ),
                ],
                'expectedData' => [
                    $this->getLineItemData($hasProduct, 42, 'PRO', 'in_stock', 0, 'litre', Price::create(0, 'USD')),
                    $this->getLineItemData($productFree, 42, 'PRO', 'in_stock', 0, 'litre', Price::create(10, 'USD')),
                    $this->getLineItemData($hasProduct, 42, 'PRO', 'out_of_stock', 0, 'litre', null),
                    $this->getLineItemData($hasProduct, 42, 'PRO', null, 0, 'litre', Price::create(10, 'USD')),
                ],
            ],
            [
                'providerData' => [
                    $this->getLineItemData($hasProduct, 42, 'PRO', 'in_stock', 10, 'litre', Price::create(10, 'USD')),
                    $this->getLineItemData($hasProduct, 42, 'PRO', 'in_stock', 0, 'litre', null),
                    $this->getLineItemData($hasProduct, 42, 'PRO', 'out_of_stock', 0, 'litre', null),
                    $this->getLineItemData($hasProduct, 42, 'PRO', 'in_stock', 10, 'litre', Price::create(10, 'UAH')),
                    $this->getLineItemData($hasProduct, 42, 'PRO', null, 0, 'litre', Price::create(10, 'USD')),
                    $this->getLineItemData(
                        $hasProduct,
                        42,
                        'PRO',
                        'out_of_stock',
                        10,
                        'litre',
                        Price::create(10, 'USD')
                    ),
                    $this->getLineItemData($productFree, 42, 'PRO', 'in_stock', 10, 'litre', Price::create(10, 'USD')),
                    $this->getLineItemData(
                        $hasProduct,
                        42,
                        'PRO',
                        'in_stock',
                        10,
                        'litre',
                        Price::create(10, 'USD'),
                        Product::STATUS_DISABLED
                    ),
                ],
                'expectedData' => [
                    $this->getLineItemData($hasProduct, 42, 'PRO', 'in_stock', 0, 'litre', null),
                    $this->getLineItemData($hasProduct, 42, 'PRO', 'out_of_stock', 0, 'litre', null),
                    $this->getLineItemData($hasProduct, 42, 'PRO', null, 0, 'litre', Price::create(10, 'USD')),
                ],
            ],
            [
                'providerData' => [
                    $this->getLineItemData($productFree, 42, 'PRO', 'in_stock', 0, 'litre', Price::create(10, 'USD')),
                    $this->getLineItemData($productFree, 42, 'PRO', 'in_stock', 10, 'litre', Price::create(10, 'USD')),
                    $this->getLineItemData($hasProduct, 42, 'PRO', 'in_stock', 10, 'litre', null),
                    $this->getLineItemData($hasProduct, 42, 'PRO', 'in_stock', 10, 'litre', Price::create(10, 'UAH')),
                    $this->getLineItemData(
                        $hasProduct,
                        42,
                        'PRO',
                        'out_of_stock',
                        10,
                        'litre',
                        Price::create(10, 'USD')
                    ),
                ],
                'expectedData' => [
                    $this->getLineItemData($productFree, 42, 'PRO', 'in_stock', 0, 'litre', Price::create(10, 'USD')),
                ],
            ],
        ];
    }
}
