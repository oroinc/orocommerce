<?php

namespace Oro\Bundle\WebsiteSearchBundle\Engine\ORM\Driver;

use Oro\Bundle\SearchBundle\Engine\Orm\QueryBuilderCreatorInterface;

/**
 * This trait contains useful methods for ORM search drivers.
 */
trait DriverAwareTrait
{
    /** @var DriverInterface|QueryBuilderCreatorInterface */
    private $driver;

    public function setDriver(DriverInterface $driver)
    {
        $this->driver = $driver;
    }

    /**
     * @return DriverInterface|QueryBuilderCreatorInterface
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
