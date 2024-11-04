<?php

namespace Oro\Bundle\ShoppingListBundle\Tests\Behat\Element;

use Oro\Bundle\TestFrameworkBundle\Behat\Element\TableRow;

class ProductTableRow extends TableRow implements ConfigurableProductTableRowAwareInterface
{
    #[\Override]
    public function clickProductLink(): void
    {
        $this->getElement('Shopping List Line Item Product View Link')->click();
    }

    #[\Override]
    public function isRowContainingAttributes(array $attributeLabels): bool
    {
        foreach ($attributeLabels as $attributeLabel) {
            $attributeElement =
                $this->findElementContains('Shopping List Line Item Product Attribute', $attributeLabel);

            if (!$attributeElement->isValid()) {
                return false;
            }
        }

        return true;
    }

    #[\Override]
    public function getProductSku(): string
    {
        return $this->getElement('ShoppingListLineItemProductSku')->getText();
    }
}
