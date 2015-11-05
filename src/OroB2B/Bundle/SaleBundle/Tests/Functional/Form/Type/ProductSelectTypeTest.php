<?php

namespace OroB2B\Bundle\SaleBundle\Tests\Functional\Form\Type;

use OroB2B\Bundle\ProductBundle\Tests\Functional\Form\Type\AbstractProductSelectTypeTest;

/**
 * @dbIsolation
 */
class ProductSelectTypeTest extends AbstractProductSelectTypeTest
{
    /** @var string */
    protected $scope = 'quote';

    /** @var string */
    protected $configPath = 'oro_b2b_sale.product_visibility.value';

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
