<?php

namespace Oro\Bundle\InventoryBundle\CacheWarmer;

use Symfony\Component\HttpKernel\CacheWarmer\CacheWarmerInterface;

/**
 * Removes relations of ShippingRul entity from different associations (Note, ActivityList), at the level of
 * entity config data, because this entity was removed and is not available anymore.
 */
class UpdateEntityConfigRelationsWarmer implements CacheWarmerInterface
{
    /**
     * @var EntityConfigRelationsMigration
     */
    private $entityConfigRelationsMigration;

    /**
     * @param EntityConfigRelationsMigration $entityConfigRelationsMigration
     */
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
