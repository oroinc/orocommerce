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

        $this->provider = new FrontendProductPricesProvider(
            $this->doctrineHelper,
            $this->priceListRequestHandler,
            $this->userCurrencyManager
        );
    }

    public function testGetProductPrices()
    {
        $priceListId = 23;
        $priceList = $this->getEntity('Oro\Bundle\PricingBundle\Entity\PriceList', ['id' => $priceListId]);
        $productId = 24;
        /** @var Product|\PHPUnit_Framework_MockObject_MockObject $product */
        $product = $this->getMockBuilder('Oro\Bundle\ProductBundle\Entity\Product')
            ->disableOriginalConstructor()
            ->getMock();

        $product->expects($this->once())
            ->method('getId')
            ->willReturn($productId);

        $unitPrecisions[] = $this->createUnitPrecision('each', true);
        $unitPrecisions[] = $this->createUnitPrecision('set', false);

        $product->expects($this->once())
            ->method('getUnitPrecisions')
            ->willReturn($unitPrecisions);

        $productPrice1 = $this->createProductPrice('each', $product);
        $productPrice2 = $this->createProductPrice('set', $product);
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
        $this->userCurrencyManager->expects($this->once())
            ->method('getUserCurrency')
            ->willReturn('EUR');

        $actual = $this->provider->getProductPrices($product);
        $this->assertCount(1, $actual);
        $this->assertEquals('each', current($actual)->getUnit());
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
     * @return CombinedProductPrice
     */
    private function createProductPrice($unit, $product)
    {
        $p = new CombinedProductPrice();
        $p->setProduct($product);
        $p->setUnit($this->getUnit($unit));

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
