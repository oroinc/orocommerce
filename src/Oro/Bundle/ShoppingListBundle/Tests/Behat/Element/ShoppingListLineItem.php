<?php

namespace Oro\Bundle\ShoppingListBundle\Tests\Behat\Element;

use Oro\Bundle\TestFrameworkBundle\Behat\Element\Element;

class ShoppingListLineItem extends Element implements LineItemInterface
{
    /**
     * {@inheritdoc}
     */
    public function getProductSKU(): string
    {
        return $this->getElement('ShoppingListLineItemProductSku')->getText();
    }

    public function delete()
    {
        $deleteButton = $this->find('css', 'span.fa-trash-o');
        $deleteButton->click();
    }
}
