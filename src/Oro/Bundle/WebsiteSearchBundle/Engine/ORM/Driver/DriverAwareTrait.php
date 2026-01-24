<?php

namespace Oro\Bundle\WebsiteSearchBundle\Engine\ORM\Driver;

/**
 * Provides database driver dependency injection for website search components.
 *
 * This trait enables classes to receive and access a {@see DriverInterface} instance, which is essential for executing
 * database-specific search operations in the website search ORM engine.
 * It provides a setter for dependency injection and a protected getter that enforces driver initialization before use.
 * Components that need to interact with the search database (such as query builders, indexers, or search repositories)
 * should use this trait to obtain the appropriate database driver.
 */
trait DriverAwareTrait
{
    /** @var DriverInterface */
    private $driver;

    public function setDriver(DriverInterface $driver)
    {
        $this->driver = $driver;
    }

    /**
     * @return DriverInterface
     * @throws \RuntimeException
     */
    protected function getDriver()
    {
        if (!$this->driver) {
            throw new \RuntimeException('Driver is missing');
        }

        return $this->driver;
    }
}
