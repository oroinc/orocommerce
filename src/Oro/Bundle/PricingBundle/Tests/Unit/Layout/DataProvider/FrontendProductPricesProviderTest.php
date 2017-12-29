<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Layout\DataProvider;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\PricingBundle\Entity\CombinedProductPrice;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Entity\Repository\ProductPriceRepository;
use Oro\Bundle\PricingBundle\Formatter\ProductPriceFormatter;
use Oro\Bundle\PricingBundle\Layout\DataProvider\FrontendProductPricesProvider;
use Oro\Bundle\PricingBundle\Manager\UserCurrencyManager;
use Oro\Bundle\PricingBundle\Model\PriceListRequestHandler;
use Oro\Bundle\PricingBundle\Sharding\ShardManager;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Entity\ProductUnitPrecision;
use Oro\Bundle\ProductBundle\Provider\ProductVariantAvailabilityProvider;
use Oro\Component\Testing\Unit\EntityTrait;

class FrontendProductPricesProviderTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    /**
     * @var ShardManager|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $shardManager;

    /**
     * @var FrontendProductPricesProvider
     */
    protected $provider;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|DoctrineHelper
     */
    protected $doctrineHelper;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|PriceListRequestHandler
     */
    protected $priceListRequestHandler;

    /**
     * @var UserCurrencyManager|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $userCurrencyManager;

    /**
     * @var ProductPriceFormatter|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $productPriceFormatter;

    /**
     * @var ProductVariantAvailabilityProvider|\PHPUnit_Framework_MockObject_MockObject
     */
    private $productVariantAvailabilityProvider;

    public function setUp()
    {
        $this->doctrineHelper = $this->getMockBuilder('Oro\Bundle\EntityBundle\ORM\DoctrineHelper')
            ->disableOriginalConstructor()
            ->getMock();
        $this->priceListRequestHandler = $this
            ->getMockBuilder('Oro\Bundle\PricingBundle\Model\PriceListRequestHandler')
            ->disableOriginalConstructor()
            ->getMock();
        $this->userCurrencyManager = $this
            ->getMockBuilder('Oro\Bundle\PricingBundle\Manager\UserCurrencyManager')
            ->disableOriginalConstructor()
            ->getMock();
        $this->productPriceFormatter = $this
            ->getMockBuilder('Oro\Bundle\PricingBundle\Formatter\ProductPriceFormatter')
            ->disableOriginalConstructor()
            ->getMock();

        $this->productVariantAvailabilityProvider = $this->createMock(ProductVariantAvailabilityProvider::class);
        $this->shardManager = $this->createMock(ShardManager::class);

        $this->provider = new FrontendProductPricesProvider(
            $this->doctrineHelper,
            $this->priceListRequestHandler,
            $this->productVariantAvailabilityProvider,
            $this->userCurrencyManager,
            $this->productPriceFormatter,
            $this->shardManager
        );
    }

    public function testGetByProduct()
    {
        $priceList = $this->getEntity(PriceList::class, ['id' => 23]);

        $unitPrecisions[] = $this->createUnitPrecision('each', true);
        $unitPrecisions[] = $this->createUnitPrecision('set', false);

        $product = $this->getEntity(Product::class, ['id' => 24, 'unitPrecisions' => $unitPrecisions]);
        $price = $this->getEntity(Price::class);

        $productPrice1 = $this->createProductPrice('each', $product, $price);
        $productPrice2 = $this->createProductPrice('set', $product, $price);
        $prices = [$productPrice1, $productPrice2];

        $priceSorting = ['unit' => 'ASC', 'currency' => 'DESC', 'quantity' => 'ASC'];

        $repo = $this->getMockBuilder(ProductPriceRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $repo->expects($this->once())
            ->method('findByPriceListIdAndProductIds')
            ->with($this->shardManager, $priceList->getId(), [$product->getId()], true, 'EUR', null, $priceSorting)
            ->willReturn($prices);

        $this->doctrineHelper->expects($this->once())
            ->method('getEntityRepository')
            ->with('OroPricingBundle:CombinedProductPrice')
            ->willReturn($repo);

        $this->priceListRequestHandler->expects($this->once())
            ->method('getPriceListByCustomer')
            ->willReturn($priceList);

        $productPrices = [ '24' => [
            'each' => ['qty' => null, 'price' => null, 'currency' => null, 'unit' => 'each'],
            'set' => ['qty' => null, 'price' => null, 'currency' => null, 'unit' => 'set'],
            ]
        ];

        $this->productPriceFormatter->expects($this->once())
            ->method('formatProducts')
            ->willReturn($productPrices);

        $this->userCurrencyManager->expects($this->once())
            ->method('getUserCurrency')
            ->willReturn('EUR');

        $actual = $this->provider->getByProduct($product);

        $this->assertInternalType('array', $actual);
        $this->assertCount(1, $actual);
        $this->assertEquals('each', current($actual)['unit']);
    }

    /**
     * @param string $unitCode
     * @param boolean $sell
     * @return ProductUnitPrecision
     */
    private function createUnitPrecision($unitCode, $sell)
    {
        $productUnitPrecision = new ProductUnitPrecision();
        $productUnitPrecision->setSell($sell);
        $productUnitPrecision->setUnit($this->getUnit($unitCode));

        return $productUnitPrecision;
    }

    /**
     * @param string $unit
     * @param Product $product
     * @param Price $price
     * @return CombinedProductPrice
     */
    private function createProductPrice($unit, $product, $price)
    {
        $combinedProductPrice = new CombinedProductPrice();
        $combinedProductPrice->setProduct($product);
        $combinedProductPrice->setUnit($this->getUnit($unit));
        $combinedProductPrice->setPrice($price);

        return $combinedProductPrice;
    }

    /**
     * @param string $unitCode
     * @return ProductUnit
     */
    private function getUnit($unitCode)
    {
        $unit = new ProductUnit();
        $unit->setCode($unitCode);

        return $unit;
    }
}
