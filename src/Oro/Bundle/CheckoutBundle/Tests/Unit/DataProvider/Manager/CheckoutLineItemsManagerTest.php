<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\DataProvider\Manager;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Oro\Bundle\CacheBundle\Tests\Unit\Provider\MemoryCacheProviderAwareTestTrait;
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
use Oro\Component\Testing\Unit\EntityTrait;

class CheckoutLineItemsManagerTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;
    use MemoryCacheProviderAwareTestTrait;

    /**
     * @var CheckoutLineItemsConverter|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $checkoutLineItemsConverter;

    /**
     * @var UserCurrencyManager
     */
    protected $currencyManager;

    /**
     * @var ConfigManager|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $configManager;

    protected function setUp(): void
    {
        $this->checkoutLineItemsConverter = $this->createMock(CheckoutLineItemsConverter::class);

        $this->currencyManager = $this->createMock(UserCurrencyManager::class);
        $this->currencyManager->expects($this->any())->method('getUserCurrency')->willReturn('USD');

        $this->checkoutLineItemsConverter->expects($this->any())
            ->method('convert')
            ->willReturnCallback(
                function ($data) {
                    $result = new ArrayCollection();
                    foreach ($data as $productData) {
                        $result->add($this->getEntity(OrderLineItem::class, $productData));
                    }

                    return $result;
                }
            );
        $this->configManager = $this->createMock(ConfigManager::class);
    }

    /**
     * @param CheckoutDataProviderInterface[] $providers
     *
     * @return CheckoutLineItemsManager
     */
    private function getCheckoutLineItemsManager(array $providers)
    {
        $checkoutLineItemsManager = new CheckoutLineItemsManager(
            $providers,
            $this->checkoutLineItemsConverter,
            $this->currencyManager,
            $this->configManager
        );

        $this->setMemoryCacheProvider($checkoutLineItemsManager);

        return $checkoutLineItemsManager;
    }

    public function testGetDataWhenCache(): void
    {
        $checkout = $this->createMock(Checkout::class);
        $disablePriceFilter = false;
        $configVisibilityPath = 'oro_order.frontend_product_visibility';
        $lineItems = $this->createMock(Collection::class);

        $this->mockMemoryCacheProvider($lineItems);

        $this->assertEquals(
            $lineItems,
            $this->getCheckoutLineItemsManager([])->getData($checkout, $disablePriceFilter, $configVisibilityPath)
        );
    }

    /**
     * @dataProvider getDataDataProvider
     *
     * @param bool $withDataProvider
     * @param bool $isEntitySupported
     * @param bool $visible
     */
    public function testGetDataEntitySupported($withDataProvider, $isEntitySupported, $visible)
    {
        $this->mockMemoryCacheProvider();

        $this->configManager->expects($this->any())
            ->method('get')
            ->with('oro_order.frontend_product_visibility')
            ->willReturn(['in_stock']);

        $checkout = $this->getCheckout();
        $data = [];

        $providers = [];
        if ($withDataProvider) {
            if ($isEntitySupported && $visible) {
                $data = [$this->getLineItemData(true, 42, 'PRO', 'in_stock', 10, 'litre', Price::create(10, 'USD'))];
            }
            $providers[] = $this->getProvider($checkout, $data, $isEntitySupported);
        }

        $checkoutLineItemsManager = $this->getCheckoutLineItemsManager($providers);
        $result = $checkoutLineItemsManager->getData($checkout);
        $this->assertEquals($this->checkoutLineItemsConverter->convert($data), $result);
    }

    /**
     * @dataProvider getDataDataProvider
     * @param array $providerData
     * @param bool $disablePriceFilter
     * @param bool $visible
     * @param ArrayCollection|array $expectedData
     */
    public function testGetData(array $providerData, $disablePriceFilter, $visible, array $expectedData)
    {
        $this->mockMemoryCacheProvider();

        $this->configManager->expects($this->any())
            ->method('get')
            ->with('oro_order.frontend_product_visibility')
            ->willReturn(['in_stock']);

        $checkout = $this->getCheckout();

        $checkoutLineItemsManager = $this->getCheckoutLineItemsManager(
            [$this->getProvider($checkout, $providerData)]
        );

        $expectedData = $this->checkoutLineItemsConverter->convert($expectedData);
        $actualData = $checkoutLineItemsManager->getData($checkout, $disablePriceFilter);
        $this->assertEquals($expectedData, $actualData);
    }

    /**
     * @return array
     *
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function getDataDataProvider()
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

    /**
     * @param bool $hasProduct
     * @param integer $productId
     * @param string|null $productSku
     * @param string|null $inventoryStatus
     * @param float $qty
     * @param string $unit
     * @param Price|null $price
     * @param string $status
     * @return array
     */
    protected function getLineItemData(
        $hasProduct,
        $productId,
        $productSku,
        $inventoryStatus,
        $qty,
        $unit,
        Price $price = null,
        $status = Product::STATUS_ENABLED
    ) {
        $product = null;
        if ($hasProduct && $productSku) {
            $product = $this->getEntity(
                Product::class,
                [
                    'id' => $productId,
                    'sku' => $productSku,
                    'status' => $status
                ]
            );

            if ($inventoryStatus) {
                $inventoryStatus = new TestEnumValue($inventoryStatus, $inventoryStatus);
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

    /**
     * @param Checkout $entity
     * @param array $returnData
     * @param bool $isSupported
     * @return CheckoutDataProviderInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected function getProvider(Checkout $entity, array $returnData, $isSupported = true)
    {
        /** @var CheckoutDataProviderInterface|\PHPUnit\Framework\MockObject\MockObject $provider */
        $provider = $this->createMock('Oro\Component\Checkout\DataProvider\CheckoutDataProviderInterface');

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
     * @return Checkout
     */
    protected function getCheckout()
    {
        return new Checkout();
    }

    /**
     * @dataProvider getLineItemsWithoutQuantityDataProvider
     * @param array $providerData
     * @param bool $visible
     * @param ArrayCollection|array $expectedData
     */
    public function testGetLineItemsWithoutQuantity(array $providerData, $visible, array $expectedData)
    {
        $this->mockMemoryCacheProvider();

        $this->configManager->expects($this->any())
            ->method('get')
            ->with('oro_order.frontend_product_visibility')
            ->willReturn(['in_stock']);

        $checkout = $this->getCheckout();

        $checkoutLineItemsManager = $this->getCheckoutLineItemsManager(
            [$this->getProvider($checkout, $providerData)]
        );

        $expectedData = $this->checkoutLineItemsConverter->convert($expectedData);
        $actualData = $checkoutLineItemsManager->getLineItemsWithoutQuantity($checkout);
        $this->assertEquals($expectedData, $actualData);
    }

    /**
     * @return array
     */
    public function getLineItemsWithoutQuantityDataProvider()
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
                'visible' => true,
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
                'visible' => true,
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
                'visible' => false,
                'expectedData' => [
                    $this->getLineItemData($productFree, 42, 'PRO', 'in_stock', 0, 'litre', Price::create(10, 'USD')),
                ],
            ],
        ];
    }
}
