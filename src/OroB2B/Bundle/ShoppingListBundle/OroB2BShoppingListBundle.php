<?php

namespace OroB2B\Bundle\ShoppingListBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;

use OroB2B\Bundle\ShoppingListBundle\DependencyInjection\OroB2BShoppingListExtension;

class OroB2BShoppingListBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function getContainerExtension()
    {
        if (!$this->extension) {
            $this->extension = new OroB2BShoppingListExtension();
        }

        return $this->extension;
    }
}
