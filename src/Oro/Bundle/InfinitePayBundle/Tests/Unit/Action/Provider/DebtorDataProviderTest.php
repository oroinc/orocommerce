<?php

namespace Oro\Bundle\InfinitePayBundle\Tests\Unit\Action\Mapper;

use Oro\Bundle\AddressBundle\Entity\Country;
use Oro\Bundle\CustomerBundle\Entity\Account;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\InfinitePayBundle\Action\Provider\DebtorDataProvider;
use Oro\Bundle\InfinitePayBundle\Action\Provider\DebtorDataProviderInterface;
use Oro\Bundle\InfinitePayBundle\Service\InfinitePay\CompanyData;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Entity\OrderAddress;

/**
 * {@inheritdoc}
 */
class DebtorDataProviderTest extends \PHPUnit_Framework_TestCase
{
    /** @var DebtorDataProviderInterface */
    protected $debtorDataProvider;

    /** @var string */
    protected $companyDataIdNum = 'test_id_num';

    /** @var string */
    protected $companyDataIdType = 'freelance';

    /** @var string */
    protected $companyDataName = 'test_company';

    /** @var string */
    protected $companyDataFsName = 'test_first_name';

    /** @var string */
    protected $companyDataLsName = 'test_last_name';

    /** @var string */
    protected $clientIp = '8.8.8.8';

    /** @var string */
    protected $isp = 'google-public-dns-a.google.com';

    /** @var Country */
    protected $billingCountry;
    /** @var string */
    protected $billingCity = 'Mainz';
    /** @var string */
    protected $street = 'Am Rosengarten 1';
    /** @var string */
    protected $zip = '55131';

    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        $this->billingCountry = (new Country('DE'))->setIso3Code('DEU');

        $companyDataProvider = $this
            ->getMockBuilder('Oro\Bundle\InfinitePayBundle\Action\Provider\CompanyDataProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $companyDataProvider
            ->method('getCompanyData')
            ->willReturn($this->returnValue($this->getCompanyData()));

        $requestProvider = $this
            ->getMockBuilder('Oro\Bundle\InfinitePayBundle\Action\Provider\RequestProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $requestProvider
            ->method('getClientIp')
            ->willReturn($this->clientIp);

        $addressExtractor = $this
            ->getMockBuilder('Oro\Bundle\PaymentBundle\Provider\AddressExtractor')
            ->disableOriginalConstructor()
            ->getMock();

        $addressExtractor
            ->method('extractAddress')
            ->willReturn($this->getOrderAddress());

        $this->debtorDataProvider = new DebtorDataProvider($companyDataProvider, $requestProvider, $addressExtractor);
    }

    public function testGetDebtorData()
    {
        $order = new Order();
        $order->setCustomer(new Customer());
        $debtorDataActual = $this->debtorDataProvider->getDebtorData($order);

        $this->assertEquals($this->zip, $debtorDataActual->getBdZip());
        $this->assertEquals($this->clientIp, $debtorDataActual->getIpAdd());
        $this->assertEquals($this->billingCity, $debtorDataActual->getBdCity());
        $this->assertEquals($this->street, $debtorDataActual->getBdStreet());
        $this->assertEquals($this->clientIp, $debtorDataActual->getIpAdd());
        $this->assertEquals($this->isp, $debtorDataActual->getIsp());
        $this->assertEquals($this->companyDataFsName, $debtorDataActual->getBdNameFs());
        $this->assertEquals($this->companyDataLsName, $debtorDataActual->getBdNameLs());
    }

    private function getCompanyData()
    {
        $companyData = (new CompanyData())
            ->setComIdNum($this->companyDataIdNum)
            ->setComIdType($this->companyDataIdType)
            ->setCompanyName($this->companyDataName)
            ->setOwnerFsName($this->companyDataFsName)
            ->setOwnerLsName($this->companyDataLsName)
        ;

        return $companyData;
    }

    private function getOrderAddress()
    {
        $address = new OrderAddress();
        $address
            ->setFirstName($this->companyDataFsName)
            ->setLastName($this->companyDataLsName)
            ->setCountry($this->billingCountry)
            ->setCity($this->billingCity)
            ->setStreet($this->street)
            ->setPostalCode($this->zip)
            ;

        return $address;
    }
}
