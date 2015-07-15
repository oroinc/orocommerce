<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Unit\Model;

use Doctrine\Common\Persistence\ManagerRegistry;

use OroB2B\Bundle\PricingBundle\Entity\PriceList;
use OroB2B\Bundle\PricingBundle\Model\PriceListRequestHandler;
use Symfony\Component\HttpFoundation\Request;

class PriceListRequestHandlerTest extends \PHPUnit_Framework_TestCase
{
    /** @var PriceListRequestHandler */
    protected $handler;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|ManagerRegistry
     */
    protected $repository;

    protected function setUp()
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|ManagerRegistry $registry */
        $registry = $this->getMock('Doctrine\Common\Persistence\ManagerRegistry');
        $em = $this->getMock('Doctrine\Common\Persistence\ObjectManager');
        $registry->expects($this->any())->method('getManagerForClass')->willReturn($em);
        $this->repository = $this->getMockBuilder('OroB2B\Bundle\PricingBundle\Entity\Repository\PriceListRepository')
            ->disableOriginalConstructor()->getMock();
        $em->expects($this->any())->method('getRepository')->willReturn($this->repository);

        $this->handler = new PriceListRequestHandler($registry, '\stdClass');
    }

    protected function tearDown()
    {
        unset($this->repository, $this->handler);
    }

    public function testGetPriceListWithoutRequest()
    {
        $priceList = $this->getPriceList(2);
        $this->repository->expects($this->once())->method('getDefault')->willReturn($priceList);
        $this->assertSame($priceList, $this->handler->getPriceList());
    }

    public function testGetPriceListWithoutParam()
    {
        $priceList = $this->getPriceList(2);

        /** @var \PHPUnit_Framework_MockObject_MockObject|Request $request */
        $request = $this->getMock('Symfony\Component\HttpFoundation\Request');
        $this->handler->setRequest($request);

        $this->repository->expects($this->once())->method('getDefault')->willReturn($priceList);
        $this->repository->expects($this->never())->method('find');
        $this->assertSame($priceList, $this->handler->getPriceList());
    }

    public function testGetPriceList()
    {
        $priceList = $this->getPriceList(2);

        /** @var \PHPUnit_Framework_MockObject_MockObject|Request $request */
        $request = $this->getMock('Symfony\Component\HttpFoundation\Request');
        $this->handler->setRequest($request);

        $request->expects($this->exactly(2))->method('get')->with(PriceListRequestHandler::PRICE_LIST_KEY)
            ->willReturn($priceList->getId());

        $this->repository->expects($this->once())->method('find')->with($priceList->getId())->willReturn($priceList);
        $this->repository->expects($this->never())->method('getDefault');
        $this->assertSame($priceList, $this->handler->getPriceList());

        // cache
        $this->assertSame($priceList, $this->handler->getPriceList());
    }

    public function testGetPriceListNotFound()
    {
        $priceList = $this->getPriceList(2);

        /** @var \PHPUnit_Framework_MockObject_MockObject|Request $request */
        $request = $this->getMock('Symfony\Component\HttpFoundation\Request');
        $this->handler->setRequest($request);

        $request->expects($this->once())->method('get')->with(PriceListRequestHandler::PRICE_LIST_KEY)
            ->willReturn($priceList->getId());

        $this->repository->expects($this->once())->method('find')->with($priceList->getId())->willReturn(null);
        $this->repository->expects($this->once())->method('getDefault')->willReturn($priceList);
        $this->assertSame($priceList, $this->handler->getPriceList());
    }

    public function testGetPriceListCurrenciesWithoutRequest()
    {
        $priceList = $this->getPriceList(2, ['USD']);
        $this->repository->expects($this->once())->method('getDefault')->willReturn($priceList);
        $this->repository->expects($this->never())->method('find');
        $this->assertSame(['USD'], $this->handler->getPriceListCurrencies());
    }

    public function testGetPriceListCurrenciesWithoutParamShouldReturnAll()
    {
        $priceList = $this->getPriceList(2, ['USD']);

        /** @var \PHPUnit_Framework_MockObject_MockObject|Request $request */
        $request = $this->getMock('Symfony\Component\HttpFoundation\Request');
        $this->handler->setRequest($request);

        $request->expects($this->once())->method('get')->will(
            $this->returnValueMap(
                [
                    [PriceListRequestHandler::PRICE_LIST_KEY, null, false, $priceList->getId()],
                    [PriceListRequestHandler::PRICE_LIST_CURRENCY_KEY, null, false, null],
                ]
            )
        );

        $this->repository->expects($this->never())->method('getDefault');
        $this->repository->expects($this->once())->method('find')->with($priceList->getId())->willReturn($priceList);
        $this->assertSame(['USD'], $this->handler->getPriceListCurrencies());
    }

    public function testShowTierPricesWithoutRequest()
    {
        $this->assertFalse($this->handler->showTierPrices());
    }

    public function testShowTierPricesWithoutParam()
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|Request $request */
        $request = $this->getMock('Symfony\Component\HttpFoundation\Request');
        $this->handler->setRequest($request);

        $request->expects($this->exactly(2))->method('get')->with(PriceListRequestHandler::TIER_PRICES_KEY)
            ->will($this->onConsecutiveCalls([null, false]));

        $this->assertFalse($this->handler->showTierPrices());
        $this->assertFalse($this->handler->showTierPrices());
    }

    public function testShowTierPrices()
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|Request $request */
        $request = $this->getMock('Symfony\Component\HttpFoundation\Request');
        $this->handler->setRequest($request);

        $request->expects($this->once())->method('get')->with(PriceListRequestHandler::TIER_PRICES_KEY)
            ->willReturn(true);

        $this->assertTrue($this->handler->showTierPrices());
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Default PriceList not found
     */
    public function testDefaultPriceListNotFound()
    {
        $this->repository->expects($this->once())->method('getDefault')->willReturn(null);
        $this->repository->expects($this->never())->method('find');
        $this->handler->getPriceList();
    }

    /**
     * @param int $id
     * @param array $currencies
     * @return PriceList
     */
    protected function getPriceList($id, array $currencies = [])
    {
        $priceList = new PriceList();
        $reflection = new \ReflectionProperty(get_class($priceList), 'id');
        $reflection->setAccessible(true);
        $reflection->setValue($priceList, $id);

        $priceList->setCurrencies($currencies);

        return $priceList;
    }
}
