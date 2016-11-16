<?php

namespace Oro\Bundle\RFPBundle\Layout\DataProvider;

use Oro\Bundle\PricingBundle\Entity\Repository\ProductPriceRepository;
use Oro\Bundle\PricingBundle\Model\PriceListRequestHandler;
use Oro\Component\Layout\DataProvider\AbstractFormProvider;
use Oro\Bundle\RFPBundle\Entity\Request as RFPRequest;

class TierPricesProvider extends AbstractFormProvider
{
    /**
     * @var ProductPriceRepository
     */
    private $productPriceRepository;

    /**
     * @var PriceListRequestHandler
     */
    private $priceListRequestHandler;

    /**
     * @param ProductPriceRepository $productPriceRepository
     * @param PriceListRequestHandler $priceListRequestHandler
     */
    public function __construct(ProductPriceRepository $productPriceRepository, PriceListRequestHandler $priceListRequestHandler)
    {
        $this->productPriceRepository = $productPriceRepository;
        $this->priceListRequestHandler = $priceListRequestHandler;
    }

    /**
     * @param RFPRequest $rfpRequest
     * @return array
     */
    public function getPrices(RFPRequest $rfpRequest)
    {
        $productIds = [];

        foreach ($rfpRequest->getRequestProducts() as $requestProduct) {
            $productIds[] = $requestProduct->getProduct()->getId();
        }

        $prices = $this->productPriceRepository->findByPriceListIdAndProductIds(
            $this->priceListRequestHandler->getPriceList(),
            $productIds,
            true,
            null,
            null,
            ['unit' => 'asc', 'value' => 'asc']
        );

        $result = [];

        foreach ($prices as $price) {
            $entryId = $price->getProduct()->getId();

            if (!isset($result[$entryId])) {
                $result[$entryId] = [];
            }

            $result[$entryId][] = [
                'currency' => $price->getPrice()->getCurrency(),
                'price' => $price->getPrice()->getValue(),
                'quantity' => 1,
                'unit' => $price->getUnit()->getCode()
            ];
        }

        return $result;
    }
}
