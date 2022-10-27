<?php

namespace Oro\Bundle\ShoppingListBundle\Tests\Behat\Element;

interface ConfigurableProductTableRowAwareInterface
{
    public function clickProductLink(): void;

    public function isRowContainingAttributes(array $attributeLabels): bool;

    public function getProductSku(): string;
}
