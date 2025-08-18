<?php

namespace Oro\Bundle\CheckoutBundle\Api\Processor;

use Doctrine\ORM\Query;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionFieldConfig;
use Oro\Bundle\ApiBundle\Processor\CustomizeLoadedData\CustomizeLoadedDataContext;
use Oro\Bundle\ApiBundle\Request\ValueTransformer;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Provider\CheckoutTotalsProvider;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Computes values of "totalValue" and "totals" fields for Checkout entity.
 */
class ComputeCheckoutTotals implements ProcessorInterface
{
    public function __construct(
        private readonly CheckoutTotalsProvider $checkoutTotalsProvider,
        private readonly DoctrineHelper $doctrineHelper,
        private readonly ValueTransformer $valueTransformer
    ) {
    }

    #[\Override]
    public function process(ContextInterface $context): void
    {
        /** @var CustomizeLoadedDataContext $context */

        $config = $context->getConfig();
        $totalValueFieldName = $context->getResultFieldName('totalValue', $config);
        $totalsFieldName = $context->getResultFieldName('totals', $config);
        $isTotalFieldRequested = $context->isFieldRequested($totalValueFieldName);
        $isTotalsFieldRequested = $context->isFieldRequested($totalsFieldName);
        if (!$isTotalFieldRequested && !$isTotalsFieldRequested) {
            return;
        }

        $data = $context->getData();
        $dataMap = $this->getDataMap($data, $context->getResultFieldName('id', $config));
        $allTotals = $this->loadAllCheckoutTotals(array_keys($dataMap));
        $normalizationContext = $context->getNormalizationContext();
        foreach ($allTotals as $checkoutId => $totalsData) {
            $dataKey = $dataMap[$checkoutId] ?? null;
            if (null === $dataKey) {
                continue;
            }
            if ($isTotalFieldRequested) {
                $data[$dataKey][$totalValueFieldName] = $this->normalizeTotalValue(
                    $totalsData['total']['amount'] ?? null,
                    $config->getField($totalValueFieldName),
                    $normalizationContext
                );
            }
            if ($isTotalsFieldRequested) {
                $data[$dataKey][$totalsFieldName] = $this->normalizeTotalsValue(
                    $totalsData['subtotals'] ?? [],
                    $config->getField($totalsFieldName)->getTargetEntity(),
                    $normalizationContext
                );
            }
        }
        $context->setData($data);
    }

    private function getDataMap(array $data, string $idFieldName): array
    {
        $dataMap = [];
        foreach ($data as $key => $item) {
            $dataMap[$item[$idFieldName]] = $key;
        }

        return $dataMap;
    }

    private function loadAllCheckoutTotals(array $checkoutIds): array
    {
        /** @var Checkout[] $checkouts */
        $checkouts = $this->doctrineHelper->createQueryBuilder(Checkout::class, 'c')
            ->select('c, li, p, kli, klik, klip')
            ->leftJoin('c.lineItems', 'li')
            ->leftJoin('li.product', 'p')
            ->leftJoin('li.kitItemLineItems', 'kli')
            ->leftJoin('kli.kitItem', 'klik')
            ->leftJoin('kli.product', 'klip')
            ->where('c.id IN (:ids)')
            ->setParameter('ids', $checkoutIds)
            ->getQuery()
            ->setHint(Query::HINT_REFRESH, true)
            ->getResult();

        $allTotals = [];
        foreach ($checkouts as $checkout) {
            $allTotals[$checkout->getId()] = $this->checkoutTotalsProvider->getTotalsArray($checkout);
        }

        return $allTotals;
    }

    private function normalizeTotalValue(mixed $value, EntityDefinitionFieldConfig $config, array $context): mixed
    {
        $totalValue = $this->normalizeValue($value, $config, $context);
        if (null !== $totalValue && $this->normalizeValue(0.0, $config, $context) === $totalValue) {
            $totalValue = null;
        }

        return $totalValue;
    }

    private function normalizeTotalsValue(
        array $totals,
        EntityDefinitionConfig $totalsConfig,
        array $context
    ): mixed {
        $totalsFieldValue = [];
        foreach ($totals as $total) {
            if (!$total['visible']) {
                continue;
            }
            $totalFieldValue = [];
            $totalsConfigFields = $totalsConfig->getFields();
            foreach ($totalsConfigFields as $fieldName => $fieldConfig) {
                $totalFieldValue[$fieldName] = $this->normalizeValue(
                    $total[$fieldConfig->getPropertyPath($fieldName)],
                    $fieldConfig,
                    $context
                );
            }
            $totalsFieldValue[] = $totalFieldValue;
        }

        return $totalsFieldValue;
    }

    private function normalizeValue(mixed $value, EntityDefinitionFieldConfig $config, array $context): mixed
    {
        return $this->valueTransformer->transformFieldValue($value, $config->toArray(true), $context);
    }
}
