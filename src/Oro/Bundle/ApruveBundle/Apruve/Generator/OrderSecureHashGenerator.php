<?php

namespace Oro\Bundle\ApruveBundle\Apruve\Generator;

use Oro\Bundle\ApruveBundle\Apruve\Model\ApruveLineItem;
use Oro\Bundle\ApruveBundle\Apruve\Model\ApruveOrder;

/**
 * @see https://docs.apruve.com/guides/merchant-integration-tutorial#1b-creating-a-secure-hash
 */
class OrderSecureHashGenerator implements OrderSecureHashGeneratorInterface
{
    /**
     * @internal
     */
    const HASH_ALGORITHM = 'sha256';

    /**
     * @internal
     */
    const DELIMITER = '';

    /**
     * Order fields order.
     */
    const ORDER_FIELDS_ORDER = [
        ApruveOrder::MERCHANT_ID,
        ApruveOrder::MERCHANT_ORDER_ID,
        ApruveOrder::AMOUNT_CENTS,
        ApruveOrder::CURRENCY,
        ApruveOrder::TAX_CENTS,
        ApruveOrder::SHIPPING_CENTS,
        ApruveOrder::EXPIRE_AT,
        ApruveOrder::ACCEPTS_PAYMENT_TERMS,
        ApruveOrder::FINALIZE_ON_CREATE,
        ApruveOrder::INVOICE_ON_CREATE,
    ];

    /**
     * Line Item fields order.
     */
    const LINE_ITEM_FIELDS_ORDER = [
        ApruveLineItem::TITLE,
        ApruveLineItem::AMOUNT_CENTS,
        ApruveLineItem::PRICE_EA_CENTS,
        ApruveLineItem::QUANTITY,
        ApruveLineItem::MERCHANT_NOTES,
        ApruveLineItem::DESCRIPTION,
        ApruveLineItem::VARIANT_INFO,
        ApruveLineItem::SKU,
        ApruveLineItem::VENDOR,
        ApruveLineItem::VIEW_PRODUCT_URL,
    ];

    /**
     * {@inheritDoc}
     */
    public function generate(ApruveOrder $apruveOrder, $apiKey)
    {
        $data = $apruveOrder->getData();

        // API key must be in the beginning.
        $secureArray[] = $apiKey;

        // Then goes order data.
        $secureArray = array_merge($secureArray, $this->prepareSecureArray($data, self::ORDER_FIELDS_ORDER));

        // Then goes order line items data.
        foreach ($data[ApruveOrder::LINE_ITEMS] as $lineItemData) {
            $secureArray = array_merge(
                $secureArray,
                $this->prepareSecureArray($lineItemData, self::LINE_ITEM_FIELDS_ORDER)
            );
        }

        $secureString = $this->convertToString($secureArray);

        return $this->makeSecureHash($secureString);
    }

    /**
     * @param string $secureString
     *
     * @return string
     */
    protected function makeSecureHash($secureString)
    {
        return hash(self::HASH_ALGORITHM, $secureString);
    }

    /**
     * @param array $array
     *
     * @return string
     */
    protected function convertToString(array $array)
    {
        $array = array_map([$this, 'convertTypes'], $array);

        return implode(self::DELIMITER, $array);
    }

    /**
     * @param array $array
     * @param array $requiredOrderedFields
     *
     * @return array
     */
    protected function prepareSecureArray(array $array, array $requiredOrderedFields)
    {
        // Remove elements which are not present in the array template.
        $array = array_intersect_key($array, array_flip($requiredOrderedFields));

        // Sort elements according to required fields array.
        $array = array_merge(array_flip($requiredOrderedFields), $array);

        return array_values($array);
    }

    /**
     * Callback for array_map to convert types to string representation.
     *
     * @param mixed $value
     *
     * @return mixed
     */
    protected function convertTypes($value)
    {
        switch (true) {
            case ($value === true):
                return 'true';
                break;

            case ($value === false):
                return 'false';
                break;

            default:
                return $value;
        }
    }
}
