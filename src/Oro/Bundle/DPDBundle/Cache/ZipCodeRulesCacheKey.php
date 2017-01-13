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
     * @var string
     */
    private $methodId;

    /**
     * @return DPDTransport
     */
    public function getTransport()
    {
        return $this->transport;
    }

    /**
     * @param DPDTransport $transport
     * @return $this
     */
    public function setTransport($transport)
    {
        $this->transport = $transport;
        return $this;
    }

    /**
     * @return ZipCodeRulesRequest
     */
    public function geZipCodeRulesRequest()
    {
        return $this->zipCodeRulesRequest;
    }

    /**
     * @param ZipCodeRulesRequest $request
     * @return $this
     */
    public function setZipCodeRulesRequest($request)
    {
        $this->zipCodeRulesRequest = $request;
        return $this;
    }

    /**
     * @return string
     */
    public function getMethodId()
    {
        return $this->methodId;
    }

    /**
     * @param string $methodId
     * @return $this
     */
    public function setMethodId($methodId)
    {
        $this->methodId = $methodId;
        return $this;
    }

    /**
     * @return string
     */
    public function generateKey()
    {
        return implode('_', [
            $this->methodId,
            $this->transport ? $this->transport->getId() : null,
        ]);
    }
}
