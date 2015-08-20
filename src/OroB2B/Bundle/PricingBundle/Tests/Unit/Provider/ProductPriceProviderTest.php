<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Unit\Provider;

use Doctrine\Common\Persistence\ManagerRegistry;

use Oro\Bundle\CurrencyBundle\Model\Price;
use OroB2B\Bundle\PricingBundle\Entity\ProductPrice;
use OroB2B\Bundle\PricingBundle\Provider\ProductPriceProvider;
use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\ProductBundle\Entity\ProductUnit;

class ProductPriceProviderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ProductPriceProvider
     */
    protected $provider;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|ManagerRegistry
     */
    protected $registry;

    protected function setUp()
    {
        $this->registry = $this->getMock('Doctrine\Common\Persistence\ManagerRegistry');

        $this->provider = new ProductPriceProvider($this->registry, '\stdClass');
    }

    protected function tearDown()
    {
        unset($this->provider, $this->registry);
    }

    /**
     * @dataProvider getAvailableCurrenciesDataProvider
     * @param int $priceListId
     * @param array $productIds
     * @param array $prices
     * @param array $expectedData
     */
    public function testGetAvailableCurrencies($priceListId, array $productIds, array $prices, array $expectedData)
    {
        $repository = $this->getMockBuilder('OroB2B\Bundle\PricingBundle\Entity\Repository\ProductPriceRepository')
            ->disableOriginalConstructor()
            ->getMock();

        $repository->expects($this->once())
            ->method('findByPriceListIdAndProductIds')
            ->with($priceListId, $productIds, true, null)
            ->willReturn($prices);

        $manager = $this->getMockBuilder('Doctrine\Common\Persistence\ObjectManager')
            ->disableOriginalConstructor()
            ->getMock();

        $manager->expects($this->once())
            ->method('getRepository')
            ->with($this->equalTo('\stdClass'))
            ->willReturn($repository);

        $this->registry->expects($this->once())
            ->method('getManagerForClass')
            ->with($this->equalTo('\stdClass'))
            ->will($this->returnValue($manager));

        $this->assertEquals(
            $expectedData,
            $this->provider->getPriceByPriceListIdAndProductIds($priceListId, $productIds)
        );
    }

    /**
     * @return array
     */
    public function getAvailableCurrenciesDataProvider()
    {
        return [
            'with prices' => [
                'priceListId' => 1,
                'productIds' => [1, 2],
                'prices' => [
                    $this->createPrice(1, 'item', 1, 100.0000, 'USD'),
                    $this->createPrice(1, 'kg', 1, 20.0000, 'USD'),
                    $this->createPrice(1, 'kg', 10, 15.0000, 'USD'),
                    $this->createPrice(2, 'kg', 3, 50.0000, 'EUR')
                ],
                'expectedData' => [
                    '1' => [
                        'item' => [
                            [
                                'price' => '100.0000',
                                'currency' => 'USD',
                                'qty' => 1
                            ]
                        ],
                        'kg' => [
                            [
                                'price' => '20.0000',
                                'currency' => 'USD',
                                'qty' => 1
                            ],
                            [
                                'price' => '15.0000',
                                'currency' => 'USD',
                                'qty' => 10
                            ]
                        ]
                    ],
                    '2' => [
                        'kg' => [
                            [
                                'price' => '50.0000',
                                'currency' => 'EUR',
                                'qty' => 3
                            ]
                        ]
                    ],
                ]
            ],
            'without prices' => [
                'priceListId' => 1,
                'productIds' => [1, 2],
                'prices' => [],
                'expectedData' => []
            ]
        ];
    }

    /**
     * @param int $productId
     * @param string $unitCode
     * @param int $quantity
     * @param float $value
     * @param string $currency
     * @return ProductPrice
     */
    protected function createPrice($productId, $unitCode, $quantity, $value, $currency)
    {
        $productPrice = new ProductPrice();

        $price = new Price();
        $price->setCurrency($currency);
        $price->setValue($value);

        $product = new Product();
        $idReflection = new \ReflectionProperty(get_class($product), 'id');
        $idReflection->setAccessible(true);
        $idReflection->setValue($product, $productId);

        $unit = new ProductUnit();
        $unit->setCode($unitCode);

        $productPrice->setProduct($product);
        $productPrice->setUnit($unit);
        $productPrice->setQuantity($quantity);
        $productPrice->setPrice($price);

        return $productPrice;
    }
}
