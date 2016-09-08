<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Layout\DataProvider;

use Oro\Component\Testing\Unit\EntityTrait;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\PricingBundle\Entity\CombinedProductPrice;
use Oro\Bundle\PricingBundle\Layout\DataProvider\FrontendProductPricesProvider;
use Oro\Bundle\PricingBundle\Model\PriceListRequestHandler;
use Oro\Bundle\PricingBundle\Manager\UserCurrencyManager;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Entity\ProductUnitPrecision;
use Oro\Bundle\PricingBundle\Formatter\ProductPriceFormatter;
use Oro\Bundle\CurrencyBundle\Entity\Price;

class FrontendProductPricesProviderTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

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

        $this->provider = new FrontendProductPricesProvider(
            $this->doctrineHelper,
            $this->priceListRequestHandler,
            $this->userCurrencyManager,
            $this->productPriceFormatter
        );
    }

    public function testGetByProduct()
    {
        $priceListId = 23;
        $priceList = $this->getEntity('Oro\Bundle\PricingBundle\Entity\PriceList', ['id' => $priceListId]);
        $productId = 24;
        /** @var Product|\PHPUnit_Framework_MockObject_MockObject $product */
        $product = $this->getMockBuilder('Oro\Bundle\ProductBundle\Entity\Product')
            ->disableOriginalConstructor()
            ->getMock();

        $product->expects($this->any())
            ->method('getId')
            ->willReturn($productId);

        $unitPrecisions[] = $this->createUnitPrecision('each', true);
        $unitPrecisions[] = $this->createUnitPrecision('set', false);

        $product->expects($this->once())
            ->method('getUnitPrecisions')
            ->willReturn($unitPrecisions);

        $price = $this->getMockBuilder('Oro\Bundle\CurrencyBundle\Entity\Price')
            ->disableOriginalConstructor()
            ->getMock();

        $productPrice1 = $this->createProductPrice('each', $product, $price);
        $productPrice2 = $this->createProductPrice('set', $product, $price);
        $prices = [$productPrice1, $productPrice2];

        $priceSorting = ['unit' => 'ASC', 'currency' => 'DESC', 'quantity' => 'ASC'];

        $repo = $this->getMockBuilder('Oro\Bundle\PricingBundle\Entity\Repository\ProductPriceRepository')
            ->disableOriginalConstructor()
            ->getMock();

        $repo->expects($this->once())
            ->method('findByPriceListIdAndProductIds')
            ->with($priceListId, [$productId], true, 'EUR', null, $priceSorting)
            ->willReturn($prices);

        $this->doctrineHelper->expects($this->once())
            ->method('getEntityRepository')
            ->with('OroPricingBundle:CombinedProductPrice')
            ->willReturn($repo);

        $this->priceListRequestHandler->expects($this->once())
            ->method('getPriceListByAccount')
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
        $p = new ProductUnitPrecision();
        $p->setSell($sell);
        $p->setUnit($this->getUnit($unitCode));

        return $p;
    }

    /**
     * @param string $unit
     * @param Product $product
     * @param Price $price
     * @return CombinedProductPrice
     */
    private function createProductPrice($unit, $product, $price)
    {
        $p = new CombinedProductPrice();
        $p->setProduct($product);
        $p->setUnit($this->getUnit($unit));
        $p->setPrice($price);

        return $p;
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
