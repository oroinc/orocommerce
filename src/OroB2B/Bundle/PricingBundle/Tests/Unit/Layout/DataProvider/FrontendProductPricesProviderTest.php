<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Unit\Layout\DataProvider;

use Oro\Component\Layout\LayoutContext;
use Oro\Component\Testing\Unit\EntityTrait;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;

use OroB2B\Bundle\PricingBundle\Layout\DataProvider\FrontendProductPricesProvider;
use OroB2B\Bundle\PricingBundle\Model\PriceListRequestHandler;
use OroB2B\Bundle\PricingBundle\Manager\UserCurrencyManager;

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
        $prices = [1 => 'test', 2 => 'test2'];
        $priceListId = 23;
        $priceList = $this->getEntity('OroB2B\Bundle\PricingBundle\Entity\PriceList', ['id' => $priceListId]);
        $productId = 24;
        $product =  $this->getMockBuilder('OroB2B\Bundle\ProductBundle\Entity\Product')
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
        $this->assertEquals(1, count($actual));
        $this->assertEquals('each', current($actual)->getUnit());
    }

    /**
     * @param string $unitCode
     * @param boolen $sell
     * @return productUnitPresion
     */
    private function createUnitPrecision($unitCode, $sell)
    {
        $p = $this->getMockBuilder('OroB2B\Bundle\ProductBundle\Entity\ProductUnionPrecision')
            ->setMethods(array('isSell', 'getUnit'))
            ->disableOriginalConstructor()
            ->getMock();

        $p->expects($this->once())
            ->method('isSell')
            ->willReturn($sell);

        $p->expects($this->any())
           ->method('getUnit')
           ->willReturn($unitCode);

        return $p;
    }

    /**
     * @param string $unit
     * @param Product $product
     * @return CombinedProductPrice
     */
    private function createProductPrice($unit, $product)
    {
        $p = $this->getMockBuilder(
            'OroB2B\Bundle\PricingBundle\Entity\CombinedProductPrice',
            array('getUnit', 'getProduct')
        )
            ->disableOriginalConstructor()
            ->getMock();

        $p->expects($this->any())
            ->method('getProduct')
            ->willReturn($product);

        $p->expects($this->any())
            ->method('getUnit')
            ->willReturn($unit);

        return $p;
    }
}
