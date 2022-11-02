<?php

namespace Oro\Bundle\ShoppingListBundle\Tests\Behat\Element;

interface ConfigurableProductAwareInterface
{
    /**
     * @return ConfigurableProductTableRowAwareInterface[]|array
     */
    public function getProductRows(): array;
}
