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
        $priceList = $this->getEntity('OroB2B\Bundle\PricingBundle\Entity\PriceList', ['id'=> 23]);
        $product = $this->getEntity('OroB2B\Bundle\ProductBundle\Entity\Product', ['id'=> 24]);
        $context = new LayoutContext();
        $context->data()->set('product', null, $product);

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

        $this->frontendPriceListRequestHandler->expects($this->once())
            ->method('getPriceList')
            ->willReturn($priceList);

        $actual = $this->provider->getData($context);

        $this->assertEquals($prices, $actual);
    }
}
