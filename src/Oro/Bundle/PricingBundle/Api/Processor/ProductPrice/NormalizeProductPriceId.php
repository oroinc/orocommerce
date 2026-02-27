<?php

namespace Oro\Bundle\PricingBundle\Api\Processor\ProductPrice;

use Oro\Bundle\ApiBundle\Processor\SingleItemContext;
use Oro\Bundle\ApiBundle\Request\DataType;
use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Bundle\ApiBundle\Request\ValueNormalizer;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Extracts the product price and price list IDs from the 'GUID-priceListId' format
 * and sets them to the context.
 */
class NormalizeProductPriceId implements ProcessorInterface
{
    private ValueNormalizer $valueNormalizer;

    public function __construct(ValueNormalizer $valueNormalizer)
    {
        $this->valueNormalizer = $valueNormalizer;
    }

    #[\Override]
    public function process(ContextInterface $context): void
    {
        /** @var SingleItemContext $context */

        $id = $context->getId();
        if (!\is_string($id)) {
            // an entity identifier does not exist
            return;
        }
        if (PriceListIdContextUtil::hasPriceListId($context) && PriceListIdContextUtil::isProductPriceId($id)) {
            // an entity identifier is already normalized
            return;
        }

        [$productPriceId, $priceListId] = PriceListIdContextUtil::parseProductPriceId($id);
        if (null !== $priceListId) {
            $requestType = $context->getRequestType();
            $productPriceId = $this->normalizeProductPriceId($productPriceId, $requestType);
            $priceListId = $this->normalizePriceListId($priceListId, $requestType);
        }
        if (null === $productPriceId || null === $priceListId) {
            throw new NotFoundHttpException('An entity with the requested identifier does not exist.');
        }

        $context->setId($productPriceId);
        PriceListIdContextUtil::storePriceListId($context, $priceListId);
    }

    private function normalizeProductPriceId(string $value, RequestType $requestType): ?string
    {
        try {
            return $this->valueNormalizer->normalizeValue($value, DataType::GUID, $requestType);
        } catch (\UnexpectedValueException $e) {
            return null;
        }
    }

    private function normalizePriceListId(string $value, RequestType $requestType): ?int
    {
        try {
            return $this->valueNormalizer->normalizeValue($value, DataType::INTEGER, $requestType);
        } catch (\UnexpectedValueException $e) {
            return null;
        }
    }
}
