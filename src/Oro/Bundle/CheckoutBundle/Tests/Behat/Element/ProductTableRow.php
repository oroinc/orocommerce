<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Behat\Element;

use Oro\Bundle\ShoppingListBundle\Tests\Behat\Element\ConfigurableProductTableRowAwareInterface;
use Oro\Bundle\TestFrameworkBundle\Behat\Element\TableRow;

class ProductTableRow extends TableRow implements ConfigurableProductTableRowAwareInterface
{
    /**
     * {@inheritdoc}
     */
    public function clickProductLink(): void
    {
        $this->getElement('CheckoutProductViewLink')->click();
    }

    /**
     * {@inheritdoc}
     */
    public function isRowContainingAttributes(array $attributeLabels): bool
    {
        foreach ($attributeLabels as $attributeLabel) {
            $attributeElement = $this->findElementContains('Checkout Line Item Product Attribute', $attributeLabel);

            if (!$attributeElement->isValid()) {
                return false;
            }
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function getProductSku(): string
    {
        return $this->getElement('CheckoutStepLineItemProductSku')->getText();
    }
}
