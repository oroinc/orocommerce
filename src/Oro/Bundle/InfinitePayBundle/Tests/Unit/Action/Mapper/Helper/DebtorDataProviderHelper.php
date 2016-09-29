<?php

namespace Oro\Bundle\InfinitePayBundle\Tests\Unit\Action\Mapper\Helper;

use Oro\Bundle\InfinitePayBundle\Service\InfinitePay\CompanyData;
use Oro\Bundle\InfinitePayBundle\Service\InfinitePay\DebtorData;

/**
 * * @SuppressWarnings(PHPMD.TooManyFields)
 * {@inheritdoc}
 */
class DebtorDataProviderHelper extends \PHPUnit_Framework_TestCase
{
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
    protected $email = 'test_email';
    /** @var string */
    protected $dbNew = '2';
    protected $negHist = '0';
    protected $bdSalut = 'n/a';
    protected $ipAddress = '8.8.8.8';
    protected $isp = 'test isp';
    protected $bdZip = '55131';
    protected $bdCountry = 'DE';
    protected $bdCity = 'Mainz';
    protected $bdStreet = 'Am Rosengarten 1';
    protected $bdFirstName = 'Anton';
    protected $bdLastName = 'Müller';

    public function getDebtorDataProvider()
    {
        $debtorDataProvider = $this
            ->getMockBuilder('Oro\Bundle\InfinitePayBundle\Action\Provider\DebtorDataProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $debtorData = (new DebtorData())
            ->setCompanyData($this->getCompanyData())
            ->setBdEmai($this->email)
            ->setDbNew($this->dbNew)
            ->setNegPayHist($this->negHist)
            ->setBdSalut($this->bdSalut)
            ->setIpAdd($this->ipAddress)
            ->setIsp($this->isp)
            ->setBdZip($this->bdZip)
            ->setBdCountry($this->bdCountry)
            ->setBdCity($this->bdCity)
            ->setBdStreet($this->bdStreet)
            ->setBdNameFs($this->bdFirstName)
            ->setBdNameLs($this->bdLastName)
            ;

        $debtorDataProvider
            ->method('getDebtorData')
            ->willReturn($debtorData)
        ;

        return $debtorDataProvider;
    }

    /**
     * @param string $companyDataIdNum
     *
     * @return DebtorDataProviderHelper
     */
    public function setCompanyDataIdNum($companyDataIdNum)
    {
        $this->companyDataIdNum = $companyDataIdNum;

        return $this;
    }

    /**
     * @param string $companyDataIdType
     *
     * @return DebtorDataProviderHelper
     */
    public function setCompanyDataIdType($companyDataIdType)
    {
        $this->companyDataIdType = $companyDataIdType;

        return $this;
    }

    /**
     * @param string $companyDataName
     *
     * @return DebtorDataProviderHelper
     */
    public function setCompanyDataName($companyDataName)
    {
        $this->companyDataName = $companyDataName;

        return $this;
    }

    /**
     * @param string $companyDataFsName
     *
     * @return DebtorDataProviderHelper
     */
    public function setCompanyDataFsName($companyDataFsName)
    {
        $this->companyDataFsName = $companyDataFsName;

        return $this;
    }

    /**
     * @param string $companyDataLsName
     *
     * @return DebtorDataProviderHelper
     */
    public function setCompanyDataLsName($companyDataLsName)
    {
        $this->companyDataLsName = $companyDataLsName;

        return $this;
    }

    /**
     * @param string $email
     *
     * @return DebtorDataProviderHelper
     */
    public function setEmail($email)
    {
        $this->email = $email;

        return $this;
    }

    /**
     * @param string $dbNew
     *
     * @return DebtorDataProviderHelper
     */
    public function setDbNew($dbNew)
    {
        $this->dbNew = $dbNew;

        return $this;
    }

    /**
     * @param string $negHist
     *
     * @return DebtorDataProviderHelper
     */
    public function setNegHist($negHist)
    {
        $this->negHist = $negHist;

        return $this;
    }

    /**
     * @param string $bdSalut
     *
     * @return DebtorDataProviderHelper
     */
    public function setBdSalut($bdSalut)
    {
        $this->bdSalut = $bdSalut;

        return $this;
    }

    /**
     * @param string $ipAddress
     *
     * @return DebtorDataProviderHelper
     */
    public function setIpAddress($ipAddress)
    {
        $this->ipAddress = $ipAddress;

        return $this;
    }

    /**
     * @param string $isp
     *
     * @return DebtorDataProviderHelper
     */
    public function setIsp($isp)
    {
        $this->isp = $isp;

        return $this;
    }

    /**
     * @param string $bdZip
     *
     * @return DebtorDataProviderHelper
     */
    public function setBdZip($bdZip)
    {
        $this->bdZip = $bdZip;

        return $this;
    }

    /**
     * @param string $bdCountry
     *
     * @return DebtorDataProviderHelper
     */
    public function setBdCountry($bdCountry)
    {
        $this->bdCountry = $bdCountry;

        return $this;
    }

    /**
     * @param string $bdCity
     *
     * @return DebtorDataProviderHelper
     */
    public function setBdCity($bdCity)
    {
        $this->bdCity = $bdCity;

        return $this;
    }

    /**
     * @param string $bdStreet
     *
     * @return DebtorDataProviderHelper
     */
    public function setBdStreet($bdStreet)
    {
        $this->bdStreet = $bdStreet;

        return $this;
    }

    /**
     * @param string $bdFirstName
     *
     * @return DebtorDataProviderHelper
     */
    public function setBdFirstName($bdFirstName)
    {
        $this->bdFirstName = $bdFirstName;

        return $this;
    }

    /**
     * @param string $bdLastName
     *
     * @return DebtorDataProviderHelper
     */
    public function setBdLastName($bdLastName)
    {
        $this->bdLastName = $bdLastName;

        return $this;
    }

    /**
     * @return CompanyData
     */
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
}
