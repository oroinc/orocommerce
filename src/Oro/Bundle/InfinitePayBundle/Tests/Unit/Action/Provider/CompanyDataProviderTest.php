<?php

namespace Oro\Bundle\InfinitePayBundle\Tests\Unit\Action\Mapper;

use Oro\Bundle\AddressBundle\Entity\Country;
use Oro\Bundle\CustomerBundle\Entity\Account;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\InfinitePayBundle\Action\PropertyAccessor\CustomerPropertyAccessor;
use Oro\Bundle\InfinitePayBundle\Action\Provider\CompanyDataProvider;
use Oro\Bundle\OrderBundle\Entity\OrderAddress;

/**
 * {@inheritdoc}
 */
class CompanyDataProviderTest extends \PHPUnit_Framework_TestCase
{
    protected $vatId = 'DE129274202';

    /** @var string */
    protected $companyDataName = 'test_company';

    /** @var string */
    protected $companyDataFsName = 'test_first_name';

    /** @var string */
    protected $companyDataLsName = 'test_last_name';

    /** @var Country */
    protected $billingCountry;
    /** @var string */
    protected $billingCity = 'Mainz';
    /** @var string */
    protected $street = 'Am Rosengarten 1';
    /** @var string */
    protected $zip = '55131';

    /** @var CustomerPropertyAccessor */
    protected $propertyAccessor;

    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        $this->billingCountry = (new Country('DE'))->setIso3Code('DEU');
        $this->propertyAccessor = $this
            ->getMockBuilder(CustomerPropertyAccessor::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->propertyAccessor->method('extractVatId')->willReturn($this->vatId);
    }

    public function testGetCompanyData()
    {
        $companyDataProvider = new CompanyDataProvider($this->propertyAccessor);
        $billingAddress = new OrderAddress();
        $billingAddress
            ->setFirstName($this->companyDataFsName)
            ->setLastName($this->companyDataLsName)
            ->setCountry($this->billingCountry)
            ->setCity($this->billingCity)
            ->setStreet($this->street)
            ->setPostalCode($this->zip)
            ->setOrganization($this->companyDataName)
        ;

        $customer = new Customer();
        $actualCompanyData = $companyDataProvider->getCompanyData($billingAddress, $customer);

        $this->assertEquals($this->companyDataName, $actualCompanyData->getCompanyName());
        $this->assertEquals($this->companyDataFsName, $actualCompanyData->getOwnerFsName());
        $this->assertEquals($this->companyDataLsName, $actualCompanyData->getOwnerLsName());
        $this->assertEquals('DE129274202', $actualCompanyData->getComIdVat());
    }
}
