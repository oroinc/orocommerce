<?php

namespace OroB2B\Bundle\OrderBundle\Tests\Functional\Form\Type;

use OroB2B\Bundle\ProductBundle\Tests\Functional\Form\Type\AbstractProductSelectTypeTest;

/**
 * @dbIsolation
 */
class ProductSelectTypeTest extends AbstractProductSelectTypeTest
{
    /** @var string */
    protected $scope = 'order';

    /** @var string */
    protected $configPath = 'oro_b2b_order.product_visibility.value';

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
