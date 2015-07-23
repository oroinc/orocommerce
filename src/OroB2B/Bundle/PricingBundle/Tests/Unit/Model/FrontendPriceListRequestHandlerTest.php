<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Unit\Model;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

use Oro\Bundle\SecurityBundle\SecurityFacade;
use Oro\Bundle\UserBundle\Entity\AbstractUser;

use OroB2B\Bundle\CustomerBundle\Entity\AccountUser;
use OroB2B\Bundle\PricingBundle\Entity\PriceList;
use OroB2B\Bundle\PricingBundle\Model\FrontendPriceListRequestHandler;
use OroB2B\Bundle\PricingBundle\Model\PriceListTreeHandler;

class FrontendPriceListRequestHandlerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|SessionInterface
     */
    protected $session;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|SecurityFacade
     */
    protected $securityFacade;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|PriceListTreeHandler
     */
    protected $priceListTreeHandler;

    /**
     * @var FrontendPriceListRequestHandler
     */
    protected $handler;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|Request
     */
    protected $request;

    protected function setUp()
    {
        $this->session = $this->getMock('Symfony\Component\HttpFoundation\Session\SessionInterface');

        $this->securityFacade = $this->getMockBuilder('Oro\Bundle\SecurityBundle\SecurityFacade')
            ->disableOriginalConstructor()
            ->getMock();

        $this->priceListTreeHandler = $this->getMockBuilder('OroB2B\Bundle\PricingBundle\Model\PriceListTreeHandler')
            ->disableOriginalConstructor()
            ->getMock();

        $this->handler = new FrontendPriceListRequestHandler(
            $this->session,
            $this->securityFacade,
            $this->priceListTreeHandler
        );

        $this->request = $this->getMock('Symfony\Component\HttpFoundation\Request');
    }

    protected function tearDown()
    {
        unset($this->session, $this->securityFacade, $this->priceListTreeHandler, $this->handler, $this->request);
    }

    /**
     * @dataProvider getPriceListDataProvider
     *
     * @param PriceList $priceList
     * @param AbstractUser|null $user
     * @param AbstractUser|null $expectedUser
     */
    public function testGetPriceList(PriceList $priceList, AbstractUser $user = null, AbstractUser $expectedUser = null)
    {
        $this->securityFacade->expects($this->once())
            ->method('getLoggedUser')
            ->willReturn($user);

        $this->priceListTreeHandler->expects($this->once())
            ->method('getPriceList')
            ->with($expectedUser)
            ->willReturn($priceList);

        $this->assertSame($priceList, $this->handler->getPriceList());
    }

    /**
     * @return array
     */
    public function getPriceListDataProvider()
    {
        /** @var PriceList $priceList */
        $priceList = $this->getPriceList(42);

        /** @var AccountUser $priceList */
        $accountUser = $this->getEntity('OroB2B\Bundle\CustomerBundle\Entity\AccountUser', 11);

        return [
            [
                'priceList' => $priceList,
                'user' => $accountUser,
                'expectedUser' => $accountUser
            ],
            [
                'priceList' => $priceList,
                'user' => $this->getEntity('Oro\Bundle\UserBundle\Entity\User', 21),
                'expectedUser' => null
            ],
            [
                'priceList' => $priceList,
                'user' => null,
                'expectedUser' => null
            ]
        ];
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage PriceList not found
     */
    public function testGetPriceListException()
    {
        $this->priceListTreeHandler->expects($this->once())
            ->method('getPriceList')
            ->willReturn(null);

        $this->handler->getPriceList();
    }

    public function testGetPriceListCurrenciesWithoutRequestAndSessionParam()
    {
        $priceList = $this->getPriceList(42, ['USD', 'GBP', 'EUR']);

        $this->priceListTreeHandler->expects($this->once())
            ->method('getPriceList')
            ->willReturn($priceList);

        $this->assertSame(['EUR'], $this->handler->getPriceListSelectedCurrencies());
    }

    /**
     * @dataProvider getPriceListCurrenciesDataProvider
     *
     * @param string $paramValue
     * @param array $currencies
     * @param array $expected
     */
    public function testGetPriceListCurrenciesWithRequest($paramValue, array $currencies = [], array $expected = [])
    {
        $this->handler->setRequest($this->request);

        $this->request->expects($this->once())
            ->method('get')
            ->with(FrontendPriceListRequestHandler::PRICE_LIST_CURRENCY_KEY)
            ->willReturn($paramValue);

        $this->priceListTreeHandler->expects($this->once())
            ->method('getPriceList')
            ->willReturn($this->getPriceList(42, $currencies));

        $this->assertEquals($expected, $this->handler->getPriceListSelectedCurrencies());
    }

    /**
     * @dataProvider getPriceListCurrenciesDataProvider
     *
     * @param string $paramVal
     * @param array $currencies
     * @param array $expected
     */
    public function testGetPriceListCurrenciesWithSessionParam($paramVal, array $currencies = [], array $expected = [])
    {
        $this->session->expects($this->once())
            ->method('has')
            ->with(FrontendPriceListRequestHandler::PRICE_LIST_CURRENCY_KEY)
            ->willReturn(true);

        $this->session->expects($this->once())
            ->method('get')
            ->with(FrontendPriceListRequestHandler::PRICE_LIST_CURRENCY_KEY)
            ->willReturn($paramVal);

        $this->priceListTreeHandler->expects($this->once())
            ->method('getPriceList')
            ->willReturn($this->getPriceList(42, $currencies));

        $this->assertEquals($expected, $this->handler->getPriceListSelectedCurrencies());
    }

    /**
     * @return array
     */
    public function getPriceListCurrenciesDataProvider()
    {
        return [
            'submit valid currency' => ['GBP', ['USD', 'GBP', 'EUR'], ['GBP']],
            'submit invalid currency' => ['EUR', ['USD', 'GBP'], ['GBP']],
            'null value returns all currencies on initial state' => [null, ['USD', 'GBP', 'EUR'], ['EUR']],
        ];
    }

    /**
     * @param int $id
     * @param array $currencies
     * @return PriceList
     */
    protected function getPriceList($id, array $currencies = [])
    {
        /** @var PriceList $priceList */
        $priceList = $this->getEntity('OroB2B\Bundle\PricingBundle\Entity\PriceList', $id);
        $priceList->setCurrencies($currencies);

        return $priceList;
    }

    /**
     * @param string $class
     * @param int $id
     * @return object
     */
    protected function getEntity($class, $id)
    {
        $entity = new $class();
        $reflection = new \ReflectionProperty(get_class($entity), 'id');
        $reflection->setAccessible(true);
        $reflection->setValue($entity, $id);

        return $entity;
    }
}
