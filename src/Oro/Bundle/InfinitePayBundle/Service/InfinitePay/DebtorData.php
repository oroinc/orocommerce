<?php

namespace Oro\Bundle\InfinitePayBundle\Service\InfinitePay;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 * @SuppressWarnings(PHPMD.TooManyFields)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 * @SuppressWarnings(PHPMD.ExcessiveParameterList)
 */
class DebtorData
{
    /**
     * @var CompanyData
     */
    protected $COMPANY_DATA;

    /**
     * @var string
     */
    protected $BD_BIRTH_DT;

    /**
     * @var string
     */
    protected $BD_CITY;

    /**
     * @var string
     */
    protected $BD_COUNTRY;

    /**
     * @var string
     */
    protected $BD_EMAIL;

    /**
     * @var string
     */
    protected $BD_MOBILE;

    /**
     * @var string
     */
    protected $BD_NAME_FS;

    /**
     * @var string
     */
    protected $BD_NAME_LS;

    /**
     * @var string
     */
    protected $BD_SALUT;

    /**
     * @var string
     */
    protected $BD_STREET;

    /**
     * @var string
     */
    protected $BD_STREET_NR;

    /**
     * @var string
     */
    protected $BD_TEL;

    /**
     * @var string
     */
    protected $BD_TITLE;

    /**
     * @var string
     */
    protected $BD_ZIP;

    /**
     * @var string
     */
    protected $COM_OR_PER;

    /**
     * @var string
     */
    protected $DB_ID_EXT;

    /**
     * @var string
     */
    protected $DB_ID_INT;

    /**
     * @var string
     */
    protected $DB_NEW;

    /**
     * @var string
     */
    protected $DEBTOR_PROVIDER_ID;

    /**
     * @var string
     */
    protected $IP_ADD;

    /**
     * @var string
     */
    protected $ISP;

    /**
     * @var string
     */
    protected $NEG_PAY_HIST;

    /**
     * @var string
     */
    protected $SCORE;

    /**
     * @var string
     */
    protected $SCORE_DATE;

    /**
     * @var string
     */
    protected $SCORE_PROVIDER;

    /**
     * @var string
     */
    protected $USER_DD_1;

    /**
     * @var string
     */
    protected $USER_DD_10;

    /**
     * @var string
     */
    protected $USER_DD_2;

    /**
     * @var string
     */
    protected $USER_DD_3;

    /**
     * @var string
     */
    protected $USER_DD_4;

    /**
     * @var string
     */
    protected $USER_DD_5;

    /**
     * @var string
     */
    protected $USER_DD_6;

    /**
     * @var string
     */
    protected $USER_DD_7;

    /**
     * @var string
     */
    protected $USER_DD_8;

    /**
     * @var string
     */
    protected $USER_DD_9;

    /**
     * @param string $BD_BIRTH_DT
     * @param string $BD_CITY
     * @param string $BD_COUNTRY
     * @param string $BD_EMAIL
     * @param string $BD_MOBILE
     * @param string $BD_NAME_FS
     * @param string $BD_NAME_LS
     * @param string $BD_SALUT
     * @param string $BD_STREET
     * @param string $BD_STREET_NR
     * @param string $BD_TEL
     * @param string $BD_TITLE
     * @param string $BD_ZIP
     * @param string $COM_OR_PER
     * @param string $DB_ID_EXT
     * @param string $DB_ID_INT
     * @param string $DB_NEW
     * @param string $DEBTOR_PROVIDER_ID
     * @param string $IP_ADD
     * @param string $ISP
     * @param string $NEG_PAY_HIST
     * @param string $SCORE
     * @param string $SCORE_DATE
     * @param string $SCORE_PROVIDER
     * @param string $USER_DD_1
     * @param string $USER_DD_10
     * @param string $USER_DD_2
     * @param string $USER_DD_3
     * @param string $USER_DD_4
     * @param string $USER_DD_5
     * @param string $USER_DD_6
     * @param string $USER_DD_7
     * @param string $USER_DD_8
     * @param string $USER_DD_9
     */
    public function __construct(
        $BD_BIRTH_DT = null,
        $BD_CITY = null,
        $BD_COUNTRY = null,
        $BD_EMAIL = null,
        $BD_MOBILE = null,
        $BD_NAME_FS = null,
        $BD_NAME_LS = null,
        $BD_SALUT = null,
        $BD_STREET = null,
        $BD_STREET_NR = null,
        $BD_TEL = null,
        $BD_TITLE = null,
        $BD_ZIP = null,
        $COM_OR_PER = null,
        $DB_ID_EXT = null,
        $DB_ID_INT = null,
        $DB_NEW = null,
        $DEBTOR_PROVIDER_ID = null,
        $IP_ADD = null,
        $ISP = null,
        $NEG_PAY_HIST = null,
        $SCORE = null,
        $SCORE_DATE = null,
        $SCORE_PROVIDER = null,
        $USER_DD_1 = null,
        $USER_DD_10 = null,
        $USER_DD_2 = null,
        $USER_DD_3 = null,
        $USER_DD_4 = null,
        $USER_DD_5 = null,
        $USER_DD_6 = null,
        $USER_DD_7 = null,
        $USER_DD_8 = null,
        $USER_DD_9 = null
    ) {
        $this->BD_BIRTH_DT = $BD_BIRTH_DT;
        $this->BD_CITY = $BD_CITY;
        $this->BD_COUNTRY = $BD_COUNTRY;
        $this->BD_EMAIL = $BD_EMAIL;
        $this->BD_MOBILE = $BD_MOBILE;
        $this->BD_NAME_FS = $BD_NAME_FS;
        $this->BD_NAME_LS = $BD_NAME_LS;
        $this->BD_SALUT = $BD_SALUT;
        $this->BD_STREET = $BD_STREET;
        $this->BD_STREET_NR = $BD_STREET_NR;
        $this->BD_TEL = $BD_TEL;
        $this->BD_TITLE = $BD_TITLE;
        $this->BD_ZIP = $BD_ZIP;
        $this->COM_OR_PER = $COM_OR_PER;
        $this->DB_ID_EXT = $DB_ID_EXT;
        $this->DB_ID_INT = $DB_ID_INT;
        $this->DB_NEW = $DB_NEW;
        $this->DEBTOR_PROVIDER_ID = $DEBTOR_PROVIDER_ID;
        $this->IP_ADD = $IP_ADD;
        $this->ISP = $ISP;
        $this->NEG_PAY_HIST = $NEG_PAY_HIST;
        $this->SCORE = $SCORE;
        $this->SCORE_DATE = $SCORE_DATE;
        $this->SCORE_PROVIDER = $SCORE_PROVIDER;
        $this->USER_DD_1 = $USER_DD_1;
        $this->USER_DD_10 = $USER_DD_10;
        $this->USER_DD_2 = $USER_DD_2;
        $this->USER_DD_3 = $USER_DD_3;
        $this->USER_DD_4 = $USER_DD_4;
        $this->USER_DD_5 = $USER_DD_5;
        $this->USER_DD_6 = $USER_DD_6;
        $this->USER_DD_7 = $USER_DD_7;
        $this->USER_DD_8 = $USER_DD_8;
        $this->USER_DD_9 = $USER_DD_9;
    }

    /**
     * @return CompanyData
     */
    public function getCompanyData()
    {
        return $this->COMPANY_DATA;
    }

    /**
     * @param CompanyData $COMPANY_DATA
     *
     * @return \Oro\Bundle\InfinitePayBundle\Service\InfinitePay\DebtorData
     */
    public function setCompanyData($COMPANY_DATA)
    {
        $this->COMPANY_DATA = $COMPANY_DATA;

        return $this;
    }

    /**
     * @return string
     */
    public function getBdBirthDt()
    {
        return $this->BD_BIRTH_DT;
    }

    /**
     * @param string $BD_BIRTH_DT
     *
     * @return \Oro\Bundle\InfinitePayBundle\Service\InfinitePay\DebtorData
     */
    public function setBdBirthDt($BD_BIRTH_DT)
    {
        $this->BD_BIRTH_DT = $BD_BIRTH_DT;

        return $this;
    }

    /**
     * @return string
     */
    public function getBdCity()
    {
        return $this->BD_CITY;
    }

    /**
     * @param string $BD_CITY
     *
     * @return \Oro\Bundle\InfinitePayBundle\Service\InfinitePay\DebtorData
     */
    public function setBdCity($BD_CITY)
    {
        $this->BD_CITY = $BD_CITY;

        return $this;
    }

    /**
     * @return string
     */
    public function getBdCountry()
    {
        return $this->BD_COUNTRY;
    }

    /**
     * @param string $BD_COUNTRY
     *
     * @return \Oro\Bundle\InfinitePayBundle\Service\InfinitePay\DebtorData
     */
    public function setBdCountry($BD_COUNTRY)
    {
        $this->BD_COUNTRY = $BD_COUNTRY;

        return $this;
    }

    /**
     * @return string
     */
    public function getBdEmail()
    {
        return $this->BD_EMAIL;
    }

    /**
     * @param string $BD_EMAIL
     *
     * @return \Oro\Bundle\InfinitePayBundle\Service\InfinitePay\DebtorData
     */
    public function setBdEmai($BD_EMAIL)
    {
        $this->BD_EMAIL = $BD_EMAIL;

        return $this;
    }

    /**
     * @return string
     */
    public function getBdMobile()
    {
        return $this->BD_MOBILE;
    }

    /**
     * @param string $BD_MOBILE
     *
     * @return \Oro\Bundle\InfinitePayBundle\Service\InfinitePay\DebtorData
     */
    public function setBdMobile($BD_MOBILE)
    {
        $this->BD_MOBILE = $BD_MOBILE;

        return $this;
    }

    /**
     * @return string
     */
    public function getBdNameFs()
    {
        return $this->BD_NAME_FS;
    }

    /**
     * @param string $BD_NAME_FS
     *
     * @return \Oro\Bundle\InfinitePayBundle\Service\InfinitePay\DebtorData
     */
    public function setBdNameFs($BD_NAME_FS)
    {
        $this->BD_NAME_FS = $BD_NAME_FS;

        return $this;
    }

    /**
     * @return string
     */
    public function getBdNameLs()
    {
        return $this->BD_NAME_LS;
    }

    /**
     * @param string $BD_NAME_LS
     *
     * @return \Oro\Bundle\InfinitePayBundle\Service\InfinitePay\DebtorData
     */
    public function setBdNameLs($BD_NAME_LS)
    {
        $this->BD_NAME_LS = $BD_NAME_LS;

        return $this;
    }

    /**
     * @return string
     */
    public function getBdSalut()
    {
        return $this->BD_SALUT;
    }

    /**
     * @param string $BD_SALUT
     *
     * @return \Oro\Bundle\InfinitePayBundle\Service\InfinitePay\DebtorData
     */
    public function setBdSalut($BD_SALUT)
    {
        $this->BD_SALUT = $BD_SALUT;

        return $this;
    }

    /**
     * @return string
     */
    public function getBdStreet()
    {
        return $this->BD_STREET;
    }

    /**
     * @param string $BD_STREET
     *
     * @return \Oro\Bundle\InfinitePayBundle\Service\InfinitePay\DebtorData
     */
    public function setBdStreet($BD_STREET)
    {
        $this->BD_STREET = $BD_STREET;

        return $this;
    }

    /**
     * @return string
     */
    public function getBdStreetNr()
    {
        return $this->BD_STREET_NR;
    }

    /**
     * @param string $BD_STREET_NR
     *
     * @return \Oro\Bundle\InfinitePayBundle\Service\InfinitePay\DebtorData
     */
    public function setBdStreetNr($BD_STREET_NR)
    {
        $this->BD_STREET_NR = $BD_STREET_NR;

        return $this;
    }

    /**
     * @return string
     */
    public function getBdTel()
    {
        return $this->BD_TEL;
    }

    /**
     * @param string $BD_TEL
     *
     * @return \Oro\Bundle\InfinitePayBundle\Service\InfinitePay\DebtorData
     */
    public function setBdTel($BD_TEL)
    {
        $this->BD_TEL = $BD_TEL;

        return $this;
    }

    /**
     * @return string
     */
    public function getBdTitle()
    {
        return $this->BD_TITLE;
    }

    /**
     * @param string $BD_TITLE
     *
     * @return \Oro\Bundle\InfinitePayBundle\Service\InfinitePay\DebtorData
     */
    public function setBdTitle($BD_TITLE)
    {
        $this->BD_TITLE = $BD_TITLE;

        return $this;
    }

    /**
     * @return string
     */
    public function getBdZip()
    {
        return $this->BD_ZIP;
    }

    /**
     * @param string $BD_ZIP
     *
     * @return \Oro\Bundle\InfinitePayBundle\Service\InfinitePay\DebtorData
     */
    public function setBdZip($BD_ZIP)
    {
        $this->BD_ZIP = $BD_ZIP;

        return $this;
    }

    /**
     * @return string
     */
    public function getComOrPer()
    {
        return $this->COM_OR_PER;
    }

    /**
     * @param string $COM_OR_PER
     *
     * @return \Oro\Bundle\InfinitePayBundle\Service\InfinitePay\DebtorData
     */
    public function setComOrPer($COM_OR_PER)
    {
        $this->COM_OR_PER = $COM_OR_PER;

        return $this;
    }

    /**
     * @return string
     */
    public function getDbIdExt()
    {
        return $this->DB_ID_EXT;
    }

    /**
     * @param string $DB_ID_EXT
     *
     * @return \Oro\Bundle\InfinitePayBundle\Service\InfinitePay\DebtorData
     */
    public function setDbIdExt($DB_ID_EXT)
    {
        $this->DB_ID_EXT = $DB_ID_EXT;

        return $this;
    }

    /**
     * @return string
     */
    public function getDbIdInt()
    {
        return $this->DB_ID_INT;
    }

    /**
     * @param string $DB_ID_INT
     *
     * @return \Oro\Bundle\InfinitePayBundle\Service\InfinitePay\DebtorData
     */
    public function setDbIdInt($DB_ID_INT)
    {
        $this->DB_ID_INT = $DB_ID_INT;

        return $this;
    }

    /**
     * @return string
     */
    public function getDbNew()
    {
        return $this->DB_NEW;
    }

    /**
     * @param string $DB_NEW
     *
     * @return \Oro\Bundle\InfinitePayBundle\Service\InfinitePay\DebtorData
     */
    public function setDbNew($DB_NEW)
    {
        $this->DB_NEW = $DB_NEW;

        return $this;
    }

    /**
     * @return string
     */
    public function getDebtorProviderId()
    {
        return $this->DEBTOR_PROVIDER_ID;
    }

    /**
     * @param string $DEBTOR_PROVIDER_ID
     *
     * @return \Oro\Bundle\InfinitePayBundle\Service\InfinitePay\DebtorData
     */
    public function setDebtorProviderId($DEBTOR_PROVIDER_ID)
    {
        $this->DEBTOR_PROVIDER_ID = $DEBTOR_PROVIDER_ID;

        return $this;
    }

    /**
     * @return string
     */
    public function getIpAdd()
    {
        return $this->IP_ADD;
    }

    /**
     * @param string $IP_ADD
     *
     * @return \Oro\Bundle\InfinitePayBundle\Service\InfinitePay\DebtorData
     */
    public function setIpAdd($IP_ADD)
    {
        $this->IP_ADD = $IP_ADD;

        return $this;
    }

    /**
     * @return string
     */
    public function getIsp()
    {
        return $this->ISP;
    }

    /**
     * @param string $ISP
     *
     * @return \Oro\Bundle\InfinitePayBundle\Service\InfinitePay\DebtorData
     */
    public function setIsp($ISP)
    {
        $this->ISP = $ISP;

        return $this;
    }

    /**
     * @return string
     */
    public function getNegPayHist()
    {
        return $this->NEG_PAY_HIST;
    }

    /**
     * @param string $NEG_PAY_HIST
     *
     * @return \Oro\Bundle\InfinitePayBundle\Service\InfinitePay\DebtorData
     */
    public function setNegPayHist($NEG_PAY_HIST)
    {
        $this->NEG_PAY_HIST = $NEG_PAY_HIST;

        return $this;
    }

    /**
     * @return string
     */
    public function getScore()
    {
        return $this->SCORE;
    }

    /**
     * @param string $SCORE
     *
     * @return \Oro\Bundle\InfinitePayBundle\Service\InfinitePay\DebtorData
     */
    public function setScore($SCORE)
    {
        $this->SCORE = $SCORE;

        return $this;
    }

    /**
     * @return string
     */
    public function getScoreDate()
    {
        return $this->SCORE_DATE;
    }

    /**
     * @param string $SCORE_DATE
     *
     * @return \Oro\Bundle\InfinitePayBundle\Service\InfinitePay\DebtorData
     */
    public function setScoreDate($SCORE_DATE)
    {
        $this->SCORE_DATE = $SCORE_DATE;

        return $this;
    }

    /**
     * @return string
     */
    public function getScoreProvider()
    {
        return $this->SCORE_PROVIDER;
    }

    /**
     * @param string $SCORE_PROVIDER
     *
     * @return \Oro\Bundle\InfinitePayBundle\Service\InfinitePay\DebtorData
     */
    public function setScoreProvider($SCORE_PROVIDER)
    {
        $this->SCORE_PROVIDER = $SCORE_PROVIDER;

        return $this;
    }

    /**
     * @return string
     */
    public function getUserDd1()
    {
        return $this->USER_DD_1;
    }

    /**
     * @param string $USER_DD_1
     *
     * @return \Oro\Bundle\InfinitePayBundle\Service\InfinitePay\DebtorData
     */
    public function setUserDd1($USER_DD_1)
    {
        $this->USER_DD_1 = $USER_DD_1;

        return $this;
    }

    /**
     * @return string
     */
    public function getUserDd10()
    {
        return $this->USER_DD_10;
    }

    /**
     * @param string $USER_DD_10
     *
     * @return \Oro\Bundle\InfinitePayBundle\Service\InfinitePay\DebtorData
     */
    public function setUserDd10($USER_DD_10)
    {
        $this->USER_DD_10 = $USER_DD_10;

        return $this;
    }

    /**
     * @return string
     */
    public function getUserDd2()
    {
        return $this->USER_DD_2;
    }

    /**
     * @param string $USER_DD_2
     *
     * @return \Oro\Bundle\InfinitePayBundle\Service\InfinitePay\DebtorData
     */
    public function setUserDd2($USER_DD_2)
    {
        $this->USER_DD_2 = $USER_DD_2;

        return $this;
    }

    /**
     * @return string
     */
    public function getUserDd3()
    {
        return $this->USER_DD_3;
    }

    /**
     * @param string $USER_DD_3
     *
     * @return \Oro\Bundle\InfinitePayBundle\Service\InfinitePay\DebtorData
     */
    public function setUserDd3($USER_DD_3)
    {
        $this->USER_DD_3 = $USER_DD_3;

        return $this;
    }

    /**
     * @return string
     */
    public function getUserDd4()
    {
        return $this->USER_DD_4;
    }

    /**
     * @param string $USER_DD_4
     *
     * @return \Oro\Bundle\InfinitePayBundle\Service\InfinitePay\DebtorData
     */
    public function setUserDd4($USER_DD_4)
    {
        $this->USER_DD_4 = $USER_DD_4;

        return $this;
    }

    /**
     * @return string
     */
    public function getUserDd5()
    {
        return $this->USER_DD_5;
    }

    /**
     * @param string $USER_DD_5
     *
     * @return \Oro\Bundle\InfinitePayBundle\Service\InfinitePay\DebtorData
     */
    public function setUserDd5($USER_DD_5)
    {
        $this->USER_DD_5 = $USER_DD_5;

        return $this;
    }

    /**
     * @return string
     */
    public function getUserDd6()
    {
        return $this->USER_DD_6;
    }

    /**
     * @param string $USER_DD_6
     *
     * @return \Oro\Bundle\InfinitePayBundle\Service\InfinitePay\DebtorData
     */
    public function setUserDd6($USER_DD_6)
    {
        $this->USER_DD_6 = $USER_DD_6;

        return $this;
    }

    /**
     * @return string
     */
    public function getUserDd7()
    {
        return $this->USER_DD_7;
    }

    /**
     * @param string $USER_DD_7
     *
     * @return \Oro\Bundle\InfinitePayBundle\Service\InfinitePay\DebtorData
     */
    public function setUserDd7($USER_DD_7)
    {
        $this->USER_DD_7 = $USER_DD_7;

        return $this;
    }

    /**
     * @return string
     */
    public function getUserDd8()
    {
        return $this->USER_DD_8;
    }

    /**
     * @param string $USER_DD_8
     *
     * @return \Oro\Bundle\InfinitePayBundle\Service\InfinitePay\DebtorData
     */
    public function setUserDd8($USER_DD_8)
    {
        $this->USER_DD_8 = $USER_DD_8;

        return $this;
    }

    /**
     * @return string
     */
    public function getUserDd9()
    {
        return $this->USER_DD_9;
    }

    /**
     * @param string $USER_DD_9
     *
     * @return \Oro\Bundle\InfinitePayBundle\Service\InfinitePay\DebtorData
     */
    public function setUserDd9($USER_DD_9)
    {
        $this->USER_DD_9 = $USER_DD_9;

        return $this;
    }
}
