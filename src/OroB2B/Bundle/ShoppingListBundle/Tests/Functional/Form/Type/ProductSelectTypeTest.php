<?php

namespace OroB2B\Bundle\ShoppingListBundle\Tests\Functional\Autocomlete;

use OroB2B\Bundle\ProductBundle\Tests\Functional\Form\Type\AbstractProductSelectTypeTest;

/**
 * @dbIsolation
 */
class ProductSelectTypeTest extends AbstractProductSelectTypeTest
{
    /** @var string  */
    protected $scope = 'shopping_list';

    /** @var string  */
    protected $configPath = 'oro_b2b_shopping_list.backend_product_visibility';

    /**
     * {@inheritdoc}
     */
    public function getScope()
    {
        return $this->scope;
    }

    /**
     * {@inheritdoc}
     */
    public function getConfigPath()
    {
        return $this->configPath;
    }
}
