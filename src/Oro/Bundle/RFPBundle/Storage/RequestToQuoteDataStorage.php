<?php

namespace Oro\Bundle\RFPBundle\Storage;

use Doctrine\Common\Collections\Collection;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceList;
use Oro\Bundle\PricingBundle\Model\PriceListTreeHandler;
use Oro\Bundle\PricingBundle\Provider\MatchingPriceProvider;
use Oro\Bundle\ProductBundle\Storage\ProductDataStorage;
use Oro\Bundle\RFPBundle\Entity\Request as RFPRequest;
use Oro\Bundle\RFPBundle\Entity\RequestProductItem;

/**
 * Extracts data from RFPRequest and puts it into storage. Saved data is used later during Quote creation.
 */
class RequestToQuoteDataStorage
{
    /** @var ProductDataStorage */
    protected $storage;

    /**
     * @var MatchingPriceProvider
     */
    private $matchingPriceProvider;

    /**
     * @var PriceListTreeHandler
     */
    private $priceListTreeHandler;

    /**
     * @param ProductDataStorage    $storage
     * @param MatchingPriceProvider $matchingPriceProvider
     * @param PriceListTreeHandler  $priceListTreeHandler
     */
    public function __construct(
        ProductDataStorage $storage,
        MatchingPriceProvider $matchingPriceProvider,
        PriceListTreeHandler $priceListTreeHandler
    ) {
        $this->storage = $storage;
        $this->matchingPriceProvider = $matchingPriceProvider;
        $this->priceListTreeHandler = $priceListTreeHandler;
    }

    /**
     * @param RFPRequest $rfpRequest
     */
    public function saveToStorage(RFPRequest $rfpRequest)
    {
        $data = [
            ProductDataStorage::ENTITY_DATA_KEY => [
                'customerUser' => $rfpRequest->getCustomerUser() ? $rfpRequest->getCustomerUser()->getId() : null,
                'customer' => $rfpRequest->getCustomer() ? $rfpRequest->getCustomer()->getId() : null,
                'request' => $rfpRequest->getId(),
                'poNumber' => $rfpRequest->getPoNumber(),
                'shipUntil' => $rfpRequest->getShipUntil(),
                'assignedUsers' => $this->getEntitiesIds($rfpRequest->getAssignedUsers()),
                'assignedCustomerUsers' => $this->getEntitiesIds($rfpRequest->getAssignedCustomerUsers()),
                'website' => $rfpRequest->getWebsite() ? $rfpRequest->getWebsite()->getId() : null,
            ],
        ];

        foreach ($rfpRequest->getRequestProducts() as $requestProduct) {
            $items = [];
            foreach ($requestProduct->getRequestProductItems() as $requestProductItem) {
                $productUnitCode = $requestProductItem->getProductUnit()
                    ? $requestProductItem->getProductUnit()->getCode()
                    : null;

                $items[] = [
                    'price' => $requestProductItem->getPrice() ?: $this->getListedPrice($requestProductItem),
                    'quantity' => $requestProductItem->getQuantity(),
                    'productUnit' => $productUnitCode,
                    'productUnitCode' => $productUnitCode,
                    'requestProductItem' => $requestProductItem->getId(),
                ];
            }

            $data[ProductDataStorage::ENTITY_ITEMS_DATA_KEY][] = [
                ProductDataStorage::PRODUCT_SKU_KEY =>  $requestProduct->getProductSku(),
                ProductDataStorage::PRODUCT_QUANTITY_KEY => null,
                'commentCustomer' => $requestProduct->getComment(),
                'requestProductItems' => $items,
            ];
        }

        $this->storage->set($data);
    }

    /**
     * @param Collection $collection
     * @return array
     */
    protected function getEntitiesIds(Collection $collection)
    {
        $ids = [];

        foreach ($collection as $item) {
            if (method_exists($item, 'getId') && $item->getId()) {
                $ids[] = $item->getId();
            }
        }

        return $ids;
    }

    /**
     * @param RequestProductItem $requestProductItem
     *
     * @return Price
     */
    private function getListedPrice(RequestProductItem $requestProductItem)
    {
        $request = $requestProductItem->getRequestProduct()->getRequest();

        $value = null;
        $currency = null;

        $priceList = $this->priceListTreeHandler->getPriceList($request->getCustomer(), $request->getWebsite());
        if ($priceList) {
            $matchingPrices = $this->getMatchingPrices($requestProductItem, $priceList);
            if ($matchingPrices) {
                list($value, $currency) = array_values(current($matchingPrices));
            }
        }

        return Price::create($value, $currency);
    }

    /**
     * @param RequestProductItem $requestProductItem
     * @param CombinedPriceList  $priceList
     *
     * @return array
     */
    private function getMatchingPrices(RequestProductItem $requestProductItem, CombinedPriceList $priceList)
    {
        if (!$requestProductItem->getProduct()) {
            return [];
        }

        $lineItems = [];
        foreach ($priceList->getCurrencies() as $enabledCurrency) {
            $lineItems[] = [
                'product' => $requestProductItem->getProduct()->getId(),
                'unit' => $requestProductItem->getProductUnitCode(),
                'qty' => $requestProductItem->getQuantity(),
                'currency' => $enabledCurrency,
            ];
        }

        return $this->matchingPriceProvider->getMatchingPrices($lineItems, $priceList);
    }
}
