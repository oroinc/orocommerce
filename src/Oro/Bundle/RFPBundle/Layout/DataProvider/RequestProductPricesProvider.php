<?php

namespace Oro\Bundle\RFPBundle\Layout\DataProvider;

use Oro\Bundle\PricingBundle\Model\ProductPriceInterface;
use Oro\Bundle\RFPBundle\Entity\Request as RFPRequest;
use Oro\Bundle\RFPBundle\Provider\RequestProductLineItemTierPricesProvider;

/**
 * Layout data provider of tier prices for an {@see RFPRequest}.
 */
class RequestProductPricesProvider
{
    private RequestProductLineItemTierPricesProvider $requestProductLineItemTierPricesProvider;

    public function __construct(RequestProductLineItemTierPricesProvider $requestProductLineItemTierPricesProvider)
    {
        $this->requestProductLineItemTierPricesProvider = $requestProductLineItemTierPricesProvider;
    }

    /**
     * @param RFPRequest $rfpRequest
     * @return array<int,array<int,ProductPriceInterface>>
     */
    public function getTierPrices(RFPRequest $rfpRequest): array
    {
        return $this->requestProductLineItemTierPricesProvider->getTierPrices($rfpRequest);
    }
}
