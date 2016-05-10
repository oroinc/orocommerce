<?php

namespace OroB2B\Bundle\ProductBundle\Provider;

use Oro\Bundle\ConfigBundle\Config\CongifManager;

class DefaultProductUnitProvider
{
    private $configManager;

    /** @var \PHPUnit_Framework_MockObject_MockObject|CongifManager $configManager */
    public function __construct(CongifManager $configManager)
    {
        $this->configManager = $configManager;
    }
    /**
     * @return array
     */
    public function getDefaultProductUnit()
    {
        return
            ['default_unit'=>$this->configManager->get('orob2b_product.default_unit'),
             'default_unit_precision'=>$this->configManager->get('orob2b_product.default_unit_precision')
            ];
    }
}

