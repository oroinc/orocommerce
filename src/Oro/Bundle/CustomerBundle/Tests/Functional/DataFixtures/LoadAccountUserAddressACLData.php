<?php

namespace Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures;

use Oro\Bundle\CustomerBundle\Entity\AccountUserAddress;

class LoadAccountUserAddressACLData extends AbstractLoadACLData
{
    /**
     * @return string
     */
    protected function getAclResourceClassName()
    {
        return AccountUserAddress::class;
    }

    /**
     * @return array
     */
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
