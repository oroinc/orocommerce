<?php

namespace Oro\Bundle\DPDBundle\Cache;

use Oro\Bundle\DPDBundle\Entity\DPDTransport;
use Oro\Bundle\DPDBundle\Model\ZipCodeRulesRequest;

class ZipCodeRulesCacheKey
{
    /**
     * @var DPDTransport
     */
    private $transport;

    /**
     * @var ZipCodeRulesRequest
     */
    private $zipCodeRulesRequest;

    /**
     * @return DPDTransport
     */
    public function getTransport()
    {
        return $this->transport;
    }

    /**
     * @param DPDTransport $transport
     *
     * @return $this
     */
    public function setTransport(DPDTransport $transport)
    {
        $this->transport = $transport;

        return $this;
    }

    /**
     * @return ZipCodeRulesRequest
     */
    public function getZipCodeRulesRequest()
    {
        return $this->zipCodeRulesRequest;
    }

    /**
     * @param ZipCodeRulesRequest $request
     *
     * @return $this
     */
    public function setZipCodeRulesRequest(ZipCodeRulesRequest $request)
    {
        $this->zipCodeRulesRequest = $request;

        return $this;
    }

    /**
     * @return string
     */
    public function generateKey()
    {
        return $this->transport ? $this->transport->getId() : '';
    }
}
