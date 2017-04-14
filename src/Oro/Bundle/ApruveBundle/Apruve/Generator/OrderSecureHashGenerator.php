<?php

namespace Oro\Bundle\ApruveBundle\Apruve\Generator;

use Oro\Bundle\ApruveBundle\Apruve\Model\Order\ApruveOrderInterface;
use Oro\Bundle\ApruveBundle\Method\Config\ApruveConfigInterface;

/**
 * @see https://docs.apruve.com/guides/merchant-integration-tutorial#1b-creating-a-secure-hash
 */
class OrderSecureHashGenerator implements OrderSecureHashGeneratorInterface
{
    const HASH_ALGO = 'sha256';
    const DELIMITER = '';

    /**
     * Order
     */
    const ORDER_MERCHANT_ID = 'merchant_id';
    const ORDER_AMOUNT_CENTS = 'amount_cents';
    const ORDER_CURRENCY = 'currency';
    const ORDER_LINE_ITEMS = 'order_items';
    const ORDER_MERCHANT_ORDER_ID = 'merchant_order_id';
    const ORDER_TAX_CENTS = 'tax_cents';
    const ORDER_SHIPPING_CENTS = 'shipping_cents';
    const ORDER_EXPIRE_AT = 'expire_at';
    const ORDER_ACCEPTS_PT = 'accepts_payment_terms';
    const ORDER_FINALIZE_ON_CREATE = 'finalize_on_create';
    const ORDER_INVOICE_ON_CREATE = 'invoice_on_create';

    const ORDER_FIELDS_ORDER = [
        self::ORDER_MERCHANT_ID,
        self::ORDER_MERCHANT_ORDER_ID,
        self::ORDER_AMOUNT_CENTS,
        self::ORDER_CURRENCY,
        self::ORDER_TAX_CENTS,
        self::ORDER_SHIPPING_CENTS,
        self::ORDER_EXPIRE_AT,
        self::ORDER_ACCEPTS_PT,
        self::ORDER_FINALIZE_ON_CREATE,
        self::ORDER_INVOICE_ON_CREATE,
    ];

    /**
     * Line Item
     */
    const LINE_ITEM_PRICE_TOTAL_CENTS = 'price_total_cents';
    /**
     * Property 'price_total_cents' is not respected by Apruve when secure hash is generated,
     * hence use 'amount_cents' instead.
     */
    const LINE_ITEM_AMOUNT_CENTS = 'amount_cents';
    const LINE_ITEM_QUANTITY = 'quantity';
    const LINE_ITEM_VARIANT_INFO = 'variant_info';
    const LINE_ITEM_SKU = 'sku';
    const LINE_ITEM_TITLE = 'title';
    const LINE_ITEM_DESCRIPTION = 'description';
    const LINE_ITEM_VIEW_PRODUCT_URL = 'view_product_url';
    const LINE_ITEM_PRICE_EA_CENTS = 'price_ea_cents';
    const LINE_ITEM_VENDOR = 'vendor';
    const LINE_ITEM_MERCHANT_NOTES = 'merchant_notes';

    const LINE_ITEM_FIELDS_ORDER = [
        self::LINE_ITEM_TITLE,
        self::LINE_ITEM_AMOUNT_CENTS,
        self::LINE_ITEM_PRICE_EA_CENTS,
        self::LINE_ITEM_QUANTITY,
        self::LINE_ITEM_MERCHANT_NOTES,
        self::LINE_ITEM_DESCRIPTION,
        self::LINE_ITEM_VARIANT_INFO,
        self::LINE_ITEM_SKU,
        self::LINE_ITEM_VENDOR,
        self::LINE_ITEM_VIEW_PRODUCT_URL,
    ];

    /**
     * {@inheritDoc}
     */
    public function generate(ApruveOrderInterface $apruveOrder, ApruveConfigInterface $config)
    {
        $data = $apruveOrder->getData();
        $lineItems =& $data[self::ORDER_LINE_ITEMS];

        // API key must be in the beginning of string.
        $secureString = $config->getApiKey();
        $secureString .= $this->makeSecureString($data, self::ORDER_FIELDS_ORDER);

        foreach ((array) $lineItems as $lineItemData) {
            $secureString .= $this->makeSecureString($lineItemData, self::LINE_ITEM_FIELDS_ORDER);
        }

        return $this->makeSecureHash($secureString);
    }

    /**
     * @param string $secureString
     *
     * @return string
     */
    protected function makeSecureHash($secureString)
    {
        return hash(self::HASH_ALGO, $secureString);
    }

    /**
     * @param array $array
     * @param array $arrayTpl
     *
     * @return string
     */
    protected function makeSecureString(array $array, array $arrayTpl)
    {
        $array = $this->prepareDataArray($array, $arrayTpl);

        return $this->convertToString($array);
    }

    /**
     * @param array $array
     *
     * @return string
     */
    protected function convertToString(array $array)
    {
        $array = array_map([$this, 'convertTypes'], $array);

        $string = implode(self::DELIMITER, $array);
        // Remove line breaks, they are not allowed.
        $string = str_replace(PHP_EOL, ' ', $string);

        return $string;
    }

    /**
     * @param array $array
     * @param array $arrayTpl
     *
     * @return array
     */
    protected function prepareDataArray(array $array, array $arrayTpl)
    {
        // Remove elements which are not present in the array template.
        $array = array_intersect_key($array, array_flip($arrayTpl));

        $this->sortWithTemplate($array, $arrayTpl);

        return array_values($array);
    }

    /**
     * @param array $array
     * @param array $arrayTpl
     */
    protected function sortWithTemplate(array &$array, array $arrayTpl)
    {
        uksort($array, $this->getSortCallback($array, $arrayTpl));
    }

    /**
     * @param array $array
     * @param array $arrayTpl
     *
     * @return \Closure
     */
    protected function getSortCallback(array $array, array $arrayTpl)
    {
        $arrayKeys = array_keys($array);
        return function ($key1, $key2) use ($arrayKeys, $arrayTpl) {
            $index1 = array_search($key1, $arrayTpl, true);
            $index2 = array_search($key2, $arrayTpl, true);

            if ($index1 === $index2) {
                return 0;
            }

            return $index1 > $index2 ? 1 : -1;
        };
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
