<?php

namespace Oro\Bundle\ProductBundle\ImportExport\EventListener;

use Oro\Bundle\LocaleBundle\ImportExport\Normalizer\LocalizationCodeFormatter;
use Oro\Bundle\ProductBundle\ImportExport\Event\ProductDataConverterEvent;

/**
 * Listener convert KitItems data in a special data format during product import.
 */
class KitItemsProductDataConverterEventListener
{
    public const KIT_ITEMS_EXTRA_FIELDS = 'kit_items_extra_fields';
    public const KIT_ITEMS_INVALID_VALUES  = 'kit_items_invalid_values';

    private const ID = 'id';
    private const LABEL = 'label';
    private const OPTIONAL = 'optional';
    private const PRODUCTS = 'products';
    private const MIN_QTY = 'min_qty';
    private const MAX_QTY = 'max_qty';
    private const UNIT = 'unit';

    private const SORT_ORDER = 'sortOrder';

    private const ALLOWED_FIELDS = [
        self::ID,
        self::LABEL,
        self::OPTIONAL,
        self::PRODUCTS,
        self::MIN_QTY,
        self::MAX_QTY,
        self::UNIT
    ];
    private const KIT_ITEMS_PATTERN = '/(\w+)=("(?:[^"\\\\]|\\\\.)*"|[^,]+)(?:,|$)/';

    private static array $mapping = [
        self::LABEL    => 'labels',
        self::MIN_QTY  => 'minimumQuantity',
        self::MAX_QTY  => 'maximumQuantity',
        self::UNIT     => 'productUnit',
        self::PRODUCTS => 'kitItemProducts'
    ];

    public function __construct(
        private string $skuSeparator
    ) {
    }

    /**
     * Examples of converting the value for the kitItems field:
     *      [
     *          0 => [
     *              'sortOrder' => 0,
     *              'id' => 1,
     *              'labels' => [
     *                  0 => ['string' => 'Base Unit']
     *              ],
     *              'optional' => false,
     *              'kitItemProducts' => [
     *                  0 => [
     *                      'product' => ['sku' => 'TJ23']
     *                  ],
     *                  // ...
     *              ],
     *              'minimumQuantity' => 1.0,
     *              'maximumQuantity' => 1.0,
     *              'productUnit' => ['code' => 'set']
     *          ],
     *          // ...
     *      ]
     *
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function onConvertToImport(ProductDataConverterEvent $event): void
    {
        $data = $event->getData();
        if (empty($data['kitItems'])) {
            return;
        }

        $kitItemsExtraFields = $kitItemsInvalidValues = $kitItems = [];
        $items = explode(PHP_EOL, $data['kitItems']);
        foreach ($items as $sortOrder => $item) {
            $item = $this->sanitizeKitItem($item);
            preg_match_all(self::KIT_ITEMS_PATTERN, $item, $matches);

            $kitItem = [
                self::SORT_ORDER => $sortOrder,
                self::$mapping[self::LABEL] => [LocalizationCodeFormatter::DEFAULT_LOCALIZATION => ['string' => '']],
            ];
            for ($i = 0, $iMax = count($matches[1]); $i < $iMax; $i++) {
                $field = mb_strtolower($matches[1][$i]);
                $value = $matches[2][$i];

                if (!$this->isAllowedField($field)) {
                    $kitItemsExtraFields[$sortOrder][] = $field;
                    continue;
                }

                $kitItem[self::$mapping[$field] ?? $field] = match ($field) {
                    self::ID => $this->convertIdValue($value),
                    self::LABEL => $this->convertLabelValue($value),
                    self::OPTIONAL => $this->convertOptionalValue($value),
                    self::PRODUCTS => $this->convertProductsValue($value),
                    self::UNIT => ['code' => $value],
                    self::MIN_QTY, self::MAX_QTY => $this->convertQuantityValue($value),
                    default => $value,
                };
            }

            $this->validateKitItem($kitItem, $sortOrder, $kitItemsInvalidValues);
            $kitItems[] = $kitItem;
        }

        $event->getContext()?->setValue(self::KIT_ITEMS_EXTRA_FIELDS, $kitItemsExtraFields);
        $event->getContext()?->setValue(self::KIT_ITEMS_INVALID_VALUES, $kitItemsInvalidValues);

        $data['kitItems'] = $kitItems;
        $event->setData($data);
    }

    private function isAllowedField(string $field): bool
    {
        return in_array($field, self::ALLOWED_FIELDS, true);
    }

    private function sanitizeKitItem(string $item): string
    {
        return str_replace(['“', '”'], '"', rtrim($item, "\n\r"));
    }

    /**
     * Keep not allowed values of optional, id, min_qty, max_qty parameters
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    private function validateKitItem(array &$kitItem, int $sortOrder, array &$kitItemsInvalidValues): void
    {
        if (isset($kitItem[self::OPTIONAL]) && !is_bool($kitItem[self::OPTIONAL])) {
            $kitItemsInvalidValues[$sortOrder][self::OPTIONAL] = $kitItem[self::OPTIONAL];
            unset($kitItem[self::OPTIONAL]);
        }

        if (isset($kitItem[self::$mapping[self::MIN_QTY]]) && !is_float($kitItem[self::$mapping[self::MIN_QTY]])) {
            $kitItemsInvalidValues[$sortOrder][self::MIN_QTY] = $kitItem[self::$mapping[self::MIN_QTY]];
            unset($kitItem[self::$mapping[self::MIN_QTY]]);
        }

        if (isset($kitItem[self::$mapping[self::MAX_QTY]]) && !is_float($kitItem[self::$mapping[self::MAX_QTY]])) {
            $kitItemsInvalidValues[$sortOrder][self::MAX_QTY] = $kitItem[self::$mapping[self::MAX_QTY]];
            unset($kitItem[self::$mapping[self::MAX_QTY]]);
        }

        if (isset($kitItem[self::ID]) && !is_int($kitItem[self::ID]) && !is_null($kitItem[self::ID])) {
            $kitItemsInvalidValues[$sortOrder][self::ID] = $kitItem[self::ID];
            unset($kitItem[self::ID]);
        }
    }

    private function convertLabelValue(string $value): array
    {
        if (str_starts_with($value, '"') && str_ends_with($value, '"')) {
            $value = substr(stripslashes($value), 1, -1);
        }

        return [LocalizationCodeFormatter::DEFAULT_LOCALIZATION => ['string' => trim($value)]];
    }

    private function convertOptionalValue(string $value): mixed
    {
        return filter_var($value, FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE) ?? $value;
    }

    private function convertQuantityValue(string $value): mixed
    {
        return filter_var($value, FILTER_VALIDATE_FLOAT, FILTER_NULL_ON_FAILURE) ?? $value;
    }

    private function convertIdValue(string $value): mixed
    {
        // Check on empty string
        $value = !empty($value) ? $value : null;
        return filter_var($value, FILTER_VALIDATE_INT, FILTER_NULL_ON_FAILURE) ?? $value;
    }

    private function convertProductsValue(string $value): array
    {
        return array_map(
            static fn (string $sku) => ['product' => ['sku' => $sku]],
            array_unique(explode($this->skuSeparator, $value))
        );
    }
}
