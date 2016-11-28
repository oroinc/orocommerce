<?php

namespace Oro\Bundle\WebsiteSearchBundle\Engine\ORM\Driver;

use Oro\Bundle\SearchBundle\Engine\Orm\PdoPgsql as BaseDriver;

class PdoPgsql extends BaseDriver implements DriverInterface
{
    use DriverTrait;

    /** {@inheritdoc} */
    public function getName()
    {
        return DriverInterface::DRIVER_POSTGRESQL;
    }
}
