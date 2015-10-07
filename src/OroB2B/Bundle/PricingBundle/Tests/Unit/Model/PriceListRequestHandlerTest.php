<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Unit\Model;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

use Doctrine\Common\Persistence\ManagerRegistry;

use OroB2B\Bundle\PricingBundle\Entity\PriceList;
use OroB2B\Bundle\PricingBundle\Model\PriceListRequestHandler;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 */
class PriceListRequestHandlerTest extends \PHPUnit_Framework_TestCase
{
    /** @var PriceListRequestHandler */
    protected $handler;

    /** @var  Request|\PHPUnit_Framework_MockObject_MockObject */
    protected $request;

    /** @var ManagerRegistry|\PHPUnit_Framework_MockObject_MockObject */
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

        $this->request = $this->getMock('Symfony\Component\HttpFoundation\Request');
        /** @var RequestStack|\PHPUnit_Framework_MockObject_MockObject $requestStack */
        $requestStack = $this->getMock('Symfony\Component\HttpFoundation\RequestStack');
        $requestStack->expects($this->any())->method('getCurrentRequest')->willReturn($this->request);

        $this->handler = new PriceListRequestHandler($requestStack, $registry, '\stdClass');
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

        $this->repository->expects($this->once())->method('getDefault')->willReturn($priceList);
        $this->repository->expects($this->never())->method('find');
        $this->assertSame($priceList, $this->handler->getPriceList());
    }

    public function testGetPriceList()
    {
        $priceList = $this->getPriceList(2);

        $this->request->expects($this->exactly(2))->method('get')->with(PriceListRequestHandler::PRICE_LIST_KEY)
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

        $this->request->expects($this->once())->method('get')->with(PriceListRequestHandler::PRICE_LIST_KEY)
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

        $this->request->expects($this->atLeastOnce())->method('get')->will(
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

    /**
     * @param mixed $value
     * @param bool|int $expected
     *
     * @dataProvider priceListIdDataProvider
     */
    public function testGetPriceListId($value, $expected)
    {
        $this->request->expects($this->atLeastOnce())->method('get')->willReturn($value);
        $this->assertEquals($expected, $this->handler->getPriceListId());
    }

    /**
     * @return array
     */
    public function priceListIdDataProvider()
    {
        return [
            [true, false],
            [false, false],
            ['true', false],
            ['false', false],
            [2, true],
            [1, true],
            [0, false],
            [-1, false],
            ['2', true],
            ['1', true],
            ['0', false],
            ['-1', false],
        ];
    }
}
