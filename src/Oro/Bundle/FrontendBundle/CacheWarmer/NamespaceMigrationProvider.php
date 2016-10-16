<?php

namespace Oro\Bundle\FrontendBundle\CacheWarmer;

use Oro\Bundle\InstallerBundle\CacheWarmer\NamespaceMigrationProviderInterface;

class NamespaceMigrationProvider implements NamespaceMigrationProviderInterface
{
    /** @var string[] */
    protected $additionConfig
        = [
            'OroB2B\Bundle\AccountBundle' => 'OroB2B\Bundle\CustomerBundle',
            'OroB2BAccountBundle'         => 'OroB2BCustomerBundle',
            'Oro\Bundle\AccountBundle'    => 'Oro\Bundle\CustomerBundle',
            'OroAccountBundle'            => 'OroCustomerBundle',
            'oro.account'                 => 'oro.customer',
            'OroB2B'                      => 'Oro',
            'orob2b'                      => 'oro',
        ];

    /**
     * (@inheritdoc}
     */
    public function getConfig()
    {
        return $this->additionConfig;
    }
}
