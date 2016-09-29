<?php

namespace Oro\Bundle\InfinitePayBundle\Service\InfinitePay;

class DebtorCorrectedData
{
    /**
     * @var string
     */
    protected $C_CITY;

    /**
     * @var string
     */
    protected $C_COUNTRY;

    /**
     * @var string
     */
    protected $C_NAME;

    /**
     * @var string
     */
    protected $C_STREET;

    /**
     * @var string
     */
    protected $C_STREET_NO;

    /**
     * @var string
     */
    protected $C_ZIP;

    /**
     * @var int
     */
    protected $DB_ID;

    /**
     * @param string $C_CITY
     * @param string $C_COUNTRY
     * @param string $C_NAME
     * @param string $C_STREET
     * @param string $C_STREET_NO
     * @param string $C_ZIP
     * @param int    $DB_ID
     */
    public function __construct(
        $C_CITY = null,
        $C_COUNTRY = null,
        $C_NAME = null,
        $C_STREET = null,
        $C_STREET_NO = null,
        $C_ZIP = null,
        $DB_ID = null
    ) {
        $this->C_CITY = $C_CITY;
        $this->C_COUNTRY = $C_COUNTRY;
        $this->C_NAME = $C_NAME;
        $this->C_STREET = $C_STREET;
        $this->C_STREET_NO = $C_STREET_NO;
        $this->C_ZIP = $C_ZIP;
        $this->DB_ID = $DB_ID;
    }

    /**
     * @return string
     */
    public function getCCity()
    {
        return $this->C_CITY;
    }

    /**
     * @param string $C_CITY
     *
     * @return \Oro\Bundle\InfinitePayBundle\Service\InfinitePay\DebtorCorrectedData
     */
    public function setCCity($C_CITY)
    {
        $this->C_CITY = $C_CITY;

        return $this;
    }

    /**
     * @return string
     */
    public function getCCountry()
    {
        return $this->C_COUNTRY;
    }

    /**
     * @param string $C_COUNTRY
     *
     * @return \Oro\Bundle\InfinitePayBundle\Service\InfinitePay\DebtorCorrectedData
     */
    public function setCCountry($C_COUNTRY)
    {
        $this->C_COUNTRY = $C_COUNTRY;

        return $this;
    }

    /**
     * @return string
     */
    public function getCName()
    {
        return $this->C_NAME;
    }

    /**
     * @param string $C_NAME
     *
     * @return \Oro\Bundle\InfinitePayBundle\Service\InfinitePay\DebtorCorrectedData
     */
    public function setCName($C_NAME)
    {
        $this->C_NAME = $C_NAME;

        return $this;
    }

    /**
     * @return string
     */
    public function getCStreet()
    {
        return $this->C_STREET;
    }

    /**
     * @param string $C_STREET
     *
     * @return \Oro\Bundle\InfinitePayBundle\Service\InfinitePay\DebtorCorrectedData
     */
    public function setCStreet($C_STREET)
    {
        $this->C_STREET = $C_STREET;

        return $this;
    }

    /**
     * @return string
     */
    public function getCStreetNo()
    {
        return $this->C_STREET_NO;
    }

    /**
     * @param string $C_STREET_NO
     *
     * @return \Oro\Bundle\InfinitePayBundle\Service\InfinitePay\DebtorCorrectedData
     */
    public function setCStreetNo($C_STREET_NO)
    {
        $this->C_STREET_NO = $C_STREET_NO;

        return $this;
    }

    /**
     * @return string
     */
    public function getCZip()
    {
        return $this->C_ZIP;
    }

    /**
     * @param string $C_ZIP
     *
     * @return \Oro\Bundle\InfinitePayBundle\Service\InfinitePay\DebtorCorrectedData
     */
    public function setCZip($C_ZIP)
    {
        $this->C_ZIP = $C_ZIP;

        return $this;
    }

    /**
     * @return int
     */
    public function getDbId()
    {
        return $this->DB_ID;
    }

    /**
     * @param int $DB_ID
     *
     * @return \Oro\Bundle\InfinitePayBundle\Service\InfinitePay\DebtorCorrectedData
     */
    public function setDbId($DB_ID)
    {
        $this->DB_ID = $DB_ID;

        return $this;
    }
}
