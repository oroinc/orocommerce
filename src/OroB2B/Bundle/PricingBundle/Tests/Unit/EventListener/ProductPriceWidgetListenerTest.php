<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Unit\Datagrid;

use OroB2B\Bundle\PricingBundle\Entity\PriceList;
use OroB2B\Bundle\PricingBundle\EventListener\ProductPriceWidgetListener;
use OroB2B\Bundle\PricingBundle\Model\PriceListRequestHandler;
use OroB2B\Bundle\ProductBundle\Event\ProductGridWidgetRenderEvent;

class ProductPriceWidgetListenerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ProductPriceWidgetListener
     */
    protected $listener;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|PriceListRequestHandler
     */
    protected $priceListRequestHandler;

    protected function setUp()
    {
        $this->priceListRequestHandler = $this
            ->getMockBuilder('OroB2B\Bundle\PricingBundle\Model\PriceListRequestHandler')
            ->disableOriginalConstructor()
            ->getMock();

        $this->listener = new ProductPriceWidgetListener($this->priceListRequestHandler);
    }

    protected function tearDown()
    {
        unset($this->priceListRequestHandler, $this->listener);
    }

    public function testOnWidgetRender()
    {
        $priceList = $this->getPriceList(42);
        $currencies = ['UAH'];
        $showTierPrices = false;

        /** @var \PHPUnit_Framework_MockObject_MockObject|ProductGridWidgetRenderEvent $event */
        $event = $this->getMockBuilder('OroB2B\Bundle\ProductBundle\Event\ProductGridWidgetRenderEvent')
            ->disableOriginalConstructor()->getMock();
        $event->expects($this->once())->method('getWidgetRouteParameters')->willReturn([]);

        $this->priceListRequestHandler->expects($this->once())->method('getPriceListFromRequest')
            ->willReturn($priceList);
        $this->priceListRequestHandler->expects($this->once())->method('getPriceListCurrenciesFromRequest')
            ->willReturn($currencies);
        $this->priceListRequestHandler->expects($this->once())->method('showTierPrices')->willReturn($showTierPrices);

        $event->expects($this->once())->method('setWidgetRouteParameters')->with(
            $this->logicalAnd(
                $this->isType('array'),
                $this->arrayHasKey('priceListId'),
                $this->arrayHasKey('priceCurrencies'),
                $this->arrayHasKey('showTierPrices'),
                $this->contains($priceList->getId()),
                $this->contains($currencies),
                $this->contains($showTierPrices)
            )
        );

        $this->listener->onWidgetRender($event);
    }

    /**
     * @param int $id
     * @return PriceList
     */
    protected function getPriceList($id)
    {
        $priceList = new PriceList();
        $reflection = new \ReflectionProperty(get_class($priceList), 'id');
        $reflection->setAccessible(true);
        $reflection->setValue($priceList, $id);

        return $priceList;
    }
}
