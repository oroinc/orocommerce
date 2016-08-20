<?php

namespace Oro\Bundle\ShoppingListBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;

use Oro\Bundle\ShoppingListBundle\DependencyInjection\OroShoppingListExtension;

class OroShoppingListBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function getContainerExtension()
    {
        if (!$this->extension) {
            $this->extension = new OroShoppingListExtension();
        }

        return $this->extension;
    }
}
