<?php

namespace Oro\Bundle\ShoppingListBundle\Tests\Behat\Element;

use Oro\Bundle\TestFrameworkBundle\Behat\Element\Table;

class ProductTable extends Table implements ConfigurableProductAwareInterface
{
    private const PRODUCT_TABLE_ROW_ELEMENT = 'ShoppingListProductTableRow';

    /**
     * {@inheritdoc}
     */
    public function getProductRows(): array
    {
        return $this->getElements(self::PRODUCT_TABLE_ROW_ELEMENT);
    }
}
