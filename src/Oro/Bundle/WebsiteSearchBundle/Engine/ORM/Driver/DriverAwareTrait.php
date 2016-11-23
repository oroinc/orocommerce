<?php

namespace Oro\Bundle\WebsiteSearchBundle\Engine\ORM\Driver;

trait DriverAwareTrait
{
    /** @var DriverInterface */
    private $driver;

    /**
     * @param DriverInterface $driver
     */
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
