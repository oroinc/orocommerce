<?php

namespace Oro\Bundle\OrderBundle\Tests\Behat\Element;

use Oro\Bundle\TestFrameworkBundle\Behat\Element\TableRow;

class BackendOrderLineItem extends TableRow
{
    /**
     * {@inheritdoc}
     */
    public function getProductSKU()
    {
        return $this->getCellValue('SKU');
    }
}
