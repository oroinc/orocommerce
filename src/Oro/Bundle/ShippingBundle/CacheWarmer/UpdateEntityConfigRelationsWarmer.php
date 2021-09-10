<?php

namespace Oro\Bundle\ShippingBundle\CacheWarmer;

use Symfony\Component\HttpKernel\CacheWarmer\CacheWarmerInterface;

/**
 * Removes relations of ShippingRule entity from different associations (Note, ActivityList), at the level of
 * entity config data, because this entity was removed and is not available anymore.
 */
class UpdateEntityConfigRelationsWarmer implements CacheWarmerInterface
{
    /**
     * @var EntityConfigRelationsMigration
     */
    private $entityConfigRelationsMigration;

    public function __construct(EntityConfigRelationsMigration $entityConfigRelationsMigration)
    {
        $this->entityConfigRelationsMigration = $entityConfigRelationsMigration;
    }

    /**
     * {@inheritdoc}
     */
    public function warmUp($cacheDir)
    {
        $this->entityConfigRelationsMigration->migrate();
    }

    /**
     * {@inheritdoc}
     */
    public function isOptional()
    {
        return false;
    }
}
