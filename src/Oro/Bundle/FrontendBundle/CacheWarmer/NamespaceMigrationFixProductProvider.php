<?php

namespace Oro\Bundle\FrontendBundle\CacheWarmer;

use Oro\Bundle\InstallerBundle\CacheWarmer\NamespaceMigrationProviderInterface;

class NamespaceMigrationFixProductProvider implements NamespaceMigrationProviderInterface
{
    /** @var string[] */
    protected $additionConfig
        = [
            'OroductBundle'               => 'OroProductBundle',
        ];

    /**
     * (@inheritdoc}
     */
    public function getConfig()
    {
        return $this->additionConfig;
    }
}
