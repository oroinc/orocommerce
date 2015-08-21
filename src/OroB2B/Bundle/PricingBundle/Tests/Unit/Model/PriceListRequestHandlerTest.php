<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Unit\Model;

use Doctrine\Common\Persistence\ManagerRegistry;

use Symfony\Component\HttpFoundation\Request;

use OroB2B\Bundle\PricingBundle\Entity\PriceList;
use OroB2B\Bundle\PricingBundle\Model\PriceListRequestHandler;

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
        $this->assertSame(['USD'], $this->handler->getPriceListSelectedCurrencies());
    }

    /**
     * @param mixed $paramValue
     * @param array $currencies
     * @param array $expected
     *
     * @dataProvider currenciesDataProvider
     */
    public function testGetPriceListCurrenciesWithTrueParamShouldReturnArray(
        $paramValue,
        array $currencies = [],
        array $expected = []
    ) {
        $priceList = $this->getPriceList(2, $currencies);

        /** @var \PHPUnit_Framework_MockObject_MockObject|Request $request */
        $request = $this->getMock('Symfony\Component\HttpFoundation\Request');
        $this->handler->setRequest($request);

        $request->expects($this->atLeastOnce())->method('get')->will(
            $this->returnValueMap(
                [
                    [PriceListRequestHandler::PRICE_LIST_KEY, null, false, $priceList->getId()],
                    [PriceListRequestHandler::PRICE_LIST_CURRENCY_KEY, null, false, $paramValue],
                ]
            )
        );

        $this->repository->expects($this->never())->method('getDefault');
        $this->repository->expects($this->once())->method('find')->with($priceList->getId())->willReturn($priceList);
        $this->assertEquals($expected, $this->handler->getPriceListSelectedCurrencies());
    }

    /**
     * @return array
     */
    public function currenciesDataProvider()
    {
        return [
            'true returns all price list currencies wih cast' => ['true', ['USD', 'EUR'], ['EUR', 'USD']],
            'true returns all price list currencies' => [true, ['USD', 'EUR'], ['EUR', 'USD']],
            'false returns nothings with cast' => [false, ['USD', 'EUR'], []],
            'false returns nothings' => ['false', ['USD', 'EUR'], []],
            'submit invalid currency' => [['USD', 'UAH'], ['USD', 'EUR'], ['USD']],
            'null value returns all currencies on initial state' => [null, ['USD', 'EUR'], ['EUR', 'USD']],
        ];
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
