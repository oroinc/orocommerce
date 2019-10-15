<?php

namespace Oro\Bundle\ShoppingListBundle\Tests\Behat\Element;

interface ConfigurableProductTableRowAwareInterface
{
    public function clickProductLink(): void;

    /**
     * @param array $attributeLabels
     * @return bool
     */
    public function isRowContainingAttributes(array $attributeLabels): bool;

    /**
     * @return string
     */
    public function getProductSku(): string;
}
