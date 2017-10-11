<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\DataProvider\Manager;

use Doctrine\Common\Collections\ArrayCollection;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Component\Testing\Unit\Entity\Stub\StubEnumValue;
use Oro\Component\Testing\Unit\EntityTrait;
use Oro\Bundle\CheckoutBundle\DataProvider\Converter\CheckoutLineItemsConverter;
use Oro\Bundle\CheckoutBundle\DataProvider\Manager\CheckoutLineItemsManager;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\PricingBundle\Manager\UserCurrencyManager;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Tests\Unit\Entity\Stub\Product;
use Oro\Component\Checkout\DataProvider\CheckoutDataProviderInterface;

class CheckoutLineItemsManagerTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    /**
     * @var CheckoutLineItemsConverter|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $checkoutLineItemsConverter;

    /**
     * @var CheckoutLineItemsManager
     */
    protected $checkoutLineItemsManager;

    /**
     * @var UserCurrencyManager
     */
    protected $currencyManager;

    /**
     * @var ConfigManager|\PHPUnit_Framework_MockObject_MockObject
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

        $this->checkoutLineItemsManager = new CheckoutLineItemsManager(
            $this->checkoutLineItemsConverter,
            $this->currencyManager,
            $this->configManager
        );
    }

    public function testAddProvider()
    {
        /** @var CheckoutDataProviderInterface|\PHPUnit_Framework_MockObject_MockObject $provider */
        $provider = $this->createMock('Oro\Component\Checkout\DataProvider\CheckoutDataProviderInterface');

        $this->checkoutLineItemsManager->addProvider($provider);

        $this->assertAttributeSame(
            [$provider],
            'providers',
            $this->checkoutLineItemsManager
        );
    }

    /**
     * @dataProvider getDataDataProvider
     * @param bool $withDataProvider
     * @param bool $isEntitySupported
     */
    public function testGetDataEntitySupported($withDataProvider, $isEntitySupported)
    {
        $this->configManager->expects($this->any())
            ->method('get')
            ->with('oro_order.frontend_product_visibility')
            ->willReturn(['in_stock']);

        $checkout = $this->getCheckout();
        $data = [];

        if ($withDataProvider) {
            if ($isEntitySupported) {
                $data = [$this->getLineItemData(true, 'PRO', 'in_stock', 10, 'litre', Price::create(10, 'USD'))];
            }
            $provider = $this->getProvider($checkout, $data, $isEntitySupported);

            $this->checkoutLineItemsManager->addProvider($provider);
        }

        $result = $this->checkoutLineItemsManager->getData($checkout);
        $this->assertEquals($this->checkoutLineItemsConverter->convert($data), $result);
    }

    /**
     * @dataProvider getDataDataProvider
     * @param array $providerData
     * @param bool $disablePriceFilter
     * @param ArrayCollection|array $expectedData
     */
    public function testGetData(array $providerData, $disablePriceFilter, array $expectedData)
    {
        $this->configManager->expects($this->any())
            ->method('get')
            ->with('oro_order.frontend_product_visibility')
            ->willReturn(['in_stock']);

        $checkout = $this->getCheckout();

        $this->checkoutLineItemsManager->addProvider($this->getProvider($checkout, $providerData));

        $expectedData = $this->checkoutLineItemsConverter->convert($expectedData);
        $actualData = $this->checkoutLineItemsManager->getData($checkout, $disablePriceFilter);
        $this->assertEquals($expectedData, $actualData);
    }

    /**
     * @return array
     */
    public function getDataDataProvider()
    {
        $hasProduct = true;
        $productFree = false;
        return [
            [
                'providerData' => [
                    $this->getLineItemData($hasProduct, 'PRO', 'in_stock', 10, 'litre', Price::create(10, 'USD')),
                    $this->getLineItemData($hasProduct, 'PRO', 'in_stock', 10, 'litre', Price::create(0, 'USD')),
                    $this->getLineItemData($productFree, 'PRO', 'in_stock', 10, 'litre', Price::create(10, 'USD')),
                    $this->getLineItemData($hasProduct, 'PRO', 'in_stock', 10, 'litre', null),
                    $this->getLineItemData($hasProduct, 'PRO', 'out_of_stock', 10, 'litre', null),
                    $this->getLineItemData($hasProduct, 'PRO', 'in_stock', 10, 'litre', Price::create(10, 'UAH')),
                    $this->getLineItemData($hasProduct, 'PRO', null, 10, 'litre', Price::create(10, 'USD')),
                    $this->getLineItemData($hasProduct, 'PRO', 'out_of_stock', 10, 'litre', Price::create(10, 'USD')),
                ],
                'disablePriceFilter' => false,
                'expectedData' => [
                    $this->getLineItemData($hasProduct, 'PRO', 'in_stock', 10, 'litre', Price::create(10, 'USD')),
                    $this->getLineItemData($hasProduct, 'PRO', 'in_stock', 10, 'litre', Price::create(0, 'USD')),
                    $this->getLineItemData($productFree, 'PRO', 'in_stock', 10, 'litre', Price::create(10, 'USD')),
                ],
            ],
            [
                'providerData' => [
                    $this->getLineItemData($hasProduct, 'PRO', 'in_stock', 10, 'litre', Price::create(10, 'USD')),
                    $this->getLineItemData($hasProduct, 'PRO', 'in_stock', 10, 'litre', null),
                    $this->getLineItemData($hasProduct, 'PRO', 'out_of_stock', 10, 'litre', null),
                    $this->getLineItemData($hasProduct, 'PRO', 'in_stock', 10, 'litre', Price::create(10, 'UAH')),
                    $this->getLineItemData($hasProduct, 'PRO', null, 10, 'litre', Price::create(10, 'USD')),
                    $this->getLineItemData($hasProduct, 'PRO', 'out_of_stock', 10, 'litre', Price::create(10, 'USD')),
                    $this->getLineItemData($productFree, 'PRO', 'in_stock', 10, 'litre', Price::create(10, 'USD')),
                ],
                'disablePriceFilter' => true,
                'expectedData' => [
                    $this->getLineItemData($hasProduct, 'PRO', 'in_stock', 10, 'litre', Price::create(10, 'USD')),
                    $this->getLineItemData($hasProduct, 'PRO', 'in_stock', 10, 'litre', null),
                    $this->getLineItemData($hasProduct, 'PRO', 'out_of_stock', 10, 'litre', null),
                    $this->getLineItemData($hasProduct, 'PRO', 'in_stock', 10, 'litre', Price::create(10, 'UAH')),
                    $this->getLineItemData($hasProduct, 'PRO', null, 10, 'litre', Price::create(10, 'USD')),
                    $this->getLineItemData($hasProduct, 'PRO', 'out_of_stock', 10, 'litre', Price::create(10, 'USD')),
                    $this->getLineItemData($productFree, 'PRO', 'in_stock', 10, 'litre', Price::create(10, 'USD')),
                ],
            ],
        ];
    }

    /**
     * @param bool $hasProduct
     * @param string|null $productSku
     * @param string|null $inventoryStatus
     * @param float $qty
     * @param string $unit
     * @param Price|null $price
     * @return array
     */
    protected function getLineItemData($hasProduct, $productSku, $inventoryStatus, $qty, $unit, Price $price = null)
    {
        $product = null;
        if ($hasProduct && $productSku) {
            $product = new Product();
            $product->setSku($productSku);

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
     * @return CheckoutDataProviderInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function getProvider(Checkout $entity, array $returnData, $isSupported = true)
    {
        /** @var CheckoutDataProviderInterface|\PHPUnit_Framework_MockObject_MockObject $provider */
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
}
