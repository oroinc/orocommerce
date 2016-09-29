<?php

namespace Oro\Bundle\InfinitePayBundle\Service\InfinitePay;

/**
 * @SuppressWarnings(PHPMD.ExcessiveParameterList)
 */
class ShippingData
{
    /**
     * @var string
     */
    protected $DD_BIRTH_DT;

    /**
     * @var string
     */
    protected $DD_CITY;

    /**
     * @var string
     */
    protected $DD_COUNTRY;

    /**
     * @var string
     */
    protected $DD_EMAIL;

    /**
     * @var string
     */
    protected $DD_MOBILE;

    /**
     * @var string
     */
    protected $DD_NAME_FS;

    /**
     * @var string
     */
    protected $DD_NAME_LS;

    /**
     * @var string
     */
    protected $DD_SALUT;

    /**
     * @var string
     */
    protected $DD_STREET;

    /**
     * @var string
     */
    protected $DD_STREET_NR;

    /**
     * @var string
     */
    protected $DD_TEL;

    /**
     * @var string
     */
    protected $DD_TITLE;

    /**
     * @var string
     */
    protected $DD_ZIP;

    /**
     * @var string
     */
    protected $USE_BILL_DATA;

    /**
     * @param string $DD_BIRTH_DT
     * @param string $DD_CITY
     * @param string $DD_COUNTRY
     * @param string $DD_EMAIL
     * @param string $DD_MOBILE
     * @param string $DD_NAME_FS
     * @param string $DD_NAME_LS
     * @param string $DD_SALUT
     * @param string $DD_STREET
     * @param string $DD_STREET_NR
     * @param string $DD_TEL
     * @param string $DD_TITLE
     * @param string $DD_ZIP
     * @param string $USE_BILL_DATA
     */
    public function __construct(
        $DD_BIRTH_DT = null,
        $DD_CITY = null,
        $DD_COUNTRY = null,
        $DD_EMAIL = null,
        $DD_MOBILE = null,
        $DD_NAME_FS = null,
        $DD_NAME_LS = null,
        $DD_SALUT = null,
        $DD_STREET = null,
        $DD_STREET_NR = null,
        $DD_TEL = null,
        $DD_TITLE = null,
        $DD_ZIP = null,
        $USE_BILL_DATA = null
    ) {
        $this->DD_BIRTH_DT = $DD_BIRTH_DT;
        $this->DD_CITY = $DD_CITY;
        $this->DD_COUNTRY = $DD_COUNTRY;
        $this->DD_EMAIL = $DD_EMAIL;
        $this->DD_MOBILE = $DD_MOBILE;
        $this->DD_NAME_FS = $DD_NAME_FS;
        $this->DD_NAME_LS = $DD_NAME_LS;
        $this->DD_SALUT = $DD_SALUT;
        $this->DD_STREET = $DD_STREET;
        $this->DD_STREET_NR = $DD_STREET_NR;
        $this->DD_TEL = $DD_TEL;
        $this->DD_TITLE = $DD_TITLE;
        $this->DD_ZIP = $DD_ZIP;
        $this->USE_BILL_DATA = $USE_BILL_DATA;
    }

    /**
     * @return string
     */
    public function getDdBirthDt()
    {
        return $this->DD_BIRTH_DT;
    }

    /**
     * @param string $DD_BIRTH_DT
     *
     * @return \Oro\Bundle\InfinitePayBundle\Service\InfinitePay\ShippingData
     */
    public function setDdBirthDt($DD_BIRTH_DT)
    {
        $this->DD_BIRTH_DT = $DD_BIRTH_DT;

        return $this;
    }

    /**
     * @return string
     */
    public function getDdCity()
    {
        return $this->DD_CITY;
    }

    /**
     * @param string $DD_CITY
     *
     * @return \Oro\Bundle\InfinitePayBundle\Service\InfinitePay\ShippingData
     */
    public function setDdCity($DD_CITY)
    {
        $this->DD_CITY = $DD_CITY;

        return $this;
    }

    /**
     * @return string
     */
    public function getDdCountry()
    {
        return $this->DD_COUNTRY;
    }

    /**
     * @param string $DD_COUNTRY
     *
     * @return \Oro\Bundle\InfinitePayBundle\Service\InfinitePay\ShippingData
     */
    public function setDdCountry($DD_COUNTRY)
    {
        $this->DD_COUNTRY = $DD_COUNTRY;

        return $this;
    }

    /**
     * @return string
     */
    public function getDdEmail()
    {
        return $this->DD_EMAIL;
    }

    /**
     * @param string $DD_EMAIL
     *
     * @return \Oro\Bundle\InfinitePayBundle\Service\InfinitePay\ShippingData
     */
    public function setDdEmail($DD_EMAIL)
    {
        $this->DD_EMAIL = $DD_EMAIL;

        return $this;
    }

    /**
     * @return string
     */
    public function getDdMobile()
    {
        return $this->DD_MOBILE;
    }

    /**
     * @param string $DD_MOBILE
     *
     * @return \Oro\Bundle\InfinitePayBundle\Service\InfinitePay\ShippingData
     */
    public function setDdMobile($DD_MOBILE)
    {
        $this->DD_MOBILE = $DD_MOBILE;

        return $this;
    }

    /**
     * @return string
     */
    public function getDdNameFs()
    {
        return $this->DD_NAME_FS;
    }

    /**
     * @param string $DD_NAME_FS
     *
     * @return \Oro\Bundle\InfinitePayBundle\Service\InfinitePay\ShippingData
     */
    public function setDdNameFs($DD_NAME_FS)
    {
        $this->DD_NAME_FS = $DD_NAME_FS;

        return $this;
    }

    /**
     * @return string
     */
    public function getDdNameLs()
    {
        return $this->DD_NAME_LS;
    }

    /**
     * @param string $DD_NAME_LS
     *
     * @return \Oro\Bundle\InfinitePayBundle\Service\InfinitePay\ShippingData
     */
    public function setDdNameLs($DD_NAME_LS)
    {
        $this->DD_NAME_LS = $DD_NAME_LS;

        return $this;
    }

    /**
     * @return string
     */
    public function getDdSalut()
    {
        return $this->DD_SALUT;
    }

    /**
     * @param string $DD_SALUT
     *
     * @return \Oro\Bundle\InfinitePayBundle\Service\InfinitePay\ShippingData
     */
    public function setDdSalut($DD_SALUT)
    {
        $this->DD_SALUT = $DD_SALUT;

        return $this;
    }

    /**
     * @return string
     */
    public function getDdStreet()
    {
        return $this->DD_STREET;
    }

    /**
     * @param string $DD_STREET
     *
     * @return \Oro\Bundle\InfinitePayBundle\Service\InfinitePay\ShippingData
     */
    public function setDdStreet($DD_STREET)
    {
        $this->DD_STREET = $DD_STREET;

        return $this;
    }

    /**
     * @return string
     */
    public function getDdStreetNr()
    {
        return $this->DD_STREET_NR;
    }

    /**
     * @param string $DD_STREET_NR
     *
     * @return \Oro\Bundle\InfinitePayBundle\Service\InfinitePay\ShippingData
     */
    public function setDdStreetNr($DD_STREET_NR)
    {
        $this->DD_STREET_NR = $DD_STREET_NR;

        return $this;
    }

    /**
     * @return string
     */
    public function getDdTel()
    {
        return $this->DD_TEL;
    }

    /**
     * @param string $DD_TEL
     *
     * @return \Oro\Bundle\InfinitePayBundle\Service\InfinitePay\ShippingData
     */
    public function setDdTel($DD_TEL)
    {
        $this->DD_TEL = $DD_TEL;

        return $this;
    }

    /**
     * @return string
     */
    public function getDdTitle()
    {
        return $this->DD_TITLE;
    }

    /**
     * @param string $DD_TITLE
     *
     * @return \Oro\Bundle\InfinitePayBundle\Service\InfinitePay\ShippingData
     */
    public function setDdTitle($DD_TITLE)
    {
        $this->DD_TITLE = $DD_TITLE;

        return $this;
    }

    /**
     * @return string
     */
    public function getDdZip()
    {
        return $this->DD_ZIP;
    }

    /**
     * @param string $DD_ZIP
     *
     * @return \Oro\Bundle\InfinitePayBundle\Service\InfinitePay\ShippingData
     */
    public function setDdZip($DD_ZIP)
    {
        $this->DD_ZIP = $DD_ZIP;

        return $this;
    }

    /**
     * @return string
     */
    public function getUseBillData()
    {
        return $this->USE_BILL_DATA;
    }

    /**
     * @param string $USE_BILL_DATA
     *
     * @return \Oro\Bundle\InfinitePayBundle\Service\InfinitePay\ShippingData
     */
    public function setUseBillData($USE_BILL_DATA)
    {
        $this->USE_BILL_DATA = $USE_BILL_DATA;

        return $this;
    }
}
