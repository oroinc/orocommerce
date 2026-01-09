<?php

namespace Oro\Bundle\WebsiteSearchBundle\Engine\ORM\Driver;

use Oro\Bundle\SearchBundle\Engine\Orm\PdoPgsql as BaseDriver;

/**
 * PostgreSQL database driver implementation for the website search ORM engine.
 *
 * This driver extends the base PostgreSQL driver from {@see BaseDriver} and implements the {@see DriverInterface}
 * to provide PostgreSQL-specific search operations for the website search index.
 * It handles PostgreSQL fulltext search queries using tsvector/tsquery, index management, and data persistence
 * operations specific to the storefront search functionality.
 * The driver uses the {@see DriverTrait} to provide common operations like index alias management, entity removal,
 * and item creation.
 */
class PdoPgsql extends BaseDriver implements DriverInterface
{
    use DriverTrait;

    #[\Override]
    public function getName()
    {
        return DriverInterface::DRIVER_POSTGRESQL;
    }
}
