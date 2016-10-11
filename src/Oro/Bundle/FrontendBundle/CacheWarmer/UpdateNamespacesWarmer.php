<?php

namespace Oro\Bundle\FrontendBundle\CacheWarmer;

use Symfony\Component\HttpKernel\CacheWarmer\CacheWarmerInterface;

/**
 * Change namespace in all loaded migrations, fixtures and entity config data
 * It can't be done in migrations because cache warmup requires existing entities in entity config, see BAP-11101
 */
class UpdateNamespacesWarmer implements CacheWarmerInterface
{
    /**
     * @var ClassMigration
     */
    private $classMigration;

    /**
     * @param ClassMigration $classMigration
     */
    public function __construct(ClassMigration $classMigration)
    {
        $this->classMigration = $classMigration;
    }

    /**
     * {@inheritdoc}
     */
    public function warmUp($cacheDir)
    {
        $this->classMigration->migrate();
    }

    /**
     * {@inheritdoc}
     */
    public function isOptional()
    {
        return false;
    }
}
