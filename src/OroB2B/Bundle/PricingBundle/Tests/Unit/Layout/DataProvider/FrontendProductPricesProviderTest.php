<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Unit\Layout\DataProvider;

use Oro\Component\Layout\LayoutContext;
use Oro\Component\Testing\Unit\EntityTrait;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;

use OroB2B\Bundle\PricingBundle\Entity\CombinedProductPrice;
use OroB2B\Bundle\PricingBundle\Layout\DataProvider\FrontendProductPricesProvider;
use OroB2B\Bundle\PricingBundle\Model\PriceListRequestHandler;
use OroB2B\Bundle\PricingBundle\Manager\UserCurrencyManager;
use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\ProductBundle\Entity\ProductUnit;
use OroB2B\Bundle\ProductBundle\Entity\ProductUnitPrecision;

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
            ->getMockBuilder('OroB2B\Bundle\PricingBundle\Model\PriceListRequestHandler')
            ->disableOriginalConstructor()
            ->getMock();
        $this->userCurrencyManager = $this
            ->getMockBuilder('OroB2B\Bundle\PricingBundle\Manager\UserCurrencyManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->provider = new FrontendProductPricesProvider(
            $this->doctrineHelper,
            $this->priceListRequestHandler,
            $this->userCurrencyManager
        );
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Undefined data item index: product.
     */
    public function testGetDataWithEmptyContext()
    {
        $context = new LayoutContext();
        $this->provider->getData($context);
    }

    public function testGetData()
    {
        $priceListId = 23;
        $priceList = $this->getEntity('OroB2B\Bundle\PricingBundle\Entity\PriceList', ['id' => $priceListId]);
        $productId = 24;
        /** @var Product|\PHPUnit_Framework_MockObject_MockObject $product */
        $product = $this->getMockBuilder('OroB2B\Bundle\ProductBundle\Entity\Product')
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
        $context = new LayoutContext();
        $context->data()->set('product', null, $product);

        $repo = $this->getMockBuilder('OroB2B\Bundle\PricingBundle\Entity\Repository\ProductPriceRepository')
            ->disableOriginalConstructor()
            ->getMock();

        $repo->expects($this->once())
            ->method('findByPriceListIdAndProductIds')
            ->with($priceListId, [$productId], true, 'EUR', null, $priceSorting)
            ->willReturn($prices);

        $this->doctrineHelper->expects($this->once())
            ->method('getEntityRepository')
            ->with('OroB2BPricingBundle:CombinedProductPrice')
            ->willReturn($repo);

        $this->priceListRequestHandler->expects($this->once())
            ->method('getPriceListByAccount')
            ->willReturn($priceList);
        $this->userCurrencyManager->expects($this->once())
            ->method('getUserCurrency')
            ->willReturn('EUR');

        $actual = $this->provider->getData($context);
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
