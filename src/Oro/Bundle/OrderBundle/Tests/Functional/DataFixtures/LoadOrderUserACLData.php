<?php

namespace Oro\Bundle\OrderBundle\Tests\Functional\DataFixtures;

use Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\AbstractLoadACLData;
use Oro\Bundle\OrderBundle\Entity\Order;

class LoadOrderUserACLData extends AbstractLoadACLData
{
    /**
     * @return string
     */
    #[\Override]
    protected function getAclResourceClassName()
    {
        return Order::class;
    }

    /**
     * @return array
     */
    #[\Override]
    protected function getSupportedRoles()
    {
        return [
            self::ROLE_BASIC,
            self::ROLE_LOCAL,
            self::ROLE_LOCAL_VIEW_ONLY,
            self::ROLE_DEEP,
            self::ROLE_DEEP_VIEW_ONLY,
        ];
    }
}
