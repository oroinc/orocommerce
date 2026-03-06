<?php

namespace Oro\Bundle\PricingBundle\Api\Processor\ProductPrice;

use Oro\Bundle\ApiBundle\Filter\FilterCollection;
use Oro\Bundle\ApiBundle\Filter\FilterValue;
use Oro\Bundle\ApiBundle\Filter\StandaloneFilter;
use Oro\Bundle\ApiBundle\Model\Error;
use Oro\Bundle\ApiBundle\Model\ErrorSource;
use Oro\Bundle\ApiBundle\Processor\Context;
use Oro\Bundle\ApiBundle\Request\Constraint;
use Oro\Bundle\ApiBundle\Request\DataType;
use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Bundle\ApiBundle\Request\ValueNormalizer;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Normalizes a value of the product price ID filter.
 */
class NormalizeFilterValueForProductPriceId implements ProcessorInterface
{
    private ValueNormalizer $valueNormalizer;

    public function __construct(ValueNormalizer $valueNormalizer)
    {
        $this->valueNormalizer = $valueNormalizer;
    }

    #[\Override]
    public function process(ContextInterface $context): void
    {
        /** @var Context $context */

        if ($context->hasQuery()) {
            // a query is already built
            return;
        }

        $idFieldNames = $context->getMetadata()->getIdentifierFieldNames();
        if (\count($idFieldNames) !== 1) {
            return;
        }

        $filters = $context->getFilters();
        $idFilterKey = $this->getIdFilterKey($filters, $idFieldNames[0]);
        $idFilter = $filters->get($idFilterKey);
        if (!$idFilter instanceof StandaloneFilter) {
            return;
        }

        $filterValues = $context->getFilterValues();
        $priceListId = (int)$filterValues->get($this->getIdFilterKey($filters, 'priceList'))->getValue();

        $idFilterValues = $filterValues->getMultiple($idFilterKey);
        foreach ($idFilterValues as $idFilterValue) {
            $this->normalizeIdFilterValue($idFilterKey, $idFilterValue, $priceListId, $context);
        }
    }

    private function getIdFilterKey(FilterCollection $filters, string $idFieldName): string
    {
        $filterGroup = $filters->getDefaultGroupName();

        return $filterGroup
            ? $filters->getGroupedFilterKey($filterGroup, $idFieldName)
            : $idFieldName;
    }

    private function normalizeIdFilterValue(
        string $idFilterKey,
        FilterValue $idFilterValue,
        int $priceListId,
        Context $context
    ): void {
        $requestType = $context->getRequestType();
        $value = $idFilterValue->getValue();
        if (\is_string($value)) {
            try {
                $idFilterValue->setValue($this->normalizeProductPriceId($value, $priceListId, $requestType));
            } catch (\UnexpectedValueException $e) {
                $context->addError($this->createInvalidIdFilterValueError($idFilterKey, $value));
            }
        } elseif (\is_array($value)) {
            $normalizedValue = [];
            foreach ($value as $key => $val) {
                try {
                    $normalizedValue[$key] = $this->normalizeProductPriceId($val, $priceListId, $requestType);
                } catch (\UnexpectedValueException $e) {
                    $normalizedValue = [];
                    $context->addError($this->createInvalidIdFilterValueError($idFilterKey, $val));
                    break;
                }
            }
            if ($normalizedValue) {
                $idFilterValue->setValue($normalizedValue);
            }
        }
    }

    /**
     * @throws \UnexpectedValueException if the value is invalid
     */
    private function normalizeProductPriceId(string $value, int $priceListId, RequestType $requestType): string
    {
        [$productPriceId, $plId] = PriceListIdContextUtil::parseProductPriceId($value);
        if (null === $plId || $this->normalizePriceListId($plId) !== $priceListId) {
            throw new \UnexpectedValueException('Invalid product price identifier.');
        }

        return $this->valueNormalizer->normalizeValue($productPriceId, DataType::GUID, $requestType);
    }

    private function normalizePriceListId(string $value): ?int
    {
        if (null === filter_var($value, FILTER_VALIDATE_INT, FILTER_NULL_ON_FAILURE)) {
            return null;
        }

        return (int)$value;
    }

    private function createInvalidIdFilterValueError(string $filterKey, string $value): Error
    {
        return Error::createValidationError(
            Constraint::FILTER,
            \sprintf('Expected a product price ID. Given "%s".', $value)
        )->setSource(ErrorSource::createByParameter($filterKey));
    }
}
