<?php

namespace Oro\Bundle\OrderBundle\Tests\Behat\Element;

use Behat\Mink\Element\NodeElement;
use Oro\Bundle\DataGridBundle\Tests\Behat\Element\GridRow;

class BackendOrderLineItem extends GridRow
{
    /**
     * {@inheritdoc}
     */
    public function getProductSKU()
    {
        return $this->getCellValue('SKU');
    }

    /**
     * @param string $action text of button - Create, Edit, Delete etc.
     * @return NodeElement|null
     */
    public function findActionLink($action)
    {
        $link = $this->find('xpath', sprintf('//button[contains(@class,"%s")]', strtolower($action)));

        static::assertNotNull(
            $link,
            sprintf('BackendOrderLineItem "%s" has no "%s" action', $this->getText(), $action)
        );

        return $link;
    }
}
