<?php

namespace Oro\Bundle\InfinitePayBundle\Service\InfinitePay;

class ClientData
{
    /**
     * @var string
     */
    protected $CLIENT_REF;

    /**
     * @var string
     */
    protected $SECURITY_CD;

    /**
     * @param string $CLIENT_REF
     * @param string $SECURITY_CD
     */
    public function __construct($CLIENT_REF = null, $SECURITY_CD = null)
    {
        $this->CLIENT_REF = $CLIENT_REF;
        $this->SECURITY_CD = $SECURITY_CD;
    }

    /**
     * @return string
     */
    public function getClientRef()
    {
        return $this->CLIENT_REF;
    }

    /**
     * @param string $CLIENT_REF
     *
     * @return \Oro\Bundle\InfinitePayBundle\Service\InfinitePay\ClientData
     */
    public function setClientRef($CLIENT_REF)
    {
        $this->CLIENT_REF = $CLIENT_REF;

        return $this;
    }

    /**
     * @return string
     */
    public function getSecurityCd()
    {
        return $this->SECURITY_CD;
    }

    /**
     * @param string $SECURITY_CD
     *
     * @return \Oro\Bundle\InfinitePayBundle\Service\InfinitePay\ClientData
     */
    public function setSecurityCd($SECURITY_CD)
    {
        $this->SECURITY_CD = $SECURITY_CD;

        return $this;
    }
}
