<?php

namespace Oro\Bundle\TaxBundle\Api;

use Oro\Bundle\ApiBundle\Processor\CustomizeLoadedData\CustomizeLoadedDataContext;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\TaxBundle\Entity\TaxValue;
use Oro\Bundle\TaxBundle\Model\Result;
use Oro\Bundle\TaxBundle\Model\ResultElement;
use Oro\Bundle\TaxBundle\Provider\TaxationSettingsProvider;

/**
 * Provides taxes for order line items and cache it in API context.
 */
class OrderLineItemTaxesProvider
{
    public const UNIT_PRICE_INCLUDING_TAX = 'unitPriceIncludingTax';
    public const UNIT_PRICE_EXCLUDING_TAX = 'unitPriceExcludingTax';
    public const UNIT_PRICE_TAX_AMOUNT    = 'unitPriceTaxAmount';
    public const ROW_TOTAL_INCLUDING_TAX  = 'rowTotalIncludingTax';
    public const ROW_TOTAL_EXCLUDING_TAX  = 'rowTotalExcludingTax';
    public const ROW_TOTAL_TAX_AMOUNT     = 'rowTotalTaxAmount';
    public const TAXES                    = 'taxes';

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
     * @param int[]                      $lineItemIds
     *
     * @return array [line item id => taxes, ...]
     */
    public function getTaxes(CustomizeLoadedDataContext $context, array $lineItemIds): array
    {
        if (!$context->has(self::TAXES_CONTEXT_KEY)) {
            $context->set(self::TAXES_CONTEXT_KEY, $this->buildTaxes($lineItemIds));
        }

        return $context->get(self::TAXES_CONTEXT_KEY);
    }

    /**
     * @param int[] $lineItemIds
     *
     * @return array
     */
    private function buildTaxes(array $lineItemIds): array
    {
        $result = [];
        if (!empty($lineItemIds) && $this->taxationSettingsProvider->isEnabled()) {
            $allTaxes = $this->loadTaxes($lineItemIds);
            foreach ($lineItemIds as $lineItemId) {
                $taxes = [];
                if (array_key_exists($lineItemId, $allTaxes)) {
                    $taxes = $allTaxes[$lineItemId];
                }

                $result[$lineItemId] = [
                    self::TAXES                    => $this->getAppliedTaxes($taxes),
                    self::UNIT_PRICE_INCLUDING_TAX => $this->getTaxValue(
                        $taxes,
                        Result::UNIT,
                        ResultElement::INCLUDING_TAX
                    ),
                    self::UNIT_PRICE_EXCLUDING_TAX => $this->getTaxValue(
                        $taxes,
                        Result::UNIT,
                        ResultElement::EXCLUDING_TAX
                    ),
                    self::UNIT_PRICE_TAX_AMOUNT    => $this->getTaxValue(
                        $taxes,
                        Result::UNIT,
                        ResultElement::TAX_AMOUNT
                    ),
                    self::ROW_TOTAL_INCLUDING_TAX  => $this->getTaxValue(
                        $taxes,
                        Result::ROW,
                        ResultElement::INCLUDING_TAX
                    ),
                    self::ROW_TOTAL_EXCLUDING_TAX  => $this->getTaxValue(
                        $taxes,
                        Result::ROW,
                        ResultElement::EXCLUDING_TAX
                    ),
                    self::ROW_TOTAL_TAX_AMOUNT     => $this->getTaxValue(
                        $taxes,
                        Result::ROW,
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
     * @param array $taxes
     *
     * @return mixed
     */
    private function getAppliedTaxes(array $taxes)
    {
        $result = [];
        if (!empty($taxes['taxes'])) {
            $result = $taxes['taxes'];
        }

        return $result;
    }

    /**
     * @param array $lineItemIds
     *
     * @return array [lineItemId => taxes, ...]
     */
    private function loadTaxes(array $lineItemIds): array
    {
        $qb = $this->doctrineHelper->getEntityManagerForClass(TaxValue::class)
            ->createQueryBuilder()
            ->from(TaxValue::class, 'taxValue')
            ->select('taxValue.entityId, taxValue.result AS taxes')
            ->where('taxValue.entityClass = :entityClass AND taxValue.entityId IN (:entityIds)')
            ->setParameter('entityClass', OrderLineItem::class)
            ->setParameter('entityIds', $lineItemIds);

        $result = [];
        $rows = $qb->getQuery()->getArrayResult();
        foreach ($rows as $row) {
            $result[$row['entityId']] = $row['taxes'];
        }

        return $result;
    }
}
