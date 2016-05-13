<?php

namespace OroB2B\Bundle\CheckoutBundle\Tests\Unit\DataProvider\Manager;

use Doctrine\Common\Collections\ArrayCollection;

use Oro\Component\Testing\Unit\EntityTrait;

use OroB2B\Bundle\CheckoutBundle\DataProvider\Converter\CheckoutLineItemsConverter;
use OroB2B\Bundle\CheckoutBundle\DataProvider\Manager\CheckoutLineItemsManager;
use OroB2B\Bundle\CheckoutBundle\Entity\Checkout;
use OroB2B\Bundle\CheckoutBundle\Entity\CheckoutSource;
use OroB2B\Component\Checkout\DataProvider\CheckoutDataProviderInterface;

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

    protected function setUp()
    {
        $this->checkoutLineItemsConverter = $this
            ->getMockBuilder('OroB2B\Bundle\CheckoutBundle\DataProvider\Converter\CheckoutLineItemsConverter')
            ->disableOriginalConstructor()
            ->getMock();

        $this->checkoutLineItemsConverter->expects($this->any())
            ->method('convert')
            ->will($this->returnCallback(function ($data) {
                $result = new ArrayCollection();
                foreach ($data as $productData) {
                    $result->add($this->getEntity('OroB2B\Bundle\OrderBundle\Entity\OrderLineItem', $productData));
                }
                return $result;
            }));

        $this->checkoutLineItemsManager = new CheckoutLineItemsManager($this->checkoutLineItemsConverter);
    }

    public function testAddProvider()
    {
        /** @var CheckoutDataProviderInterface|\PHPUnit_Framework_MockObject_MockObject $provider */
        $provider = $this->getMock('OroB2B\Component\Checkout\DataProvider\CheckoutDataProviderInterface');

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
        $entity = new \stdClass();
        $data = [];
        if ($withDataProvider) {
            if ($isEntitySupported) {
                $data = [$this->getProductDataWithPrice()];
            }
            $provider = $this->getProvider($entity, $data, $isEntitySupported);

            $this->checkoutLineItemsManager->addProvider($provider);
        }

        $result = $this->checkoutLineItemsManager->getData($this->getCheckout($entity));
        $this->assertEquals($this->checkoutLineItemsConverter->convert($data), $result);
    }

    /**
     * @return array
     */
    public function getDataEntitySupportedDataProvider()
    {
        return [
            'without data providers' => [
                'withDataProvider' => false,
                'isEntitySupported' => false
            ],
            'not supported entity' => [
                'withDataProvider' => true,
                'isEntitySupported' => false
            ],
            'supported entity' => [
                'withDataProvider' => true,
                'isEntitySupported' => true
            ]
        ];
    }

    /**
     * @dataProvider getDataDataProvider
     * @param array $providerData
     * @param bool $disablePriceFilter
     * @param ArrayCollection|array $expectedData
     */
    public function testGetData(array $providerData, $disablePriceFilter, array $expectedData)
    {
        $entity = new \stdClass();

        $this->checkoutLineItemsManager->addProvider($this->getProvider($entity, $providerData));

        $expectedData = $this->checkoutLineItemsConverter->convert($expectedData);
        $actualData = $this->checkoutLineItemsManager->getData($this->getCheckout($entity), $disablePriceFilter);
        $this->assertEquals($expectedData, $actualData);
    }

    /**
     * @return array
     */
    public function getDataDataProvider()
    {
        $productWithPrice = $this->getProductDataWithPrice();
        $productWithoutPrice = $this->getProductDataWithoutPrice();
        return [
            [
                'providerData' => [$productWithPrice, $productWithoutPrice],
                'disablePriceFilter' => false,
                'expectedData' => [$productWithPrice],
            ],
            [
                'providerData' => [$productWithPrice, $productWithoutPrice],
                'disablePriceFilter' => true,
                'expectedData' => [$productWithPrice, $productWithoutPrice],
            ],
        ];
    }

    /**
     * @return array
     */
    protected function getProductDataWithPrice()
    {
        $product = $this->getEntity('OroB2B\Bundle\ProductBundle\Entity\Product', ['sku' => 'PRO']);
        $productUnitLitre = $this->getEntity('OroB2B\Bundle\ProductBundle\Entity\ProductUnit', ['code' => 'litre']);
        return [
            'product' => $product,
            'productSku' => $product->getSku(),
            'quantity' => 10,
            'productUnit' => $productUnitLitre,
            'productUnitCode' => $productUnitLitre->getCode(),
            'price' => $this->getEntity('Oro\Bundle\CurrencyBundle\Entity\Price')
        ];
    }

    /**
     * @return array
     */
    protected function getProductDataWithoutPrice()
    {
        $product = $this->getEntity('OroB2B\Bundle\ProductBundle\Entity\Product', ['sku' => 'PRO2']);
        $productUnitEa = $this->getEntity('OroB2B\Bundle\ProductBundle\Entity\ProductUnit', ['code' => 'each']);
        return [
            'product' => $product,
            'productSku' => $product->getSku(),
            'quantity' => 5,
            'productUnit' => $productUnitEa,
            'productUnitCode' => $productUnitEa->getCode(),
            'price' => null
        ];
    }

    /**
     * @param \stdClass $entity
     * @param array $returnData
     * @param bool $isSupported
     * @return CheckoutDataProviderInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function getProvider($entity, array $returnData, $isSupported = true)
    {
        /** @var CheckoutDataProviderInterface|\PHPUnit_Framework_MockObject_MockObject $provider */
        $provider = $this->getMock('OroB2B\Component\Checkout\DataProvider\CheckoutDataProviderInterface');

        $provider->expects($this->once())
            ->method('isEntitySupported')
            ->with(new \stdClass())
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
     * @param \stdClass $entity
     * @return Checkout
     */
    protected function getCheckout($entity)
    {
        /** @var CheckoutSource|\PHPUnit_Framework_MockObject_MockObject $checkoutSource */
        $checkoutSource = $this->getMock('OroB2B\Bundle\CheckoutBundle\Entity\CheckoutSource');
        $checkoutSource->expects($this->once())
            ->method('getEntity')
            ->willReturn($entity);

        $checkout = new Checkout();
        $checkout->setSource($checkoutSource);

        return $checkout;
    }
}
