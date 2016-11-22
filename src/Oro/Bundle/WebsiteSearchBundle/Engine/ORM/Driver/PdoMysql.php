<?php

namespace Oro\Bundle\WebsiteSearchBundle\Engine\ORM\Driver;

use Oro\Bundle\SearchBundle\Engine\Orm\PdoMysql as BaseDriver;

class PdoMysql extends BaseDriver implements DriverInterface
{
    use DriverTrait;

    /** {@inheritdoc} */
    public function getName()
    {
        return DriverInterface::DRIVER_MYSQL;
    }
}
