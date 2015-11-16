<?php

namespace OroB2B\Bundle\RFPBundle\Storage;

use Oro\Bundle\LocaleBundle\Formatter\NumberFormatter;

use OroB2B\Bundle\ProductBundle\Formatter\ProductUnitValueFormatter;
use OroB2B\Bundle\RFPBundle\Entity\Request as RFPRequest;
use OroB2B\Bundle\ProductBundle\Storage\ProductDataStorage as Storage;

class ProductDataStorage
{
    /**
     * @param Storage                   $productDataStorage
     * @param NumberFormatter           $numberFormatter
     * @param ProductUnitValueFormatter $productUnitValueFormatter
     */
    public function __construct(
        Storage $productDataStorage,
        NumberFormatter $numberFormatter,
        ProductUnitValueFormatter $productUnitValueFormatter
    ) {
        $this->productDataStorage = $productDataStorage;
        $this->numberFormatter = $numberFormatter;
        $this->productUnitValueFormatter = $productUnitValueFormatter;
    }

    /**
     * @param RFPRequest $request
     */
    public function saveToStorage(RFPRequest $request)
    {
        $data = [
            'withOffers'             => 1,
            Storage::ENTITY_DATA_KEY => [
                'accountUser' => $request->getAccountUser()->getId(),
                'account'     => $request->getAccount()->getId(),
            ]
        ];
        foreach ($request->getRequestProducts() as $lineItem) {
            $offers = [];
            foreach ($lineItem->getRequestProductItems() as $productItem) {
                $currency = $productItem->getPrice() ? $productItem->getPrice()->getCurrency() : null;
                $priceValue = $productItem->getPrice() ? $productItem->getPrice()->getValue() : 0;
                $offers[] = [
                    'quantity'          => $productItem->getQuantity(),
                    'unit'              => $productItem->getProductUnit(),
                    'currency'          => $currency,
                    'price'             => $priceValue
                ];
            }

            $data[Storage::ENTITY_ITEMS_DATA_KEY][] = [
                Storage::PRODUCT_SKU_KEY => $lineItem->getProduct()->getSku(),
                'comment'                => $lineItem->getComment(),
                'offers'                 => $offers,
            ];
        }
        $this->productDataStorage->set($data);
    }
}
