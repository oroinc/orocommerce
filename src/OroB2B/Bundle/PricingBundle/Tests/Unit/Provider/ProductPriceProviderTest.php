<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Unit\Provider;

use Doctrine\Common\Persistence\ManagerRegistry;

use Oro\Bundle\CurrencyBundle\Model\Price;

use OroB2B\Bundle\PricingBundle\Entity\ProductPrice;
use OroB2B\Bundle\PricingBundle\Model\FrontendPriceListRequestHandler;
use OroB2B\Bundle\PricingBundle\Model\ProductPriceCriteria;
use OroB2B\Bundle\PricingBundle\Provider\ProductPriceProvider;
use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\ProductBundle\Entity\ProductUnit;

class ProductPriceProviderTest extends \PHPUnit_Framework_TestCase
{
    const CLASS_NAME = '\stdClass';

    /**
     * @var ProductPriceProvider
     */
    protected $provider;

    /**
     * @var  \PHPUnit_Framework_MockObject_MockObject|FrontendPriceListRequestHandler
     */
    protected $requestHandler;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|ManagerRegistry
     */
    protected $registry;

    protected function setUp()
    {
        $this->registry = $this->getMock('Doctrine\Common\Persistence\ManagerRegistry');

        $this->requestHandler = $this
            ->getMockBuilder('OroB2B\Bundle\PricingBundle\Model\FrontendPriceListRequestHandler')
            ->disableOriginalConstructor()
            ->getMock();

        $this->provider = new ProductPriceProvider($this->registry, $this->requestHandler, self::CLASS_NAME);
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

    /**
     * @dataProvider getMatchedPricesDataProvider
     *
     * @param array $productPriceCriteria
     * @param bool $withPriceList
     * @param array $repositoryData
     * @param array $expectedData
     */
    public function testGetMatchedPrices(
        array $productPriceCriteria,
        $withPriceList,
        array $repositoryData,
        array $expectedData
    ) {
        $priceList = $this->getEntity('OroB2B\Bundle\PricingBundle\Entity\PriceList', 12);

        if ($withPriceList) {
            $this->requestHandler->expects($this->never())->method('getPriceList');
        } else {
            $this->requestHandler->expects($this->once())->method('getPriceList')->willReturn($priceList);
        }

        $repository = $this->getMockBuilder('OroB2B\Bundle\PricingBundle\Entity\Repository\ProductPriceRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $repository->expects($this->once())->method('getPricesBatch')->willReturn($repositoryData);

        $em = $this->getMockBuilder('Doctrine\ORM\EntityManager')->disableOriginalConstructor()->getMock();
        $em->expects($this->once())
            ->method('getRepository')
            ->with(self::CLASS_NAME)
            ->willReturn($repository);

        $this->registry->expects($this->once())
            ->method('getManagerForClass')
            ->with(self::CLASS_NAME)
            ->willReturn($em);

        $prices = $this->provider->getMatchedPrices(
            $productPriceCriteria,
            $withPriceList ? $priceList : null
        );

        $this->assertInternalType('array', $prices);
        $this->assertEquals(count($productPriceCriteria), count($prices));
        $this->assertEquals($expectedData, $prices);
    }

    /**
     * @return array
     */
    public function getMatchedPricesDataProvider()
    {
        $currency = 'USD';
        $prodUnitQty1 = $this->getProductPriceCriteria(1, $currency);
        $prodUnitQty105 = $this->getProductPriceCriteria(10.5, $currency);
        $prodUnitQty50 = $this->getProductPriceCriteria(50, $currency);
        $prodUnitQty200 = $this->getProductPriceCriteria(200, $currency);
        $prodUnitQty01 = $this->getProductPriceCriteria(0.1, $currency);

        $repositoryData = $this->getRepositoryData($prodUnitQty50);

        return [
            'with priceList' => [
                'productPriceCriteria' => [$prodUnitQty1, $prodUnitQty105],
                'withPriceList' => true,
                'repositoryData' => $repositoryData,
                'expectedData' => [
                    $prodUnitQty1->getIdentifier() => null,
                    $prodUnitQty105->getIdentifier() => Price::create(15, $currency),
                ]
            ],
            'without priceList' => [
                'productPriceCriteria' => [$prodUnitQty50, $prodUnitQty200, $prodUnitQty01],
                'withPriceList' => false,
                'repositoryData' => $repositoryData,
                'expectedData' => [
                    $prodUnitQty50->getIdentifier() => Price::create(300, $currency),
                    $prodUnitQty200->getIdentifier() => Price::create(1400, $currency),
                    $prodUnitQty01->getIdentifier() => null,
                ]
            ]
        ];
    }

    /**
     * @param float $quantity
     * @param string $currency
     * @return ProductPriceCriteria
     */
    protected function getProductPriceCriteria($quantity, $currency)
    {
        /** @var Product $product */
        $product = $this->getEntity('OroB2B\Bundle\ProductBundle\Entity\Product', 42);

        $productUnit = new ProductUnit();
        $productUnit->setCode('kg');

        return new ProductPriceCriteria($product, $productUnit, $quantity, $currency);
    }

    /**
     * @param ProductPriceCriteria $productPriceCriteria
     * @return array
     */
    protected function getRepositoryData(ProductPriceCriteria $productPriceCriteria)
    {
        $product = $productPriceCriteria->getProduct();
        $productUnit = $productPriceCriteria->getProductUnit();

        return [
            [
                'id' => $product->getId(),
                'code' => $productUnit->getCode(),
                'quantity' => 1,
                'value' => 20,
                'currency' => 'EUR'
            ],
            [
                'id' => $product->getId(),
                'code' => $productUnit->getCode(),
                'quantity' => 1.5,
                'value' => 15,
                'currency' => 'USD'
            ],
            [
                'id' => $product->getId(),
                'code' => $productUnit->getCode(),
                'quantity' => 20,
                'value' => 300,
                'currency' => 'USD'
            ],
            [
                'id' => $product->getId(),
                'code' => $productUnit->getCode(),
                'quantity' => 100,
                'value' => 1400,
                'currency' => 'USD'
            ]
        ];
    }

    /**
     * @param string $className
     * @param int $id
     * @return object
     */
    protected function getEntity($className, $id)
    {
        $entity = new $className;

        $reflectionClass = new \ReflectionClass($className);
        $method = $reflectionClass->getProperty('id');
        $method->setAccessible(true);
        $method->setValue($entity, $id);

        return $entity;
    }
}
