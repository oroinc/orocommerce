<?php

namespace Oro\Bundle\FrontendBundle\CacheWarmer;

use Oro\Bundle\InstallerBundle\CacheWarmer\NamespaceMigrationProviderInterface;

class NamespaceMigrationProvider implements NamespaceMigrationProviderInterface
{
    /** @var string[] */
    protected $additionConfig
        = [
            'OroB2B\Bundle\AccountBundle'       => 'OroB2B\Bundle\CustomerBundle',
            'OroB2BAccountBundle'               => 'OroB2BCustomerBundle',
            'Oro\Bundle\AccountBundle'          => 'Oro\Bundle\CustomerBundle',
            'OroAccountBundle'                  => 'OroCustomerBundle',
            'AccountBundle'                     => 'CustomerBundle',
            'oro.account.entity_plural_label'   => 'oro.customer.account.entity_plural_label',
            'oro.account.entity_label'          => 'oro.customer.account.entity_label',
            'oro.account.id'                    => 'oro.customer.account.id',
            'oro.account.name'                  => 'oro.customer.account.name',
            'oro.account.addresses'             => 'oro.customer.account.addresses',
            'oro.account.children'              => 'oro.customer.account.children',
            'oro.account.group'                 => 'oro.customer.account.group',
            'oro.account.internal_rating'       => 'oro.customer.account.internal_rating',
            'oro.account.organization'          => 'oro.customer.account.organization',
            'oro.account.owner'                 => 'oro.customer.account.owner',
            'oro.account.parent'                => 'oro.customer.account.parent',
            'oro.account.sales_representatives' => 'oro.customer.account.sales_representatives',
            'oro.account.users'                 => 'oro.customer.account.users',
            'oro.account'                       => 'oro.customer',
            'OroB2B'                            => 'Oro',
            'orob2b'                            => 'oro',
        ];

    /**
     * (@inheritdoc}
     */
    public function getConfig()
    {
        return $this->additionConfig;
    }
}
