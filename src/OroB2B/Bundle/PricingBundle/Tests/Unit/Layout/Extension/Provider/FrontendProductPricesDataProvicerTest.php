<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Unit\Layout\Extension\Provider;

use Oro\Component\Layout\LayoutContext;
use Oro\Component\Testing\Unit\EntityTrait;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;

use OroB2B\Bundle\PricingBundle\Model\FrontendPriceListRequestHandler;
use OroB2B\Bundle\PricingBundle\Layout\Extension\Provider\FrontendProductPricesDataProvicer;

class FrontendProductPricesDataProvicerTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    const PRODUCT_ID = 24;

    /** @var FrontendProductPricesDataProvicer */
    protected $provider;

    /** @var \PHPUnit_Framework_MockObject_MockObject|DoctrineHelper */
    protected $doctrineHelper;

    /** @var \PHPUnit_Framework_MockObject_MockObject|FrontendPriceListRequestHandler */
    protected $frontendPriceListRequestHandler;

    public function setUp()
    {
        $this->doctrineHelper = $this->getMockBuilder('Oro\Bundle\EntityBundle\ORM\DoctrineHelper')
            ->disableOriginalConstructor()
            ->getMock();
        $this->frontendPriceListRequestHandler = $this->getMockBuilder(
            'OroB2B\Bundle\PricingBundle\Model\FrontendPriceListRequestHandler'
        )
            ->disableOriginalConstructor()
            ->getMock();

        $this->provider = new FrontendProductPricesDataProvicer(
            $this->doctrineHelper,
            $this->frontendPriceListRequestHandler
        );
    }

    /**
     * @expectedException \BadMethodCallException
     */
    public function testGetIdentifier()
    {
        $this->provider->getIdentifier();
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Context[productId] should be specified.
     */
    public function testGetDataWithEmptyContext()
    {
        $context = new LayoutContext();
        $this->provider->getData($context);
    }

    public function testGetData()
    {
        $prices = [1 => 'test', 2 => 'test2'];
        $priceList = $this->getEntity('OroB2B\Bundle\PricingBundle\Entity\PriceList', ['id'=> 23]);
        $product = $this->getEntity('OroB2B\Bundle\ProductBundle\Entity\Product', ['id'=> self::PRODUCT_ID]);
        $context = new LayoutContext();
        $context->set(FrontendProductPricesDataProvicer::PRODUCT_ID_ALIAS, self::PRODUCT_ID);

        $repo = $this->getMockBuilder('OroB2B\Bundle\PricingBundle\Entity\Repository\ProductPriceRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $repo->expects($this->once())
            ->method('findByPriceListIdAndProductIds')
            ->willReturn($prices);

        $this->doctrineHelper->expects($this->once())
            ->method('getEntityRepository')
            ->with('OroB2BPricingBundle:ProductPrice')
            ->willReturn($repo);

        $this->doctrineHelper->expects($this->once())
            ->method('getEntityReference')
            ->with('OroB2BProductBundle:Product', self::PRODUCT_ID)
            ->willReturn($product);

        $this->frontendPriceListRequestHandler->expects($this->once())
            ->method('getPriceList')
            ->willReturn($priceList);

        $actual = $this->provider->getData($context);

        $this->assertEquals($prices, $actual);
    }
}
