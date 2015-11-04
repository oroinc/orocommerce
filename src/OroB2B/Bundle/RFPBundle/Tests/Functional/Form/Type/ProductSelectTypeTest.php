<?php

namespace OroB2B\Bundle\RFPBundle\Tests\Functional\Form\Type;

use OroB2B\Bundle\ProductBundle\Tests\Functional\Form\Type\AbstractProductSelectTypeTest;

/**
 * @dbIsolation
 */
class ProductSelectTypeTest extends AbstractProductSelectTypeTest
{
    /** @var string */
    protected $scope = 'rfp';

    /** @var string */
    protected $configPath = 'oro_b2b_rfp.backend_product_visibility';

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
