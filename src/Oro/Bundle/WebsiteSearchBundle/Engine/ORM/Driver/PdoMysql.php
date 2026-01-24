<?php

namespace Oro\Bundle\WebsiteSearchBundle\Engine\ORM\Driver;

use Oro\Bundle\SearchBundle\Engine\Orm\PdoMysql as BaseDriver;

/**
 * MySQL database driver implementation for the website search ORM engine.
 *
 * This driver extends the base MySQL driver from {@see BaseDriver} and implements the {@see DriverInterface}
 * to provide MySQL-specific search operations for the website search index.
 * It handles MySQL fulltext search queries, index management, and data persistence operations
 * specific to the storefront search functionality. The driver uses the {@see DriverTrait} to provide common operations
 * like index alias management, entity removal, and item creation.
 */
class PdoMysql extends BaseDriver implements DriverInterface
{
    use DriverTrait;

    #[\Override]
    public function getName()
    {
        return DriverInterface::DRIVER_MYSQL;
    }
}
