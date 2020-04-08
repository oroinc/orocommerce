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
use Oro\Bundle\PricingBundle\Manager\UserCurrencyManager;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Tests\Unit\Entity\Stub\Product;
use Oro\Component\Checkout\DataProvider\CheckoutDataProviderInterface;
use Oro\Component\Testing\Unit\Entity\Stub\StubEnumValue;
use Oro\Component\Testing\Unit\EntityTrait;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class CheckoutLineItemsManagerTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;
    use MemoryCacheProviderAwareTestTrait;

    /**
     * @var CheckoutLineItemsConverter|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $checkoutLineItemsConverter;

    /**
     * @var AuthorizationCheckerInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $authorizationChecker;

    /**
     * @var UserCurrencyManager
     */
    protected $currencyManager;

    /**
     * @var ConfigManager|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $configManager;

    protected function setUp()
    {
        $this->checkoutLineItemsConverter = $this->createMock(CheckoutLineItemsConverter::class);

        $this->currencyManager = $this->createMock(UserCurrencyManager::class);
        $this->currencyManager->expects($this->any())->method('getUserCurrency')->willReturn('USD');

        $this->checkoutLineItemsConverter->expects($this->any())
            ->method('convert')
            ->will($this->returnCallback(function ($data) {
                $result = new ArrayCollection();
                foreach ($data as $productData) {
                    $result->add($this->getEntity('Oro\Bundle\OrderBundle\Entity\OrderLineItem', $productData));
                }
                return $result;
            }));
        $this->configManager = $this->createMock(ConfigManager::class);
        $this->authorizationChecker = $this->createMock(AuthorizationCheckerInterface::class);
    }

    /**
     * @param CheckoutDataProviderInterface[] $providers
     *
     * @return CheckoutLineItemsManager
     */
    private function getCheckoutLineItemsManager(array $providers)
    {
        return new CheckoutLineItemsManager(
            $providers,
            $this->checkoutLineItemsConverter,
            $this->currencyManager,
            $this->configManager,
            $this->authorizationChecker
        );
    }

    public function testGetDataWhenCache(): void
    {
        $checkout = $this->createMock(Checkout::class);
        $disablePriceFilter = false;
        $configVisibilityPath = 'oro_order.frontend_product_visibility';
        $lineItems = $this->createMock(Collection::class);

        $this->mockMemoryCacheProvider($lineItems);

        $checkoutLineItemsManager = $this->getCheckoutLineItemsManager([]);
        $this->setMemoryCacheProvider($checkoutLineItemsManager);

        $this->assertEquals(
            $lineItems,
            $checkoutLineItemsManager->getData($checkout, $disablePriceFilter, $configVisibilityPath)
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
        $this->configManager->expects($this->exactly(2))
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
        $expectedData = $this->checkoutLineItemsConverter->convert($data);

        $this->assertEquals($expectedData, $checkoutLineItemsManager->getData($checkout));

        $this->mockMemoryCacheProvider();
        $this->setMemoryCacheProvider($checkoutLineItemsManager);

        $this->assertEquals($expectedData, $checkoutLineItemsManager->getData($checkout));
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
        $this->configManager->expects($this->exactly(2))
            ->method('get')
            ->with('oro_order.frontend_product_visibility')
            ->willReturn(['in_stock']);

        $checkout = $this->getCheckout();

        $checkoutLineItemsManager = $this->getCheckoutLineItemsManager(
            [$this->getProvider($checkout, $providerData)]
        );

        $expectedData = $this->checkoutLineItemsConverter->convert($expectedData);

        $this->assertEquals($expectedData, $checkoutLineItemsManager->getData($checkout, $disablePriceFilter));

        $this->mockMemoryCacheProvider();
        $this->setMemoryCacheProvider($checkoutLineItemsManager);

        $this->assertEquals($expectedData, $checkoutLineItemsManager->getData($checkout, $disablePriceFilter));
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
                $inventoryStatus = new StubEnumValue($inventoryStatus, $inventoryStatus);
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
        $provider = $this->createMock(CheckoutDataProviderInterface::class);

        $provider->expects($this->atLeastOnce())
            ->method('isEntitySupported')
            ->with($entity)
            ->willReturn($isSupported);

        if ($isSupported) {
            $provider->expects($this->atLeastOnce())
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
        $this->configManager->expects($this->exactly(2))
            ->method('get')
            ->with('oro_order.frontend_product_visibility')
            ->willReturn(['in_stock']);

        $checkout = $this->getCheckout();

        $checkoutLineItemsManager = $this->getCheckoutLineItemsManager(
            [$this->getProvider($checkout, $providerData)]
        );

        $expectedData = $this->checkoutLineItemsConverter->convert($expectedData);
        $this->assertEquals($expectedData, $checkoutLineItemsManager->getLineItemsWithoutQuantity($checkout));

        $this->mockMemoryCacheProvider();
        $this->setMemoryCacheProvider($checkoutLineItemsManager);

        $this->assertEquals($expectedData, $checkoutLineItemsManager->getLineItemsWithoutQuantity($checkout));
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
