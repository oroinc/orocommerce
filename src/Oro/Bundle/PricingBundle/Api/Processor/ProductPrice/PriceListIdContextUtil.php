<?php

namespace Oro\Bundle\PricingBundle\Api\Processor\ProductPrice;

use Oro\Bundle\ApiBundle\Processor\SharedDataAwareContextInterface;

/**
 * Provides a set of static methods to work with a price list ID stored in the context.
 */
class PriceListIdContextUtil
{
    private const PRICE_LIST_ID_ATTRIBUTE = 'price_list_id';
    private const PRICE_LIST_ID_MAP = 'price_list_id_map';
    private const PAYLOAD = 'payload';

    /**
     * Stores the given price list ID to the context.
     */
    public static function storePriceListId(SharedDataAwareContextInterface $context, int $priceListId): void
    {
        $context->getSharedData()->set(self::PRICE_LIST_ID_ATTRIBUTE, $priceListId);
    }

    /**
     * Retrieves a price list ID from the context.
     */
    public static function getPriceListId(SharedDataAwareContextInterface $context): int
    {
        $priceListId = $context->getSharedData()->get(self::PRICE_LIST_ID_ATTRIBUTE);
        if (null === $priceListId) {
            throw new \LogicException('A price list ID has not been set in the context.');
        }

        return $priceListId;
    }

    /**
     * Checks whether a price list ID exists in the context.
     */
    public static function hasPriceListId(SharedDataAwareContextInterface $context): bool
    {
        return $context->getSharedData()->has(self::PRICE_LIST_ID_ATTRIBUTE);
    }

    /**
     * Adds price list ID and product price ID to a price list ID map stored in the context.
     */
    public static function addToPriceListIdMap(
        SharedDataAwareContextInterface $context,
        string $productPriceId,
        int $priceListId
    ): void {
        $payload = $context->getSharedData()->get(self::PAYLOAD) ?? [];
        $payload[self::PRICE_LIST_ID_MAP]['@' . $priceListId][] = $productPriceId;
        $context->getSharedData()->set(self::PAYLOAD, $payload);
    }

    /**
     * Gets a price list ID map from the context.
     *
     * @return array<int, array<string>>|null [price list id => [product price id, ...], ...]
     */
    public static function getPriceListIdMap(SharedDataAwareContextInterface $context): ?array
    {
        $payload = $context->getSharedData()->get(self::PAYLOAD);
        if (!$payload) {
            return null;
        }

        $priceListIdMap = $payload[self::PRICE_LIST_ID_MAP] ?? null;
        if (!$priceListIdMap) {
            return null;
        }

        $normalizedPriceListIdMap = [];
        foreach ($priceListIdMap as $priceListId => $productPriceIds) {
            $normalizedPriceListIdMap[(int)substr($priceListId, 1)] = $productPriceIds;
        }

        return $normalizedPriceListIdMap;
    }

    /**
     * Adds a price list ID stored in the context as a suffix to the given product price ID.
     */
    public static function normalizeProductPriceId(
        SharedDataAwareContextInterface $context,
        string $productPriceId
    ): string {
        $priceListId = null;
        if (self::hasPriceListId($context)) {
            $priceListId = self::getPriceListId($context);
        } else {
            $priceListIdMap = self::getPriceListIdMap($context);
            if ($priceListIdMap) {
                if (\count($priceListIdMap) === 1) {
                    $priceListId = array_key_first($priceListIdMap);
                } else {
                    foreach ($priceListIdMap as $plId => $productPriceIds) {
                        if (\in_array($productPriceId, $productPriceIds, true)) {
                            $priceListId = $plId;
                            break;
                        }
                    }
                }
            }
        }
        if (null === $priceListId) {
            throw new \LogicException(\sprintf(
                'Cannot resolve a price list ID for the price list "%s".',
                $productPriceId
            ));
        }

        return \sprintf('%s-%d', $productPriceId, $priceListId);
    }

    /**
     * Extracts a product price ID and a price list ID from the given ID.
     *
     * @return array<string, string|null> [product price ID, price list ID or null]
     */
    public static function parseProductPriceId(string $productPriceId): array
    {
        if (self::isProductPriceId($productPriceId)) {
            // the specified product price ID does not contain price list ID
            return [$productPriceId, null];
        }

        $lastDelimiterPos = strrpos($productPriceId, '-');
        if (false === $lastDelimiterPos) {
            // the specified product price ID cannot be parsed due to unknown format of the given value
            return [$productPriceId, null];
        }

        return [substr($productPriceId, 0, $lastDelimiterPos), substr($productPriceId, $lastDelimiterPos + 1)];
    }

    /**
     * Checks whether the given value represents a product price ID.
     * The product price ID is a GUID value.
     */
    public static function isProductPriceId(string $value): bool
    {
        return substr_count($value, '-') === 4;
    }
}
