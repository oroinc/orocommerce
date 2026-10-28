<?php

declare(strict_types=1);

namespace Oro\Bundle\RFPBundle\Form\DataTransformer;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\RFPBundle\Provider\ProductRFPAvailabilityProvider;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;

/**
 * Transforms a product kit to its id and back.
 * Fails the reverse transformation if the product is not a kit allowed for RFP on the storefront.
 */
final class FrontendProductKitToIdTransformer implements DataTransformerInterface
{
    public function __construct(
        private readonly DataTransformerInterface $productToIdTransformer,
        private readonly ProductRFPAvailabilityProvider $productRFPAvailabilityProvider
    ) {
    }

    #[\Override]
    public function transform(mixed $value): mixed
    {
        return $this->productToIdTransformer->transform($value);
    }

    #[\Override]
    public function reverseTransform(mixed $value): ?Product
    {
        if (!$value) {
            return null;
        }

        $productId = filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if (
            false === $productId
            || !\in_array($productId, $this->productRFPAvailabilityProvider->getAllowedProductIds([$productId]), true)
        ) {
            throw new TransformationFailedException('The product is not allowed for RFP.');
        }

        $product = $this->productToIdTransformer->reverseTransform($productId);
        if (!$product instanceof Product || !$product->isKit()) {
            throw new TransformationFailedException('The product is not a product kit.');
        }

        return $product;
    }
}
