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

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context): void
    {
        /** @var SingleItemContext $context */

        $id = $context->getId();
        if (!\is_string($id) || (PriceListIdContextUtil::hasPriceListId($context) && substr_count($id, '-') === 4)) {
            // an entity identifier does not exist or it is already normalized
            return;
        }

        $productPriceId = null;
        $priceListId = null;
        $lastDelimiterPos = strrpos($id, '-');
        if (false !== $lastDelimiterPos) {
            $requestType = $context->getRequestType();
            $productPriceId = $this->normalizeProductPriceId(substr($id, 0, $lastDelimiterPos), $requestType);
            $priceListId = $this->normalizePriceListId(substr($id, $lastDelimiterPos + 1), $requestType);
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
