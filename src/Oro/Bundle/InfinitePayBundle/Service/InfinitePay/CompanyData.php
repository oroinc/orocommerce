<?php

namespace Oro\Bundle\InfinitePayBundle\Service\InfinitePay;

class CompanyData
{
    /**
     * @var string
     */
    protected $COM_ID_NUM;

    /**
     * @var string
     */
    protected $COM_ID_TYPE;

    /**
     * @var string
     */
    protected $COM_ID_VAT;

    /**
     * @var string
     */
    protected $OWNER_FS_NAME;

    /**
     * @var string
     */
    protected $OWNER_LS_NAME;

    /**
     * @var string
     */
    protected $COMPANY_NAME;

    /**
     * @param string $COM_ID_NUM
     * @param string $COM_ID_TYPE
     * @param string $COM_ID_VAT
     * @param string $OWNER_FS_NAME
     * @param string $OWNER_LS_NAME
     * @param string $COMPANY_NAME
     */
    public function __construct(
        $COM_ID_NUM = null,
        $COM_ID_TYPE = null,
        $COM_ID_VAT = null,
        $OWNER_FS_NAME = null,
        $OWNER_LS_NAME = null,
        $COMPANY_NAME = null
    ) {
        $this->COM_ID_NUM = $COM_ID_NUM;
        $this->COM_ID_TYPE = $COM_ID_TYPE;
        $this->COM_ID_VAT = $COM_ID_VAT;
        $this->OWNER_FS_NAME = $OWNER_FS_NAME;
        $this->OWNER_LS_NAME = $OWNER_LS_NAME;
        $this->COMPANY_NAME = $COMPANY_NAME;
    }

    /**
     * @return string
     */
    public function getComIdNum()
    {
        return $this->COM_ID_NUM;
    }

    /**
     * @param string $COM_ID_NUM
     *
     * @return \Oro\Bundle\InfinitePayBundle\Service\InfinitePay\CompanyData
     */
    public function setComIdNum($COM_ID_NUM)
    {
        $this->COM_ID_NUM = $COM_ID_NUM;

        return $this;
    }

    /**
     * @return string
     */
    public function getComIdType()
    {
        return $this->COM_ID_TYPE;
    }

    /**
     * @param string $COM_ID_TYPE
     *
     * @return \Oro\Bundle\InfinitePayBundle\Service\InfinitePay\CompanyData
     */
    public function setComIdType($COM_ID_TYPE)
    {
        $this->COM_ID_TYPE = $COM_ID_TYPE;

        return $this;
    }

    /**
     * @return string
     */
    public function getComIdVat()
    {
        return $this->COM_ID_VAT;
    }

    /**
     * @param string $COM_ID_VAT
     *
     * @return \Oro\Bundle\InfinitePayBundle\Service\InfinitePay\CompanyData
     */
    public function setComIdVat($COM_ID_VAT)
    {
        $this->COM_ID_VAT = $COM_ID_VAT;

        return $this;
    }

    /**
     * @return string
     */
    public function getOwnerFsName()
    {
        return $this->OWNER_FS_NAME;
    }

    /**
     * @param string $OWNER_FS_NAME
     *
     * @return \Oro\Bundle\InfinitePayBundle\Service\InfinitePay\CompanyData
     */
    public function setOwnerFsName($OWNER_FS_NAME)
    {
        $this->OWNER_FS_NAME = $OWNER_FS_NAME;

        return $this;
    }

    /**
     * @return string
     */
    public function getOwnerLsName()
    {
        return $this->OWNER_LS_NAME;
    }

    /**
     * @param string $OWNER_LS_NAME
     *
     * @return \Oro\Bundle\InfinitePayBundle\Service\InfinitePay\CompanyData
     */
    public function setOwnerLsName($OWNER_LS_NAME)
    {
        $this->OWNER_LS_NAME = $OWNER_LS_NAME;

        return $this;
    }

    /**
     * @return string
     */
    public function getCompanyName()
    {
        return $this->COMPANY_NAME;
    }

    /**
     * @param string $COMPANY_NAME
     *
     * @return \Oro\Bundle\InfinitePayBundle\Service\InfinitePay\CompanyData
     */
    public function setCompanyName($COMPANY_NAME)
    {
        $this->COMPANY_NAME = $COMPANY_NAME;

        return $this;
    }
}
