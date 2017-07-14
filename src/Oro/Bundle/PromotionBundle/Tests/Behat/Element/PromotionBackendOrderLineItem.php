<?php

namespace Oro\Bundle\PromotionBundle\Tests\Behat\Element;

use Oro\Bundle\TestFrameworkBundle\Behat\Element\Table;
use Oro\Bundle\TestFrameworkBundle\Behat\Element\TableRow;

class PromotionBackendOrderLineItem extends TableRow implements DiscountAwareLineItemInterface
{
    const APPLIED_DISCOUNTS_CELL_HEADER = 'Applied Discounts';
    const DISCOUNT_AMOUNT_CELL_HEADER = 'Disc. Amount';
    const DISCOUNT_AMOUNT_INCL_TAX_CELL_HEADER = 'After Disc. Incl. Tax';
    const DISCOUNT_AMOUNT_EXCL_TAX_CELL_HEADER = 'After Disc. Excl. Tax';

    /**
     * {@inheritdoc}
     */
    public function getDiscount()
    {
        $cellElement = $this->getCellByHeader(self::APPLIED_DISCOUNTS_CELL_HEADER);

        /** @var Table $discountTable */
        $discountTable = $this->elementFactory->createElement('PromotionBackendLineItemDiscountTable', $cellElement);

        return $discountTable->getRowByNumber(0)->getCellValue(self::DISCOUNT_AMOUNT_CELL_HEADER);
    }

    /**
     * @return array [$rowTotalInclTax, $rowTotalExclTax, $discountAmount]
     */
    public function getDiscountWithRowTotals()
    {
        $cellElement = $this->getCellByHeader(self::APPLIED_DISCOUNTS_CELL_HEADER);

        /** @var Table $discountTable */
        $discountTable = $this->elementFactory->createElement('PromotionBackendLineItemDiscountTable', $cellElement);

        return [
            $discountTable->getRowByNumber(1)->getCellValue(self::DISCOUNT_AMOUNT_INCL_TAX_CELL_HEADER),
            $discountTable->getRowByNumber(1)->getCellValue(self::DISCOUNT_AMOUNT_EXCL_TAX_CELL_HEADER),
            $discountTable->getRowByNumber(1)->getCellValue(self::DISCOUNT_AMOUNT_CELL_HEADER)
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getProductSKU()
    {
        return $this->getCellValue('SKU');
    }
}
