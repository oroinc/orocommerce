<?php

namespace Oro\Bundle\UPSBundle\Cache;

use Oro\Bundle\UPSBundle\Entity\UPSTransport;
use Oro\Bundle\UPSBundle\Model\PriceRequest;

class ShippingPriceCacheKey
{
    /**
     * @var UPSTransport
     */
    private $transport;

    /**
     * @var PriceRequest
     */
    private $priceRequest;

    /**
     * @var string
     */
    private $methodId;

    /**
     * @var string
     */
    private $typeId;

    /**
     * @return UPSTransport
     */
    public function getTransport()
    {
        return $this->transport;
    }

    /**
     * @param UPSTransport $transport
     * @return $this
     */
    public function setTransport($transport)
    {
        $this->transport = $transport;
        return $this;
    }

    /**
     * @return PriceRequest
     */
    public function getPriceRequest()
    {
        return $this->priceRequest;
    }

    /**
     * @param PriceRequest $request
     * @return $this
     */
    public function setPriceRequest($request)
    {
        $this->priceRequest = $request;
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
    public function getTypeId()
    {
        return $this->typeId;
    }

    /**
     * @param string $typeId
     * @return $this
     */
    public function setTypeId($typeId)
    {
        $this->typeId = $typeId;
        return $this;
    }

    /**
     * @return string
     */
    public function generateKey()
    {
        $requestData = json_decode($this->priceRequest->toJson(), true);
        if (array_key_exists('Service', $requestData['RateRequest']['Shipment'])) {
            unset($requestData['RateRequest']['Shipment']['Service']);
        }
        unset($requestData['UPSSecurity'], $requestData['RateRequest']['Request']);

        return implode('_', [
            md5(serialize($requestData)),
            $this->methodId,
            $this->typeId,
            $this->transport ? $this->transport->getId() : null,
        ]);
    }
}
