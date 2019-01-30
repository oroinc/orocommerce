<?php

namespace Oro\Bundle\TaxBundle\Api;

use Oro\Bundle\ApiBundle\Processor\CustomizeLoadedData\CustomizeLoadedDataContext;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\TaxBundle\Entity\TaxValue;
use Oro\Bundle\TaxBundle\Model\Result;
use Oro\Bundle\TaxBundle\Model\ResultElement;
use Oro\Bundle\TaxBundle\Provider\TaxationSettingsProvider;

/**
 * Provides taxes for order line items and cache it in API context.
 */
class OrderTaxesProvider
{
    public const TOTAL_INCLUDING_TAX = 'totalIncludingTax';
    public const TOTAL_EXCLUDING_TAX = 'totalExcludingTax';
    public const TOTAL_TAX_AMOUNT    = 'totalTaxAmount';

    private const TAXES_CONTEXT_KEY = '_taxes';

    /** @var DoctrineHelper */
    private $doctrineHelper;

    /** @var TaxationSettingsProvider */
    private $taxationSettingsProvider;

    /**
     * @param DoctrineHelper           $doctrineHelper
     * @param TaxationSettingsProvider $taxationSettingsProvider
     */
    public function __construct(DoctrineHelper $doctrineHelper, TaxationSettingsProvider $taxationSettingsProvider)
    {
        $this->doctrineHelper = $doctrineHelper;
        $this->taxationSettingsProvider = $taxationSettingsProvider;
    }

    /**
     * @param CustomizeLoadedDataContext $context
     * @param int[]                      $orderIds
     *
     * @return array [line item id => taxes, ...]
     */
    public function getTaxes(CustomizeLoadedDataContext $context, array $orderIds): array
    {
        if (!$context->has(self::TAXES_CONTEXT_KEY)) {
            $context->set(self::TAXES_CONTEXT_KEY, $this->buildTaxes($orderIds));
        }

        return $context->get(self::TAXES_CONTEXT_KEY);
    }

    /**
     * @param int[] $orderIds
     *
     * @return array
     */
    private function buildTaxes(array $orderIds): array
    {
        $result = [];
        if (!empty($orderIds) && $this->taxationSettingsProvider->isEnabled()) {
            $allTaxes = $this->loadTaxes($orderIds);
            foreach ($orderIds as $orderId) {
                $taxes = [];
                if (array_key_exists($orderId, $allTaxes)) {
                    $taxes = $allTaxes[$orderId];
                }

                $result[$orderId] = [
                    self::TOTAL_INCLUDING_TAX => $this->getTaxValue(
                        $taxes,
                        Result::TOTAL,
                        ResultElement::INCLUDING_TAX
                    ),
                    self::TOTAL_EXCLUDING_TAX => $this->getTaxValue(
                        $taxes,
                        Result::TOTAL,
                        ResultElement::EXCLUDING_TAX
                    ),
                    self::TOTAL_TAX_AMOUNT    => $this->getTaxValue(
                        $taxes,
                        Result::TOTAL,
                        ResultElement::TAX_AMOUNT
                    )
                ];
            }
        }

        return $result;
    }

    /**
     * @param array  $taxes
     * @param string $valueType
     * @param string $valueName
     *
     * @return mixed
     */
    private function getTaxValue(array $taxes, string $valueType, string $valueName)
    {
        $result = null;
        if (!empty($taxes[$valueType]) && array_key_exists($valueName, $taxes[$valueType])) {
            $result = $taxes[$valueType][$valueName];
        }

        return $result;
    }

    /**
     * @param array $orderIds
     *
     * @return array [orderId => taxes, ...]
     */
    private function loadTaxes(array $orderIds): array
    {
        $qb = $this->doctrineHelper->getEntityManagerForClass(TaxValue::class)
            ->createQueryBuilder()
            ->from(TaxValue::class, 'taxValue')
            ->select('taxValue.entityId, taxValue.result AS taxes')
            ->where('taxValue.entityClass = :entityClass AND taxValue.entityId IN (:entityIds)')
            ->setParameter('entityClass', Order::class)
            ->setParameter('entityIds', $orderIds);

        $result = [];
        $rows = $qb->getQuery()->getArrayResult();
        foreach ($rows as $row) {
            $result[$row['entityId']] = $row['taxes'];
        }

        return $result;
    }
}
