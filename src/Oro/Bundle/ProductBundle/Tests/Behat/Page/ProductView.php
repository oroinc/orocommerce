<?php

namespace Oro\Bundle\ProductBundle\Tests\Behat\Page;

use Oro\Bundle\TestFrameworkBundle\Behat\Element\Page;

class ProductView extends Page
{
    /**
     * {@inheritdoc}
     */
    public function open(array $parameters = [])
    {
        $this->getMainMenu()->openAndClick('Products/Products');
    }
}
