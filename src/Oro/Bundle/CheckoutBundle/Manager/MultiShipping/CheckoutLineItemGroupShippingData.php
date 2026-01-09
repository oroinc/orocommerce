<?php

namespace Oro\Bundle\CheckoutBundle\Manager\MultiShipping;

use Oro\Bundle\ShippingBundle\Provider\GroupLineItemHelper;

/**
 * Holds checkout shipping data when each group of line items has own shipping method.
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
final class CheckoutLineItemGroupShippingData
{
    private const SHIPPING_METHODS = 'shippingMethods';
    private const SHIPPING_AMOUNT = 'shippingAmount';
    private const METHOD = 'method';
    private const TYPE = 'type';
    private const AMOUNT = 'amount';

    private array $data = [];

    /**
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function __construct(array $shippingData = [])
    {
        if ($shippingData) {
            $shippingDataDataGroupingFieldPath = null;
            foreach ($shippingData as $lineItemGroupKey => $item) {
                if (!\is_string($lineItemGroupKey)) {
                    throw new \InvalidArgumentException('The line item group key must be a string.');
                }
                if (!\is_array($item)) {
                    throw new \InvalidArgumentException(sprintf(
                        'The data for "%s" must be an array.',
                        $lineItemGroupKey
                    ));
                }
                self::validateSerializedItem($lineItemGroupKey, $item);
                if (GroupLineItemHelper::OTHER_ITEMS_KEY !== $lineItemGroupKey) {
                    $groupingFieldPath = self::getGroupingFieldPath($lineItemGroupKey);
                    if (!$shippingDataDataGroupingFieldPath) {
                        $shippingDataDataGroupingFieldPath = $groupingFieldPath;
                    } elseif ($groupingFieldPath !== $shippingDataDataGroupingFieldPath) {
                        throw new \InvalidArgumentException(sprintf(
                            'All line items should be grouped by the same field path,'
                            . ' but detected two different field paths "%s" and "%s".',
                            $shippingDataDataGroupingFieldPath,
                            $groupingFieldPath
                        ));
                    }
                }
                if (\array_key_exists(self::METHOD, $item)) {
                    $this->data[self::SHIPPING_METHODS][$lineItemGroupKey] = [
                        self::METHOD => $item[self::METHOD],
                        self::TYPE   => $item[self::TYPE]
                    ];
                }
                if (\array_key_exists(self::AMOUNT, $item)) {
                    $this->data[self::SHIPPING_AMOUNT][$lineItemGroupKey] = (float)$item[self::AMOUNT];
                }
            }
        }
    }

    public function toArray(): array
    {
        $result = [];
        if (!empty($this->data[self::SHIPPING_METHODS])) {
            foreach ($this->data[self::SHIPPING_METHODS] as $lineItemGroupKey => $shippingData) {
                $result[$lineItemGroupKey] = $shippingData;
            }
        }
        if (!empty($this->data[self::SHIPPING_AMOUNT])) {
            foreach ($this->data[self::SHIPPING_AMOUNT] as $lineItemGroupKey => $amount) {
                $result[$lineItemGroupKey][self::AMOUNT] = $amount;
            }
        }

        return $result;
    }

    /**
     * @return array ['product.category:1' => ['method' => 'flat_rate_1', 'type' => 'primary'], ... ]
     */
    public function getShippingMethods(): array
    {
        return $this->data[self::SHIPPING_METHODS] ?? [];
    }

    public function setShippingMethod(string $lineItemGroupKey, string $shippingMethod, string $shippingType): void
    {
        if (!$lineItemGroupKey) {
            throw new \InvalidArgumentException('The line item group key must be not an empty string.');
        }
        if (!$shippingMethod) {
            throw new \InvalidArgumentException('The shipping method must be not an empty string.');
        }
        if (!$shippingType) {
            throw new \InvalidArgumentException('The shipping type must be not an empty string.');
        }

        if (GroupLineItemHelper::OTHER_ITEMS_KEY !== $lineItemGroupKey) {
            $this->clearOutdatedData(self::getGroupingFieldPath($lineItemGroupKey));
        }

        $this->data[self::SHIPPING_METHODS][$lineItemGroupKey] = [
            self::METHOD => $shippingMethod,
            self::TYPE   => $shippingType
        ];

        $this->removeShippingEstimateAmount($lineItemGroupKey);
    }

    public function removeShippingMethod(string $lineItemGroupKey): void
    {
        if (!$lineItemGroupKey) {
            throw new \InvalidArgumentException('The line item group key must be not an empty string.');
        }

        if (!empty($this->data[self::SHIPPING_METHODS])) {
            unset($this->data[self::SHIPPING_METHODS][$lineItemGroupKey]);
            if (empty($this->data[self::SHIPPING_METHODS])) {
                unset($this->data[self::SHIPPING_METHODS]);
            }
        }

        $this->removeShippingEstimateAmount($lineItemGroupKey);
    }

    public function removeAllShippingMethods(): void
    {
        unset($this->data[self::SHIPPING_METHODS]);
        $this->removeAllShippingEstimateAmounts();
    }

    /**
     * @return array ['product.category:1' => amount, ... ]
     */
    public function getShippingEstimateAmounts(): array
    {
        return $this->data[self::SHIPPING_AMOUNT] ?? [];
    }

    public function setShippingEstimateAmount(string $lineItemGroupKey, float $shippingEstimateAmount): void
    {
        if (!$lineItemGroupKey) {
            throw new \InvalidArgumentException('The line item group key must be not an empty string.');
        }

        if (GroupLineItemHelper::OTHER_ITEMS_KEY !== $lineItemGroupKey) {
            $this->clearOutdatedData(self::getGroupingFieldPath($lineItemGroupKey));
        }

        $this->data[self::SHIPPING_AMOUNT][$lineItemGroupKey] = $shippingEstimateAmount;
    }

    public function removeShippingEstimateAmount(string $lineItemGroupKey): void
    {
        if (!$lineItemGroupKey) {
            throw new \InvalidArgumentException('The line item group key must be not an empty string.');
        }

        if (!empty($this->data[self::SHIPPING_AMOUNT])) {
            unset($this->data[self::SHIPPING_AMOUNT][$lineItemGroupKey]);
            if (empty($this->data[self::SHIPPING_AMOUNT])) {
                unset($this->data[self::SHIPPING_AMOUNT]);
            }
        }
    }

    public function removeAllShippingEstimateAmounts(): void
    {
        unset($this->data[self::SHIPPING_AMOUNT]);
    }

    public function clear(): void
    {
        $this->data = [];
    }

    public function isEmpty(): bool
    {
        return !$this->data;
    }

    private function clearOutdatedData(string $groupingFieldPath): void
    {
        $prefix = $groupingFieldPath . GroupLineItemHelper::GROUPING_DELIMITER;
        if (!empty($this->data[self::SHIPPING_METHODS])) {
            $lineItemGroupKeys = array_keys($this->data[self::SHIPPING_METHODS]);
            foreach ($lineItemGroupKeys as $lineItemGroupKey) {
                if (
                    GroupLineItemHelper::OTHER_ITEMS_KEY !== $lineItemGroupKey
                    && !str_starts_with($lineItemGroupKey, $prefix)
                ) {
                    unset($this->data[self::SHIPPING_METHODS][$lineItemGroupKey]);
                }
            }
        }
        if (!empty($this->data[self::SHIPPING_AMOUNT])) {
            $lineItemGroupKeys = array_keys($this->data[self::SHIPPING_AMOUNT]);
            foreach ($lineItemGroupKeys as $lineItemGroupKey) {
                if (
                    GroupLineItemHelper::OTHER_ITEMS_KEY !== $lineItemGroupKey
                    && !str_starts_with($lineItemGroupKey, $prefix)
                ) {
                    unset($this->data[self::SHIPPING_AMOUNT][$lineItemGroupKey]);
                }
            }
        }
    }

    private static function getGroupingFieldPath(string $lineItemGroupKey): string
    {
        $pos = strrpos($lineItemGroupKey, GroupLineItemHelper::GROUPING_DELIMITER);
        if (false === $pos) {
            throw new \InvalidArgumentException(sprintf(
                'The line item group key must be "field_path%sfield_value" or "%s". Given "%s".',
                GroupLineItemHelper::GROUPING_DELIMITER,
                GroupLineItemHelper::OTHER_ITEMS_KEY,
                $lineItemGroupKey
            ));
        }

        return substr($lineItemGroupKey, 0, $pos);
    }

    /**
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    private static function validateSerializedItem(string $lineItemGroupKey, array $item): void
    {
        if (!$lineItemGroupKey) {
            throw new \InvalidArgumentException('The line item group key must be not an empty string.');
        }
        if (\array_key_exists(self::METHOD, $item)) {
            if (!\array_key_exists(self::TYPE, $item)) {
                throw new \InvalidArgumentException(sprintf(
                    'The shipping type for "%s" must be specified.',
                    $lineItemGroupKey
                ));
            }
            $shippingMethod = $item[self::METHOD];
            if (!\is_string($shippingMethod) || !$shippingMethod) {
                throw new \InvalidArgumentException(sprintf(
                    'The shipping method for "%s" must be not an empty string.',
                    $lineItemGroupKey
                ));
            }
            $shippingType = $item[self::TYPE];
            if (!\is_string($shippingType) || !$shippingType) {
                throw new \InvalidArgumentException(sprintf(
                    'The shipping type for "%s" must be not an empty string.',
                    $lineItemGroupKey
                ));
            }
        } elseif (\array_key_exists(self::TYPE, $item)) {
            throw new \InvalidArgumentException(sprintf(
                'The shipping type for "%s" can be specified only together with the shipping method.',
                $lineItemGroupKey
            ));
        }
        if (\array_key_exists(self::AMOUNT, $item)) {
            $shippingEstimateAmount = $item[self::AMOUNT];
            if (!\is_float($shippingEstimateAmount) && !\is_int($shippingEstimateAmount)) {
                throw new \InvalidArgumentException(sprintf(
                    'The shipping estimate amount for "%s" must be a number.',
                    $lineItemGroupKey
                ));
            }
        }
    }
}
